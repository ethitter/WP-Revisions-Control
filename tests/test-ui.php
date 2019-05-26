<?php
/**
 * Test UI methods.
 *
 * @package WP_Revisions_Control
 */

/**
 * Class TestUI.
 */
class TestUI extends WP_UnitTestCase {
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
	 * Test meta box with no meta set.
	 */
	public function test_no_meta() {
		$post_id    = $this->factory->post->create();

		ob_start();
		WP_Revisions_Control::get_instance()->revisions_meta_box( get_post( $post_id ) );
		$meta_box = ob_get_clean();

		$this->assertContains(
			'value=""',
			$meta_box,
			'Failed to assert that meta box has no value when no setting exists.'
		);
	}

	/**
	 * Test meta box with no limit set.
	 */
	public function test_no_limit() {
		$post_id    = $this->factory->post->create();
		update_post_meta( $post_id, static::$meta_key, -1 );

		ob_start();
		WP_Revisions_Control::get_instance()->revisions_meta_box( get_post( $post_id ) );
		$meta_box = ob_get_clean();

		$this->assertContains(
			'value=""',
			$meta_box,
			'Failed to assert that meta box has no value when no limit exists.'
		);
	}

	/**
	 * Test settings-field output when no options are set.
	 */
	public function test_settings_fields_no_options() {
		ob_start();
		WP_Revisions_Control::get_instance()->field_post_type( array( 'post_type' => 'post' ) );
		$post_field = ob_get_clean();

		ob_start();
		WP_Revisions_Control::get_instance()->field_post_type( array( 'post_type' => 'page' ) );
		$page_field = ob_get_clean();

		$name_format  = 'name="%1$s[%2$s]"';
		$value_format = 'value=""';

		$this->assertContains(
			sprintf(
				$name_format,
				static::$settings_section,
				'post'
			),
			$post_field,
			'Failed to assert that post field had correct name for post type.'
		);

		$this->assertContains(
			sprintf(
				$name_format,
				static::$settings_section,
				'page'
			),
			$page_field,
			'Failed to assert that page field had correct name for post type.'
		);

		$this->assertContains(
			$value_format,
			$post_field,
			'Failed to assert that post field had correct value.'
		);

		$this->assertContains(
			$value_format,
			$page_field,
			'Failed to assert that page field had correct value.'
		);
	}

	/**
	 * Test settings-field output when options are set.
	 */
	public function test_settings_fields_with_options() {
		$value = 12;

		update_option(
			static::$settings_section,
			[
				'post' => $value,
			]
		);

		ob_start();
		WP_Revisions_Control::get_instance()->field_post_type( array( 'post_type' => 'post' ) );
		$post_field = ob_get_clean();

		$name_format  = 'name="%1$s[%2$s]"';
		$value_format = 'value="%1$s"';

		$this->assertContains(
			sprintf(
				$name_format,
				static::$settings_section,
				'post'
			),
			$post_field,
			'Failed to assert that post field had correct name for post type.'
		);

		$this->assertContains(
			sprintf(
				$value_format,
				$value
			),
			$post_field,
			'Failed to assert that post field had correct value.'
		);
	}

	/**
	 * Test settings-field output when options are set.
	 */
	public function test_settings_fields_with_options_keep_all() {
		$value = -1;

		update_option(
			static::$settings_section,
			[
				'post' => $value,
			]
		);

		ob_start();
		WP_Revisions_Control::get_instance()->field_post_type( array( 'post_type' => 'post' ) );
		$post_field = ob_get_clean();

		$name_format  = 'name="%1$s[%2$s]"';
		$value_format = 'value=""';

		$this->assertContains(
			sprintf(
				$name_format,
				static::$settings_section,
				'post'
			),
			$post_field,
			'Failed to assert that post field had correct name for post type.'
		);

		$this->assertContains(
			sprintf(
				$value_format,
				$value
			),
			$post_field,
			'Failed to assert that post field had correct value.'
		);
	}
}
