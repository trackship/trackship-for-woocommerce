(function ($, data, wp, ajaxurl) {

	var trackship_js = {

		init: function () {

			$("#wc_ast_trackship_form").on('click', '.woocommerce-save-button', this.save_wc_ast_trackship_form);
			$("#trackship_tracking_page_form").on('click', '.woocommerce-save-button', this.save_trackship_tracking_page_form);
			$("#trackship_mapping_form").on('click', '.woocommerce-save-button', this.save_trackship_mapping_form);

			$("#trackship_late_shipments_form").on('click', '.woocommerce-save-button', this.save_trackship_late_shipments_form);
			$(".tipTip").tipTip();

		},

		save_wc_ast_trackship_form: function (event) {
			event.preventDefault();

			$("#wc_ast_trackship_form").find(".spinner").addClass("active");
			var ajax_data = $("#wc_ast_trackship_form").serialize();

			$.post(ajaxurl, ajax_data, function (response) {
				$("#wc_ast_trackship_form").find(".spinner").removeClass("active");
				$(document).trackship_snackbar(trackship_script.i18n.data_saved);
				jQuery('.heading_panel').removeClass('active');
				jQuery('.heading_panel').siblings('.panel_content').removeClass('active').slideUp('slow');
				jQuery('.heading_panel').find('span.dashicons').addClass('dashicons-arrow-right-alt2');
				jQuery('.heading_panel').find('button.button-primary').hide();
			});
			return false;
		},

		save_trackship_tracking_page_form: function (event) {
			event.preventDefault();

			$("#trackship_tracking_page_form").find(".spinner").addClass("active");
			var ajax_data = $("#trackship_tracking_page_form").serialize();

			$.post(ajaxurl, ajax_data, function (response) {
				$("#trackship_tracking_page_form").find(".spinner").removeClass("active");
				$(document).trackship_snackbar(trackship_script.i18n.data_saved);
				jQuery('.heading_panel').removeClass('active');
				jQuery('.heading_panel').siblings('.panel_content').removeClass('active').slideUp('slow');
				jQuery('.heading_panel').find('span.dashicons').addClass('dashicons-arrow-right-alt2');
				jQuery('.heading_panel').find('button.button-primary').hide();
			});
			return false;
		},

		save_trackship_mapping_form: function (event) {
			event.preventDefault();

			$("#trackship_mapping_form").find(".heading_panel .spinner").addClass("active");
			var ajax_data = $("#trackship_mapping_form").serialize();

			$.post(ajaxurl, ajax_data, function (response) {
				$("#trackship_mapping_form").find(".spinner").removeClass("active");
				$(document).trackship_snackbar(trackship_script.i18n.data_saved);
				jQuery('.heading_panel').removeClass('active');
				jQuery('.heading_panel').siblings('.panel_content').removeClass('active').slideUp('slow');
				jQuery('.heading_panel').find('span.dashicons').addClass('dashicons-arrow-right-alt2');
				jQuery('.heading_panel').find('button.button-primary').hide();
			});
			return false;
		},

		save_trackship_late_shipments_form: function (event) {
			event.preventDefault();
			email_address = document.getElementById("wcast_late_shipments_email_to").value;
			if (email_address === "") {
				alert("Please fill the email address");
				return false;
			}

			$("#trackship_late_shipments_form").find(".spinner").addClass("active").slideDown('slow');
			var ajax_data = $("#trackship_late_shipments_form").serialize();

			$.post(ajaxurl, ajax_data, function (response) {
				$("#trackship_late_shipments_form").find(".spinner").removeClass("active").slideUp('slow');
				jQuery(document).trackship_snackbar(trackship_script.i18n.data_saved);
				jQuery('.late-shipment-tr').removeClass('open');
				jQuery('.late-shipments-email-content-table').slideUp('slow');
			});
			return false;
		},
	};
	$(window).on('load', function () {
		trackship_js.init();
	});

})(jQuery, trackship_script, wp, ajaxurl);

jQuery(document).ready(function () {

	jQuery(".trackship-tip").tipTip();
	jQuery(".ts-custom-tool-tip").tipTip({
		defaultPosition: "left",
	});
	
	if (jQuery.fn.wpColorPicker) {

		jQuery('#wc_ast_select_border_color').wpColorPicker({
			change: function (e, ui) {
				var color = ui.color.toString();
				jQuery('#tracking_preview_iframe').contents().find('.col.tracking-detail').css('border', '1px solid ' + color);
				jQuery('.tracking_page_preview').prop("disabled", true);
			},
		});

		jQuery('#wc_ast_status_label_color').wpColorPicker({
			change: function (e, ui) {
				var color = ui.color.toString();
				jQuery('.ts4wc_delivered_color .order-label.wc-delivered').css('background', color);
			},
		});
	}
});

jQuery(document).on("change", ".select_t_layout_section .radio-img", function () {
	jQuery('.tracking_page_preview').prop("disabled", true);
});

jQuery(document).on("click", "#wc_ast_link_to_shipping_provider", function () {
	jQuery('.tracking_page_preview').prop("disabled", true);
});

jQuery(document).on("click", "#wc_ast_hide_tracking_provider_image", function () {
	jQuery('.tracking_page_preview').prop("disabled", true);
});

jQuery(document).on("click", "#wc_ast_hide_tracking_events", function () {
	jQuery('.tracking_page_preview').prop("disabled", true);
});

jQuery(document).on("click", "#wc_ast_remove_trackship_branding", function () {
	jQuery('.tracking_page_preview').prop("disabled", true);
});

jQuery(document).on("click", ".tracking_page_preview", function () {

	jQuery("#trackship_tracking_page_form").find(".spinner").addClass("active");
	document.getElementById('tracking_preview_iframe').contentDocument.location.reload(true);

	jQuery('#tracking_preview_iframe').load(function () {
		jQuery("#trackship_tracking_page_form").find(".spinner").removeClass("active");
		jQuery('.tracking_page_preview_popup').show();
		var iframe = document.getElementById("tracking_preview_iframe");
		iframe.style.height = iframe.contentWindow.document.body.scrollHeight + 'px';
	});
});

jQuery(document).on("click", ".popupclose", function () {
	jQuery('.tracking_page_preview_popup').hide();
});
jQuery(document).on("click", ".popup_close_icon", function () {
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

jQuery(document).ready(function () {
	'use strict';
	jQuery('.inner_tab_input:checked').trigger('click');
	jQuery('.tab_input:checked').trigger('click');
	jQuery('.map-provider-table .select2').select2();
});

jQuery(document).on("click", ".trackship_admin_content .trackship_nav_div .tab_input", function () {
	"use strict";
	var tab = jQuery(this).data('tab');
	var label = jQuery(this).data('label');
	jQuery('.zorem-layout__header .header-breadcrumbs-last').text(label);
	var url = window.location.protocol + "//" + window.location.host + window.location.pathname + "?page=trackship-for-woocommerce&tab=" + tab;
	window.history.pushState({ path: url }, '', url);
	jQuery('.trackship_nav_div .inner_tab_section').hide();
	jQuery('.trackship_nav_div #content_trackship_' + tab).show();
	jQuery(window).trigger('resize');
	if (jQuery('.tab_input#tab_trackship_notifications').is(':checked')) {
		jQuery('.inner_tab_input:checked').trigger('click');
	}
});

jQuery(document).on("click", ".ts_notifications_outer_table .inner_tab_input", function () {
	'use strict';
	var type = jQuery(this).data("type");
	var tab = jQuery(this).data('tab');
	jQuery('.tab_inner_container .inner_tab_section').hide();
	jQuery('.outer_form_table.ts_notifications_outer_table .shipment-status-' + type + '-section').show();
	var url = window.location.protocol + "//" + window.location.host + window.location.pathname + "?page=trackship-for-woocommerce&tab=" + tab;
	window.history.pushState({ path: url }, '', url);
	jQuery(window).trigger('resize');
});

jQuery(document).on("click", "#wc_ast_status_label_font_color", function () {
	var value = jQuery('#wc_ast_status_label_font_color').val();
	jQuery(".order-label.wc-delivered").css("color", value);
});

jQuery(document).on("change", ".ts_delivered_order_status_toggle", function () {
	if (jQuery(this).prop("checked") == true) {
		jQuery('.status-label-li').show();
		jQuery('.automation_parent').show();
	} else {
		jQuery('.status-label-li').hide();
		jQuery('.automation_parent').hide();
	}
});

jQuery(document).on("click", ".trackship-notice .bulk_shipment_status_button", function () {
	jQuery(".trackship-notice").block({
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
		success: function (response) {
			jQuery(".trackship-notice").unblock();
			jQuery('.trackship-notice .bulk_shipment_status_button').attr("disabled", true);
			jQuery(document).trackship_snackbar('Tracking info sent to Trackship for all Orders.');
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
			jQuery(document).trackship_snackbar_warning(warning_msg);
		}
	});
	return false;
});

jQuery(document).on("click", ".tool_link", function () {
	jQuery('#tab_tools').trigger("click");
});

jQuery(document).on("change", ".shipment_status_toggle input", function () {
	jQuery("#content5 ").block({
		message: null,
		overlayCSS: {
			background: "#fff",
			opacity: .6
		}
	});

	var settings_data = jQuery(this).data("settings");
	var wcast_enable_status_email;

	if (jQuery(this).prop("checked") == true) {
		wcast_enable_status_email = 1;
		jQuery(this).closest('tr').addClass('enable');
		jQuery(this).closest('tr').removeClass('disable');
		if (settings_data === 'late_shipments_email_settings') jQuery('.late-shipment-tr').removeClass('disable').addClass('enable');
	} else {
		wcast_enable_status_email = 0;
		jQuery(this).closest('tr').addClass('disable');
		jQuery(this).closest('tr').removeClass('enable');
		if (settings_data === 'late_shipments_email_settings') jQuery('.late-shipment-tr').addClass('disable').removeClass('enable');
	}

	var id = jQuery(this).attr('id');

	var ajax_data = {
		action: 'update_shipment_status_email_status',
		id: id,
		wcast_enable_status_email: wcast_enable_status_email,
		settings_data: settings_data,
		security: jQuery('#tswc_shipment_status_email').val()
	};

	jQuery.ajax({
		url: ajaxurl,
		data: ajax_data,
		type: 'POST',
		success: function (response) {
			jQuery("#content5 ").unblock();
			jQuery(document).trackship_snackbar(trackship_script.i18n.data_saved);
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
			jQuery(document).trackship_snackbar_warning(warning_msg);
		}
	});
	return;
});

/*ajex call for general tab form save*/
jQuery(document).on("change", "#all-shipment-status-delivered", function () {
	"use strict";

	if (jQuery(this).prop("checked") == true) {
		var checked = 1;
	} else {
		var checked = 0;
	}

	var ajax_data = {
		action: 'update_all_shipment_status_delivered',
		shipment_status_delivered: checked,
		security: jQuery('#all_status_delivered').val()
	};

	jQuery.ajax({
		url: ajaxurl,
		data: ajax_data,
		type: 'POST',
		success: function (response) {
			jQuery(document).trackship_snackbar(trackship_script.i18n.data_saved);
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
			jQuery(document).trackship_snackbar_warning(warning_msg);
		}
	});
	return false;
});

jQuery(document).on("click", ".late-shipment-tr", function (event) {
	var $trigger = jQuery(".shipment_status_toggle");
	if ($trigger !== event.target && !$trigger.has(event.target).length) {
		if (jQuery(this).hasClass("open")) {
			jQuery('.late-shipments-email-content-table').slideUp('slow');
			jQuery(this).removeClass('open');
		} else {
			jQuery('.late-shipments-email-content-table').slideDown('slow');
			jQuery(this).addClass('open');
		}
	}
});

jQuery(document).on("change", ".ts_order_status_toggle", function () {

	if (jQuery(this).prop("checked") == true) {
		jQuery('.ts4wc_delivered_color').fadeIn();
	} else {
		jQuery('.ts4wc_delivered_color').fadeOut();
	}

});

jQuery(document).on("change", "#wc_ast_use_tracking_page", function () {
	'use strict';
	if (jQuery(this).prop("checked") == true) {
		jQuery('.li_wc_ast_trackship_page_id').fadeIn();
	} else {
		jQuery('.li_wc_ast_trackship_page_id').fadeOut();
	}

});

jQuery(document).on("change", "#smswoo_sms_provider", function () {
	'use strict';
	jQuery(".smswoo_sms_provider").hide();

	var provider = jQuery(this).val();
	//jQuery( "."+provider+"_link_provider" ).show();
	jQuery("." + provider + "_sms_provider").show();
});

/*
* trigger change event on page load
*/
jQuery(document).ready(function () {
	'use strict';
	jQuery("#smswoo_sms_provider").trigger("change");
	jQuery("#wc_ast_use_tracking_page").trigger("change");
	jQuery(".ts_order_status_toggle").trigger("change");
});

/* trackship_snackbar jquery */
(function ($) {
	$.fn.trackship_snackbar = function (msg) {
		if (jQuery('.snackbar-logs').length === 0) {
			$("body").append("<section class=snackbar-logs></section>");
		}
		var trackship_snackbar = $("<article></article>").addClass('snackbar-log snackbar-log-success snackbar-log-show').text(msg);
		$(".snackbar-logs").empty();
		$(".snackbar-logs").append(trackship_snackbar);
		setTimeout(function () { trackship_snackbar.remove(); }, 3000);
		return this;
	};
})(jQuery);

/* trackship_snackbar_warning jquery */
(function ($) {
	$.fn.trackship_snackbar_warning = function (msg) {
		if (jQuery('.snackbar-logs').length === 0) {
			$("body").append("<section class=snackbar-logs></section>");
		}
		var trackship_snackbar_warning = $("<article></article>").addClass('snackbar-log snackbar-log-error snackbar-log-show').html(msg);
		$(".snackbar-logs").empty();
		$(".snackbar-logs").append(trackship_snackbar_warning);
		setTimeout(function () { trackship_snackbar_warning.remove(); }, 3000);
		return this;
	};
})(jQuery);

/*
* save tracking page form
*/
jQuery(document).on("change", "#wc_ast_trackship_page_id", function () {
	'use strict';
	var wc_ast_trackship_page_id = jQuery(this).val();
	if (wc_ast_trackship_page_id === 'other') {
		jQuery('.trackship_other_page_fieldset').show();
	} else {
		jQuery('.trackship_other_page_fieldset').hide();
	}
});

jQuery(document).on("click", ".open_ts_video", function () {
	jQuery('.ts_video_popup').show();
});

jQuery(document).on("click", ".ts_video_popup .popupclose", function () {
	jQuery('#ts_video').each(function (index) {
		jQuery(this).attr('src', jQuery(this).attr('src'));
		return false;
	});
	jQuery('.ts_video_popup').hide();
});

jQuery(document).on("click", ".add_custom_mapping_h3", function () {
	var spinner = jQuery('#trackship_mapping_form').find(".add-custom-mapping.spinner").addClass("active");
	var ajax_data = {
		action: 'add_trackship_mapping_row',
	};
	jQuery.ajax({
		url: ajaxurl,
		data: ajax_data,
		type: 'POST',
		success: function (response) {
			jQuery('.map-provider-table tr:last').after(response.table_row);
			jQuery('.map-provider-table .select2').select2();
			spinner.removeClass("active");
		},
		error: function (response, jqXHR, exception) {
			console.log(response);
			spinner.removeClass("active");
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
			jQuery(document).trackship_snackbar_warning(warning_msg);
		}
	});
	return false;
});

jQuery(document).on("click", ".remove_custom_maping_row", function () {
	jQuery(this).closest('tr').remove();
});

jQuery(document).on("click", ".metabox_get_shipment_status", function () {
	var data = {
		action: 'metabox_get_shipment_status',
		order_id: woocommerce_admin_meta_boxes.post_id,
		security: jQuery('#get_shipment_nonce').val()
	}
	jQuery.ajax({
		url: ajaxurl,
		data: data,
		type: 'POST',
		dataType: "json",
		success: function (response) {
			if (response.success === true) {
				jQuery(".metabox_get_shipment_status").hide();
				jQuery(".temp-pending_trackship").show();
				jQuery(document).trackship_snackbar(response.data.msg);
			} else {
				jQuery(document).trackship_snackbar_warning(response.data.msg);
			}
		},
		error: function (jqXHR, exception) {
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
			} else if (jqXHR.responseText === '-1') {
				msg = 'Security check fail, please refresh and try again.';
			} else {
				msg = 'Uncaught Error.\n' + jqXHR.responseText;
			}
			jQuery(document).trackship_snackbar_warning(msg);
		}
	});
});

jQuery(document).on("click", ".open_tracking_details", function () {
	var data = {
		action: 'get_admin_tracking_widget',
		order_id: jQuery(this).data('orderid'),
		security: jQuery(this).data('nonce'),
		page: jQuery(this).data('page'),
		tracking_id: jQuery(this).data('tracking_id'),
		tnumber: jQuery(this).data('tnumber'),
	}
	jQuery.ajax({
		url: ajaxurl,
		data: data,
		type: 'POST',
		success: function (response) {
			jQuery("#admin_tracking_widget .popuprow").html(response);
			jQuery("#admin_tracking_widget").show();
			jQuery('.shipment-header .ts_from_input:checked').trigger('change');
			jQuery('.heading_panel.checked').trigger('click');
			jQuery(".trackship-tip").tipTip();
		},
		error: function (jqXHR, exception) {
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
			} else if (jqXHR.responseText === '-1') {
				msg = 'Security check fail, please refresh and try again.';
			} else {
				msg = 'Uncaught Error.\n' + jqXHR.responseText;
			}
			jQuery(document).trackship_snackbar_warning(msg);
		}
	});
	return false;

});

jQuery(document).on("click", ".popupclose", function () {
	jQuery(".popupwrapper").hide();
});

jQuery(document).on("click", ".popup_close_icon", function () {
	jQuery(".popupwrapper").hide();
});

jQuery(document).on("click", ".update_shipping_provider", function () {
	jQuery('.sync_trackship_provider_popup').show();
	jQuery('.sync_message').show();
	jQuery(".sync_trackship_providers_btn").show();
	jQuery('.synch_result').hide();
});

jQuery(document).on("click", ".sync_trackship_providers_btn", function () {

	jQuery('.sync_trackship_provider_popup .spinner').addClass('active');
	var nonce = jQuery('#nonce_trackship_provider').val();

	var ajax_data = {
		action: 'update_trackship_providers',
		security: nonce,
	};
	jQuery.ajax({
		url: ajaxurl,
		data: ajax_data,
		type: 'POST',
		dataType: "json",
		success: function (response) {
			jQuery('.sync_trackship_provider_popup .spinner').removeClass('active');
			jQuery('.sync_message').hide();
			jQuery(".sync_trackship_providers_btn").hide();
			jQuery('.synch_result').show();
		},
		error: function (response, jqXHR, exception) {
			console.log(response);
			jQuery('.sync_trackship_provider_popup .spinner').removeClass('active');
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
			jQuery(document).trackship_snackbar_warning(warning_msg);
		}
	});
});

jQuery(document).on("click", ".view_old_details", function () {
	jQuery(this).hide();
	jQuery(this).closest('.tracking-details').find('.hide_old_details').show();
	jQuery(this).closest('.tracking-details').find('.old-details').fadeIn();
});
jQuery(document).on("click", ".hide_old_details", function () {
	jQuery(this).hide();
	jQuery(this).closest('.tracking-details').find('.view_old_details').show();
	jQuery(this).closest('.tracking-details').find('.old-details').fadeOut();
});

/*
* click on tracking_page_link
*/
jQuery(document).on("click", ".copy_tracking_page", function () {
	var text = jQuery(this).data("tracking_page_link");
	copyTextToClipboard(text);
	jQuery(document).trackship_snackbar('Tracking link copied to clipboard');
});

/*
* click on tracking number from dashboard
*/
jQuery(document).on("click", ".copied_tracking_numnber", function () {
	var text = jQuery(this).data("number");
	copyTextToClipboard(text);
	jQuery(document).trackship_snackbar('Tracking number copied to clipboard');
});

/*
* click on copy_view_order_page
*/
jQuery(document).on("click", ".copy_view_order_page", function () {
	var text = jQuery(this).data("view_order_link");
	copyTextToClipboard(text);
	jQuery(document).trackship_snackbar('View Order page link copied to clipboard');
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

jQuery(document).on("click", ".tracking-event-delete-notice .bulk_shipment_status_button", function () {
	var days = jQuery("#delete_time").val();
	var ajax_data = {
		action: 'remove_tracking_event',
		days: days,
		security: jQuery('#wc_ast_tools').val()
	};
	jQuery.ajax({
		url: ajaxurl,
		data: ajax_data,
		type: 'POST',
		dataType: "json",
		success: function (response) {
			jQuery(document).trackship_snackbar('Tracking event deleted for ' + response.order_count + ' orders out of ' + response.found_orders + ' orders');
		},
		error: function (response) {
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
			} else if (jqXHR.responseText === '-1') {
				msg = 'Security check fail, please refresh and try again.';
			} else {
				warning_msg = 'Uncaught Error.\n' + jqXHR.responseText;
			}

			jQuery(document).trackship_snackbar_warning(warning_msg);
			console.log(response);
		}
	});
	return false;
});

jQuery(document).on("click", ".tracking_notification_log_delete .delete_notification", function () {
	var ajax_data = {
		action: 'remove_trackship_logs',
		security: jQuery('#wc_ast_tools').val()
	};
	jQuery.ajax({
		url: ajaxurl,
		data: ajax_data,
		type: 'POST',
		dataType: "json",
		success: function (response) {
			jQuery(document).trackship_snackbar('Notifications logs deleted more than 30 days.');
		},
		error: function (response) {
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
			} else if (jqXHR.responseText === '-1') {
				msg = 'Security check fail, please refresh and try again.';
			} else {
				warning_msg = 'Uncaught Error.\n' + jqXHR.responseText;
			}
			jQuery(document).trackship_snackbar_warning(warning_msg);
			console.log(response);
		}
	});
	return false;
});

jQuery(document).on("click", ".trackship-notice p span.dashicons", function () {
	var date = new Date();
	date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000));
	expires = "; expires=" + date.toUTCString();
	var cookies = document.cookie = "Notice=delete " + expires;
	jQuery('.trackship_notice_msg').hide();
	console.log(cookies);
});

/*
* click on more info
*/
jQuery(document).on("click", ".open_more_info_popup", function () {
	jQuery("#admin_error_more_info_widget").show();
	return false;
});

jQuery(document).on('click', '.inner_tab_section .heading_panel.section_sms_heading', function() {
	if ( smswoo_active == 'yes' ) {
		jQuery('.heading_panel.section_sms_heading').find( 'button' ).attr('disabled', true);
		jQuery('.panel_content.section_sms_content').find( 'select, input' ).attr('disabled', true);
		jQuery('.panel_content.section_sms_content .outer_form_table').addClass('smswoo_active');
	}
});

jQuery(document).on("click", ".inner_tab_section .heading_panel", function () {
	if (jQuery(this).next('.panel_content').hasClass('active')) {
		jQuery(this).removeClass('active');
		jQuery(this).siblings('.panel_content').removeClass('active').slideUp('slow');
		jQuery(".heading_panel").find('span.dashicons').addClass('dashicons-arrow-right-alt2');
		jQuery(".heading_panel").find('button.button-primary').hide();
	} else {
		jQuery(".heading_panel").css('border-color', '');
		jQuery(".heading_panel").removeClass('active');
		jQuery(".panel_content").removeClass('active').slideUp("slow");
		jQuery(".heading_panel").find('button.button-primary').hide();
		jQuery(".heading_panel").find('span.dashicons').addClass('dashicons-arrow-right-alt2');
		jQuery(this).addClass('active');
		jQuery(this).next('.panel_content').addClass('active').slideDown("slow", function () {
			jQuery('.map-provider-table .select2').select2();
		});
		jQuery(this).find('button.button-primary').show();
		jQuery(this).find('span.dashicons').removeClass('dashicons-arrow-right-alt2');
	}

});

jQuery(document).on("click", "#activity-panel-tab-help", function () {
	jQuery(this).addClass('is-active');
	jQuery('.woocommerce-layout__activity-panel-wrapper').addClass('is-open is-switching');
});

jQuery(document).on("click", function ( event ) {
	var $trigger = jQuery(".woocommerce-layout__activity-panel");
	if ($trigger !== event.target && !$trigger.has(event.target).length) {
		jQuery('#activity-panel-tab-help').removeClass('is-active');
		jQuery('.woocommerce-layout__activity-panel-wrapper').removeClass('is-open is-switching');
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
//If we will do change into below jQuery so we need to also change in trackship.js and front.js
jQuery(document).on("change", ".shipment-header .ts_from_input", function(){
	var id = jQuery(this).attr('id');
	var count = jQuery('.tracking-detail.col.active').length > 0;
	if ( count > 0 ) {
		jQuery( '.tracking-detail.col.active' ).removeClass('active').slideUp("slow", function(){
			jQuery( '.tracking-detail.col.' + id ).addClass('active').slideDown("slow");
		} );
	} else {
		jQuery( '.tracking-detail.col.' + id ).addClass('active').slideDown("slow");
	}
});
