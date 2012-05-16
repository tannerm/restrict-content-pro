jQuery(document).ready(function($) {
	// settings tabs

	//when the history state changes, gets the url from the hash and display 
	jQuery(window).bind( 'hashchange', function(e) {
		
		var url = jQuery.param.fragment();

		//hide all 
		jQuery( '#tab_container .tab_content' ).hide();
		jQuery( '#tab_container' ).children(".tab_content").hide();
		jQuery(".nav-tab-wrapper a").removeClass("nav-tab-active");

		//find a href that matches url
		if (url) {
			jQuery( '.nav-tab-wrapper a[href="#' + url + '"]' ).addClass( 'nav-tab-active' ); 
			jQuery("#tab_container #" + url).addClass("selected").fadeIn();
		} else {
			jQuery( 'h2.nav-tab-wrapper a[href="#messages"]' ).addClass( 'nav-tab-active' ); 
			jQuery("#tab_container #messages").addClass("selected").fadeIn();
		}		
	});
 
	// Since the event is only triggered when the hash changes, we need to trigger
	// the event now, to handle the hash the page may have loaded with.
	jQuery(window).trigger( 'hashchange' );			

	
	if($('.form-table .datepicker').length > 0 ) {
		var dateFormat = 'yy-mm-dd';
		$('.datepicker').datepicker({dateFormat: dateFormat});
	}
	$('.rcp_deactivate').click(function() {
		if(confirm('Are you sure you wish to cancel this member\'s subscription?')) {
			return true;
		} else {
			return false;
		}
	});
	$('.rcp_delete_subscription').click(function() {
		if(confirm("If you delete this subscription, all members registered with this level will be canceled. \n\nProceed?")) {
			return true;
		} else {
			return false;
		}
		return false;
	});
	$('#rcp-add-new-member').submit(function() {
		if($('#rcp-user option:selected').val() == 'choose') {
			alert('You must choose a username');	
			return false;
		}
		return true;
	});
	$('#rcp-price-select').change(function() {
		var free = $('option:selected', this).val();
		if(free == 'free') {
			$('#rcp-price').val(0);
		}
	});
	// make columns sortable via drag and drop
	$(".rcp-subscriptions tbody").sortable({
		handle: '.dragHandle', items: '.rcp-subscription', opacity: 0.6, cursor: 'move', axis: 'y', update: function() {
			var order = $(this).sortable("serialize") + '&action=update-subscription-order';
			$.post(ajaxurl, order, function(response) {
				// response here
			});
		}
	});
	// prettify the documentation code samples
	$.beautyOfCode.init({
			theme: 'Default',
		brushes: ['Php']
	});
	
	// auto calculate the subscription expiration when manually adding a user
	$('#rcp-level').change(function() {
		var level_id = $('option:selected', this).val();
		data = {
			action: 'rcp_get_subscription_expiration',
			subscription_level: level_id
		};
		$.post(ajaxurl, data, function(response) {
			$('#rcp-expiration').val(response);
		});
	});
});