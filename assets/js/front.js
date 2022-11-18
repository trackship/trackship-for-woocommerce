jQuery(document).on("submit", ".order_track_form", function(){
	var form = jQuery(this);
	var error;
	var order_id = form.find("#order_id");
	var order_email = form.find("#order_email");
	var tracking_number = form.find("#order_tracking_number");
	
	if (tracking_number.val() == '') {
		if( order_id.val() === '' ){		
			showerror( order_id );error = true;
		} else{
			hideerror(order_id);
		}
		if(order_email.val() == '' ){		
			showerror(order_email);error = true;
		} else {
			hideerror(order_email);
		}
	} else {
		if(tracking_number.val() == '' ){		
			showerror(tracking_number);error = true;
		} else {
			hideerror(tracking_number);
		}
	}
	
	if(error == true){
		return false;
	}
	
	jQuery(".order_track_form ").block({
    message: null,
    overlayCSS: {
        background: "#fff",
        opacity: .6
	}	
    });
	
	jQuery.ajax({
		url: zorem_ajax_object.ajax_url,		
		data: form.serialize(),
		type: 'POST',
		dataType: "json",
		success: function(response) {
			if(response.success == 'true'){
				jQuery('.track-order-section').replaceWith(response.html);
				jQuery('.heading_panel.checked').trigger('click');
			} else{				
				jQuery(".track_fail_msg").text(response.message);
				jQuery(".track_fail_msg").show();				
			}			
			jQuery(".order_track_form").unblock();
		},
		error: function(jqXHR, exception) {
			console.log(jqXHR.status);
			if(jqXHR.status == 302){				
				jQuery(".track_fail_msg ").show();
				jQuery(".track_fail_msg ").text('Tracking details not found.');
				jQuery(".order_track_form ").unblock();	
			} else{				
				jQuery(".track_fail_msg ").show();
				jQuery(".track_fail_msg ").text('There are some issue with Trackship.');
				jQuery(".order_track_form ").unblock();	
			}	
			
		}
	});
	return false;
});

jQuery(document).on("click", ".back_to_tracking_form", function(){
	jQuery('.tracking-detail').hide();
	jQuery('.track-order-section').show();
});
jQuery(document).on("click", ".view_table_rows", function(){
	jQuery(this).hide();
	jQuery(this).closest('.shipment_progress_div').find('.hide_table_rows').show();
	jQuery(this).closest('.shipment_progress_div').find('table.tracking-table tr:nth-child(n+3)').show();	
});
jQuery(document).on("click", ".hide_table_rows", function(){
	jQuery(this).hide();
	jQuery(this).closest('.shipment_progress_div').find('.view_table_rows').show();
	jQuery(this).closest('.shipment_progress_div').find('table.tracking-table tr:nth-child(n+3)').hide();	
});

jQuery(document).on("click", ".view_old_details", function(){
	jQuery(this).hide();
	jQuery(this).closest('.tracking-details').find('.hide_old_details').show();
	jQuery(this).closest('.tracking-details').find('.old-details').fadeIn();
});
jQuery(document).on("click", ".hide_old_details", function(){
	jQuery(this).hide();
	jQuery(this).closest('.tracking-details').find('.view_old_details').show();
	jQuery(this).closest('.tracking-details').find('.old-details').fadeOut();	
});

jQuery(document).on("click", ".view_destination_old_details", function(){
	jQuery(this).hide();
	jQuery(this).closest('.tracking-details').find('.hide_destination_old_details').show();
	jQuery(this).closest('.tracking-details').find('.old-destination-details').fadeIn();
});
jQuery(document).on("click", ".hide_destination_old_details", function(){
	jQuery(this).hide();
	jQuery(this).closest('.tracking-details').find('.view_destination_old_details').show();
	jQuery(this).closest('.tracking-details').find('.old-destination-details').fadeOut();	
});

function showerror(element){
	element.css("border-color","red");
}
function hideerror(element){
	element.css("border-color","");
}

jQuery(document).on("click", ".open_tracking_lightbox", function(){	
	
	jQuery(".tracking_info,.my_account_tracking,.fluid_section").block({
    message: null,
		overlayCSS: {
			background: "#fff",
			opacity: .6
		}	
    });
	
	
	var order_id = jQuery(this).data('order');
	var tracking_number = jQuery(this).data('tracking');	
	
	var ajax_data = {
		action: 'ts_open_tracking_lightbox',
		order_id: order_id,
		tracking_number: tracking_number,		
	};
	
	jQuery.ajax({
		url: zorem_ajax_object.ajax_url,		
		data: ajax_data,
		type: 'POST',						
		success: function(response) {		
			jQuery(".ts_tracking_popup .popuprow").html(response);				
			jQuery('.ts_tracking_popup').show();	
			jQuery(".tracking_info,.my_account_tracking,.fluid_section").unblock();				
		},
		error: function(response) {					
			jQuery(".tracking_info,.my_account_tracking,.fluid_section").unblock();
		}
	});	
	
});

jQuery(document).on("click", ".popupclose", function(){
	jQuery('.ts_tracking_popup').hide();	
});

jQuery(document).on("click", ".order_track_form .search_order_form .ts_from_input", function(){
	var div = jQuery(this).data('name');
	if ( div === 'order_id_email' ) {
		jQuery( '.search_order_form .by_tracking_number' ).slideUp( "slow", function() {
			jQuery( '.search_order_form .order_id_email' ).slideDown("slow");
		});
	} else {
		jQuery( '.search_order_form .order_id_email' ).slideUp( "slow", function() {
			jQuery( '.search_order_form .by_tracking_number' ).slideDown("slow");
		});
	}
});

//If we will do change into below jQuery so we need to also change in trackship.js and front.js
jQuery(document).on("click", ".tracking-detail .heading_panel", function () {
	if (jQuery(this).hasClass('active')) {
		jQuery(this).removeClass('active');
		jQuery(this).children('.accordian-arrow').removeClass('down').addClass('right');
		jQuery(this).siblings('.content_panel').slideUp('slow');
	} else {
		var parent = jQuery(this).parent('.tracking_event_tab_view');
		parent.find(".heading_panel").removeClass('active');
		parent.find(".content_panel").removeClass('active').slideUp('slow');
		jQuery(this).addClass('active');
		parent.find('.accordian-arrow').removeClass('down').addClass('right');
		jQuery(this).children('.accordian-arrow').removeClass('right').addClass('down');
		jQuery(this).next('.content_panel').slideDown('slow');
	}
});

jQuery(document).ready(function () {
	'use strict';
	jQuery('.heading_panel.checked').trigger('click');
});

jQuery(document).on("change", ".unsubscribe_emails_checkbox, .unsubscribe_sms_checkbox", function () {
	jQuery(this).parent('.shipment_status_notifications label').start_loader();

	if (jQuery(this).prop("checked") == true) {
		var checkbox = 1;
	} else {
		var checkbox = '';
	}

	var ajax_data = {
		action: 'save_unsunscribe_email_notifications_data',
		order_id: jQuery('.order_id_field').val(),
		security: jQuery('.unsubscribe_emails_nonce').val(),
		checkbox: checkbox,
		lable:jQuery(this).data('lable')
	};
	jQuery.ajax({
		url: woocommerce_params.ajax_url,
		data: ajax_data,
		type: 'POST',
		success: function (response) {
			jQuery(".shipment_status_notifications label").stop_loader();
			if ( checkbox == 1 ) {
				jQuery(this).prop('checked', true);
			} else {
				jQuery(this).prop('checked', false);
			}
		},
		error: function (response, jqXHR, exception) {
			console.log(response);
			var warning_msg = '';
			if (jqXHR.status === 0) {
				warning_msg = 'Not connect.\n Verify Network.';
			} else if (jqXHR.status === 404) {
				warning_msg = 'Requested page not found. [404]';
			} else if (jqXHR.status === 500) {
				warning_msg = 'Internal Server Error [500].';
			} else if (exception === 'parsererror') {
				warning_msg = 'Requested JSON parse failed.';
			} else if (exception === 'timeout') {
				warning_msg = 'Time out error.';
			} else if (exception === 'abort') {
				warning_msg = 'Ajax request aborted.';
			} else {
				warning_msg = 'Uncaught Error.\n' + jqXHR.responseText;
			}
			jQuery(".shipment_status_notifications label").stop_loader();
		}
	});
	return false;
});

(function( $ ){
	'use strict';
	$.fn.start_loader = function() {
		if( this.find(".zorem_loader").length === 0 ){this.append("<span class=zorem_loader></span>");}
		return this;
	}; 
})( jQuery );

(function( $ ){
	'use strict';
	$.fn.stop_loader = function() {
		this.find(".zorem_loader").remove();
		return this;
	}; 
})( jQuery );
