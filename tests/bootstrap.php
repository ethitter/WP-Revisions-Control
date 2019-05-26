<?php
/**
 * PHPUnit bootstrap file
 *
 * @package WP_Revisions_Control
 */

$wp_revisions_control_tests_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $wp_revisions_control_tests_tests_dir ) {
	$wp_revisions_control_tests_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $wp_revisions_control_tests_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $wp_revisions_control_tests_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // WPCS: XSS ok.
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $wp_revisions_control_tests_tests_dir . '/includes/functions.php';

/**
 * Stub admin-only function not needed for testing.
 */
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
if ( ! function_exists( 'post_revisions_meta_box' ) ) {
	/**
	 * Stub for Core's revisions meta box.
	 */
	function post_revisions_meta_box() {}
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound

/**
 * Manually load the plugin being tested.
 */
function wp_revisions_control_tests_manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/wp-revisions-control.php';
}
tests_add_filter( 'muplugins_loaded', 'wp_revisions_control_tests_manually_load_plugin' );

// Start up the WP testing environment.
require $wp_revisions_control_tests_tests_dir . '/includes/bootstrap.php';
