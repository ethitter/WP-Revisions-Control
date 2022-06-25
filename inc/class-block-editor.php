<?php
/**
 * Support block editor (Gutenberg).
 *
 * @package WP_Revisions_Control
 */

namespace WP_Revisions_Control;

use WP_REST_Response;
use WP_REST_Request;
use WP_Revisions_Control;

/**
 * Class Block_Editor.
 */
class Block_Editor {
	use Singleton;

	/**
	 * Name of action used to clean up post's revisions via cron.
	 *
	 * @var string
	 */
	private $cron_action = 'wp_revisions_control_cron_purge';

	/**
	 * Prepare class.
	 *
	 * This is called at `init`, so cannot use that hook unless priority is
	 * greater than 10.
	 */
	private function setup() {
		add_action( 'rest_api_init', array( $this, 'action_rest_api_init' ) );
		add_filter( 'is_protected_meta', array( $this, 'filter_is_protected_meta' ), 10, 2 );
		add_action( $this->cron_action, array( WP_Revisions_Control::get_instance(), 'do_purge_excess' ), 10, 2 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'action_enqueue_block_editor_assets' ) );
	}

	/**
	 * Register REST API components for Gutenberg UI.
	 */
	public function action_rest_api_init() {
		if ( ! function_exists( 'register_rest_route' ) ) {
			return;
		}

		foreach ( array_keys( WP_Revisions_Control::get_instance()->get_post_types() ) as $post_type ) {
			register_meta(
				'post',
				WP_REVISIONS_CONTROL_LIMIT_META_KEY,
				array(
					'object_subtype' => $post_type,
					'type'           => 'string', // Can be empty, so must be string.
					'default'        => '',
					'single'         => true,
					'show_in_rest'   => true,
					'description'    => __(
						'Number of revisions to retain.',
						'wp-revisions-control'
					),
				)
			);
		}

		register_rest_route(
			'wp-revisions-control/v1',
			'schedule/(?P<id>[\d]+)/(?P<limit_override>[\d]+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'rest_api_schedule_purge' ),
				'permission_callback' => array( $this, 'rest_api_permission_callback' ),
				'args'                => array(
					'id'             => array(
						'required'          => true,
						'type'              => 'integer',
						'validate_callback' => array( $this, 'rest_api_validate_id' ),
					),
					'limit_override' => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => null,
						'validate_callback' => array( $this, 'rest_api_validate_id' ),
					),
				),
				'show_in_index'       => false,
			)
		);
	}

	/**
	 * Permissions callback for REST endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function rest_api_permission_callback( $request ) {
		return current_user_can(
			'edit_post',
			$request->get_param( 'id' )
		);
	}

	/**
	 * Validate post ID.
	 *
	 * @param int $input Post ID.
	 * @return bool
	 */
	public function rest_api_validate_id( $input ) {
		return is_numeric( $input );
	}

	/**
	 * Schedule cleanup of post's excess revisions.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function rest_api_schedule_purge( $request ) {
		$result = wp_schedule_single_event(
			time() + 3,
			$this->cron_action,
			array(
				$request->get_param( 'id' ),
				$request->get_param( 'limit_override' ),
			)
		);

		return rest_ensure_response( $result );
	}

	/**
	 * Allow our meta to be edited from Gutenberg.
	 *
	 * @param bool   $protected If meta is protected.
	 * @param string $meta_key  Meta key being checked.
	 * @return false
	 */
	public function filter_is_protected_meta( $protected, $meta_key ) {
		if ( WP_REVISIONS_CONTROL_LIMIT_META_KEY === $meta_key ) {
			return false;
		}

		return $protected;
	}

	/**
	 * Register Gutenberg script.
	 */
	public function action_enqueue_block_editor_assets() {
		global $pagenow;

		if ( 'widgets.php' === $pagenow ) {
			return;
		}

		$handle     = 'wp-revisions-control-block-editor';
		$asset_data = require_once dirname( __DIR__ ) . '/assets/build/gutenberg.asset.php';

		wp_enqueue_script(
			$handle,
			plugins_url(
				'assets/build/gutenberg.js',
				__DIR__
			),
			$asset_data['dependencies'],
			$asset_data['version']
		);

		wp_localize_script(
			$handle,
			'wpRevisionsControlBlockEditorSettings',
			array(
				'metaKey' => WP_REVISIONS_CONTROL_LIMIT_META_KEY,
			)
		);

		wp_set_script_translations(
			$handle,
			'wp-revisions-control',
			dirname( __DIR__ ) . '/languages'
		);
	}
}
