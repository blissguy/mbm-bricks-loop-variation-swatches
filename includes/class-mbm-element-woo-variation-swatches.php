<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Normalize Woo attribute keys (attribute_pa_color -> pa_color) */
function mbm_bvs_normalize_attribute_key( $key ) {
	$key = (string) $key;
	return preg_replace( '/^attribute_/', '', $key );
}

/* Basic CSS value hardening for inline CSS vars */
function mbm_bvs_safe_css_value( $value ) {
	$value = sanitize_text_field( (string) $value );
	$value = str_replace( array( ';', '{', '}', '"', "'" ), '', $value );
	return trim( $value );
}

/* Get global attribute taxonomies as select options (pa_color => Color) */
function mbm_bvs_get_global_attribute_options() {
	$options = array();

	if ( ! function_exists( 'wc_get_attribute_taxonomy_names' ) ) {
		return $options;
	}

	$taxonomies = wc_get_attribute_taxonomy_names();

	foreach ( $taxonomies as $taxonomy ) {
		$taxonomy_obj = get_taxonomy( $taxonomy );

		if ( $taxonomy_obj && ! empty( $taxonomy_obj->labels->singular_name ) ) {
			$options[ $taxonomy ] = $taxonomy_obj->labels->singular_name;
			continue;
		}

		$options[ $taxonomy ] = $taxonomy;
	}

	return $options;
}

/* Cache variation attributes per request */
function mbm_bvs_get_variation_attributes_cached( $product ) {
	if ( ! $product || ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
		return array();
	}

	$product_id = (int) $product->get_id();
	$cache_key  = 'variation_attributes_' . $product_id;

	$cached = wp_cache_get( $cache_key, 'mbm_bvs' );

	if ( false !== $cached ) {
		return is_array( $cached ) ? $cached : array();
	}

	if ( ! method_exists( $product, 'get_variation_attributes' ) ) {
		return array();
	}

	$attrs = $product->get_variation_attributes();

	wp_cache_set( $cache_key, $attrs, 'mbm_bvs', 300 );

	return is_array( $attrs ) ? $attrs : array();
}

/* CONFIRMED: term image attachment ID stored in bricks_swatch_image_value */
function mbm_bvs_get_term_image_url_strict( $term_id ) {
	$raw = get_term_meta( (int) $term_id, 'bricks_swatch_image_value', true );

	$raw = is_string( $raw ) ? trim( $raw ) : (string) $raw;

	if ( $raw === '' || ! ctype_digit( $raw ) ) {
		return '';
	}

	$url = wp_get_attachment_image_url( (int) $raw, 'thumbnail' );

	return is_string( $url ) ? $url : '';
}

/* Build attr_value -> variation image URL map (only used when bricks_swatch_use_variation_image = 1) */
function mbm_bvs_get_variation_image_map_cached( $product ) {
	if ( ! $product || ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
		return array();
	}

	$product_id = (int) $product->get_id();
	$cache_key  = 'variation_image_map_' . $product_id;

	$cached = wp_cache_get( $cache_key, 'mbm_bvs' );

	if ( false !== $cached ) {
		return is_array( $cached ) ? $cached : array();
	}

	$map = array();

	if ( ! method_exists( $product, 'get_children' ) ) {
		return $map;
	}

	$child_ids = $product->get_children();

	foreach ( $child_ids as $child_id ) {
		$variation = wc_get_product( $child_id );

		if ( ! $variation || ! is_object( $variation ) || ! method_exists( $variation, 'get_image_id' ) ) {
			continue;
		}

		$image_id = (int) $variation->get_image_id();

		if ( $image_id <= 0 ) {
			continue;
		}

		$image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );

		if ( ! is_string( $image_url ) || $image_url === '' ) {
			continue;
		}

		$attrs = method_exists( $variation, 'get_attributes' ) ? $variation->get_attributes() : array();

		if ( ! is_array( $attrs ) ) {
			continue;
		}

		foreach ( $attrs as $attr_key => $attr_value ) {
			$attr_key = sanitize_key( mbm_bvs_normalize_attribute_key( $attr_key ) );
			$val      = is_string( $attr_value ) ? trim( $attr_value ) : '';

			if ( $attr_key === '' || $val === '' ) {
				continue;
			}

			if ( empty( $map[ $attr_key ] ) ) {
				$map[ $attr_key ] = array();
			}

			if ( isset( $map[ $attr_key ][ $val ] ) ) {
				continue;
			}

			$map[ $attr_key ][ $val ] = $image_url;
		}
	}

	wp_cache_set( $cache_key, $map, 'mbm_bvs', 300 );

	return $map;
}

class MBM_Element_Woo_Variation_Swatches extends \Bricks\Element {
	public $category     = 'mbm-woo';
	public $name         = 'mbm-woo-variation-swatches';
	public $icon         = 'ti-paint-bucket';
	public $css_selector = '.mbm-variation-swatches';

	public function get_label() {
		return esc_html__( 'Loop Variation Swatches', 'mbm-bricks-variation-swatches' );
	}

	public function set_control_groups() {
		$this->control_groups['attributes'] = array(
			'title' => esc_html__( 'Attributes', 'mbm-bricks-variation-swatches' ),
			'tab'   => 'content',
		);

		$this->control_groups['layout'] = array(
			'title' => esc_html__( 'Layout', 'mbm-bricks-variation-swatches' ),
			'tab'   => 'content',
		);

		/* Attribute style controls (per type) */
		$this->control_groups['attribute_style_label'] = array(
			'title' => esc_html__( 'Attribute Style: Label', 'mbm-bricks-variation-swatches' ),
			'tab'   => 'content',
		);

		$this->control_groups['attribute_style_color'] = array(
			'title' => esc_html__( 'Attribute Style: Color', 'mbm-bricks-variation-swatches' ),
			'tab'   => 'content',
		);

		$this->control_groups['attribute_style_image'] = array(
			'title' => esc_html__( 'Attribute Style: Image', 'mbm-bricks-variation-swatches' ),
			'tab'   => 'content',
		);

		$this->control_groups['attribute_style_group_label'] = array(
			'title' => esc_html__( 'Attribute Group Label', 'mbm-bricks-variation-swatches' ),
			'tab'   => 'content',
		);
	}

	public function set_controls() {
		$attribute_options = mbm_bvs_get_global_attribute_options();

		/* Attribute selection (no display mode in UI; render is AUTO only) */
		$this->controls['attributes'] = array(
			'tab'           => 'content',
			'group'         => 'attributes',
			'label'         => esc_html__( 'Attributes to display', 'mbm-bricks-variation-swatches' ),
			'type'          => 'repeater',
			'titleProperty' => 'attribute',
			'fields'        => array(
				'attribute' => array(
					'label'       => esc_html__( 'Attribute', 'mbm-bricks-variation-swatches' ),
					'type'        => 'select',
					'options'     => array_merge(
						array(
							'custom' => esc_html__( 'Custom attribute (type name)', 'mbm-bricks-variation-swatches' ),
						),
						$attribute_options
					),
					'placeholder' => esc_html__( 'Select attribute', 'mbm-bricks-variation-swatches' ),
					'searchable'  => true,
					'clearable'   => false,
				),

				'custom_attribute' => array(
					'label'       => esc_html__( 'Custom attribute name', 'mbm-bricks-variation-swatches' ),
					'type'        => 'text',
					'placeholder' => esc_html__( 'e.g. size', 'mbm-bricks-variation-swatches' ),
					'required'    => array( 'attribute', '=', 'custom' ),
				),

				'show_label' => array(
					'label'   => esc_html__( 'Show attribute label', 'mbm-bricks-variation-swatches' ),
					'type'    => 'checkbox',
					'default' => true,
				),

				'limit' => array(
					'label'       => esc_html__( 'Max values (0 = all)', 'mbm-bricks-variation-swatches' ),
					'type'        => 'text',
					'placeholder' => '0',
					'default'     => '0',
				),

				'min_values' => array(
					'label'       => esc_html__( 'Min values to render', 'mbm-bricks-variation-swatches' ),
					'type'        => 'number',
					'placeholder' => '0',
					'default'     => 0,
					'min'         => 0,
					'description' => esc_html__( 'Only render when more than this many values exist (0 = always render)', 'mbm-bricks-variation-swatches' ),
				),
			),
		);

		/* Layout */
		$this->controls['listDirection'] = array(
			'tab'   => 'content',
			'group' => 'layout',
			'label' => esc_html__( 'List direction', 'mbm-bricks-variation-swatches' ),
			'type'  => 'direction',
			'css'   => array(
				array(
					'property' => 'flex-direction',
					'selector' => '.mbm-variation-swatches__list',
				),
			),
		);

		$this->controls['listJustify'] = array(
			'tab'   => 'content',
			'group' => 'layout',
			'label' => esc_html__( 'Justify content', 'mbm-bricks-variation-swatches' ),
			'type'  => 'justify-content',
			'css'   => array(
				array(
					'property' => 'justify-content',
					'selector' => '.mbm-variation-swatches__list',
				),
			),
		);

		$this->controls['listAlign'] = array(
			'tab'   => 'content',
			'group' => 'layout',
			'label' => esc_html__( 'Align items', 'mbm-bricks-variation-swatches' ),
			'type'  => 'align-items',
			'css'   => array(
				array(
					'property' => 'align-items',
					'selector' => '.mbm-variation-swatches__list',
				),
			),
		);

		$this->controls['listGap'] = array(
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'List gap', 'mbm-bricks-variation-swatches' ),
			'type'        => 'text',
			'placeholder' => '6px',
			'css'         => array(
				array(
					'property' => 'gap',
					'selector' => '.mbm-variation-swatches__list',
				),
			),
		);

		/*
		 * Attribute group label styling (applies to all types).
		 *
		 * Typography control docs:
		 * https://academy.bricksbuilder.io/article/typography-control/
		 */
		$this->controls['groupLabelTypography'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_group_label',
			'label' => esc_html__( 'Typography', 'mbm-bricks-variation-swatches' ),
			'type'  => 'typography',
			'css'   => array(
				array(
					'property' => 'typography',
					'selector' => '.mbm-variation-swatches__group-label',
				),
			),
		);

		$this->controls['groupLabelBackground'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_group_label',
			'label' => esc_html__( 'Background', 'mbm-bricks-variation-swatches' ),
			'type'  => 'background',
			'css'   => array(
				array(
					'property' => 'background',
					'selector' => '.mbm-variation-swatches__group-label',
				),
			),
		);

		/*
		 * Helper: type-scoped selectors.
		 * We rely on a deterministic class added in render():
		 * .mbm-variation-swatches__group[data-swatch-type="{label|color|image}"]
		 */
		/*
		 * Reduce CSS specificity for Bricks-generated, instance-scoped styles.
		 * Bricks will scope rules with .brxe-<id> for each element instance.
		 * Wrapping the target selector in :where(...) keeps specificity low, making it easier
		 * to override with custom CSS.
		 */
		$sel_label_group = ':where(.mbm-variation-swatches__group[data-swatch-type="label"])';
		$sel_color_group = ':where(.mbm-variation-swatches__group[data-swatch-type="color"])';
		$sel_image_group = ':where(.mbm-variation-swatches__group[data-swatch-type="image"])';

		$sel_group_list_suffix = ' .mbm-variation-swatches__list';

		/* Attribute Style: Label (layout - group) */
		$this->controls['labelGroupDirection'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_label',
			'label' => esc_html__( 'Group direction', 'mbm-bricks-variation-swatches' ),
			'type'  => 'direction',
			'css'   => array(
				array(
					'property' => '--mbm-group-direction',
					'selector' => $sel_label_group,
				),
			),
		);

		$this->controls['labelGroupJustify'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_label',
			'label' => esc_html__( 'Group justify content', 'mbm-bricks-variation-swatches' ),
			'type'  => 'justify-content',
			'css'   => array(
				array(
					'property' => 'justify-content',
					'selector' => $sel_label_group,
				),
			),
		);

		$this->controls['labelGroupAlign'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_label',
			'label' => esc_html__( 'Group align items', 'mbm-bricks-variation-swatches' ),
			'type'  => 'align-items',
			'css'   => array(
				array(
					'property' => 'align-items',
					'selector' => $sel_label_group,
				),
			),
		);

		$this->controls['labelGroupGap'] = array(
			'tab'         => 'style',
			'group'       => 'attribute_style_label',
			'label'       => esc_html__( 'Group gap', 'mbm-bricks-variation-swatches' ),
			'type'        => 'text',
			'placeholder' => '6px',
			'css'         => array(
				array(
					'property' => 'gap',
					'selector' => $sel_label_group,
				),
			),
		);

		$this->controls['labelGroupWrap'] = array(
			'tab'     => 'style',
			'group'   => 'attribute_style_label',
			'label'   => esc_html__( 'Group wrap', 'mbm-bricks-variation-swatches' ),
			'type'    => 'select',
			'options' => array(
				'nowrap'       => esc_html__( 'No wrap', 'mbm-bricks-variation-swatches' ),
				'wrap'         => esc_html__( 'Wrap', 'mbm-bricks-variation-swatches' ),
				'wrap-reverse' => esc_html__( 'Wrap reverse', 'mbm-bricks-variation-swatches' ),
			),
			'default' => 'nowrap',
			'css'     => array(
				array(
					'property' => 'flex-wrap',
					'selector' => $sel_label_group,
				),
			),
		);

		/* Attribute Style: Label (layout - list) */
		$this->controls['labelListDirection'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_label',
			'label' => esc_html__( 'List direction', 'mbm-bricks-variation-swatches' ),
			'type'  => 'direction',
			'css'   => array(
				array(
					'property' => 'flex-direction',
					'selector' => $sel_label_group . $sel_group_list_suffix,
				),
			),
		);

		$this->controls['labelListJustify'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_label',
			'label' => esc_html__( 'List justify content', 'mbm-bricks-variation-swatches' ),
			'type'  => 'justify-content',
			'css'   => array(
				array(
					'property' => 'justify-content',
					'selector' => $sel_label_group . $sel_group_list_suffix,
				),
			),
		);

		$this->controls['labelListAlign'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_label',
			'label' => esc_html__( 'List align items', 'mbm-bricks-variation-swatches' ),
			'type'  => 'align-items',
			'css'   => array(
				array(
					'property' => 'align-items',
					'selector' => $sel_label_group . $sel_group_list_suffix,
				),
			),
		);

		$this->controls['labelListGap'] = array(
			'tab'         => 'style',
			'group'       => 'attribute_style_label',
			'label'       => esc_html__( 'List gap', 'mbm-bricks-variation-swatches' ),
			'type'        => 'text',
			'placeholder' => '6px',
			'css'         => array(
				array(
					'property' => 'gap',
					'selector' => $sel_label_group . $sel_group_list_suffix,
				),
			),
		);

		$this->controls['labelListWrap'] = array(
			'tab'     => 'style',
			'group'   => 'attribute_style_label',
			'label'   => esc_html__( 'List wrap', 'mbm-bricks-variation-swatches' ),
			'type'    => 'select',
			'options' => array(
				'nowrap'       => esc_html__( 'No wrap', 'mbm-bricks-variation-swatches' ),
				'wrap'         => esc_html__( 'Wrap', 'mbm-bricks-variation-swatches' ),
				'wrap-reverse' => esc_html__( 'Wrap reverse', 'mbm-bricks-variation-swatches' ),
			),
			'default' => 'wrap',
			'css'     => array(
				array(
					'property' => 'flex-wrap',
					'selector' => $sel_label_group . $sel_group_list_suffix,
				),
			),
		);

		/* Attribute Style: Label (styling) */
		$this->controls['labelSwatchSize'] = array(
			'tab'         => 'style',
			'group'       => 'attribute_style_label',
			'label'       => esc_html__( 'Swatch size', 'mbm-bricks-variation-swatches' ),
			'type'        => 'text',
			'placeholder' => '14px',
			'css'         => array(
				array(
					'property' => '--mbm-swatch-size',
					'selector' => $sel_label_group,
				),
			),
		);

		$this->controls['labelSwatchTypography'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_label',
			'label' => esc_html__( 'Swatch typography', 'mbm-bricks-variation-swatches' ),
			'type'  => 'typography',
			'css'   => array(
				array(
					'property' => 'typography',
					'selector' => $sel_label_group . ' .mbm-variation-swatches__label-swatch',
				),
				array(
					'property' => 'typography',
					'selector' => $sel_label_group . ' .mbm-variation-swatches__more',
				),
			),
		);

		$this->controls['labelSwatchBackground'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_label',
			'label' => esc_html__( 'Swatch background', 'mbm-bricks-variation-swatches' ),
			'type'  => 'background',
			'css'   => array(
				array(
					'property' => 'background',
					'selector' => $sel_label_group . ' .mbm-variation-swatches__label-swatch',
				),
			),
		);

		$this->controls['labelSwatchBorder'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_label',
			'label' => esc_html__( 'Swatch border', 'mbm-bricks-variation-swatches' ),
			'type'  => 'border',
			'css'   => array(
				array(
					'property' => 'border',
					'selector' => $sel_label_group . ' .mbm-variation-swatches__label-swatch',
				),
			),
		);

		/* Attribute Style: Color (layout - group) */
		$this->controls['colorGroupDirection'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_color',
			'label' => esc_html__( 'Group direction', 'mbm-bricks-variation-swatches' ),
			'type'  => 'direction',
			'css'   => array(
				array(
					'property' => '--mbm-group-direction',
					'selector' => $sel_color_group,
				),
			),
		);

		$this->controls['colorGroupJustify'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_color',
			'label' => esc_html__( 'Group justify content', 'mbm-bricks-variation-swatches' ),
			'type'  => 'justify-content',
			'css'   => array(
				array(
					'property' => 'justify-content',
					'selector' => $sel_color_group,
				),
			),
		);

		$this->controls['colorGroupAlign'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_color',
			'label' => esc_html__( 'Group align items', 'mbm-bricks-variation-swatches' ),
			'type'  => 'align-items',
			'css'   => array(
				array(
					'property' => 'align-items',
					'selector' => $sel_color_group,
				),
			),
		);

		$this->controls['colorGroupGap'] = array(
			'tab'         => 'style',
			'group'       => 'attribute_style_color',
			'label'       => esc_html__( 'Group gap', 'mbm-bricks-variation-swatches' ),
			'type'        => 'text',
			'placeholder' => '6px',
			'css'         => array(
				array(
					'property' => 'gap',
					'selector' => $sel_color_group,
				),
			),
		);

		$this->controls['colorGroupWrap'] = array(
			'tab'     => 'style',
			'group'   => 'attribute_style_color',
			'label'   => esc_html__( 'Group wrap', 'mbm-bricks-variation-swatches' ),
			'type'    => 'select',
			'options' => array(
				'nowrap'       => esc_html__( 'No wrap', 'mbm-bricks-variation-swatches' ),
				'wrap'         => esc_html__( 'Wrap', 'mbm-bricks-variation-swatches' ),
				'wrap-reverse' => esc_html__( 'Wrap reverse', 'mbm-bricks-variation-swatches' ),
			),
			'default' => 'nowrap',
			'css'     => array(
				array(
					'property' => 'flex-wrap',
					'selector' => $sel_color_group,
				),
			),
		);

		/* Attribute Style: Color (layout - list) */
		$this->controls['colorListDirection'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_color',
			'label' => esc_html__( 'List direction', 'mbm-bricks-variation-swatches' ),
			'type'  => 'direction',
			'css'   => array(
				array(
					'property' => 'flex-direction',
					'selector' => $sel_color_group . $sel_group_list_suffix,
				),
			),
		);

		$this->controls['colorListJustify'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_color',
			'label' => esc_html__( 'List justify content', 'mbm-bricks-variation-swatches' ),
			'type'  => 'justify-content',
			'css'   => array(
				array(
					'property' => 'justify-content',
					'selector' => $sel_color_group . $sel_group_list_suffix,
				),
			),
		);

		$this->controls['colorListAlign'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_color',
			'label' => esc_html__( 'List align items', 'mbm-bricks-variation-swatches' ),
			'type'  => 'align-items',
			'css'   => array(
				array(
					'property' => 'align-items',
					'selector' => $sel_color_group . $sel_group_list_suffix,
				),
			),
		);

		$this->controls['colorListGap'] = array(
			'tab'         => 'style',
			'group'       => 'attribute_style_color',
			'label'       => esc_html__( 'List gap', 'mbm-bricks-variation-swatches' ),
			'type'        => 'text',
			'placeholder' => '6px',
			'css'         => array(
				array(
					'property' => 'gap',
					'selector' => $sel_color_group . $sel_group_list_suffix,
				),
			),
		);

		$this->controls['colorListWrap'] = array(
			'tab'     => 'style',
			'group'   => 'attribute_style_color',
			'label'   => esc_html__( 'List wrap', 'mbm-bricks-variation-swatches' ),
			'type'    => 'select',
			'options' => array(
				'nowrap'       => esc_html__( 'No wrap', 'mbm-bricks-variation-swatches' ),
				'wrap'         => esc_html__( 'Wrap', 'mbm-bricks-variation-swatches' ),
				'wrap-reverse' => esc_html__( 'Wrap reverse', 'mbm-bricks-variation-swatches' ),
			),
			'default' => 'wrap',
			'css'     => array(
				array(
					'property' => 'flex-wrap',
					'selector' => $sel_color_group . $sel_group_list_suffix,
				),
			),
		);

		/* Attribute Style: Color (styling) */
		$this->controls['colorSwatchSize'] = array(
			'tab'         => 'style',
			'group'       => 'attribute_style_color',
			'label'       => esc_html__( 'Swatch size', 'mbm-bricks-variation-swatches' ),
			'type'        => 'text',
			'placeholder' => '14px',
			'css'         => array(
				array(
					'property' => '--mbm-swatch-size',
					'selector' => $sel_color_group,
				),
			),
		);

		$this->controls['colorSwatchBorder'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_color',
			'label' => esc_html__( 'Swatch border', 'mbm-bricks-variation-swatches' ),
			'type'  => 'border',
			'css'   => array(
				array(
					'property' => 'border',
					'selector' => $sel_color_group . ' .mbm-variation-swatches__swatch',
				),
			),
		);

		/* Attribute Style: Image (layout - group) */
		$this->controls['imageGroupDirection'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_image',
			'label' => esc_html__( 'Group direction', 'mbm-bricks-variation-swatches' ),
			'type'  => 'direction',
			'css'   => array(
				array(
					'property' => '--mbm-group-direction',
					'selector' => $sel_image_group,
				),
			),
		);

		$this->controls['imageGroupJustify'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_image',
			'label' => esc_html__( 'Group justify content', 'mbm-bricks-variation-swatches' ),
			'type'  => 'justify-content',
			'css'   => array(
				array(
					'property' => 'justify-content',
					'selector' => $sel_image_group,
				),
			),
		);

		$this->controls['imageGroupAlign'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_image',
			'label' => esc_html__( 'Group align items', 'mbm-bricks-variation-swatches' ),
			'type'  => 'align-items',
			'css'   => array(
				array(
					'property' => 'align-items',
					'selector' => $sel_image_group,
				),
			),
		);

		$this->controls['imageGroupGap'] = array(
			'tab'         => 'style',
			'group'       => 'attribute_style_image',
			'label'       => esc_html__( 'Group gap', 'mbm-bricks-variation-swatches' ),
			'type'        => 'text',
			'placeholder' => '6px',
			'css'         => array(
				array(
					'property' => 'gap',
					'selector' => $sel_image_group,
				),
			),
		);

		$this->controls['imageGroupWrap'] = array(
			'tab'     => 'style',
			'group'   => 'attribute_style_image',
			'label'   => esc_html__( 'Group wrap', 'mbm-bricks-variation-swatches' ),
			'type'    => 'select',
			'options' => array(
				'nowrap'       => esc_html__( 'No wrap', 'mbm-bricks-variation-swatches' ),
				'wrap'         => esc_html__( 'Wrap', 'mbm-bricks-variation-swatches' ),
				'wrap-reverse' => esc_html__( 'Wrap reverse', 'mbm-bricks-variation-swatches' ),
			),
			'default' => 'nowrap',
			'css'     => array(
				array(
					'property' => 'flex-wrap',
					'selector' => $sel_image_group,
				),
			),
		);

		/* Attribute Style: Image (layout - list) */
		$this->controls['imageListDirection'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_image',
			'label' => esc_html__( 'List direction', 'mbm-bricks-variation-swatches' ),
			'type'  => 'direction',
			'css'   => array(
				array(
					'property' => 'flex-direction',
					'selector' => $sel_image_group . $sel_group_list_suffix,
				),
			),
		);

		$this->controls['imageListJustify'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_image',
			'label' => esc_html__( 'List justify content', 'mbm-bricks-variation-swatches' ),
			'type'  => 'justify-content',
			'css'   => array(
				array(
					'property' => 'justify-content',
					'selector' => $sel_image_group . $sel_group_list_suffix,
				),
			),
		);

		$this->controls['imageListAlign'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_image',
			'label' => esc_html__( 'List align items', 'mbm-bricks-variation-swatches' ),
			'type'  => 'align-items',
			'css'   => array(
				array(
					'property' => 'align-items',
					'selector' => $sel_image_group . $sel_group_list_suffix,
				),
			),
		);

		$this->controls['imageListGap'] = array(
			'tab'         => 'style',
			'group'       => 'attribute_style_image',
			'label'       => esc_html__( 'List gap', 'mbm-bricks-variation-swatches' ),
			'type'        => 'text',
			'placeholder' => '6px',
			'css'         => array(
				array(
					'property' => 'gap',
					'selector' => $sel_image_group . $sel_group_list_suffix,
				),
			),
		);

		$this->controls['imageListWrap'] = array(
			'tab'     => 'style',
			'group'   => 'attribute_style_image',
			'label'   => esc_html__( 'List wrap', 'mbm-bricks-variation-swatches' ),
			'type'    => 'select',
			'options' => array(
				'nowrap'       => esc_html__( 'No wrap', 'mbm-bricks-variation-swatches' ),
				'wrap'         => esc_html__( 'Wrap', 'mbm-bricks-variation-swatches' ),
				'wrap-reverse' => esc_html__( 'Wrap reverse', 'mbm-bricks-variation-swatches' ),
			),
			'default' => 'wrap',
			'css'     => array(
				array(
					'property' => 'flex-wrap',
					'selector' => $sel_image_group . $sel_group_list_suffix,
				),
			),
		);

		/* Attribute Style: Image (styling) */
		$this->controls['imageSwatchSize'] = array(
			'tab'         => 'style',
			'group'       => 'attribute_style_image',
			'label'       => esc_html__( 'Swatch size', 'mbm-bricks-variation-swatches' ),
			'type'        => 'text',
			'placeholder' => '14px',
			'css'         => array(
				array(
					'property' => '--mbm-swatch-size',
					'selector' => $sel_image_group,
				),
			),
		);

		$this->controls['imageObjectFit'] = array(
			'tab'     => 'style',
			'group'   => 'attribute_style_image',
			'label'   => esc_html__( 'Image fit', 'mbm-bricks-variation-swatches' ),
			'type'    => 'select',
			'options' => array(
				'cover'   => esc_html__( 'Cover', 'mbm-bricks-variation-swatches' ),
				'contain' => esc_html__( 'Contain', 'mbm-bricks-variation-swatches' ),
			),
			'default' => 'cover',
			'css'     => array(
				array(
					'property' => '--mbm-image-fit',
					'selector' => $sel_image_group,
				),
			),
		);

		$this->controls['imageSwatchBorder'] = array(
			'tab'   => 'style',
			'group' => 'attribute_style_image',
			'label' => esc_html__( 'Swatch border', 'mbm-bricks-variation-swatches' ),
			'type'  => 'border',
			'css'   => array(
				array(
					'property' => 'border',
					'selector' => $sel_image_group . ' .mbm-variation-swatches__swatch--image',
				),
			),
		);
	}

	public function enqueue_scripts() {
		wp_enqueue_style(
			'mbm-bvs-frontend',
			MBM_BVS_URL . 'assets/css/frontend.css',
			array(),
			MBM_BVS_VERSION
		);
	}

	public function render() {
		$product_id = get_the_ID();
		$product    = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;

		/*
		 * Bricks element IDs are often strings (not numeric). We must not cast to int,
		 * otherwise scoped selectors like #brxe-{$this->id} break (become #brxe-0).
		 */
		$element_id = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $this->id );
		$element_id = ( $element_id !== '' ) ? $element_id : (string) $this->id;

		if ( ! $product || ! method_exists( $product, 'is_type' ) || ! $product->is_type( 'variable' ) ) {
			return;
		}


		$rows = ! empty( $this->settings['attributes'] ) && is_array( $this->settings['attributes'] )
			? $this->settings['attributes']
			: array();

		if ( empty( $rows ) ) {
			return;
		}

		$variation_attributes = mbm_bvs_get_variation_attributes_cached( $product );

		/* Normalize keys: attribute_pa_color -> pa_color */
		$normalized = array();

		foreach ( $variation_attributes as $attr_key => $values ) {
			$key = sanitize_key( mbm_bvs_normalize_attribute_key( $attr_key ) );
			$normalized[ $key ] = is_array( $values ) ? $values : array();
		}

		$this->set_attribute( '_root', 'class', array( 'mbm-variation-swatches' ) );

		/*
		 * Per-element scoped CSS rules (no inline style attributes).
		 * We print a <style> tag AFTER the <ul> to keep <ul> content model valid.
		 */
		$scoped_css_rules = array();

		echo "<ul {$this->render_attributes( '_root' )}>";

		$variation_image_map = null;

		foreach ( $rows as $row ) {
			$selected   = isset( $row['attribute'] ) ? (string) $row['attribute'] : '';
			$limit      = isset( $row['limit'] ) ? (int) $row['limit'] : 0;
			$min_values = isset( $row['min_values'] ) ? (int) $row['min_values'] : 0;

			$is_custom = ( $selected === 'custom' );

			$attr_key = '';
			if ( $is_custom ) {
				$attr_key = ! empty( $row['custom_attribute'] ) ? sanitize_key( $row['custom_attribute'] ) : '';
			} else {
				$attr_key = sanitize_key( $selected );
			}

			if ( $attr_key === '' || empty( $normalized[ $attr_key ] ) ) {
				continue;
			}

			$values = $normalized[ $attr_key ];

			$total = count( $values );

			/* Skip rendering if total values is not greater than min_values threshold */
			if ( $min_values > 0 && $total <= $min_values ) {
				continue;
			}
			$show  = ( $limit > 0 ) ? array_slice( $values, 0, $limit ) : $values;
			$more  = ( $limit > 0 && $total > $limit ) ? ( $total - $limit ) : 0;

			$taxonomy = $attr_key;
			$is_tax   = taxonomy_exists( $taxonomy );

			$group_label = $attr_key;

			if ( $is_tax ) {
				$tax_obj = get_taxonomy( $taxonomy );
				if ( $tax_obj && ! empty( $tax_obj->labels->singular_name ) ) {
					$group_label = $tax_obj->labels->singular_name;
				}
			}

			/*
			 * Determine attribute group type (auto): Image > Color > Label.
			 * This is used for type-specific styling controls without inline styles.
			 */
			$group_type = 'label';

			if ( $is_tax ) {
				foreach ( $show as $value_for_type ) {
					$value_for_type = (string) $value_for_type;
					$term_for_type  = get_term_by( 'slug', $value_for_type, $taxonomy );

					if ( ! $term_for_type || is_wp_error( $term_for_type ) ) {
						continue;
					}

					$use_var_image_on_type = ( (int) get_term_meta( (int) $term_for_type->term_id, 'bricks_swatch_use_variation_image', true ) === 1 );
					$img_url_type          = mbm_bvs_get_term_image_url_strict( (int) $term_for_type->term_id );

					if ( $img_url_type === '' && $use_var_image_on_type ) {
						if ( null === $variation_image_map ) {
							$variation_image_map = mbm_bvs_get_variation_image_map_cached( $product );
						}

						if ( isset( $variation_image_map[ $attr_key ][ $value_for_type ] ) ) {
							$img_url_type = (string) $variation_image_map[ $attr_key ][ $value_for_type ];
						}
					}

					if ( $img_url_type !== '' ) {
						$group_type = 'image';
						break;
					}

					$raw_color_type  = get_term_meta( (int) $term_for_type->term_id, 'bricks_swatch_color_value', true );
					$raw_color_type  = is_string( $raw_color_type ) ? $raw_color_type : '';
					$term_color_type = sanitize_hex_color( $raw_color_type );

					if ( $term_color_type !== '' ) {
						$group_type = 'color';
					}
				}
			}

			echo '<li class="mbm-variation-swatches__group" data-attribute="' . esc_attr( $attr_key ) . '" data-swatch-type="' . esc_attr( $group_type ) . '">';

			if ( ! empty( $row['show_label'] ) ) {
				echo '<span class="mbm-variation-swatches__group-label">' . esc_html( $group_label ) . '</span>';
			}

			echo '<ul class="mbm-variation-swatches__list">';

			foreach ( $show as $value ) {
				$value = (string) $value;

				$term_name = $value;

				/* AUTO only: Image > Color > Label */
				$img_url          = '';
				$term_color       = '';
				$use_var_image_on = false;

				if ( $is_tax ) {
					$term = get_term_by( 'slug', $value, $taxonomy );

					if ( $term && ! is_wp_error( $term ) ) {
						$term_name = $term->name;

						/* CONFIRMED keys only */
						$use_var_image_on = ( (int) get_term_meta( $term->term_id, 'bricks_swatch_use_variation_image', true ) === 1 );
						$img_url          = mbm_bvs_get_term_image_url_strict( $term->term_id );

						$raw_color  = get_term_meta( $term->term_id, 'bricks_swatch_color_value', true );
						$raw_color  = is_string( $raw_color ) ? $raw_color : '';
						$term_color = sanitize_hex_color( $raw_color );
					}
				}

				if ( $img_url === '' && $use_var_image_on ) {
					if ( null === $variation_image_map ) {
						$variation_image_map = mbm_bvs_get_variation_image_map_cached( $product );
					}

					if ( isset( $variation_image_map[ $attr_key ][ $value ] ) ) {
						$img_url = (string) $variation_image_map[ $attr_key ][ $value ];
					}
				}

				echo '<li class="mbm-variation-swatches__item">';

				if ( $img_url !== '' ) {
					echo '<span class="mbm-variation-swatches__swatch mbm-variation-swatches__swatch--image" aria-label="' . esc_attr( $term_name ) . '" title="' . esc_attr( $term_name ) . '">';
					echo '<img class="mbm-variation-swatches__image" src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $term_name ) . '" loading="lazy" decoding="async">';
					echo '</span>';
				} elseif ( $term_color !== '' ) {
					$term_id = ( $is_tax && isset( $term ) && $term && ! is_wp_error( $term ) ) ? (int) $term->term_id : 0;

					if ( $term_id > 0 ) {
						$rule_key = $term_id . ':' . $term_color;

						if ( ! isset( $scoped_css_rules[ $rule_key ] ) ) {
							$scoped_css_rules[ $rule_key ] = array(
								'term_id' => $term_id,
								'color'   => $term_color,
							);
						}
					}

					$data_term_id = ( $term_id > 0 ) ? ' data-term-id="' . esc_attr( (string) $term_id ) . '"' : '';
					echo '<span class="mbm-variation-swatches__swatch"' . $data_term_id . ' aria-label="' . esc_attr( $term_name ) . '" title="' . esc_attr( $term_name ) . '"></span>';

				} else {
					echo '<span class="mbm-variation-swatches__label-swatch" aria-label="' . esc_attr( $term_name ) . '" title="' . esc_attr( $term_name ) . '">' . esc_html( $term_name ) . '</span>';
				}

				echo '</li>';
			}

			if ( $more > 0 ) {
				echo '<li class="mbm-variation-swatches__item mbm-variation-swatches__item--more">';
				echo '<span class="mbm-variation-swatches__more" aria-label="' . esc_attr( sprintf( '+%d', $more ) ) . '">' . esc_html( sprintf( '+%d', $more ) ) . '</span>';
				echo '</li>';
			}

			echo '</ul>';
			echo '</li>';
		}

		echo '</ul>';

		if ( ! empty( $scoped_css_rules ) ) {
			$css = '';

			foreach ( $scoped_css_rules as $entry ) {
				$term_id = isset( $entry['term_id'] ) ? (int) $entry['term_id'] : 0;
				$color   = isset( $entry['color'] ) ? sanitize_hex_color( (string) $entry['color'] ) : '';

				if ( $term_id <= 0 || $color === '' ) {
					continue;
				}

				$css .= sprintf(
					'.mbm-variation-swatches .mbm-variation-swatches__swatch[data-term-id="%1$d"]{--mbm-swatch-color:%2$s;}',
					$term_id,
					$color
				);
			}

			if ( $css !== '' ) {
				/*
				 * Bricks builder can strip <style> tags from element HTML in the canvas/iframe,
				 * which makes term colors disappear in the builder even though they work on the frontend.
				 * Use wp_add_inline_style so the CSS is printed in <head> and applies in both contexts.
				 */
				static $mbm_bvs_inline_css_hashes = array();

				$css_hash = md5( $css ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_md5

				if ( empty( $mbm_bvs_inline_css_hashes[ $css_hash ] ) ) {
					/*
					 * Ensure our stylesheet handle exists.
					 * enqueue_scripts() should already have enqueued it, but we harden this path.
					 */
					if ( ! wp_style_is( 'mbm-bvs-frontend', 'enqueued' ) ) {
						wp_enqueue_style(
							'mbm-bvs-frontend',
							MBM_BVS_URL . 'assets/css/frontend.css',
							array(),
							MBM_BVS_VERSION
						);
					}

					wp_add_inline_style( 'mbm-bvs-frontend', $css );
					$mbm_bvs_inline_css_hashes[ $css_hash ] = true;
				}

			}
		}

	}
}
