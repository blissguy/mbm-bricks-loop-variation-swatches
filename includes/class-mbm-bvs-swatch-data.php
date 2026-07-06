<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves swatch data using the exact same term meta model as Bricks'
 * native variation swatches (Bricks 2.0+):
 *
 * - Attribute-level meta (stored against the WooCommerce attribute ID):
 *   bricks_swatch_type, bricks_swatch_default_color, bricks_swatch_default_label,
 *   bricks_swatch_default_image, bricks_swatch_use_variation_image
 * - Term-level meta: bricks_swatch_color_value, bricks_swatch_label_value,
 *   bricks_swatch_image_value
 *
 * Fallback chains match Bricks' add-to-cart swatch renderer:
 * color: term color -> attribute default color -> #ffffff
 * label: term label -> attribute default label -> term name
 * image: term image -> variation image (if enabled on attribute) -> attribute default image -> Woo placeholder
 */
final class MBM_BVS_Swatch_Data {

	const TYPE_COLOR = 'color';
	const TYPE_LABEL = 'label';
	const TYPE_IMAGE = 'image';

	/* Per-request caches */
	private static $attribute_ids        = [];
	private static $swatch_types         = [];
	private static $attribute_defaults   = [];
	private static $term_order_maps      = [];
	private static $variation_image_maps = [];

	/**
	 * Swatches are driven by the same Bricks setting that powers the native
	 * add-to-cart swatches, so both stay in sync site-wide.
	 */
	public static function is_enabled() {
		$enabled = class_exists( '\Bricks\Database' ) && (bool) \Bricks\Database::get_setting( 'woocommerceUseVariationSwatches' );

		return (bool) apply_filters( 'mbm_bvs/swatches_enabled', $enabled );
	}

	/**
	 * WooCommerce attribute ID for a pa_* taxonomy (0 for custom attributes).
	 */
	public static function get_attribute_id( $taxonomy ) {
		if ( ! isset( self::$attribute_ids[ $taxonomy ] ) ) {
			self::$attribute_ids[ $taxonomy ] = taxonomy_is_product_attribute( $taxonomy )
				? (int) wc_attribute_taxonomy_id_by_name( str_replace( 'pa_', '', $taxonomy ) )
				: 0;
		}

		return self::$attribute_ids[ $taxonomy ];
	}

	/**
	 * Attribute-level swatch type: 'color', 'label', 'image', or '' (none set).
	 * Custom (non-taxonomy) attributes have no swatch config and fall back to labels.
	 */
	public static function get_swatch_type( $taxonomy ) {
		if ( ! isset( self::$swatch_types[ $taxonomy ] ) ) {
			$attribute_id = self::get_attribute_id( $taxonomy );

			self::$swatch_types[ $taxonomy ] = $attribute_id
				? (string) get_term_meta( $attribute_id, 'bricks_swatch_type', true )
				: '';
		}

		return self::$swatch_types[ $taxonomy ];
	}

	/**
	 * Attribute-level fallback values.
	 */
	public static function get_attribute_defaults( $taxonomy ) {
		if ( ! isset( self::$attribute_defaults[ $taxonomy ] ) ) {
			$attribute_id = self::get_attribute_id( $taxonomy );

			self::$attribute_defaults[ $taxonomy ] = [
				'color'               => $attribute_id ? (string) get_term_meta( $attribute_id, 'bricks_swatch_default_color', true ) : '',
				'label'               => $attribute_id ? (string) get_term_meta( $attribute_id, 'bricks_swatch_default_label', true ) : '',
				'image'               => $attribute_id ? (int) get_term_meta( $attribute_id, 'bricks_swatch_default_image', true ) : 0,
				'use_variation_image' => $attribute_id && get_term_meta( $attribute_id, 'bricks_swatch_use_variation_image', true ),
			];
		}

		return self::$attribute_defaults[ $taxonomy ];
	}

	/**
	 * Sort option slugs by the attribute's configured term order
	 * (mirrors Bricks 2.3 swatch option sorting).
	 */
	public static function sort_values( $taxonomy, $values ) {
		if ( empty( $values ) || ! taxonomy_exists( $taxonomy ) ) {
			return $values;
		}

		if ( ! isset( self::$term_order_maps[ $taxonomy ] ) ) {
			$orderby      = 'menu_order';
			$attribute_id = self::get_attribute_id( $taxonomy );

			if ( $attribute_id ) {
				$attribute = wc_get_attribute( $attribute_id );

				if ( $attribute && ! empty( $attribute->order_by ) ) {
					$orderby = $attribute->order_by;
				}
			}

			$terms = get_terms(
				[
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'orderby'    => $orderby,
					'order'      => 'ASC',
				]
			);

			$map = [];

			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $index => $term ) {
					$map[ $term->slug ] = $index;
				}
			}

			self::$term_order_maps[ $taxonomy ] = $map;
		}

		$map = self::$term_order_maps[ $taxonomy ];

		if ( empty( $map ) ) {
			return $values;
		}

		usort(
			$values,
			function ( $a, $b ) use ( $map ) {
				$pos_a = isset( $map[ $a ] ) ? $map[ $a ] : PHP_INT_MAX;
				$pos_b = isset( $map[ $b ] ) ? $map[ $b ] : PHP_INT_MAX;

				return $pos_a <=> $pos_b;
			}
		);

		return $values;
	}

	/**
	 * Map of taxonomy => value slug => variation image attachment ID.
	 */
	public static function get_variation_image_map( $product ) {
		$product_id = $product->get_id();

		if ( ! isset( self::$variation_image_maps[ $product_id ] ) ) {
			$map = [];

			foreach ( $product->get_children() as $child_id ) {
				$variation = wc_get_product( $child_id );

				if ( ! $variation ) {
					continue;
				}

				$image_id = (int) $variation->get_image_id();

				if ( $image_id <= 0 ) {
					continue;
				}

				foreach ( $variation->get_attributes() as $attr_key => $attr_value ) {
					$attr_value = (string) $attr_value;

					if ( $attr_key === '' || $attr_value === '' || isset( $map[ $attr_key ][ $attr_value ] ) ) {
						continue;
					}

					$map[ $attr_key ][ $attr_value ] = $image_id;
				}
			}

			self::$variation_image_maps[ $product_id ] = $map;
		}

		return self::$variation_image_maps[ $product_id ];
	}

	/**
	 * Resolve one attribute of a variable product into render-ready swatch items.
	 *
	 * @param WC_Product_Variable $product  Parent product.
	 * @param string              $taxonomy pa_* taxonomy, or the raw name of a custom attribute.
	 * @param array               $values   Option slugs (taxonomy) or raw values (custom).
	 * @return array{type: string, label: string, items: array}
	 */
	public static function resolve_group( $product, $taxonomy, $values ) {
		$is_taxonomy = taxonomy_exists( $taxonomy );
		$type        = $is_taxonomy ? self::get_swatch_type( $taxonomy ) : '';

		// No swatch type configured: render plain text labels.
		if ( ! in_array( $type, [ self::TYPE_COLOR, self::TYPE_LABEL, self::TYPE_IMAGE ], true ) ) {
			$type = self::TYPE_LABEL;
		}

		if ( $is_taxonomy ) {
			$values = self::sort_values( $taxonomy, $values );
		}

		$defaults = $is_taxonomy ? self::get_attribute_defaults( $taxonomy ) : [];
		$items    = [];

		foreach ( $values as $value ) {
			$value = (string) $value;
			$term  = $is_taxonomy ? get_term_by( 'slug', $value, $taxonomy ) : false;
			$name  = $term ? $term->name : $value;

			$item = [
				'value' => $value,
				'name'  => $name,
			];

			switch ( $type ) {
				case self::TYPE_COLOR:
					$color = $term ? (string) get_term_meta( $term->term_id, 'bricks_swatch_color_value', true ) : '';

					if ( $color === '' || $color === 'none' || ! sanitize_hex_color( $color ) ) {
						$default_color = isset( $defaults['color'] ) ? $defaults['color'] : '';
						$color         = ( $default_color && sanitize_hex_color( $default_color ) ) ? $default_color : '#ffffff';
					}

					$item['color'] = $color;
					break;

				case self::TYPE_IMAGE:
					$image_id = $term ? (int) get_term_meta( $term->term_id, 'bricks_swatch_image_value', true ) : 0;

					if ( ! $image_id && ! empty( $defaults['use_variation_image'] ) ) {
						$image_map = self::get_variation_image_map( $product );

						if ( isset( $image_map[ $taxonomy ][ $value ] ) ) {
							$image_id = $image_map[ $taxonomy ][ $value ];
						}
					}

					if ( ! $image_id && ! empty( $defaults['image'] ) ) {
						$image_id = (int) $defaults['image'];
					}

					$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';

					$item['image_url'] = $image_url ? $image_url : wc_placeholder_img_src( 'thumbnail' );
					break;

				case self::TYPE_LABEL:
				default:
					$label = $term ? (string) get_term_meta( $term->term_id, 'bricks_swatch_label_value', true ) : '';

					if ( $label === '' && ! empty( $defaults['label'] ) ) {
						$label = $defaults['label'];
					}

					$item['label'] = $label !== '' ? $label : $name;
					break;
			}

			$items[] = $item;
		}

		return [
			'type'  => $type,
			'label' => wc_attribute_label( $taxonomy, $product ),
			'items' => $items,
		];
	}
}
