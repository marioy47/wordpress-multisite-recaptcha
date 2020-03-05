<?php
/**
 * WordPress Multisite Recaptcha.
 *
 * @author Mario Yepes <marioy47@gmail.com>
 * @package Wordpress_Multisite_Recaptcha
 *
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */

namespace Wp_Mu_Recaptcha;

use Exception;

/**
 * This is a pretty empty class. Use it as a template for new classes.
 */
class Auth_Recaptcha {

	/**
	 * Singleton.
	 */
	private function __construct() {

	}

	/**
	 * Factory.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		static $obj;
		return isset( $obj ) ? $obj : $obj = new self();
	}

	/**
	 * Add your WordPress hooks here.
	 *
	 * @return self
	 */
	public function add_hooks(): self {
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'script_loader_tag', array( $this, 'alter_script_tag' ) );

		add_action( 'login_head', array( $this, 'login_inline_styles' ) );
		add_action( 'login_form', array( $this, 'g_recaptcha' ) );

		add_filter( 'authenticate', array( $this, 'verify_captcha' ), 20, 3 );

		return $this;
	}

	/**
	 * Execute the 'init' hoook added in wp_hooks.
	 *
	 * @return self
	 */
	public function enqueue_scripts(): self {
		wp_enqueue_script( 'multisite-recaptcha', plugin_dir_url( __DIR__ ) . 'js/v2.js', array(), WORDPRESS_MULTISITE_RECAPTCHA, true );
		wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js??onload=recaptchaCallback&render=explicit', array(), WORDPRESS_MULTISITE_RECAPTCHA, true );
		$options = shortcode_atts(
			array(
				'sitekey' => '',
				'theme'   => '',
				'size'    => '',
				'render'  => '',
			),
			get_site_option( 'multisite_recaptcha', array() )
		);
		wp_localize_script(
			'multisite-recaptcha',
			'MULTISITE_RECAPTCHA', // Must be the same as v2.js.
			array(
				'options' => $options,
				'element' => 'google-recaptcha-container',
				'ajaxurl' => admin_url( 'admin-ajax.php' ),

			)
		);
		return $this;
	}

	/**
	 * Add async defer when loading the recaptcha script.
	 *
	 * @param string $tag The origina `<script>` tag before maing any change.
	 * @return string
	 */
	public function alter_script_tag( $tag ): string {
		if ( strpos( $tag, 'www.google.com/recaptcha/api.js' ) === false ) {
			return $tag;
		}
		return str_replace( 'src', 'async defer src', $tag );
	}

	/**
	 * Styling on the login form to acomodate the recaptcha.
	 *
	 * @return void
	 */
	public function login_inline_styles() {
		echo '<style> #login { min-width: 353px;}</style>';
	}

	/**
	 * Inserts the <div> with the recaptcha parammmeters.
	 *
	 * @return void
	 */
	public function g_recaptcha() {
		echo '<div id="google-recaptcha-container"></div>';

	}

	/**
	 * Calls Google Server to verify captcha.
	 *
	 * @param WP_User|WP_Error $user Provided by WordPress.
	 * @param string           $username Provided by WordPress.
	 * @param string           $password Provided by WordPress.
	 * @return WP_User|WP_Error
	 *
	 * @phpcs:disable WordPress.Security.NonceVerification.Missing
	 */
	public function verify_captcha( $user, $username, $password ) {
		if ( empty( $username ) && empty( $password ) ) {
			return $user;
		}
		if ( is_a( $user, 'WP_Error' ) ) {
			return $user;
		}
		if ( empty( $_POST['g-recaptcha-response'] ) ) {
			return new \WP_Error( 'authentication_failed', __( '<strong>ERROR</strong>: Empty captcha.', 'multisite-recaptcha' ) );
		}
		$options = get_site_option(
			'multisite_recaptcha',
			array(
				'site_key'    => '',
				'site_secret' => '',
			)
		);
		$url     = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $options['sitesecret'] . '&response=' . $_POST['g-recaptcha-response'];
		$json    = file_get_contents( $url );
		$result  = null;
		try {
			$result = json_decode( $json, true );
		} catch ( Exception $e ) {
			return new \WP_Error( 'authentication_failed', __( '<strong>ERROR</strong>: Could not verify recaptcha.', 'multisite-recaptcha' ) );
		}
		if ( ! $result['success'] ) {
			return new \WP_Error( 'authentication_failed', '<strong>ERROR</strong>: ' . implode( ', ', $result['error-codes'] ) );
		}
		return $user;
	}

}
