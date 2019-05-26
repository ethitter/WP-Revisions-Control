<?php
/**
 * Test purge methods.
 *
 * @package WP_Revisions_Control
 */

/**
 * Class TestPurges.
 */
class TestPurges extends WP_UnitTestCase {
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
	 * Test revision purging.
	 */
	public function test_purge_all() {
		$post_id    = $this->factory->post->create();
		$iterations = 10;

		for ( $i = 0; $i < $iterations; $i++ ) {
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => wp_rand(),
				)
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

	/**
	 * Test revision purging.
	 */
	public function test_purge_excess() {
		$post_id    = $this->factory->post->create();
		$iterations = 10;
		$limit      = 4;

		for ( $i = 0; $i < $iterations; $i++ ) {
			wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => wp_rand(),
				)
			);
		}

		$revisions_to_purge = count( wp_get_post_revisions( $post_id ) );
		$this->assertEquals(
			$iterations,
			$revisions_to_purge,
			'Failed to assert that there are revisions to purge.'
		);

		update_post_meta( $post_id, static::$meta_key, $limit );

		$this->assertEquals(
			$limit,
			wp_revisions_to_keep( get_post( $post_id ) ),
			'Failed to assert that post is limited to a given number of revisions.'
		);

		$purge = WP_Revisions_Control::get_instance()->do_purge_excess( $post_id );
		$revisions_remaining = count( wp_get_post_revisions( $post_id ) );

		$this->assertEquals(
			4,
			$revisions_remaining,
			'Failed to assert that specified number of revisions were retained.'
		);

		$this->assertEquals(
			6,
			$purge['count'],
			'Failed to assert that response includes expected count of purged revisions.'
		);
	}
}
