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

/**
 * Creates a new settings page on "Network Admin > Network >Recaptcha".
 */
class Settings_Network_Page {


	/**
	 * This will be used for the SubMenu URL in the settings page and to verify which variables to save.
	 *
	 * @var string
	 */
	protected $settings_slug = 'wp-mu-recaptcha';

	/**
	 * All the optioons in one array.
	 *
	 * @var array
	 */
	protected $options = array();


	/**
	 * Singleton.
	 */
	private function __construct() {

	}

	/**
	 * Static Factory method.
	 *
	 * You can GET an instance of this class by calling `$a = Settings_Page::get_instance();`
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		static $obj;
		return isset( $obj ) ? $obj : $obj = new self();
	}

	/**
	 * Executes the add_action() WordPress methods.
	 *
	 * @return void
	 */
	public function add_hooks() {
		// Register page on menu.
		add_action( 'network_admin_menu', array( $this, 'menu_and_fields' ) );

		// Function to execute when saving data.
		add_action( 'network_admin_edit_' . $this->settings_slug . '-update', array( $this, 'update' ) );
	}

	/**
	 * Creates the sub-menu page and register the multisite settings.
	 *
	 * @return void
	 */
	public function menu_and_fields() {

		// Create the submenu and register the page creation function.
		add_submenu_page(
			'settings.php',
			__( 'Multisite Recaptcha', 'multisite-recaptcha' ),
			__( 'Multisite Recaptcha', 'multisite-recaptcha' ),
			'manage_network_options',
			$this->settings_slug . '-page',
			array( $this, 'create_page' )
		);

		// Register a new section on the page.
		add_settings_section(
			'section-config',
			__( 'Site keys', 'multisite-recaptcha' ),
			array( $this, 'section_config' ),
			$this->settings_slug . '-page'
		);

		// Register a new variable and register the function that updates it.
		register_setting( $this->settings_slug . '-page', 'multisite_recaptcha' );

		// Fields.
		add_settings_field(
			'multisite-recaptcha-sitekey',
			__( 'Site Key', 'multisite-recaptcha' ),
			array( $this, 'field_sitekey' ), // callback.
			$this->settings_slug . '-page', // page.
			'section-config' // section.
		);
		add_settings_field(
			'multisite-recaptcha-sitesecret',
			__( 'Site Secret', 'multisite-recaptcha' ),
			array( $this, 'field_sitesecret' ), // callback.
			$this->settings_slug . '-page', // page.
			'section-config' // section.
		);
		add_settings_field(
			'multisite-recaptcha-theme',
			__( 'Theme', 'multisite-recaptcha' ),
			array( $this, 'field_theme' ), // callback.
			$this->settings_slug . '-page', // page.
			'section-config' // section.
		);
		add_settings_field(
			'multisite-recaptcha-size',
			__( 'Size', 'multisite-recaptcha' ),
			array( $this, 'field_size' ), // callback.
			$this->settings_slug . '-page', // page.
			'section-config' // section.
		);
		add_settings_field(
			'multisite-recaptcha-render',
			__( 'Render', 'multisite-recaptcha' ),
			array( $this, 'field_render' ), // callback.
			$this->settings_slug . '-page', // page.
			'section-config' // section.
		);
	}

	/**
	 * This creates the settings page itself.
	 *
	 * @return void
	 *
	 * @phpcs:disable WordPress.Security.NonceVerification.Recommended
	 */
	public function create_page() {
		$this->options = get_site_option( 'multisite_recaptcha', array() );
		?>
		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div id="message" class="updated notice is-dismissible">
				<p><?php esc_html_e( 'Options Saved', 'multisite-recaptcha' ); ?></p>
			</div>
		<?php endif; ?>

		<div class="wrap">
			<h1><?php echo esc_attr( get_admin_page_title() ); ?></h1>
			<form action="edit.php?action=<?php echo esc_attr( $this->settings_slug ); ?>-update" method="POST">
				<?php
					settings_fields( $this->settings_slug . '-page' );
					do_settings_sections( $this->settings_slug . '-page' );
					submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Multisite options require its own update function. Here we make the actual update.
	 *
	 * @return void
	 */
	public function update() {
		\check_admin_referer( $this->settings_slug . '-page-options' );
		global $new_whitelist_options;

		$options = $new_whitelist_options[ $this->settings_slug . '-page' ];

		foreach ( $options as $option ) {
			if ( isset( $_POST[ $option ] ) ) {
				update_site_option( $option, $_POST[ $option ] );
			} else {
				delete_site_option( $option );
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => $this->settings_slug . '-page',
					'updated' => 'true',
				),
				network_admin_url( 'settings.php' )
			)
		);
		exit;
	}

	/**
	 * Html after the new section title.
	 *
	 * @return void
	 */
	public function section_config() {
		// translators: %s is the URL for google recaptcha admin.
		printf( __( 'Get you site key and secret from <a href="%s" target="_blank">here</a>', 'multisite-recaptcha' ), 'https://www.google.com/recaptcha/admin' );
	}

	/**
	 * Site key field.
	 *
	 * @return void
	 */
	public function field_sitekey() {
		$val = array_key_exists( 'sitekey', $this->options ) ? $this->options['sitekey'] : '';
		echo '<input type="text" name="multisite_recaptcha[sitekey]" value="' . esc_attr( $val ) . '" size="50" />';
	}

	/**
	 * Site secret field.
	 *
	 * @return void
	 */
	public function field_sitesecret() {
		$val = array_key_exists( 'sitesecret', $this->options ) ? $this->options['sitesecret'] : '';
		echo '<input type="text" name="multisite_recaptcha[sitesecret]" value="' . esc_attr( $val ) . '" size="50"/>';
	}

	/**
	 * Select light or dark.
	 *
	 * @return void
	 */
	public function field_theme() {
		$val = array_key_exists( 'theme', $this->options ) ? $this->options['theme'] : 'light';
		echo '<select name="multisite_recaptcha[theme]">';
			echo '<option value="light" ' . selected( 'light', $val, true ) . '>' . __( 'Light', 'multisite-recaptcha' ) . '</option>';
			echo '<option value="dark" ' . selected( 'dark', $val, true ) . '>' . __( 'Dark', 'multisite-recaptcha' ) . '</option>';
		echo '</select>';
	}

	/**
	 * Select between normal or compact.
	 *
	 * @return void
	 */
	public function field_size() {
		$val = array_key_exists( 'size', $this->options ) ? $this->options['size'] : 'normal';
		echo '<select name="multisite_recaptcha[size]">';
			echo '<option value="normal" ' . selected( 'normal', $val, true ) . '>' . __( 'Normal', 'multisite-recaptcha' ) . '</option>';
			echo '<option value="compact" ' . selected( 'compact', $val, true ) . '>' . __( 'Compact', 'multisite-recaptcha' ) . '</option>';
		echo '</select>';
	}

	/**
	 * Select when to load the recaptcha: on page load or when submit button is clicked.
	 *
	 * @return void
	 */
	public function field_render() {
		$val = array_key_exists( 'render', $this->options ) ? $this->options['render'] : 'normal';
		echo '<select name="multisite_recaptcha[render]">';
			echo '<option value="onload" ' . selected( 'normal', $val, true ) . '>' . __( 'Normal', 'multisite-recaptcha' ) . '</option>';
			echo '<option value="explicit" ' . selected( 'explicit', $val, true ) . '>' . __( 'When submit buton is clicked', 'multisite-recaptcha' ) . '</option>';
		echo '</select>';
	}

}
