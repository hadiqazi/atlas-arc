<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !is_admin() ) 
{
    echo 'Direct access not allowed.';
    exit;
}

global $wpdb, $cptslotsb_addons_active_list, $cptslotsb_addons_objs_list;

$message = "";

if( isset( $_GET[ 'b' ] ) && $_GET[ 'b' ] == 1 )
{
    $this->verify_nonce ($_GET["anonce"], 'cptslotsb_actions_list');
	// Save the option for active addons
	delete_option( 'cptslotsb_addons_active_list' );
	if( !empty( $_GET[ 'cptslotsb_addons_active_list' ] ) && is_array( $_GET[ 'cptslotsb_addons_active_list' ] ) ) 
	{
        $tags = array_map( 'sanitize_text_field', $_GET[ 'cptslotsb_addons_active_list' ] );
		update_option( 'cptslotsb_addons_active_list', $tags );
	}	
	
	// Get the list of active addons
	$cptslotsb_addons_active_list = get_option( 'cptslotsb_addons_active_list', array() );
    $message = "Add Ons settings updated";
}

$nonce = wp_create_nonce( 'cptslotsb_actions_list' );

?>
<style>
	.clear{clear:both;}
	.ahb-addons-container {
		border: 1px solid #e6e6e6;
		padding: 20px;
		border-radius: 3px;
		-webkit-box-flex: 1;
		flex: 1;
		margin: 1em 1em 1em 0;
		min-width: 200px;
		background: white;
		position:relative;
	}
	.ahb-addons-container h2{margin:0 0 20px 0;padding:0;}
	.ahb-addon{border-bottom: 1px solid #efefef;padding: 10px 0;}
	.ahb-addon:first-child{border-top: 1px solid #efefef;}
	.ahb-addon:last-child{border-bottom: 0;}
	.ahb-addon label{font-weight:600;}
	.ahb-addon p{font-style:italic;margin:5px 0 0 0;}
	.ahb-first-button{margin-right:10px !important;}
    
    .ahb-buttons-container{margin:1em 1em 1em 0;}
    .ahb-return-link{float:right;}

	.ahb-disabled-addons {
		background: #f9f9f9;
	}
	.ahb-addons-container h2{margin-left:30px;}
	.ahb-disabled-addons *{
		color:#888888;
	}
	.ahb-disabled-addons input{
		pointer-events: none !important;
	}

	/** For Ribbon **/
	.ribbon {
		position: absolute;
		left: -5px; top: -5px;
		z-index: 1;
		overflow: hidden;
		width: 75px; height: 75px;
		text-align: right;
	}
	.ribbon span {
		font-size: 10px;
		font-weight: bold;
		color: #FFF;
		text-transform: uppercase;
		text-align: center;
		line-height: 20px;
		transform: rotate(-45deg);
		-webkit-transform: rotate(-45deg);
		width: 100px;
		display: block;
		background: #79A70A;
		background: linear-gradient(#2989d8 0%, #1e5799 100%);
		box-shadow: 0 3px 10px -5px rgba(0, 0, 0, 1);
		position: absolute;
		top: 19px; left: -21px;
	}
	.ribbon span::before {
		content: "";
		position: absolute; left: 0px; top: 100%;
		z-index: -1;
		border-left: 3px solid #1e5799;
		border-right: 3px solid transparent;
		border-bottom: 3px solid transparent;
		border-top: 3px solid #1e5799;
	}
	.ribbon span::after {
		content: "";
		position: absolute; right: 0px; top: 100%;
		z-index: -1;
		border-left: 3px solid transparent;
		border-right: 3px solid #1e5799;
		border-bottom: 3px solid transparent;
		border-top: 3px solid #1e5799;
	}
</style>

<script type="text/javascript">
    
 function cp_activateAddons()
 {
    var cptslotsb_addons = document.getElementsByName("cptslotsb_addons"),
		cptslotsb_addons_active_list = [];
	for( var i = 0, h = cptslotsb_addons.length; i < h; i++ )
	{
		if( cptslotsb_addons[ i ].checked ) cptslotsb_addons_active_list.push( 'cptslotsb_addons_active_list[]='+encodeURIComponent( cptslotsb_addons[ i ].value ) );
	}	
	document.location = 'admin.php?page=<?php echo esc_js($this->menu_parameter); ?>_addons&anonce=<?php echo esc_js($nonce); ?>&b=1&r='+Math.random()+( ( cptslotsb_addons_active_list.length ) ? '&'+cptslotsb_addons_active_list.join( '&' ) : '' )+'#addons-section';
 }    
 
</script>

<a id="top"></a>

<h1>WP Time Slots Booking Form - <?php _e('Add Ons','wp-time-slots-booking-form'); ?></h1>

<?php if ($message) echo "<div id='setting-error-settings_updated' class='updated' style='margin:0px;'><h2>".esc_html($message)."</h2></div> <br />";
 ?>

<div class="ahb-buttons-container">
	<a href="<?php print esc_attr(admin_url('admin.php?page='.$this->menu_parameter));?>" class="ahb-return-link">&larr;<?php _e('Return to the calendars list','wp-time-slots-booking-form'); ?></a>
	<div class="clear"></div>
</div>


<input type="button" value="Activate/Deactivate Marked Add Ons" onclick="cp_activateAddons();" class="button button-primary ahb-first-button" />
<input type="button" value="Get The Full List of Add Ons" onclick="document.location='?page=cp_timeslotsbooking_upgrade';"class="button" />
<div class="clear"></div>

<!-- Add Ons -->
<h2><?php _e('Active Add Ons','wp-time-slots-booking-form'); ?></h2>
<div class="ahb-addons-container">
	<div class="ahb-addons-group">

	<?php
	foreach( $cptslotsb_addons_objs_list as $key => $obj )
	{
		print '<div class="ahb-addon" style="border:0;"><label><input type="checkbox" id="'.$key.'" name="cptslotsb_addons" value="'.$key.'" '.( ( $obj->addon_is_active() ) ? 'CHECKED' : '' ).'>'.$obj->get_addon_name().'</label><p>'.$obj->get_addon_description().'</p></div>';
	}
	?>    
		
	</div>
</div>

<!-- Disabled Add Ons -->
<h2>Add Ons available only with the Developer version of the plugin</h2>

<div class="ahb-addons-container ahb-disabled-addons">
	<div class="ribbon"><span>Upgrade</span></div>
	<h2>Payment Gateways Integration</h2>
	<div class="ahb-addons-group">
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>PayPal Standard Payments Integration</label>
			<p>The add-on adds support for PayPal Standard payments</p>
		</div>
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>Authorize.net Server Integration Method</label>
			<p>The add-on adds support for Authorize.net Server Integration Method payments</p>
		</div>
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>iDeal Mollie</label>
			<p>The add-on adds support for iDeal via Mollie payments</p>
		</div>
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>iDeal TargetPay</label>
			<p>The add-on adds support for iDeal via TargetPay payments</p>
		</div>
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>PayPal Pro</label>
			<p>The add-on adds support for PayPal Payment Pro payments to accept credit cars directly into the website</p>
		</div>
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>RedSys TPV</label>
			<p>The add-on adds support for RedSys TPV payments</p>
		</div>
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>SagePay Payment Gateway</label>
			<p>The add-on adds support for SagePay payments</p>
		</div>
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>SagePayments Payment Gateway</label>
			<p>The add-on adds support for SagePayments payments</p>
		</div>
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>Skrill Payments Integration</label>
			<p>The add-on adds support for Skrill payments</p>
		</div>
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>Stripe</label>
			<p>The add-on adds support for Stripe payments</p>
		</div>
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>WorldPay Payment Gateway</label>
			<p>The add-on adds support for WorldPay payments</p>
		</div>        
	</div>
</div>
<div class="ahb-to-top"><a href="#top">&uarr; Top</a></div>

<div class="ahb-addons-container ahb-disabled-addons">
	<div class="ribbon"><span>Upgrade</span></div>
	<h2>Integration with third party plugins</h2>
	<div class="ahb-addons-group">
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>WooCommerce</label>
			<p>The add-on allows integrate the forms with WooCommerce products</p>
		</div>
	</div>
</div>
<div class="ahb-to-top"><a href="#top">&uarr; Top</a></div>

<div class="ahb-addons-container ahb-disabled-addons">
	<div class="ribbon"><span>Upgrade</span></div>
	<h2>Improvements</h2>
	<div class="ahb-addons-group">
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>Google Calendar API</label>
			<p>The add-on adds support for Google Calendar API integration</p>
		</div>    
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>Appointment Reminders</label>
			<p>The add-on adds the reminders for appointments feature</p>
		</div>
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>Appointment Cancellation</label>
			<p>The add-on adds support for cancellation links into the notification emails</p>
		</div>
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>Coupons</label>
			<p>The add-on adds support for coupons / discounts codes</p>
		</div>    
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>Double opt-in email verification</label>
			<p>Double opt-in email verification link to mark the booking as approved</p>
		</div>                 
        <div class="ahb-addon">
            <label><input type="checkbox" disabled>Frontend List: Grouped by Date Add-on</label>
            <p>The add-on allows to displays list (schedule) of bookings grouped by date in the frontend</p>
        </div>        
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>iCal Automatic Import</label>
			<p>The add-on adds support for importing iCal files from external websites/services</p>
		</div>   
        <div class="ahb-addon">
			<label><input type="checkbox" disabled>Import raw bookings (CSV format)</label>
			<p>The add-on allows to import bookings in raw CSV format</p>
		</div>           
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>Limit the number of appointments per user</label>
			<p>The add-on adds support for limiting the number of appointments per user</p>
		</div>    
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>QRCode Image - Barcode</label>
			<p>Generates a QRCode image for each booking.</p>
		</div>          
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>Remove or Ignore Old Bookings</label>
			<p>The add-on allows to automatically remove or ignore old bookings to increase the booking form speed.</p>
		</div>         
        <div class="ahb-addon">
            <label><input type="checkbox" disabled>Shared Availability between Calendars</label>
            <p>The add-on allows to share the booked times between calendars (for blocking booked times)"</p>
        </div>         
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>Signature Fields</label>
			<p>The add-on allows to replace form fields with "Signature" fields</p>
		</div>        
        <div class="ahb-addon">
            <label><input type="checkbox" disabled>Status Modification Emails</label>
            <p>The add-on allows to define emails to be sent when the booking status is changed from the bookings list or by a payment add-on</p>
        </div>          
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>Uploads</label>
			<p>The add-on allows to add the uploaded files to the Media Library, and the support for new mime types</p>
		</div>
        <div class="ahb-addon">
			<label><input type="checkbox" disabled>User Calendar Creation</label>
			<p>The add-on creates and assign a calendar for each new registered user</p>
		</div>               
	</div>
</div>
<div class="ahb-to-top"><a href="#top">&uarr; Top</a></div>

<div class="ahb-addons-container ahb-disabled-addons">
	<div class="ribbon"><span>Upgrade</span></div>
	<h2>Integration with third party services</h2>
	<div class="ahb-addons-group">
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>MailChimp</label>
			<p>The add-on creates MailChimp List members with the submitted information</p>
		</div>
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>reCAPTCHA</label>
			<p>The add-on allows to protect the forms with reCAPTCHA service of Google</p>
		</div>
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>SalesForce</label>
			<p>The add-on allows create SalesForce leads with the submitted information</p>
		</div>
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>WebHook</label>
			<p>The add-on allows put the submitted information to a webhook URL, and integrate the forms with the Zapier service</p>
		</div>
        <div class="ahb-addon">
			<label><input type="checkbox" disabled>Zoom.us Meetings Integration</label>
			<p>Automatically creates a Zoom.us meeting for the booked time</p>
		</div>                 
	</div>
</div>
<div class="ahb-to-top"><a href="#top">&uarr; Top</a></div>

<div class="ahb-addons-container ahb-disabled-addons">
	<div class="ribbon"><span>Upgrade</span></div>
	<h2>SMS Text Delivery</h2>
	<div class="ahb-addons-group">
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>Twilio</label>
			<p>The add-on allows to send notification and reminder messages (SMS) via Twilio</p>
		</div>
		<div class="ahb-addon">
			<label><input type="checkbox" disabled>Clickatell</label>
			<p>(SMS) via Clickatell</p>
		</div>
	</div>
</div>
<div class="ahb-to-top" style="margin-bottom:10px;"><a href="#top">&uarr; <?php _e('Top','wp-time-slots-booking-form'); ?></a></div>

<input type="button" value="<?php _e('Activate/Deactivate Marked Add Ons','wp-time-slots-booking-form'); ?>" onclick="cp_activateAddons();" class="button button-primary ahb-first-button" />
<input type="button" value="<?php _e('Get The Full List of Add Ons','wp-time-slots-booking-form'); ?>" onclick="document.location='?page=cp_timeslotsbooking_upgrade';"class="button" />
<div class="clear"></div>