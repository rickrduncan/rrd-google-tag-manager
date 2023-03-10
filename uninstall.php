<?php
/**
 * Runs on Uninstall of RRD Google Tag Manager
 *
 * @package   RRD Google Tag Manager
 * @author    Rick R. Duncan
 */	


/**
 * Are you allowed to be here?
 *
 * @since 1.0.0
 */
if ( !defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') ) exit();


/**
 * Delete all the options we created with this plugin.
 *
 * @since 1.0.0
 */
$options = array(
	'rrd_gtm_options',
	);
foreach ( $options as $option ) {
	if ( get_option( $option ) ) {
		delete_option( $option );
	}
} 
?>