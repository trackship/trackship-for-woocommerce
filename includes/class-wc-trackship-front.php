<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_TrackShip_Front {

	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	 */
	private static $instance;
	
	/**
	 * Initialize the main plugin function
	*/
	public function __construct() {
		$this->init();	
	}
	
	/**
	 * Get the class instance
	 *
	 * @return WC_Advanced_Shipment_Tracking_Actions
	*/
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	
	/*
	* init from parent mail class
	*/
	public function init() {
		
		add_shortcode( 'wcast-track-order', array( $this, 'woo_track_order_function') );
		add_shortcode( 'trackship-track-order', array( $this, 'woo_track_order_function') );
		add_action( 'wp_enqueue_scripts', array( $this, 'front_styles' ) );		
		add_action( 'wp_ajax_nopriv_get_tracking_info', array( $this, 'get_tracking_info_fun') );
		add_action( 'wp_ajax_get_tracking_info', array( $this, 'get_tracking_info_fun') );
		
		add_action( 'plugins_loaded', array( $this, 'on_plugin_loaded' ) );
		
		add_action( 'woocommerce_view_order', array( $this, 'show_tracking_page_widget' ), 5, 1 );

		//save optin optout butoon 
		add_action( 'wp_ajax_save_unsunscribe_email_notifications_data', array( $this, 'unsubscribe_emails_save_callback') );
		add_action( 'wp_ajax_nopriv_save_unsunscribe_email_notifications_data', array( $this, 'unsubscribe_emails_save_callback') );
		add_action( 'wp_ajax_resubscribe_emails_save', array( $this, 'resubscribe_emails_save_callback') );
		add_action( 'wp_ajax_nopriv_resubscribe_emails_save', array( $this, 'resubscribe_emails_save_callback') );
	}
	
	public function on_plugin_loaded() {
		
		if ( function_exists( 'wc_advanced_shipment_tracking' ) && !function_exists( 'ast_pro' ) ) {
			remove_action( 'woocommerce_view_order', array( wc_advanced_shipment_tracking()->actions, 'show_tracking_info_order' ) );
		}
		
		if ( function_exists( 'ast_pro' ) && isset( ast_pro()->ast_pro_actions ) ) {
			remove_action( 'woocommerce_view_order', array( ast_pro()->ast_pro_actions, 'show_tracking_info_order' ) );
		}
		
		if ( function_exists( 'wc_shipment_tracking' ) && !function_exists( 'ast_pro' ) ) {
			// View Order Page.
			remove_action( 'woocommerce_view_order', array( wc_shipment_tracking()->actions, 'display_tracking_info' ) );
			remove_action( 'woocommerce_email_before_order_table', array( wc_shipment_tracking()->actions, 'email_display' ), 0, 4 );
			
			// View Order Page.
			add_action( 'woocommerce_email_before_order_table', array( $this, 'wc_shipment_tracking_email_display' ), 0, 4 );
		}
	}
	
	public function wc_shipment_tracking_email_display( $order, $sent_to_admin, $plain_text = null, $email = null ) {
		
		if ( is_a( $email, 'WC_Email_Customer_Refunded_Order' ) ) {
			return;
		}
		
		$shipment_status = trackship_for_woocommerce()->actions->get_shipment_status( $order->get_id() );
		
		$local_template	= get_stylesheet_directory() . '/woocommerce/emails/tracking-info.php';			
		if ( file_exists( $local_template ) && is_writable( $local_template ) ) {				
			wc_get_template( 'emails/tracking-info.php', array( 
				'tracking_items' => trackship_for_woocommerce()->get_tracking_items( $order->get_id() ),
				'shipment_status' => $shipment_status,
				'order_id' => $order->get_id(),
			), 'woocommerce-advanced-shipment-tracking/', get_stylesheet_directory() . '/woocommerce/' );
		} else {
			wc_get_template( 'emails/tracking-info.php', array( 
				'tracking_items' => trackship_for_woocommerce()->get_tracking_items( $order->get_id() ),
				'shipment_status' => $shipment_status,
				'order_id' => $order->get_id(),
				'new_status' => 'shipped',
			), 'woocommerce-advanced-shipment-tracking/', trackship_for_woocommerce()->get_plugin_path() . '/templates/' );
		}
	}

	/*
	* Save data
	*/
	public function unsubscribe_emails_save_callback() {
		$order_id = isset( $_POST['order_id'] ) ? sanitize_text_field( $_POST['order_id'] ) : '';
		check_ajax_referer( 'unsubscribe_emails' . $order_id, 'security' );
		$checkbox = isset( $_POST['checkbox'] ) ? sanitize_text_field( $_POST['checkbox'] ) : '';
		$order = wc_get_order( $order_id );
		$lable = isset( $_POST['lable'] ) ? sanitize_text_field( $_POST['lable'] ) : '';
		if ( 'email' == $lable ) {
			$order->update_meta_data( '_receive_shipment_emails', $checkbox );
		} else {
			$receive_sms = $checkbox ? 'yes' : 'no';
			$order->update_meta_data( '_smswoo_receive_sms', $receive_sms );
		}
		$order->save();

		// print_r($order->get_meta_data());
		echo json_encode( array('success' => 'true') );
		die();
	}

	/**
	 * Show tracking page widget
	**/
	public function show_tracking_page_widget( $order_id ) {
		$order = wc_get_order( $order_id );
		$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
		$shipment_status = $order->get_meta( 'shipment_status', true );
		$this->display_tracking_page( $order_id, $tracking_items, $shipment_status );
	}
	
	public function admin_tracking_page_widget( $order_id, $tracking_id ) {
		$order = wc_get_order( $order_id );
		$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
		foreach ( $tracking_items as $key => $tracking_item ) {
			if ( $tracking_item['tracking_id'] != $tracking_id && null != $tracking_id ) {
				unset($tracking_items[$key]);
			}
		}
		$shipment_status = $order->get_meta( 'shipment_status', true );
		$this->display_tracking_page( $order_id, $tracking_items, $shipment_status );	
	}
			
	/**
	 *
	 * Include front js and css
	 *
	 *
	*/
	public function front_styles() {
		
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script( 'jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'front-js', trackship_for_woocommerce()->plugin_dir_url() . 'assets/js/front.js', array( 'jquery' ), trackship_for_woocommerce()->version );
		wp_localize_script( 'front-js', 'zorem_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		
		wp_register_style( 'front_style', trackship_for_woocommerce()->plugin_dir_url() . 'assets/css/front.css', array(), trackship_for_woocommerce()->version );		
		
		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';

		if ( 'preview_tracking_page' == $action || is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'view-order' ) ) {
			wp_enqueue_style( 'front_style' );
			wp_enqueue_script( 'front-js' );
		}
	}
	
	/**
	 * Return tracking details or tracking form for shortcode - [wcast-track-order]
	 * Return tracking details or tracking form for shortcode - [trackship-track-order]
	*/
	public function woo_track_order_function() {
		
		wp_enqueue_style( 'front_style' );
		wp_enqueue_script( 'jquery-blockui' );
		wp_enqueue_script( 'front-js' );	
		
		$wc_ast_api_key = get_option('wc_ast_api_key');	
		
		if ( !$wc_ast_api_key ) { ?>
			<p><a href="https://trackship.com/" target="blank">TrackShip</a> is not active.</p>
			<?php
			return;
		}
		
		if ( isset( $_GET['order_id'] ) &&  isset( $_GET['order_key'] ) ) {
			
			$order_id = wc_clean($_GET['order_id']);
			$order = wc_get_order( $order_id );
			
			if ( empty( $order ) ) {
				$error = new WP_Error( 'ts4wc', __( 'Invalid order', 'my_textdomain' ) );
			} else {
				
				$order_key = $order->get_order_key();
			
				if ( $order_key != $_GET['order_key'] ) {
					$error = new WP_Error( 'ts4wc', __( 'Invalid order key', 'my_textdomain' ) );
				}
				
			}
		}

		if ( isset( $_GET['tracking'] ) ) {

			global $wpdb;
			$shipment_table = $wpdb->prefix . 'trackship_shipment';

			$tracking_number = wc_clean( $_GET[ 'tracking' ] );
			$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT order_id FROM $shipment_table WHERE tracking_number = %s", $tracking_number ) );
			$order = wc_get_order( $order_id );
			if ( empty( $order ) ) {
				$error = new WP_Error( 'ts4wc', __( 'Invalid Tracking number', 'my_textdomain' ) );
			}
		}
	
		if ( ! isset( $order_id ) || empty( $order ) || isset( $error ) ) {

			if ( isset( $error ) && is_wp_error( $error ) ){
				echo $error->get_error_message();
			}

			ob_start();		
			$this->track_form_template();
			$form = ob_get_clean();	
			return $form;

		} else {

			$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
			$shipment_status = $order->get_meta( 'shipment_status', true );
			if ( !$tracking_items ) {
				unset( $order_id );
			}

			ob_start();												
			echo esc_html( $this->display_tracking_page( $order_id, $tracking_items, $shipment_status ) );
			$form = ob_get_clean();	
			return $form;		
		}		
	}
	
	/**
	 * Ajax function for get tracking details
	*/
	public function get_tracking_info_fun() {
		
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? wc_clean( $_REQUEST['_wpnonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'tracking_form' ) ) {
			wp_send_json( array('success' => 'false', 'message' => __( 'Security verification failed, please refresh page and try again.', 'trackship-for-woocommerce' ) ) );
		}

		$wc_ast_api_key = get_option('wc_ast_api_key');	
		if ( !$wc_ast_api_key ) {
			return;
		}
		
		$order_id = isset( $_POST['order_id'] ) ? wc_clean( $_POST['order_id'] ) : '';
		$email = isset( $_POST['order_email'] ) ? sanitize_email( $_POST['order_email'] ) : '';
		$tracking_number = isset( $_POST['order_tracking_number'] ) ? wc_clean( $_POST['order_tracking_number'] ) : '';
		
		$order_id = trackship_for_woocommerce()->ts_actions->get_formated_order_id($order_id);
		
		if ( !empty( $tracking_number ) ) {
			global $wpdb;
			$shipment_table = $wpdb->prefix . 'trackship_shipment';
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $shipment_table WHERE tracking_number = %s", $tracking_number ) );
			$order_id = $row ? $row->order_id : '';
		}
		
		$order = wc_get_order( $order_id );
		if ( empty( $order ) ) {
			ob_start();		
			$this->track_form_template();
			$form = ob_get_clean();
			echo json_encode( array('success' => 'false', 'message' => __( 'Order not found.', 'trackship-for-woocommerce' ), 'html' => $form ));
			die();	
		}
		
		if ( empty( $tracking_number ) ) {
			$order_email = $order->get_billing_email();
			if ( strtolower( $order_email ) != strtolower( $email ) ) {
				ob_start();		
				$this->track_form_template();
				$form = ob_get_clean();	
				echo json_encode( array('success' => 'false', 'message' => __( 'Order not found.', 'trackship-for-woocommerce' ), 'html' => $form ));
				die();	
			}
		}
		
		$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
		$shipment_status = $order->get_meta( 'shipment_status', true );
		
		if ( !$tracking_items ) {
			ob_start();		
			$this->track_processing_template( $order, $order_id );
			$form = ob_get_clean();
			echo json_encode( array('success' => 'true', 'message' => '', 'html' => $form ));
			die();	
		}
		ob_start();		
		$html = $this->display_tracking_page( $order_id, $tracking_items, $shipment_status );
		$html = ob_get_clean();
		echo json_encode( array('success' => 'true', 'message' => '', 'html' => $html ));
		die();							
	}
	
	/*
	* retuern Tracking form HTML
	*/
	public function track_form_template() {
		$local_template	= get_stylesheet_directory() . '/woocommerce/tracking/tracking-form.php';
		if ( file_exists( $local_template ) && is_writable( $local_template ) ) {	
			wc_get_template( 'tracking/tracking-form.php', array(), 'trackship-for-woocommerce/', get_stylesheet_directory() . '/woocommerce/' );
		} else {
			wc_get_template( 'tracking/tracking-form.php', array(), 'trackship-for-woocommerce/', trackship_for_woocommerce()->get_plugin_path() . '/templates/' );	
		}		
	}

	public function track_processing_template( $order, $order_id ) {
		?>
		<div class="tracking-detail col not-shipped-widget">
			<div class="shipment-content">
				<div class="tracking-header">
					<div class="tracking_number_wrap">
						<span class="wc_order_id">
							<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" target="_blank">#<?php echo $order_id; ?></a>
						</span>
						<div class="shipment_heading">Order Processing</div>	
					</div>
					<span>Your order is being processed, the tracking details will be available once it's Shipped.</span>
					<span>Please try again after you receive the shipping confirmation email.</span>
				</div>
			</div>
		</div>
		<?php
	}
	
	/*
	* retuern Tracking page HTML
	*/
	public function display_tracking_page( $order_id, $tracking_items, $shipment_status ) {
		wp_enqueue_style( 'front_style' );
		wp_enqueue_script( 'jquery-blockui' );
		wp_enqueue_script( 'front-js' );
		
		global $wpdb;

		$unsubscribe = isset( $_GET["unsubscribe"] ) ? $_GET["unsubscribe"] : "" ;
		if ( 'true' == $unsubscribe ) {
			$order = wc_get_order( $order_id );
			$order->update_meta_data( '_receive_shipment_emails', 0 );
			$order->save();
			?>
			<div class="unsubscribe_message"><?php esc_html_e( 'You have been unsubscribed from shipment status emails.', 'trackship-for-woocommerce' ); ?></div>
			<?php
		}

		$tracking_page_defaults = trackship_admin_customizer();
		
		$border_color = get_option('wc_ast_select_border_color', $tracking_page_defaults->defaults['wc_ast_select_border_color'] );
		$link_color = get_option( 'wc_ast_select_link_color', $tracking_page_defaults->defaults['wc_ast_select_link_color'] );
		$background_color = get_option('wc_ast_select_bg_color', $tracking_page_defaults->defaults['wc_ast_select_bg_color'] );
		$font_color = get_option('wc_ast_select_font_color', $tracking_page_defaults->defaults['wc_ast_select_font_color'] );
		$hide_tracking_events = get_option('wc_ast_hide_tracking_events', $tracking_page_defaults->defaults['wc_ast_hide_tracking_events'] );
		$tracking_page_layout = get_option('wc_ast_select_tracking_page_layout', $tracking_page_defaults->defaults['wc_ast_select_tracking_page_layout'] );
		$remove_trackship_branding =  get_option('wc_ast_remove_trackship_branding', $tracking_page_defaults->defaults['wc_ast_remove_trackship_branding'] );
		?>
		<style>
			<?php if ( $link_color ) { ?>
				.col.tracking-detail .tracking_number_wrap a {
					color: <?php echo esc_html( $link_color ); ?>;
				}
			<?php } ?>		
			<?php if ( $border_color ) { ?>
				body .col.tracking-detail, .shipment_heading{
					border: 1px solid <?php echo esc_html( $border_color ); ?>;
				}
				body .col.tracking-detail .shipment_heading{
					border-bottom: 1px solid <?php echo esc_html( $border_color ); ?>;
				}
				body .tracking-detail .h4-heading {
					border-bottom: 1px solid <?php echo esc_html( $border_color ); ?> !important;
				}
				.tracking-detail .tracking_number_wrap {
					border-bottom: 1px solid <?php echo esc_html( $border_color ); ?> !important;
				}
				.trackship_branding, .tracking-detail .heading_panel {
					border-top: 1px solid <?php echo esc_html( $border_color ); ?> !important;
				}
			<?php } ?>
			<?php if ( $background_color ) { ?>
				body .col.tracking-detail, .shipment-header, .tracking-detail .heading_panel, .tracking-detail .content_panel {
					background: <?php echo esc_html( $background_color ); ?> !important;
				}
			<?php } ?>
			<?php if ( $font_color ) { ?>
				body .tracking-detail .shipment-content, body .tracking-detail .shipment-content h4 {
					color: <?php echo esc_html( $font_color ); ?>;
				}				
			<?php } ?>
			.woocommerce-account.woocommerce-view-order .tracking-header span.wc_order_id {display: none;}
			<?php if ( $remove_trackship_branding ) { ?>
				.trackship_branding {display:none;}
			<?php } ?>
		</style>
		<?php
		
		$num = 1;
		$total_trackings = count( $tracking_items );
		
		foreach ( $tracking_items as $key => $item ) {
			$tracking_number = $item['tracking_number'];
			$tracking_provider = $item['tracking_provider'];
						
			$tracker = new \stdClass();
			
			if ( isset( $shipment_status[$key]['pending_status'] ) ) {
				$tracker->ep_status = $shipment_status[$key]['pending_status'];								
			} else if ( isset($shipment_status[$key]['status']) ) {
				$tracker->ep_status = $shipment_status[$key]['status'];
			} else {
				$tracker->ep_status = '';
			}
			
			$tracker->est_delivery_date = isset( $shipment_status[$key]['est_delivery_date'] ) ? $shipment_status[$key]['est_delivery_date'] : '';
						
			if ( isset( $shipment_status[$key]['tracking_events']) || isset($shipment_status[$key]['pending_status'] ) ) {
				if ( isset( $shipment_status[$key]['tracking_events'] ) ) {
					$tracker->tracking_detail = json_encode($shipment_status[$key]['tracking_events']);
				}
				
				if ( isset( $shipment_status[$key]['tracking_destination_events'] ) ) {
					$tracker->tracking_destination_events = json_encode($shipment_status[$key]['tracking_destination_events']);
				}
			}									
			
			$tracking_detail_org = '';	
			$trackind_detail_by_status_rev = '';
			if ( isset( $tracker->tracking_detail ) && 'null' != $tracker->tracking_detail ) {
				$tracking_detail_org = json_decode($tracker->tracking_detail);
				$trackind_detail_by_status_rev = is_array($tracking_detail_org) ? array_reverse($tracking_detail_org) : array();	
			}
			
			$tracking_details_by_date = array();
			
			foreach ( (array) $trackind_detail_by_status_rev as $key => $details ) {
				if ( isset( $details->datetime ) ) {
					$date = gmdate( 'Y-m-d', strtotime($details->datetime) );
					$tracking_details_by_date[$date][] = $details;
				}
			}
			
			$tracking_destination_detail_org = '';	
			$trackind_destination_detail_by_status_rev = '';
			
			if ( isset( $tracker->tracking_destination_events ) && 'null' != $tracker->tracking_destination_events ) {						
				$tracking_destination_detail_org = json_decode($tracker->tracking_destination_events);	
				$trackind_destination_detail_by_status_rev = array_reverse($tracking_destination_detail_org);	
			}
			
			$tracking_destination_details_by_date = array();
			
			foreach ( (array) $trackind_destination_detail_by_status_rev as $key => $details ) {
				if ( isset( $details->datetime ) ) {		
					$date = gmdate( 'Y-m-d', strtotime( $details->datetime ) );
					$tracking_destination_details_by_date[$date][] = $details;
				}
			}	
			
			$order = wc_get_order( $order_id );
			
			if ( isset( $tracker->ep_status ) ) {
				?>
				<div class="shipment-header">
					<?php if ( $total_trackings > 1 ) { ?>
						<p class="shipment_heading">
						<?php /* translators: %s: search for a num and todal tracking */ ?>
						<?php printf( esc_html__( 'Shipment %1$s / %2$s', 'trackship-for-woocommerce' ), esc_html($num), esc_html($total_trackings) ); ?>
						</p>
					<?php } ?>
				</div>
				<div class="tracking-detail col <?php echo !in_array( $tracking_page_layout, array( 't_layout_1', 't_layout_3' ) ) ? 'tracking-layout-2' : ''; ?> ">
					<div class="shipment-content">
						<?php
						
						esc_html_e( $this->tracking_page_header( $order, $tracking_provider, $tracking_number, $tracker, $item, $trackind_detail_by_status_rev ) );
						
						esc_html_e( $this->tracking_progress_bar( $tracker ) );
						
						if ( empty( $trackind_detail_by_status_rev ) ) {
							
							$pending_message = __( 'Tracking information is not available, please try again later.', 'trackship-for-woocommerce' );
							?>
							<p class="pending_message"><?php esc_html_e( apply_filters( 'trackship_pending_status_message', $pending_message, $tracker->ep_status ) ); ?></p>
							<?php
						}
						
						if ( !empty( $trackind_detail_by_status_rev ) ) {
							esc_html_e( $this->layout1_tracking_details( $trackind_detail_by_status_rev, $tracking_details_by_date, $trackind_destination_detail_by_status_rev, $tracking_destination_details_by_date, $tracker , $order_id, $tracking_provider, $tracking_number ) );
						} 
						?>
					</div>
					<div class="trackship_branding">
						<p><span><?php esc_html_e( 'Powered by ', 'trackship-for-woocommerce' ); ?></span><a href="https://track.trackship.com/track/<?php esc_html_e( $tracking_number ); ?>" title="TrackShip" target="blank"><img src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/images/trackship-logo.png"></a></p>
					</div>
					<?php if ( in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ) { ?>
						<style> .trackship_branding{display:block !important;} </style>
					<?php } ?>
				</div>
				<?php
			}
			$num++;
		}
	}
	
	/*
	* Tracking Page Header
	*/
	public function tracking_page_header( $order, $tracking_provider, $tracking_number, $tracker, $item, $trackind_detail_by_status_rev ) {
		$hide_tracking_provider_image = get_option('wc_ast_hide_tracking_provider_image');
		$hide_from_to = get_option('wc_ast_hide_from_to', trackship_admin_customizer()->defaults['wc_ast_hide_from_to'] );
		$hide_last_mile = get_option( 'wc_ast_hide_list_mile_tracking', trackship_admin_customizer()->defaults['wc_ast_hide_list_mile_tracking'] );
		$provider_name = isset( $item[ 'formatted_tracking_provider' ] ) && !empty( $item[ 'formatted_tracking_provider' ] ) ? $item[ 'formatted_tracking_provider' ] : $item[ 'tracking_provider' ] ;
		$provider_image = isset( $item[ 'tracking_provider_image' ] ) ? $item[ 'tracking_provider_image' ] : false ;
		$formatted_tracking_link = isset( $item[ 'formatted_tracking_link' ] ) ? $item[ 'formatted_tracking_link' ] : false ;
		$wc_ast_link_to_shipping_provider = get_option( 'wc_ast_link_to_shipping_provider' );
		
		include 'views/front/tracking_page_header.php';	
	}
	
	public function tracking_progress_bar( $tracker ) {
		
		if ( in_array( $tracker->ep_status, array( 'invalid_tracking', 'carrier_unsupported', 'invalid_user_key', 'invalid_carrier', 'deleted' ) ) ) {
			return;
		}
		
		$tracking_page_layout = get_option( 'wc_ast_select_tracking_page_layout', 't_layout_1' );
		
		if ( in_array( $tracking_page_layout, array( 't_layout_1', 't_layout_3' ) ) ) {
			$width = '0';
		} else {
			if ( in_array( $tracker->ep_status, array( 'pending_trackship', 'pending', 'unknown', 'carrier_unsupported', 'insufficient_balance', 'invalid_carrier', '' ) ) ) {
				$width = '10%';
			} elseif ( in_array( $tracker->ep_status, array( 'in_transit', 'on_hold', 'failure' ) ) ) {
				$width = '30%';
			} elseif ( in_array( $tracker->ep_status, array( 'out_for_delivery', 'available_for_pickup', 'return_to_sender', 'exception' ) ) ) {
				$width = '60%';			
			} elseif ( 'delivered' == $tracker->ep_status ) {
				$width = '100%';
			} elseif ( 'pre_transit' == $tracker->ep_status ) {
				$width = '10%';				
			} else {
				$width = '0';
			}
		}
		if ( 't_layout_4' == $tracking_page_layout && in_array( $tracker->ep_status, array( 'pending_trackship', 'pending', 'unknown', 'carrier_unsupported', 'insufficient_balance', 'invalid_carrier' ) ) ) {
			$width = '10%';
		}
		?>
		<div class="tracker-progress-bar <?php echo in_array( $tracking_page_layout, array( 't_layout_1', 't_layout_3' ) ) ? 'tracking_icon_layout ' : 'tracking_progress_layout'; ?> <?php echo esc_html( $tracking_page_layout ); ?>">
			<div class="progress <?php esc_html_e( $tracker->ep_status ); ?>">
				<div class="progress-bar <?php esc_html_e( $tracker->ep_status ); ?>" style="width: <?php esc_html_e( $width ); ?>;"></div>
				<?php if ( in_array( $tracking_page_layout, array( 't_layout_1', 't_layout_3' ) ) ) { ?>
					<div class="progress-icon icon1"></div>
					<div class="progress-icon icon2"></div>
					<div class="progress-icon icon3"></div>
					<div class="progress-icon icon4"></div>
				<?php } ?>
			</div>
		</div>
	<?php
	}
	
	public function layout1_tracking_details( $trackind_detail_by_status_rev, $tracking_details_by_date, $trackind_destination_detail_by_status_rev, $tracking_destination_details_by_date, $tracker, $order_id, $tracking_provider, $tracking_number ) {  
		$tracking_page_defaults = trackship_admin_customizer();
		$hide_tracking_events = get_option( 'wc_ast_hide_tracking_events', $tracking_page_defaults->defaults[ 'wc_ast_hide_tracking_events' ] );
		$action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '';
		if ( 'get_admin_tracking_widget' == $action ) {
			$hide_tracking_events = 2;
		}
		include 'views/front/layout1_tracking_details.php';
	}		
	
	public function get_products_detail_in_shipment ( $order_id, $tracker, $tracking_provider, $tracking_number ) {
		// echo $order_id;
		$order = wc_get_order( $order_id );		
		$items = $order->get_items();
		$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
		 
		$products = array();
		foreach ( $items as $item_id => $item ) {
			
			$variation_id = $item->get_variation_id();
			$product_id = $item->get_product_id();					
			
			if ( 0 != $variation_id ) {
				$product_id = $variation_id;
			}
			
			$products[$product_id] = array(
				'item_id' => $item_id,
				'product_id' => $product_id,
				'product_name' => $item->get_name(),
				'product_qty' => $item->get_quantity(),
			);
		}
		$products = apply_filters( 'tracking_widget_product_array', $products, $order_id, $tracker, $tracking_provider, $tracking_number );

		//echo '<pre>';print_r($products);echo '</pre>';
		?>
		
			<ul class="tpi_product_tracking_ul">
				<?php
				foreach ( $products as $item_id => $product ) {
					$_product = wc_get_product( $product['product_id'] );
					if ( $_product ) {
						$image_size = array( 50, 50 );
						$image = $_product->get_image( $image_size );
						// echo esc_html($image);
						echo '<li>' . wp_kses_post( $image ) . '<span><a target="_blank" href=' . esc_url( get_permalink( $product['product_id'] ) ) . '>' . esc_html( $product['product_name'] ) . '</a> x ' . esc_html( $product['product_qty'] ) . '</span></li>';
					}
				}
				?>
			</ul>
		
		<style>
		ul.tpi_product_tracking_ul {
			list-style: none;
		}
		ul.tpi_product_tracking_ul li{
			font-size: 14px;
			margin: 0 0 5px;
			border-bottom: 1px solid #ccc;
			padding: 0 0 5px;
		}
		ul.tpi_product_tracking_ul li:last-child{
			border-bottom: 0;
			margin: 0;
			padding: 0;
		}
		ul.tpi_product_tracking_ul li img{
			vertical-align: middle;
		}
		ul.tpi_product_tracking_ul li span{
			margin: 0px 0px 0 10px;
			vertical-align: middle;
		}
		.tpi_products_heading{
			margin-top: -10px;
		}
		</style>
		<?php
	}

	public function get_notifications_option ( $order_id ) {
		if ( get_option( 'enable_email_widget' ) ) {
			$order = wc_get_order( $order_id );
			$receive_email = $order->get_meta( '_receive_shipment_emails', true );
			$receive_email = '' != $receive_email ? $receive_email : 1;

			$receive_sms = $order->get_meta( '_smswoo_receive_sms', true );
			$receive_sms = '' != $receive_sms ? $receive_sms : 1;
			$receive_sms = 'no' == $receive_sms ? 0 : 1;
			?>
			<label>
				<input type="checkbox" class="unsubscribe_emails_checkbox" name="unsubscribe_emails" data-lable="email" value="1" <?php echo $receive_email ? 'checked' : ''; ?>>
				<span style="font-weight: normal;"><?php esc_html_e( 'Email notifications', 'trackship-for-woocommerce' ); ?></span>
			</label>
			<?php if ( class_exists( 'SMS_for_WooCommerce' ) ) { ?>
				<label>
					<input type="checkbox" class="unsubscribe_sms_checkbox" name="unsubscribe_sms" data-lable="sms" value="1" <?php echo $receive_sms ? 'checked' : ''; ?>>
					<span style="font-weight: normal;"><?php esc_html_e( 'SMS notifications', 'trackship-for-woocommerce' ); ?></span>
				</label>
			<?php } ?>
			<?php $ajax_nonce = wp_create_nonce( 'unsubscribe_emails' . $order_id ); ?>
			<input type="hidden" class="order_id_field" value="<?php echo $order_id; ?>">
			<input type="hidden" name="action" value="unsubscribe_emails_save">
			<input type="hidden" name="unsubscribe_emails_nonce" class="unsubscribe_emails_nonce" value="<?php echo esc_html( $ajax_nonce ); ?>"/>

			<?php do_action( 'tracking_page_notifications_tab', $order_id ); ?>
			<?php
		}
	}

	/*
	* Tracking Page preview
	*/
	public static function preview_tracking_page() {
		
		$action = isset( $_REQUEST[ 'action' ] ) ? sanitize_text_field( $_REQUEST[ 'action'] ) : '';
		//echo '<pre>';print_r($_GET);echo '</pre>';
		$type = isset( $_GET["type"] ) ? $_GET["type"] : "" ;
		$status = isset( $_GET["status"] ) ? $_GET["status"] : "" ;
		
		if ( 'preview_tracking_page' != $action ) {
			return;
		}
		
		wp_head();

		show_admin_bar( false );
		
		$tracking_page_defaults = trackship_admin_customizer();
		
		$tracking_page_layout = get_option( 'wc_ast_select_tracking_page_layout', $tracking_page_defaults->defaults['wc_ast_select_tracking_page_layout'] );
		$hide_tracking_events = get_option( 'wc_ast_hide_tracking_events', $tracking_page_defaults->defaults['wc_ast_hide_tracking_events'] );
		$border_color = get_option( 'wc_ast_select_border_color', $tracking_page_defaults->defaults['wc_ast_select_border_color'] );
		$link_color = get_option( 'wc_ast_select_link_color', $tracking_page_defaults->defaults['wc_ast_select_link_color'] );
		$font_color = get_option( 'wc_ast_select_font_color', $tracking_page_defaults->defaults['wc_ast_select_font_color'] );
		$wc_ast_link_to_shipping_provider = get_option( 'wc_ast_link_to_shipping_provider' );
		$hide_tracking_provider_image = get_option( 'wc_ast_hide_tracking_provider_image' );
		$remove_trackship_branding =  get_option( 'wc_ast_remove_trackship_branding' );
		$font_color = get_option( 'wc_ast_select_font_color', $tracking_page_defaults->defaults['wc_ast_select_font_color'] );
		$background_color = get_option( 'wc_ast_select_bg_color' );
		$hide_from_to = get_option('wc_ast_hide_from_to', $tracking_page_defaults->defaults['wc_ast_hide_from_to'] );
		$hide_last_mile = get_option( 'wc_ast_hide_list_mile_tracking', $tracking_page_defaults->defaults['wc_ast_hide_list_mile_tracking'] );
		
		include 'views/front/preview_tracking_page.php';
		wp_footer();
		die();
	}
}
