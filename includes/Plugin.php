<?php
/**
 * Main plugin class.
 *
 * @package Advance\CustomFee
 * @since 1.0.0
 */

declare(strict_types=1);

namespace Advance\CustomFee;

use Advance\CustomFee\Admin\Admin;
use Advance\CustomFee\Frontend\CustomFee;

/**
 * Class Plugin.
 *
 * @package Advance\CustomFee
 */
class Plugin {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	public $path;

	/**
	 * Plugin's url.
	 *
	 * @var string
	 */
	public $url;

	/**
	 * Assets directory path.
	 *
	 * @var string
	 */
	public $assets_dir;

	/**
	 * Fire the plugin initialization step.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->path       = dirname( __FILE__, 2 );
		$this->url        = plugin_dir_url( trailingslashit( dirname( __FILE__, 2 ) ) . 'custom-fee.php' );
		$this->assets_dir = trailingslashit( $this->url ) . 'assets/';

		$this->includes();

		add_action( 'init', [ $this, 'register_posttype' ] );
		add_action( 'init', [ $this, 'init_classes' ] );
	}

	/**
	 * Register fee post type
	 *
	 * @return void
	 */
	public function register_posttype(): void {
		$labels = array(
			'name'               => _x( 'Fee', 'Post type general name', 'custom-fee' ),
			'singular_name'      => _x( 'Fee', 'Post type singular name', 'custom-fee' ),
			'menu_name'          => _x( 'Fees', 'Admin Menu text', 'custom-fee' ),
			'name_admin_bar'     => _x( 'Fee', 'Add New on Toolbar', 'custom-fee' ),
			'add_new'            => __( 'Add New', 'custom-fee' ),
			'add_new_item'       => __( 'Add New Fee', 'custom-fee' ),
			'new_item'           => __( 'New Fee', 'custom-fee' ),
			'edit_item'          => __( 'Edit Fee', 'custom-fee' ),
			'view_item'          => __( 'View Fee', 'custom-fee' ),
			'all_items'          => __( 'All Fees', 'custom-fee' ),
			'search_items'       => __( 'Search Fees', 'custom-fee' ),
			'parent_item_colon'  => __( 'Parent Fees:', 'custom-fee' ),
			'not_found'          => __( 'No fees found.', 'custom-fee' ),
			'not_found_in_trash' => __( 'No fees found in trash.', 'custom-fee' ),

		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'wc_custom_fee' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'excerpt' ),
		);

		register_post_type( 'wc_custom_fee', $args );
	}

	/**
	 * Includes custom files
	 *
	 * @return void
	 */
	public function includes(): void {
		require_once trailingslashit( $this->path ) . 'includes/functions.php';
	}

	/**
	 * Init all classes
	 *
	 * @return void
	 */
	public function init_classes(): void {
		new Ajax();
		new Admin();
		new CustomFee();
	}

}
