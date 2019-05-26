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
	 * Singleton.
	 *
	 * @var static
	 */
	private static $__instance;

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
	 * Silence is golden!
	 */
	private function __construct() {}

	/**
	 * Singleton implementation.
	 *
	 * @param array $post_types Supported post types, used only on instantiation.
	 * @return static
	 */
	public static function get_instance( $post_types = array() ) {
		if ( ! is_a( static::$__instance, __CLASS__ ) ) {
			static::$__instance = new self();

			static::$__instance->setup( $post_types );
		}

		return static::$__instance;
	}

	/**
	 * One-time actions.
	 *
	 * @param array $post_types Supported post types.
	 */
	public function setup( $post_types ) {
		if ( empty( $post_types ) || ! is_array( $post_types ) ) {
			return;
		}

		$this->post_types = $post_types;
		$this->register_actions();

		add_action( 'load-edit.php', array( $this, 'register_admin_hooks' ) );
		add_filter( 'removable_query_args', array( $this, 'remove_message_query_args' ) );
	}

	/**
	 * Register custom actions.
	 */
	protected function register_actions() {
		$actions = array();

		$actions[ $this->action_base . 'purge_excess' ] = __(
			'Purge excess revisions',
			'wp_revisions_control'
		);

		$actions[ $this->action_base . 'purge_all' ] = __(
			'Purge ALL revisions',
			'wp_revisions_control'
		);

		$this->actions = $actions;
	}

	/**
	 * Register various hooks.
	 */
	public function register_admin_hooks() {
		$screen = get_current_screen();

		if ( null === $screen ) {
			return;
		}

		$post_types = array_keys( $this->post_types );

		if ( ! in_array( $screen->post_type, $post_types, true ) ) {
			return;
		}

		$post_type_caps = get_post_type_object( $screen->post_type )->cap;
		$user_can       = (
			current_user_can( $post_type_caps->edit_posts ) &&
			current_user_can( $post_type_caps->edit_published_posts ) &&
			current_user_can( $post_type_caps->edit_others_posts )
		);
		$user_can       = apply_filters(
			'wp_revisions_control_current_user_can_bulk_actions',
			$user_can,
			$screen->post_type
		);

		if ( ! $user_can ) {
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
		$args[] = $this->action_base . 'nonce';

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

		$response = array_fill_keys( $this->get_message_query_args(), 0 );

		switch ( str_replace( $this->action_base, '', $action ) ) {
			case 'purge_all':
				$this->purge_all( $ids );
				$response[ $action ] = 1;
				break;

			case 'purge_excess':
				$this->purge_excess( $ids );
				$response[ $action ] = 1;
				break;

			case 'nonce':
				break;

			default:
				$response[ $this->action_base . 'missing' ] = 1;
				break;
		}

		if ( is_array( $response ) ) {
			$response[ $this->action_base . 'nonce' ] = wp_create_nonce( $this->action_base );
			$redirect_to                              = add_query_arg( $response, $redirect_to );
		}

		return $redirect_to;
	}

	/**
	 * Remove all revisions from the given IDs.
	 *
	 * @param array $ids Object IDs.
	 */
	protected function purge_all( $ids ) {
		$plugin = WP_Revisions_Control::get_instance();

		foreach ( $ids as $id ) {
			$plugin->do_purge_all( $id );
		}
	}

	/**
	 * Remove excess revisions from the given IDs.
	 *
	 * @param array $ids Object IDs.
	 */
	protected function purge_excess( $ids ) {
		$plugin = WP_Revisions_Control::get_instance();

		foreach ( $ids as $id ) {
			$plugin->do_purge_excess( $id );
		}
	}

	/**
	 * Render admin notices.
	 */
	public function admin_notices() {
		$message = null;

		$nonce_key = $this->action_base . 'nonce';

		if (
			! isset( $_GET[ $nonce_key ] ) ||
			! wp_verify_nonce( sanitize_text_field( $_GET[ $nonce_key ] ), $this->action_base )
		) {
			return;
		}

		foreach ( $this->get_message_query_args() as $arg ) {
			if ( isset( $_GET[ $arg ] ) && 1 === (int) $_GET[ $arg ] ) {
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

			case 'nonce':
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

		if ( ! isset( $message, $type ) ) {
			return;
		}

		?>
		<div class="notice is-dismissible <?php echo esc_attr( $type ); ?>">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}
}
