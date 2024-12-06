<?php
/**
 * The work in this file is based around the output of an autogenerator:
 * https://jeremyhixon.com/tool/wordpress-option-page-generator/
 * These contents were heavily modified after generation so avoid copy/pasting new output from the generator.
 */

class Envoy_ReportEmailSends_AdminSettings {

	static $NS			=	'envoy_report_email_sends';
	static $NS_HANDLE	=	'envoy-report-email-sends';

	public function __construct(Envoy_ReportEmailSends $ERES) {
		add_action( 'admin_menu', array( $this, sprintf('%s_add_plugin_page', SELF::$NS) ) );
		add_action( 'admin_init', array( $this, sprintf('%s_page_init', SELF::$NS) ) );
		$this->ERES = $ERES;
	}

	public function envoy_report_email_sends_add_plugin_page() {

		//  -- This adds a sub-level menu item under an existing top-level menu item --
		add_submenu_page(
			is_network_admin() ? 'settings.php' : 'options-general.php' , // parent_slug
			'Envoy - Emails Sent Report', // page_title
			'Report Emails Sent', // menu_title
			'manage_options',
			SELF::$NS_HANDLE, // menu_slug
			array( $this, sprintf('%s_create_admin_page', SELF::$NS) ), // function
			80 // position
		);
	}

	//	-----------
	//	This merely is responsible for rendering the wrapper hml form contains input fields in the admin area.
	//	-----------
	public function envoy_report_email_sends_create_admin_page() {
		$this->envoy_rest_api_email_routing_options = get_option( sprintf('%s_option_name', SELF::$NS) );
	?>

		<div class="wrap">

			<h2>Envoy - Report on Emails Sent</h2>

			<?php settings_errors(); ?>
			<hr/>

			<p></p>

			<?php if( $this->email_report_filename && $this->email_send_success && $_POST['action']=='generate' ): ?>
				<h2>📜 Report Generation Summary</h2>
				<fieldset style="box-shadow: 3px 2px 15px 0px #999; border-radius: 10px; padding: 10px;">
					<p><span style="color: red;">✅ </span>Email report generation has been run. </p>
					<p>Reporting for date: <?php echo $_POST['target_date'] ?></p>
					<p>Reporting range (days): <?php echo $_POST['date_range'] ?></p>
					<p>Email send of report was: <?php echo $this->email_send_success ? '✅ Successful' : '❌ A Failure' ?> <span style="font-size: 12px; color: LightGray;">(caveats exist)</span></p>
					<p>Generated temporary file: <?php echo $this->email_report_filename ?></p>
				</fieldset>

				<hr/>
			<?php endif; ?>

			<h2>📧 Generate & send a new report</h2>
			<fieldset style="box-shadow: 3px 2px 15px 0px #999; border-radius: 10px; padding: 10px;">
				<form method="POST" action="options-general.php?page=envoy-report-email-sends">
					<p>
						<label>
							Report activity for date:
							<input type="date" name="target_date" required />
						</label>
					</p>
					<p>
						<label>
							Date range (in days):
							<input type="number" step="1" min="1" max="360" name="date_range" value="1" required />
						</label>
					</p>
					<p>
						<button name="action" value="generate">Generate Report & Send as Email</button>
					</p>
					<input type="hidden" name="page" value="envoy-report-email-sends" />
				</form>
			</fieldset>
			<p>Note: This server's TimeZone is set to and showing <?php echo SELF::getTimezoneDescription() ?></p>

			<hr/>

			<form method="post" action="options.php">
				<?php
					settings_fields( sprintf('%s_option_group', SELF::$NS) );
					do_settings_sections( sprintf('%s-admin', SELF::$NS_HANDLE) );
					submit_button();
				?>
			</form>

		</div>
	<?php
	}

	//	-------
	//	This is responsible for (in reverse order):
	//		-	Creating an admin setting field
	//		-	The setting section that contains that field
	//		-	The setting configuration that connects the field to the saved value in the database
	//	-------
	public function envoy_report_email_sends_page_init() {

		if( $_POST['action']=='generate' ):

			list($success, $file_path) = $this->ERES->sendEmail();

			//	Remember these for reading when rendering Admin Page
			$this->email_report_filename = $file_path;
			$this->email_send_success = $success;

		endif;

		register_setting(
			sprintf('%s_option_group', SELF::$NS), // option_group
			sprintf('%s_option_name', SELF::$NS), // option_name
			array( $this, sprintf('%s_sanitize', SELF::$NS) ) // sanitize_callback
		);

		//	----------------------
		//	Email Sending Settings
		//	----------------------
		add_settings_section(
			sprintf('%s_setting_section_email_sending', SELF::$NS),	// id
			'⚙️ Settings: Email Report To Recipients',				//	title
			array( $this, sprintf('%s_section_info', SELF::$NS) ),	// callback
			sprintf('%s-admin', SELF::$NS_HANDLE)					// page
		);

		add_settings_field(
			'send_email_recipient_to',								// id
			'📇 *Send To',											// title
			array( $this, 'send_email_recipient_to_callback'),		// callback
			sprintf('%s-admin', SELF::$NS_HANDLE),					// page
			sprintf('%s_setting_section_email_sending', SELF::$NS)	// section
		);

		add_settings_field(
			'send_email_recipients_cc',								// id
			'📧 Send CC',											// title
			array( $this, 'send_email_recipients_cc_callback' ),	// callback
			sprintf('%s-admin', SELF::$NS_HANDLE),					// page
			sprintf('%s_setting_section_email_sending', SELF::$NS)	// section
		);

		add_settings_field(
			'send_email_recipients_bcc',							// id
			'📧 Send BCC',											// title
			array( $this, 'send_email_recipients_bcc_callback' ),	// callback
			sprintf('%s-admin', SELF::$NS_HANDLE),					// page
			sprintf('%s_setting_section_email_sending', SELF::$NS)	// section
		);

		add_settings_field(
			'send_email_from_address',								// id
			'📧 Send From',											// title
			array( $this, 'send_email_from_address_callback' ),		// callback
			sprintf('%s-admin', SELF::$NS_HANDLE),					// page
			sprintf('%s_setting_section_email_sending', SELF::$NS)	// section
		);

		add_settings_field(
			'brand_name',								// id
			'📧 Brand Name',											// title
			array( $this, 'brand_name_callback' ),		// callback
			sprintf('%s-admin', SELF::$NS_HANDLE),					// page
			sprintf('%s_setting_section_email_sending', SELF::$NS)	// section
		);
	}

	//	-----------
	//	This merely is responsible for form-submission validation of WordPress admin area submission.
	//	It will mutate the submission data upon save and hand it off to the database after mutation.
	//	If fields are not explicity placed here into the sanitized output then they will not be saved.
	//	-----------
	public function envoy_report_email_sends_sanitize($input) {
		$sanitary_values = $input;	//	Passthrough; no sanitization
		return $sanitary_values;
	}

	public function envoy_report_email_sends_section_info() {
	}

	//	-----------
	//	This merely is responsible for rendering the input field in the admin area.
	//	-----------
	public function send_email_recipient_to_callback() {
		$field_id = 'send_email_recipient_to';
		printf(
			'<input class="regular-text" type="text" name="%s_option_name[%s]" id="%s" value="%s">
			<div style="color:lightslategrey; font-style:italic;">The primary recipient for this report. Only one address may go here.</div>',
			SELF::$NS,
			$field_id,
			$field_id,
			$this->ERES->getPluginSettingValue($field_id, true)
		);
	}
	public function send_email_recipients_cc_callback() {
		$field_id = 'send_email_recipients_cc';
		printf(
			'<input class="regular-text" type="text" name="%s_option_name[%s]" id="%s" value="%s">
			<div style="color:lightslategrey; font-style:italic;">The CC recipient(s) for this report. Comma-separated.</div>',
			SELF::$NS,
			$field_id,
			$field_id,
			$this->ERES->getPluginSettingValue($field_id, true)
		);
	}
	public function send_email_recipients_bcc_callback() {
		$field_id = 'send_email_recipients_bcc';
		printf(
			'<input class="regular-text" type="text" name="%s_option_name[%s]" id="%s" value="%s">
			<div style="color:lightslategrey; font-style:italic;">The BCC recipient(s) for this report. Comma-separated.</div>',
			SELF::$NS,
			$field_id,
			$field_id,
			$this->ERES->getPluginSettingValue($field_id, true)
		);
	}
	public function send_email_from_address_callback() {
		$field_id = 'send_email_from_address';
		printf(
			'<input class="regular-text" type="text" name="%s_option_name[%s]" id="%s" value="%s">
			<div style="color:lightslategrey; font-style:italic;">The email address to use as the sender of this report.</div>',
			SELF::$NS,
			$field_id,
			$field_id,
			$this->ERES->getPluginSettingValue($field_id, true)
		);
	}
	public function brand_name_callback() {
		$field_id = 'brand_name';
		printf(
			'<input class="regular-text" type="text" name="%s_option_name[%s]" id="%s" value="%s">
			<div style="color:lightslategrey; font-style:italic;">The unique brand name.</div>',
			SELF::$NS,
			$field_id,
			$field_id,
			$this->ERES->getPluginSettingValue($field_id, true)
		);
	}

	static function getTimezoneDescription(){
		$datetime = new DateTime;
		$tz_server = $datetime->getTimezone() ?? date_default_timezone_get() ;
		$tz_pacific = new DateTimeZone('America/Los_Angeles');
		$offset_hours = floor($tz_pacific->getOffset($datetime) / 60 / 60);

		return sprintf("'%s' | for reference, this looks like (%s%s) hours from '%s' .",
			$tz_server->getName(),
			$offset_hours < 0 ? '+' : '' ,	//	Flip the sign
			$offset_hours * -1,
			$tz_pacific->getName(),
		);
	}

}
