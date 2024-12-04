<?php
class Envoy_ReportEmailSends_ReportGenerator {

	private $rows_o_ses_lite_plugin;
	private $rows_cf7_plugin;

	public function __construct(){
		//  Make sure temp directory is present
		$structure = SELF::getTempSaveDirectory();
		mkdir($structure, 0777, true);
		
	}

	static function getTempSaveDirectory(){
		return sprintf('%senvoy_email_send_reports',get_temp_dir());
	}

	static function getTempSaveFilename(){
		return sprintf('%s/%s-email-report.csv',
			SELF::getTempSaveDirectory(),
			SELF::getTargetDateFromParameter()->format('Y-m-d'),
		);
	}

	static function getTargetDateFromParameter(){
		$parameter = $_POST['target_date'] ?? 'YESTERDAY' ;
		return new DateTime($parameter, new DateTimeZone('America/Los_Angeles'));
	}

	static function getDateRangeFromParameter(){
		$parameter = $_POST['date_range'] ?? '1' ;
		return $parameter;
	}

	public function generate(){
		GLOBAL $wpdb;

		$TARGET_DATE = SELF::getTargetDateFromParameter();

		$BETWEEN_START = $TARGET_DATE->format('Y-m-d');
		$dateIntervalPattern = sprintf('P%sD', SELF::getDateRangeFromParameter());
		$BETWEEN_END = $TARGET_DATE->add(new DateInterval($dateIntervalPattern))->format('Y-m-d');

		$query_o_ses_lite_plugin = "
		SELECT
			*
		FROM
			`wp_oses_emails`
		WHERE
			`email_created` BETWEEN '".$BETWEEN_START."' AND '".$BETWEEN_END."'
		;";
		
		$query_cf7_plugin = "
		SELECT
			*
		FROM
			`wp_db7_forms`
		WHERE
			`form_date` BETWEEN '".$BETWEEN_START."' AND '".$BETWEEN_END."'
		;";
		
		//	---------------------
		//	Prep .csv FilePointer
		//	---------------------
		$fp_client_facing = fopen(SELF::getTempSaveFilename(), 'w');

		//	----------------------
		//	Create .csv Header Row
		//	----------------------
		$fields = [
			"Date & Time 'Pacific Time'",
			'Email Status',
			"Form 'category'",
			"Form 'state'",
			'Uses Routing',
			'Count CC+BCC',
			'BCC Recipients',
			'CC Recipients',
			'TO Recipient',
			"Form 'subject'",
		];
		fputcsv($fp_client_facing, $fields);

		$this->rows_o_ses_lite_plugin = (array)$wpdb->get_results( $query_o_ses_lite_plugin, OBJECT );
		$this->rows_cf7_plugin = (array)$wpdb->get_results( $query_cf7_plugin, OBJECT );

		//  Debugging Only
		// var_dump($rows_o_ses_lite_plugin);
		// var_dump($rows_cf7_plugin);

		//	---------------------------------------------------
		//	Iterate 'Offload SES Lite' WordPress plugin's sends
		//	---------------------------------------------------
		foreach( $this->rows_o_ses_lite_plugin AS $__row ):
			$_row = (array)$__row;
		
			//	----------------
			//	Define Variables
			//	----------------
			$_db_timezone = 'America/Los_Angeles';	//	'Europe/London'
			$_SUCCESS = $_row['email_status'];
			$_DATETIME = new DateTime($_row['email_created'], new DateTimeZone($_db_timezone));
			$_DATETIME_PACIFICTIME = new DateTime($_DATETIME->format('Y-m-d H:i:s'), new DateTimeZone($_db_timezone));
			$_DATETIME_PACIFICTIME->setTimezone(new DateTimeZone('America/Los_Angeles'));;
		
			//	------------------
			//	Scrape Information
			//	-	`category`
			//	-	`state`
			//	------------------
		
			//	Suppressed because $_value can be undefined. Warning - this supression is too greedy.
			$_all_submitted_fields_associative = [];
			@list($_throw_away, $_all_submitted_fields_string) = preg_split('/All Submitted Fields:/i', $_row['email_message']);
			if( $_all_submitted_fields_string ):
				$_all_submitted_fields = explode("\r\n",trim($_all_submitted_fields_string));
				foreach( $_all_submitted_fields as $_string ):
					//	Suppressed because $_value can be undefined. Warning - this supression is too greedy.
					@list($_key, $_value) = explode(':', $_string);
					if( !$_key ):
						continue;
					endif;
					$_all_submitted_fields_associative[ strtolower(trim($_key)) ] = trim( (string)$_value );
				endforeach;
		
				//	Debugging Only
				/*
				echo sprintf("category:'%s' state:'%s' \r\n",
					@$_all_submitted_fields_associative['category'],
					@$_all_submitted_fields_associative['state'],
				);
				*/
			endif;
		
		
			//	----------------------------
			//	Scrape Recipient Information
			//	----------------------------
			$_HEADERS = @unserialize( $_row['email_headers'] );
			if( $_HEADERS === false ):
				//	Not a serialized string
				//	This happens (can happen) when WordPress Plugin ContactForm7 sends the email
		
				//	Debugging Only
				// echo sprintf( "email_id: %s\n", $_row['email_id']);
				// echo sprintf( "email_headers:\n%s\n", $_row['email_headers']);
		
				//	Fallback
				$_HEADERS = explode("\n", $_row['email_headers']);
			endif;
		
			$_FROM = NULL;
			$_TO = $_row['email_to'];
			$_CC = [];
			$_BCC = [];
			foreach( $_HEADERS as $_header ):
				@list($_key, $_value) = explode(':', $_header);
				$_value = @trim($_value);
				if( !$_value ):
					continue;
				endif;
				switch( strtolower(trim($_key)) ):
					case 'cc':
						$_CC[] = $_value;
						break;
					case 'bcc':
						$_BCC[] = $_value;
						break;
					case 'from':
						$_FROM = $_value;
						break;
					case 'Content-Type':
					case 'content-type':
					case 'X-WPCF7-Content-Type':
					case 'x-wpcf7-content-type':
						//	This exists when WordPress Plugin ContactForm7 sends an email.
						break;
					default:
						throw new Exception( 'Unknown header key in:' . $_header );
				endswitch;
			endforeach;
		
			//	------------
			//	Write Output
			//	------------
		
			//	Check if there is a corresponding WordPress CF7 Plugin submission record
			$_cf7_record = NULL;
			if( array_key_exists('email', $_all_submitted_fields_associative) ):
				$_cf7_record = $this->findCF7RecordWithEmailCategoryDatetime(
					strtolower((string)@$_all_submitted_fields_associative['email']),
					strtolower((string)@$_all_submitted_fields_associative['category']),
					$_DATETIME,
				);
			endif;
		
			//	Check if we should output a warning
			//	a `category` field value of either 'claimant' or 'provider' should have at least one CC/BCC recipient.
			$_COUNT_CC_BCC = count($_CC) + count($_BCC);
			$_is_catagory_routable = in_array(
				@strtolower($_all_submitted_fields_associative['category']),
				['claimant','provider']
			);

			//	-------------------
			//	Create one .csv Row
			//	-------------------
			$fields = [
				$_DATETIME_PACIFICTIME->format('Y-m-d H:i:s (g:ia)'),
				$_SUCCESS,
				@$_all_submitted_fields_associative['category'],
				@$_all_submitted_fields_associative['state'],
				$_is_catagory_routable ? 'yes' : '' ,
				$_COUNT_CC_BCC > 0 ? $_COUNT_CC_BCC : '',
				implode(', ', $_BCC),
				implode(', ', $_CC),
				$_TO,
				@$_all_submitted_fields_associative['subject'],
			];
			fputcsv($fp_client_facing, $fields);
		
		endforeach;

		fclose($fp_client_facing);

		return TRUE;

	}//function


	//	---------
	//	Helper(s)
	//	---------

	//	Create Map of WordPress CF7 Submissions using `email` as key
	function findCF7RecordWithEmailCategoryDatetime(String $given_email, String $given_category, DateTime $given_datetime): ?Array{
		// echo $given_email." ".$given_datetime->format('Y-m-d H:i:s')."\r\n";	//	Debugging Only
		$threshold_maximum_allowed_seconds_of_drift_between_records = 15;
	
		foreach( $this->rows_cf7_plugin AS $_row ):
			$_row = (array)$__row;

			$datetime = new DateTime($_row['form_date'], new DateTimeZone('America/Los_Angeles'));
			$datetime_difference_in_seconds = (Int)$datetime->diff($given_datetime)->format('s');
	
			// echo $_row['form_id'] . "\r\n";	//	Debugging Only
			$_data = @unserialize($_row['form_value']);
			if( is_array($_data) ):
				//	'unserialize()' does not also decode HTML Entities. So we have to do that also before comparison.
				$email = strtolower(trim(html_entity_decode($_data['email'])));
				$category = '';
				if( array_key_exists('category', $_data) ):
					$category = @strtolower(trim(html_entity_decode($_data['category'])));
				endif;
				$_record_matches_desired = $datetime_difference_in_seconds < $threshold_maximum_allowed_seconds_of_drift_between_records && strcasecmp($given_email, $email) === 0 && strcasecmp($given_category, $category) === 0 ;
			else:
				//	Things like "RFP/Sales" will show as "RFP&#047;Sales" unless we do this.
				$form_value_html_entity_decoded = html_entity_decode($_row['form_value']);
				//	We can't find anything useful here because we can't read the row's data.
				//	Fallback to trying to find given string(s) in the large 'form_value' string.
				$_email_is_present_in_string = preg_match(sprintf('/%s/i',$given_email),$form_value_html_entity_decoded);
				$_category_is_present_in_string = preg_match(sprintf('/%s/i',$given_email),$form_value_html_entity_decoded);
				$_record_matches_desired = $datetime_difference_in_seconds < $threshold_maximum_allowed_seconds_of_drift_between_records && $_email_is_present_in_string && $_category_is_present_in_string ;
				$_data = [
					'email'	=>	$given_email,
					'category'	=>	$given_category,
					'debugging_did_match_record_using_fallback_strategy' => TRUE,
				];
			endif;
	
			//	Nest useful DB fields into data being returned.
			$_data['form_id'] = $_row['form_id'];
			$_data['form_post_id'] = $_row['form_post_id'];
		
			//	Output
			if( $_record_matches_desired ):
				return $_data;
			endif;
		endforeach;
	
		return NULL;
	}
}