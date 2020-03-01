<?php
/**
 * WordPress Multisite Recaptcha
 *
 * Adds a Google Recaptcha validator on the login and "forgot your password" forms.
 *
 * @link              https://marioyepes.com
 * @since             1.0.0
 * @package           Wordpress_Multisite_Captcha
 *
 * @wordpress-plugin
 * Plugin Name:       WordPress Multisite Recaptcha
 * Plugin URI:        https://marioyepes.com
 * Description:       Adds a Google Recaptcha validator on the login and "forgot your password" forms.
 * Version:           1.0.0
 * Author:            Mario Yepes
 * Author URI:        https://marioyepes.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       multisite-recaptcha
 * Domain Path:       /languages
 * Network:           true
 */

namespace Wp_Mu_Recaptcha;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Rename this, and start at version 1.0.0
 *
 * @link https://semver.org
 */
define( 'WORDPRESS_MULTISITE_RECAPTCHA', '1.0.0' );

// Lets use composers autoload to load classes from the includes/ dir.
require_once __DIR__ . '/vendor/autoload.php';

// This class is creates and saves multisite options.
Settings_Page::get_instance()->add_hooks();

Auth_Recaptcha::get_instance()->add_hooks();
