jQuery(document).ready(function($) {
	if(rcp_script_options.validate == 'true') {
		$("#rcp_registration_form").validate({
			rules: {
				rcp_user_pass_confirm: {
					equalTo: '#rcp_password'
				},
				rcp_user_email: {
					email: true
				}
			},
			errorPlacement: function(error, element) {},
			submitHandler: function(form) {
				form.submit();
			}
		});
		
		$("#rcp_password_form").validate({
			rules: {
				rcp_user_pass_confirm: {
					equalTo: '#rcp_user_pass'
				}
			},
			errorPlacement: function(error, element) {},
			submitHandler: function(form) {
				form.submit();
			}
		});
	}
	
	if($('#rcp_payment_gateways').length > 0 ) {
		// show the Select Payment method option
		$('.rcp_level').change(function() {	
			var price = $(this).attr('rel');
			if(price > 0) {
				$('#rcp_payment_gateways').show(200);
			} else {
				$('#rcp_payment_gateways').hide(200);
			}
		});
	}
	$('.rcp_level').change(function() {
		if( $(this).next().next().next().attr('rel') == 0 ) {
			$('#rcp_auto_renew_wrap').hide();
			$('#rcp_auto_renew_wrap input').attr('checked', false);
		} else {
			$('#rcp_auto_renew_wrap').show();
		}
	});
	$('#rcp_discount_code').keyup(function(key) {
		if(key.which != 13) {
			var discount = $(this).val();
			var data = {
				action: 'validate_discount',
				code: discount
			};
			$.post(rcp_script_options.ajaxurl, data, function(response) {
				if(response == 'invalid') {
					// code is invalid
					$('.rcp_discount_valid').hide();
					$('.rcp_discount_invalid').show();
				} else if(response == 'valid') {
					// code is valid
					$('.rcp_discount_invalid').hide();
					$('.rcp_discount_valid').show();
				} else if(response == 'valid and full') {
					// code is valid
					$('.rcp_discount_invalid').hide();
					$('.rcp_discount_valid').show();
					$('#rcp_payment_gateways').hide();
				}
			});
		}
	});
});