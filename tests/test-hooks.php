<?php
/**
 * Test WP hooks.
 *
 * @package WP_Revisions_Control
 */

namespace WP_Revisions_Control\Tests;

use WP_Revisions_Control;
use WP_UnitTestCase;

/**
 * Class TestHooks.
 */
class TestHooks extends WP_UnitTestCase {
	/**
	 * Plugin slug used in many settings etc.
	 *
	 * @var string
	 */
	protected static $settings_section = 'wp_revisions_control';

	/**
	 * Plugin's limit meta key.
	 *
	 * @var string
	 */
	protected static $meta_key = '_wp_rev_ctl_limit';

	/**
	 * Test saving post's revisions limit.
	 */
	public function test_save_post() {
		$post_id  = $this->factory->post->create();
		$expected = 92;

		$_POST[ static::$settings_section . '_limit_nonce' ] = wp_create_nonce( static::$settings_section . '_limit' );
		$_POST[ static::$settings_section . '_qty' ]         = $expected;

		WP_Revisions_Control::get_instance()->action_save_post( $post_id );

		$to_keep          = (int) get_post_meta( $post_id, static::$meta_key, true );
		$to_keep_filtered = wp_revisions_to_keep( get_post( $post_id ) );

		$this->assertEquals( $expected, $to_keep );
		$this->assertEquals( $expected, $to_keep_filtered );
	}

	/**
	 * Test limits, ensuring no leakage.
	 */
	public function test_limits() {
		$post_id_limited   = $this->factory->post->create();
		$post_id_unlimited = $this->factory->post->create();
		$expected          = 47;

		update_post_meta( $post_id_limited, static::$meta_key, $expected );

		$this->assertEquals(
			$expected,
			wp_revisions_to_keep( get_post( $post_id_limited ) )
		);

		$this->assertEquals(
			-1,
			wp_revisions_to_keep( get_post( $post_id_unlimited ) )
		);
	}
}
