<?php
/*
Plugin Name: WP Revisions Control
Plugin URI: http://www.ethitter.com/plugins/date-based-taxonomy-archives/
Description: Control how many revisions are stored for each post type
Author: Erick Hitter
Version: 0.1
Author URI: http://www.ethitter.com/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class WP_Revisions_Control {
	/**
	 * Singleton
	 */
	private static $__instance = null;

	/**
	 * Class variables
	 */
	private static $post_types = array();

	private $settings_page = 'writing';
	private $settings_section = 'wp_revisions_control';

	private $option_name = 'wp_revisions_ctl';

	/**
	 * Silence is golden!
	 */
	private function __construct() {}

	/**
	 * Singleton implementation
	 *
	 * @uses self::setup
	 * @return object
	 */
	public static function get_instance() {
		if ( ! is_a( self::$__instance, __CLASS__ ) ) {
			self::$__instance = new self;

			self::$__instance->setup();
		}

		return self::$__instance;
	}

	/**
	 * Magic getter to provide access to class variables
	 *
	 * @param string $name
	 * @return mixed
	 */
	// public function __get( $name ) {
	//	if ( property_exists( $this, $name ) )
	//		return $this->$name;
	//	else
	//		return null;
	// }

	/**
	 * Register actions and filters
	 *
	 * @uses add_action
	 * @uses add_filter
	 * @return null
	 */
	private function setup() {
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
	}

	/**
	 *
	 */
	public function action_admin_init() {
		register_setting( $this->settings_page, $this->option_name, array( $this, 'sanitize_options' ) );

		add_settings_section( $this->settings_section, __( 'WP Revisions Control', 'wp_revisions_control' ), '__return_false', $this->settings_page );

		foreach ( $this->get_post_types() as $post_type => $name ) {
			add_settings_field( $this->settings_section . '-' . $post_type, $name, array( $this, 'field_post_type' ), $this->settings_page, $this->settings_section, array( 'post_type' => $post_type ) );
		}
	}

	/**
	 *
	 */
	public function field_post_type( $args ) {

	}

	/**
	 *
	 */
	public function sanitize_options( $options ) {

	}

	/**
	 *
	 */
	private function get_post_types() {
		if ( empty( self::$post_types ) ) {
			$types = get_post_types();

			foreach ( $types as $type ) {
				if ( post_type_supports( $type, 'revisions' ) ) {
					$object = get_post_type_object( $type );

					if ( property_exists( $object, 'labels' ) && property_exists( $object->labels, 'name' ) )
						$name = $object->labels->name;
					else
						$name = $object->name;

					self::$post_types[ $type ] = $name;
				}
			}

			self::$post_types = array_unique( self::$post_types );
		}

		return self::$post_types;
	}
}
WP_Revisions_Control::get_instance();
