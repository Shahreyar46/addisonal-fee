<?php
/**
Plugin Name: Advance fee manager for WooCommerce 
Plugin URI: https://github.com/Shahreyar46
Description: Manage your extra fees in WooCommerce conditionally in one place.
Version: 1.0.0
Author: Shahreyar Shimul
Author URI: https://github.com/Shahreyar46
Text Domain: wc-advance-fee-manager
License: GPL2
*/

/**
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

declare(strict_types=1);

namespace Advance\CustomFee;

// don't call the file directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$autoload_file = __DIR__ . '/vendor/autoload.php';

if ( ! file_exists( $autoload_file ) || ! is_readable( $autoload_file ) ) {
	return;
}

require_once $autoload_file;

/**
 * Get the main Plugin instance.
 *
 * @return Plugin
 */
function plugin(): Plugin {
	static $plugin;

	if ( null !== $plugin ) {
		return $plugin;
	}

	$plugin = new Plugin();

	return $plugin;
}

/**
 * Initialize the plugin.
 */
add_action(
	'plugins_loaded',
	function() {
		plugin()->run();
	}
);
