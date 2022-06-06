<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Test block-editor features.
 *
 * @package WP_Revisions_Control
 */

namespace WP_Revisions_Control\Tests;

use WP_Revisions_Control\Block_Editor;
use WP_UnitTestCase;
use WP_REST_Request;

/**
 * Class TestBlockEditor.
 *
 * @coversDefaultClass \WP_Revisions_Control\Block_Editor
 */
class TestBlockEditor extends WP_UnitTestCase {
	/**
	 * Test REST API additions.
	 *
	 * @covers ::action_rest_api_init()
	 */
	public function test_action_rest_api_init() {
		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		global $wp_meta_keys, $wp_rest_server;
		$wp_meta_keys   = null;
		$wp_rest_server = null;
		// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

		$object_type    = 'post';
		$object_subtype = 'post';

		// Prevent `_doing_it_wrong()` notice from `register_rest_route()`.
		remove_all_actions( 'rest_api_init' );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		do_action( 'rest_api_init' );

		$this->assertEmpty(
			get_registered_meta_keys( $object_type, $object_subtype ),
			'Failed to assert that no meta is registered at the outset.'
		);

		Block_Editor::get_instance()->action_rest_api_init();

		$this->assertArrayHasKey(
			WP_REVISIONS_CONTROL_LIMIT_META_KEY,
			get_registered_meta_keys( $object_type, $object_subtype ),
			'Failed to assert that meta is registered as expected.'
		);

		$this->assertArrayHasKey(
			'/wp-revisions-control/v1/schedule/(?P<id>[\d]+)/(?P<limit_override>[\d]+)',
			rest_get_server()->get_routes( 'wp-revisions-control/v1' ),
			'Failed to assert that REST route is registered as expected.'
		);
	}

	/**
	 * Test REST permissions callback.
	 *
	 * @covers ::rest_api_permission_callback()
	 */
	public function test_rest_api_permission_callback() {
		$editor  = $this->factory->user->create( array( 'role' => 'editor' ) );
		$author  = $this->factory->user->create( array( 'role' => 'author' ) );
		$post_id = $this->factory->post->create( array( 'post_author' => $editor ) );

		$request = new WP_REST_Request();
		$request->set_param( 'id', $post_id );

		wp_set_current_user( $editor );

		$this->assertTrue(
			Block_Editor::get_instance()->rest_api_permission_callback( $request ),
			'Failed to assert that editor can edit the post.'
		);

		wp_set_current_user( $author );

		$this->assertFalse(
			Block_Editor::get_instance()->rest_api_permission_callback( $request ),
			'Failed to assert that another author cannot edit the post.'
		);
	}

	/**
	 * Test scheduling without limit override.
	 *
	 * @covers ::rest_api_schedule_purge()
	 */
	public function test_rest_api_schedule_purge_no_override() {
		$post_id = $this->factory->post->create();
		$request = new WP_REST_Request();
		$request->set_param( 'id', $post_id );

		_set_cron_array( array() );

		$response = Block_Editor::get_instance()->rest_api_schedule_purge( $request );
		$this->assertTrue(
			$response->get_data(),
			'Failed to assert that job was scheduled successfully.'
		);

		$crons = json_encode( _get_cron_array() );

		$this->assertStringContainsString(
			'wp_revisions_control_cron_purge',
			$crons,
			'Failed to assert that an entry exists for the expected job.'
		);

		$this->assertStringContainsString(
			json_encode(
				array(
					'schedule' => false,
					'args'     => array( $post_id, null ),
				)
			),
			$crons,
			'Failed to assert that expected arguments are set in cron.'
		);
	}

	/**
	 * Test scheduling with limit override.
	 *
	 * @covers ::rest_api_schedule_purge()
	 */
	public function test_rest_api_schedule_purge_with_override() {
		$post_id        = $this->factory->post->create();
		$limit_override = 3;
		$request        = new WP_REST_Request();
		$request->set_param( 'id', $post_id );
		$request->set_param( 'limit_override', $limit_override );

		_set_cron_array( array() );

		$response = Block_Editor::get_instance()->rest_api_schedule_purge( $request );
		$this->assertTrue(
			$response->get_data(),
			'Failed to assert that job was scheduled successfully.'
		);

		$crons = json_encode( _get_cron_array() );

		$this->assertStringContainsString(
			'wp_revisions_control_cron_purge',
			$crons,
			'Failed to assert that an entry exists for the expected job.'
		);

		$this->assertStringContainsString(
			json_encode(
				array(
					'schedule' => false,
					'args'     => array( $post_id, $limit_override ),
				)
			),
			$crons,
			'Failed to assert that expected arguments are set in cron.'
		);
	}

	/**
	 * Test override for what is considered protected meta.
	 *
	 * @covers ::filter_is_protected_meta()
	 */
	public function test_filter_is_protected_meta() {
		$this->assertFalse(
			Block_Editor::get_instance()->filter_is_protected_meta(
				true,
				WP_REVISIONS_CONTROL_LIMIT_META_KEY
			),
			'Failed to assert that limit meta key is not protected.'
		);

		$this->assertTrue(
			Block_Editor::get_instance()->filter_is_protected_meta(
				true,
				'_test'
			),
			'Failed to assert that random key is protected.'
		);
	}
}
