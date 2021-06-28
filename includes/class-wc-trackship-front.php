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
		add_action( 'wp_enqueue_scripts', array( $this, 'front_styles' ) );		
		add_action( 'wp_ajax_nopriv_get_tracking_info', array( $this, 'get_tracking_info_fun') );
		add_action( 'wp_ajax_get_tracking_info', array( $this, 'get_tracking_info_fun') );
		
		add_action( 'plugins_loaded', array( $this, 'on_plugin_loaded' ) );
		
		add_action( 'woocommerce_view_order', array( $this, 'show_tracking_page_widget' ), 5, 1 );
	}
	
	public function on_plugin_loaded() {
		
		if ( function_exists( 'wc_advanced_shipment_tracking' ) && !function_exists( 'ast_pro' ) ) {
			remove_action( 'woocommerce_view_order', array( wc_advanced_shipment_tracking()->actions, 'show_tracking_info_order' ) );
		}
		
		if ( function_exists( 'ast_pro' ) && isset( ast_pro()->ast_pro_actions ) ) {
			remove_action( 'woocommerce_view_order', array( ast_pro()->ast_pro_actions, 'show_tracking_info_order' ) );
		}
		
		if ( function_exists( 'wc_shipment_tracking' ) ) {
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
			), 'woocommerce-advanced-shipment-tracking/', trackship_for_woocommerce()->get_plugin_path() . '/templates/' );
		}
	}
	
	/**
	 * Show tracking page widget
	**/
	public function show_tracking_page_widget( $order_id ) {
		$order = wc_get_order( $order_id );
		$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
		$shipment_status = get_post_meta( $order->get_id(), 'shipment_status', true );
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

		if ( 'preview_tracking_page' == $action ) {
			wp_enqueue_style( 'front_style' );
			wp_enqueue_script( 'front-js' );
		}
	}
	
	/**
	 * Return tracking details or tracking form for shortcode - [wcast-track-order]
	*/
	public function woo_track_order_function() {
		
		wp_enqueue_style( 'front_style' );
		wp_enqueue_script( 'jquery-blockui' );
		wp_enqueue_script( 'front-js' );	
		
		$wc_ast_api_key = get_option('wc_ast_api_key');	
		
		if ( !$wc_ast_api_key ) { ?>
			<p><a href="https://trackship.info/" target="blank">TrackShip</a> is not active.</p>
			<?php
			return;
		}
		
		if ( isset( $_GET['order_id'] ) &&  isset( $_GET['order_key'] ) ) {
			
			$order_id = wc_clean($_GET['order_id']);
			
			$order = wc_get_order( $order_id );
			
			if ( empty( $order ) ) {
				return;
			}
			
			$order_key = $order->get_order_key();
		
			if ( $order_key != $_GET['order_key'] ) {
				return;
			}
			
			$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
			$shipment_status = get_post_meta( $order_id, 'shipment_status', true );
			if ( !$tracking_items ) {
				unset( $order_id );
			}
		}
	
		if ( ! isset( $order_id ) ) {
			ob_start();		
			$this->track_form_template();
			$form = ob_get_clean();	
			return $form;
		} else {
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
		
		$wast = WC_Advanced_Shipment_Tracking_Actions::get_instance();
		$order_id = $wast->get_formated_order_id($order_id);
		
		$order = wc_get_order( $order_id );
		
		if ( empty( $order ) ) {
			ob_start();		
			$this->track_form_template();
			$form = ob_get_clean();
			echo json_encode( array('success' => 'false', 'message' => __( 'Order not found.', 'trackship-for-woocommerce' ), 'html' => $form ));
			die();	
		}
		
		$order_id = $wast->get_formated_order_id($order_id);									
		$order_email = $order->get_billing_email();
		
		if ( strtolower( $order_email ) != strtolower( $email ) ) {
			ob_start();		
			$this->track_form_template();
			$form = ob_get_clean();	
			echo json_encode( array('success' => 'false', 'message' => __( 'Order not found.', 'trackship-for-woocommerce' ), 'html' => $form ));
			die();	
		}
		
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$tracking_items = get_post_meta( $order_id, '_wc_shipment_tracking_items', true );			
		} else {			
			$tracking_items = $order->get_meta( '_wc_shipment_tracking_items', true );			
		} 
		
		$shipment_status = get_post_meta( $order_id, 'shipment_status', true);
		
		if ( !$tracking_items ) {
			ob_start();		
			$this->track_form_template();
			$form = ob_get_clean();
			echo json_encode( array('success' => 'false', 'message' => __( 'Tracking details not found', 'trackship-for-woocommerce' ), 'html' => $form ));
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
	
	/*
	* retuern Tracking page HTML
	*/
	public function display_tracking_page( $order_id, $tracking_items, $shipment_status ) {
		
		wp_enqueue_style( 'front_style' );
		wp_enqueue_script( 'jquery-blockui' );
		wp_enqueue_script( 'front-js' );	
		
		global $wpdb;
		
		$ts_tracking_page_customizer = new TSWC_Tracking_Page_Customizer();		
		
		$border_color = get_option('wc_ast_select_border_color', $ts_tracking_page_customizer->defaults['wc_ast_select_border_color'] );
		$background_color = get_option('wc_ast_select_bg_color', $ts_tracking_page_customizer->defaults['wc_ast_select_bg_color'] );
		$font_color = get_option('wc_ast_select_font_color', $ts_tracking_page_customizer->defaults['wc_ast_select_font_color'] );
		$hide_tracking_events = get_option('wc_ast_hide_tracking_events', $ts_tracking_page_customizer->defaults['wc_ast_hide_tracking_events'] );
		$tracking_page_layout = get_option('wc_ast_select_tracking_page_layout', $ts_tracking_page_customizer->defaults['wc_ast_select_tracking_page_layout'] );
		$remove_trackship_branding =  get_option('wc_ast_remove_trackship_branding', $ts_tracking_page_customizer->defaults['wc_ast_remove_trackship_branding'] );
		?>
		
		<style>					
			<?php if ( $border_color ) { ?>
				body .col.tracking-detail{
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
			<?php } ?>
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
			.woocommerce-account.woocommerce-view-order .tracking-header span.wc_order_id {display: none;}
		</style>
		<?php
		
		$num = 1;
		$total_trackings = sizeof( $tracking_items );
		
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
				$trackind_detail_by_status_rev = array_reverse($tracking_detail_org);	
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
                            <?php printf( esc_html__( 'Shipment %1$s out of %2$s', 'trackship-for-woocommerce' ), esc_html($num), esc_html($total_trackings) ); ?>
                            </p>
                        <?php } ?>
                    </div>
					<div class="tracking-detail col <?php echo 't_layout_1' != $tracking_page_layout ? 'tracking-layout-2' : ''; ?> ">
                    	<div class="shipment-content">
						<?php
						
						esc_html_e( $this->tracking_page_header( $order, $tracking_provider, $tracking_number, $tracker, $item ) );
						
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
					<?php if ( ! $remove_trackship_branding ) { ?>
						<div class="trackship_branding">
							<p><a href="https://trackship.info/trackings/?number=<?php esc_html_e( $tracking_number ); ?>" title="TrackShip" target="blank"><img src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/images/trackship-logo.png"></a></p>
						</div>
					<?php } ?>
				</div>
			<?php }
			$num++;
		}	
	}
	
	/*
	* Tracking Page Header
	*/
	public function tracking_page_header( $order, $tracking_provider, $tracking_number, $tracker, $item ) {
		$hide_tracking_provider_image = get_option('wc_ast_hide_tracking_provider_image');
		$provider_name = isset( $item[ 'formatted_tracking_provider' ] ) && !empty( $item[ 'formatted_tracking_provider' ] ) ? $item[ 'formatted_tracking_provider' ] : $item[ 'tracking_provider' ] ;
		$provider_image = isset( $item[ 'tracking_provider_image' ] ) ? $item[ 'tracking_provider_image' ] : false ;
		$formatted_tracking_link = isset( $item[ 'formatted_tracking_link' ] ) ? $item[ 'formatted_tracking_link' ] : false ;
		$wc_ast_link_to_shipping_provider = get_option( 'wc_ast_link_to_shipping_provider' );
		
		include 'views/front/tracking_page_header.php';	
	}
	
	public function tracking_progress_bar( $tracker ) {
		
		if ( in_array( $tracker->ep_status, array( 'INVALID_TRACKING_NUM', 'carrier_unsupported', 'invalid_user_key', 'wrong_shipping_provider', 'deleted', 'pending' ) ) ) {
			return;
		}
		
		if ( in_array( $tracker->ep_status, array( 'not_shipped', 'pending_trackship', 'pending', 'unknown', 'carrier_unsupported', 'balance_zero' ) ) ) {
			$width = '17%';
		} elseif ( in_array( $tracker->ep_status, array( 'in_transit', 'on_hold' ) ) ) {
			$width = '33%';
		} elseif ( 'out_for_delivery' == $tracker->ep_status ) {
			$width = '67%';				
		} elseif ( 'available_for_pickup' == $tracker->ep_status ) {
			$width = '67%';				
		} elseif ( 'return_to_sender' == $tracker->ep_status ) {
			$width = '67%';				
		} elseif ( 'delivered' == $tracker->ep_status ) {
			$width = '100%';				
		} else {
			$width = '0';
		}
		$tracking_page_layout = get_option( 'wc_ast_select_tracking_page_layout', 't_layout_1' );
		?>
		<div class="tracker-progress-bar <?php esc_html_e( 't_layout_1' == $tracking_page_layout ? 'tracking_layout_1' : '' ); ?>">
			<div class="progress">
				<div class="progress-bar <?php esc_html_e( $tracker->ep_status ); ?>" style="width: <?php esc_html_e( $width ); ?>;"></div>
			</div>
		</div>
	<?php
	}
	
	public function layout1_tracking_details( $trackind_detail_by_status_rev, $tracking_details_by_date, $trackind_destination_detail_by_status_rev, $tracking_destination_details_by_date, $tracker, $order_id, $tracking_provider, $tracking_number ) {  
		$ts_tracking_page_customizer = new TSWC_Tracking_Page_Customizer();
		$hide_tracking_events = get_option( 'wc_ast_hide_tracking_events', $ts_tracking_page_customizer->defaults[ 'wc_ast_hide_tracking_events' ] );
		include 'views/front/layout1_tracking_details.php';		
	}		
	
	/**
	 * Convert string to date
	*/
	public static function convertString( $date ) { 
		// convert date and time to seconds 
		$sec = strtotime($date); 
  
		// convert seconds into a specific format 
		$date = gmdate('m/d/Y H:i', $sec); 
  
		// print final date and time 
		return $date; 
	}
	
	/*
	* Tracking Page preview
	*/
	public static function preview_tracking_page() {
		
		$action = isset( $_REQUEST[ 'action' ] ) ? sanitize_text_field( $_REQUEST[ 'action'] ) : '';
		
		if ( 'preview_tracking_page' != $action ) {
			return;
		}
		
		wp_head();
		
		$ts_tracking_page_customizer = new TSWC_Tracking_Page_Customizer();
		
		$tracking_page_layout = get_option( 'wc_ast_select_tracking_page_layout', $ts_tracking_page_customizer->defaults['wc_ast_select_tracking_page_layout'] );
		$hide_tracking_events = get_option( 'wc_ast_hide_tracking_events', $ts_tracking_page_customizer->defaults['wc_ast_hide_tracking_events'] );
		$border_color = get_option( 'wc_ast_select_border_color', $ts_tracking_page_customizer->defaults['wc_ast_select_border_color'] );
		$font_color = get_option( 'wc_ast_select_font_color', $ts_tracking_page_customizer->defaults['wc_ast_select_font_color'] );
		$wc_ast_link_to_shipping_provider = get_option( 'wc_ast_link_to_shipping_provider' );
		$hide_tracking_provider_image = get_option( 'wc_ast_hide_tracking_provider_image' );
		$remove_trackship_branding =  get_option( 'wc_ast_remove_trackship_branding' );
		$background_color = get_option( 'wc_ast_select_bg_color' );
		
		include 'views/front/preview_tracking_page.php';
		wp_footer();
		die();
	}
}
