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
 * Creates the settings page.
 */
class Settings_Site_Page {

	/**
	 * This will be used for the SubMenu URL in the settings page and to verify which variables to save.
	 *
	 * @var string
	 */
	protected $plugin_slug;

	/**
	 * Setter for the plugin_slug variable.
	 *
	 * @param string $slug The new slug.
	 * @return self
	 */
	public function set_slug( $slug ): self {
		$this->plugin_slug = $slug;
		return $this;
	}

	/**
	 * The path to the initial plugin file.
	 *
	 * @var string
	 */
	protected $plugin_file;

	/**
	 * Setter for the initial plugin file.
	 *
	 * @param string $file The new absolute path.
	 * @return self
	 */
	public function set_file( $file ): self {
		$this->plugin_file = $file;
		return $this;
	}

	/**
	 * Thame of the options group for the captcha variables.
	 *
	 * @var string
	 */
	protected $options_name = 'multisite_recaptcha';

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
	 * Factory.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		static $obj;
		return isset( $obj ) ? $obj : $obj = new self();
	}

	/**
	 * Executes the add_action() and add_filter() functions.
	 *
	 * @return self
	 */
	public function add_hooks(): self {
		$basename = plugin_basename( $this->plugin_file );
		add_action( 'plugin_action_links_' . $basename, array( $this, 'action_links' ) );
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_fields' ) );
		return $this;
	}

	/**
	 * Add settings links in the plugin list page under the plugin name.
	 *
	 * @param array $links The links array provider by WordPress.
	 * @return void
	 */
	public function action_links( $links ) {
		$links[] = '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', 'multisite-captcha' ) . '</a>';
		return $links;
	}

	/**
	 * Creates the admin menu and sets-up the settings page.
	 *
	 * @return self
	 */
	public function add_menu(): self {
		add_options_page(
			__( 'Site Recaptcha', 'multisite-recaptcha' ),
			__( 'Site Recaptcha', 'multisite-recaptcha' ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'create_page' ),
			null
		);
		return $this;
	}

	/**
	 * Creates the Settings Page HTML.
	 *
	 * @return self
	 */
	public function create_page(): self {
		$this->options = get_option( $this->options_name, array() );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( $this->plugin_slug );
				do_settings_sections( $this->plugin_slug );
				submit_button( __( 'Save', 'multisite-recaptcha' ) );
				?>
			</form>
		</div>
		<?php
		return $this;
	}

	/**
	 * Registers the options, sections and fields for the plugin.
	 *
	 * @return self
	 */
	public function register_fields(): self {

		// Create the submenu and register the page creation function.
		add_submenu_page(
			'settings.php',
			__( 'Multisite Recaptcha', 'multisite-recaptcha' ),
			__( 'Multisite Recaptcha', 'multisite-recaptcha' ),
			'manage_network_options',
			$this->plugin_slug,
			array( $this, 'create_page' )
		);

		// Register a new section on the page.
		add_settings_section(
			'section-config',
			__( 'Site keys', 'multisite-recaptcha' ),
			array( $this, 'section_config' ),
			$this->plugin_slug
		);

		// Register a new variable and register the function that updates it.
		register_setting( $this->plugin_slug, 'multisite_recaptcha' );

		// Fields.

		add_settings_field(
			'multisite-recaptcha-sitekey',
			__( 'Site Key', 'multisite-recaptcha' ),
			array( $this, 'field_sitekey' ), // callback.
			$this->plugin_slug, // page.
			'section-config' // section.
		);

		add_settings_field(
			'multisite-recaptcha-sitesecret',
			__( 'Site Secret', 'multisite-recaptcha' ),
			array( $this, 'field_sitesecret' ), // callback.
			$this->plugin_slug, // page.
			'section-config' // section.
		);

		if ( ! is_multisite() ) {
			add_settings_field(
				'multisite-recaptcha-theme',
				__( 'Theme', 'multisite-recaptcha' ),
				array( $this, 'field_theme' ), // callback.
				$this->plugin_slug, // page.
				'section-config' // section.
			);

			add_settings_field(
				'multisite-recaptcha-size',
				__( 'Size', 'multisite-recaptcha' ),
				array( $this, 'field_size' ), // callback.
				$this->plugin_slug, // page.
				'section-config' // section.
			);

			add_settings_field(
				'multisite-recaptcha-render',
				__( 'Render', 'multisite-recaptcha' ),
				array( $this, 'field_render' ), // callback.
				$this->plugin_slug, // page.
				'section-config' // section.
			);
		} // is_multisite

		return $this;
	}


	/**
	 * Html after the new section title.
	 *
	 * @return void
	 */
	public function section_config() {
		// translators: %s is the URL for google recaptcha admin.
		printf( __( 'Get you site key and secret from <a href="%s" target="_blank">here</a>. If you leave this fields empty, the multisite config will be used instead', 'multisite-recaptcha' ), 'https://www.google.com/recaptcha/admin' );
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
