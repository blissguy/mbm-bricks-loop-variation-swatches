<?php
/**
 * Plugin Name: Bricks Query Loop Variation Swatches
 * Description: Show your product color, size, and image options as swatches on product cards built with Bricks query loops.
 * Version: 2.1.2
 * Author: Mixbus Marketing
 * Author URI: https://mixbusmarketing.com/
 * Text Domain: mbm-bricks-loop-variation-swatches
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires Plugins: woocommerce
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MBM_BVS_VERSION', '2.1.2' );
define( 'MBM_BVS_PATH', plugin_dir_path( __FILE__ ) );
define( 'MBM_BVS_URL', plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', 'mbm_bvs_bootstrap' );

function mbm_bvs_bootstrap() {
	add_filter( 'bricks/builder/i18n', 'mbm_bvs_add_builder_i18n' );
	add_action( 'init', 'mbm_bvs_register_bricks_elements', 11 );
}

function mbm_bvs_add_builder_i18n( $i18n ) {
	$i18n['mbm-woo'] = esc_html__( 'MBM Woo', 'mbm-bricks-loop-variation-swatches' );

	return $i18n;
}

function mbm_bvs_register_bricks_elements() {
	if ( ! class_exists( '\Bricks\Elements' ) || ! function_exists( 'wc_get_product' ) ) {
		return;
	}

	require_once MBM_BVS_PATH . 'includes/class-mbm-bvs-swatch-data.php';

	\Bricks\Elements::register_element(
		MBM_BVS_PATH . 'includes/class-mbm-element-woo-variation-swatches.php',
		'mbm-woo-variation-swatches',
		'MBM_Element_Woo_Variation_Swatches'
	);
}
