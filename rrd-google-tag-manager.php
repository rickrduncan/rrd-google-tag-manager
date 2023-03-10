<?php
/**
 * Plugin Name:		RRD - Google Tag Manager
 * Description:		Ability to add Google Tag Manager & Google Optimize code snippets to your website.
 * Author: 			Rick R. Duncan
 * Author URI: 		https://rickrduncan.com
 * License:			GPL2
 * License URI:  	https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * Version: 		1.2.0
 */


/**
 * Exit if accessed directly.
 *
 * @since 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Set a CONSTANT equal to plugin folder path.
 *
 * @since 1.0.0
 */
if ( ! defined( 'RRD_GTM_PLUGIN_DIR' ) ) {
	define( 'RRD_GTM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}


/**
 * Include files used to keep plugin updated.
 * @since 1.0.0
 * @since 1.2.0 - Updated plugin checker to 5.0
 * 
 */
require RRD_GTM_PLUGIN_DIR . 'plugin-update-checker-5-0/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
$MyUpdateChecker = PucFactory::buildUpdateChecker(
    'https://rickrduncan.com/my-plugins/rrd-google-tag-manager/rrd-google-tag-manager.json',
    __FILE__,
	'rrd-google-tag-manager'
);


/**
 * Instantiate class.
 * @since 1.0.0
 */
if ( ! class_exists( 'RRD_Google_Tag_Manager' ) ) {
	class RRD_Google_Tag_Manager {
		/**
		 * Start things up
		 * @since 1.0.0
		 */
		public function __construct() {
			// We only need to register the admin panel on the back-end
			if ( is_admin() ) {
				$plugin = plugin_basename( __FILE__ );
				add_filter( "plugin_action_links_$plugin", 'rrd_gtm_add_settings_link' );
				add_action( 'admin_menu', array( 'RRD_Google_Tag_Manager', 'rrd_gtm_add_admin_menu' ) );
				add_action( 'admin_init', array( 'RRD_Google_Tag_Manager', 'rrd_gtm_register_settings' ) );
			}
			
			/**
			 * Add plugin 'Settings' link to the plugins page
			 * 
			 * @since 1.0.0
			 */
			function rrd_gtm_add_settings_link( $links ) {
				$settings_link = '<a href="admin.php?page=gtm-settings">' . __( 'Settings' ) . '</a>';
				array_unshift($links, $settings_link);
				return $links;
			}
			
			/** 
			 * Load GTM container code (Part 1) in <head>
			 * 
			 */
			add_action( 'wp_head', array( $this, 'rrd_gtm_add_head_code' ) );
			
			/** 
			 * Load GTM container code (Part 2) in <body>
			 * @since 1.0.0 
			 * @since 1.2.0 - Changed hook genesis_before to wp_body_open. No longer need to depend on using the Genesis framework theme
			 */
			add_action( 'wp_body_open', array( $this, 'rrd_gtm_add_body_code' ) );
		}
		
		/**
		 * Returns all theme options
		 * @since 1.0.0
		 */
		public static function get_theme_options() {
			return get_option( 'rrd_gtm_options' ); //unique name of our options is rrd_gtm_options
		}

		/**
		 * Returns single theme option
		 * @since 1.0.0
		 */
		public static function get_theme_option( $id ) {
			$options = self::get_theme_options();
			if ( isset( $options[$id] ) ) {
				return $options[$id];
			}
		}

		/**
		 * Add sub menu page
		 * @since 1.0.0
		 */
		public static function rrd_gtm_add_admin_menu() {
			add_menu_page(
				'GTM Settings',
				'GTM Settings',
				'manage_options',
				'gtm-settings',
				array( 'RRD_Google_Tag_Manager', 'create_gtm_page' )
			);
		}
		
		/**
		 * Step 1 - Insert GTM code into <head> section of page.
		 *
		 * @since 1.1.0 - Edited the way we instantiated the dataLayer.	
		 * @since 1.0.0
		 */
		public static function rrd_gtm_add_head_code() { 
			$options    		= get_option( 'rrd_gtm_options' );	// Return our plugin options
			$gtm_id       		= empty( $options['rrd_gtm_id'] ) ? 'WARNING - NO ID SPECIFIED' : $options['rrd_gtm_id'];		// Google Tag Manager id number
			$gtm_enabled		= empty( $options['rrd_gtm_enabled'] ) ? '' : $options['rrd_gtm_enabled'];						// Check box for GTM being enabled/disabled
			$gop_enabled		= empty( $options['rrd_gop_enabled'] ) ? '' : $options['rrd_gop_enabled'];						// Check box for Google Optimize being enabled/disabled
			$data_layer 		= '<!-- Google Tag Manager --><script>var dataLayer = window.dataLayer = window.dataLayer || [];</script>';
			$google_optimize 	= '<style>.async-hide { opacity: 0 !important} </style>';
			
			if ( ! current_user_can( 'manage_options' ) && ( $gtm_enabled ) )  { 
				//only include GA code for non-admin users and when "on" is checked
				if ( $gop_enabled ) {
					echo $data_layer;
					echo $google_optimize;
				}
				?>
				<?php if ( $gop_enabled ) { ?>
				<script>(function(a,s,y,n,c,h,i,d,e){s.className+=' '+y;h.start=1*new Date;h.end=i=function(){s.className=s.className.replace(RegExp(' ?'+y),'')};(a[n]=a[n]||[]).hide=h;setTimeout(function(){i();h.end=null},c);h.timeout=c;})(window,document.documentElement,'async-hide','dataLayer',4000,{'<?php echo $gtm_id; ?>':true});</script>
				<?php } ?>
				<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start': new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0], j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo $gtm_id; ?>');</script><!-- End Google Tag Manager -->
				<?php
			}		
		}

		
		/**
		 * Step 2 - Insert GTM code after the opening <body> tag
		 * @since 1.0.0
		 */
		public static function rrd_gtm_add_body_code() {
			$options		= get_option( 'rrd_gtm_options' );	// Return our plugin options
			$gtm_id       	= empty( $options['rrd_gtm_id'] ) ? 'WARNING - NO ID SPECIFIED' : $options['rrd_gtm_id'];			// Google Tag Manager id number
			$gtm_enabled	= empty ( $options['rrd_gtm_enabled'] ) ? '' : $options['rrd_gtm_enabled'];	// Check box for enabled/disabled
			if ( ! current_user_can( 'manage_options' ) && ( $gtm_enabled ) )  {
				echo '<!-- Google Tag Manager (noscript) --><noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . $gtm_id . ' height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript><!-- End Google Tag Manager (noscript) -->';
			}
		}
		
		/**
		 * Register a setting and its sanitization callback.
		 *
		 * We are only registering 1 setting so we can store all options in a single option as
		 * an array. You could, however, register a new setting for each option
		 *
		 * @since 1.0.0
		 */
		public static function rrd_gtm_register_settings() {
			register_setting( 'rrd_gtm_options', 'rrd_gtm_options', array( 'RRD_Google_Tag_Manager', 'sanitize' ) );
		}

		/**
		 * Sanitization callback
		 *
		 * @since 1.0.0
		 */
		public static function sanitize( $options ) {
			// If we have options lets sanitize them
			if ( $options ) {
				if ( ! empty( $options['rrd_gtm_id'] ) ) {
					$options['rrd_gtm_id'] = sanitize_text_field( $options['rrd_gtm_id'] );
				} else {
					unset( $options['rrd_gtm_id'] ); // Remove from options if empty
				}
				if ( ! empty( $options['rrd_gtm_enabled'] ) ) {
					$options['rrd_gtm_enabled'] = sanitize_text_field( $options['rrd_gtm_enabled'] );
				} else {
					unset( $options['rrd_gtm_enabled'] ); // Remove from options if empty
				}
				if ( ! empty( $options['rrd_gop_enabled'] ) ) {
					$options['rrd_gop_enabled'] = sanitize_text_field( $options['rrd_gop_enabled'] );
				} else {
					unset( $options['rrd_gop_enabled'] ); // Remove from options if empty
				}
			}
			// Return sanitized options
			return $options;
		}	

		/**
		 * Settings page output
		 *
		 * @since 1.0.0
		 */
		public static function create_gtm_page() { ?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Google Tag Manager Settings' ); ?></h1>
				<?php settings_errors(); ?>
				<form method="post" action="options.php">
					<?php settings_fields( 'rrd_gtm_options' ); ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row">Enable Google Tag Manager?</th>
							<td>
								<?php $value = self::get_theme_option( 'rrd_gtm_enabled' ); ?>
								<input name="rrd_gtm_options[rrd_gtm_enabled]" type="checkbox" value="yes" <?php echo ($value ? 'checked=checked' : ''); ?> /> 
								<?php esc_html_e( 'Include the Google Tag Manager Container on all web pages?' ) ?>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Google Tag Manager ID#' ); ?></th>
							<td>
								<?php $value = self::get_theme_option( 'rrd_gtm_id' ); ?>
								<input class="regular-text" type="text" name="rrd_gtm_options[rrd_gtm_id]" value="<?php echo esc_attr( $value ); ?>">
								<p id="tagline-description" class="description">What is your container ID number?</p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Enable Google Optimize?</th>
							<td>
								<?php $value = self::get_theme_option( 'rrd_gop_enabled' ); ?>
								<input name="rrd_gtm_options[rrd_gop_enabled]" type="checkbox" value="yes" <?php echo ($value ? 'checked=checked' : ''); ?> /> 
								<?php esc_html_e( 'Include the Google Optimize script on all pages?' ) ?>
								<p id="tagline-description" class="description"><strong>PLEASE NOTE:</strong> You must first install Google Optimize within GTM for this code to have any effect.</p>
								<p id="tagline-description" class="description"><a href="https://support.google.com/tagmanager/answer/7164339?hl=en" target="_blank">https://support.google.com/tagmanager/answer/7164339?hl=en</a></p>
							</td>
						</tr>						
					</table>
					<?php submit_button(); ?>
				</form>
			</div><!-- .wrap -->
		<?php }
	}
}

/**
 * Create a new instance of class.
 *
 * @since 1.0.0
 */
new RRD_Google_Tag_Manager();