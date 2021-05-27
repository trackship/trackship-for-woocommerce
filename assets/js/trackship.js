( function( $, data, wp, ajaxurl ) {			
	
	var trackship_js = {
		
		init: function() {						
							
			$(document).on( 'submit', '#wc_ast_trackship_form', this.save_wc_ast_trackship_form );
			$("#trackship_tracking_page_form").on( 'click', '.woocommerce-save-button', this.save_trackship_tracking_page_form );
			$("#trackship_late_shipments_form").on( 'click', '.woocommerce-save-button', this.save_trackship_late_shipments_form );			
			$(".tipTip").tipTip();

		},				
		
		save_wc_ast_trackship_form: function( event ) {			
			event.preventDefault();
			
			$("#wc_ast_trackship_form").find(".spinner").addClass("active");			
			var ajax_data = $("#wc_ast_trackship_form").serialize();
			
			$.post( ajaxurl, ajax_data, function(response) {
				$("#wc_ast_trackship_form").find(".spinner").removeClass("active");	
				$(document).zorem_snackbar( trackship_script.i18n.data_saved );
			});
			return;
		},

		save_trackship_tracking_page_form: function( event ) {			
			event.preventDefault();
			
			$("#trackship_tracking_page_form").find(".spinner").addClass("active");			
			var ajax_data = $("#trackship_tracking_page_form").serialize();
			
			$.post( ajaxurl, ajax_data, function(response) {
				$("#trackship_tracking_page_form").find(".spinner").removeClass("active");
				
				jQuery("#trackship_settings_snackbar").addClass('show_snackbar');	
				jQuery("#trackship_settings_snackbar").text(trackship_script.i18n.data_saved);			
				setTimeout(function(){ jQuery("#trackship_settings_snackbar").removeClass('show_snackbar'); }, 3000);
				
				jQuery('.tracking_page_preview').prop("disabled", false);	
			});			
		},

		save_trackship_late_shipments_form: function( event ) {			
			event.preventDefault();
			
			$("#trackship_late_shipments_form").find(".spinner").addClass("active");			
			var ajax_data = $("#trackship_late_shipments_form").serialize();
			
			$.post( ajaxurl, ajax_data, function(response) {
				$("#trackship_late_shipments_form").find(".spinner").removeClass("active");
				jQuery(document).zorem_snackbar( trackship_script.i18n.data_saved );
			});			
		},	
	};
	$(window).load(function(e) {		
        trackship_js.init();
    });

})( jQuery, trackship_script, wp, ajaxurl );

jQuery( document ).ready(function() {
	
	jQuery(".woocommerce-help-tip").tipTip();
	
	if ( jQuery.fn.wpColorPicker ) {
	
		jQuery('#wc_ast_select_border_color').wpColorPicker({
			change: function(e, ui) {
				var color = ui.color.toString();		
				jQuery('#tracking_preview_iframe').contents().find('.col.tracking-detail').css('border','1px solid '+color);
				jQuery('.tracking_page_preview').prop("disabled", true);
			},
		});		
		
		jQuery('#wc_ast_status_label_color').wpColorPicker({
			change: function(e, ui) {		
				var color = ui.color.toString();			
				jQuery('.order-status-table .order-label.wc-delivered').css('background',color);			
			}, 	
		});
	}
});

jQuery(document).on("change", ".select_t_layout_section .radio-img", function(){
	jQuery('.tracking_page_preview').prop("disabled", true);	
});

jQuery(document).on("click", "#wc_ast_link_to_shipping_provider", function(){	
	jQuery('.tracking_page_preview').prop("disabled", true);
});

jQuery(document).on("click", "#wc_ast_hide_tracking_provider_image", function(){	
	jQuery('.tracking_page_preview').prop("disabled", true);
});

jQuery(document).on("click", "#wc_ast_hide_tracking_events", function(){
	jQuery('.tracking_page_preview').prop("disabled", true);
});

jQuery(document).on("click", "#wc_ast_remove_trackship_branding", function(){
	jQuery('.tracking_page_preview').prop("disabled", true);
});

jQuery(document).on("click", ".tracking_page_preview", function(){	
	
	jQuery("#trackship_tracking_page_form").find(".spinner").addClass("active");
	document.getElementById('tracking_preview_iframe').contentDocument.location.reload(true);
	
	jQuery('#tracking_preview_iframe').load(function(){
		jQuery("#trackship_tracking_page_form").find(".spinner").removeClass("active");
		jQuery('.tracking_page_preview_popup').show();	
		var iframe = document.getElementById("tracking_preview_iframe");
		iframe.style.height = iframe.contentWindow.document.body.scrollHeight + 'px';  		
	});	
});

jQuery(document).on("click", ".popupclose", function(){	
	jQuery('.tracking_page_preview_popup').hide();
});
jQuery(document).on("click", ".popup_close_icon", function(){	
	jQuery('.tracking_page_preview_popup').hide();	
});

var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }
};

jQuery(document).on("click", ".tab_input", function(){
	var tab = jQuery(this).data('tab');
	var label = jQuery(this).data('label');
	jQuery('.zorem-layout__header-breadcrumbs .header-breadcrumbs-last').text(label);
	var url = window.location.protocol + "//" + window.location.host + window.location.pathname+"?page=trackship-for-woocommerce&tab="+tab;
	window.history.pushState({path:url},'',url);	
});

jQuery(document).click(function(){
	var $trigger = jQuery(".trackship_dropdown");
    if($trigger !== event.target && !$trigger.has(event.target).length){
		jQuery(".trackship-dropdown-content").hide();
    }   
});

jQuery(document).on("click", ".trackship-dropdown-menu", function(){	
	jQuery('.trackship-dropdown-content').show();
});

jQuery(document).on("click", ".trackship-dropdown-content li a", function(){
	var tab = jQuery(this).data('tab');
	var label = jQuery(this).data('label');
	var section = jQuery(this).data('section');
	jQuery('.inner_tab_section').hide();
	jQuery('.trackship_nav_div').find("[data-tab='" + tab + "']").prop('checked', true); 
	jQuery('#'+section).show();
	jQuery('.zorem-layout__header-breadcrumbs .header-breadcrumbs-last').text(label);
	var url = window.location.protocol + "//" + window.location.host + window.location.pathname+"?page=trackship-for-woocommerce&tab="+tab;
	window.history.pushState({path:url},'',url);
	jQuery(".trackship-dropdown-content").hide();
});

jQuery(document).on("change", ".ts_delivered_order_status_toggle", function(){	
	if(jQuery(this).prop("checked") == true){
		jQuery('.status-label-li').show();
		jQuery('.automation_parent').show();
	} else{
		jQuery('.status-label-li').hide();
		jQuery('.automation_parent').hide();
	}
});

jQuery(document).on("click", ".bulk_shipment_status_button", function(){
	jQuery( ".trackship-notice" ).block({
		message: null,
		overlayCSS: {
			background: "#fff",
			opacity: .6
		}	
    });	
	var ajax_data = {
		action: 'bulk_shipment_status_from_settings',		
	};
	jQuery.ajax({
		url: ajaxurl,		
		data: ajax_data,		
		type: 'POST',		
		success: function(response) {
			jQuery(".trackship-notice").unblock();
			jQuery( '.bulk_shipment_status_button' ).attr("disabled", true);
			jQuery(document).zorem_snackbar( 'Tracking info sent to Trackship for all Orders.' );
		},
		error: function(response) {
			console.log(response);			
		}
	});
	return false;
});

jQuery(document).on("change", "#ts_analytics_shipping_date", function(){
	refresh_ts_analytics_report();
});

jQuery(document).on("change", "#ts_analytics_shipment_status", function(){
	refresh_ts_analytics_report();
});

jQuery(document).on("change", "#ts_analytics_shipping_provider", function(){
	refresh_ts_analytics_report();
});

function refresh_ts_analytics_report(){
	var shipping_date = jQuery("#ts_analytics_shipping_date").val();
	var shipment_status = jQuery("#ts_analytics_shipment_status").val();
	var shipping_provider = jQuery("#ts_analytics_shipping_provider").val();
	
	jQuery(".outer_form_table").block({
		message: null,
		overlayCSS: {
			background: "#fff",
			opacity: .6
		}	
    });
	
	var ajax_data = {
		action: 'refresh_ts_analytics_report',
		shipping_date: shipping_date,		
		shipment_status: shipment_status,
		shipping_provider: shipping_provider,		
	};
	
	jQuery.ajax({
		url: ajaxurl,		
		data: ajax_data,		
		type: 'POST',		
		//dataType: "json",
		success: function(response) {
			jQuery(".outer_form_table").unblock();
			jQuery('.ts-analytics-report-section').replaceWith(response);	
		},
		error: function(response) {
			console.log(response);			
		}
	});
	return false;
}

jQuery(document).on("click", ".tool_link", function(){
	jQuery('#tab_tools').trigger( "click" );
});

jQuery(document).on("change", ".shipment_status_toggle input", function(){
	jQuery("#content5 ").block({
    message: null,
    overlayCSS: {
        background: "#fff",
        opacity: .6
	}	
    });
	
	var settings_data = jQuery(this).data("settings");
	var wcast_enable_status_email;
	
	if(jQuery(this).prop("checked") == true){
		wcast_enable_status_email = 1;
		jQuery(this).closest('tr').addClass('enable');
		jQuery(this).closest('tr').removeClass('disable');
	} else {
		wcast_enable_status_email = 0;
		jQuery(this).closest('tr').addClass('disable');
		jQuery(this).closest('tr').removeClass('enable');
		if( settings_data == 'late_shipments_email_settings') jQuery('.late-shipments-email-content-table').hide();	
	}
	
	var id = jQuery(this).attr('id');
	
	var ajax_data = {
		action: 'update_shipment_status_email_status',
		id: id,
		wcast_enable_status_email: wcast_enable_status_email,
		settings_data: settings_data,
		security: jQuery( '#tswc_shipment_status_email' ).val()
	};
	
	jQuery.ajax({
		url: ajaxurl,		
		data: ajax_data,
		type: 'POST',
		success: function(response) {	
			jQuery("#content5 ").unblock();
			jQuery(document).zorem_snackbar( trackship_script.i18n.data_saved );
		},
		error: function(response) {					
		}
	});
});

jQuery(document).on("click", ".late_shipments_a", function(){
	jQuery('.late-shipments-email-content-table').toggle();
});

/*jQuery('body').click( function(){	
	if ( jQuery('.delivered_row button.button.wp-color-result').hasClass( 'wp-picker-open' ) ) { 
		save_automation_form();
	}
});*/

jQuery(document).on("click", "body", function(){
	if ( jQuery('.delivered_row button.button.wp-color-result').hasClass( 'wp-picker-open' ) ) {
		save_automation_form();
	}
});

jQuery('.delivered_row button.button.wp-color-result').click( function(){	
	if ( jQuery(this).hasClass( 'wp-picker-open' ) ) {}else{save_automation_form();}
});

jQuery(document).on("change", ".ts_custom_order_color_select, #wc_ast_status_change_to_delivered", function(){
	save_automation_form();
});
jQuery(document).on("change", ".ts_order_status_toggle", function(){
	
	if(jQuery(this).prop("checked") == true){
		jQuery('.status_change_to_delivered_tr').fadeIn();
	} else{
		jQuery('.status_change_to_delivered_tr').fadeOut();
	}
	
	save_automation_form();
});

function save_automation_form(){
	jQuery(".order-status-table").block({
		message: null,
		overlayCSS: {
			background: "#fff",
			opacity: .6
		}	
    });	
	var form = jQuery('#wc_ast_trackship_automation_form');
	jQuery.ajax({
		url: ajaxurl,		
		data: form.serialize(),		
		type: 'POST',		
		success: function(response) {
			jQuery(".order-status-table").unblock();
			jQuery(document).zorem_snackbar( trackship_script.i18n.data_saved );
		},
		error: function(response) {
			console.log(response);			
		}
	});
	return false;
}

jQuery(document).on( "change", "#smswoo_sms_provider", function(){
	'use strict';
	jQuery(".smswoo_sms_provider").hide();
	
	var provider = jQuery(this).val();	
	//jQuery( "."+provider+"_link_provider" ).show();
	jQuery( "."+provider+"_sms_provider" ).show();
});

/*
* trigger change event on page load
*/
jQuery(document).ready(function() {
	'use strict';
	jQuery("#smswoo_sms_provider").trigger("change");
});

/* zorem_snackbar jquery */
(function( $ ){
	$.fn.zorem_snackbar = function(msg) {
		var zorem_snackbar = $("<div></div>").addClass('zorem_snackbar show_snackbar').text( msg );
		$("body").append(zorem_snackbar);
		
		setTimeout(function(){ zorem_snackbar.remove(); }, 3000);
		
		return this;
	}; 
})( jQuery );

/* zorem_snackbar_warning jquery */
(function( $ ){
	$.fn.zorem_snackbar_warning = function(msg) {
		var zorem_snackbar_warning = $("<div></div>").addClass( 'zorem_snackbar_warning show_snackbar' ).html( msg );
		$("body").append(zorem_snackbar_warning);
		
		setTimeout(function(){ zorem_snackbar_warning.remove(); }, 3000);
		
		return this;
	}; 
})( jQuery );

/*
* save tracking page form
*/
jQuery(document).on("change", "#wc_ast_trackship_page_id", function(){
	'use strict';
	var wc_ast_trackship_page_id = jQuery(this).val();
	if ( wc_ast_trackship_page_id === 'other' ) {
		jQuery('.trackship_other_page_fieldset').show();
	} else{
		jQuery('.trackship_other_page_fieldset').hide();
	}
});

jQuery(document).on("change", "#wc_ast_trackship_page_id", function(){
	save_tracking_page_form();
});

jQuery(document).on( "input", "#wc_ast_trackship_other_page", function(){	
	save_tracking_page_form();
});

function save_tracking_page_form(){
	jQuery("#trackship_tracking_page_form").block({
		message: null,
		overlayCSS: {
			background: "#fff",
			opacity: .6
		}	
    });	
	var form = jQuery('#trackship_tracking_page_form');
	jQuery.ajax({
		url: ajaxurl,		
		data: form.serialize(),		
		type: 'POST',		
		success: function(response) {
			jQuery("#trackship_tracking_page_form").unblock();			
			jQuery(document).zorem_snackbar( trackship_script.i18n.data_saved );
		},
		error: function(response) {
			console.log(response);			
		}
	});
	return false;
}

jQuery(document).on("click", ".open_ts_video", function(){
	jQuery('.ts_video_popup').show();	 
});

jQuery(document).on("click", ".ts_video_popup .popupclose", function(){
	jQuery('#ts_video').each(function(index) {
		jQuery(this).attr('src', jQuery(this).attr('src'));
		return false;
    });
	jQuery('.ts_video_popup').hide();
});



jQuery(document).on( "click", ".add_custom_mapping_h3", function(){
	jQuery("#trackship_mapping_form").block({
		message: null,
		overlayCSS: {
			background: "#fff",
			opacity: .6
		}	
    });
	var ajax_data = {
		action: 'add_trackship_mapping_row',		
	};
	jQuery.ajax({
		url: ajaxurl,		
		data: ajax_data,		
		type: 'POST',				
		success: function(response) {
			jQuery( "#trackship_mapping_form" ).unblock();				
			jQuery('.map-provider-table tr:last').after( response.table_row );
			jQuery( '.map-provider-table .select2' ).select2();
		},
		error: function(response) {
			console.log(response);			
		}
	});
	return false;	
});
/*ajax call for settings tab form save*/
jQuery(document).on("submit", "#trackship_mapping_form", function(){
	'use strict';
	var spinner = jQuery(this).find(".spinner").addClass("active");
	var form = jQuery(this);
	jQuery.ajax({
		url: ajaxurl,
		data: form.serialize(),
		type: 'POST',
		dataType:"json",	
		success: function(response) {
			spinner.removeClass("active");
			jQuery(document).zorem_snackbar( trackship_script.i18n.data_saved );
		},
		error: function(response) {
			console.log(response);
			spinner.removeClass("active");
		}
	});
	return false;
});

jQuery(document).on("click", ".remove_custom_maping_row", function(){
	jQuery(this).closest('tr').remove();
});

jQuery(document).on("click", "#tab_trackship_settings", function(){
	jQuery( '.map-provider-table .select2' ).select2();
});

jQuery(document).on("click", ".metabox_get_shipment_status", function(){
	var data = {
		action:		'metabox_get_shipment_status',
		order_id:	woocommerce_admin_meta_boxes.post_id,
		security:	jQuery( '#_wpnonce' ).val()
	}
	jQuery.ajax({
		url: ajaxurl,
		data: data,
		type: 'POST',
		dataType:"json",	
		success: function( response ) {
			if( response.success === true ){
				jQuery(".metabox_get_shipment_status").hide();
				jQuery(".temp-pending_trackship").show();
				jQuery(document).zorem_snackbar( response.data.msg );
			} else {
				jQuery(document).zorem_snackbar_warning( response.data.msg );
			}
		},
		error: function( jqXHR, exception ) {
			var msg = '';
			if (jqXHR.status === 0) {
				msg = 'Not connect.\n Verify Network.';
			} else if (jqXHR.status == 404) {
				msg = 'Requested page not found. [404]';
			} else if (jqXHR.status == 500) {
				msg = 'Internal Server Error [500].';
			} else if (exception === 'parsererror') {
				msg = 'Requested JSON parse failed.';
			} else if (exception === 'timeout') {
				msg = 'Time out error.';
			} else if (exception === 'abort') {
				msg = 'Ajax request aborted.';
			} else if ( jqXHR.responseText === '-1' ) {
				msg = 'Security check fail, please refresh and try again.';
			} else {
				msg = 'Uncaught Error.\n' + jqXHR.responseText;
			}
			jQuery(document).zorem_snackbar_warning( msg );
		}
	});
});

jQuery(document).on( "click", ".open_tracking_details", function(){
	var data = {
		action:		'get_admin_tracking_widget',
		order_id:	jQuery(this).data('orderid'),
		security:	jQuery(this).data('nonce'),
	}
	jQuery.ajax({
		url: ajaxurl,
		data: data,
		type: 'POST',
		success: function(response) {			
			jQuery("#admin_tracking_widget .popuprow").html(response);
			jQuery("#admin_tracking_widget").show();
			jQuery(".woocommerce-help-tip").tipTip();
		},
		error: function( jqXHR, exception ) {
			var msg = '';
			if (jqXHR.status === 0) {
				msg = 'Not connect.\n Verify Network.';
			} else if (jqXHR.status == 404) {
				msg = 'Requested page not found. [404]';
			} else if (jqXHR.status == 500) {
				msg = 'Internal Server Error [500].';
			} else if (exception === 'parsererror') {
				msg = 'Requested JSON parse failed.';
			} else if (exception === 'timeout') {
				msg = 'Time out error.';
			} else if (exception === 'abort') {
				msg = 'Ajax request aborted.';
			} else if ( jqXHR.responseText === '-1' ) {
				msg = 'Security check fail, please refresh and try again.';
			} else {
				msg = 'Uncaught Error.\n' + jqXHR.responseText;
			}
			jQuery(document).zorem_snackbar_warning( msg );
		}
	});
	return false;

});

jQuery(document).on( "click", ".popupclose", function(){
	jQuery(".popupwrapper").hide();
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

/*
* click on tracking_page_link
*/
jQuery(document).on("click", ".copy_tracking_page", function(){
	var text = jQuery(this).data("tracking_page_link");
	copyTextToClipboard(text);
	jQuery(document).zorem_snackbar( 'Tracking link copied to clipboard' );
});

/*
* click on copy_view_order_page
*/
jQuery(document).on("click", ".copy_view_order_page", function(){
	var text = jQuery(this).data("view_order_link");
	copyTextToClipboard(text);
	jQuery(document).zorem_snackbar( 'View Order page link copied to clipboard' );
});

function copyTextToClipboard(text) {
	var textArea = document.createElement("textarea");
	textArea.style.position = 'fixed';
	textArea.style.top = 0;
	textArea.style.left = 0;
	textArea.style.width = '2em';
	textArea.style.height = '2em';
	textArea.style.padding = 0;
	textArea.style.border = 'none';
	textArea.style.outline = 'none';
	textArea.style.boxShadow = 'none';
	textArea.style.background = 'transparent';
	textArea.value = text;
  
	document.body.appendChild(textArea);
	textArea.focus();
	textArea.select();
  
	try {
	  var successful = document.execCommand('copy');
	  var msg = successful ? 'successful' : 'unsuccessful';
	  console.log('Copying text command was ' + msg);
	} catch (err) {
	  console.log('Oops, unable to copy');
	}
	document.body.removeChild(textArea);
}

jQuery(document).on( "submit", '#tswc-licence-form', function(e){
	e.preventDefault();
	var licence_form = jQuery(this);
	var action = licence_form.find(".licence_action").val();
	var data = licence_form.serialize();
	var licence_button = licence_form.find(".licence_submit");
	jQuery('.license_message').html( '' );
	jQuery.ajax({
		type: "POST",
		url : ajaxurl,
		data: data,
		beforeSend: function(){
			licence_button.prop('disabled', true).val('Please wait..');
		},
		success : function(data){
			console.log(data);
			console.log(data.success);
			var btn_value = 'Activate';
			if( data.success === true ){
				if( action === 'tswc_license_activate' ){
					btn_value = 'Deactivate';
					licence_form.find(".licence_action").val( 'tswc_license_deactivate' );
					jQuery('.license-activated').removeClass("hidden");
					jQuery(document).zorem_snackbar( 'Congratulation, your license successful activated' );
				} else {
					licence_form.find(".licence_action").val( 'tswc_license_activate' );
					licence_form.find(".license_key").val( '' );
					jQuery('.license-activated').addClass("hidden");
					jQuery(document).zorem_snackbar( 'Congratulation, your license successful deactivated' );
				}
			} else if( data.success === false ) {
				jQuery('.license_message').html( data.error );
			} else {
				jQuery('.license_message').html( data.message );
			}
			
			licence_button.prop('disabled', false).val(btn_value);
		},
		error: function(data){
			console.log(data);
		}
	});
	
});
