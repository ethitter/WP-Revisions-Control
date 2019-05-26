<?php
/**
 * Bulk actions.
 *
 * @package WP_Revisions_Control
 */

/**
 * Class WP_Revisions_Control_Bulk_Actions.
 */
class WP_Revisions_Control_Bulk_Actions {
	/**
	 * Supported post types.
	 *
	 * @var array
	 */
	protected $post_types;

	/**
	 * Base for bulk action names.
	 *
	 * @var string
	 */
	protected $action_base = 'wp_rev_ctl_bulk_';

	/**
	 * Custom bulk actions.
	 *
	 * @var array
	 */
	protected $actions;

	/**
	 * Constructor.
	 *
	 * @param array $post_types Supported post types.
	 */
	public function __construct( $post_types ) {
		if ( empty( $post_types ) || ! is_array( $post_types ) ) {
			return;
		}

		$this->post_types = $post_types;
		$this->register_actions();

		add_action( 'load-edit.php', array( $this, 'setup' ) );
	}

	/**
	 * Register custom actions.
	 */
	protected function register_actions() {
		$actions = array();

		$actions[ $this->action_base . 'purge_excess' ] = __( 'Purge excess revisions', 'wp_revisions_control' );
		$actions[ $this->action_base . 'purge_all' ]    = __( 'Purge ALL revisions', 'wp_revisions_control' );

		$this->actions = $actions;
	}

	/**
	 * Register various hooks.
	 */
	public function setup() {
		$screen = get_current_screen();

		if ( null === $screen ) {
			return;
		}

		$post_types = array_keys( $this->post_types );

		if ( ! in_array( $screen->post_type, $post_types, true ) ) {
			return;
		}

		if ( 'edit' !== $screen->base ) {
			return;
		}

		add_filter( 'bulk_actions-' . $screen->id, array( $this, 'add_actions' ) );
		add_filter( 'handle_bulk_actions-' . $screen->id, array( $this, 'handle_action' ), 10, 3 );
		// TODO: messages.
	}

	/**
	 * Add our actions.
	 *
	 * @param string[] $actions Array of available actions.
	 * @return array
	 */
	public function add_actions( $actions ) {
		return array_merge( $actions, $this->actions );
	}

	/**
	 * Handle our bulk actions.
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param string $action      Bulk action being taken.
	 * @param array  $ids         Object IDs to manipulate.
	 * @return string
	 */
	public function handle_action( $redirect_to, $action, $ids ) {
		if ( ! array_key_exists( $action, $this->actions ) ) {
			return $redirect_to;
		}

		$action = str_replace( $this->action_base, '', $action );

		switch ( $action ) {
			case 'purge_all':
				$this->purge_all( $ids );
				break;

			case 'purge_excess':
				$this->purge_excess( $ids );
				break;

			default:
				break;
		}

		// TODO: implement and add a query string to trigger a message.
		return $redirect_to;
	}

	/**
	 * Remove all revisions from the given IDs.
	 *
	 * @param array $ids Object IDs.
	 */
	protected function purge_all( $ids ) {
		foreach ( $ids as $id ) {
			WP_Revisions_Control::get_instance()->do_purge_all( $id );
		}
	}

	/**
	 * Remove excess revisions from the given IDs.
	 *
	 * @param array $ids Object IDs.
	 */
	protected function purge_excess( $ids ) {
		foreach ( $ids as $id ) {
			WP_Revisions_Control::get_instance()->do_purge_excess( $id );
		}
	}
}
