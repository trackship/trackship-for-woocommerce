/*
 * Customizer Scripts
 * Need to rewrite and clean up this file.
 */

jQuery(document).ready(function() {

    /**
     * Change description
     */	 	
	jQuery('#customize-theme-controls #accordion-section-themes').hide();			
	jQuery( '#sub-accordion-section-trackship_shipment_status_email .customize-section-title > h3 .customize-action, #sub-accordion-section-ast_tracking_page_section .customize-section-title > h3 .customize-action' ).append( '<span class="dashicons dashicons-arrow-right" style="padding-top:4px; margin: 0 -5px;"></span> TrackShip' );
	jQuery( '.accordion-section .panel-title' ).html(wcast_customizer.customizer_title);
});	

// Handle mobile button click
function custom_size_mobile() {
	// get email width.
	var email_width = '684';
	var ratio = email_width/304;
	var framescale = 100/ratio;
	var framescale = framescale/100;
	jQuery('#customize-preview iframe').width(email_width+'px');
	jQuery('#customize-preview iframe').css({
			'-webkit-transform' : 'scale(' + framescale + ')',
			'-moz-transform'    : 'scale(' + framescale + ')',
			'-ms-transform'     : 'scale(' + framescale + ')',
			'-o-transform'      : 'scale(' + framescale + ')',
			'transform'         : 'scale(' + framescale + ')'
	});
}
jQuery('#customize-footer-actions .preview-mobile').click(function(e) {
	custom_size_mobile();
});
	jQuery('#customize-footer-actions .preview-desktop').click(function(e) {
	jQuery('#customize-preview iframe').width('100%');
	jQuery('#customize-preview iframe').css({
			'-webkit-transform' : 'scale(1)',
			'-moz-transform'    : 'scale(1)',
			'-ms-transform'     : 'scale(1)',
			'-o-transform'      : 'scale(1)',
			'transform'         : 'scale(1)'
	});
});
jQuery('#customize-footer-actions .preview-tablet').click(function(e) {
	jQuery('#customize-preview iframe').width('100%');
	jQuery('#customize-preview iframe').css({
			'-webkit-transform' : 'scale(1)',
			'-moz-transform'    : 'scale(1)',
			'-ms-transform'     : 'scale(1)',
			'-o-transform'      : 'scale(1)',
			'transform'         : 'scale(1)'
	});
});

(function ( api ) {
    api.section( 'trackship_shipment_status_email', function( section ) {		
        section.expanded.bind( function( isExpanded ) {	
			var url;
            if ( isExpanded ) {
				jQuery('#save').trigger('click');
				var shipment_status = jQuery(".preview_shipment_status_type option:selected").val();				
				
				if(shipment_status == 'in_transit'){					
					url = wcast_customizer.customer_intransit_preview_url;
					api.previewer.previewUrl.set( url );
				} else if(shipment_status == 'on_hold'){					
					url = wcast_customizer.customer_onhold_preview_url;
					api.previewer.previewUrl.set( url );	
				} else if(shipment_status == 'return_to_sender'){
					url = wcast_customizer.customer_returntosender_preview_url;
					api.previewer.previewUrl.set( url );
				} else if(shipment_status == 'available_for_pickup'){
					url = wcast_customizer.customer_availableforpickup_preview_url;
					api.previewer.previewUrl.set( url );
				} else if(shipment_status == 'out_for_delivery'){
					url = wcast_customizer.customer_outfordelivery_preview_url;
					api.previewer.previewUrl.set( url );
				} else if(shipment_status == 'delivered'){
					url = wcast_customizer.customer_delivered_preview_url;
					api.previewer.previewUrl.set( url );
				} else if(shipment_status == 'failure'){
					url = wcast_customizer.customer_failure_preview_url;
					api.previewer.previewUrl.set( url );
				} else if(shipment_status == 'exception'){
					url = wcast_customizer.customer_exception_preview_url;
					api.previewer.previewUrl.set( url );
				}
            }
        } );
    } );
} ( wp.customize ) );

(function ( api ) {
    api.section( 'ast_tracking_page_section', function( section ) {		
        section.expanded.bind( function( isExpanded ) {				
            var url;
            if ( isExpanded ) {
				jQuery('#save').trigger('click');
				
				var tracking_widget_type = jQuery("#_customize-input-tracking_widget_type").val();
	
				if ( tracking_widget_type == 'tracking_email_widget' ) {
					wp.customize.previewer.previewUrl(wcast_customizer.tracking_widget_email_preview_url);
					wp.customize.previewer.refresh();					
				} else {			
					wp.customize.previewer.previewUrl(wcast_customizer.tracking_page_preview_url);
					wp.customize.previewer.refresh();
				}
            }
        } );
    } );
} ( wp.customize ) );


jQuery(document).on("change", ".preview_order_select", function(){
	var wcast_preview_order_id = jQuery(this).val();
	var data = {
		action: 'update_email_preview_order',
		wcast_preview_order_id: wcast_preview_order_id,	
	};
	jQuery.ajax({
		url: ajaxurl,		
		data: data,
		type: 'POST',
		success: function(response) {			
			jQuery(".preview_order_select option[value="+wcast_preview_order_id+"]").attr('selected', 'selected');			
		},
		error: function(response) {
			console.log(response);			
		}
	});	
});

jQuery(document).ready(function() {	
	var shipment_status = wcast_customizer.shipment_status;
	jQuery(".preview_shipment_status_type").val(shipment_status);
});

wp.customize( 'wcast_shipment_status_type', function( value ) {		
	value.bind( function( wcast_shipment_status_type ) {
		
		if(wcast_shipment_status_type == 'in_transit'){
			wp.customize.previewer.previewUrl(wcast_customizer.customer_intransit_preview_url);
			wp.customize.previewer.refresh();	
		} else if(wcast_shipment_status_type == 'on_hold'){
			wp.customize.previewer.previewUrl(wcast_customizer.customer_onhold_preview_url);
			wp.customize.previewer.refresh();	
		} else if(wcast_shipment_status_type == 'return_to_sender'){
			wp.customize.previewer.previewUrl(wcast_customizer.customer_returntosender_preview_url);
			wp.customize.previewer.refresh();	
		} else if(wcast_shipment_status_type == 'available_for_pickup'){
			wp.customize.previewer.previewUrl(wcast_customizer.customer_availableforpickup_preview_url);
			wp.customize.previewer.refresh();	
		} else if(wcast_shipment_status_type == 'out_for_delivery'){
			wp.customize.previewer.previewUrl(wcast_customizer.customer_outfordelivery_preview_url);
			wp.customize.previewer.refresh();	
		} else if(wcast_shipment_status_type == 'delivered'){			
			wp.customize.previewer.previewUrl(wcast_customizer.customer_delivered_preview_url);
			wp.customize.previewer.refresh();	
		} else if(wcast_shipment_status_type == 'failure'){
			wp.customize.previewer.previewUrl(wcast_customizer.customer_failure_preview_url);
			wp.customize.previewer.refresh();	
		} else if(wcast_shipment_status_type == 'exception'){
			wp.customize.previewer.previewUrl(wcast_customizer.customer_exception_preview_url);
			wp.customize.previewer.refresh();	
		} 				
	});
});

wp.customize( 'tracking_widget_type', function( value ) {		
	value.bind( function( tracking_widget_type ) {
		
		if(tracking_widget_type == 'tracking_page_widget'){
			wp.customize.previewer.previewUrl(wcast_customizer.tracking_page_preview_url);
			wp.customize.previewer.refresh();	
		} else {			
			wp.customize.previewer.previewUrl(wcast_customizer.tracking_widget_email_preview_url);
			wp.customize.previewer.refresh();
		}
	});
});
