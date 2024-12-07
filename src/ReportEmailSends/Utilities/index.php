<?php
$envoy_report_email_sends_utilities_plugin_options = null;
class Envoy_ReportEmailSends_Utilities {
	private $plugin_options;
  
  //	-------
	//	Helpers
	//	-------
	static function getPluginSettingValue($field_id, $normalize_value = false){
    global $envoy_report_email_sends_utilities_plugin_options;

    if( !$envoy_report_email_sends_utilities_plugin_options ):
 			$envoy_plugin_options = get_option( sprintf('%s_option_name', Envoy_ReportEmailSends_AdminSettings::$NS) ); // Array of All Options
			$envoy_report_email_sends_utilities_plugin_options = $envoy_plugin_options;
		endif;

		//	Guard
		if( !isset( $envoy_report_email_sends_utilities_plugin_options[$field_id] ) ):
			return '';
		endif;

		$value = $envoy_report_email_sends_utilities_plugin_options[$field_id];

		if( $normalize_value ):
			return esc_attr( $value );
		endif;

		return $value;
	}
}