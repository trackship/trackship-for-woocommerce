<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
wp_enqueue_script( 'trackship_script' );

$store_url = get_site_url();
$connect_url = add_query_arg( array(
	'utm_source'	=> 'wpadmin',
	'utm_campaign'	=> 'tspage',
	'store_url'		=> $store_url,
	'type'			=> 'wc',
	'token'			=> md5( $store_url ),
), 'https://my.trackship.com' );
?>
<div class="ts-connect-page">

	<div class="ts-connect-hero">
		<div class="ts-connect-badge"><?php esc_html_e( 'Get started for Free', 'trackship-for-woocommerce' ); ?></div>
		<h1 class="ts-connect-title"><?php esc_html_e( 'Your Post-Shipping & Delivery Autopilot', 'trackship-for-woocommerce' ); ?></h1>
		<p class="ts-connect-desc"><?php esc_html_e( 'Connect your store to TrackShip to auto-track shipments, automate order workflows, and give customers a superior post-purchase experience.', 'trackship-for-woocommerce' ); ?></p>
		<div class="ts-connect-actions">
			<a href="<?php echo esc_url( $connect_url ); ?>" class="button-primary button-trackship ts-connect-btn"><?php esc_html_e( 'Connect Store', 'trackship-for-woocommerce' ); ?> <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></a>
			<button class="ts-connect-play-btn ts-connect-video-trigger" aria-label="<?php esc_attr_e( 'Watch demo video', 'trackship-for-woocommerce' ); ?>">
				<span class="ts-connect-play-btn__icon">
					<svg viewBox="0 0 24 24" fill="#fff" width="12" height="12"><polygon points="7,4 20,12 7,20"/></svg>
				</span>
				<?php esc_html_e( 'Watch Video', 'trackship-for-woocommerce' ); ?>
			</button>
		</div>
	</div>

	<!-- Video modal -->
	<div class="ts-video-modal" id="ts-video-modal" style="display:none;">
		<div class="ts-video-modal__backdrop"></div>
		<div class="ts-video-modal__dialog">
			<button class="ts-video-modal__close" aria-label="<?php esc_attr_e( 'Close', 'trackship-for-woocommerce' ); ?>">&times;</button>
			<div class="ts-video-modal__embed">
				<iframe id="ts-video-iframe" src="" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
			</div>
		</div>
	</div>
	<script>
	(function($){
		var modal = $('#ts-video-modal');
		var iframe = $('#ts-video-iframe');
		var src = 'https://www.youtube.com/embed/QDKV2Irqz9M?autoplay=1';

		$('.ts-connect-video-trigger').on('click keypress', function(e){
			if ( e.type === 'keypress' && e.which !== 13 ) return;
			e.preventDefault();
			iframe.attr('src', src);
			modal.fadeIn(200);
		});

		modal.on('click', '.ts-video-modal__backdrop, .ts-video-modal__close', function(){
			modal.fadeOut(200);
			iframe.attr('src', '');
		});

		$(document).on('keydown', function(e){
			if ( e.key === 'Escape' && modal.is(':visible') ) {
				modal.fadeOut(200);
				iframe.attr('src', '');
			}
		});
	}(jQuery));
	</script>

	<div class="ts-connect-features">
		<div class="ts-connect-feature">
			<div class="ts-connect-feature__icon">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
			</div>
			<h4><?php esc_html_e( 'Auto-Track Shipments', 'trackship-for-woocommerce' ); ?></h4>
			<p><?php esc_html_e( 'Automatically track shipments across 1,000+ carriers without any manual work.', 'trackship-for-woocommerce' ); ?></p>
		</div>
		<div class="ts-connect-feature">
			<div class="ts-connect-feature__icon">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.7 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.61 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 6.29 6.29l.97-.97a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
			</div>
			<h4><?php esc_html_e( 'Branded Notifications', 'trackship-for-woocommerce' ); ?></h4>
			<p><?php esc_html_e( 'Send proactive shipment status emails that reduce "Where is my order?" support tickets.', 'trackship-for-woocommerce' ); ?></p>
		</div>
		<div class="ts-connect-feature">
			<div class="ts-connect-feature__icon">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
			</div>
			<h4><?php esc_html_e( 'Tracking Page', 'trackship-for-woocommerce' ); ?></h4>
			<p><?php esc_html_e( 'Give customers a branded tracking page to follow their shipment in real time.', 'trackship-for-woocommerce' ); ?></p>
		</div>
	</div>

</div>
