/* zorem_snackbar jquery */
(function( $ ){
	$.fn.zorem_snackbar = function(msg) {
		if ( jQuery('.snackbar-logs').length === 0 ){
			$("body").append("<section class=snackbar-logs></section>");
		}
		var zorem_snackbar = $("<article></article>").addClass('snackbar-log snackbar-log-success snackbar-log-show').text( msg );
		$(".snackbar-logs").append(zorem_snackbar);
		setTimeout(function(){ zorem_snackbar.remove(); }, 3000);
		return this;
	}; 
})( jQuery );

/* zorem_snackbar_warning jquery */
(function( $ ){
	$.fn.zorem_snackbar_warning = function(msg) {
		if ( jQuery('.snackbar-logs').length === 0 ){
			$("body").append("<section class=snackbar-logs></section>");
		}
		var zorem_snackbar_warning = $("<article></article>").addClass( 'snackbar-log snackbar-log-error snackbar-log-show' ).html( msg );
		$(".snackbar-logs").append(zorem_snackbar_warning);
		setTimeout(function(){ zorem_snackbar_warning.remove(); }, 3000);
		return this;
	}; 
})( jQuery );

jQuery(document).ready(function(){
    jQuery('.zoremmail-input.color').wpColorPicker();
	
	jQuery( ".zoremmail-input.select, .zoremmail-checkbox" ).change( function( event ) {
		jQuery('.zoremmail-layout-content-preview').addClass('customizer-unloading');
		save_customizer_setting();
	});
	
    jQuery( ".zoremmail-layout-content-media .dashicons" ).on( "click", function() {
		var width = jQuery(this).parent().data('width');
		var iframeWidth = jQuery(this).parent().data('iframe-width');
		jQuery('#template_container, #template_body').css('width', width);
		jQuery( ".zoremmail-layout-content-media .dashicons" ).css('color', '#bdbdbd');
		jQuery(this).css('color', '#09d3ac');
		jQuery("#tracking_widget_privew").css('width', iframeWidth);
		jQuery("#tracking_widget_privew").contents().find('#template_container, #template_body, #template_footer').css('width', width);
	});

	jQuery( ".zoremmail-input.heading" ).keyup( function( event ) {
		var str = event.target.value;
		var res = str.replace("{site_title}", trackship_customizer.site_title);
		var res = res.replace("{order_number}", trackship_customizer.order_number);
		var res = res.replace("{customer_first_name}", trackship_customizer.customer_first_name);
		var res = res.replace("{customer_last_name}", trackship_customizer.customer_last_name);
		var res = res.replace("{customer_company_name}", trackship_customizer.customer_company_name);
		var res = res.replace("{customer_username}", trackship_customizer.customer_username);
		var res = res.replace("{customer_email}", trackship_customizer.customer_email);
		var res = res.replace("{est_delivery_date}", trackship_customizer.est_delivery_date);
		if( str ){				
			jQuery("#tracking_widget_privew").contents().find( '#header_wrapper h1' ).text(res);
		} else{
			jQuery("#tracking_widget_privew").contents().find( '#header_wrapper h1' ).text('');
		}
	});

	jQuery( ".zoremmail-input.email_content" ).keyup( function( event ) {
		var str = event.target.value;
		var res = str.replace("{site_title}", trackship_customizer.site_title);
		var res = res.replace("{order_number}", trackship_customizer.order_number);
		var res = res.replace("{customer_first_name}", trackship_customizer.customer_first_name);
		var res = res.replace("{customer_last_name}", trackship_customizer.customer_last_name);
		var res = res.replace("{customer_company_name}", trackship_customizer.customer_company_name);
		var res = res.replace("{customer_username}", trackship_customizer.customer_username);
		var res = res.replace("{customer_email}", trackship_customizer.customer_email);
		var res = res.replace("{est_delivery_date}", trackship_customizer.est_delivery_date);
		
		if( str ){				
			jQuery("#tracking_widget_privew").contents().find( 'div#body_content_inner p.shipment_email_content' ).text(res);
		} else{
			jQuery("#tracking_widget_privew").contents().find( 'div#body_content_inner p.shipment_email_content' ).text('');
		}
	});
});

function setting_change_trigger() {	
	jQuery(".woocommerce-save-button").removeAttr("disabled").html('Save Changes');
	jQuery('.zoremmail-back-wordpress-title').addClass('back_to_notice');
}

jQuery(document).on("click", ".back_to_notice", function(){
	var r = confirm( 'The changes you made will be lost if you navigate away from this page.' );
	if (r === true ) {
	} else {	
		return false;
	}
});

jQuery(document).on("change", ".tgl.tgl-flat, .zoremmail-checkbox, .zoremmail-input.color, .zoremmail-range, .zoremmail-input.select", function(){
	setting_change_trigger();
});

jQuery( ".zoremmail-input.text, .zoremmail-input.textarea" ).keyup( function( event ) {
	setting_change_trigger();
});

jQuery(document).on("click", ".zoremmail-menu-submenu-title", function(){
	if (jQuery(this).next('.zoremmail-menu-contain').hasClass('active')) {
        jQuery(this).next('.zoremmail-menu-contain').removeClass('active');
		jQuery(this).find('.dashicons').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
		jQuery(this).css('color', '#124fd6');
    } else {
		jQuery('.zoremmail-menu-submenu-title').find('.dashicons').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
		jQuery('.zoremmail-menu-contain').removeClass('active');
		jQuery(this).next('.zoremmail-menu-contain').addClass('active');
		jQuery(this).find('.dashicons').removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
		jQuery('.zoremmail-menu-submenu-title').css('color', '#124fd6');
		jQuery(this).css('color', '#212121');
	}	
});

jQuery( "#track_button_Text" ).keyup( function( event ) {
	var str = event.target.value;
	jQuery("#tracking_widget_privew").contents().find( 'div.tracking_index a.track_your_order' ).text(str);
});

jQuery(document).on("click", "#zoremmail_email_options .button-trackship", function(){
    "use strict";
	var form = jQuery('#zoremmail_email_options');
	var btn = jQuery('#zoremmail_email_options .button-trackship');
    jQuery.ajax({
		url: ajaxurl,//csv_workflow_update,		
		data: form.serialize(),
		type: 'POST',
		dataType:"json",
		beforeSend: function(){
			btn.prop('disabled', true).html('Please wait..');
		},		
		success: function(response) {
			if( response.success === "true" ){
				btn.prop('disabled', true).html('Saved');
				jQuery(document).zorem_snackbar( "Settings Successfully Saved." );
				jQuery('iframe').attr('src', jQuery('iframe').attr('src'));
				jQuery('.button-trackship .woocommerce-save-button').attr("disabled");
				jQuery('.zoremmail-back-wordpress-title').removeClass('back_to_notice');
			} else {
				if( response.permission === "false" ){
					btn.prop('disabled', false).html('Save Changes');
					jQuery(document).zorem_snackbar_warning( "you don't have permission to save settings." );
				}
			}
		},
		error: function(response) {
            jQuery(document).zorem_snackbar_warning( "settings not saved." );
			console.log(response);			
		}
	});
});

function save_customizer_setting(){
	var form = jQuery('#zoremmail_email_options');
	jQuery.ajax({
		url: ajaxurl,//csv_workflow_update,		
		data: form.serialize(),
		type: 'POST',
		dataType:"json",		
		success: function(response) {
			if( response.success === "true" ){
				jQuery('iframe').attr('src', jQuery('iframe').attr('src'));
				//jQuery('.zoremmail-layout-content-preview').removeClass('customizer-unloading');
			} else {
				//
			}
		},
		error: function(response) {
			console.log(response);			
		}
	});
}

jQuery(document).on("change", "#shipmentStatus, #customizer_type", function(){
	"use strict";
	jQuery('.zoremmail-layout-content-preview').addClass('customizer-unloading');
	var shipmentStatus = jQuery('#shipmentStatus').val();
	var type = jQuery('#customizer_type').val();
	var sPageURL = window.location.href.split('&')[0];
	//console.log(sPageURL);
	window.history.pushState("object or string", sPageURL, sPageURL+'&type='+type+'&status='+shipmentStatus);
	if ( type === 'tracking_page' ) {
		var iframe_url = jQuery('iframe').attr('src');
		iframe_url = iframe_url+'&type='+type+'&status='+shipmentStatus;
		jQuery('iframe').attr('src', iframe_url);
	} else {
		location.reload();
	}
});

jQuery('iframe').load(function(){
	jQuery('.zoremmail-layout-content-preview').removeClass('customizer-unloading');
	jQuery("#tracking_widget_privew").contents().find( 'div#query-monitor-main' ).css( 'display', 'none');
})

jQuery(document).on("change", "#customizer_type", function(){
	location.reload();
});

jQuery(document).on("click", ".radio-button-label input", function(){
	if( jQuery( this ).val() == 15 ) {
		jQuery("#tracking_widget_privew").contents().find( 'a.track_your_order' ).css( 'padding', '10px 15px');
	} else {
		jQuery("#tracking_widget_privew").contents().find( 'a.track_your_order' ).css( 'padding', '12px 20px');
	}
	setting_change_trigger();
});

jQuery(document).on("change", "#track_button_border_radius", function(){
	var radius = jQuery( this ).val();
	jQuery("#tracking_widget_privew").contents().find('div.tracking_index a.track_your_order' ).css( 'border-radius', radius+'px' );
});
jQuery(document).on("change", ".track_button_border_radius .slider__value", function(){
	var radius = jQuery( this ).val();
	jQuery( "#track_button_border_radius" ).val(radius).trigger('change');
});

jQuery(document).on("change", "#widget_padding", function(){
	var padding = jQuery( this ).val();
	jQuery("#tracking_widget_privew").contents().find('div.tracking_index.display-table' ).css( 'padding', padding );
});
jQuery(document).on("change", ".widget_padding .slider__value", function(){
	var padding = jQuery( this ).val();
	jQuery( "#widget_padding" ).val(padding).trigger('change');
});

jQuery(document).on("change", "#wc_ast_select_widget_padding", function(){
	var padding = jQuery( this ).val();
	jQuery("#tracking_widget_privew").contents().find('body .col.tracking-detail' ).css( 'padding', padding );
});
jQuery(document).on("change", ".wc_ast_select_widget_padding .slider__value", function(){
	var padding = jQuery( this ).val();
	jQuery( "#wc_ast_select_widget_padding" ).val(padding).trigger('change');
});


if ( jQuery.fn.wpColorPicker ) {
	jQuery('#wc_ast_select_bg_color').wpColorPicker({
		change: function(e, ui) {
			var color = ui.color.toString();
			jQuery("#tracking_widget_privew").contents().find('body .col.tracking-detail' ).css( 'background', color );
			setting_change_trigger();
		}, 	
	});

	jQuery('#wc_ast_select_font_color').wpColorPicker({
		change: function(e, ui) {
			var color = ui.color.toString();
			jQuery("#tracking_widget_privew").contents().find('body .tracking-detail .shipment-content, body .tracking-detail .shipment-content h4' ).css( 'color', color );
			setting_change_trigger();
		}, 	
	});

	jQuery('#wc_ast_select_border_color').wpColorPicker({
		change: function(e, ui) {
			var color = ui.color.toString();
			jQuery("#tracking_widget_privew").contents().find('.col.tracking-detail' ).css( 'border-color', color );
			jQuery("#tracking_widget_privew").contents().find('body .col.tracking-detail .shipment-header' ).css( 'border-color', color );
			jQuery("#tracking_widget_privew").contents().find('body .col.tracking-detail .trackship_branding' ).css( 'border-color', color );
			jQuery("#tracking_widget_privew").contents().find('body .tracking-detail .h4-heading' ).css( 'border-color', color );
			setting_change_trigger();
		}, 	
	});

	jQuery('#wc_ast_select_link_color').wpColorPicker({
		change: function(e, ui) {
			var color = ui.color.toString();
			jQuery("#tracking_widget_privew").contents().find('.col.tracking-detail .tracking_number_wrap a' ).css( 'color', color );
			setting_change_trigger();
		}, 	
	});

	jQuery('#bg_color').wpColorPicker({
		change: function(e, ui) {
			var color = ui.color.toString();
			jQuery("#tracking_widget_privew").contents().find('div.tracking_index.display-table' ).css( 'background', color );
			setting_change_trigger();
		}, 	
	});

	jQuery('#font_color').wpColorPicker({
		change: function(e, ui) {
			var color = ui.color.toString();
			jQuery("#tracking_widget_privew").contents().find('div.tracking_index.display-table' ).css( 'color', color );
			setting_change_trigger();
		}, 	
	});

	jQuery('#border_color').wpColorPicker({
		change: function(e, ui) {
			var color = ui.color.toString();
			jQuery("#tracking_widget_privew").contents().find('div.tracking_index.display-table' ).css( 'border-color', color );
			setting_change_trigger();
		}, 	
	});

	jQuery('#link_color').wpColorPicker({
		change: function(e, ui) {
			var color = ui.color.toString();
			jQuery("#tracking_widget_privew").contents().find('div.tracking_index.display-table .tracking_info a' ).css( 'color', color );
			setting_change_trigger();
		}, 	
	});

	jQuery('#track_button_color').wpColorPicker({
		change: function(e, ui) {
			var color = ui.color.toString();
			jQuery("#tracking_widget_privew").contents().find('div.tracking_index a.track_your_order' ).css( 'background', color );
			setting_change_trigger();
		}, 	
	});

	jQuery('#track_button_text_color').wpColorPicker({
		change: function(e, ui) {
			var color = ui.color.toString();
			jQuery("#tracking_widget_privew").contents().find('div.tracking_index a.track_your_order' ).css( 'color', color );
			setting_change_trigger();
		}, 	
	});
}