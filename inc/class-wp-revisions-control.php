<?php
/**
 * Main plugin functionality.
 *
 * @package WP_Revisions_Control
 */

/**
 * Class WP_Revisions_Control.
 */
class WP_Revisions_Control {
	/**
	 * Singleton.
	 *
	 * @var static
	 */
	private static $__instance;

	/**
	 * Filter priority.
	 *
	 * @see $this->filter_priority()
	 *
	 * @var int
	 */
	private static $priority = null;

	/**
	 * Default filter priority.
	 *
	 * @var int
	 */
	private $priority_default = 50;

	/**
	 * Supported post types.
	 *
	 * @see $this->get_post_types()
	 *
	 * @var array
	 */
	private static $post_types = array();

	/**
	 * Plugin settings.
	 *
	 * @see $this->get_settings()
	 *
	 * @var array
	 */
	private static $settings = array();

	/**
	 * WordPress options page to display settings on.
	 *
	 * @var string
	 */
	private $settings_page = 'writing';

	/**
	 * Name of custom settings sections.
	 *
	 * @var string
	 */
	private $settings_section = 'wp_revisions_control';

	/**
	 * Meta key holding post's revisions limit.
	 *
	 * @var string
	 */
	private $meta_key_limit = '_wp_rev_ctl_limit';

	/**
	 * Silence is golden!
	 */
	private function __construct() {}

	/**
	 * Singleton implementation.
	 *
	 * @return static
	 */
	public static function get_instance() {
		if ( ! is_a( static::$__instance, __CLASS__ ) ) {
			static::$__instance = new self();

			static::$__instance->setup();
		}

		return static::$__instance;
	}

	/**
	 * Register actions and filters at `init` so others can interact, if desired.
	 */
	private function setup() {
		add_action( 'plugins_loaded', array( $this, 'action_plugins_loaded' ) );
		add_action( 'init', array( $this, 'action_init' ) );
	}

	/**
	 * Load plugin translations.
	 */
	public function action_plugins_loaded() {
		load_plugin_textdomain(
			'wp_revisions_control',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}

	/**
	 * Register actions and filters.
	 */
	public function action_init() {
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );

		add_filter( 'wp_revisions_to_keep', array( $this, 'filter_wp_revisions_to_keep' ), $this->plugin_priority(), 2 );
	}

	/**
	 * Register plugin's admin-specific elements.
	 *
	 * Plugin title is intentionally not translatable.
	 */
	public function action_admin_init() {
		$post_types = $this->get_post_types();

		// Plugin setting section.
		register_setting( $this->settings_page, $this->settings_section, array( $this, 'sanitize_options' ) );

		add_settings_section( $this->settings_section, 'WP Revisions Control', array( $this, 'settings_section_intro' ), $this->settings_page );

		foreach ( $post_types as $post_type => $name ) {
			add_settings_field( $this->settings_section . '-' . $post_type, $name, array( $this, 'field_post_type' ), $this->settings_page, $this->settings_section, array( 'post_type' => $post_type ) );
		}

		// Post-level functionality.
		add_action( 'add_meta_boxes', array( $this, 'action_add_meta_boxes' ), 10, 2 );
		add_action( 'wp_ajax_' . $this->settings_section . '_purge', array( $this, 'ajax_purge' ) );
		add_action( 'save_post', array( $this, 'action_save_post' ) );

		// Bulk actions.
		WP_Revisions_Control_Bulk_Actions::get_instance( $post_types );
	}

	/**
	 * PLUGIN SETTINGS SECTION
	 * FOUND UNDER SETTINGS > WRITING
	 */

	/**
	 * Display assistive text in settings section.
	 */
	public function settings_section_intro() {
		?>
		<p><?php esc_html_e( 'Set the number of revisions to save for each post type listed. To retain all revisions for a given post type, leave the field empty.', 'wp_revisions_control' ); ?></p>
		<p><?php esc_html_e( 'If a post type isn\'t listed, revisions are not enabled for that post type.', 'wp_revisions_control' ); ?></p>
		<?php

		// Display a note if the plugin priority is other than the default.
		// Will be useful when debugging issues later.
		if ( $this->plugin_priority() !== $this->priority_default ) :
			?>
			<p>
				<?php
				printf(
					/* translators: 1. Filter tag. */
					esc_html__(
						'A local change is causing this plugin\'s functionality to run at a priority other than the default. If you experience difficulties with the plugin, please unhook any functions from the %1$s filter.',
						'wp_revisions_control'
					),
					'<code>wp_revisions_control_priority</code>'
				);
				?>
			</p>
			<?php
		endif;
	}

	/**
	 * Render field for each post type.
	 *
	 * @param array $args Field arguments.
	 */
	public function field_post_type( $args ) {
		$revisions_to_keep = $this->get_revisions_to_keep( $args['post_type'], true );
		?>
		<input type="text" name="<?php echo esc_attr( $this->settings_section . '[' . $args['post_type'] . ']' ); ?>" value="<?php echo esc_attr( $revisions_to_keep ); ?>" class="small-text" />
		<?php
	}

	/**
	 * Sanitize plugin settings.
	 *
	 * @param array $options Unsanitized settings.
	 * @return array
	 */
	public function sanitize_options( $options ) {
		$options_sanitized = array();

		if ( is_array( $options ) ) {
			foreach ( $options as $post_type => $to_keep ) {
				$type_length = strlen( $to_keep );

				if ( 0 === $type_length ) {
					$to_keep = -1;
				} else {
					$to_keep = (int) $to_keep;
				}

				// Lowest possible value is -1, used to indicate infinite revisions are stored.
				if ( -1 > $to_keep ) {
					$to_keep = -1;
				}

				$options_sanitized[ $post_type ] = $to_keep;
			}
		}

		return $options_sanitized;
	}

	/**
	 * REVISIONS QUANTITY OVERRIDES.
	 */

	/**
	 * Allow others to change the priority this plugin's functionality runs at
	 *
	 * @uses apply_filters
	 * @return int
	 */
	private function plugin_priority() {
		if ( is_null( self::$priority ) ) {
			$plugin_priority = apply_filters( 'wp_revisions_control_priority', $this->priority_default );

			self::$priority = is_numeric( $plugin_priority ) ? (int) $plugin_priority : $this->priority_default;
		}

		return self::$priority;
	}

	/**
	 * Override number of revisions to keep using plugin's settings.
	 *
	 * Can either be post-specific or universal.
	 *
	 * @param int     $qty  Number of revisions to keep.
	 * @param WP_Post $post Post object.
	 * @return int
	 */
	public function filter_wp_revisions_to_keep( $qty, $post ) {
		$post_limit = get_post_meta( $post->ID, $this->meta_key_limit, true );

		if ( 0 < strlen( $post_limit ) ) {
			$qty = $post_limit;
		} else {
			$post_type = get_post_type( $post ) ? get_post_type( $post ) : $post->post_type;
			$settings  = $this->get_settings();

			if ( array_key_exists( $post_type, $settings ) ) {
				$qty = $settings[ $post_type ];
			}
		}

		return $qty;
	}

	/**
	 * POST-LEVEL FUNCTIONALITY.
	 */

	/**
	 * Override Core's revisions metabox.
	 *
	 * @param string $post_type Post type.
	 * @param object $post      Post object.
	 */
	public function action_add_meta_boxes( $post_type, $post ) {
		if ( post_type_supports( $post_type, 'revisions' ) && 'auto-draft' !== get_post_status() && count( wp_get_post_revisions( $post ) ) > 1 ) {
			// Replace the metabox.
			remove_meta_box( 'revisionsdiv', null, 'normal' );
			add_meta_box(
				'revisionsdiv-wp-rev-ctl',
				__(
					'Revisions',
					'wp_revisions_control'
				),
				array(
					$this,
					'revisions_meta_box',
				),
				null,
				'normal',
				'core'
			);

			// A bit of JS for us.
			$handle = 'wp-revisions-control-post';
			wp_enqueue_script( $handle, plugins_url( 'js/post.js', __DIR__ ), array( 'jquery' ), '20131205', true );
			wp_localize_script(
				$handle,
				$this->settings_section,
				array(
					'namespace'       => $this->settings_section,
					'action_base'     => $this->settings_section,
					'processing_text' => __( 'Processing&hellip;', 'wp_revisions_control' ),
					'ays'             => __( 'Are you sure you want to remove revisions from this post?', 'wp_revisions_control' ),
					'autosave'        => __( 'Autosave', 'wp_revisions_control' ),
					'nothing_text'    => wpautop( __( 'There are no revisions to remove.', 'wp_revisions_control' ) ),
					'error'           => __( 'An error occurred. Please refresh the page and try again.', 'wp_revisions_control' ),
				)
			);

			// Add some styling to our metabox additions.
			add_action( 'admin_head', array( $this, 'action_admin_head' ), 999 );
		}
	}

	/**
	 * Render Revisions metabox with plugin's additions
	 *
	 * @param WP_Post $post Post object.
	 */
	public function revisions_meta_box( $post ) {
		post_revisions_meta_box( $post );

		?>
		<div id="<?php echo esc_attr( $this->settings_section ); ?>">
			<h4>WP Revisions Control</h4>

			<p class="button purge" data-postid="<?php the_ID(); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( $this->settings_section . '_purge' ) ); ?>"><?php _e( 'Purge these revisions', 'wp_revisions_control' ); ?></p>

			<p>
				<?php
				printf(
					/* translators: 1. Text input field. */
					esc_html__(
						'Limit this post to %1$s revisions. Leave this field blank for default behavior.',
						'wp_revisions_control'
					),
					'<input type="text" name="' . esc_attr( $this->settings_section ) . '_qty" value="' . esc_attr( $this->get_post_revisions_to_keep( $post->ID ) ) . '" id="' . esc_attr( $this->settings_section ) . '_qty" size="2" />'
				);
				?>

				<?php wp_nonce_field( $this->settings_section . '_limit', $this->settings_section . '_limit_nonce', false ); ?>
			</p>
		</div><!-- #<?php echo esc_attr( $this->settings_section ); ?> -->
		<?php
	}

	/**
	 * Process a post-specific request to purge revisions.
	 */
	public function ajax_purge() {
		$post_id = isset( $_REQUEST['post_id'] ) ? (int) $_REQUEST['post_id'] : false;

		// Hold the current state of this Ajax request.
		$response = array();

		// Check for necessary data and capabilities.
		if ( ! $post_id ) {
			$response['error'] = __( 'No post ID was provided. Please refresh the page and try again.', 'wp_revisions_control' );
		} elseif ( ! check_ajax_referer( $this->settings_section . '_purge', 'nonce', false ) ) {
			$response['error'] = __( 'Invalid request. Please refresh the page and try again.', 'wp_revisions_control' );
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			$response['error'] = __( 'You are not allowed to edit this post.', 'wp_revisions_control' );
		}

		// Request is valid if $response is still empty, as no errors arose above.
		if ( empty( $response ) ) {
			$response = $this->do_purge_all( $post_id );
		}

		// Pass the response back to JS.
		echo json_encode( $response );
		exit;
	}

	/**
	 * Remove all revisions from a given post ID.
	 *
	 * @param int $post_id Post ID to purge of revisions.
	 * @return array
	 */
	public function do_purge_all( $post_id ) {
		$response = array();

		$revisions = wp_get_post_revisions( $post_id );

		$count = count( $revisions );

		foreach ( $revisions as $revision ) {
			wp_delete_post_revision( $revision->ID );
		}

		$response['success'] = sprintf(
			/* translators: 1. Number of removed revisions, already formatted for locale. */
			esc_html__(
				'Removed %1$s revisions associated with this post.',
				'wp_revisions_control'
			),
			number_format_i18n( $count, 0 )
		);

		$response['count'] = $count;

		return $response;
	}

	/**
	 * Remove any revisions in excess of a post's limit.
	 *
	 * @param int $post_id Post ID to purge of excess revisions.
	 * @return array
	 */
	public function do_purge_excess( $post_id ) {
		$response = array(
			'count' => 0,
		);

		$to_keep = wp_revisions_to_keep( get_post( $post_id ) );

		if ( $to_keep < 0 ) {
			$response['success'] = __(
				'No revisions to remove.',
				'wp_revisions_control'
			);

			return $response;
		}

		$revisions      = wp_get_post_revisions( $post_id );
		$starting_count = count( $revisions );

		if ( $starting_count <= $to_keep ) {
			$response['success'] = __(
				'No revisions to remove.',
				'wp_revisions_control'
			);

			return $response;
		}

		$to_remove         = array_slice( $revisions, $to_keep, null, true );
		$response['count'] = count( $to_remove );

		foreach ( $to_remove as $revision ) {
			wp_delete_post_revision( $revision->ID );
		}

		return $response;
	}

	/**
	 * Sanitize and store post-specifiy revisions quantity.
	 *
	 * @param int $post_id Post ID.
	 */
	public function action_save_post( $post_id ) {
		$nonce = $this->settings_section . '_limit_nonce';
		$qty   = $this->settings_section . '_qty';

		if ( isset( $_POST[ $nonce ], $_POST[ $qty ] ) && wp_verify_nonce( sanitize_text_field( $_POST[ $nonce ] ), $this->settings_section . '_limit' ) ) {
			$limit = (int) $_POST[ $qty ];

			if ( -1 === $limit || empty( $limit ) ) {
				delete_post_meta( $post_id, $this->meta_key_limit );
			} else {
				update_post_meta( $post_id, $this->meta_key_limit, absint( $limit ) );
			}
		}
	}

	/**
	 * Add a border between the regular revisions list and this plugin's additions.
	 */
	public function action_admin_head() {
		?>
		<style type="text/css">
			#revisionsdiv-wp-rev-ctl #<?php echo esc_attr( $this->settings_section ); ?> {
				border-top: 1px solid #dfdfdf;
				padding-top: 0;
				margin-top: 20px;
			}

			#revisionsdiv-wp-rev-ctl #<?php echo esc_attr( $this->settings_section ); ?> h4 {
				border-top: 1px solid #fff;
				padding-top: 1.33em;
				margin-top: 0;
			}
		</style>
		<?php
	}

	/**
	 * PLUGIN UTILITIES.
	 */

	/**
	 * Retrieve plugin settings.
	 *
	 * @return array
	 */
	private function get_settings() {
		static $hash = null;

		$settings = get_option( $this->settings_section, array() );

		if ( empty( self::$settings ) || $hash !== $this->hash_settings( $settings ) ) {
			$post_types = $this->get_post_types();

			if ( ! is_array( $settings ) ) {
				$settings = array();
			}

			$merged_settings = array();

			foreach ( $post_types as $post_type => $name ) {
				if ( array_key_exists( $post_type, $settings ) ) {
					$merged_settings[ $post_type ] = (int) $settings[ $post_type ];
				} else {
					$merged_settings[ $post_type ] = - 1;
				}
			}

			self::$settings = $merged_settings;
			$hash           = $this->hash_settings( self::$settings );
		}

		return self::$settings;
	}

	/**
	 * Hash settings to limit re-parsing.
	 *
	 * @param array $settings Settings array.
	 * @return string
	 */
	private function hash_settings( $settings ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		return md5( serialize( $settings ) );
	}

	/**
	 * Retrieve array of supported post types and their labels.
	 *
	 * @return array
	 */
	private function get_post_types() {
		if ( empty( self::$post_types ) ) {
			foreach ( get_post_types() as $type ) {
				if ( post_type_supports( $type, 'revisions' ) ) {
					$object = get_post_type_object( $type );

					if ( null === $object ) {
						continue;
					}

					if ( property_exists( $object, 'labels' ) && property_exists( $object->labels, 'name' ) ) {
						$name = $object->labels->name;
					} else {
						$name = $object->name;
					}

					self::$post_types[ $type ] = $name;
				}
			}
		}

		return self::$post_types;
	}

	/**
	 * Retrieve number of revisions to keep for a given post type.
	 *
	 * @param string $post_type     Post type.
	 * @param bool   $blank_for_all Should blank value be used to indicate all are kept.
	 * @return int|string
	 */
	private function get_revisions_to_keep( $post_type, $blank_for_all = false ) {
		// wp_revisions_to_keep() accepts a post object, not just the post type.
		// We construct a new WP_Post object to ensure anything hooked to the wp_revisions_to_keep filter has the same basic data WP provides.
		$_post   = new WP_Post( (object) array( 'post_type' => $post_type ) );
		$to_keep = wp_revisions_to_keep( $_post );

		if ( $blank_for_all && ( -1 === $to_keep || '-1' === $to_keep ) ) {
			return '';
		} else {
			return (int) $to_keep;
		}
	}

	/**
	 * Retrieve number of revisions to keep for a give post.
	 *
	 * @param int $post_id Post ID.
	 * @return int|string
	 */
	private function get_post_revisions_to_keep( $post_id ) {
		$to_keep = get_post_meta( $post_id, $this->meta_key_limit, true );

		if ( empty( $to_keep ) || -1 === $to_keep || '-1' === $to_keep ) {
			$to_keep = '';
		} else {
			$to_keep = (int) $to_keep;
		}

		return $to_keep;
	}
}

WP_Revisions_Control::get_instance();
