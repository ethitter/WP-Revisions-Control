<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Test miscellaneous methods.
 *
 * @package WP_Revisions_Control
 */

namespace WP_Revisions_Control\Tests;

use WP_Revisions_Control;
use WP_UnitTestCase;

/**
 * Class TestMisc.
 */
class TestMisc extends WP_UnitTestCase {
	/**
	 * Test settings sanitization.
	 */
	public function test_settings_sanitization() {
		$input = array(
			'minus_ten'    => -10,
			'minus_one'    => -1,
			'zero'         => 0,
			'one'          => 1,
			'thirty'       => 30,
			'empty_string' => '',
			'bool_false'   => false,
			'bool_true'    => true,
			'null'         => null,
		);

		$expected = array(
			'minus_ten'    => -1,
			'minus_one'    => -1,
			'zero'         => 0,
			'one'          => 1,
			'thirty'       => 30,
			'empty_string' => -1,
			'bool_false'   => -1,
			'bool_true'    => 1,
			'null'         => -1,
		);

		$sanitized = WP_Revisions_Control::get_instance()->sanitize_options( $input );

		$this->assertEquals(
			$expected,
			$sanitized,
			'Failed to assert that options were sanitized correctly.'
		);
	}
}
