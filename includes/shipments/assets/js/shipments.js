function shipment_js_error(response, jqXHR, exception) {
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

/* ajax_loader jquery */
(function( $ ){
	'use strict';
	$.fn.ajax_loader = function( class_id ) {
		jQuery( class_id ).block({
			message: null,
			overlayCSS: {
				background: "#fff",
				opacity: 0.6
			}	
		});
		return this;
	}; 
})( jQuery );

jQuery('.shipping_date').on('apply.daterangepicker', function(ev, picker) {
	jQuery(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD')).trigger("change");
});

jQuery('.shipping_date').on('cancel.daterangepicker', function(ev, picker) {
	jQuery(this).val('').trigger("change");
});

jQuery(document).ready(function() {
	'use strict';
	var start = moment().subtract(6, 'month');
	var end = moment();

	jQuery('#shipment_date_range').daterangepicker({
		startDate: start,
		endDate: end,
		locale: {
			format: 'YYYY-MM-DD'
		},
		ranges: {
		   'Today': [moment(), moment()],
		   'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
		   'Last 7 Days': [moment().subtract(6, 'days'), moment()],
		   'Last 30 Days': [moment().subtract(29, 'days'), moment()],
		   'This Month': [moment().startOf('month'), moment().endOf('month')],
		   'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
		   'Last 6 Month': [moment().subtract(6, 'month'), moment()]
		}
	}, function(start, end, label) {
		jQuery('#shipment_start_date_range').val(start.format('YYYY-MM-DD'));
		jQuery('#shipment_end_date_range').val(end.format('YYYY-MM-DD'));
		$table.ajax.reload();
	});
	// Set default value on load
	jQuery('#shipment_date_range').val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));

	// Optionally set the hidden fields too
	jQuery('#shipment_start_date_range').val(start.format('YYYY-MM-DD'));
	jQuery('#shipment_end_date_range').val(end.format('YYYY-MM-DD'));

	var $table = jQuery("#active_shipments_table").DataTable({
		dom: "i<'shipments_custom_data'>B<'table_scroll't><'datatable_footer'ipl>",
		searching: false,
		fixedColumns: { end: 1 },
		scrollX: true,
		buttons: [
			'csvHtml5'
		],
		processing: true,
		ordering: true,
		serverSide: true,
		"order": [[ 1, 'desc' ]],
		"ajax": {
			'type': 'POST',
			'url': ajaxurl+'?action=get_trackship_shipments',
			beforeSend: function(){
				jQuery(document).ajax_loader("#active_shipments_table");
			},
			'data': function ( d ) {
				d.ajax_nonce = jQuery("#nonce_trackship_shipments").val();
				d.active_shipment = jQuery("#shipment_status").val();
				d.shipping_provider = jQuery("#shipping_provider").val();
				d.ts4wc_shipment_times = jQuery("#ts4wc_shipment_times").val();
				d.search_bar = jQuery("#search_bar").val();
				d.shipping_provider = jQuery("#shipping_provider").val();
				d.tracking_code = jQuery("#tracking_code").val();
				d.shipping_date = jQuery("#shipping_date").val();
				d.order_id = jQuery("#order_id").val();
				d.shipment_type	= jQuery("#shipment_type").val();
				d.start_date = jQuery("#shipment_start_date_range").val();
				d.end_date = jQuery("#shipment_end_date_range").val();
			},
		},
		
		"lengthMenu": [[25, 50, 100, 200], [25, 50, 100, 200]],
		"pageLength":25,
		"drawCallback": function(settings) {
			jQuery(window).resize();
			jQuery(".trackship-tip").tipTip();
			jQuery("#active_shipments_table").unblock();
		},
		oLanguage: {
			sProcessing: '<div id=loader><div class="fa-3x"><i class="fas fa-sync fa-spin"></i></div>',
			"sEmptyTable": "No data is available for this status",
		},
		
		"columns":[
			{
				"width": "35px",
				'orderable': false,
				"mRender":function(data,type,full) {
					return '<input type="checkbox" class="shipment_checkbox" data-orderid="' +full.order_id+ '" data-tnumber="' + full.tracking_number + '">';
				},
			},
			{
				"width": "100px",
				'orderable': true,
				"mRender":function(data,type,full) {
					return '<a href="'+shipments_script.admin_url+'post.php?post='+full.order_id+'&action=edit">' + full.order_number + '</a>';
				},
			},
			{
				"width": "130px",
				'orderable': true,
				'data': 'order_date',
				"mRender":function(data,type,full) {
					return full.order_date.formatted_date1 ? '<span class="trackship-tip" title="' + full.order_date.formatted_date2 + '">' + full.order_date.formatted_date1 + '</span>' : '';
				},
			},
			{
				"width": "130px",
				'orderable': true,
				'data': 'et_shipped_at',
				"mRender":function(data,type,full) {
					return '<span class="trackship-tip" title="'+full.et_shipped_at+'">'+full.et_shipped_at+'</span>';
				},
			},
			{
				"width": "130px",
				'orderable': true,
				"mRender":function(data,type,full) {
					return full.updated_at.updated_date1 ? '<span class="trackship-tip" title="' + full.updated_at.updated_date2 + '">' + full.updated_at.updated_date1 + '</span>' : '';
				},
			},
			{
				"width": "150px",
				'orderable': false,
				// 'data': 'tracking_number_colom',
				"mRender":function(data,type,full) {
					return '<span class="copied_tracking_numnber dashicons dashicons-admin-page" data-number="' + full.tracking_number + '"></span><a class="open_tracking_details shipment_tracking_number" data-orderid="' + full.order_id + '" data-tnumber="' + full.tracking_number + '" data-nonce="' + full.nonce + '">' + full.tracking_number + '</a>';
				},
			},
			{
				"width": "180px",
				'orderable': false,
				'data': 'formated_tracking_provider',
			},
			{
				"width": "190px",
				'orderable': false,
				"mRender":function(data,type,full) {
					return '<span class="shipment_status_label '+full.shipment_status_id+'">' + full.shipment_status + '</span>';
				},
			},
			{
				"width": "140px",
				'orderable': false,
				// 'data': 'ship_from',
				"mRender": function(data, type, full) {
					return full.ship_from.country_code ? '<div class="shipment_country"><img class="country_flag" src="https://trackship.github.io/country-flag/3x2/' + full.ship_from.country_code + '.svg"><span class="trackship-tip" title="' + full.ship_from.country_name + '">' + full.ship_from.country_name + '</span></div>' : '';
				},
			},
			{
				"width": "140px",
				'orderable': false,
				// 'data': 'ship_to',
				"mRender": function(data, type, full) {
					return full.ship_to.country_code ? '<div class="shipment_country"><img class="country_flag" src="https://trackship.github.io/country-flag/3x2/' + full.ship_to.country_code + '.svg"><span class="trackship-tip" title="' + full.ship_to.country_name + '">' + full.ship_to.country_name + '</span></div>' : '';
				},
			},
			{
				"width": "140px",
				'orderable': false,
				'data': 'ship_state',
			},
			{
				"width": "140px",
				'orderable': false,
				'data': 'ship_city',
			},
			{
				"width": "140px",
				'orderable': false,
				'data': 'last_event_date',
			},
			{
				"width": "200px",
				'orderable': false,
				'data': 'last_event',
				"mRender": function(data, type, full) {
					return full.last_event ? '<span class="last_event trackship-tip" title="' + full.last_event + '">' + full.last_event + '</span>' : 'N/A';
				},
			},
			{
				"width": "170px",
				'orderable': false,
				'data': 'customer',
			},
			{
				"width": "120px",
				'orderable': false,
				'data': 'shipment_length',
				"mRender": function(data, type, full) {
					var late_shipment = full.shipment_length.cond ? '<span class="dashicons dashicons-info trackship-tip ' + full.shipment_length.late_class + ' late_shipment" title="late shipment"></span>' : '';
					return '<span class="shipment_length ' + full.shipment_length.late_class + '">' + late_shipment + full.shipment_length.shipping_length + '</span>';
				},
			},	
			{
				"width": "115px",
				'orderable': false,
				'data': 'est_delivery_date',
			},
			{
				"width": "115px",
				'orderable': false,
				'data': 'delivery_number',
			},
			{
				"width": "55px",
				'orderable': false,
				"mRender": function(data, type, full) {
					return 'delivered' == full.shipment_status_id ? '' : '<span class="shipments_get_shipment_status" data-orderid="' + full.order_id + '" data-tnumber="' + full.tracking_number + '"><span class="dashicons dashicons-update"></span></span>';
				},
			},
		],
	});	
	
	var html = jQuery('.shipments_custom_data.custom_data').html();
	jQuery('.shipments_custom_data.custom_data').remove();
	jQuery('.shipments_custom_data').append(html);

	jQuery(document).on("change", "#shipment_status", function(){

		var active_status = jQuery(this).val();
		var active_provider = jQuery( "#shipping_provider" ).val();
		$table.ajax.reload();
		if ( active_status != 'all_ship' ) {
			jQuery('.filter_data.status_filter').show();
			jQuery('.filters_div').show();
			jQuery('.status_name').text(jQuery('#shipment_status option:selected').text());
		} else {
			jQuery('.filter_data.status_filter').hide();
			if ( jQuery('.filter_data:visible').length == 0 ) {
				jQuery('.filters_div').hide();
			}
		}
		var url = window.location.protocol + "//" + window.location.host + window.location.pathname+"?page=trackship-shipments&status="+active_status+"&provider=" + active_provider;
		window.history.pushState({path:url},'',url);
	});
	jQuery(document).on("change", "#shipping_provider", function(e){
		var active_provider = jQuery(this).val();
		var active_status = jQuery( "#shipment_status" ).val();
		$table.ajax.reload();
		if ( active_provider != 'all' ) {
			jQuery('.filter_data.provider_filter').show();
			jQuery('.filters_div').show();
			jQuery('.provider_name').text(jQuery('#shipping_provider option:selected').text());
		} else {
			jQuery('.filter_data.provider_filter').hide();
			if ( jQuery('.filter_data:visible').length == 0 ) {
				jQuery('.filters_div').hide();
			}
		}
		var url = window.location.protocol + "//" + window.location.host + window.location.pathname+"?page=trackship-shipments&status="+active_status+"&provider=" + active_provider;
		window.history.pushState({path:url},'',url);
	});

	// Show provider filter default
	if ( jQuery("#shipping_provider").val() != 'all' ) {
		jQuery('.filter_data.provider_filter').show();
		jQuery('.filters_div').show();
		jQuery('.provider_name').text(jQuery('#shipping_provider option:selected').text());
	} else {
		jQuery('.filter_data.provider_filter').hide();
		if ( jQuery('.filter_data:visible').length == 0 ) {
			jQuery('.filters_div').hide();
		}
	}
	// Show shipment_status filter default
	if ( jQuery("#shipment_status").val() != 'all_ship' ) {
		jQuery('.filter_data.status_filter').show();
		jQuery('.filters_div').show();
		jQuery('.status_name').text(jQuery('#shipment_status option:selected').text());
	} else {
		jQuery('.filter_data.status_filter').hide();
		if ( jQuery('.filter_data:visible').length == 0 ) {
			jQuery('.filters_div').hide();
		}
	}

	// keyupo search bar field and show/hide x icon
	jQuery(document).on("keyup", "#search_bar", function(event) {
		if ( jQuery(this).val() ) {
			jQuery('.shipment_search_bar span.dashicons-no').show();
			if (event.keyCode == 13) {
				jQuery('.serch_icon').trigger("click");
			}
		} else {
			jQuery('.shipment_search_bar span.dashicons-no').hide();
		}
	});

	jQuery(document).on("click", ".serch_icon", function() {
		$table.ajax.reload();
	});
	
	jQuery(document).on("click", ".shipment_search_bar span.dashicons-no", function(){
		jQuery(this).prev().val('').focus();
		jQuery(this).hide();
		$table.ajax.reload();
	});

	jQuery(document).on("click", ".shipments_get_shipment_status", function(){
		jQuery(this).addClass( 'spin' );
		var order_id = jQuery(this).data('orderid');
		
		var ajax_data = {
			action: 'get_shipment_status_from_shipments',
			order_id: order_id,
			security: jQuery( '#nonce_trackship_shipments' ).val()
		};
		jQuery.ajax({
			url: ajaxurl,
			data: ajax_data,
			type: 'POST',
			success: function(response) {
				$table.ajax.reload();
			},
			error: function(response, jqXHR, exception) {
				shipment_js_error(response, jqXHR, exception)
			}
		});	
	});

	jQuery(document).on("click", ".bulk_action_button", function(){
		var selected_option = jQuery('#bulk_actions').val();
		if( selected_option == 'get_shipment_status' ){
			var checkboxes = jQuery('.shipment_checkbox:checked');
			var orderids = checkboxes.filter(':checked').map(function() {
				return jQuery(this).data('orderid');
			}).get();

			if( checkboxes.length !== 0 ){
				var ajax_data = {
					action: 'bulk_shipment_status_from_shipments',
					orderids: orderids,
					security: jQuery( '#nonce_trackship_shipments' ).val()
				};
				jQuery.ajax({
					url: ajaxurl,
					data: ajax_data,
					type: 'POST',
					success : function( response ) {
						$table.ajax.reload()
						jQuery('.bulk_action_button').attr('disabled','disabled');
						jQuery('.all_checkboxes').prop("checked", false);
					},
					error: function(response, jqXHR, exception){
						shipment_js_error(response, jqXHR, exception)
					}
				});
			}
		}	
	});

	var localStorageData = localStorage.getItem('shipment_columns');
	if(localStorageData){
		var data = JSON.parse(localStorageData);
		Object.keys(data).map((keyName) => {
			jQuery(`#column_${keyName}`).prop("checked",data[keyName]);
			$table.columns(keyName).visible(keyName == 18 ? true : data[keyName]);
		})
	}

	jQuery(document).on("change", ".column_toogle input", function () {
		var localStorageData = localStorage.getItem('shipment_columns')
		var number = jQuery(this).data('number');
		if(localStorageData){
			localStorage.setItem('shipment_columns',JSON.stringify({
				...JSON.parse(localStorageData),
				[number]:jQuery(this).prop("checked") == true
			}));
		}else{
			localStorage.setItem('shipment_columns',JSON.stringify({
				[number]:jQuery(this).prop("checked") == true
			}));
		}
		if (jQuery(this).prop("checked") == true) {
			$table.columns(number).visible(true);
		} else {
			$table.columns(number).visible(false);
		}
	});
});

jQuery(document).on("click", "#bulk_actions", function(){
	var length = jQuery('.shipment_checkbox:checked').length;
	if( length && jQuery(this).val() == 'get_shipment_status' ){
		jQuery('.bulk_action_button').removeAttr('disabled');
	} else {
		jQuery('.bulk_action_button').attr('disabled','disabled');
	}
});

jQuery(document).on("change", ".all_checkboxes", function(){
	'use strict';
	var selected_option = jQuery('#bulk_actions').val();
	if (jQuery(this).prop("checked") == true) {	
		jQuery('.shipment_checkbox').prop("checked", true);
	} else {
		jQuery('.shipment_checkbox').prop("checked", false);
	}
	if (jQuery(this).prop("checked") == true && selected_option == 'get_shipment_status') {
		jQuery('.bulk_action_button').removeAttr('disabled');
	} else {
		jQuery('.bulk_action_button').attr('disabled','disabled');
	}
});

jQuery(document).on("change", ".shipment_checkbox", function(){
	var length = jQuery('.shipment_checkbox:checked').length;
	var selected_option = jQuery('#bulk_actions').val();
	if (length && selected_option == 'get_shipment_status') {
		jQuery('.bulk_action_button').removeAttr('disabled');
	} else {
		jQuery('.bulk_action_button').attr('disabled','disabled');
	}
});

jQuery(document).on("click", ".dashboard_input_tab .tab_input", function(){
	'use strict';
	jQuery(document).ajax_loader(".fullfillment_dashboard_section_content");
	
	var selected_option = jQuery( this ).data('tab');
	var ajax_data = {
		action: 'dashboard_page_count_query',
		selected_option: selected_option,
		security: jQuery( '#ts_tools' ).val()
	};
	jQuery.ajax({
		url: ajaxurl,
		data: ajax_data,
		type: 'POST',
		dataType:"json",
		success: function(response) {
			jQuery('.innner_content .total_shipment').html(response.total_shipment ? response.total_shipment : 0);
			jQuery('.innner_content .active_shipment').html(response.active_shipment ? response.active_shipment : 0);
			jQuery('.innner_content .delivered_shipment').html(response.delivered_shipment ? response.delivered_shipment : 0);
			jQuery('.innner_content .tracking_issues').html(response.tracking_issues ? response.tracking_issues : 0);
			jQuery(".fullfillment_dashboard_section_content").unblock();
		},
		error: function(response, jqXHR, exception) {
			shipment_js_error(response, jqXHR, exception)
		}
	});
});

jQuery(document).on( "click", ".popupclose", function(){
	'use strict';
	jQuery(".popupwrapper").hide();
});

jQuery( document ).ready(function() {
	'use strict';
	jQuery('.bulk_action_button').attr('disabled','disabled');
});

//remove status filter
jQuery(document).on("click", ".status_filter .dashicons", function(){
	jQuery('#shipment_status').val('all_ship').trigger('change');
});

//remove provider filter
jQuery(document).on("click", ".provider_filter .dashicons", function(){
	jQuery('#shipping_provider').val('all').trigger('change');
});

// Trigger click on csv button
jQuery(document).on("click", ".export_shipment .dashicons", function(){
	jQuery('.dt-buttons .buttons-csv').trigger('click');
});
