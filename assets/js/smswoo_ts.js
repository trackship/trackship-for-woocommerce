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

/*ajax call for settings tab form save*/
jQuery(document).on("click", ".zorem_plugin_setting_tab_form .woocommerce-save-button", function(){
	'use strict';
	var form = jQuery( '.zorem_plugin_setting_tab_form' );
	form.find(".spinner").addClass("active");
	jQuery.ajax({
		url: ajaxurl,
		data: form.serialize(),
		type: 'POST',
		dataType:"json",	
		success: function(response) {
			form.find(".spinner").removeClass("active");
			jQuery( '.smswoo-top.smswoo-open .smswoo-top-click' ).trigger('click');
			jQuery(document).trackship_snackbar( trackship_script.i18n.data_saved );
			jQuery( '.heading_panel' ).removeClass( 'active' );
			jQuery( '.heading_panel' ).siblings( '.panel_content' ).removeClass('active').slideUp( 'slow' );
			jQuery( '.heading_panel' ).find('span.dashicons').addClass('dashicons-arrow-right-alt2');
			jQuery( '.heading_panel' ).find('button.button-primary').hide();
		},
		error: function(response) {
			console.log(response);
		}
	});
	return false;
});

/*ajax call for settings tab form toggle*/
jQuery(document).on("change", ".zorem_plugin_setting_tab_form .tgl-flat", function(){
	'use strict';
	jQuery(this).parents('form').submit();
});

/** show/ hide event **/
jQuery(document).on( "click", ".shipment-status-sms-section .smswoo-top-click", function(){
	'use strict';
	var smswootop = jQuery(this).parents(".smswoo-top");
	var smswoobottom = smswootop.siblings(".smswoo-bottom");
	var smssavebtn = smswootop.find(".button-smswoo");
	var smscustomizebtn = smswootop.find(".smswoo-shipment-sendto-customer");
	
	if ( smswootop.hasClass( 'smswoo-open' ) ) {
	} else {
		jQuery(".smswoo-bottom").slideUp(400);
	}
	jQuery(".button-smswoo").hide();
	jQuery(".smswoo-shipment-sendto-customer").show();
	jQuery(".smswoo-top").removeClass('smswoo-open');
	
	smswoobottom.slideToggle( 400, "swing", function(){
		if( smswoobottom.is(":visible") ){
			smswootop.addClass('smswoo-open');
			smssavebtn.show();
			smscustomizebtn.hide();
		} else {
			smswootop.removeClass('smswoo-open');
			smssavebtn.hide();
			smscustomizebtn.show();
		}
	});
});

jQuery(document).on( "change", ".smswoo-checkbox", function(){
	'use strict';
	if( jQuery(this).prop("checked") === true ){
		jQuery(this).closest('.smswoo-row').removeClass('disable_row');
	} else {
		jQuery(this).closest('.smswoo-row').addClass('disable_row');
	}
});

jQuery(document).on( "change", ".smswoo-shipment-checkbox", function(){
	'use strict';
	var row_class = jQuery(this).data( "row_class" );
	
	if( jQuery(this).prop("checked") === true ){
		jQuery(this).closest('.smswoo-row').addClass( row_class );
	} else {
		jQuery(this).closest('.smswoo-row').removeClass( row_class );
	}
});

function copyToClipboard(text) {
	var $temp = jQuery("<input>");
	jQuery("body").append($temp);
	$temp.val(text).select();
	document.execCommand("copy");
	$temp.remove();
}

jQuery(document).on( "click", ".shipment-status-sms-section .clipboard", function(){
	'use strict';
	var clipboard_text = jQuery(this).data( "clipboard-text" );
	copyToClipboard( clipboard_text );
	
	jQuery(".clipboard").removeClass("active");
	jQuery(this).addClass("active");
	
	jQuery(document).trackship_snackbar( clipboard_text + ' is copied to clipboard.' );
});
