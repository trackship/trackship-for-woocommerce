/* show_popup jquery */
(function( $ ){
	'use strict';
	$.fn.show_popup = function() {
		var val = jQuery( '.disable_pro' ).val();
		if ( val === 'disable_pro' ) {
			jQuery("#free_user_popup").show();
		}
		return this;
	}; 
})( jQuery );

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
	var url;
	
	var $table = jQuery("#shipments_table").DataTable({
		dom: "i<'table_scroll't><'datatable_footer'pl>",
		searching: false,
		"processing": true,
		"serverSide": true,
		"pagingType": "simple",	
		"order": [[ 5, "desc" ]],		
		"ajax": {
			'type': 'POST',
			'url': ajaxurl+'?action=get_trackship_shipments',
			'data': function ( d ) {				
				d.ajax_nonce = jQuery("#nonce_trackship_shipments").val();	
				d.active_shipment = jQuery("#active_shipment").val();
				d.shipment_status = jQuery("#shipment_status").val();
				d.ts4wc_shipment_times = jQuery("#ts4wc_shipment_times").val();
				d.search_bar = jQuery("#search_bar").val();
				d.shipping_provider = jQuery("#shipping_provider").val();
				d.tracking_code = jQuery("#tracking_code").val();
				d.shipping_date = jQuery("#shipping_date").val();
				d.order_id = jQuery("#order_id").val();		
				d.shipment_type	= jQuery("#shipment_type").val();							
			},
		},
		
		"lengthMenu": [[25, 50, 100, 200], [25, 50, 100, 200]],
		"pageLength":25,
		"drawCallback": function(settings) {
			jQuery(window).resize();
			jQuery(".trackship-tip").tipTip();
			jQuery("#shipments_table").unblock();
		},		
		oLanguage: {
			sProcessing: '<div id=loader><div class="fa-3x"><i class="fas fa-sync fa-spin"></i></div>'
		},
		
		"columns":[
			{
				"width": "100px",
				'orderable': false,		
				'data': 'et_shipped_at',
			},			
			{
				"width": "50px",
				'orderable': false,	
				"mRender":function(data,type,full) {
					return '<a href="'+shipments_script.admin_url+'post.php?post='+full.order_id+'&action=edit">' + full.order_number + '</a>';
				},									
			},	
			{
				"width": "150px",
				'orderable': false,	
				"mRender":function(data,type,full) {
					return '<span class="shipment_status_label '+full.shipment_status_id+'">' + full.shipment_status + '</span>';
				},				
			},	
			{
				"width": "150px",
				'orderable': false,		
				'data': 'formated_tracking_provider',
			},	
			{
				"width": "260px",
				'orderable': false,		
				"mRender":function(data,type,full) {
					return '<span class="copied_tracking_numnber dashicons dashicons-admin-page" data-number="' + full.tracking_number + '"></span><a target="_blank" href="'+full.tracking_url+'">' + full.tracking_number + '</a>';
				},				
			},	
			{
				"width": "150px",
				'orderable': false,		
				'data': 'ship_to',
			},
			{
				"width": "150px",
				'orderable': false,	
				'data': 'shipment_length',				
			},	
			{
				"width": "50px",	
				'orderable': false,	
				"mRender":function(data,type,full) {
					return '<a href="javascript:void(0);" class="shipments_get_shipment_status" data-orderid="' + full.order_id + '"><span class="dashicons dashicons-update"></span></a>';
				},	
			},		
		],
			
	});
	
	jQuery('#active_shipment').change(function() {
		jQuery(document).show_popup();
		jQuery(document).ajax_loader("#shipments_table");
		var label1 = jQuery( "#active_shipment" ).val();
		$table.ajax.reload();
	});
	jQuery('#shipment_status').change(function() {
		jQuery(document).show_popup();
		jQuery(document).ajax_loader("#shipments_table");
		var label2 = jQuery( "#shipment_status" ).val();
		$table.ajax.reload();
	});
	jQuery('#ts4wc_shipment_times').change(function() {
		jQuery(document).show_popup();
		jQuery(document).ajax_loader("#shipments_table");
		var label3 = jQuery( "#ts4wc_shipment_times" ).val();
		$table.ajax.reload();
	});	
	jQuery('.serch_button').click(function() {
		jQuery(document).show_popup();
		jQuery(document).ajax_loader("#shipments_table");
		var label4 = jQuery( "#search_bar" ).val();
		$table.ajax.reload();		
	});	
	
	jQuery(document).on("click", ".shipments_get_shipment_status", function(){
		jQuery(document).show_popup();
		jQuery(document).ajax_loader("#shipments_table");
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
			error: function(response) {
				console.log(response);			
			}
		});	
	});	
});

jQuery(document).on("click", ".bulk_action_submit", function(){
	var selected_option = jQuery('#bulk_action').children("option:selected").val();
	if( selected_option == 'get_shipment_status' ){
		var data = jQuery("#shipments_table input[name='order_id[]']").serializeArray();
		if( data.length !== 0 ){
			jQuery.ajax({
				url: ajaxurl+'?action=bulk_shipment_status_from_shipments',
				type : 'post',
				data : data,
				success : function( response ) {
					jQuery('#order_id').trigger("change");
				},
				error: function(errorThrown){
					console.log(errorThrown);
				}
			});
		}
	}	
});

jQuery(document).ready(function() {
	'use strict';
	jQuery( '#shipping_time' ).trigger('change');
});

jQuery(document).on("change", "#shipping_time", function(){
	'use strict';
	jQuery(document).show_popup();
	
	jQuery(document).ajax_loader(".flexcontainer");
	var selected_option = jQuery( "#shipping_time" ).val();
	var ajax_data = {
		action: 'get_tracking_analytics_overview',
		selected_option: selected_option,
		security: jQuery( '#wc_ast_dashboard_tab' ).val()
	};
	jQuery.ajax({
		url: ajaxurl,
		data: ajax_data,
		type: 'POST',	
		dataType:"json",
		success: function(response) {
			jQuery('.total_shipments_count').html(response.total_shipments);
			jQuery('.active_shipments_count').html(response.active_shipments);
			jQuery('.active_shipments_percent').html(response.active_shipments_percent);
			jQuery('.delivered_shipments_percent').html(response.delivered_shipments_percent);
			jQuery('.delivered_shipments_count').html(response.delivered_shipments);
			jQuery('.avg_shipment_length_count').html(response.avg_shipment_length);
			jQuery(".flexcontainer").unblock();
		},
		error: function(response) {
			console.log(response);
		}
	});
});

jQuery(document).on("click", ".inner_tab_label.inner_sms_tab", function(){
	jQuery(document).show_popup();
});

jQuery(document).on( "click", ".popupclose", function(){
	'use strict';
	jQuery(".popupwrapper").hide();
	jQuery( "#tab_email_notifications" ).trigger('click');
});
