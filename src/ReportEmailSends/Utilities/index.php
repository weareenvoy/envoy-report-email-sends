<?php

class Envoy_ReportEmailSends_Utilities {
	private $plugin_options;
	public function __construct() {
	}
  
  //	-------
	//	Helpers
	//	-------
	public function getPluginSettingValue($field_id, $normalize_value = false){
		if( !$this->plugin_options ):
 			$envoy_plugin_options = get_option( sprintf('%s_option_name', Envoy_ReportEmailSends_AdminSettings::$NS) ); // Array of All Options
			$this->plugin_options = $envoy_plugin_options;
		endif;

		//	Guard
		if( !isset( $this->plugin_options[$field_id] ) ):
			return '';
		endif;

		$value = $this->plugin_options[$field_id];

		if( $normalize_value ):
			return esc_attr( $value );
		endif;

		return $value;
	}
}