<?php
/**
 *
 * @package   My Portfolio
 * @author    Granth <granthweb@gmail.com>
 * @link      http://granthweb.com
 * @copyright 2016 Granth
 *
 * Plugin Name: My Portfolio 
 * Description: Portfolio manager plugin. This plugin allows you to manage, edit, and create new portfolios, showcases or teasers.
 * Version:     1.6.4
 * Text Domain: my_portfolio_textdomain
 * Domain Path: /lang
 */

/* Prevent direct call */
if ( ! defined( 'RES' ) ) { die; }

/* Load includes */
require_once( plugin_dir_path( __FILE__ ) . 'class_my_portfolio.php' );
require_once( plugin_dir_path( __FILE__ ) . trailingslashit( 'includes/vc' ) . 'class_vc_extend.php' );

/* Register hooks */
register_activation_hook( __FILE__, array( 'My_Portfolio', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'My_Portfolio', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( 'My_Portfolio', 'uninstall' ) );

/* Init */
My_Portfolio::get_instance();


?>