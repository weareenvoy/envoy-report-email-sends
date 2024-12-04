<?php
/**
 * Plugin Name: Envoy Report Email Sends
 * Description: Envoy Report Email Sends
 * Author: WeAreEnvoy
 * Author URI: https://www.weareenvoy.com/
 */
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// TO-DO: Create auto-loader so we don't have to require_once()
require_once __DIR__ . '/src/ReportEmailSends/index.php';
require_once __DIR__ . '/src/ReportEmailSends/AdminSettings/index.php';
require_once __DIR__ . '/src/ReportEmailSends/ReportGenerator/index.php';

if( is_admin() ):
	$ERESRG	=	new Envoy_ReportEmailSends_ReportGenerator;
	$ERES	=	new Envoy_ReportEmailSends($ERESRG);
	$ERESAS	=	new Envoy_ReportEmailSends_AdminSettings($ERES);
endif;

// Register the cron event
function envoy_report_email_sends_schedule() {
	// Ensure plugin is active
	if ( is_plugin_active( plugin_basename( __FILE__ ) ) && ! wp_next_scheduled( 'envoy_report_email_send_cron_hook' ) ) {
			// Schedule the event to run daily at 5 PM UTC (which is 9 AM PST)
			$timestamp = strtotime('today 17:00 UTC');
			wp_schedule_event( $timestamp, 'daily', 'envoy_report_email_send_cron_hook' );
	}
}
add_action( 'plugins_loaded', 'envoy_report_email_sends_schedule' );


// Function to send the email
function envoy_report_email_send_function() {
	$report_generator = new Envoy_ReportEmailSends_ReportGenerator();
	$report_email_sender = new Envoy_ReportEmailSends( $report_generator );
	$report_email_sender->sendEmail();

  $message = 'envoy_report_email_send has run on cron: ' . date( 'Y-m-d H:i:s' ) . "\n";
	// abspath is /www/wp-preview.corvel.corvel-marketing.com/current/web/wp/
	list( $root ) = explode( 'current', ABSPATH );
	$log_file = $root . 'shared/log/wp-cron.log';
  file_put_contents( $log_file, $message, FILE_APPEND );

}
add_action( 'envoy_report_email_send_cron_hook', 'envoy_report_email_send_function' );