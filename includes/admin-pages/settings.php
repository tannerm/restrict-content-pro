<?php

// register the plugin settings
function rcp_register_settings() {

	// create whitelist of options
	register_setting( 'rcp_settings_group', 'rcp_settings' );
}
//call register settings function
add_action( 'admin_init', 'rcp_register_settings' );

function rcp_settings_page()
{
	global $rcp_options;
		
	?>
	<div class="wrap">
		<?php
		if ( ! isset( $_REQUEST['updated'] ) )
			$_REQUEST['updated'] = false;
		?>
		<?php if ( false !== $_REQUEST['updated'] ) : ?>
		<div class="updated fade"><p><strong><?php _e( 'Options saved', 'rcp' ); ?></strong></p></div>
		<?php endif; ?>
		<form method="post" action="options.php" class="rcp_options_form">

			<?php settings_fields( 'rcp_settings_group' ); ?>
			
			<?php $pages = get_pages(); ?>
			
			<h2 class="nav-tab-wrapper">
				<?php _e('Restrict Content Pro', 'rcp'); ?>
				<a href="#messages" class="nav-tab"><?php _e('Messages', 'rcp'); ?></a>
				<a href="#payments" class="nav-tab"><?php _e('Payments', 'rcp'); ?></a>
				<a href="#forms" class="nav-tab"><?php _e('Signup Forms', 'rcp'); ?></a>
				<a href="#emails" class="nav-tab"><?php _e('Emails', 'rcp'); ?></a>
				<a href="#misc" class="nav-tab"><?php _e('Misc', 'rcp'); ?></a>
				<a href="#logging" class="nav-tab"><?php _e('Logging', 'rcp'); ?></a>
			</h2>
			
			<div id="tab_container">
			
				<div class="tab_content" id="messages">
					<p>
						<label for="rcp_settings[free_message]"><?php _e( 'Free Content Message', 'rcp' ); ?></label><br/>		
						<textarea id="rcp_settings[free_message]" style="width: 300px; height: 100px;" name="rcp_settings[free_message]"><?php if(isset($rcp_options['free_message'])) { echo $rcp_options['free_message']; } ?></textarea>
						<div class="description"><?php _e('This is the message shown to users that do not have privilege to view free, user only content.', 'rcp'); ?></div>
					</p>
					<p>
						<label for="rcp_settings[paid_message]"><?php _e( 'Premium Content Message', 'rcp' ); ?></label><br/>		
						<textarea id="rcp_settings[paid_message]" style="width: 300px; height: 100px;" name="rcp_settings[paid_message]"><?php if(isset($rcp_options['paid_message'])) { echo $rcp_options['paid_message']; } ?></textarea>
						<div class="description"><?php _e('This is the message shown to users that do not have privilege to view premium content.', 'rcp'); ?></div>
					</p>
				
					<?php do_action('rcp_messages_settings', $rcp_options); ?>
			
				</div><!--end #messages-->
				
				<div class="tab_content" id="payments">
					<h3><?php _e('General', 'rcp'); ?></h3>
					<p>
						<select id="rcp_settings[currency]" name="rcp_settings[currency]">
							<?php 
							$currencies = array(
								'USD' => __('US Dollars (&#36;)', 'rcp'),
				                'EUR' => __('Euros (&euro;)', 'rcp'),
				                'GBP' => __('Pounds Sterling (&pound;)', 'rcp'),
				                'AUD' => __('Australian Dollars (&#36;)', 'rcp'),
				                'BRL' => __('Brazilian Real (&#36;)', 'rcp'),
				                'CAD' => __('Canadian Dollars (&#36;)', 'rcp'),
				                'CZK' => __('Czech Koruna', 'rcp'),
				                'DKK' => __('Danish Krone', 'rcp'),
				                'HKD' => __('Hong Kong Dollar (&#36;)', 'rcp'),
				                'HUF' => __('Hungarian Forint', 'rcp'),
				                'ILS' => __('Israeli Shekel', 'rcp'),
				                'JPY' => __('Japanese Yen (&yen;)', 'rcp'),
				                'MYR' => __('Malaysian Ringgits', 'rcp'),
				                'MXN' => __('Mexican Peso (&#36;)', 'rcp'),
				                'NZD' => __('New Zealand Dollar (&#36;)', 'rcp'),
				                'NOK' => __('Norwegian Krone', 'rcp'),
				                'PHP' => __('Philippine Pesos', 'rcp'),
				                'PLN' => __('Polish Zloty', 'rcp'),
				                'SGD' => __('Singapore Dollar (&#36;)', 'rcp'),
				                'SEK' => __('Swedish Krona', 'rcp'),
				                'CHF' => __('Swiss Franc', 'rcp'),
				                'TWD' => __('Taiwan New Dollars', 'rcp'),
				                'THB' => __('Thai Baht', 'rcp')
							);
							foreach($currencies as $key => $currency) {
								echo '<option value="' . $key . '" ' . selected($key, $rcp_options['currency'], false) . '>' . $currency . '</option>';
							}
						
							?>
						</select>
						<label for="rcp_settings[currency]"><?php _e( 'Currency', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('Choose your currency.', 'rcp'); ?></div>
					</p>
					<p>
						<select id="rcp_settings[currency_position]" name="rcp_settings[currency_position]">
							<option value="before" <?php selected('before', $rcp_options['currency_position']); ?>><?php _e('Before - $10', 'rcp'); ?></option>
							<option value="after" <?php selected('after', $rcp_options['currency_position']); ?>><?php _e('After - 10$', 'rcp'); ?></option>
						</select>
						<label for="rcp_settings[currency_position]"><?php _e( 'Currency Position', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('Show the currency sign before or after the price?', 'rcp'); ?></div>
					</p>
					
					<h3><?php _e('Gateways', 'rcp'); ?></h3>
					
					<p><?php _e('Check each of the payment gateways you would like to enable. Configure the selected gateways below.', 'rcp'); ?></p>
					<p>
						<?php
							$gateways = rcp_get_payment_gateways();
							foreach($gateways as $key => $gateway) :
								if(isset($rcp_options['gateways'][$key])) { $enabled = '1'; } else { $enabled = NULL; }
								echo '<input name="rcp_settings[gateways][' . $key . ']" id="rcp_settings[gateways][' . $key . ']" type="checkbox" value="1" ' . checked('1', $enabled, false) . '/>&nbsp;';
								echo '<label for="rcp_settings[gateways][' . $key . ']">' . $gateway . '</label><br/>';
							endforeach;
						?>
					</p>
					
					<hr/>
					<h3><?php _e('PayPal', 'rcp'); ?></h3>
					<p>
						<label for="rcp_settings[paypal_email]"><?php _e( 'PayPal Address', 'rcp' ); ?></label><br/>		
						<input class="regular-text" id="rcp_settings[paypal_email]" style="width: 300px;" name="rcp_settings[paypal_email]" value="<?php if(isset($rcp_options['paypal_email'])) { echo $rcp_options['paypal_email']; } ?>"/>
						<div class="description"><?php _e('Enter your PayPal email address.', 'rcp'); ?></div>
					</p>
					<p>
						<input type="checkbox" value="1" name="rcp_settings[sandbox]" id="rcp_settings[sandbox]" <?php if(isset($rcp_options['sandbox'])) checked('1', $rcp_options['sandbox']); ?>/>
						<label for="rcp_settings[sandbox]"><?php _e( 'Sandbox Mode', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('Use PayPal in Sandbox mode. This allows you to test the plugin with the <a href="http://developer.paypal.com">PayPal IPN tester</a>', 'rcp'); ?></div>
					</p>
					<p>
						<input type="checkbox" value="1" name="rcp_settings[disable_curl]" id="rcp_settings[disable_curl]" <?php if(isset($rcp_options['disable_curl'])) checked('1', $rcp_options['disable_curl']); ?>/>
						<label for="rcp_settings[disable_curl]"><?php _e( 'Disable CURL', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('Only check this option if your host does not allow cURL', 'rcp'); ?></div>
					</p>
					
					<?php do_action('rcp_payments_settings', $rcp_options); ?>
					
				</div><!--end #payments-->
			
				<div class="tab_content" id="forms">
					<p>
						<input type="checkbox" value="1" name="rcp_settings[front_end_validate]" id="rcp_settings[front_end_validate]" <?php if(isset($rcp_options['front_end_validate'])) checked('1', $rcp_options['front_end_validate']); ?>/>
						<label for="rcp_settings[front_end_validate]"><?php _e( 'jQuery Validation', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('Check this to enable live, front end form validation. If this is disabled, all validation will be done server side and will require a page reload.', 'rcp'); ?></div>
					</p>
					<p>
						<input type="checkbox" value="1" name="rcp_settings[disable_css]" id="rcp_settings[disable_css]" <?php if(isset($rcp_options['disable_css'])) checked('1', $rcp_options['disable_css']); ?>/>
						<label for="rcp_settings[disable_css]"><?php _e( 'Disable Form CSS', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('Check this to disable all included form styling.', 'rcp'); ?></div>
					</p>
					<p>
						<select id="rcp_settings[redirect]" name="rcp_settings[redirect]">
							<?php
							if($pages) :
								foreach ( $pages as $page ) {
								  	$option = '<option value="' . $page->ID . '" ' . selected($page->ID, $rcp_options['redirect'], false) . '>';
									$option .= $page->post_title;
									$option .= '</option>';
									echo $option;
								}
							else :
								echo '<option>' . __('No pages found', 'rcp') . '</option>';
							endif;
							?>
						</select>
						<label for="rcp_settings[redirect]"><?php _e( 'Redirect Page', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('This is the page users are redirected to after a successful registration', 'rcp'); ?></div>
					</p>
					<p>
						<select id="rcp_settings[registration_page]" name="rcp_settings[registration_page]">
							<?php
							if($pages) :
								foreach ( $pages as $page ) {
								  	$option = '<option value="' . $page->ID . '" ' . selected($page->ID, $rcp_options['registration_page'], false) . '>';
									$option .= $page->post_title;
									$option .= '</option>';
									echo $option;
								}
							else :
								echo '<option>' . __('No pages found', 'rcp') . '</option>';
							endif;
							?>
						</select>
						<label for="rcp_settings[registration_page]"><?php _e( 'Registration Page', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('Choose the page that has the [register_form] short code', 'rcp'); ?></div>
					</p>
					<p>
						<input type="checkbox" value="1" name="rcp_settings[enable_recaptcha]" id="rcp_settings[enable_recaptcha]" <?php if(isset($rcp_options['enable_recaptcha'])) checked('1', $rcp_options['enable_recaptcha']); ?>/>
						<label for="rcp_settings[enable_recaptcha]"><?php _e( 'Enable reCaptcha', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('Check this to enable reCaptcha on the registration form.', 'rcp'); ?></div>
					</p>
					<p>
						<label for="rcp_settings[recaptcha_public_key]"><?php _e( 'reCaptcha Public Key' ); ?></label><br/>
						<input id="rcp_settings[recaptcha_public_key]" style="width: 300px;" name="rcp_settings[recaptcha_public_key]" type="text" value="<?php if(isset($rcp_options['recaptcha_public_key'])) echo $rcp_options['recaptcha_public_key']; ?>" />
						<p class="description"><?php _e('This your own personal reCaptcha Public key. Go to', 'rcp'); ?> <a href="https://www.google.com/recaptcha/admin/list"><?php _e('your account', 'rcp'); ?></a>, <?php _e('then click on your domain (or add a new one) to find your public key.', 'rcp'); ?></p>
					</p>
					<p>
						<label for="rcp_settings[recaptcha_private_key]"><?php _e( 'reCaptcha Private Key' ); ?></label><br/>
						<input id="rcp_settings[recaptcha_private_key]" style="width: 300px;" name="rcp_settings[recaptcha_private_key]" type="text" value="<?php if(isset($rcp_options['recaptcha_private_key'])) echo $rcp_options['recaptcha_private_key']; ?>" />
						<p class="description"><?php _e('This your own personal reCaptcha Private key. Go to', 'rcp'); ?> <a href="https://www.google.com/recaptcha/admin/list"><?php _e('your account', 'rcp'); ?></a>, <?php _e('then click on your domain (or add a new one) to find your private key.', 'rcp'); ?></p>
					</p>
					<p>
						<select id="rcp_settings[recaptcha_style]" name="rcp_settings[recaptcha_style]">
							<?php
							$styles = array('red', 'white', 'blackglass', 'clean');
							foreach ( $styles as $style ) {
							  	$option = '<option value="' . $style . '" ' . selected($style, $rcp_options['recaptcha_style'], false) . '>';
								$option .= ucwords($style);
								$option .= '</option>';
								echo $option;
							}
						
							?>
						</select>
						<label for="rcp_settings[recaptcha_style]"><?php _e( 'reCaptcha Style', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('Choose the style you wish to use for your reCaptcha form', 'rcp'); ?></div>
					</p>
					<p>
						<input type="checkbox" value="1" name="rcp_settings[ssl]" id="rcp_settings[ssl]" <?php if(isset($rcp_options['ssl'])) checked('1', $rcp_options['ssl']); ?>/>
						<label for="rcp_settings[ssl]"><?php _e( 'SSL', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('Check this option if your registration page is using the https:// protocol. This will be the case if you have an SSL certificate installed.', 'rcp'); ?></div>
					</p>
					<p>
						<input type="checkbox" value="1" name="rcp_settings[disable_auto_renew]" id="rcp_settings[disable_auto_renew]" <?php if(isset($rcp_options['disable_auto_renew'])) checked('1', $rcp_options['disable_auto_renew']); ?>/>
						<label for="rcp_settings[disable_auto_renew]"><?php _e( 'Disable Auto Renew', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('Check this option if you do NOT want to allow recurring subscriptions', 'rcp'); ?></div>
					</p>
					
					<?php do_action('rcp_forms_settings', $rcp_options); ?>
					
				</div><!--end #forms-->
				
				<div class="tab_content" id="emails">
					<div id="rcp_email_options">
						<h3><?php _e('Active Subscription Email', 'rcp'); ?></h3>
						<p>
							<label for="rcp_settings[active_subject]"><?php _e( 'Subject line for the Active email', 'rcp' ); ?></label><br/>		
							<input class="regular-text" id="rcp_settings[active_subject]" style="width: 300px;" name="rcp_settings[active_subject]" value="<?php if(isset($rcp_options['active_subject'])) { echo $rcp_options['active_subject']; } ?>"/>
							<div class="description"><?php _e('The subject line for the email sent to users when their subscription becomes active.', 'rcp'); ?></div>
						</p>
						<p>
							<label for="rcp_settings[active_email]"><?php _e( 'Active Email Message', 'rcp' ); ?></label><br/>		
							<textarea id="rcp_settings[active_email]" style="width: 300px; height: 100px;" name="rcp_settings[active_email]"><?php if(isset($rcp_options['active_email'])) { echo $rcp_options['active_email']; } ?></textarea>
							<div class="description"><?php _e('This is the email message that is sent to users when their subscription becomes active.', 'rcp'); ?></div>
						</p>
						
						<h3><?php _e('Cancelled Subscription Email', 'rcp'); ?></h3>
						<p>
							<label for="rcp_settings[cancelled_subject]"><?php _e( 'Subject line for the Cancelled email', 'rcp' ); ?></label><br/>		
							<input class="regular-text" id="rcp_settings[cancelled_subject]" style="width: 300px;" name="rcp_settings[cancelled_subject]" value="<?php if(isset($rcp_options['cancelled_subject'])) { echo $rcp_options['cancelled_subject']; } ?>"/>
							<div class="description"><?php _e('The subject line for the email sent to users when their subscription is cancelled.', 'rcp'); ?></div>
						</p>
						<p>
							<label for="rcp_settings[cancelled_email]"><?php _e( 'Cancelled Email Message', 'rcp' ); ?></label><br/>		
							<textarea id="rcp_settings[cancelled_email]" style="width: 300px; height: 100px;" name="rcp_settings[cancelled_email]"><?php if(isset($rcp_options['cancelled_email'])) { echo $rcp_options['cancelled_email']; } ?></textarea>
							<div class="description"><?php _e('This is the email message that is sent to users when their subscription is cancelled.', 'rcp'); ?></div>
						</p>
						
						<h3><?php _e('Expired Subscription Email', 'rcp'); ?></h3>
						<p>
							<label for="rcp_settings[expired_subject]"><?php _e( 'Subject line for the Expired email', 'rcp' ); ?></label><br/>		
							<input class="regular-text" id="rcp_settings[expired_subject]" style="width: 300px;" name="rcp_settings[expired_subject]" value="<?php if(isset($rcp_options['expired_subject'])) { echo $rcp_options['expired_subject']; } ?>"/>
							<div class="description"><?php _e('The subject line for the email sent to users when their subscription is expired.', 'rcp'); ?></div>
						</p>
						<p>
							<label for="rcp_settings[expired_email]"><?php _e( 'Expired Email Message', 'rcp' ); ?></label><br/>		
							<textarea id="rcp_settings[expired_email]" style="width: 300px; height: 100px;" name="rcp_settings[expired_email]"><?php if(isset($rcp_options['expired_email'])) { echo $rcp_options['expired_email']; } ?></textarea>
							<div class="description"><?php _e('This is the email message that is sent to users when their subscription is expired.', 'rcp'); ?></div>
						</p>
						
						<h3><?php _e('Free Subscription Email', 'rcp'); ?></h3>
						<p>
							<label for="rcp_settings[free_subject]"><?php _e( 'Subject line for the Free membership email', 'rcp' ); ?></label><br/>		
							<input class="regular-text" id="rcp_settings[free_subject]" style="width: 300px;" name="rcp_settings[free_subject]" value="<?php if(isset($rcp_options['free_subject'])) { echo $rcp_options['free_subject']; } ?>"/>
							<div class="description"><?php _e('The subject line for the email sent to users when they sign up for a free membership.', 'rcp'); ?></div>
						</p>
						<p>
							<label for="rcp_settings[free_email]"><?php _e( 'Free Email Message', 'rcp' ); ?></label><br/>		
							<textarea id="rcp_settings[free_email]" style="width: 300px; height: 100px;" name="rcp_settings[free_email]"><?php if(isset($rcp_options['free_email'])) { echo $rcp_options['free_email']; } ?></textarea>
							<div class="description"><?php _e('This is the email message that is sent to users when they sign up for a free account.', 'rcp'); ?></div>
						</p>
						
						<h3><?php _e('Trial Subscription Email', 'rcp'); ?></h3>
						<p>
							<label for="rcp_settings[trial_subject]"><?php _e( 'Subject line for the Free Trial email', 'rcp' ); ?></label><br/>		
							<input class="regular-text" id="rcp_settings[trial_subject]" style="width: 300px;" name="rcp_settings[trial_subject]" value="<?php if(isset($rcp_options['trial_subject'])) { echo $rcp_options['trial_subject']; } ?>"/>
							<div class="description"><?php _e('The subject line for the email sent to users when they sign up for a free trial.', 'rcp'); ?></div>
						</p>
						<p>
							<label for="rcp_settings[trial_email]"><?php _e( 'Trial Email Message', 'rcp' ); ?></label><br/>		
							<textarea id="rcp_settings[trial_email]" style="width: 300px; height: 100px;" name="rcp_settings[trial_email]"><?php if(isset($rcp_options['trial_email'])) { echo $rcp_options['trial_email']; } ?></textarea>
							<div class="description"><?php _e('This is the email message that is sent to users when they sign up for a free trial.', 'rcp'); ?></div>
						</p>
						
						<?php do_action('rcp_email_settings', $rcp_options); ?>
						
					</div><!--end #rcp_email_options-->
					<div id="rcp_email_tags">
						<p><strong><?php _e('Available Template Tags', 'rcp'); ?></strong></p>
						<ul>
							<li><em>%blogname%</em> - <?php _e('will be replaced with the name of your site', 'rcp'); ?></li>
							<li><em>%username%</em> - <?php _e('will be replaced with the user name of the person receiving the email', 'rcp'); ?></li>
							<li><em>%expiration%</em> - <?php _e('will be replaced with the expiration date of subscription', 'rcp'); ?></li>
							<li><em>%subscription_name%</em> - <?php _e('will be replaced with the name of the subscription', 'rcp'); ?></li>
							<li><em>%subscription_key%</em> - <?php _e('will be replaced with the unique, 32 character key created when the user is registered', 'rcp'); ?></li>
						</ul>
					</div><!--end #rcp_email_tags-->
					<div class="clear"></div>
				</div><!--end #emails-->
				
				<div class="tab_content" id="misc">
					<p>
						<input type="checkbox" value="1" name="rcp_settings[hide_premium]" id="rcp_settings[hide_premium]" <?php if(isset($rcp_options['hide_premium'])) checked('1', $rcp_options['hide_premium']); ?>/>
						<label for="rcp_settings[hide_premium]"><?php _e( 'Hide Premium Posts', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('Check this to hide all premium posts from queries when user is not logged in. Note, this will only hide posts that have the "Paid Only?" checkbox checked.', 'rcp'); ?></div>
					</p>
					<p>
						<select id="rcp_settings[redirect_from_premium]" name="rcp_settings[redirect_from_premium]">
							<?php
							if($pages) :
								foreach ( $pages as $page ) {
								  	$option = '<option value="' . $page->ID . '" ' . selected($page->ID, $rcp_options['redirect_from_premium'], false) . '>';
									$option .= $page->post_title;
									$option .= '</option>';
									echo $option;
								}
							else :
								echo '<option>' . __('No pages found', 'rcp') . '</option>';
							endif;
							?>
						</select>
						<label for="rcp_settings[redirect]"><?php _e( 'Redirect Page', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('This is the page non-subscribed users are redirected to when attempting to access a premium post or page', 'rcp'); ?></div>
					</p>
					
					<p>
						<input type="checkbox" value="1" name="rcp_settings[hijack_login_url]" id="rcp_settings[hijack_login_url]" <?php if(isset($rcp_options['hijack_login_url'])) checked('1', $rcp_options['hijack_login_url']); ?>/>
						<label for="rcp_settings[hijack_login_url]"><?php _e( 'Redirect Default Login URL', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('Check this to force the default login URL to redirect to the page specified below.', 'rcp'); ?></div>
					</p>
					<p>
						<select id="rcp_settings[login_redirect]" name="rcp_settings[login_redirect]">
							<?php
							if($pages) :
								foreach ( $pages as $page ) {
								  	$option = '<option value="' . $page->ID . '" ' . selected($page->ID, $rcp_options['login_redirect'], false) . '>';
									$option .= $page->post_title;
									$option .= '</option>';
									echo $option;
								}
							else :
								echo '<option>' . __('No pages found', 'rcp') . '</option>';
							endif;
							?>
						</select>
						<label for="rcp_settings[redirect]"><?php _e( 'Login Page', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('This is the page the default login URL redirects to, if the option above is checked. This should be the page that contains the [login_form] short code.', 'rcp'); ?></div>
					</p>
					
					<?php do_action('rcp_misc_settings', $rcp_options); ?>
				</div><!--end #misc-->
				
				<div class="tab_content" id="logging">
					<p>
						<input type="checkbox" value="1" name="rcp_settings[email_ipn_reports]" id="rcp_settings[email_ipn_reports]" <?php if(isset($rcp_options['email_ipn_reports'])) checked('1', $rcp_options['email_ipn_reports']); ?>/>
						<label for="rcp_settings[email_ipn_reports]"><?php _e( 'Email IPN reports', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('Check this to send an email each time an IPN request is made with PayPal. The email will contain a list of all data sent. This is useful for debugging in the case that something is not working with the PayPal integration.', 'rcp'); ?></div>
					</p>
					<p>
						<input type="checkbox" value="1" name="rcp_settings[log_ipn_errors]" id="rcp_settings[log_ipn_errors]" <?php if(isset($rcp_options['log_ipn_errors'])) checked('1', $rcp_options['log_ipn_errors']); ?>/>
						<label for="rcp_settings[log_ipn_errors]"><?php _e( 'Log IPN errors', 'rcp' ); ?></label><br/>		
						<div class="description"><?php _e('Check this to enable logging of all IPN errors. This is useful for debugging in the case that something is not working with the PayPal integration. The log file will be located in restrict-content-pro/includes/ipn_errors.log', 'rcp'); ?></div>
					</p>
					<?php do_action('rcp_log_settings', $rcp_options); ?>
				</div><!--end #logging-->

			</div><!--end #tab_container-->
			
			<!-- save the options -->
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e( 'Save Options', 'rcp' ); ?>" />
			</p>
							
			
		</form>
	</div><!--end wrap-->
		
	<?php
}
