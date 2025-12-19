<?php
/**
 * Plugin Name: Bricks Query Loop Variation Swatches
 * Description: Adds a Bricks custom element to render WooCommerce variation swatches on product query loops.
 * Version: 1.0.0
 * Author: MixBus Marketing
 * Author URI: https://mixbusmarketing.com
 * Text Domain: mbm-bricks-loop-variation-swatches
 * Requires Plugins: woocommerce
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MBM_BVS_VERSION', '1.0.0' );
define( 'MBM_BVS_PATH', plugin_dir_path( __FILE__ ) );
define( 'MBM_BVS_URL', plugin_dir_url( __FILE__ ) );

/* Bootstrap after plugins are loaded */
add_action( 'plugins_loaded', 'mbm_bvs_bootstrap' );

function mbm_bvs_bootstrap() {
	/* Load translations */
	load_plugin_textdomain(
		'mbm-bricks-loop-variation-swatches',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	/*
	 * Register a custom element category label in the Bricks builder.
	 */
	add_filter( 'bricks/builder/i18n', 'mbm_bvs_add_builder_i18n' );

	/*
	 * Register the element after Bricks core initializes.
	 */
	add_action( 'init', 'mbm_bvs_register_bricks_elements', 11 );
}

function mbm_bvs_add_builder_i18n( $i18n ) {
	$i18n['mbm-woo'] = esc_html__( 'MBM Woo', 'mbm-bricks-loop-variation-swatches' );
	return $i18n;
}

function mbm_bvs_register_bricks_elements() {
	/* Require Bricks */
	if ( ! class_exists( '\Bricks\Elements' ) ) {
		return;
	}

	/* Require WooCommerce */
	if ( ! function_exists( 'wc_get_product' ) ) {
		return;
	}

	$file = MBM_BVS_PATH . 'includes/class-mbm-element-woo-variation-swatches.php';

	/*
	 * register_element accepts: file (required), name (optional), class (optional).
	 * Passing name + class improves loading performance.
	 */
	\Bricks\Elements::register_element(
		$file,
		'mbm-woo-variation-swatches',
		'MBM_Element_Woo_Variation_Swatches'
	);
}