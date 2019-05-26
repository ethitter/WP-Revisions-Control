<?php
/**
 * Load plugin.
 *
 * @package WP_Revisions_Control
 */

/**
 * Plugin Name: WP Revisions Control
 * Plugin URI: https://ethitter.com/plugins/wp-revisions-control/
 * Description: Control how many revisions are stored for each post type
 * Author: Erick Hitter
 * Version: 1.3
 * Author URI: https://ethitter.com/
 * Text Domain: wp_revisions_control
 * Domain Path: /languages/
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

require_once __DIR__ . '/inc/class-wp-revisions-control-bulk-actions.php';
require_once __DIR__ . '/inc/class-wp-revisions-control.php';
