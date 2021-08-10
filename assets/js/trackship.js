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
				$(document).trackship_snackbar( trackship_script.i18n.data_saved );
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
			email_address = document.getElementById("wcast_late_shipments_email_to").value;
			if (email_address === "") {
				alert("Please fill the email address");
				return false;
			}
			
			$("#trackship_late_shipments_form").find(".spinner").addClass("active");			
			var ajax_data = $("#trackship_late_shipments_form").serialize();
			
			$.post( ajaxurl, ajax_data, function(response) {
				$("#trackship_late_shipments_form").find(".spinner").removeClass("active");
				jQuery(document).trackship_snackbar( trackship_script.i18n.data_saved );
			});			
		},	
	};
	$(window).load(function(e) {		
        trackship_js.init();
    });

})( jQuery, trackship_script, wp, ajaxurl );

jQuery( document ).ready(function() {
	
	jQuery(".trackship-tip").tipTip();
	
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
				jQuery('.ts4wc_delivered_color .order-label.wc-delivered').css('background',color);			
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

jQuery(document).on("click", ".trackship_admin_content .trackship_nav_div .tab_input", function(){
	var tab = jQuery(this).data('tab');
	var label = jQuery(this).data('label');
	jQuery('.zorem-layout__header-breadcrumbs .header-breadcrumbs-last').text(label);
	var url = window.location.protocol + "//" + window.location.host + window.location.pathname+"?page=trackship-for-woocommerce&tab="+tab;
	window.history.pushState({path:url},'',url);
	jQuery(window).trigger('resize');	
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

jQuery(document).on("click", "#wc_ast_status_label_font_color", function(){	
	var value = jQuery('#wc_ast_status_label_font_color').val();
	jQuery(".order-label.wc-delivered").css("color", value);
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

jQuery(document).on("click", ".trackship-notice .bulk_shipment_status_button", function(){
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
			jQuery( '.trackship-notice .bulk_shipment_status_button' ).attr("disabled", true);
			jQuery(document).trackship_snackbar( 'Tracking info sent to Trackship for all Orders.' );
		},
		error: function(response) {
			console.log(response);			
		}
	});
	return false;
});

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
			jQuery(document).trackship_snackbar( trackship_script.i18n.data_saved );
		},
		error: function(response) {					
			jQuery(document).trackship_snackbar_warning( "Settingd not saved" );
		}
	});
});

/*ajex call for general tab form save*/	 
jQuery(document).on("change", "#all-shipment-status-delivered", function(){
	"use strict";
	
	if(jQuery(this).prop("checked") == true){
		var checked = 1;		
	} else {
		var checked = 0;		
	}
	
	var ajax_data = {
		action: 'update_all_shipment_status_delivered',
		shipment_status_delivered: checked,
		security: jQuery( '#all_status_delivered' ).val()
	};
	
	jQuery.ajax({
		url: ajaxurl,
		data: ajax_data,		
		type: 'POST',
		success: function(response) {	
			jQuery(document).trackship_snackbar( trackship_script.i18n.data_saved );
		},
		error: function(response) {
			console.log(response);
			jQuery(document).trackship_snackbar_warning( trackship_script.i18n.data_saved );		
		}
	});
	return false;
});

jQuery(document).on("click", ".late_shipments_a", function(){
	jQuery('.late-shipments-email-content-table').toggle();
});

jQuery(document).on("change", ".ts_order_status_toggle", function(){
	
	if(jQuery(this).prop("checked") == true){
		jQuery('.ts4wc_delivered_color').fadeIn();
	} else{
		jQuery('.ts4wc_delivered_color').fadeOut();
	}
	
});

jQuery(document).on("change", "#wc_ast_use_tracking_page", function(){
	
	if(jQuery(this).prop("checked") == true){
		jQuery('.li_wc_ast_trackship_page_id').fadeIn();
	} else{
		jQuery('.li_wc_ast_trackship_page_id').fadeOut();
	}
	
});

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
	jQuery("#wc_ast_use_tracking_page").trigger("change");
	jQuery(".ts_order_status_toggle").trigger("change");
});

/* trackship_snackbar jquery */
(function( $ ){
	$.fn.trackship_snackbar = function(msg) {
		if ( jQuery('.snackbar-logs').length === 0 ){
			$("body").append("<section class=snackbar-logs></section>");
		}
		var trackship_snackbar = $("<article></article>").addClass('snackbar-log snackbar-log-success snackbar-log-show').text( msg );
		$(".snackbar-logs").empty();
		$(".snackbar-logs").append(trackship_snackbar);
		setTimeout(function(){ trackship_snackbar.remove(); }, 3000);
		return this;
	}; 
})( jQuery );

/* trackship_snackbar_warning jquery */
(function( $ ){
	$.fn.trackship_snackbar_warning = function(msg) {
		if ( jQuery('.snackbar-logs').length === 0 ){
			$("body").append("<section class=snackbar-logs></section>");
		}
		var trackship_snackbar_warning = $("<article></article>").addClass( 'snackbar-log snackbar-log-error snackbar-log-show' ).html( msg );
		$(".snackbar-logs").empty();
		$(".snackbar-logs").append(trackship_snackbar_warning);
		setTimeout(function(){ trackship_snackbar_warning.remove(); }, 3000);
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
	save_tracking_page_form();
});

jQuery(document).on( "input", "#wc_ast_trackship_other_page", function(){	
	save_tracking_page_form();
});

jQuery(document).on( "click", "#trackship_tracking_page_form button", function(){
	save_tracking_page_form();
	return false;
});

function save_tracking_page_form(){
	var spinner = jQuery('#trackship_tracking_page_form').find(".spinner").addClass("active");
	var form = jQuery('#trackship_tracking_page_form');
	jQuery.ajax({
		url: ajaxurl,		
		data: form.serialize(),		
		type: 'POST',		
		success: function(response) {
			spinner.removeClass("active");
			jQuery(document).trackship_snackbar( trackship_script.i18n.data_saved );
		},
		error: function(response) {
			spinner.removeClass("active");
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
	var spinner = jQuery('#trackship_mapping_form').find(".add-custom-mapping.spinner").addClass("active");
	var ajax_data = {
		action: 'add_trackship_mapping_row',		
	};
	jQuery.ajax({
		url: ajaxurl,		
		data: ajax_data,		
		type: 'POST',				
		success: function(response) {
			jQuery('.map-provider-table tr:last').after( response.table_row );
			jQuery( '.map-provider-table .select2' ).select2();
			spinner.removeClass("active");
		},
		error: function(response) {
			console.log(response);	
			spinner.removeClass("active");		
		}
	});
	return false;	
});
/*ajax call for settings tab form save*/
jQuery(document).on("submit", "#trackship_mapping_form", function(){
	'use strict';
	var spinner = jQuery(this).find(".mapping-save.spinner").addClass("active");
	var form = jQuery(this);
	jQuery.ajax({
		url: ajaxurl,
		data: form.serialize(),
		type: 'POST',
		dataType:"json",	
		success: function(response) {
			spinner.removeClass("active");
			jQuery(document).trackship_snackbar( trackship_script.i18n.data_saved );
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

jQuery(document).on("click", "#tab_trackship_settings, #tab_trackship_map-providers", function(){
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
				jQuery(document).trackship_snackbar( response.data.msg );
			} else {
				jQuery(document).trackship_snackbar_warning( response.data.msg );
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
			jQuery(document).trackship_snackbar_warning( msg );
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
			jQuery(".trackship-tip").tipTip();
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
			jQuery(document).trackship_snackbar_warning( msg );
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
	jQuery(document).trackship_snackbar( 'Tracking link copied to clipboard' );
});

/*
* click on tracking number from dashboard
*/
jQuery(document).on("click", ".copied_tracking_numnber", function(){
	var text = jQuery(this).data("number");
	copyTextToClipboard(text);
	jQuery(document).trackship_snackbar( 'Tracking number copied to clipboard' );
});

/*
* click on copy_view_order_page
*/
jQuery(document).on("click", ".copy_view_order_page", function(){
	var text = jQuery(this).data("view_order_link");
	copyTextToClipboard(text);
	jQuery(document).trackship_snackbar( 'View Order page link copied to clipboard' );
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

jQuery(document).on( "click", ".tracking-event-delete-notice .bulk_shipment_status_button", function(){
	var days = jQuery( "#delete_time" ).val();
	var ajax_data = {
		action: 'remove_tracking_event',
		days: days,
		security: jQuery( '#wc_ast_tools' ).val()
	};
	jQuery.ajax({
		url: ajaxurl,		
		data: ajax_data,		
		type: 'POST',
		dataType:"json",				
		success: function(response) {
			jQuery(document).trackship_snackbar( 'Tracking event deleted for ' + response.order_count +' orders out of ' + response.found_orders + ' orders' );
		},
		error: function(response) {
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
			} else if ( jqXHR.responseText === '-1' ) {
				msg = 'Security check fail, please refresh and try again.';
			}else {
				warning_msg = 'Uncaught Error.\n' + jqXHR.responseText;
			}
			
			jQuery(document).trackship_snackbar_warning( warning_msg );
			console.log(response);	
		}
	});
	return false;	
});

jQuery(document).on( "click", ".tools_tab_ts4wc .remove-icon.dashicons-no-alt", function(){
	var date = new Date();
	date.setTime(date.getTime() + (30*24*60*60*1000));
	expires = "; expires=" + date.toUTCString();
	var cookies = document.cookie = "Notice=delete " + expires ;
	jQuery('#content_trackship_settings .tools_tab_ts4wc').hide();
	console.log(cookies);
});

/*
* click on more info
*/
jQuery(document).on("click", ".open_more_info_popup", function(){
	jQuery("#admin_error_more_info_widget").show();
	return false;
});