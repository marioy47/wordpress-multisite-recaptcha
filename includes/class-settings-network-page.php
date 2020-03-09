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
	protected $plugin_slug;

	/**
	 * This will be used for the SubMenu URL in the settings page and to verify which variables to save.
	 *
	 * @param string $slug The new slug.
	 * @return self
	 */
	public function set_slug( $slug ): self {
		$this->plugin_slug = $slug;
		return $this;
	}

	/**
	 * Required for the plugin list links.
	 *
	 * @var string
	 */
	protected $plugin_file;

	/**
	 * Reqruired for the plugin list links.
	 *
	 * @param string $file the path to the initial pluigin file.
	 * @return self
	 */
	public function set_file( $file ): self {
		$this->plugin_file = $file;
		return $this;
	}

	/**
	 * All the options in one array.
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
		$basename = plugin_basename( $this->plugin_file );

		add_action( 'network_admin_plugin_action_links_' . $basename, array( $this, 'action_links' ) );
		// Register page on menu.
		add_action( 'network_admin_menu', array( $this, 'menu_and_fields' ) );

		// Function to execute when saving data.
		add_action( 'network_admin_edit_' . $this->plugin_slug . '-update', array( $this, 'update' ) );
	}

	/**
	 * Adds links under the name.
	 *
	 * @param [type] $links
	 * @return array
	 */
	public function action_links( $links ) {
		$links[] = '<a href="' . network_admin_url( 'settings.php?page=' ) . $this->plugin_slug . '-page">' . __( 'Settings', 'multisite-recaptcha' ) . '</a>';
		return $links;
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
			$this->plugin_slug . '-page',
			array( $this, 'create_page' )
		);

		// Register a new section on the page.
		add_settings_section(
			'section-config',
			__( 'Site keys', 'multisite-recaptcha' ),
			array( $this, 'section_config' ),
			$this->plugin_slug . '-page'
		);

		// Register a new variable and register the function that updates it.
		register_setting( $this->plugin_slug . '-page', 'multisite_recaptcha' );

		// Fields.
		add_settings_field(
			'multisite-recaptcha-sitekey',
			__( 'Site Key', 'multisite-recaptcha' ),
			array( $this, 'field_sitekey' ), // callback.
			$this->plugin_slug . '-page', // page.
			'section-config' // section.
		);
		add_settings_field(
			'multisite-recaptcha-sitesecret',
			__( 'Site Secret', 'multisite-recaptcha' ),
			array( $this, 'field_sitesecret' ), // callback.
			$this->plugin_slug . '-page', // page.
			'section-config' // section.
		);
		add_settings_field(
			'multisite-recaptcha-theme',
			__( 'Theme', 'multisite-recaptcha' ),
			array( $this, 'field_theme' ), // callback.
			$this->plugin_slug . '-page', // page.
			'section-config' // section.
		);
		add_settings_field(
			'multisite-recaptcha-size',
			__( 'Size', 'multisite-recaptcha' ),
			array( $this, 'field_size' ), // callback.
			$this->plugin_slug . '-page', // page.
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
			<form action="edit.php?action=<?php echo esc_attr( $this->plugin_slug ); ?>-update" method="POST">
				<?php
					settings_fields( $this->plugin_slug . '-page' );
					do_settings_sections( $this->plugin_slug . '-page' );
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
		\check_admin_referer( $this->plugin_slug . '-page-options' );
		global $new_whitelist_options;

		$options = $new_whitelist_options[ $this->plugin_slug . '-page' ];

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
					'page'    => $this->plugin_slug . '-page',
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
		printf( __( 'Get you site key and secret from <a href="%1$s" target="_blank">here</a>. And remember to add every domain you support to the <a href="%2$s" target="_blank">recapcha config</a>', 'multisite-recaptcha' ), 'https://www.google.com/recaptcha/admin', 'https://developers.google.com/recaptcha/docs/settings' );

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
}
