/* zorem_snackbar jquery */
(function( $ ){
	$.fn.zorem_snackbar = function(msg) {
		var zorem_snackbar = $("<div></div>").addClass('zorem_snackbar show_snackbar').text( msg );
		$("body").append(zorem_snackbar);
		
		setTimeout(function(){ zorem_snackbar.remove(); }, 3000);
		
		return this;
	}; 
})( jQuery );

/*ajax call for settings tab form save*/
jQuery(document).on("submit", ".zorem_plugin_setting_tab_form", function(){
	'use strict';
	jQuery(".zorem_plugin_setting_tab_form").block({
		message: null,
		overlayCSS: {
			background: "#fff",
			opacity: 0.6
		}	
	});
	var form = jQuery(this);
	jQuery.ajax({
		url: ajaxurl,
		data: form.serialize(),
		type: 'POST',
		dataType:"json",	
		success: function(response) {
			jQuery(".zorem_plugin_setting_tab_form").unblock();
			jQuery( '.smswoo-top.smswoo-open .smswoo-top-click' ).trigger('click');
			jQuery(document).zorem_snackbar( trackship_script.i18n.data_saved );
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
	
	jQuery(document).zorem_snackbar( clipboard_text + ' is copied to clipboard.' );
});
