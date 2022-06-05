<?php
/**
 * Test block-editor features.
 *
 * @package WP_Revisions_Control
 */

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
		$this->markTestIncomplete();
	}

	/**
	 * Test REST permissions callback.
	 *
	 * @covers ::rest_api_permission_callback()
	 */
	public function test_rest_api_permission_callback() {
		$this->markTestIncomplete();
	}

	/**
	 * Test scheduling.
	 *
	 * @covers ::rest_api_schedule_purge()
	 */
	public function test_rest_api_schedule_purge() {
		$this->markTestIncomplete();
	}

	/**
	 * Test override for what is considered protected meta.
	 *
	 * @covers ::filter_is_protected_meta()
	 */
	public function test_filter_is_protected_meta() {
		$this->markTestIncomplete();
	}
}
