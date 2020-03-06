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
		add_filter( 'script_loader_tag', array( $this, 'alter_script_tag' ) );

		// WordPress Native
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'login_head', array( $this, 'login_inline_styles' ) );
		add_action( 'login_form', array( $this, 'g_recaptcha' ) );
		add_filter( 'authenticate', array( $this, 'verify_captcha' ), 20, 3 );

		// Lost password executes "login_enqueue_scripts" and  "login_head" .
		add_action( 'lostpassword_form', array( $this, 'g_recaptcha' ) );
		add_action( 'lostpassword_post', array( $this, 'lost_password_verify_captcha' ), 10, 1 );

		// WooCommerce.
		add_action( 'woocommerce_login_form_start', array( $this, 'enqueue_scripts' ) );
		add_action( 'woocommerce_before_lost_password_form', array( $this, 'enqueue_scripts' ) );
		add_action( 'woocommerce_login_form', array( $this, 'g_recaptcha' ) );
		add_action( 'woocommerce_lostpassword_form', array( $this, 'g_recaptcha' ) );

		return $this;
	}


	/**
	 * Add async defer to the `<script>` tag when loading the recaptcha script.
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
	 * Execute the 'init' hoook added in wp_hooks.
	 *
	 * @return self
	 */
	public function enqueue_scripts(): self {

		$network = get_site_option( 'multisite_recaptcha', array() );
		$site    = get_option( 'multisite_recaptcha', array() );

		if ( ! empty( $site['sitekey'] ) ) {
			$network['sitekey'] = $site['sitekey'];
		}
		if ( ! empty( $site['sitesecret'] ) ) {
			$network['sitesecret'] = $site['sitesecret'];
		}
		$network = array_merge( $site, $network );

		$options = shortcode_atts(
			array(
				'sitekey' => '',
				'theme'   => '',
				'size'    => '',
				'render'  => '',
			),
			$network
		);
		if ( empty( $network['sitekey'] ) || empty( $network['sitesecret'] ) ) {
			return $this;
		}

		wp_enqueue_script( 'multisite-recaptcha', plugin_dir_url( __DIR__ ) . 'js/v2.js', array(), WORDPRESS_MULTISITE_RECAPTCHA, true );
		wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js??onload=recaptchaCallback&render=explicit', array(), WORDPRESS_MULTISITE_RECAPTCHA, true );
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
	 * Does the actual capcha verification on google.
	 *
	 * @return bool|\WP_Error true on success, WP_Error on if not.
	 */
	protected function verify_google_recaptcha() {
		$network = get_site_option( 'multisite_recaptcha', array() );
		$site    = get_option( 'multisite_recaptcha', array() );

		if ( ! empty( $site['sitesecret'] ) ) {
			$network['sitesecret'] = $site['sitesecret'];
		}

		$url    = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $network['sitesecret'] . '&response=' . $_POST['g-recaptcha-response'];
		$json   = file_get_contents( $url );
		$result = null;
		try {
			$result = json_decode( $json, true );
		} catch ( Exception $e ) {
			return new \WP_Error( 'recaptcha_failed', __( '<strong>ERROR</strong>: Could not verify recaptcha.', 'multisite-recaptcha' ) );
		}
		if ( ! $result['success'] ) {
			return new \WP_Error( 'recaptcha_failed', '<strong>ERROR</strong>: ' . implode( ', ', $result['error-codes'] ) );
		}
		return true;
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
		$verify = $this->verify_google_recaptcha();
		if ( true !== $verify ) {
			return $verify;
		}

		return $user;
	}

	/**
	 * Verifies the recaptcha on the `forgot_password` form.
	 *
	 * @param bool|\WP_Error $errors object that carries out erros.
	 * @return void
	 */
	public function lost_password_verify_captcha( $errors ) {
		$verify = $this->verify_google_recaptcha();
		if ( true !== $verify ) {
			$errors->add( 'no_captcha', $verify->get_error_message() );
		}

	}


}
