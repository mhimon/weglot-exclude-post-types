<?php
/**
 * Plugin Name: Exclude Post Types for Weglot
 * Description: Exclude specific post types from Weglot translation.
 * Version: 1.0.0
 * Author: Mahbub Hasan Imon
 * Author URI: https://mhimon.dev
 * Plugin URI: https://ultradevs.com
 * Text Domain: exclude-post-types-for-weglot
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Tested up to: 6.4.3
 * License: GPL2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exclude Post Types for Weglot
 */
final class UDWLEPT_Main {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Check if weglot plugin is installed and activated.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! is_plugin_active( 'weglot/weglot.php' ) ) {
			add_action( 'admin_notices', array( $this, 'weglot_plugin_not_active_notice' ) );
		}

		add_filter( 'weglot_is_eligible_url', array( $this, 'exclude_post_types_from_weglot' ) );
		add_filter( 'weglot_active_translation_before_process', array( $this, 'check_page_translation' ) );
	}

	public function weglot_plugin_not_active_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Weglot plugin is not installed or activated. Please install and activate Weglot plugin first.', 'exclude-post-types-for-weglot' ); ?></p>
		</div>
		<?php
	}

	public function add_admin_menu() {
		add_options_page(
			__( 'Exclude Post Types for Weglot', 'exclude-post-types-for-weglot' ),
			__( 'Exclude Post Types for Weglot', 'exclude-post-types-for-weglot' ),
			'manage_options',
			'udwlept_post_types',
			array( $this, 'render_admin_page' )
		);
	}

	public function register_settings() {
		register_setting( 'udwlept_post_types', 'udwlept_post_types', array( $this, 'sanitize_post_types' ) );
	}

	public function sanitize_post_types($input) {
		$post_types = get_post_types();

		if ( ! is_array( $input ) ) {
			return array();
		}
		// Keep only valid post types
		return array_intersect( $input, $post_types );
	}

	public function exclude_post_types_from_weglot($is_eligible) {
		$excluded_post_types = get_option( 'udwlept_post_types', array() );
		$current_post_type   = get_post_type( weglot_get_postid_from_url() );

		if ( in_array( $current_post_type, $excluded_post_types ) ) {
			return false; // Exclude from translation
		}

		return $is_eligible;
	}

	public function check_page_translation() {
		// Get excluded post types
		$excluded_post_types = get_option( 'udwlept_post_types', array() );
		$current_post_type   = get_post_type( weglot_get_postid_from_url() );

		if (
			in_array(
				$current_post_type,
				$excluded_post_types
			) && weglot_get_current_language() !== weglot_get_original_language()
		) {
			wp_redirect( weglot_get_request_url_service()->get_full_url() );
			exit;
		}
	
		return true;
	}

	public function render_admin_page() {
		?>
		<div class="wrap">
			<h2>Exclude Post Types for Weglot Settings</h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'udwlept_post_types' ); ?>
				<?php do_settings_sections( 'udwlept_post_types' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Excluded Post Types</th>
						<td>
							<?php
							$post_types          = get_post_types();
							$excluded_post_types = get_option( 'udwlept_post_types', array() );
							foreach ( $post_types as $post_type ) {
								?>
								<label>
									<input type="checkbox" name="udwlept_post_types[]" value="<?php echo esc_attr( $post_type ); ?>" <?php esc_attr( checked( in_array( $post_type, $excluded_post_types ), true ) ); ?>>
									<?php echo esc_html( $post_type ); ?>
								</label>
								<br />
								<?php
							}
							?>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

// Initialize the plugin
new UDWLEPT_Main();
