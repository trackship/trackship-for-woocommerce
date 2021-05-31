<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Trackship_Actions {
	
	/**
	 * Initialize the main plugin function
	*/
	public function __construct() {
		
	}
	
	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	*/
	private static $instance;		
	
	/**
	 * Get the class instance
	 *
	 * @since  1.0
	 * @return smswoo_license
	*/
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	
	/*
	 * init function
	 *
	 * @since  1.0
	*/
	public function init() {	
		
		//load trackship css js 
		add_action( 'admin_enqueue_scripts', array( $this, 'trackship_styles' ), 100 );
		
		//ajax save admin trackship settings
		add_action( 'wp_ajax_wc_ast_trackship_form_update', array( $this, 'wc_ast_trackship_form_update_callback' ) );
		add_action( 'wp_ajax_trackship_tracking_page_form_update', array( $this, 'trackship_tracking_page_form_update_callback' ) );
		add_action( 'wp_ajax_ts_late_shipments_email_form_update', array( $this, 'ts_late_shipments_email_form_update_callback' ) );
		
		$api_enabled = get_option( 'wc_ast_api_enabled', 0 );
		
		if ( true == $api_enabled ) {
			//add Shipment status column after tracking
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'wc_add_order_shipment_status_column_header'), 20 );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'wc_add_order_shipment_status_column_content') );
			
			//add bulk action - get shipment status
			add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_bulk_actions_get_shipment_status'), 10, 1 );
			
			// Make the action from selected orders to get shipment status
			add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'get_shipment_status_handle_bulk_action_edit_shop_order'), 10, 3 );
			
			// Bulk shipment status sync ajax call from settings
			add_action( 'wp_ajax_bulk_shipment_status_from_settings', array( $this, 'bulk_shipment_status_from_settings_fun' ) );
			
			// The results notice from bulk action on orders
			add_action( 'admin_notices', array( $this, 'shipment_status_bulk_action_admin_notice' ) );
			
			// add 'get_shipment_status' order meta box order action
			add_action( 'woocommerce_order_actions', array( $this, 'add_order_meta_box_get_shipment_status_actions' ) );
			add_action( 'woocommerce_order_action_get_shipment_status_edit_order', array( $this, 'process_order_meta_box_actions_get_shipment_status' ) );
			
			// add bulk order filter for exported / non-exported orders
			if ( get_option( 'wc_ast_show_shipment_status_filter' ) ) {
				add_action( 'restrict_manage_posts', array( $this, 'filter_orders_by_shipment_status') , 20 );
				add_filter( 'request', array( $this, 'filter_orders_by_shipment_status_query' ) );
			}
			
			add_action( 'wp_dashboard_setup', array( $this, 'ast_add_dashboard_widgets') );	
		}
		
		// trigger when order status changed to shipped or completed
		
		// filter for shipment status
		add_filter( 'trackship_status_filter', array($this, 'trackship_status_filter_func' ), 10 , 1 );
		
		// filter for shipment status icon
		add_filter( 'trackship_status_icon_filter', array( $this, 'trackship_status_icon_filter_func' ), 10 , 2 );
		
		add_action( 'wp_ajax_update_shipment_status_email_status', array( $this, 'update_shipment_status_email_status_fun') );
	
		add_action( 'ast_shipment_tracking_end', array( $this, 'display_shipment_tracking_info'), 10, 2 );
		
		add_action( 'delete_tracking_number_from_trackship', array( $this, 'delete_tracking_number_from_trackship'), 10, 3 );
		
		//fix shipment tracking for deleted tracking
		add_action( 'fix_shipment_tracking_for_deleted_tracking', array( $this, 'func_fix_shipment_tracking_for_deleted_tracking' ), 10, 3 );
				
		add_action( 'admin_footer', array( $this, 'footer_function'), 1 );
		
		// if trackship is connected
		if ( ! $this->get_trackship_key() ) {
			return;
		}
		
		//filter in shipped orders
		add_filter( 'is_order_shipped', array( $this, 'check_order_status' ), 5, 2 );
		add_filter( 'is_order_shipped', array( $this, 'check_tracking_exist' ), 10, 2 );
		
		// CSV / manually
		add_action( 'send_order_to_trackship', array( $this, 'schedule_while_adding_tracking' ), 10, 1 );
		
		//run cron action
		add_action( 'wcast_retry_trackship_apicall', array( $this, 'trigger_trackship_apicall' ) );
		
		$valid_order_statuses = get_option( 'trackship_trigger_order_statuses', array() );
		foreach( $valid_order_statuses as $order_status ){
			// trigger Trackship for spacific order
			add_action( 'woocommerce_order_status_' . $order_status, array( $this, 'schedule_when_order_status_changed' ), 8, 2 );
		}
	}
	
	/**
	* Load trackship styles.
	*/
	public function trackship_styles( $hook ) {
		$screen = get_current_screen(); 
		
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		
		wp_register_style( 'trackshipcss', trackship_for_woocommerce()->plugin_dir_url() . 'assets/css/trackship.css', array(), trackship_for_woocommerce()->version );
		wp_register_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
		
		wp_register_style( 'smswoo_ts', trackship_for_woocommerce()->plugin_dir_url() . 'assets/css/smswoo_ts.css', array(), trackship_for_woocommerce()->version );
		wp_register_script( 'smswoo_ts', trackship_for_woocommerce()->plugin_dir_url() . 'assets/js/smswoo_ts.js', array( 'jquery', 'wp-util' ), trackship_for_woocommerce()->version );
		
		wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ), WC_VERSION, true );
		wp_register_script( 'jquery-blockui', WC()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'trackship_script', trackship_for_woocommerce()->plugin_dir_url() . 'assets/js/trackship.js', array( 'jquery', 'wp-util' ), trackship_for_woocommerce()->version );
		
		wp_localize_script( 'trackship_script', 'trackship_script', array(
			'i18n' => array(				
				'data_saved'	=> __( 'Your settings have been successfully saved.', 'trackship-for-woocommerce' ),				
			),
		) );
		
		if ( 'shop_order' === $screen->post_type ) {
			wp_enqueue_style( 'trackshipcss' );
			wp_enqueue_script( 'trackship_script' );
			
			//front_style for tracking widget
			wp_register_style( 'front_style', trackship_for_woocommerce()->plugin_dir_url() . 'assets/css/front.css', array(), trackship_for_woocommerce()->version );
			wp_enqueue_style( 'front_style' );
		}
		
		$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		
		if ( 'trackship-for-woocommerce' != $page ) {
			return;
		}				
					
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_style( 'trackshipcss' );
		
		if ( !class_exists( 'SMS_for_WooCommerce' ) ) {
			wp_enqueue_style( 'smswoo_ts' );
			wp_enqueue_script( 'smswoo_ts' );
		}
		
		wp_enqueue_script( 'wp-color-picker' );	
		wp_enqueue_script( 'jquery-tiptip' );
		wp_enqueue_script( 'jquery-blockui' );
		
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';	

		wp_register_script( 'select2', WC()->plugin_url() . '/assets/js/select2/select2.full' . $suffix . '.js', array( 'jquery' ), '4.0.3' );
		wp_enqueue_script( 'select2');
		
		wp_register_script( 'selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full' . $suffix . '.js', array( 'jquery' ), '1.0.4' );
		wp_register_script( 'wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select' . $suffix . '.js', array( 'jquery', 'selectWoo' ), WC_VERSION );
		
		wp_enqueue_script( 'selectWoo');
		wp_enqueue_script( 'wc-enhanced-select');
		
		wp_enqueue_script( 'trackship_script' );
	}
	
	/*
	* settings form save
	*/
	public function wc_ast_trackship_form_update_callback() {
		
		if ( ! empty( $_POST ) && check_admin_referer( 'wc_ast_trackship_form', 'wc_ast_trackship_form_nonce' ) ) {
			
			$admin = WC_Trackship_Admin::get_instance();
			
			$data = $this->get_delivered_data();								
						
			foreach ( $data as $key => $val ) {
				if ( 'wcast_enable_delivered_email' == $key ) {					
					if ( isset( $_POST['wcast_enable_delivered_email'] ) ) {											
						
						if ( 1 == $_POST['wcast_enable_delivered_email'] ) {
							update_option( 'customizer_delivered_order_settings_enabled', wc_clean( $_POST['wcast_enable_delivered_email'] ) );
							$enabled = 'yes';
						} else {
							update_option( 'customizer_delivered_order_settings_enabled', '');	
							$enabled = 'no';
						}
						
						$wcast_enable_delivered_email = get_option('woocommerce_customer_delivered_order_settings'); 
						$wcast_enable_delivered_email['enabled'] = $enabled;												
						
						update_option( 'woocommerce_customer_delivered_order_settings', $wcast_enable_delivered_email );	
					}	
				}
				
				if ( isset( $_POST[ $key ] ) ) {						
					update_option( $key, wc_clean($_POST[ $key ]) );
				}	
			}
			
			$data2 = $admin->get_trackship_general_data();
			foreach ( $data2 as $key2 => $val2 ) {
				if ( 'multiple_select' == $val2[ 'type' ] ) {
					$posted_val = isset( $_POST[ $key2 ] ) ? wc_clean( $_POST[ $key2 ] ) : array();
					update_option( $key2, $posted_val );
				} else {
					update_option( $key2, wc_clean( $_POST[ $key2 ] ) );
				}
			}
			
			echo json_encode( array('success' => 'true') );
			die();

		}
	}
	
	/*
	* tracking page form save
	*/
	public function trackship_tracking_page_form_update_callback() {
		if ( ! empty( $_POST ) && check_admin_referer( 'trackship_tracking_page_form', 'trackship_tracking_page_form_nonce' ) ) {
			
			$admin = WC_Trackship_Admin::get_instance();
			$data1 = $admin->get_tracking_page_data();
			
			foreach ( $data1 as $key1 => $val1 ) {
				
				if ( 'button' == $val1[ 'type' ] ) {
					continue;
				}
				$post_key1 = isset( $_POST[ $key1 ] ) ? sanitize_text_field( $_POST[ $key1 ] ) : '';
				update_option( $key1, sanitize_text_field( $post_key1 ) );
			}
			
			wp_send_json( array('success' => 'true') );
		}
	}
	
	/*
	* late shipmenta form save
	*/
	public function ts_late_shipments_email_form_update_callback() {
		echo 'ts_late_shipments_email_form_update_callback';
		if ( ! empty( $_POST ) && check_admin_referer( 'ts_late_shipments_email_form', 'ts_late_shipments_email_form_nonce' ) ) {
			
			$wcast_late_shipments_days = isset( $_POST['wcast_late_shipments_days'] ) ? sanitize_text_field( $_POST['wcast_late_shipments_days'] ) : '';
			$wcast_late_shipments_email_to = isset( $_POST['wcast_late_shipments_email_to'] ) ? sanitize_text_field( $_POST['wcast_late_shipments_email_to'] ) : '';			
			$wcast_late_shipments_email_subject = isset( $_POST['wcast_late_shipments_email_subject'] ) ? sanitize_text_field( $_POST['wcast_late_shipments_email_subject'] ) : '';			
			$wcast_late_shipments_email_content = isset( $_POST['wcast_late_shipments_email_content'] ) ? sanitize_text_field( $_POST['wcast_late_shipments_email_content'] ) : '';
			$wcast_late_shipments_trigger_alert = isset( $_POST['wcast_late_shipments_trigger_alert'] ) ? sanitize_text_field( $_POST['wcast_late_shipments_trigger_alert'] ) : '';			
			$wcast_late_shipments_daily_digest_time = isset( $_POST['wcast_late_shipments_daily_digest_time'] ) ? sanitize_text_field( $_POST['wcast_late_shipments_daily_digest_time'] ) : '';
			$wcast_enable_late_shipments_admin_email = isset( $_POST['wcast_enable_late_shipments_admin_email'] ) ? sanitize_text_field( $_POST['wcast_enable_late_shipments_admin_email'] ) : '';

			$late_shipments_email_settings = array(
				'wcast_enable_late_shipments_admin_email' => $wcast_enable_late_shipments_admin_email,
				'wcast_late_shipments_days' => $wcast_late_shipments_days,
				'wcast_late_shipments_email_to' => $wcast_late_shipments_email_to,
				'wcast_late_shipments_email_subject' => $wcast_late_shipments_email_subject,
				'wcast_late_shipments_email_content' => $wcast_late_shipments_email_content,
				'wcast_late_shipments_trigger_alert' => $wcast_late_shipments_trigger_alert,
				'wcast_late_shipments_daily_digest_time' => $wcast_late_shipments_daily_digest_time,
			);
			
			update_option( 'late_shipments_email_settings', $late_shipments_email_settings );
			
			$Late_Shipments = new WC_TrackShip_Late_Shipments();
			$Late_Shipments->remove_cron();
			$Late_Shipments->setup_cron();
		}
	}

	/*
	* get settings tab array data
	* return array
	*/
	public function get_delivered_data() {		
		$form_data = array(			
			'wc_ast_status_delivered' => array(
				'type'		=> 'checkbox',
				'title'		=> __( 'Enable custom order status “Delivered"', '' ),				
				'show'		=> true,
				'class'     => '',
			),			
			'wc_ast_status_label_color' => array(
				'type'		=> 'color',
				'title'		=> __( 'Delivered Label color', '' ),				
				'class'		=> 'status_label_color_th',
				'show'		=> true,
			),
			'wc_ast_status_label_font_color' => array(
				'type'		=> 'dropdown',
				'title'		=> __( 'Delivered Label font color', '' ),
				'options'   => array( 
									'' =>__( 'Select', 'woocommerce' ),
									'#fff' =>__( 'Light', '' ),
									'#000' =>__( 'Dark', '' ),
								),			
				'class'		=> 'status_label_color_th',
				'show'		=> true,
			),
			'wcast_enable_delivered_email' => array(
				'type'		=> 'checkbox',
				'title'		=> __( 'Enable the Delivered order status email', '' ),				
				'class'		=> 'status_label_color_th',
				'show'		=> true,
			),				
		);
		return $form_data;

	}	
	
	/**
	 * Adds 'shipment_status' column header to 'Orders' page immediately after 'woocommerce-advanced-shipment-tracking' column.
	 *
	 * @param string[] $columns
	 * @return string[] $new_columns
	 */
	public function wc_add_order_shipment_status_column_header( $columns ) {
		wp_enqueue_style( 'trackshipcss' );
		wp_enqueue_script( 'trackship_script' );
		
		//front_style for tracking widget
		wp_register_style( 'front_style', trackship_for_woocommerce()->plugin_dir_url() . 'assets/css/front.css', array(), trackship_for_woocommerce()->version );
		wp_enqueue_style( 'front_style' );
		
		$columns['shipment_status'] = __( 'Shipment status', 'trackship-for-woocommerce' );
		return $columns;
	}
	
	/**
	 * Adds 'shipment_status' column content to 'Orders' page.
	 *
	 * @param string[] $column name of column being displayed
	 */
	public function wc_add_order_shipment_status_column_content( $column ) {		
		global $post;
		
		if ( 'shipment_status' === $column ) {
						
			$tracking_items = trackship_for_woocommerce()->get_tracking_items( $post->ID );
			$shipment_status = get_post_meta( $post->ID, 'shipment_status', true);				
			$wp_date_format = get_option( 'date_format' );
			if ( 'd/m/Y' == $wp_date_format ) {
				$date_format = 'd/m'; 
			} else {
				$date_format = 'm/d';
			}

			if ( count( $tracking_items ) > 0 ) {
				?>
					<ul class="wcast-shipment-status-list">
						<?php
						foreach ( $tracking_items as $key => $tracking_item ) { 
							if ( !isset( $shipment_status[$key] ) ) {
								echo '<li class="tracking-item-';
								esc_html_e( $tracking_item['tracking_id'] );
								echo '">-</li>';
								continue;
							}
							$has_est_delivery = false;
							
							if ( isset( $shipment_status[$key]['pending_status'] ) ) {
								$status = $shipment_status[$key]['pending_status'];
							} else {
								$status = $shipment_status[$key]['status'];	
							}
							
							$status_date = $shipment_status[$key]['status_date'];
							
							if ( isset( $shipment_status[$key]['est_delivery_date'] ) ) {
								$est_delivery_date = $shipment_status[$key]['est_delivery_date'];
							}
							
							if ( 'delivered' != $status && 'return_to_sender' != $status && !empty($est_delivery_date) ) {
								$has_est_delivery = true;
							}
							?>
							<li id="shipment-item-<?php esc_html_e( $tracking_item['tracking_id'] ); ?>" class="tracking-item-<?php esc_html_e( $tracking_item['tracking_id'] ); ?>" >                            	
								<div class="ast-shipment-status shipment-<?php esc_html_e( sanitize_title($status) ); ?> has_est_delivery_<?php esc_html_e( $has_est_delivery ? 1 : 0 ); ?>">

									<span class="shipment-icon icon-default icon-<?php esc_html_e( $status ); ?>">
										<span class="ast-shipment-tracking-status"><?php esc_html_e( apply_filters( 'trackship_status_filter', $status ) ); ?></span>
											<?php if ( '' != $status_date ) { ?>
                                                <span class="showif_has_est_delivery_0 ft11">Updated <?php esc_html_e( gmdate( $date_format, strtotime($status_date) ) ); ?> 
													<a class="ts4wc_track_button ft12 open_tracking_details" data-orderid="<?php esc_html_e( $post->ID ); ?>" data-tracking_id="<?php esc_html_e( $tracking_item['tracking_id'] ); ?>" data-nonce=<?php esc_html_e( wp_create_nonce( 'tswc-' . $post->ID ) ); ?> >Track</a>
                                                   
                                                    <?php if ( 'pending_trackship' == $status ) { ?>
                                                    	<a href="javascript:;" class="trackship-tip" title="Pending TrackShip is a temporary status that will display for a few minutes until we update the order with the first tracking event from the shipping provider. Please refresh the orders admin in 2-3 minutes." >more info</a>
                                                    <?php } ?>
                                                    <?php if ( in_array( $status, array( 'carrier_unsupported', 'wrong_shipping_provider', 'INVALID_TRACKING_NUM') ) ) { ?>
                                                    	<a href="https://trackship.info/docs/trackship-resources/shipment-tracking-status-reference/#trackship-status-messages" target="_blank">more info</a>
                                                    <?php } ?>
                                                    <?php if ( 'connection_issue' == $status ) { ?>
                                                    	<a href="https://trackship.info/docs/trackship-for-woocommerce/connect-trackship-to-your-store/#requirements" target="_blank">more info</a>
                                                    <?php } ?>
                                                </span>
                                            <?php } ?>
											<?php if ( $has_est_delivery ) { ?>
                                                <span class="wcast-shipment-est-delivery ft11">Est. Delivery(<?php esc_html_e( gmdate( $date_format, strtotime($est_delivery_date) ) ); ?>) <a class="ts4wc_track_button ft12 open_tracking_details" data-orderid="<?php esc_html_e( $post->ID ); ?>" data-tracking_id="<?php esc_html_e( $tracking_item['tracking_id'] ); ?>" data-nonce=<?php esc_html_e( wp_create_nonce( 'tswc-' . $post->ID ) ); ?> > Track</a></span>
                                            <?php } ?>
                                            
									</span>
								</div>
							</li>
						<?php } ?>
					</ul>
				<?php
			} else {
				echo '–';
			}
		}
	}
	
	/*
	* add bulk action
	* Change order status to delivered
	*/
	public function add_bulk_actions_get_shipment_status( $bulk_actions ) {
		$bulk_actions['get_shipment_status'] = 'Get Shipment Status';
		return $bulk_actions;
	}
	
	/*
	* order bulk action for get shipment status
	*/
	public function get_shipment_status_handle_bulk_action_edit_shop_order( $redirect_to, $action, $post_ids ) {
		
		if ( 'get_shipment_status' !== $action ) {
			return $redirect_to;
		}
	
		$processed_ids = array();
		
		$order_count = count($post_ids);
		
		foreach ( $post_ids as $post_id ) {
			
			$this->schedule_trackship_trigger( $post_id );
			$processed_ids[] = $post_id;
			
		}
	
		$redirect_to = add_query_arg( array(
			'get_shipment_status' => '1',
			'processed_count' => count( $processed_ids ),
			'processed_ids' => implode( ',', $processed_ids ),
		), $redirect_to );
		return $redirect_to;
	}
	
	/*
	* bulk shipment status action for completed order with tracking details and without shipment status
	*/
	public function bulk_shipment_status_from_settings_fun() {
		$args = array(
			'status' => 'wc-completed',
			'limit'	 => 100,	
			'date_created' => '>' . ( time() - 2592000 ),
		);		
		$orders = wc_get_orders( $args );		
		foreach ( $orders as $order ) {
			$order_id = $order->get_id();
			
			$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
			
			if ( $tracking_items ) {
				$shipment_status = get_post_meta( $order_id, 'shipment_status', true);				
				foreach ( $tracking_items as $key => $tracking_item ) { 
					
					//bulk shipment status action for completed order with tracking details and without shipment status
					if ( !isset( $shipment_status[$key] ) ) {
						$this->schedule_trackship_trigger( $order_id );
					}
					
					//bulk shipment status action for "TrackShip balance is 0" status
					if ( isset( $shipment_status[$key]['pending_status'] ) && 'TrackShip balance is 0' == $shipment_status[$key]['pending_status'] ) {
						$this->schedule_trackship_trigger( $order_id );
					}
					
					//bulk shipment status action for "TrackShip balance is 0" status
					if ( isset( $shipment_status[$key]['pending_status'] ) && 'TrackShip connection issue' == $shipment_status[$key]['pending_status'] ) {
						$this->schedule_trackship_trigger( $order_id );
					}
				}									
			}			
		}
		$url = admin_url('/edit.php?post_type=shop_order');		
		echo esc_url( $url );
		die();		
	}
	
	/*
	* The results notice from bulk action on orders
	*/
	public function shipment_status_bulk_action_admin_notice() {
		if ( empty( $_REQUEST['get_shipment_status'] ) ) {
			return; // Exit
		}
	
		//$count = intval( $_REQUEST['processed_count'] );
		
		echo '<div id="message" class="updated fade"><p>';
		esc_html_e( 'The shipment status updates will run in the background, please refresh the page in a few minutes.', 'trackship-for-woocommerce' );
		echo '</p></div>';
	}

	/**
	 * Add 'get_shipment_status' link to order actions select box on edit order page
	 *
	 * @since 1.0
	 * @param array $actions order actions array to display
	 * @return array
	 */
	public function add_order_meta_box_get_shipment_status_actions( $actions ) {

		// add download to CSV action
		$actions['get_shipment_status_edit_order'] = __( 'Get Shipment Status', 'trackship-for-woocommerce' );
		return $actions;
	}

	/*
	* order details meta box action
	*/
	public function process_order_meta_box_actions_get_shipment_status( $order ) {
		$this->trigger_trackship_apicall( $order->get_id() );
	}	
	
	/**
	 * Add bulk filter for Shipment status in orders list
	 *
	 * @since 2.4
	 */
	public function filter_orders_by_shipment_status() {
		global $typenow;

		if ( 'shop_order' === $typenow ) {

			$terms = array(
				'pending_trackship' => (object) array( 'term' => __( 'Pending TrackShip', 'trackship-for-woocommerce' ) ),
				'unknown' => (object) array( 'term' => __( 'Unknown', 'trackship-for-woocommerce' ) ),
				'pre_transit' => (object) array( 'term' => __( 'Pre Transit', 'trackship-for-woocommerce' ) ),
				'in_transit' => (object) array( 'term' => __( 'In Transit', 'trackship-for-woocommerce' ) ),
				'available_for_pickup' => (object) array( 'term' => __( 'Available For Pickup', 'trackship-for-woocommerce' ) ),
				'out_for_delivery' => (object) array( 'term' => __( 'Out For Delivery', 'trackship-for-woocommerce' ) ),
				'delivered' => (object) array( 'term' => __( 'Delivered', 'trackship-for-woocommerce' ) ),
				'failure' => (object) array( 'term' => __( 'Failed Attempt', 'trackship-for-woocommerce' ) ),
				'cancelled' => (object) array( 'term' => __( 'Cancelled', 'woocommerce' ) ),
				'carrier_unsupported' => (object) array( 'term' => __( 'Carrier Unsupported', 'trackship-for-woocommerce' ) ),
				'return_to_sender' => (object) array( 'term' => __( 'Return To Sender', 'trackship-for-woocommerce' ) ),				
				'INVALID_TRACKING_NUM' => (object) array( 'term' => __( 'Invalid Tracking Number', 'trackship-for-woocommerce' ) ),
			);

			?>
			<select name="_shop_order_shipment_status" id="dropdown_shop_order_shipment_status">
				<option value=""><?php esc_html_e( 'Filter by shipment status', 'trackship-for-woocommerce' ); ?></option>
				<?php foreach ( $terms as $value => $term ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php echo esc_attr( isset( $_GET['_shop_order_shipment_status'] ) ? selected( $value, sanitize_text_field( $_GET['_shop_order_shipment_status'] ), false ) : '' ); ?>>
					<?php printf( '%1$s', esc_html( $term->term ) ); ?>
				</option>
				<?php endforeach; ?>
			</select>
			<?php
		}
	}
	
	/**
	 * Process bulk filter action for shipment status orders
	 *
	 * @since 3.0.0
	 * @param array $vars query vars without filtering
	 * @return array $vars query vars with (maybe) filtering
	 */
	public function filter_orders_by_shipment_status_query( $vars ) {
		global $typenow;		
		if ( 'shop_order' === $typenow && isset( $_GET['_shop_order_shipment_status'] ) && '' != $_GET['_shop_order_shipment_status'] ) {
			$vars['meta_key']   = 'ts_shipment_status';
			$vars['meta_value'] = sanitize_text_field( $_GET['_shop_order_shipment_status'] );
			$vars['meta_compare'] = 'LIKE';						
		}

		return $vars;
	}	
	
	/*
	* filter for shipment status
	*/
	public function trackship_status_filter_func( $status ) {
		switch ($status) {
			case 'in_transit':
				$status = __( 'In Transit', 'trackship-for-woocommerce' );
				break;
			case 'on_hold':
				$status = __( 'On Hold', 'trackship-for-woocommerce' );
				break;
			case 'pre_transit':
				$status = __( 'Pre Transit', 'trackship-for-woocommerce' );
				break;
			case 'delivered':
				$status = __( 'Delivered', 'trackship-for-woocommerce' );
				break;
			case 'out_for_delivery':
				$status = __( 'Out For Delivery', 'trackship-for-woocommerce' );
				break;
			case 'available_for_pickup':
				$status = __( 'Available For Pickup', 'trackship-for-woocommerce' );
				break;
			case 'return_to_sender':
				$status = __( 'Return To Sender', 'trackship-for-woocommerce' );
				break;
			case 'failure':
				$status = __( 'Failed Attempt', 'trackship-for-woocommerce' );
				break;
			case 'unknown':
				$status = __( 'Unknown', 'trackship-for-woocommerce' );
				break;
			case 'pending_trackship':
				$status = __( 'Pending TrackShip', 'trackship-for-woocommerce' );
				break;
			case 'INVALID_TRACKING_NUM':
				$status = __( 'Invalid Tracking Number', 'trackship-for-woocommerce' );
				break;
			case 'carrier_unsupported':
				$status = __( 'Carrier Unsupported', 'trackship-for-woocommerce' );
				break;
			case 'invalid_user_key':
				$status = __( 'Invalid User Key', 'trackship-for-woocommerce' );
				break;
			case 'wrong_shipping_provider':
				$status = __( 'Wrong Shipping Provider', 'trackship-for-woocommerce' );
				break;	
			case 'deleted':
				$status = __( 'Deleted', 'woocommerce' );
				break;
			case 'balance_zero':
				$status = __( 'TrackShip balance is 0', 'woocommerce' );
				break;
			case 'connection_issue':
				$status = __( 'TrackShip connection issue', 'woocommerce' );
				break;
		}
		return $status;
	}
	
	/*
	* filter for shipment status icon
	*/
	public function trackship_status_icon_filter_func( $html, $status ) {
		switch ($status) {
			case 'in_transit':
				$html = '<span class="shipment-icon icon-' . $status . '">';
				break;
			case 'on_hold':
				$html = '<span class="shipment-icon icon-' . $status . '">';
				break;	
			case 'pre_transit':
				$html = '<span class="shipment-icon icon-' . $status . '">';
				break;
			case 'delivered':
				$html = '<span class="shipment-icon icon-' . $status . '">';
				break;
			case 'out_for_delivery':
				$html = '<span class="shipment-icon icon-' . $status . '">';
				break;
			case 'available_for_pickup':
				$html = '<span class="shipment-icon icon-' . $status . '">';
				break;
			case 'return_to_sender':
				$html = '<span class="shipment-icon icon-' . $status . '">';
				break;
			case 'failure':
				$html = '<span class="shipment-icon icon-' . $status . '">';
				break;
			case 'unknown':
				$html = '<span class="shipment-icon icon-' . $status . '">';
				break;
			case 'pending_trackship':
				$html = '<span class="shipment-icon icon-' . $status . '">';
				break;
			case 'INVALID_TRACKING_NUM':
				$html = '<span class="shipment-icon icon-' . $status . '">';
				break;
			case 'wrong_shipping_provider':
				$html = '<span class="shipment-icon icon-' . $status . '">';
				break;	
			case 'invalid_user_key':
				$html = '<span class="shipment-icon icon-' . $status . '">';
				break;
			case 'carrier_unsupported':
				$html = '<span class="shipment-icon icon-' . $status . '">';
				break;				
			default:
				$html = '<span class="shipment-icon icon-default">';
				break;

		}
		return $html;
	}

	/*
	* update all shipment status email status
	*/
	public function update_shipment_status_email_status_fun() {
		check_ajax_referer( 'tswc_shipment_status_email', 'security' );
		$settings_data = isset( $_POST['settings_data'] ) ? wc_clean( $_POST['settings_data'] ) : '';
		$status_settings = get_option( $settings_data );
		$enable_status_email = isset( $_POST['wcast_enable_status_email'] ) ? wc_clean( $_POST['wcast_enable_status_email'] ) : '';
		$p_id = isset( $_POST['id'] ) ? wc_clean( $_POST['id'] ) : '';
		$status_settings[$p_id] = wc_clean( $enable_status_email );
		
		update_option( wc_clean( $settings_data ), $status_settings );		
		exit;
	}
	
	/*
	* get completed order with tracking that not sent to TrackShip
	* return number
	*/
	public function completed_order_with_tracking() {
		// Get orders completed.
		$args = array(
			'status' => 'wc-completed',
			'limit'	 => 100,	
			'date_created' => '>' . ( time() - 2592000 ),
		);
		
		$orders = wc_get_orders( $args );
		
		$completed_order_with_tracking = 0;
		
		foreach ( $orders as $order) {
			$order_id = $order->get_id();
			
			$ast = new WC_Advanced_Shipment_Tracking_Actions();
			$tracking_items = $ast->get_tracking_items( $order_id, true );
			if ( $tracking_items ) {
				$shipment_status = get_post_meta( $order_id, 'shipment_status', true);
				foreach ( $tracking_items as $key => $tracking_item ) { 				
					if ( !isset( $shipment_status[$key] ) ) {						
						$completed_order_with_tracking++;		
					}
				}									
			}			
		}
		return $completed_order_with_tracking;
	}
	
	/*
	* get completed order with Trackship Balance 0 status
	* return number
	*/
	public function completed_order_with_zero_balance() {
		
		// Get orders completed.
		$args = array(
			'status' => 'wc-completed',
			'limit'	 => 100,	
			'date_created' => '>' . ( time() - 2592000 ),
		);		
		
		$orders = wc_get_orders( $args );
		
		$completed_order_with_zero_balance = 0;
		
		foreach ( $orders as $order ) {
			$order_id = $order->get_id();
			
			$ast = new WC_Advanced_Shipment_Tracking_Actions();
			$tracking_items = $ast->get_tracking_items( $order_id, true );
			if ( $tracking_items ) {				
				$shipment_status = get_post_meta( $order_id, 'shipment_status', true);				
				foreach ( $tracking_items as $key => $tracking_item ) { 					
					if ( isset( $shipment_status[$key]['pending_status'] ) && 'TrackShip balance is 0' == $shipment_status[$key]['pending_status'] ) {
						$completed_order_with_zero_balance++;		
					}
				}									
			}			
		}				
		return $completed_order_with_zero_balance;
	}
	
	/*
	* get completed order with Trackship connection issue status
	* return number
	*/
	public function completed_order_with_do_connection() {
		
		// Get orders completed.
		$args = array(
			'status' => 'wc-completed',
			'limit'	 => 100,	
			'date_created' => '>' . ( time() - 2592000 ),
		);		
		
		$orders = wc_get_orders( $args );
		
		$completed_order_with_do_connection = 0;
		
		foreach ( $orders as $order ) {
			$order_id = $order->get_id();
			
			$ast = new WC_Advanced_Shipment_Tracking_Actions();
			$tracking_items = $ast->get_tracking_items( $order_id, true );
			if ( $tracking_items ) {				
				$shipment_status = get_post_meta( $order_id, 'shipment_status', true);				
				foreach ( $tracking_items as $key => $tracking_item ) { 					
					if ( isset( $shipment_status[$key]['pending_status'] ) && 'TrackShip connection issue' == $shipment_status[$key]['pending_status'] ) {
						$completed_order_with_do_connection++;		
					}
				}									
			}			
		}				
		return $completed_order_with_do_connection;
	}
	
	/**
	 * Shipment tracking info html in orders details page
	 */
	public function display_shipment_tracking_info( $order_id, $item ) {
		$shipment_status = get_post_meta( $order_id, 'shipment_status', true);		
		$tracking_id = $item['tracking_id'];
		
		$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
		
		$wp_date_format = get_option( 'date_format' );
		
		if ( 'd/m/Y' == $wp_date_format ) {
			$date_format = 'd/m'; 
		} else {
			$date_format = 'm/d';
		}
		
		if ( count( $tracking_items ) > 0 ) {
			foreach ( $tracking_items as $key => $tracking_item ) {
				if ( $tracking_id == $tracking_item['tracking_id'] ) {
					if ( isset( $shipment_status[$key] ) ) {
						$has_est_delivery = false;
						$data = $shipment_status[$key];						
						
						if ( isset( $data['pending_status'] ) ) {
							$status = $data['pending_status'];
						} else {
							$status = $data['status'];	
						}
						
						$status_date = $data['status_date'];
						
						if ( !empty( $data['est_delivery_date'] ) ) {
							$est_delivery_date = $data['est_delivery_date'];
						}
						
						if ( 'delivered' != $status  && 'return_to_sender' != $status && !empty($est_delivery_date) ) {
							$has_est_delivery = true;
						}
						?>
						<div class="ast-shipment-status-div">	
							<span class="ast-shipment-status shipment-<?php echo esc_html( sanitize_title($status) ); ?>">

								<span class="shipment-icon icon-default icon-<?php esc_html_e( $status ); ?>" >
									<strong><?php echo esc_html( apply_filters('trackship_status_filter', $status) ); ?></strong>
								</span>
								<?php if ( '' != $status_date ) { ?>
                                    <span class="">Updated <?php esc_html_e( gmdate( $date_format, strtotime($status_date) ) ); ?> 
                                    	<?php if ( !$has_est_delivery && !in_array( $status, array( 'carrier_unsupported', 'wrong_shipping_provider', 'INVALID_TRACKING_NUM', 'pending_trackship', 'connection_issue' ) ) ) { ?>
                                        	<a class="ts4wc_track_button ft12 open_tracking_details" data-orderid="<?php esc_html_e( $order_id ); ?>" data-tracking_id="<?php echo esc_html( $tracking_id ); ?>" data-nonce="<?php esc_html_e( wp_create_nonce( 'tswc-' . $order_id ) ); ?>" >Track</a>
										<?php } ?>
                                       
                                        <?php if ( 'pending_trackship' == $status ) { ?>
                                            <a href="javascript:;" class="trackship-tip" title="Pending TrackShip is a temporary status that will display for a few minutes until we update the order with the first tracking event from the shipping provider. Please refresh the orders admin in 2-3 minutes." >more info</a>
                                        <?php } ?>
                                        <?php if ( in_array( $status, array( 'carrier_unsupported', 'wrong_shipping_provider', 'INVALID_TRACKING_NUM') ) ) { ?>
                                            <a href="https://trackship.info/docs/trackship-resources/shipment-tracking-status-reference/#trackship-status-messages" target="_blank">more info</a>
                                        <?php } ?>
                                        <?php if ( 'connection_issue' == $status ) { ?>
                                            <a href="https://trackship.info/docs/trackship-for-woocommerce/connect-trackship-to-your-store/#requirements" target="_blank">more info</a>
                                        <?php } ?>
                                    </span>
                                <?php } ?>
								<br>
								<?php if ( $has_est_delivery ) { ?>
                                    <span class="wcast-shipment-est-delivery ft11">Est. Delivery(<?php esc_html_e( gmdate( $date_format, strtotime($est_delivery_date) ) ); ?>) <a class="ts4wc_track_button ft12 open_tracking_details" data-orderid="<?php esc_html_e( $order_id ); ?>" data-tracking_id="<?php echo esc_html( $tracking_id ); ?>" data-nonce="<?php esc_html_e( wp_create_nonce( 'tswc-' . $order_id ) ); ?>" > Track</a></span>
                                <?php } ?>
							</span>
						</div>	
					<?php } else { ?>
						<button type="button" class="button metabox_get_shipment_status"><?php esc_html_e( 'Get Shipment Status', 'trackship-for-woocommerce' ); ?></span></button>
						<div class="ast-shipment-status-div temp-pending_trackship" style="display:none;">	
							<span class="open_tracking_details ast-shipment-status shipment-pending_trackship" data-orderid="<?php esc_html_e( $order_id ); ?>" data-tracking_id="<?php esc_html_e( $tracking_id ); ?>" >
								<span class="shipment-icon icon-pending_trackship">
									<strong><?php esc_html_e( apply_filters( 'trackship_status_filter', 'pending_trackship' ) ); ?></strong>
								</span>
							</span>
						</div>
					<?php
					}
				}
			}
		}
	}

	/**
	 * Delete tracking information from TrackShip when tracking deleted from AST
	 */
	public function delete_tracking_number_from_trackship( $tracking_items, $tracking_id, $order_id ) {
		
		$api_enabled = get_option( 'wc_ast_api_enabled', 0);
		if ( $api_enabled ) {			
			foreach ( $tracking_items as $tracking_item ) {
				if ( $tracking_item['tracking_id'] == $tracking_id ) {					
					$tracking_number = $tracking_item['tracking_number'];
					$tracking_provider = $tracking_item['tracking_provider'];					
					$api = new WC_TrackShip_Api_Call();
					$array = $api->delete_tracking_number_from_trackship( $order_id, $tracking_number, $tracking_provider );
				}				
			}						
		}	
	}
	
	/*
	* fix shipment tracking for deleted tracking
	*/
	public function func_fix_shipment_tracking_for_deleted_tracking( $order_id, $key, $item ) {
		$shipment_status = get_post_meta( $order_id, 'shipment_status', true);
		if ( isset( $shipment_status[$key] ) ) {
			unset( $shipment_status[$key] );
			update_post_meta( $order_id, 'shipment_status', $shipment_status);
		}
	}

	/**
	 * Code for check if tracking number in order is delivered or not
	*/
	public function check_tracking_delivered( $order_id ) {
		$delivered = true;
		$shipment_status = get_post_meta( $order_id, 'shipment_status', true);
		$wc_ast_status_delivered = get_option('wc_ast_status_delivered');						
		
		foreach ( (array) $shipment_status as $shipment ) {
			$status = $shipment['status'];
			if ( 'delivered' != $status ) {
				$delivered = false;
			}
		}
		if ( count( $shipment_status ) > 0 && true == $delivered && $wc_ast_status_delivered ) {
			//trigger order deleivered
			$order = wc_get_order( $order_id );
			$order_status  = $order->get_status();
			if ( 'completed' == $order_status ) {
				$order->update_status('delivered');
			}
		}
	}

	/**
	 * Code for trigger shipment status email
	*/
	public function trigger_tracking_email( $order_id, $old_status, $new_status, $tracking_item, $shipment_status ) {
		
		$order = wc_get_order( $order_id );					
		
		if ( $old_status != $new_status ) {
			if ( 'delivered' == $new_status ) {
				wc_trackship_email_manager()->delivered_shippment_status_email_trigger($order_id, $order, $old_status, $new_status, $tracking_item, $shipment_status );
			} elseif ( in_array( $new_status, array( 'failure', 'in_transit', 'on_hold', 'out_for_delivery', 'available_for_pickup', 'return_to_sender', 'exception' ) ) ) {
				wc_trackship_email_manager()->shippment_status_email_trigger( $order_id, $order, $old_status, $new_status, $tracking_item, $shipment_status );
			}
			do_action( 'ast_trigger_ts_status_change', $order_id, $old_status, $new_status, $tracking_item, $shipment_status );
			
			// The text for the note
			$note = sprintf( __( 'Tracking Status (%s - %s) was updated to %s. (TrackShip)' ), $tracking_item['tracking_provider'], $tracking_item['tracking_number'], $new_status );
			
			// Add the note
			$order->add_order_note( $note );
		}
	}	
	
	/**
	* Add a new dashboard widget.
	*/
	public function ast_add_dashboard_widgets() {
		//amcharts js	
		wp_enqueue_script( 'amcharts', trackship_for_woocommerce()->plugin_dir_url() . 'assets/js/amcharts/amcharts.js', array(), trackship_for_woocommerce()->version );
		wp_enqueue_script( 'amcharts-light-theme', trackship_for_woocommerce()->plugin_dir_url() . 'assets/js/amcharts/light.js', array(), trackship_for_woocommerce()->version );
		wp_enqueue_script( 'amcharts-serial', trackship_for_woocommerce()->plugin_dir_url() . 'assets/js/amcharts/serial.js', array(), trackship_for_woocommerce()->version );		
		wp_enqueue_style( 'dashboard_widget_styles', trackship_for_woocommerce()->plugin_dir_url() . 'assets/css/dashboard_widget.css', array(), trackship_for_woocommerce()->version );
		
		wp_add_dashboard_widget( 'trackship_dashboard_widget', 'Tracking Analytics <small>(last 30 days)</small>', array( $this, 'dashboard_widget_function') );
	}
	
	/**
	* Output the contents of the dashboard widget
	*/
	public function dashboard_widget_function( $post, $callback_args ) {				
				
		global $wpdb;		
		$paid_order_statuses =  array('completed','delivered','shipped');		
		$shipment_status_results = $wpdb->get_results( $wpdb->prepare( "
			SELECT p.ID, pm.* FROM {$wpdb->prefix}posts AS p
			INNER JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
			WHERE p.post_status IN ( 'wc-completed','wc-delivered','wc-shipped' )
			AND p.post_type LIKE %s
			AND pm.meta_key = 'shipment_status'
			AND post_date > %s
		", 'shop_order', gmdate('Y-m-d', strtotime('-30 days')) ) );

		$tracking_items_results = $wpdb->get_results( $wpdb->prepare( "
			SELECT p.ID, pm.* FROM {$wpdb->prefix}posts AS p
			INNER JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
			WHERE p.post_status IN ( 'wc-completed','wc-delivered','wc-shipped' )
			AND p.post_type LIKE %s
			AND pm.meta_key = '_wc_shipment_tracking_items'
			AND post_date > %s
		", 'shop_order', gmdate('Y-m-d', strtotime('-30 days')) ) );		
					
		$shipment_status = array();
		$shipment_status_merge = array();
		$tracking_item_merge = array();
		
		foreach ( $shipment_status_results as $order ) {
			$order_id = $order->ID;														
			$shipment_status = unserialize($order->meta_value);			
						
			if ( is_array( $shipment_status ) ) {
				$shipment_status_merge = array_merge($shipment_status_merge, $shipment_status);				
			}					
		}
				
		foreach ( $tracking_items_results as $order ) {
			$order_id = $order->ID;						
			$tracking_items = unserialize($order->meta_value);
			
			if ( $tracking_items ) {								
				foreach ( $tracking_items as $key => $tracking_item ) { 				
					if ( isset( $shipment_status[$key] ) ) {							
						$tracking_item_merge[] = $tracking_item;							
					}
				}								
			}			
		}
		
		$shipment_status_arr = array();

		foreach ( (array) $shipment_status_merge as $key => $item ) {
			if ( isset($item['status'] ) ) {
				$shipment_status_arr[$item['status']][$key] = $item;
			}
		}
		
		$tracking_provider_arr = array();
		
		foreach ( $tracking_item_merge as $key => $item ) {	
			$tracking_provider = $wpdb->get_var( $wpdb->prepare( "SELECT provider_name FROM {$wpdb->get_blog_prefix(0)}woo_shippment_provider WHERE ts_slug = %s", $item['tracking_provider'] ) );
			$tracking_provider_arr[$tracking_provider][$key] = $item;
		}		
		
		$tracking_issue_array = array();
		foreach ( $shipment_status_arr as $status => $val ) {
			if ( 'carrier_unsupported' == $status || 'INVALID_TRACKING_NUM' == $status || 'unknown' == $status || 'wrong_shipping_provider' == $status ) {
				$tracking_issue_array[$status] = $val; 
			}
		}
		
		ksort($shipment_status_arr, SORT_NUMERIC);
		ksort($tracking_provider_arr, SORT_NUMERIC);
		?>
		<script type="text/javascript">
			 AmCharts.makeChart("ast_dashboard_status_chart",
				{
					"type": "serial",
					"categoryField": "shipment_status",
					"startDuration": 1,
					"handDrawScatter": 4,
					"theme": "light",
					"categoryAxis": {
						"autoRotateAngle": 0,
						"autoRotateCount": 0,
						"autoWrap": true,
						"gridPosition": "start",
						"minHorizontalGap": 10,
						"offset": 1
					},
					"trendLines": [],
					"graphs": [
						{
							"balloonText": " [[shipment_status]] : [[value]]",
							"bulletBorderThickness": 7,
							"colorField": "color",
							"fillAlphas": 1,
							"id": "AmGraph-1",
							"lineColorField": "color",
							"title": "graph 1",
							"type": "column",
							"valueField": "count"
						}
					],
					"guides": [],
					"valueAxes": [
						{
							"id": "ValueAxis-1",
							"title": ""
						}
					],
					"allLabels": [],
					"balloon": {},
					"titles": [
						{
							"id": "Title-1",
							"size": 15,
							"text": ""
						}
					],
					"dataProvider": [
						<?php foreach ( $shipment_status_arr as $status => $array ) { ?>
							{
								"shipment_status": "<?php esc_html_e( apply_filters( 'trackship_status_filter', $status) ); ?>",
								"count": <?php esc_html_e( count($array) ); ?>,
								"color": "#BBE285",								
							},
						<?php } ?>
					]					
				}
			);
		</script>
		<script type="text/javascript">
			 AmCharts.makeChart("ast_dashboard_providers_chart",
				{
					"type": "serial",
					"categoryField": "shipment_provider",
					"startDuration": 1,
					"handDrawScatter": 4,
					"theme": "light",
					"categoryAxis": {
						"autoRotateAngle": 0,
						"autoRotateCount": 0,
						"autoWrap": true,
						"gridPosition": "start",
						"minHorizontalGap": 10,
						"offset": 1
					},
					"trendLines": [],
					"graphs": [
						{
							"balloonText": " [[shipment_provider]] : [[value]]",
							"bulletBorderThickness": 7,
							"colorField": "color",
							"fillAlphas": 1,
							"id": "AmGraph-1",
							"lineColorField": "color",
							"title": "graph 1",
							"type": "column",
							"valueField": "count"
						}
					],
					"guides": [],
					"valueAxes": [
						{
							"id": "ValueAxis-1",
							"title": ""
						}
					],
					"allLabels": [],
					"balloon": {},
					"titles": [
						{
							"id": "Title-1",
							"size": 15,
							"text": ""
						}
					],
					"dataProvider": [
						<?php foreach ( $tracking_provider_arr as $provider => $array ) { ?>
							{
								"shipment_provider": "<?php esc_html_e( $provider ); ?>",
								"count": <?php esc_html_e( count($array) ); ?>,
								"color": "#BBE285",	
							},
						<?php } ?>
					]					
				}
			);
		</script>	
		<style>
		a[href="http://www.amcharts.com"] {
			display: none !important;
		}
		</style>	
		<div class="ast-dashborad-widget">			
			
			<input id="tab_s_providers" type="radio" name="tabs" class="widget_tab_input" checked>
			<label for="tab_s_providers" class="widget_tab_label first_label"><?php esc_html_e('Shipment providers', 'trackship-for-woocommerce'); ?></label>
			
			<input id="tab_s_status" type="radio" name="tabs" class="widget_tab_input">
			<label for="tab_s_status" class="widget_tab_label"><?php esc_html_e('Shipment status', 'trackship-for-woocommerce'); ?></label>
			
			<input id="tab_t_issues" type="radio" name="tabs" class="widget_tab_input">
			<label for="tab_t_issues" class="widget_tab_label"><?php esc_html_e('Tracking issues', 'trackship-for-woocommerce'); ?></label>
			
			<section id="content_s_providers" class="widget_tab_section">
				<?php if ( $tracking_provider_arr ) { ?>
					<div id="ast_dashboard_providers_chart" class="" style="width: 100%;height: 300px;"></div>
				<?php } else { ?>
					<p style="padding: 8px 12px;"><?php esc_html_e('data not available.', 'trackship-for-woocommerce'); ?></p>
				<?php } ?>
			</section>	
			
			<section id="content_s_status" class="widget_tab_section">	
				<?php if ( $shipment_status_arr ) { ?>
					<div id="ast_dashboard_status_chart" class="" style="width: 100%;height: 300px;"></div>				
				<?php } else { ?>
					<p style="padding: 8px 12px;"><?php esc_html_e('data not available.', 'trackship-for-woocommerce'); ?></p>
				<?php } ?>
			</section>

			<section id="content_t_issues" class="widget_tab_section">	
				<?php if ( $tracking_issue_array ) { ?>					
					<table class="table widefat fixed striped" style="border: 0;border-bottom: 1px solid #e5e5e5;">
						<tbody>
							<?php foreach ( $tracking_issue_array as $status => $array ) { ?>
								<tr>
									<td><a href="<?php echo esc_url( get_site_url() ); ?>/wp-admin/edit.php?s&post_status=all&post_type=shop_order&_shop_order_shipment_status=<?php esc_html_e( $status ); ?>"><?php esc_html_e( apply_filters('trackship_status_filter', $status) ); ?></a></td>
									<td><?php echo count($array); ?></td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				<?php } else { ?>
					<p style="padding: 8px 12px;"><?php esc_html_e('data not available.', 'trackship-for-woocommerce'); ?></p>
				<?php } ?>
			</section>			
			
		</div>
		<div class="widget_footer">	
			<a class="" href="https://trackship.info/my-account/analytics/" target="blank"><?php esc_html_e( 'View more on TrackShip', 'trackship-for-woocommerce' ); ?></a>
		</div>
	<?php
	}
	
	/**
	* Create tracking page after store is connected
	*/
	public function create_tracking_page() {
		if ( version_compare( get_option( 'wc_advanced_shipment_tracking_ts_page' ), '1.0', '<' ) ) {
			$new_page_title = 'Shipment Tracking';
			$new_page_slug = 'ts-shipment-tracking';		
			$new_page_content = '[wcast-track-order]';       
			//don't change the code below, unless you know what you're doing
			$page_check = get_page_by_title($new_page_title);		
	
			if ( !isset( $page_check->ID ) ) {
				$new_page = array(
					'post_type' => 'page',
					'post_title' => $new_page_title,
					'post_name' => $new_page_slug,
					'post_content' => $new_page_content,
					'post_status' => 'publish',
					'post_author' => 1,
				);
				$new_page_id = wp_insert_post($new_page);	
				update_option( 'wc_ast_trackship_page_id', $new_page_id );	
			}
			update_option( 'wc_advanced_shipment_tracking_ts_page', '1.0');					
		}	
	}
	
	/*
	* Return option value for customizer
	*/
	public function get_option_value_from_array( $array, $key, $default_value) {		
		$array_data = get_option($array);	
		$value = '';
		
		if ( isset( $array_data[$key] ) ) {
			$value = $array_data[$key];	
		}					
		
		if ( '' == $value ) {
			$value = $default_value;
		}
		return $value;
	}
	
	/*
	* Return checkbox option value for customizer
	*/
	public function get_checkbox_option_value_from_array( $array, $key, $default_value) {		
		$array_data = get_option($array);	
		$value = '';
		
		if ( isset( $array_data[$key] ) ) {
			$value = $array_data[$key];				
			return $value;
		}							
		if ( '' == $value ) {
			$value = $default_value;
		}		
		return $value;
	}
	
	/*
	* change style of delivered order label
	*/	
	public function footer_function() {
		if ( !is_plugin_active( 'woocommerce-order-status-manager/woocommerce-order-status-manager.php' ) ) {
			$bg_color = get_option('wc_ast_status_label_color', '#212c42');
			$color = get_option('wc_ast_status_label_font_color', '#fff');						
			?>
			<style>
			.order-status.status-delivered,.order-status-table .order-label.wc-delivered{
				background: <?php echo esc_html( $bg_color ); ?>;
				color: <?php esc_html_e( $color ); ?>;
			}			
			</style>
			<?php
		}
	}

	/*
	 * tracking number filter
	 * if number not found. return false
	 * if number found. return true
	*/
	public function check_tracking_exist( $bool, $order ) {
		
		if ( true == $bool ) {
				
			$tracking_items = $order->get_meta( '_wc_shipment_tracking_items', true );
			if ( $tracking_items ) {
				return true;
			} else {
				return false;
			}
		}
		return $bool;
	}		
	
	/*
	 * check order status?
	 * is it valid for TS trigger
	*/
	public function check_order_status( $bool, $order ) {
		$valid_order_statuses = get_option( 'trackship_trigger_order_statuses' );
		$bool = in_array( $order->get_status(), $valid_order_statuses );
		return $bool;
	}
	
	/*
	 * order status change
	 * schedule to trigger trackship
	*/
	public function schedule_when_order_status_changed( $order_id, $order ) {
		$this->trigger_trackship_apicall( $order_id );
	}
	
	/*
	 * schedule trackship trigger in action scheduler
	*/
	public function schedule_trackship_trigger( $order_id ) {
		$order = wc_get_order( $order_id );
		$order_shipped = apply_filters( 'is_order_shipped', false, $order );
		if ( $order_shipped ) {
			as_schedule_single_action( time() + 1, 'wcast_retry_trackship_apicall', array( $order_id ), 'TrackShip' );
			$this->set_temp_pending( $order_id );
			return true;
		}
		return false;
	}
	
	public function set_temp_pending( $order_id ) {
		
		$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id, false );
		$shipment_statuses = $this->get_shipment_status( $order_id, false );
		//echo '<pre>';print_r($tracking_items);echo '</pre>';
		//echo '<pre>';print_r($shipment_statuses);echo '</pre>';
		
		foreach ( $tracking_items as $key => $tracking_item ) {
			
			if ( isset( $shipment_statuses[$key]['status'] ) && 'delivered' == $shipment_statuses[$key]['status'] ) {
				continue;
			}
			
			$shipment_statuses[$key]['pending_status'] = 'pending_trackship';
			$shipment_statuses[$key]['status_date'] = gmdate( 'y-m-d' );
		}
		//echo '<pre>';print_r($shipment_statuses);echo '</pre>';
		update_post_meta( $order_id, 'shipment_status', $shipment_statuses );
	}
	
	/*
	* trigger trackship api call
	*/
	public function trigger_trackship_apicall( $order_id ) {
		
		$order = wc_get_order( $order_id );
		$order_shipped = apply_filters( 'is_order_shipped', false, $order );
		if ( $order_shipped ) {
			$api = new WC_TrackShip_Api_Call();
			$array = $api->get_trackship_apicall( $order_id );
		}
	}
	
	/*
	* trigger when order status changed to shipped or completed or update tracking
	* param $order_id
	*/	
	public function schedule_while_adding_tracking( $order_id ) {
		$this->schedule_trackship_trigger( $order_id );
	}
	
	/*
	* Get custom order number
	*/
	public function get_custom_order_number( $order_id ) {
		
		if ( is_plugin_active( 'custom-order-numbers-for-woocommerce/custom-order-numbers-for-woocommerce.php' ) ) {
			$custom_order_number = get_post_meta( $order_id, '_alg_wc_custom_order_number', true );
			if ( !empty( $custom_order_number ) ) {
				return $custom_order_number;
			}
		}		
		
		if ( is_plugin_active( 'woocommerce-sequential-order-numbers/woocommerce-sequential-order-numbers.php' ) ) {					
			$order = wc_get_order( $order_id );
			$custom_order_number = $order->get_order_number();
			if ( !empty( $custom_order_number ) ) {
				return $custom_order_number;
			}
		}
		
		if ( is_plugin_active( 'woocommerce-sequential-order-numbers-pro/woocommerce-sequential-order-numbers-pro.php' ) ) {				
			$order = wc_get_order( $order_id );
			$custom_order_number = $order->get_order_number();
			if ( !empty( $custom_order_number ) ) {
				return $custom_order_number;
			}
		}
		
		if ( is_plugin_active( 'woocommerce-jetpack/woocommerce-jetpack.php' ) ) {			
			$custom_order_number = get_post_meta( $order_id, '_wcj_order_number', true );
			$order = wc_get_order( $order_id );	
			if ( class_exists( 'WCJ_Order_Numbers' ) ) {	
				$WCJ_Order_Numbers = new WCJ_Order_Numbers();
				$custom_order_number = $WCJ_Order_Numbers->display_order_number( $order_id, $order );				
				if ( !empty( $custom_order_number ) ) {
					return $custom_order_number;
				}
			}
		}
		
		if ( is_plugin_active( 'wp-lister-amazon/wp-lister-amazon.php' ) ) {			
			$custom_order_number = get_post_meta( $order_id, '_wpla_amazon_order_id', true );
			if ( !empty( $custom_order_number ) ) {
				return $custom_order_number;				
			} 
		}	
		
		if ( is_plugin_active( 'wp-lister/wp-lister.php' ) || is_plugin_active( 'wp-lister-for-ebay/wp-lister.php' ) ) {
			$custom_order_number = get_post_meta( $order_id, '_ebay_extended_order_id', true );
			if ( empty($custom_order_number ) ) {
				$custom_order_number = get_post_meta( $order_id, '_ebay_order_id', true );
			}
			if ( !empty( $custom_order_number ) ) {
				return $custom_order_number;
			}
		}	
		
		if ( is_plugin_active( 'yith-woocommerce-sequential-order-number-premium/init.php' ) ) {			
			$custom_order_number = get_post_meta( $order_id, '_ywson_custom_number_order_complete', true );
			if ( !empty( $custom_order_number ) ) {
				return $custom_order_number;
			}
		}
		
		if ( is_plugin_active( 'wt-woocommerce-sequential-order-numbers/wt-advanced-order-number.php' ) ) {			
			$custom_order_number = get_post_meta($order_id, '_order_number', true);			
			if ( !empty( $custom_order_number ) ) {
				return $custom_order_number;
			}
		}
		
		return apply_filters( 'ast_custom_order_number', $order_id );	
	}
	
	public function get_shipment_status( $order_id, $formatted = true ) {
		$shipment_statuses = get_post_meta( $order_id, 'shipment_status', true );
		
		if ( is_array( $shipment_statuses ) ) {
			if ( $formatted ) {
				$tracking_page = $this->get_tracking_page_link( $order_id );
				foreach ( $shipment_statuses as &$item ) {
					if ( isset( $item[ 'pending_status' ] ) ) {
						$item[ 'status' ] = $item[ 'pending_status' ];
					}
					if ( 'carrier_unsupported' != $item[ 'status' ] ) {
						$array	= array( 'tracking_page' => $tracking_page );
						$item	= array_merge( $item, $array );
					}
				}
			}
			return $shipment_statuses;
		} else {
			return array();
		}
	}
	
	public function get_tracking_page_link( $order_id ) {
		
		$page_id = get_option( 'wc_ast_trackship_page_id' );
		$order = wc_get_order( $order_id );
		
		return add_query_arg( array(
			'order_id'	=> $order_id,
			'order_key'	=> $order->get_order_key(),
		), get_permalink( $page_id ) );
		
	}
	
	/*
	 * get trackship key
	 *
	 * @since   1.0
	 *
	 * Return @void
	 *
	 */
	public function get_trackship_key() {
		$wc_ast_api_key = get_option( 'wc_ast_api_key', false );
		return $wc_ast_api_key;
	}
}
