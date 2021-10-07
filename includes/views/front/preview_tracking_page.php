<style>	
	html{
		background-color:#fff;
		margin-top:0px !important;
	}
	.col.tracking-detail{
		margin: 50px auto 100px;
	}
	.est_delivery_date {
		margin-bottom: 15px;
		display: block;
	}
	.customize-partial-edit-shortcut-button {display: none;}
	<?php if ( $border_color ) { ?>
		.col.tracking-detail{
			border: 1px solid <?php echo esc_html( $border_color ); ?>;
		}
		body .col.tracking-detail .shipment-header{
			border-bottom: 1px solid <?php echo esc_html( $border_color ); ?>;
		}
		body .col.tracking-detail .trackship_branding{
			border-top: 1px solid <?php echo esc_html( $border_color ); ?>;
		}
		body .tracking-detail .h4-heading {
			border-bottom: 1px solid <?php echo esc_html( $border_color ); ?>;
		}
		body .tracking_number_wrap {
			border-bottom: 1px solid <?php echo esc_html( $border_color ); ?>;
		}
	<?php }	?>
	<?php if ( $background_color ) { ?>
		body .col.tracking-detail{
			background: <?php echo esc_html( $background_color ); ?>;
		}
	<?php } ?>
	<?php if ( $font_color ) { ?>
		body .tracking-detail .shipment-content, body .tracking-detail .shipment-content h4 {
			color: <?php echo esc_html( $font_color ); ?>;
		}				
	<?php } ?>
</style>

<div class="tracking-detail col">
	<div class="shipment-content">
		<div class="tracking-header">
			<div class="tracking_number_wrap">
				<span class="wc_order_id">
					<a href="#" target="_blank">
						<?php /* translators: %s: search 14696 */ ?>
						<?php printf( esc_html( '#%d' ), 14696 ); ?>
					</a>
				</span>
				<div class="provider_image_div" style="">
					<img class="provider_image" src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/images/usps.png?v=3.1.1">
				</div>
				<div class="tracking_number_div">
					<ul>
						<li>
						<div>USPS</div> 
						<a href="https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1=9410803699300126968507" target="blank"><strong>9410803699300126968507</strong></a>
						<strong>9410803699300126968507</strong>
						</li>
					</ul>
				</div>
			</div>
			<h1 class="shipment_status_heading out_for_delivery">Out For Delivery</h1>
			<span class="est_delivery_date">Est. Delivery Date: <strong>Thursday, Oct 01</strong></span>	
		</div>
		<div class="tracker-progress-bar <?php echo 't_layout_1' == $tracking_page_layout ? 'tracking_layout_1' : ''; ?>">
			<div class="progress out_for_delivery">
				<div class="progress-bar out_for_delivery" <?php echo 't_layout_1' == $tracking_page_layout ? 'style="width: 0%;"' : 'style="width: 60%;"'; ?>></div>
				<?php if ( 't_layout_1' == $tracking_page_layout ) { ?>
					<div class="progress-icon icon1"></div>
					<div class="progress-icon icon2"></div>
					<div class="progress-icon icon3"></div>
					<div class="progress-icon icon4"></div>
				<?php } ?>
			</div>
		</div>
		<div class="tracking-details" style="<?php echo 1 == $hide_tracking_events ? 'display:none' : ''; ?>">
			<div class="shipment_progress_heading_div">
				<h4 class="h4-heading text-uppercase">Tracking Details</h4>
			</div>
			<?php if ( 0 == $hide_tracking_events ) { ?>
				<div class="tracking_details_by_date">			
					<ul class="timeline">
						<li>
							<strong>October 1, 2020 07:59</strong>
							<p>Out for Delivery, Expected Delivery by 8:00pm - EAST HARTFORD, CT - <span>EAST HARTFORD</span></p>
						</li>
						<li>
							<strong>October 1, 2020 07:48</strong>
							<p>Arrived at Post Office - HARTFORD, CT - <span>HARTFORD</span></p>
						</li> 
						<li>
							<strong>October 1, 2020 00:10</strong>
							<p>Arrived at USPS Regional Destination Facility - SPRINGFIELD MA NETWORK DISTRIBUTION CENTER,  - <span>SPRINGFIELD MA NETWORK DISTRIBUTION CENTER</span></p>
						</li>
						<li>
							<strong>September 30, 2020 00:00</strong>
							<p>In Transit to Next Facility<span></span></p>
						</li>   
						<li>
							<strong>September 29, 2020 13:12</strong>
							<p>USPS in possession of item - SHELDON, WI - <span>SHELDON</span></p>
						</li>
					</ul>							
				</div>
			<?php } elseif ( 2 == $hide_tracking_events ) { ?>
				<div class="tracking_details_by_date">						
					<ul class="timeline new-details">
						<li>
							<strong>October 1, 2020 07:59</strong>
							<p>Out for Delivery, Expected Delivery by 8:00pm - EAST HARTFORD, CT - <span>EAST HARTFORD</span></p>
						</li>
						<li>
							<strong>October 1, 2020 07:48</strong>
							<p>Arrived at Post Office - HARTFORD, CT - <span>HARTFORD</span></p>
						</li> 				
					</ul>			
					<ul class="timeline old-details" style="display:none;">
						<li>
							<strong>October 1, 2020 00:10</strong>
							<p>Arrived at USPS Regional Destination Facility - SPRINGFIELD MA NETWORK DISTRIBUTION CENTER,  - <span>SPRINGFIELD MA NETWORK DISTRIBUTION CENTER</span></p>
						</li>
						<li>
							<strong>September 30, 2020 00:00</strong>
							<p>In Transit to Next Facility<span></span></p>
						</li>   
						<li>
							<strong>September 29, 2020 13:12</strong>
							<p>USPS in possession of item - SHELDON, WI - <span>SHELDON</span></p>
						</li>
					</ul>							
				</div>
				<a class="view_old_details" href="javaScript:void(0);" style="display: inline;"><?php esc_html_e( 'view more', 'trackship-for-woocommerce' ); ?></a>
				<a class="hide_old_details" href="javaScript:void(0);" style="display: none;"><?php esc_html_e( 'view less', 'trackship-for-woocommerce' ); ?></a>		
			<?php } ?>

		</div>
	</div>
	<div class="trackship_branding" >
		<p>
        	<span><?php esc_html_e( 'Powered by ', 'trackship-for-woocommerce' ); ?></span>
			<a href="https://trackship.info" title="TrackShip" target="blank"><img src="<?php echo esc_html( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/images/trackship-logo.png"></a>
		</p>
	</div>
</div>
<style>
<?php if ( $remove_trackship_branding ) { ?>
	.trackship_branding{display:none;}
<?php } ?>
<?php if (in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ) { ?>
	.trackship_branding{display:block !important;}
<?php } ?>
<?php if ( $hide_tracking_provider_image ) { ?>
	.provider_image_div{display:none;}
<?php } ?>
<?php if ( $wc_ast_link_to_shipping_provider ) { ?>
.tracking_number_div ul li > strong{display:none;}
<?php } else { ?>
.tracking_number_div ul li > a{display:none;}
<?php } ?>
</style>
