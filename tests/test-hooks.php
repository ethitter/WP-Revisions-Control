<?php
/**
 * Test WP hooks.
 *
 * @package WP_Revisions_Control
 */

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

	/**
	 * Test revision purging.
	 */
	public function test_purge_all() {
		$post_id    = $this->factory->post->create();
		$iterations = 10;

		for ( $i = 0; $i < $iterations; $i++ ) {
			wp_update_post(
				[
					'ID'           => $post_id,
					'post_content' => wp_rand(),
				]
			);
		}

		$revisions_to_purge = count( wp_get_post_revisions( $post_id ) );
		$this->assertEquals(
			$iterations,
			$revisions_to_purge,
			'Failed to assert that there are revisions to purge.'
		);

		$purge = WP_Revisions_Control::get_instance()->do_purge_all( $post_id );
		$revisions_remaining = count( wp_get_post_revisions( $post_id ) );

		$this->assertEquals(
			0,
			$revisions_remaining,
			'Failed to assert that all revisions were purged.'
		);

		$this->assertEquals(
			10,
			$purge['count'],
			'Failed to assert that response includes expected count of purged revisions.'
		);

		$this->assertEquals(
			'Removed 10 revisions associated with this post.',
			$purge['success'],
			'Failed to assert that response includes expected success message.'
		);
	}
}
