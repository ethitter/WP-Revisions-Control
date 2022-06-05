<?php
/**
 * Reusable singleton.
 *
 * @package WP_Revisions_Control
 */

namespace WP_Revisions_Control;

/**
 * Trait Singleton.
 */
trait Singleton {
	/**
	 * Singleton.
	 *
	 * @var static
	 */
	private static $__instance;

	/**
	 * Silence is golden!
	 */
	final private function __construct() {}

	/**
	 * Singleton implementation.
	 *
	 * @return static
	 */
	final public static function get_instance() {
		if ( ! is_a( static::$__instance, __CLASS__ ) ) {
			static::$__instance = new self();

			static::$__instance->setup();
		}

		return static::$__instance;
	}

	/**
	 * Prepare class.
	 */
	abstract public function setup();
}
