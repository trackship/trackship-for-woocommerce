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

function text_contain (state) {
	return 'Preview: ' + state.text;
};

jQuery(document).ready(function(){
    jQuery('.zoremmail-input.color').wpColorPicker();
	jQuery( '#shipmentStatus' ).select2({
		// templateSelection: text_contain,
		minimumResultsForSearch: Infinity
	});

	jQuery( '#email_preview' ).select2({
		templateSelection: text_contain,
		minimumResultsForSearch: Infinity,
		width: '250px'
	});

	var type = jQuery('#customizer_type').val();
	jQuery('#customizer_type').trigger('change');
	if ( 'tracking_page' == type ) {
		jQuery( '.zoremmail-panel-title.tracking_page_panel' ).trigger('click');
	}

	jQuery( ".zoremmail-input.select, .zoremmail-checkbox" ).change( function( event ) {
		jQuery('.zoremmail-layout-content-preview').addClass('customizer-unloading');
		save_customizer_setting();
	});
	
    jQuery( ".zoremmail-layout-content-media .dashicons" ).on( "click", function() {
		jQuery(this).parent().siblings().removeClass('last-checked');
		var width = jQuery(this).parent().data('width');
		var iframeWidth = jQuery(this).parent().data('iframe-width');
		jQuery('#template_container, #template_body').css('width', width);
		jQuery(this).parent().addClass('last-checked');
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
		var res = res.replace(/\n/g,"<br>");
		
		if( str ){			
			jQuery("#tracking_widget_privew").contents().find( 'div#body_content_inner div.shipment_email_content' ).empty();	
			jQuery("#tracking_widget_privew").contents().find( 'div#body_content_inner div.shipment_email_content' ).html(res);
		} else{
			jQuery("#tracking_widget_privew").contents().find( 'div#body_content_inner div.shipment_email_content' ).text('');
		}
	});

	jQuery('#wc_ast_select_border_color').wpColorPicker({
		change: function(e, ui) {
			var color = ui.color.toString();
			jQuery('#widget_form_border_color').val( color );
			jQuery("#tracking_widget_privew").contents().find('.col.tracking-detail' ).css( 'border-color', color );
			jQuery("#tracking_widget_privew").contents().find('body .col.tracking-detail .shipment-header' ).css( 'border-color', color );
			jQuery("#tracking_widget_privew").contents().find('body .col.tracking-detail .trackship_branding, .tracking-detail .heading_panel' ).css( 'border-color', color );
			jQuery("#tracking_widget_privew").contents().find('body .tracking-detail .h4-heading, .tracking-detail .tracking_number_wrap' ).css( 'border-color', color );
			setting_change_trigger();
		}, 	
	});

});

function setting_change_trigger() {	
	jQuery(".woocommerce-save-button").removeAttr("disabled").html('Save');
	jQuery('.zoremmail-back-wordpress-title').addClass('back_to_notice');
}

function change_submenu_item() {
	var shipmentStatus = jQuery('#shipmentStatus').val();
	jQuery( '.all_status_submenu' ).hide();
	jQuery( '.all_status_submenu.' + shipmentStatus + '_sub_menu' ).show();
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
	change_submenu_item();
	if (jQuery(this).next('.zoremmail-menu-contain').hasClass('active')) {
        jQuery(this).next('.zoremmail-menu-contain').removeClass('active');
    } else {
		jQuery('.zoremmail-menu-submenu-title').find('.dashicons').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
		jQuery('.zoremmail-menu-contain').removeClass('active');
		jQuery(this).next('.zoremmail-menu-contain').addClass('active');
	}
});

jQuery( ".text.track_button_Text" ).keyup( function( event ) {
	var str = event.target.value;
	jQuery("#tracking_widget_privew").contents().find( 'div.tracking_index a.track_your_order' ).text(str);
});

jQuery( ".text.form_button_Text" ).keyup( function( event ) {
	var str = event.target.value;
	jQuery("#tracking_widget_privew").contents().find( '.order_track_form div.search_order_form button' ).text(str);
});

jQuery( ".text.shipped_product_label" ).keyup( function( event ) {
	var str = event.target.value;
	jQuery( ".text.shipped_product_label" ).val(str);
	jQuery("#tracking_widget_privew").contents().find( 'h2.shipment_email_shipped_product_label' ).text(str);
});

jQuery( ".text.shipping_address_label" ).keyup( function( event ) {
	var str = event.target.value;
	jQuery( ".text.shipping_address_label" ).val(str);
	jQuery("#tracking_widget_privew").contents().find( 'h2.shipment_email_shipping_address_label' ).text(str);
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
					btn.prop('disabled', false).html('Save');
					jQuery(document).zorem_snackbar_warning( "you don't have permission to save settings." );
				}
			}
		},
		error: function(response, jqXHR, exception) {
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
			jQuery(document).zorem_snackbar_warning( warning_msg );
		}
	});
	return false;
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
			}
		},
		error: function(response) {
			console.log(response);			
		}
	});
}

jQuery(document).on("change", "#shipmentStatus", function(){
	"use strict";
	jQuery('.zoremmail-layout-content-preview').addClass('customizer-unloading');
	var shipmentStatus = jQuery('#shipmentStatus').val();
	var type = jQuery('#customizer_type').val();
	var sPageURL = window.location.href.split('&')[0];
	window.history.pushState("object or string", sPageURL, sPageURL+'&type='+type+'&status='+shipmentStatus);
	
	var tracking_page_iframe_url = trackship_customizer.tracking_iframe_url+'&status='+shipmentStatus;
	var shipment_iframe_url = trackship_customizer.email_iframe_url+'&status='+shipmentStatus;
	jQuery('.tracking_page_panel').attr('data-iframe_url',tracking_page_iframe_url);
	jQuery('.shipment_email_panel').attr('data-iframe_url',shipment_iframe_url);
	
	if ( type === 'tracking_page' ) {
		jQuery('iframe').attr('src', tracking_page_iframe_url);
	} else {
		jQuery('iframe').attr('src', shipment_iframe_url);
	}
	change_submenu_item();
	jQuery( ".tgl-btn-parent span" ).hide();
	jQuery( ".tgl-btn-parent .tgl_"+shipmentStatus ).show();
});

jQuery('iframe').load(function(){
	jQuery('.zoremmail-layout-content-preview').removeClass('customizer-unloading');
	jQuery("#tracking_widget_privew").contents().find( 'div#query-monitor-main' ).css( 'display', 'none');
	jQuery("#tracking_widget_privew").contents().find( 'div.col.tracking-detail' ).css( 'display', 'block');
	jQuery( '.zoremmail-layout-content-media .last-checked .dashicons' ).trigger('click');
	
})

jQuery(document).on("change", "#track_button_border_radius", function(){
	var radius = jQuery( this ).val();
	jQuery("#tracking_widget_privew").contents().find('div.tracking_index a.track_your_order' ).css( 'border-radius', radius+'px' );
});
jQuery(document).on("change", ".track_button_border_radius .slider__value", function(){
	var radius = jQuery( this ).val();
	jQuery( "#track_button_border_radius" ).val(radius).trigger('change');
});

jQuery(document).on("change", "#form_button_border_radius", function(){
	var radius = jQuery( this ).val();
	jQuery("#tracking_widget_privew").contents().find('.order_track_form div.search_order_form button' ).css( 'border-radius', radius+'px' );
});
jQuery(document).on("change", ".form_button_border_radius .slider__value", function(){
	var radius = jQuery( this ).val();
	jQuery( "#form_button_border_radius" ).val(radius).trigger('change');
});

jQuery(document).on("change", "#wc_ast_select_border_radius", function(){
	var radius = jQuery( this ).val();
	jQuery("#tracking_widget_privew").contents().find('.col.tracking-detail' ).css( 'border-radius', radius+'px' );
});
jQuery(document).on("change", ".wc_ast_select_border_radius .slider__value", function(){
	var radius = jQuery( this ).val();
	jQuery( "#wc_ast_select_border_radius" ).val(radius).trigger('change');
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
			jQuery('#widget_form_bg_color').val( color );
			jQuery("#tracking_widget_privew").contents().find('body .col.tracking-detail' ).css( 'background', color );
			setting_change_trigger();
		},
	});

	jQuery('#wc_ast_select_font_color').wpColorPicker({
		change: function(e, ui) {
			var color = ui.color.toString();
			jQuery('#widget_form_font_color').val( color );
			jQuery("#tracking_widget_privew").contents().find('body .tracking-detail .shipment-content, body .tracking-detail .shipment-content h4' ).css( 'color', color );
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
			jQuery("#tracking_widget_privew").contents().find('div.tracking_index.display-table, div.tracking_index .tracking_widget_bottom' ).css( 'border-color', color );
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

	jQuery('#form_button_color').wpColorPicker({
		change: function(e, ui) {
			var color = ui.color.toString();
			jQuery("#tracking_widget_privew").contents().find('.order_track_form div.search_order_form button' ).css( 'background', color );
			setting_change_trigger();
		}, 	
	});

	jQuery('#form_button_text_color').wpColorPicker({
		change: function(e, ui) {
			var color = ui.color.toString();
			jQuery("#tracking_widget_privew").contents().find('.order_track_form div.search_order_form button' ).css( 'color', color );
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

jQuery(document).on("click", ".zoremmail-panel-title", function(){
	jQuery('.header_shipment_status').show();
	jQuery('.zoremmail-layout-content-preview').addClass('customizer-unloading');
	jQuery( ".zoremmail-panel-title, .sub_options_panel, .zoremmail-panels" ).hide();
	var id = jQuery(this).attr('id');
	jQuery('.zoremmail-sub-panels, .zoremmail-sub-panels li.'+id).show();
	jQuery( ".customize-section-back" ).addClass('panels').show();
	jQuery( '.zoremmail-sub-panel-heading.sub_options_panel.'+id ).addClass('open'); //subpanels back

	var label = jQuery(this).data('label');

	// chaneg back div sub heading
	jQuery( '.zoremmail-sub-panel-heading .sub_heading' ).html( label );

	var shipmentStatus = jQuery('#shipmentStatus').val();
	//For open section of perticular panel
	jQuery('.customize-section-title').each(function(index, element) {
		if ( jQuery(this).data('id') ===  id ) {
			jQuery(this).addClass('open');
		} else {
			jQuery(this).removeClass('open');
		}
	});
	
	//For click on first section 
	if ( jQuery('.zoremmail-sub-panel-title:visible').length == 1) {
		jQuery(".zoremmail-sub-panel-title:visible").trigger('click');	
		change_submenu_item();
	}

	//For change url and ifram url
	var sPageURL = window.location.href.split('&')[0];
	if ( 'tracking_page' == id ) {
		jQuery( "#customizer_type" ).val( 'tracking_page' );
		window.history.pushState("object or string", sPageURL, sPageURL+'&type=tracking_page&status='+shipmentStatus);
		var tracking_page_iframe_url = trackship_customizer.tracking_iframe_url+'&status='+shipmentStatus;
		jQuery('iframe').attr('src', tracking_page_iframe_url);
	} else {
		jQuery( "#customizer_type" ).val( 'shipment_email' );
		window.history.pushState("object or string", sPageURL, sPageURL+'&type=shipment_email&status='+shipmentStatus);
		var shipment_iframe_url = trackship_customizer.email_iframe_url+'&status='+shipmentStatus;
		jQuery('iframe').attr('src', shipment_iframe_url);
	}
	jQuery('#customizer_type').trigger('change');
	jQuery( '#email_preview' ).select2({
		templateSelection: text_contain,
		minimumResultsForSearch: Infinity,
		width: '250px'
	});
});

jQuery(document).on("change", "#customizer_type", function(){
	var val = jQuery( "#customizer_type" ).val();
	if ( val == 'shipment_email' ) {
		jQuery('.header_mockup_order').show();
	} else {
		jQuery('.header_mockup_order').hide();
	}
});

jQuery(document).on("click", ".zoremmail-sub-panel-title", function(){
	
	var type = jQuery(this).data('type');
	var label = jQuery(this).data('label');
	var id = jQuery(this).attr('id');
	var shipmentStatus = jQuery('#shipmentStatus').val();
	jQuery('.zoremmail-sub-panel-title').hide();
	jQuery('.customize-action-default').hide(); // hide default back heading
	jQuery('.customize-action-changed').show(); // Show back chanegd heading
	jQuery('.zoremmail-sub-panel-heading.'+type).show();
	jQuery( ".customize-section-back" ).removeClass('panels').addClass('sub-panels').show();

	var parent_label = jQuery('.zoremmail-panels #'+type).data('label');
	jQuery( '.zoremmail-sub-panel-heading.'+ type +' .customize-action-changed' ).html( 'TrackShip <span class="dashicons dashicons-arrow-right-alt2"></span> '+parent_label );
	jQuery( '.zoremmail-sub-panel-heading.'+ type +' .sub_heading' ).html( label );

	jQuery('.zoremmail-menu-submenu-title').each(function(index, element) {
		if ( jQuery(this).data('id') ===  id ) {
			jQuery(this).addClass('open');
			jQuery(this).next('.zoremmail-menu-contain').addClass('active');
		} else {
			jQuery(this).removeClass('open');
			jQuery(this).next('.zoremmail-menu-contain').removeClass('active');
		}
	});
	if ( 'tracking_page' == type ) {
		jQuery('.zoremmail-layout-content-preview').addClass('customizer-unloading');
		if ( 'form_content' == id ) {
			jQuery('iframe').attr('src', trackship_customizer.form_iframe_url);
		} else {
			var tracking_page_iframe_url = trackship_customizer.tracking_iframe_url+'&status='+shipmentStatus;
			jQuery('iframe').attr('src', tracking_page_iframe_url);
		}
	}
	jQuery( '#shipmentStatus' ).select2({
		// templateSelection: text_contain,
		minimumResultsForSearch: Infinity
	});
	change_submenu_item();
});

jQuery(document).on("click", ".customize-section-back", function(){
	if ( jQuery(this).hasClass('panels') ) {
		jQuery('.sub_options_panel').hide();
		jQuery( ".customize-section-back, .customize-action-changed" ).hide();
		jQuery( ".zoremmail-panel-title, .zoremmail-layout-sider-heading .trackship_logo, .customize-action-default" ).show();
		jQuery( ".zoremmail-panels" ).show();
		jQuery('.zoremmail-sub-panel-heading').removeClass('open');
		jQuery('.zoremmail-sub-panel-heading').removeClass('active');
	}
	if ( jQuery(this).hasClass('sub-panels') ) {
		jQuery( ".customize-section-back" ).removeClass('sub-panels').addClass('panels');
		jQuery( ".zoremmail-sub-panels" ).show();

		var parent = jQuery(this).parents('.zoremmail-sub-panel-heading'); 
		
		// if ( parent.hasClass( 'email_notifications' ) ) {
		// 	jQuery(this).trigger('click');
		// } else {
			var id = parent.data('id');
			jQuery('.customize-action-changed').hide();
			jQuery('.customize-action-default').show();
			var parent_label = jQuery('.zoremmail-panels #'+id).data('label');
			jQuery( '.zoremmail-sub-panel-heading .sub_heading' ).html( parent_label );
			jQuery('.zoremmail-sub-panel-title.'+id).show();
		// }
	}
	jQuery('.zoremmail-menu-contain').removeClass('active');
	jQuery('.zoremmail-menu-submenu-title').removeClass('open');
});

jQuery('#email_preview').on("change", function(){
	jQuery('.zoremmail-layout-content-preview').addClass('customizer-unloading');
	save_customizer_setting();
});
