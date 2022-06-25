<?php
/**
 * Plugin Name: WP Revisions Control
 * Plugin URI: https://ethitter.com/plugins/wp-revisions-control/
 * Description: Control how many revisions are stored for each post type
 * Author: Erick Hitter
 * Version: 1.4.3
 * Author URI: https://ethitter.com/
 * Text Domain: wp-revisions-control
 * Domain Path: /languages/
 *
 * @package WP_Revisions_Control
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

define( 'WP_REVISIONS_CONTROL_LIMIT_META_KEY', '_wp_rev_ctl_limit' );

require_once __DIR__ . '/inc/trait-singleton.php';
require_once __DIR__ . '/inc/class-block-editor.php';
require_once __DIR__ . '/inc/class-wp-revisions-control-bulk-actions.php';
require_once __DIR__ . '/inc/class-wp-revisions-control.php';
