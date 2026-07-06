<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBM_Element_Woo_Variation_Swatches extends \Bricks\Element {
	public $category = 'mbm-woo';
	public $name     = 'mbm-woo-variation-swatches';
	public $icon     = 'ti-paint-bucket';

	public function get_label() {
		return esc_html__( 'Loop Variation Swatches', 'mbm-bricks-loop-variation-swatches' );
	}

	public function get_keywords() {
		return [ 'woocommerce', 'product', 'variation', 'swatch', 'attribute', 'loop' ];
	}

	public function set_control_groups() {
		$this->control_groups['swatchLayout'] = [
			'title' => esc_html__( 'Swatch layout', 'mbm-bricks-loop-variation-swatches' ),
			'tab'   => 'style',
		];

		$this->control_groups['groupLabel'] = [
			'title' => esc_html__( 'Attribute label', 'mbm-bricks-loop-variation-swatches' ),
			'tab'   => 'style',
		];

		$this->control_groups['colorSwatches'] = [
			'title' => esc_html__( 'Color swatches', 'mbm-bricks-loop-variation-swatches' ),
			'tab'   => 'style',
		];

		$this->control_groups['labelSwatches'] = [
			'title' => esc_html__( 'Label swatches', 'mbm-bricks-loop-variation-swatches' ),
			'tab'   => 'style',
		];

		$this->control_groups['imageSwatches'] = [
			'title' => esc_html__( 'Image swatches', 'mbm-bricks-loop-variation-swatches' ),
			'tab'   => 'style',
		];
	}

	public function set_controls() {
		/**
		 * Content: attribute selection
		 */
		$attribute_options = [];

		if ( function_exists( 'wc_get_attribute_taxonomy_names' ) ) {
			foreach ( wc_get_attribute_taxonomy_names() as $taxonomy ) {
				$attribute_options[ $taxonomy ] = wc_attribute_label( $taxonomy );
			}
		}

		$this->controls['attributes'] = [
			'tab'           => 'content',
			'label'         => esc_html__( 'Attributes to display', 'mbm-bricks-loop-variation-swatches' ),
			'type'          => 'repeater',
			'titleProperty' => 'attribute',
			'fields'        => [
				'attribute'        => [
					'label'       => esc_html__( 'Attribute', 'mbm-bricks-loop-variation-swatches' ),
					'type'        => 'select',
					'options'     => array_merge(
						$attribute_options,
						[ 'custom' => esc_html__( 'Custom attribute (type name)', 'mbm-bricks-loop-variation-swatches' ) ]
					),
					'placeholder' => esc_html__( 'Select attribute', 'mbm-bricks-loop-variation-swatches' ),
					'searchable'  => true,
					'clearable'   => false,
				],
				'custom_attribute' => [
					'label'       => esc_html__( 'Custom attribute name', 'mbm-bricks-loop-variation-swatches' ),
					'type'        => 'text',
					'placeholder' => esc_html__( 'e.g. Pattern', 'mbm-bricks-loop-variation-swatches' ),
					'required'    => [ 'attribute', '=', 'custom' ],
				],
				'show_label'       => [
					'label'   => esc_html__( 'Show attribute label', 'mbm-bricks-loop-variation-swatches' ),
					'type'    => 'checkbox',
					'default' => true,
				],
				'limit'            => [
					'label'       => esc_html__( 'Max values (0 = all)', 'mbm-bricks-loop-variation-swatches' ),
					'type'        => 'number',
					'min'         => 0,
					'placeholder' => '0',
				],
				'min_values'       => [
					'label'       => esc_html__( 'Min values to show', 'mbm-bricks-loop-variation-swatches' ),
					'type'        => 'number',
					'min'         => 0,
					'placeholder' => '0',
					'description' => esc_html__( 'Only show this attribute when the product offers more than this many options (0 = always show)', 'mbm-bricks-loop-variation-swatches' ),
				],
			],
		];

		/**
		 * Style: swatch layout
		 */
		$this->controls['groupsGap'] = [
			'tab'         => 'style',
			'group'       => 'swatchLayout',
			'label'       => esc_html__( 'Gap between attributes', 'mbm-bricks-loop-variation-swatches' ),
			'type'        => 'number',
			'units'       => true,
			'placeholder' => '8px',
			'css'         => [
				[
					'property' => 'gap',
				],
			],
		];

		$this->controls['groupDirection'] = [
			'tab'   => 'style',
			'group' => 'swatchLayout',
			'label' => esc_html__( 'Attribute label position', 'mbm-bricks-loop-variation-swatches' ),
			'type'  => 'direction',
			'css'   => [
				[
					'property' => 'flex-direction',
					'selector' => '.mbm-variation-swatches__group',
				],
			],
		];

		$this->controls['groupGap'] = [
			'tab'         => 'style',
			'group'       => 'swatchLayout',
			'label'       => esc_html__( 'Gap between label and swatches', 'mbm-bricks-loop-variation-swatches' ),
			'type'        => 'number',
			'units'       => true,
			'placeholder' => '6px',
			'css'         => [
				[
					'property' => 'gap',
					'selector' => '.mbm-variation-swatches__group',
				],
			],
		];

		$this->controls['swatchesDirection'] = [
			'tab'   => 'style',
			'group' => 'swatchLayout',
			'label' => esc_html__( 'Swatch list direction', 'mbm-bricks-loop-variation-swatches' ),
			'type'  => 'direction',
			'css'   => [
				[
					'property' => 'flex-direction',
					'selector' => '.mbm-variation-swatches__list',
				],
			],
		];

		$this->controls['swatchesWrap'] = [
			'tab'     => 'style',
			'group'   => 'swatchLayout',
			'label'   => esc_html__( 'Swatch list wrap', 'mbm-bricks-loop-variation-swatches' ),
			'type'    => 'select',
			'options' => [
				'wrap'         => esc_html__( 'Wrap', 'mbm-bricks-loop-variation-swatches' ),
				'nowrap'       => esc_html__( 'No wrap', 'mbm-bricks-loop-variation-swatches' ),
				'wrap-reverse' => esc_html__( 'Wrap reverse', 'mbm-bricks-loop-variation-swatches' ),
			],
			'inline'  => true,
			'css'     => [
				[
					'property' => 'flex-wrap',
					'selector' => '.mbm-variation-swatches__list',
				],
			],
		];

		$this->controls['swatchesJustifyContent'] = [
			'tab'   => 'style',
			'group' => 'swatchLayout',
			'label' => esc_html__( 'Swatch list justify', 'mbm-bricks-loop-variation-swatches' ),
			'type'  => 'justify-content',
			'css'   => [
				[
					'property' => 'justify-content',
					'selector' => '.mbm-variation-swatches__list',
				],
			],
		];

		$this->controls['swatchesAlignItems'] = [
			'tab'   => 'style',
			'group' => 'swatchLayout',
			'label' => esc_html__( 'Swatch list align', 'mbm-bricks-loop-variation-swatches' ),
			'type'  => 'align-items',
			'css'   => [
				[
					'property' => 'align-items',
					'selector' => '.mbm-variation-swatches__list',
				],
			],
		];

		$this->controls['swatchesGap'] = [
			'tab'         => 'style',
			'group'       => 'swatchLayout',
			'label'       => esc_html__( 'Gap between swatches', 'mbm-bricks-loop-variation-swatches' ),
			'type'        => 'number',
			'units'       => true,
			'placeholder' => '6px',
			'css'         => [
				[
					'property' => 'gap',
					'selector' => '.mbm-variation-swatches__list',
				],
			],
		];

		/**
		 * Style: attribute (group) label
		 */
		$this->controls['groupLabelTypography'] = [
			'tab'   => 'style',
			'group' => 'groupLabel',
			'label' => esc_html__( 'Typography', 'mbm-bricks-loop-variation-swatches' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'typography',
					'selector' => '.mbm-variation-swatches__group-label',
				],
			],
		];

		$this->controls['groupLabelBackground'] = [
			'tab'   => 'style',
			'group' => 'groupLabel',
			'label' => esc_html__( 'Background', 'mbm-bricks-loop-variation-swatches' ),
			'type'  => 'background',
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.mbm-variation-swatches__group-label',
				],
			],
		];

		$this->controls['groupLabelPadding'] = [
			'tab'   => 'style',
			'group' => 'groupLabel',
			'label' => esc_html__( 'Padding', 'mbm-bricks-loop-variation-swatches' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.mbm-variation-swatches__group-label',
				],
			],
		];

		/**
		 * Style: color swatches
		 */
		$this->controls['colorSize'] = [
			'tab'         => 'style',
			'group'       => 'colorSwatches',
			'label'       => esc_html__( 'Size', 'mbm-bricks-loop-variation-swatches' ),
			'type'        => 'number',
			'units'       => true,
			'placeholder' => '20px',
			'css'         => [
				[
					'property' => 'width',
					'selector' => '.mbm-variation-swatches__swatch--color',
				],
				[
					'property' => 'height',
					'selector' => '.mbm-variation-swatches__swatch--color',
				],
			],
		];

		$this->controls['colorBorder'] = [
			'tab'   => 'style',
			'group' => 'colorSwatches',
			'label' => esc_html__( 'Border', 'mbm-bricks-loop-variation-swatches' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.mbm-variation-swatches__swatch--color',
				],
			],
		];

		/**
		 * Style: label swatches
		 */
		$this->controls['labelTypography'] = [
			'tab'   => 'style',
			'group' => 'labelSwatches',
			'label' => esc_html__( 'Typography', 'mbm-bricks-loop-variation-swatches' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'typography',
					'selector' => '.mbm-variation-swatches__label-swatch',
				],
				[
					'property' => 'typography',
					'selector' => '.mbm-variation-swatches__more',
				],
			],
		];

		$this->controls['labelBackground'] = [
			'tab'   => 'style',
			'group' => 'labelSwatches',
			'label' => esc_html__( 'Background', 'mbm-bricks-loop-variation-swatches' ),
			'type'  => 'background',
			'css'   => [
				[
					'property' => 'background',
					'selector' => '.mbm-variation-swatches__label-swatch',
				],
			],
		];

		$this->controls['labelBorder'] = [
			'tab'   => 'style',
			'group' => 'labelSwatches',
			'label' => esc_html__( 'Border', 'mbm-bricks-loop-variation-swatches' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.mbm-variation-swatches__label-swatch',
				],
			],
		];

		$this->controls['labelPadding'] = [
			'tab'   => 'style',
			'group' => 'labelSwatches',
			'label' => esc_html__( 'Padding', 'mbm-bricks-loop-variation-swatches' ),
			'type'  => 'spacing',
			'css'   => [
				[
					'property' => 'padding',
					'selector' => '.mbm-variation-swatches__label-swatch',
				],
			],
		];

		/**
		 * Style: image swatches
		 */
		$this->controls['imageSize'] = [
			'tab'         => 'style',
			'group'       => 'imageSwatches',
			'label'       => esc_html__( 'Size', 'mbm-bricks-loop-variation-swatches' ),
			'type'        => 'number',
			'units'       => true,
			'placeholder' => '20px',
			'css'         => [
				[
					'property' => 'width',
					'selector' => '.mbm-variation-swatches__swatch--image',
				],
				[
					'property' => 'height',
					'selector' => '.mbm-variation-swatches__swatch--image',
				],
			],
		];

		$this->controls['imageObjectFit'] = [
			'tab'     => 'style',
			'group'   => 'imageSwatches',
			'label'   => esc_html__( 'Image fit', 'mbm-bricks-loop-variation-swatches' ),
			'type'    => 'select',
			'options' => [
				'cover'   => esc_html__( 'Cover', 'mbm-bricks-loop-variation-swatches' ),
				'contain' => esc_html__( 'Contain', 'mbm-bricks-loop-variation-swatches' ),
			],
			'inline'  => true,
			'css'     => [
				[
					'property' => 'object-fit',
					'selector' => '.mbm-variation-swatches__image',
				],
			],
		];

		$this->controls['imageBorder'] = [
			'tab'   => 'style',
			'group' => 'imageSwatches',
			'label' => esc_html__( 'Border', 'mbm-bricks-loop-variation-swatches' ),
			'type'  => 'border',
			'css'   => [
				[
					'property' => 'border',
					'selector' => '.mbm-variation-swatches__swatch--image',
				],
			],
		];
	}

	public function enqueue_scripts() {
		wp_enqueue_style(
			'mbm-bvs-frontend',
			MBM_BVS_URL . 'assets/css/frontend.css',
			[],
			MBM_BVS_VERSION
		);
	}

	/**
	 * Current product, aware of Bricks query loop context.
	 */
	protected function get_loop_product() {
		$post_id = $this->post_id;

		if ( \Bricks\Query::is_looping() && \Bricks\Query::get_loop_object_type() === 'post' ) {
			$post_id = \Bricks\Query::get_loop_object_id();
		}

		return $post_id ? wc_get_product( $post_id ) : false;
	}

	public function render() {
		if ( ! MBM_BVS_Swatch_Data::is_enabled() ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'Turn on "Variation swatches" under Bricks > Settings > WooCommerce to use this element.', 'mbm-bricks-loop-variation-swatches' ),
				],
				'warning'
			);
		}

		$rows = ! empty( $this->settings['attributes'] ) && is_array( $this->settings['attributes'] )
			? $this->settings['attributes']
			: [];

		if ( empty( $rows ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'Add at least one attribute to display.', 'mbm-bricks-loop-variation-swatches' ),
				]
			);
		}

		$product = $this->get_loop_product();

		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'Swatches only show on variable products. This preview product has no variations.', 'mbm-bricks-loop-variation-swatches' ),
				]
			);
		}

		/*
		 * Variation attributes keyed by a normalized name so custom attribute
		 * rows ("Pattern") match their variation data key.
		 */
		$available = [];

		foreach ( $product->get_variation_attributes() as $raw_key => $values ) {
			$available[ sanitize_title( $raw_key ) ] = [
				'raw_key' => (string) $raw_key,
				'values'  => is_array( $values ) ? array_values( array_filter( array_map( 'strval', $values ), 'strlen' ) ) : [],
			];
		}

		$groups = [];

		foreach ( $rows as $row ) {
			$selected  = isset( $row['attribute'] ) ? (string) $row['attribute'] : '';
			$is_custom = ( $selected === 'custom' );

			$key = $is_custom
				? sanitize_title( isset( $row['custom_attribute'] ) ? (string) $row['custom_attribute'] : '' )
				: sanitize_title( $selected );

			if ( $key === '' || empty( $available[ $key ]['values'] ) ) {
				continue;
			}

			$values     = $available[ $key ]['values'];
			$total      = count( $values );
			$min_values = isset( $row['min_values'] ) ? (int) $row['min_values'] : 0;

			if ( $min_values > 0 && $total <= $min_values ) {
				continue;
			}

			$limit = isset( $row['limit'] ) ? (int) $row['limit'] : 0;
			$shown = ( $limit > 0 ) ? array_slice( $values, 0, $limit ) : $values;

			$group = MBM_BVS_Swatch_Data::resolve_group( $product, $available[ $key ]['raw_key'], $shown );

			$group['key']        = $key;
			$group['show_label'] = ! empty( $row['show_label'] );
			$group['more']       = ( $limit > 0 && $total > $limit ) ? ( $total - $limit ) : 0;

			$groups[] = $group;
		}

		if ( empty( $groups ) ) {
			return $this->render_element_placeholder(
				[
					'title' => esc_html__( 'The preview product does not use the selected attributes.', 'mbm-bricks-loop-variation-swatches' ),
				]
			);
		}

		$this->set_attribute( '_root', 'class', 'mbm-variation-swatches' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_attributes() returns pre-escaped HTML attributes
		echo '<ul ' . $this->render_attributes( '_root' ) . '>';

		foreach ( $groups as $group ) {
			$this->render_group( $group );
		}

		echo '</ul>';
	}

	protected function render_group( $group ) {
		printf(
			'<li class="mbm-variation-swatches__group mbm-variation-swatches__group--%1$s" data-attribute="%2$s" data-swatch-type="%1$s">',
			esc_attr( $group['type'] ),
			esc_attr( $group['key'] )
		);

		if ( $group['show_label'] ) {
			echo '<span class="mbm-variation-swatches__group-label">' . esc_html( $group['label'] ) . '</span>';
		}

		echo '<ul class="mbm-variation-swatches__list">';

		foreach ( $group['items'] as $item ) {
			echo '<li class="mbm-variation-swatches__item">';
			$this->render_swatch( $group['type'], $item );
			echo '</li>';
		}

		if ( $group['more'] > 0 ) {
			echo '<li class="mbm-variation-swatches__item mbm-variation-swatches__item--more">';
			echo '<span class="mbm-variation-swatches__more">' . esc_html( sprintf( '+%d', $group['more'] ) ) . '</span>';
			echo '</li>';
		}

		echo '</ul>';
		echo '</li>';
	}

	protected function render_swatch( $type, $item ) {
		switch ( $type ) {
			case MBM_BVS_Swatch_Data::TYPE_COLOR:
				printf(
					'<span class="mbm-variation-swatches__swatch mbm-variation-swatches__swatch--color" style="--mbm-swatch-color:%s" title="%s" aria-label="%s"></span>',
					esc_attr( $item['color'] ),
					esc_attr( $item['name'] ),
					esc_attr( $item['name'] )
				);
				break;

			case MBM_BVS_Swatch_Data::TYPE_IMAGE:
				printf(
					'<span class="mbm-variation-swatches__swatch mbm-variation-swatches__swatch--image" title="%1$s"><img class="mbm-variation-swatches__image" src="%2$s" alt="%1$s" loading="lazy" decoding="async"></span>',
					esc_attr( $item['name'] ),
					esc_url( $item['image_url'] )
				);
				break;

			case MBM_BVS_Swatch_Data::TYPE_LABEL:
			default:
				printf(
					'<span class="mbm-variation-swatches__label-swatch" title="%s">%s</span>',
					esc_attr( $item['name'] ),
					esc_html( $item['label'] )
				);
				break;
		}
	}
}
