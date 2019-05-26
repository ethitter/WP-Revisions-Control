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
		add_filter( 'removable_query_args', array( $this, 'remove_message_query_args' ) );
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
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Remove message query arguments to prevent re-display.
	 *
	 * @param array $args Array of query variables to remove from URL.
	 * @return array
	 */
	public function remove_message_query_args( $args ) {
		return array_merge( $args, $this->get_message_query_args() );
	}

	/**
	 * Return array of supported query args that trigger admin notices.
	 *
	 * @return array
	 */
	protected function get_message_query_args() {
		$args   = array_keys( $this->actions );
		$args[] = $this->action_base . 'missing';

		return $args;
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

		$response = array(
			$action => 1,
		);

		switch ( str_replace( $this->action_base, '', $action ) ) {
			case 'purge_all':
				$this->purge_all( $ids );
				break;

			case 'purge_excess':
				$this->purge_excess( $ids );
				break;

			default:
				$response = array(
					$this->action_base . 'missing' => 1,
				);
				break;
		}

		if ( is_array( $response ) ) {
			$redirect_to = add_query_arg( $response, $redirect_to );
		}

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

	/**
	 * Render admin notices.
	 */
	public function admin_notices() {
		$message = null;

		foreach ( $this->get_message_query_args() as $arg ) {
			// phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
			if ( isset( $_GET[ $arg ] ) ) {
				$message = $arg;
				break;
			}
		}

		if ( null === $message ) {
			return;
		}

		$type = 'updated';

		switch ( str_replace( $this->action_base, '', $message ) ) {
			case 'purge_all':
				$message = __(
					'Purged all revisions.',
					'wp_revisions_control'
				);
				break;

			case 'purge_excess':
				$message = __(
					'Purged excess revisions.',
					'wp_revisions_control'
				);
				break;

			default:
			case 'missing':
				$message = __(
					'WP Revisions Control encountered an unspecified error.',
					'wp_revisions_control'
				);
				$type    = 'error';
				break;
		}

		?>
		<div class="notice is-dismissible <?php echo esc_attr( $type ); ?>">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}
}
