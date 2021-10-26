<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( 1 == $hide_tracking_events ) {
	return;
}
?>
<div class="tracking-details" style="">	
	<?php if ( 2 == $hide_tracking_events || is_wc_endpoint_url( 'order-received' ) ) { ?>
		<?php if ( !empty( $tracking_details_by_date ) ) { ?>
						
			<div class="shipment_progress_heading_div">	               				
				<h4 class="h4-heading text-uppercase"><?php esc_html_e( 'Tracking Details', 'trackship-for-woocommerce' ); ?></h4>					
			</div>	
			
			<?php if ( !empty( $trackind_destination_detail_by_status_rev ) ) { ?>
			
			<div class="tracking_destination_details_by_date">
				
				<h4 style=""><?php esc_html_e( 'Destination Details', 'trackship-for-woocommerce' ); ?></h4>
				<ul class="timeline new-details">	
					<?php
					$a = 1; 
					foreach ( $trackind_destination_detail_by_status_rev as $key => $value ) { 
						if ( $a > 1) {
							break;
						}
						$date = gmdate( 'Y-m-d', strtotime( $value->datetime ) );
						?>
						<li>
							<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime($date) ) ); ?> <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime($value->datetime) ) ); ?></strong>
							<p>
							<?php echo wp_kses_post( apply_filters( 'trackship_tracking_event_description', $value->message ) ); ?>
							<span><?php echo ( null != $value->tracking_location->city ) ? ' - ' : ''; ?><?php echo esc_html( apply_filters( 'trackship_tracking_event_location', $value->tracking_location->city ) ); ?></span>
							</p>					
						</li>						
					<?php $a++; } ?>
				</ul>	
				
				<ul class="timeline old-details" style="display:none;">	
					<?php 
					$a = 1;	
					foreach ( $trackind_destination_detail_by_status_rev as $key => $value ) {
						if ( $a <= 1 ) {
							$a++;
							continue;
						}
						$date = gmdate('Y-m-d', strtotime( $value->datetime ) );
						?>
						<li>
							<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime($date) ) ); ?> <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime($value->datetime) ) ); ?></strong>
							<p>
							<?php echo wp_kses_post( apply_filters( 'trackship_tracking_event_description', $value->message ) ); ?>
							<span><?php echo ( null != $value->tracking_location->city ) ? ' - ' : ''; ?><?php echo esc_html( esc_html( apply_filters( 'trackship_tracking_event_location', $value->tracking_location->city ) ) ); ?></span>
							</p>					
						</li>						
					<?php $a++; } ?>
				</ul>	
			</div>
			
			<?php } ?>
			
			<div class="tracking_details_by_date">
				
				<?php if ( !empty( $trackind_destination_detail_by_status_rev ) ) { ?>
					<h4 class="" style=""><?php esc_html_e( 'Origin Details', 'trackship-for-woocommerce' ); ?></h4>
				<?php } ?> 
				
				<ul class="timeline new-details">	
					<?php
					$a = 1; 
					foreach ( $trackind_detail_by_status_rev as $key => $value ) { 
						if ( $a > 1) {
							break;
						}
						$date = gmdate('Y-m-d', strtotime($value->datetime));
						?>
						<li>
							<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime($date) ) ); ?> <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime($value->datetime) ) ); ?></strong>
							<p>
								<?php echo wp_kses_post( apply_filters( 'trackship_tracking_event_description', $value->message ) ); ?>
								<span><?php echo ( null != $value->tracking_location->city ) ? ' - ' : ''; ?><?php echo esc_html( apply_filters( 'trackship_tracking_event_location', $value->tracking_location->city ) ); ?></span>
							</p>					
						</li>						
					<?php $a++; } ?>
				</ul>	
				
				<ul class="timeline old-details" style="display:none;">	
					<?php 
					$a = 1;	
					foreach ( $trackind_detail_by_status_rev as $key => $value ) {
						if ( $a <= 1 ) {
							$a++;
							continue;
						}
						$date = gmdate( 'Y-m-d', strtotime($value->datetime ) );
						?>
						<li>
							<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime($date) ) ); ?> <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime($value->datetime) ) ); ?></strong>
							<p>
								<?php echo wp_kses_post( apply_filters( 'trackship_tracking_event_description', $value->message ) ); ?>
								<span><?php echo ( null != $value->tracking_location->city ) ? ' - ' : ''; ?><?php echo esc_html( apply_filters( 'trackship_tracking_event_location', $value->tracking_location->city ) ); ?></span>
							</p>					
						</li>						
					<?php $a++; } ?>
				</ul>	
				
			</div>	
			
			<a class="view_old_details" href="javaScript:void(0);" style="display: inline;"><?php esc_html_e( 'view more', 'trackship-for-woocommerce' ); ?></a>
			<a class="hide_old_details" href="javaScript:void(0);" style="display: none;"><?php esc_html_e( 'view less', 'trackship-for-woocommerce' ); ?></a>	
		
		<?php } ?>
	<?php } else { ?>
	
		<?php if ( !empty( $tracking_details_by_date ) ) { ?>
			<div class="shipment_progress_heading_div">	               				
				<h4 class="h4-heading text-uppercase"><?php esc_html_e( 'Tracking Details', 'trackship-for-woocommerce' ); ?></h4>					
			</div>	
			<?php if ( !empty( $trackind_destination_detail_by_status_rev ) ) { ?>
				<div class="tracking_destination_details_by_date">
					<h4 style=""><?php esc_html_e( 'Destination Details', 'trackship-for-woocommerce' ); ?></h4>
					<ul class="timeline">	
						<?php
						foreach ( $trackind_destination_detail_by_status_rev as $key => $value ) {
							$date = gmdate('Y-m-d', strtotime($value->datetime));
							?>
							<li>
								<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime($date) ) ); ?> <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime($value->datetime) ) ); ?></strong>
								<p>
									<?php echo wp_kses_post( apply_filters( 'trackship_tracking_event_description', $value->message ) ); ?>
									<span><?php echo ( null != $value->tracking_location->city ) ? ' - ' : ''; ?><?php echo esc_html( apply_filters( 'trackship_tracking_event_location', $value->tracking_location->city ) ); ?></span>
								</p>
							</li>					
						<?php } ?>								
					</ul>	
				</div>
			<?php } ?>
			<div class="tracking_details_by_date">
				<?php if ( !empty( $trackind_destination_detail_by_status_rev ) ) { ?>
					<h4 class="" style=""><?php esc_html_e( 'Origin Details', 'trackship-for-woocommerce' ); ?></h4>
				<?php } ?>
				<ul class="timeline">
					<?php
					foreach ( $trackind_detail_by_status_rev as $key => $value ) { 
						$date = gmdate( 'Y-m-d', strtotime( $value->datetime ) );
						?>
						<li>
							<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime($date) ) ); ?> <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime($value->datetime) ) ); ?></strong>
							<p>
							<?php echo wp_kses_post( apply_filters( 'trackship_tracking_event_description', $value->message ) ); ?>
							<span><?php echo ( null != $value->tracking_location->city ) ? ' - ' : ''; ?><?php echo esc_html( apply_filters( 'trackship_tracking_event_location', $value->tracking_location->city ) ); ?></span>
							</p>
						</li>						
					<?php } ?>
				</ul>	
			</div>		
		<?php } ?>
	<?php } ?>
</div>
