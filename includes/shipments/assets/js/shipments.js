/* show_popup jquery */
(function( $ ){
	'use strict';
	$.fn.show_popup = function() {
		if ( jQuery.inArray( shipments_script.user_plan, ["Free Trial", "Free 50", "No active plan"] ) == 1 ) {
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
	var $table = jQuery("#active_shipments_table").DataTable({
		dom: "i<'table_scroll't><'datatable_footer'ilp>",
		searching: false,
		"processing": true,
		"ordering": false,
		"serverSide": true,
		"sPaginationType": "input",
		"order": [[ 5, "desc" ]],
		"ajax": {
			'type': 'POST',
			'url': ajaxurl+'?action=get_trackship_shipments',
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
			},
		},
		
		"lengthMenu": [[25, 50, 100, 200], [25, 50, 100, 200]],
		"pageLength":25,
		"drawCallback": function(settings) {
			jQuery(window).resize();
			jQuery(".trackship-tip").tipTip();
			jQuery("#active_shipments_table").unblock();
			jQuery(document).show_popup();
		},		
		oLanguage: {
			sProcessing: '<div id=loader><div class="fa-3x"><i class="fas fa-sync fa-spin"></i></div>',
			"sEmptyTable": "No data is available for this status",
		},
		
		"columns":[
			{
				"width": "120px",
				'orderable': false,		
				'data': 'et_shipped_at',
			},			
			{
				"width": "100px",
				'orderable': false,	
				"mRender":function(data,type,full) {
					return '<a href="'+shipments_script.admin_url+'post.php?post='+full.order_id+'&action=edit">' + full.order_number + '</a>';
				},									
			},	
			{
				"width": "185px",
				'orderable': false,	
				"mRender":function(data,type,full) {
					return '<span class="shipment_status_label '+full.shipment_status_id+'">' + full.shipment_status + '</span>';
				},				
			},	
			{
				"width": "160px",
				'orderable': false,		
				'data': 'formated_tracking_provider',
			},	
			{
				"width": "200px",
				'orderable': false,		
				'data': 'tracking_number_colom',				
			},
			{
				"width": "170px",
				'orderable': false,
				'data': 'ship_to',
			},
			{
				"width": "120px",
				'orderable': false,	
				'data': 'shipment_length',
			},	
			{
				"width": "125px",
				'orderable': false,		
				'data': 'est_delivery_date',
			},
			{
				"width": "100px",
				'orderable': false,
				'data': 'refresh_button',
			},
		],
	});	

	jQuery(document).on("change", "#shipment_status", function(){
		var active_status = jQuery(this).val();
		var active_provider = jQuery( "#shipping_provider" ).val();
		jQuery(document).show_popup();
		jQuery(document).ajax_loader("#active_shipments_table");
		$table.ajax.reload();
		var url = window.location.protocol + "//" + window.location.host + window.location.pathname+"?page=trackship-shipments&status="+active_status+"&provider=" + active_provider;
		window.history.pushState({path:url},'',url);
		if ( active_status === 'delivered' ) {
			$table.columns(8).visible(false);			
		} else {
			$table.columns(8).visible(true);						
		}		
	});
	jQuery(document).on("change", "#shipping_provider", function(){
		var active_provider = jQuery(this).val();
		var active_status = jQuery( "#shipment_status" ).val();
		jQuery(document).show_popup();
		jQuery(document).ajax_loader("#active_shipments_table");
		$table.ajax.reload();
		var url = window.location.protocol + "//" + window.location.host + window.location.pathname+"?page=trackship-shipments&status="+active_status+"&provider=" + active_provider;
		window.history.pushState({path:url},'',url);
	});
	jQuery("#search_bar").keyup(function(event) {
		if ( jQuery(this).val() ) {
			jQuery('.shipment_search_bar span').show();
		} else {
			jQuery('.shipment_search_bar span').hide();
		}
		if (event.keyCode === 13) {
			jQuery(".serch_button").click();
		}
	});
	jQuery(document).on("click", ".serch_button", function(){
		jQuery(document).show_popup();
		jQuery(document).ajax_loader("#active_shipments_table");
		$table.ajax.reload();		
	});	
	
	jQuery(document).on("click", ".shipments_get_shipment_status", function(){
		jQuery(document).show_popup();
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
			error: function(response) {
				console.log(response);			
			}
		});	
	});	
});

jQuery(document).on("click", ".fullfillment_dashboard_section .fullfillment_table tr", function(){
	'use strict';
	var current_plan = jQuery(".dashboard_hidden_field").val();
	if ( jQuery.inArray( current_plan, ["Free Trial", "Free 50", "No active plan"] ) == 1 ) {
		jQuery("#free_user_popup").show();
	}
});

jQuery(document).on("click", ".dashboard_input_tab .tab_input", function(){
	'use strict';
	var current_plan = jQuery(".dashboard_hidden_field").val();
	if ( jQuery.inArray( current_plan, ["Free Trial", "Free 50", "No active plan"] ) == 1 ) {
		if (jQuery( this ).hasClass('not_show')) {
			jQuery("#free_user_popup").show();
			jQuery('.dashboard_input_tab .tab_input.first_label').trigger("click");
			return;
		}
	}
	jQuery(document).ajax_loader(".fullfillment_dashboard_section_content");
	
	var selected_option = jQuery( this ).data('tab');
	var ajax_data = {
		action: 'dashboard_page_count_query',
		selected_option: selected_option,
		security: jQuery( '#wc_ast_dashboard_tab' ).val()
	};
	jQuery.ajax({
		url: ajaxurl,
		data: ajax_data,
		type: 'POST',	
		dataType:"json",
		success: function(response) {
			jQuery('.innner_content .total_shipment').html(response.total_shipment);
			jQuery('.innner_content .active_shipment').html(response.active_shipment);
			jQuery('.innner_content .delivered_shipment').html(response.delivered_shipment);
			jQuery('.innner_content .tracking_issues').html(response.tracking_issues);
			jQuery(".fullfillment_dashboard_section_content").unblock();
		},
		error: function(response) {
			console.log(response);
		}
	});
});

jQuery(document).on("click", ".bulk_action_submit", function(){
	var selected_option = jQuery('#bulk_action').children("option:selected").val();
	if( selected_option == 'get_shipment_status' ){
		var data = jQuery("#active_shipments_table input[name='order_id[]']").serializeArray();
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

jQuery(document).on("click", ".inner_tab_label.inner_sms_tab", function(){
	'use strict';
	if ( smswoo_active == 'no' ) {
		jQuery(document).show_popup();
	}
});

jQuery(document).on( "click", ".popupclose", function(){
	'use strict';
	jQuery(".popupwrapper").hide();
	jQuery( "#tab_email_notifications" ).trigger('click');
});

jQuery( document ).ready(function() {
	'use strict';
	var current_plan = jQuery(".dashboard_hidden_field").val();
	if ( jQuery.inArray( current_plan, ["Free Trial", "Free 50", "No active plan"] ) == 1 ) {
		jQuery('.fullfillment_dashboard_section .fullfillment_table tr').removeAttr('onclick');
	}
	var urlParams = new URLSearchParams(window.location.search);
	var has_status = urlParams.has('status'); // conditions
	if ( has_status ) {
		var status = urlParams.get('status');
		jQuery('#shipment_status').val(status).change();
	}
	var has_provider = urlParams.has('provider'); // conditions
	if ( has_provider ) {
		var provider = urlParams.get('provider');
		jQuery('#shipping_provider').val(provider).change();
	}
});

jQuery(document).on("click", ".shipment_search_bar span", function(){
	jQuery(this).prev().val('').focus();
	jQuery(this).hide();
});