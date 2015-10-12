<?php
/**
 * The Custom wordpress maps plugin to speed up custom map inforamtion for Arrowhead Clients
 *
 *
 *
 * @package   Maps
 * @author    Sean Corgan <seancorgan@gmail.com>
 * @copyright 2015
 *
 * @wordpress-plugin
 * Plugin Name:       Maps
 * Description:       A custom plugin to exclusivly manage google map locations for clients.  Built exclusivly for Arrowhead Advertising
 * Version:           1.0.3
 * Author:            Sean Corgan
 * Text Domain:       maps-locale
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/<owner>/<repo>
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-maps.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'Maps', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Maps', 'deactivate' ) );


add_action( 'plugins_loaded', array( 'Maps', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if ( is_admin() ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-maps-admin.php' );
	add_action( 'plugins_loaded', array( 'MapsAdmin', 'get_instance' ) );

}
