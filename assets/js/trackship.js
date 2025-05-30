(function ($, data, wp, ajaxurl) {

	var trackship_js = {

		init: function () {

			$("#wc_trackship_form").on('click', '.woocommerce-save-button', this.save_trackship_form);
			$("#trackship_tracking_page_form").on('click', '.woocommerce-save-button', this.save_tracking_page_form);
			$("#trackship_mapping_form").on('click', '.woocommerce-save-button', this.save_trackship_mapping_form);
			$("#trackship_late_shipments_form").on('click', '.woocommerce-save-button', this.save_trackship_late_shipments_form);
			$(".tipTip").tipTip();

		},

		save_trackship_form: function (event) {
			event.preventDefault();

			$("#wc_trackship_form").find(".spinner").addClass("active");
			var ajax_data = $("#wc_trackship_form").serialize();

			$.post(ajaxurl, ajax_data)
			.done(function (response) {
				$("#wc_trackship_form").find(".spinner").removeClass("active");
				$(document).trackship_snackbar(trackship_script.i18n.data_saved);
				jQuery('.heading_panel').removeClass('active');
				jQuery('.heading_panel').siblings('.panel_content').removeClass('active').slideUp('slow');
				jQuery('.heading_panel').find('span.dashicons').addClass('dashicons-arrow-right-alt2');
				jQuery('.heading_panel').find('button.button-primary').hide();
			})
			.fail(function (response, jqXHR, exception) {
				trackship_js_error(response, jqXHR, exception);
			});
			return false;
		},

		save_tracking_page_form: function (event) {
			event.preventDefault();

			$("#trackship_tracking_page_form").find(".spinner").addClass("active");
			var ajax_data = $("#trackship_tracking_page_form").serialize();

			$.post(ajaxurl, ajax_data)
			.done(function (response) {
				$("#trackship_tracking_page_form").find(".spinner").removeClass("active");
				$(document).trackship_snackbar(trackship_script.i18n.data_saved);
				jQuery('.heading_panel').removeClass('active');
				jQuery('.heading_panel').siblings('.panel_content').removeClass('active').slideUp('slow');
				jQuery('.heading_panel').find('span.dashicons').addClass('dashicons-arrow-right-alt2');
				jQuery('.heading_panel').find('button.button-primary').hide();
			})
			.fail(function (response, jqXHR, exception) {
				trackship_js_error(response, jqXHR, exception);
			});
			return false;
		},

		save_trackship_mapping_form: function (event) {
			event.preventDefault();

			$("#trackship_mapping_form").find(".heading_panel .spinner").addClass("active");
			var ajax_data = $("#trackship_mapping_form").serialize();

			$.post(ajaxurl, ajax_data)
			.done(function (response) {
				$("#trackship_mapping_form").find(".spinner").removeClass("active");
				$(document).trackship_snackbar(trackship_script.i18n.data_saved);
				jQuery('.heading_panel').removeClass('active');
				jQuery('.heading_panel').siblings('.panel_content').removeClass('active').slideUp('slow');
				jQuery('.heading_panel').find('span.dashicons').addClass('dashicons-arrow-right-alt2');
				jQuery('.heading_panel').find('button.button-primary').hide();
			})
			.fail(function (response, jqXHR, exception) {
				trackship_js_error(response, jqXHR, exception);
			});
			return false;
		},

		save_trackship_late_shipments_form: function (event) {
			event.preventDefault();
			var email_address = jQuery('#late_shipments_email_to').val();

			if (email_address === "") {
				alert("Please fill the email address");
				return false;
			}

			$("#trackship_late_shipments_form").find(".spinner").addClass("active").slideDown('slow');
			var ajax_data = $("#trackship_late_shipments_form").serialize();
			$.post(ajaxurl, ajax_data)
			.done(function (response) {
				$("#trackship_late_shipments_form").find(".spinner").removeClass("active").slideUp('slow');
				jQuery(document).trackship_snackbar(trackship_script.i18n.data_saved);
				jQuery('.admin_notifications_tr').removeClass('open');
				jQuery('.admin_notifiations_content').slideUp('slow');
			})
			.fail(function (response, jqXHR, exception) {
				trackship_js_error(response, jqXHR, exception);
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
	var auto = jQuery('.trackship-update-tracking-info .bulk_migration').data('auto');
	jQuery('.inner_tab_input:checked').trigger('click');
	jQuery('.tab_input:checked').trigger('click');
	jQuery('.map-provider-table .select2').select2();
	jQuery('.accordion_container .heading_panel.checked').trigger('click');
	jQuery('.verify_database_table.checked').trigger('click');

	if ( 'yes' == auto ) {
		jQuery('.trackship-update-tracking-info .bulk_migration').trigger('click');
	}
	if ( ! jQuery('.map-provider-table tbody').find('tr').length ) {
		jQuery(".map-provider-table .ptw_provider_border").hide();
	}
});

jQuery(document).on("click", ".trackship_admin_content .trackship_nav_div .tab_input", function () {
	"use strict";
	var tab = jQuery(this).data('tab');
	var label = jQuery(this).data('label');
	jQuery('.zorem-layout__header .header-breadcrumbs-last').text(label);

	// Get the current URL parameters
	var urlParams = new URLSearchParams(window.location.search);
	// Set or update the 'tab' parameter
	urlParams.set('tab', tab);
	// Construct the new URL with the updated parameters
	var url = window.location.protocol + "//" + window.location.host + window.location.pathname + '?' + urlParams.toString();
	// Update the browser's URL without reloading the page
	window.history.pushState({ path: url }, '', url);

	// Hide/show content based on the selected tab
	jQuery('.trackship_nav_div .inner_tab_section').hide();
	jQuery('.trackship_nav_div #content_trackship_' + tab).show();
	jQuery(window).trigger('resize');

	// Handle the notifications tab specifically
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
	// Get the current URL parameters
	var urlParams = new URLSearchParams(window.location.search);
	// Set or update the 'tab' parameter
	urlParams.set('tab', tab);
	// Construct the new URL with the updated parameters
	var url = window.location.protocol + "//" + window.location.host + window.location.pathname + '?' + urlParams.toString();
	// Update the browser's URL without reloading the page
	window.history.pushState({ path: url }, '', url);
	jQuery(window).trigger('resize');
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
			trackship_js_error(response, jqXHR, exception)
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
	var status = jQuery(this).data('status');
	if ( jQuery.inArray( settings_data, [ "late_shipments_email_settings", "exception_admin_email", "on_hold_admin_email" ] ) !== -1 ) {
		if ( jQuery.inArray( shipments_script.user_plan, ["Free 50", "No active plan", "Trial Ended"] ) !== -1 ) {
			jQuery("#free_user_popup").show();
			return;
		}
	}

	var enable_status_email;
	if (jQuery(this).prop("checked") == true) {
		enable_status_email = 1;
		jQuery(this).closest('tr').addClass('enable');
		jQuery(this).closest('tr').removeClass('disable');
		if (settings_data === 'late_shipments_email_settings') jQuery('.late-shipment-tr').removeClass('disable').addClass('enable');
	} else {
		enable_status_email = 0;
		jQuery(this).closest('tr').addClass('disable');
		jQuery(this).closest('tr').removeClass('enable');
		if (settings_data === 'late_shipments_email_settings') jQuery('.late-shipment-tr').addClass('disable').removeClass('enable');
	}

	var id = jQuery(this).attr('id');

	var ajax_data = {
		action: 'update_shipment_status_email_status',
		id: id,
		enable_status_email: enable_status_email,
		settings_data: settings_data,
		status: status,
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
			trackship_js_error(response, jqXHR, exception)
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
			trackship_js_error(response, jqXHR, exception)
		}
	});
	return false;
});

/* Ajex call for Integration save*/
jQuery(document).on("change", ".ts_integration_checkbox input", function () {
	"use strict";

	jQuery.ajax({
		url: ajaxurl,
		data: jQuery("#trackship_integrations_form").serialize(),
		type: 'POST',
		success: function (response) {
			jQuery(document).trackship_snackbar(trackship_script.i18n.data_saved);
		},
		error: function (response, jqXHR, exception) {
			trackship_js_error(response, jqXHR, exception)
		}
	});
	return false;
});

jQuery(document).on("click", ".admin_notifications_tr", function (event) {

	if ( jQuery.inArray( shipments_script.user_plan, ["Free 50", "No active plan", "Trial Ended"] ) !== -1 ) {
		jQuery("#free_user_popup").show();
		return;
	}

	var $trigger = jQuery(".shipment_status_toggle");
	if ($trigger !== event.target && !$trigger.has(event.target).length) {
		var parent = jQuery(this).closest('.admin_notifications_div');
		if (jQuery(this).hasClass("open")) {
			parent.find(".admin_notifiations_content").slideUp('slow');
			jQuery(this).removeClass('open');
		} else {
			jQuery('.admin_notifications_tr').removeClass('open');
			jQuery('.admin_notifiations_content').slideUp('slow');
			parent.find(".admin_notifiations_content").slideDown('slow');
			jQuery(this).addClass('open');
		}
	}
});

jQuery(document).on("change", "#ts_tracking_page", function () {
	'use strict';
	if (jQuery(this).prop("checked") == true) {
		jQuery('.li_tracking_page_id').fadeIn();
	} else {
		jQuery('.li_tracking_page_id').fadeOut();
	}
});

jQuery(document).on("change", "#smswoo_sms_provider", function () {
	'use strict';
	jQuery(".smswoo_sms_provider").hide();
	var provider = jQuery(this).val();
	jQuery("." + provider + "_sms_provider").show();
});

/*
* trigger change event on page load
*/
jQuery(document).ready(function () {
	'use strict';
	jQuery("#smswoo_sms_provider").trigger("change");
	jQuery("#ts_tracking_page").trigger("change");
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

function trackship_js_error(response, jqXHR, exception) {
	console.log(response, jqXHR, exception);
	var msg = '';
	if (response.status === 0) {
		msg = 'Not connect.\n Verify Network.';
	} else if (response.status == 404) {
		msg = 'Requested page not found. [404]';
	} else if (response.status == 500) {
		msg = 'Internal Server Error [500].';
	} else if (exception === 'parsererror') {
		msg = 'Requested JSON parse failed.';
	} else if (exception === 'timeout') {
		msg = 'Time out error.';
	} else if (exception === 'abort') {
		msg = 'Ajax request aborted.';
	} else if (response.responseText === '-1') {
		msg = 'Security check fail, please refresh and try again.';
	} else {
		msg = 'Uncaught Error.\n' + response.responseText;
	}
	jQuery(document).trackship_snackbar_warning(msg);
}

/*
* save tracking page form
*/
jQuery(document).on("change", "#tracking_page_id", function () {
	'use strict';
	var tracking_page_id = jQuery(this).val();
	if (tracking_page_id === 'other') {
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
	
	jQuery(this).prop('disabled', true);

	var ajax_data = {
		action: 'add_trackship_mapping_row',
	};
	jQuery.ajax({
		url: ajaxurl,
		data: ajax_data,
		type: 'POST',
		success: function (response) {
			jQuery('.map-provider-table tbody').append(response.table_row);
			jQuery(".map-provider-table .ptw_provider_border").show();
			jQuery('.map-provider-table .select2').select2();
			spinner.removeClass("active");
			jQuery('.add_custom_mapping_h3').prop('disabled', false);
		},
		error: function (response, jqXHR, exception) {
			trackship_js_error(response, jqXHR, exception);
			jQuery('.add_custom_mapping_h3').prop('disabled', false);
		}
	});
	return false;
});

jQuery(document).on("click", ".remove_custom_maping_row", function () {
	jQuery(this).closest('tr').remove();
});

jQuery(document).on("click", ".remove_custom_maping_row, .add_custom_mapping_h3", function () {
	if ( ! jQuery('.map-provider-table tbody').find('tr').length ) {
		jQuery(".map-provider-table .ptw_provider_border").hide();
	}
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
			jQuery('.enhanced_tracking_detail .tracking_number_wrap.checked').trigger('click');
			jQuery(".trackship-tip").tipTip();
		},
		error: function (response, jqXHR, exception) {
			trackship_js_error(response, jqXHR, exception)
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
			trackship_js_error(response, jqXHR, exception)
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

jQuery(document).on("click", ".tracking_notification_log_delete .delete_notification", function () {
	var ajax_data = {
		action: 'remove_trackship_logs',
		security: jQuery('#ts_tools').val()
	};
	jQuery.ajax({
		url: ajaxurl,
		data: ajax_data,
		type: 'POST',
		dataType: "json",
		success: function (response) {
			jQuery(document).trackship_snackbar('Notifications logs deleted more than 30 days.');
		},
		error: function (response, jqXHR, exception) {
			trackship_js_error(response, jqXHR, exception)
		}
	});
	return false;
});

jQuery(document).on("click", ".trackship-verify-table .verify_database_table", function () {
	var ajax_data = {
		action: 'verify_database_table',
		security: jQuery('#ts_tools').val()
	};
	jQuery.ajax({
		url: ajaxurl,
		data: ajax_data,
		type: 'POST',
		dataType: "json",
		success: function (response) {
			jQuery(document).trackship_snackbar('Database table verified successfully');
			jQuery('.db_upgrade').hide();
			var urlParams = new URLSearchParams(window.location.search); //// Get the current URL parameters
			urlParams.delete('verify-db'); // Remove the 'verify-db' parameter
			var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?' + urlParams.toString(); // Construct the new URL without 'verify-db'
			window.history.pushState({ path: newUrl }, '', newUrl);
		},
		error: function (response, jqXHR, exception) {
			trackship_js_error(response, jqXHR, exception)
		}
	});
	return false;
});

jQuery(document).on("click", ".trackship-update-tracking-info .bulk_migration", function () {
	var ajax_data = {
		action: 'ts_bulk_migration',
		security: jQuery('#ts_tools').val()
	};
	jQuery.ajax({
		url: ajaxurl,
		data: ajax_data,
		type: 'POST',
		dataType: "json",
		success: function (response) {
			jQuery(document).trackship_snackbar('Migration is currently in progress.');
			jQuery('.trackship_migration_notice, .tools_tab .trackship-update-tracking-info').hide();
		},
		error: function (response, jqXHR, exception) {
			trackship_js_error(response, jqXHR, exception)
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
});

jQuery(document).on('click', '.inner_tab_section .heading_panel.section_sms_heading', function() {
	if ( smswoo_active == 'yes' ) {
		jQuery('.heading_panel.section_sms_heading').find( 'button' ).attr('disabled', true);
		jQuery('.panel_content.section_sms_content').find( 'select, input' ).attr('disabled', true);
		jQuery('.panel_content.section_sms_content .outer_form_table').addClass('smswoo_active');
	} else if ( jQuery.inArray( shipments_script.user_plan, ["Free 50", "No active plan", "Trial Ended"] ) !== -1) {
		jQuery('.panel_content.section_sms_content').find( 'select, input' ).attr('disabled', true);
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
	jQuery('.ts_activity-panel-wrapper').addClass('is-open is-switching');
});

jQuery(document).on("click", function ( event ) {
	var $trigger = jQuery(".ts_activity-panel");
	if ($trigger !== event.target && !$trigger.has(event.target).length) {
		jQuery('#activity-panel-tab-help').removeClass('is-active');
		jQuery('.ts_activity-panel-wrapper').removeClass('is-open is-switching');
	}
});

//If we will do change into below jQuery so we need to also change in trackship.js and front.js
jQuery(document).on("click", ".tracking-detail .heading_panel", function () {
	if (jQuery(this).hasClass('active')) {
		jQuery(this).removeClass('active');
		jQuery(this).children('.accordian-arrow').removeClass('ts-down').addClass('ts-right');
		jQuery(this).siblings('.content_panel').slideUp('slow');
	} else {
		var parent = jQuery(this).parent('.tracking_event_tab_view');
		parent.find(".heading_panel").removeClass('active');
		parent.find(".content_panel").removeClass('active').slideUp('slow');
		jQuery(this).addClass('active');
		parent.find('.accordian-arrow').removeClass('ts-down').addClass('ts-right');
		jQuery(this).children('.accordian-arrow').removeClass('ts-right').addClass('ts-down');
		jQuery(this).next('.content_panel').slideDown('slow');
	}
});
//If we will do change into below jQuery so we need to also change in trackship.js and front.js
jQuery(document).on("click", ".enhanced_tracking_detail .enhanced_heading", function () {
	if (jQuery(this).hasClass('active')) {
		jQuery(this).removeClass('active');
		jQuery(this).children('.enhanced_tracking_content .accordian-arrow').removeClass('ts-down').addClass('ts-right');
		jQuery(this).siblings('.enhanced_content').slideUp('slow');
	} else {
		var parent = jQuery(this).closest('.enhanced_tracking_detail');
		parent.find(".enhanced_heading").removeClass('active');
		parent.find(".enhanced_content").removeClass('active').slideUp('slow');
		jQuery(this).addClass('active');
		parent.find('.enhanced_tracking_content .accordian-arrow').removeClass('ts-down').addClass('ts-right');
		jQuery(this).children('.enhanced_tracking_content .accordian-arrow').removeClass('ts-right').addClass('ts-down');
		jQuery(this).next('.enhanced_content').slideDown('slow');
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
//If we will do change into below jQuery so we need to also change in trackship.js and front.js
jQuery(document).on("change", ".tracking_details_switch .enhanced_switch_input", function(){
	var number = jQuery(this).data('number');
	var type = jQuery(this).data('type');
	if ( 'overview' == type ) {
		jQuery( '.' + number + ' .enhanced_journey' ).slideUp();
	} else {
		jQuery( '.' + number + ' .enhanced_journey' ).slideDown();
	}
});
//If we will do change into below jQuery so we need to also change in trackship.js and front.js
jQuery(document).on("click", ".enhanced_tracking_detail .tracking_number_wrap .tracking_number_div a", function () {
	jQuery(this).addClass('clicked');
});
//If we will do change into below jQuery so we need to also change in trackship.js and front.js
jQuery(document).on("click", ".enhanced_tracking_detail .tracking_number_wrap", function () {
	if (jQuery('.enhanced_tracking_detail .tracking_number_wrap .tracking_number_div a').hasClass('clicked')) {
		jQuery('.enhanced_tracking_detail .tracking_number_wrap .tracking_number_div a').removeClass('clicked');
		return;
	}
	if (jQuery(this).hasClass('active')) {
		jQuery(this).removeClass('active');
		jQuery(this).find('.accordian-arrow').removeClass('ts-down').addClass('ts-right');
		jQuery(this).siblings('.enhanced_tracking_content').slideUp('slow');
	} else {
		jQuery(".enhanced_tracking_detail .tracking_number_wrap").removeClass('active');
		jQuery(".enhanced_tracking_content").slideUp('slow');
		jQuery(this).addClass('active');
		jQuery('.tracking_number_wrap .accordian-arrow').removeClass('ts-down').addClass('ts-right');
		jQuery(this).find('.accordian-arrow').removeClass('ts-right').addClass('ts-down');
		jQuery(this).siblings('.enhanced_tracking_content').slideDown('slow');
	}
});
