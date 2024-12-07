<?php

class Envoy_ReportEmailSends {

	private $brand_name = 'Wordpress';

	private $plugin_options;

	public function __construct(Envoy_ReportEmailSends_ReportGenerator $ERESRG) {
			$this->ERESRG = $ERESRG; // ReportGenerator
			$this->brand_name = Envoy_ReportEmailSends_Utilities::getPluginSettingValue('brand_name');
	}

	public function sendEmail(){

		//	Generate the Report to send
		if( $this->ERESRG->generate() ):
			// $file_path = Envoy_ReportEmailSends_ReportGenerator::getTempSaveFilename();
			$file_path = $this->ERESRG->getTempSaveFilename();
			$to = Envoy_ReportEmailSends_Utilities::getPluginSettingValue('send_email_recipient_to');
			$subject = sprintf("%s | 'Email Send' Report", $this->brand_name);
			$message_text = $this->_getEmailMessageText();
			$headers = $this->_getEmailHeaders();
			$attachments = [$file_path];
			$success = wp_mail( $to, $subject, $message_text, $headers, $attachments );

			return [$success, $file_path];
		endif;

		return [false, ''];
	}


	//	-------------
	//	Email Helpers
	//	-------------
	private function _getEmailMessageText(){
		$message_rows = [
			"To Whom It May Concern,",
			"\r\n",
			"Attached to this email is a `.csv` report of emails sent.",
			sprintf("For '%s' on date: '%s'",
				$this->brand_name,
				$this->ERESRG->getTargetDateFromParameter()->format('Y-m-d')
			),
			"\r\n",
			"Have a great day!",
		];

		//	Join the message rows together to they are compatible with email
		$message_text = implode("\r\n", $message_rows);

		return $message_text;
	}

	private function _getEmailHeaders(){

		$headers = [];

		//	From
    $headers[] = sprintf("From: %s DoNotReply <%s>", $this->brand_name, Envoy_ReportEmailSends_Utilities::getPluginSettingValue('send_email_from_address') );

		//	CC
		$cc_emails = SELF::arrayFromCommaSeparatedString( Envoy_ReportEmailSends_Utilities::getPluginSettingValue('send_email_recipients_cc') );
		foreach( $cc_emails AS $_email ):
			$headers[] = sprintf('Cc: %s', $_email);
		endforeach;
		
		//	BCC
		$bcc_emails = SELF::arrayFromCommaSeparatedString( Envoy_ReportEmailSends_Utilities::getPluginSettingValue('send_email_recipients_bcc') );
		foreach( $cc_emails AS $_email ):
			$headers[] = sprintf('Bcc: %s', $_email);
		endforeach;

		//	Return
		return $headers;
	}

	//	-------
	//	Helpers
	//	-------

	static function arrayFromCommaSeparatedString($comma_separated_string=''){
		$array = array_map(
			function($element){
				return strtolower(trim($element));
			},
			explode(',', $comma_separated_string)
		);
		return $array;
	}

}//class
