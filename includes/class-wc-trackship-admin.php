<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Trackship_Admin {
	
	/**
	 * Initialize the main plugin function
	*/
	public function __construct() {
		global $wpdb;
		$this->log_table = $wpdb->prefix . 'zorem_email_sms_log';
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
	 * @return WC_Advanced_Shipment_Tracking_Admin
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
		
		add_action( 'admin_menu', array( $this, 'register_woocommerce_menu' ), 110 );

		add_action( 'admin_footer', array( $this, 'footer_function'), 1 );
		add_action( 'wp_ajax_add_trackship_mapping_row', array( $this, 'add_trackship_mapping_row' ) );
		add_action( 'wp_ajax_remove_tracking_event', array( $this, 'remove_tracking_event' ) );
		add_action( 'wp_ajax_remove_trackship_logs', array( $this, 'remove_trackship_logs' ) );
		add_action( 'wp_ajax_trackship_mapping_form_update', array( $this, 'trackship_custom_mapping_form_update') );
		add_filter( 'convert_provider_name_to_slug', array( $this, 'detect_custom_mapping_provider') );	
		add_action( 'wp_ajax_ts_late_shipments_email_form_update', array( $this, 'ts_late_shipments_email_form_update_callback' ) );
		add_action( 'wp_ajax_dashboard_page_count_query', array( $this, 'dashboard_page_count_query' ) );
		
		add_action( 'add_meta_boxes', array( $this, 'register_metabox') );
		
		add_action( 'wp_ajax_wc_shipment_tracking_delete_item', array( $this, 'meta_box_delete_tracking' ), 20 );

		add_action( 'wp_ajax_metabox_get_shipment_status', array( $this, 'metabox_get_shipment_status_cb' ) );
		
		add_action( 'wp_ajax_get_admin_tracking_widget', array( $this, 'get_admin_tracking_widget_cb' ) );
		
		add_action( 'woocommerce_auth_page_footer', array( $this, 'remove_connect_store_border' ), 5 );
		
		add_filter('woocommerce_order_is_download_permitted', array( $this, 'add_onhold_status_to_download_permission' ), 10, 2);

		$newstatus = get_option( 'wc_ast_status_delivered', 0);
		if ( true == $newstatus ) {
			//register order status 
			add_action( 'init', array( $this, 'register_order_status') );
			//add status after completed
			add_filter( 'wc_order_statuses', array( $this, 'add_delivered_to_order_statuses') );
			//Custom Statuses in admin reports
			add_filter( 'woocommerce_reports_order_statuses', array( $this, 'include_custom_order_status_to_reports'), 20, 1 );
			// for automate woo to check order is paid
			add_filter( 'woocommerce_order_is_paid_statuses', array( $this, 'delivered_woocommerce_order_is_paid_statuses' ) );
			//add bulk action
			add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_bulk_actions'), 50, 1 );
			//add reorder button
			add_filter( 'woocommerce_valid_order_statuses_for_order_again', array( $this, 'add_reorder_button_delivered'), 50, 1 );
			//add button in preview
			add_filter( 'woocommerce_admin_order_preview_actions', array( $this, 'additional_admin_order_preview_buttons_actions'), 5, 2 );
			//add actions in column
			add_filter( 'woocommerce_admin_order_actions', array( $this, 'add_delivered_order_status_actions_button'), 100, 2 );
		}

	}
	
	public function add_onhold_status_to_download_permission( $data, $order ) {
		if ( $order->has_status( 'delivered' ) ) {
			return true;
		}
		return $data;
	}
	
	public function remove_connect_store_border() {
		?>
			<style>body.wc-auth.wp-core-ui {border: 0;}</style>
		<?php
	}
	
	public function get_admin_tracking_widget_cb() {
		if ( !current_user_can( 'manage_product' ) && !current_user_can( 'manage_woocommerce' ) ) {
			exit( 'You are not allowed' );
		}
		$page = isset( $_POST['page'] ) ? sanitize_text_field( $_POST['page'] ) : '' ;
		$tracking_id = isset( $_POST['tracking_id'] ) && 'wcpv-vendor-order' == $page ? sanitize_text_field( $_POST['tracking_id'] ) : null ;
		$order_id = isset( $_POST['order_id'] ) ? sanitize_text_field( $_POST['order_id'] ) : '' ;
		$order = wc_get_order( $order_id );
		check_ajax_referer( 'tswc-' . $order_id, 'security' );
		
		if ( current_user_can( 'manage_product' ) || current_user_can( 'manage_woocommerce' ) ) {
			$wc_ast_api_key = get_option('wc_ast_api_key');
			if ( $wc_ast_api_key ) {
				$tracking_page_link = trackship_for_woocommerce()->actions->get_tracking_page_link( $order_id );
				?>
				<div class="ts4wc_tracking-widget-header">
					<span style="line-height: 30px; font-size: 14px;"><?php echo 'Order #' . esc_html( $order->get_order_number() ); ?></span>
					<?php if ( 'wcpv-vendor-order' != $page ) { ?>
						<button class="button btn_outline copy_tracking_page trackship-tip" title="Copy the secure link to the Tracking page" style="border: 0;float:right;" data-tracking_page_link=<?php echo esc_url( $tracking_page_link ); ?> >
							<span class="dashicons dashicons-media-default" style="vertical-align: middle;"></span>
							<span style="vertical-align: middle;line-height: 30px;" ><?php esc_html_e( 'Copy Tracking page', 'trackship-for-woocommerce' ); ?></span>
						</button>
						<button class="button btn_outline copy_view_order_page trackship-tip" title="Copy the secure link to the View Order details page" style="border: 0;float:right;" data-view_order_link=<?php echo esc_url( $order->get_view_order_url() ); ?> >
							<span class="dashicons dashicons-media-default" style="vertical-align: middle;"></span>
							<span style="vertical-align: middle;line-height: 30px;" ><?php esc_html_e( 'Copy View order page', 'trackship-for-woocommerce' ); ?></span>
						</button>
					<?php } ?>
				</div>
				<?php
				trackship_for_woocommerce()->front->admin_tracking_page_widget( $order_id, $tracking_id );
			} else {
				echo '<strong>';esc_html_e( 'Please connect your store with trackship.com.', 'trackship-for-woocommerce' );echo '</strong>';
			}
		} else {
			esc_html_e( 'Please refresh the page and try again.', 'trackship-for-woocommerce' );
		}
		die();
	}
	
	public function metabox_get_shipment_status_cb() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			exit( 'You are not allowed' );
		}
		$o_id = isset( $_POST['order_id'] ) ? sanitize_text_field( $_POST['order_id'] ) : '' ;
		$order_id    = wc_clean( $o_id );
		if ( isset( $_REQUEST['security'] ) && wp_verify_nonce( sanitize_text_field( $_REQUEST['security'] ), 'update-post_' . $order_id ) ) {
			$bool = trackship_for_woocommerce()->actions->schedule_trackship_trigger( $order_id );
			if ( $bool ) {
				$data = array(
					'msg' => 'Tracking information has been sent to TrackShip.'
				);
				wp_send_json_success( $data );
			} else {
				$data = array(
					'msg' => 'Tracking information was not sent to TrackShip.'
				);

				wp_send_json_error( $data );
			}
		} else {
			$data = array(
				'msg' => 'Please refresh the page and try again.'
			);
			wp_send_json_error( $data );
		}
		die();
	}
	
	public function meta_box_delete_tracking() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			exit( 'You are not allowed' );
		}
		check_ajax_referer( 'delete-tracking-item', 'security' );
		$o_id = isset( $_POST['order_id'] ) ? sanitize_text_field( $_POST['order_id'] ) : '' ;
		$order_id    = wc_clean( $o_id );
		$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
		$order = wc_get_order( $order_id );
		
		$shipment_statuses = $order->get_meta( 'shipment_status', true );
		if ( ! is_array( $shipment_statuses ) ) {
			$shipment_statuses = array();
		}
		
		foreach ( $shipment_statuses as $key => $shipment_status ) {
			if ( ! isset( $tracking_items[$key] ) ) {
				unset( $shipment_statuses[$key] );
			}
		}
		
		$order->update_meta_data( 'shipment_status', $shipment_statuses );
		$order->save();
		return;
	}
	
	public function register_metabox() {
		if ( ! trackship_for_woocommerce()->is_ast_active() && trackship_for_woocommerce()->is_st_active() ) {
			if ( class_exists( 'Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) ) {
				$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
			} else {
				$screen = 'shop_order';
			}
			add_meta_box( 'trackship', 'TrackShip', array( $this, 'trackship_metabox_cb'), $screen, 'side', 'high' );
		}
	}
	
	public function trackship_metabox_cb( $post ) {
		$order_id = $post->ID;
		$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
		$shipment_status = trackship_for_woocommerce()->actions->get_shipment_status( $order_id );
		?>
		<div id="trackship-tracking-items">
			<?php foreach ( $tracking_items as $key => $tracking_item ) { ?>
				<?php
				$tracking_provider = ! empty( $tracking_item['formatted_tracking_provider'] ) ? $tracking_item['formatted_tracking_provider'] : ( !empty( $tracking_item['tracking_provider'] ) ? $tracking_item['tracking_provider'] : $tracking_item['custom_tracking_provider'] ) ;
				$tracking_number = $tracking_item['tracking_number'];
				$tracking_link = isset( $shipment_status[ $key ]['tracking_page'] ) ?  $shipment_status[ $key ]['tracking_page'] : $tracking_item['formatted_tracking_link'];
				?>
				<div class="ts-tracking-item">
					<div class="tracking-content">
						<div>
							<strong><?php esc_html_e( $tracking_provider ); ?></strong> - 
							<?php if ( $tracking_link ) { ?>
								<?php echo sprintf( '<a href="%s" target="_blank" title="' . esc_attr( __( 'Track Shipment', 'trackship-for-woocommerce' ) ) . '">' . esc_html( $tracking_number ) . '</a>', esc_url( $tracking_link ) ); ?>
							<?php } else { ?>
								<span><?php esc_html_e( $tracking_number ); ?></span>
							<?php } ?>
						</div>
						<?php 
						do_action(	'ast_after_tracking_number', $order_id, $tracking_item['tracking_id'] );
						do_action(	'ast_shipment_tracking_end', $order_id, $tracking_item ); 
						?>
					</div>
				</div>
			<?php } ?>
		</div>
		<?php
		//echo '<pre>';print_r($tracking_items);echo '</pre>';
		//echo '<pre>';print_r($shipment_status);echo '</pre>';
		wp_enqueue_style( 'trackshipcss' );
		wp_enqueue_script( 'trackship_script' );
		
		//front_style for tracking widget
		wp_register_style( 'front_style', trackship_for_woocommerce()->plugin_dir_url() . 'assets/css/front.css', array(), trackship_for_woocommerce()->version );
		wp_enqueue_style( 'front_style' );
	}
	
	public function build_html( $template, $data = null ) {
		global $wpdb;
		$t = new \stdclass();
		$t->data = $data;
		ob_start();
		include(dirname(__FILE__) . '/admin-html/' . $template . '.phtml');
		$s = ob_get_contents();
		ob_end_clean();
		return $s;
	}
	
	/*
	* Admin Menu add function
	* WC sub menu
	*/
	public function register_woocommerce_menu() {

		add_menu_page( __( 'TrackShip', 'trackship-for-woocommerce' ), __( 'TrackShip', 'trackship-for-woocommerce' ), apply_filters( 'trackship_menu_capabilities', 'manage_woocommerce' ), 'trackship-dashboard', array( $this, 'dashboard_page_callback' ), trackship_for_woocommerce()->plugin_dir_url() . 'assets/images/ts-20.svg', '55.4' );
		
		if ( trackship_for_woocommerce()->is_trackship_connected() ) {
			add_submenu_page( 'trackship-dashboard', 'Dashboard', __( 'Dashboard', 'trackship-for-woocommerce' ), apply_filters( 'trackship_dashboard_menu_capabilities', 'manage_woocommerce' ), 'trackship-dashboard', array( $this, 'dashboard_page_callback' ), 1 );
			
			add_submenu_page( 'trackship-dashboard', 'Analytics', __( 'Analytics', 'trackship-for-woocommerce' ), 'manage_woocommerce', 'trackship-analytics', array( $this, 'analytics_page_callback' ) );

			add_submenu_page( 'trackship-dashboard', 'Shipments', __( 'Shipments', 'trackship-for-woocommerce' ), apply_filters( 'trackship_shipments_menu_capabilities', 'manage_woocommerce' ), 'trackship-shipments', array( $this, 'shipments_page_callback' ) );

			add_submenu_page( 'trackship-dashboard', 'Logs', __( 'Logs', 'trackship-for-woocommerce' ), apply_filters( 'trackship_logs_menu_capabilities', 'manage_woocommerce' ), 'trackship-logs', array( $this, 'logs_page_callback' ) );

			add_submenu_page( 'trackship-dashboard', 'Settings', __( 'Settings', 'trackship-for-woocommerce' ), apply_filters( 'trackship_settings_menu_capabilities', 'manage_woocommerce' ), 'trackship-for-woocommerce', array( $this, 'settings_page_callback' ) );

			add_submenu_page( 'trackship-dashboard', 'Tools', __( 'Tools', 'trackship-for-woocommerce' ), apply_filters( 'trackship_tools_menu_capabilities', 'manage_woocommerce' ), 'trackship-tools', array( $this, 'tools_page_callback' ) );
		}
	}
	
	/*
	* callback for Settings
	*/
	public function settings_page_callback() {
		?>
		<div class="zorem-layout">
			<?php include 'views/header2.php'; ?>
			<?php include 'views/content.php'; ?>
		</div>
		<?php
	}
	
	/*
	* callback for Shipment
	*/
	public function shipments_page_callback() {
		?>
		<div class="zorem-layout">
			<?php include 'views/header2.php'; ?>
			<?php $this->get_trackship_notice_msg(); ?>
			<div class="trackship_admin_content">
				<section id="content_trackship_dashboard" style="display:block" class="inner_tab_section">
					<div class="tab_inner_container">
						<?php include 'views/shipments.php'; ?>
					</div>
				</section>
			</div>
		</div>
		<?php
	}

	/*
	* callback for Shipment
	*/
	public function logs_page_callback() {
		?>
		<div class="zorem-layout">
			<?php include 'views/header2.php'; ?>
			<?php $this->get_trackship_notice_msg(); ?>
			<div class="trackship_admin_content">
				<section id="content_trackship_logs" style="display:block" class="inner_tab_section">
					<div class="tab_inner_container">
						<?php include 'views/logs.php'; ?>
					</div>
				</section>
			</div>
		</div>
		<?php
	}

	/*
	* callback for Shipment
	*/
	public function tools_page_callback() {
		?>
		<div class="zorem-layout">
			<?php include 'views/header2.php'; ?>
			<div class="trackship_admin_content">
				<section id="content_trackship_tools" style="display:block" class="inner_tab_section">
					<div class="tab_inner_container">
						<?php include 'views/tools.php'; ?>
					</div>
				</section>
			</div>
		</div>
		<?php
	}
	
	/*
	* callback for Dashboard
	*/
	public function dashboard_page_callback() {
		?>
		<div class="zorem-layout">
			<?php
			include 'views/header2.php';
			if ( trackship_for_woocommerce()->is_trackship_connected() ) {
				$this->get_trackship_notice_msg();
			}
			?>
			<div class="trackship_admin_content">
				<section id="content_trackship_fullfill_dashboard" class="">
					<div class="tab_inner_container">
						<?php if ( trackship_for_woocommerce()->is_trackship_connected() ) { ?>
							<?php include 'views/dashboard.php'; ?>
						<?php } else { ?>
							<div class="woocommerce trackship_admin_layout">
								<div class="trackship_admin_content" >
									<div class="trackship_nav_div">	
										<?php include 'views/trackship-integration.php'; ?>
									</div>
								</div>
							</div>
						<?php } ?>
					</div>
				</section>
			</div>
		</div>
		<?php
	}
	
	/*
	* callback for Analytics
	*/
	public function analytics_page_callback () {
		wp_redirect( admin_url('admin.php?page=wc-admin&path=/analytics/trackship-analytics'), 301 ); 
		exit;
	}

	/*
	* Query for Dashboard
	*/
	public function dashboard_page_count_query() {
		
		check_ajax_referer( 'wc_ast_tools', 'security' );
		$start_date = isset( $_POST['selected_option'] ) ? wc_clean( $_POST['selected_option'] ) : '';
		$end_date = gmdate( 'Y-m-d' );
		
		global $wpdb;
		$woo_trackship_shipment = $wpdb->prefix . 'trackship_shipment';
		$total_shipment = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipping_date BETWEEN %s AND %s", $start_date, $end_date ) );
		$active_shipment = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipment_status NOT LIKE ( %s ) AND shipping_date BETWEEN %s AND %s", '%delivered%', $start_date, $end_date ) );
		$delivered_shipment = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipment_status LIKE ( %s ) AND shipping_date BETWEEN %s AND %s", '%delivered%', $start_date, $end_date ) );
		$tracking_issues = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row	
			WHERE 
				shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
				AND shipping_date BETWEEN %s AND %s
		", '%delivered%', '%pre_transit%', '%in_transit%','%out_for_delivery%', '%return_to_sender%', '%available_for_pickup%', '%exception%', $start_date, $end_date ) );
		
		$result['total_shipment']		= $total_shipment;
		$result['active_shipment']		= $active_shipment;
		$result['delivered_shipment']	= $delivered_shipment;
		$result['tracking_issues']		= $tracking_issues;
		
		wp_send_json($result);
	}
	
	/*
	* get html of fields
	*/
	public function get_html( $arrays ) {
		
		$checked = '';
		?>
		<table class="form-table">
			<tbody>
				<?php 
				foreach ( (array) $arrays as $id => $array ) {
					if ( $array['show'] ) {
						if ( 'title' == $array['type'] ) {
							?>
							<tr valign="top titlerow">
								<th colspan="2"><h3><?php echo esc_html( $array['title'] ); ?></h3></th>
							</tr>    	
						<?php continue; } ?>
						<tr valign="top" class="<?php echo esc_html( $array['class'] ); ?>">
							<?php if ( 'desc' != $array['type'] ) { ?>										
							<th scope="row" class="titledesc">
								<label for=""><?php echo esc_html( $array['title'] ); ?><?php echo isset ( $array['title_link'] ) ? esc_html( $array['title_link'] ) : ''; ?>
									<?php if ( isset($array['tooltip']) ) { ?>
										<span class="woocommerce-help-tip tipTip" title="<?php echo esc_html( $array['tooltip'] ); ?>"></span>
									<?php } ?>
								</label>
							</th>
							<?php } ?>
							<td class="forminp" <?php echo 'desc' == $array['type'] ? '' : 'colspan=2'; ?>>
								<?php 
								if ( 'checkbox' == $array['type'] ) {								
									if ( 'wcast_enable_delivered_email' === $id ) {
										$wcast_enable_delivered_email = get_option('woocommerce_customer_delivered_order_settings');
										
										if ( 'yes' == $wcast_enable_delivered_email['enabled'] || 1 == $wcast_enable_delivered_email['enabled'] ) {
											$checked = 'checked';
										} else {
											$checked = '';									
										}
										
									} elseif ( 'wcast_enable_partial_shipped_email' === $id ) {
										$wcast_enable_partial_shipped_email = get_option('woocommerce_customer_partial_shipped_order_settings');
								
										if ( 'yes' == $wcast_enable_partial_shipped_email['enabled'] || 1 == $wcast_enable_partial_shipped_email['enabled'] ) {
											$checked = 'checked';
										} else {
											$checked = '';									
										}								
									} else {																		
										if ( get_option($id) ) {
											$checked = 'checked';
										} else {
											$checked = '';
										} 
									} 
									
									if ( isset ($array['disabled']) && true == $array['disabled'] ) {
										$disabled = 'disabled';
										$checked = '';
									} else {
										$disabled = '';
									}							
									?>
									<input type="hidden" name="<?php echo esc_html( $id ); ?>" value="0"/>
									<input class="tgl tgl-flat" id="<?php echo esc_html( $id ); ?>" name="<?php echo esc_html( $id ); ?>" type="checkbox" <?php echo esc_html( $checked ); ?> value="1" <?php echo esc_html( $disabled ); ?>/>
									<label class="tgl-btn" for="<?php echo esc_html( $id ); ?>"></label>	
								<?php } elseif ( isset ( $array['type'] ) && 'dropdown' == $array['type'] ) { ?>
									<?php
									if ( isset($array['multiple'] ) ) {
										$multiple = 'multiple';
										$field_id = $array['multiple'];
									} else {
										$multiple = '';
										$field_id = $id;
									}
									?>
									<fieldset>
										<select class="select select2" id="<?php echo esc_html( $field_id ); ?>" name="<?php echo esc_html( $id ); ?>" <?php echo esc_html( $multiple ); ?>>
											<?php 
											foreach ( (array) $array['options'] as $key => $val ) {
												$selected = '';
												if ( isset ( $array['multiple'] ) ) {
													if ( in_array( $key, (array) $this->data->$field_id ) ) {
														$selected = 'selected';
													}
												} else {
													if ( get_option($id) == (string) $key ) {
														$selected = 'selected';
													}
												}
												?>
												<option value="<?php echo esc_html( $key ); ?>" <?php echo esc_html( $selected ); ?> ><?php echo esc_html( $val ); ?></option>
											<?php } ?>
										</select>
									</fieldset>
								<?php } elseif ( isset ( $array['type'] ) && 'radio' == $array['type'] ) { ?>                        	
									<fieldset>
										<?php 
										foreach ( (array) $array['options'] as $key => $val ) {
											$selected = '';									
											if ( get_option( $id, $array['default'] ) == (string) $key ) {
												$selected = 'checked';
											}
											?>
											<span class="radio_section">
												<label class="" for="<?php echo esc_html( $id); ?>_<?php echo esc_html( $key ); ?>">												
													<input type="radio" id="<?php echo esc_html( $id ); ?>_<?php echo esc_html( $key ); ?>" name="<?php echo esc_html( $id ); ?>" class="<?php echo esc_html( $id ); ?>"  value="<?php echo esc_html( $key ); ?>" <?php echo esc_html( $selected ); ?>/>
													<span class=""><?php echo esc_html( $val ); ?></span>	
													</br>
												</label>																		
											</span></br>	
										<?php } ?>								
									</fieldset>
								<?php } elseif ( 'key_field' == $array['type'] ) { ?>
								<?php } elseif ( 'label' == $array['type'] ) { ?>
									<fieldset>
										<label><?php echo esc_html( $array['value'] ); ?></label>
									</fieldset>
								<?php } elseif ( 'tooltip_button' == $array['type'] ) { ?>
									<fieldset>
										<a href="<?php echo esc_html( $array['link'] ); ?>" class="button-primary" target="<?php echo esc_html( $array['target'] ); ?>"><?php echo esc_html( $array['link_label'] ); ?></a>
									</fieldset>
								<?php } elseif ( 'button' == $array['type'] ) { ?>
									<fieldset>
										<button class="button-primary btn_green2 <?php echo esc_html( $array['button_class'] ); ?>" <?php echo 1 == $array['disable'] ? 'disabled' : ''; ?>>
											<?php echo esc_html( $array['label'] ); ?>
										</button>
									</fieldset>
								<?php } else { ?>
									<fieldset>
										<input class="input-text regular-input " type="text" name="<?php echo esc_html( $id ); ?>" id="<?php echo esc_html( $id ); ?>" style="" value="<?php echo esc_html( get_option($id) ); ?>" placeholder="<?php echo !empty( $array['placeholder'] ) ? esc_html( $array['placeholder'] ) : ''; ?>">
									</fieldset>
								<?php } ?>
							</td>
						</tr>
						<?php if ( isset( $array['desc'] ) && '' != $array['desc'] ) { ?>
							<tr class="<?php echo esc_html( $array['class'] ); ?>"><td colspan="2" style=""><p class="description"><?php echo esc_html( ( isset( $array['desc'] ) ) ? $array['desc']: '' ); ?></p></td></tr>
						<?php } ?>				
					<?php } ?>
				<?php } ?>
			</tbody>
		</table>
	<?php 
	}
	
	/*
	* get html of fields
	*/
	public function get_html_2( $arrays ) {
		
		$checked = '';
		?>
		<table class="form-table table-layout-2">
			<tbody>
				<?php foreach ( (array) $arrays as $id => $array ) { ?>
					<?php if ( $array['show'] ) { ?>                						
						<tr valign="top" class="<?php echo esc_html( $array['class'] ); ?>">
							<th scope="row" class="titledesc"  <?php echo 'desc' == $array['type'] ? 'colspan=2' : ''; ?>>
								<?php
								if ( 'checkbox' == $array['type'] ) {								
									if ( 'wcast_enable_delivered_email' === $id ) {
										$wcast_enable_delivered_email = get_option('woocommerce_customer_delivered_order_settings');
										
										if ( 'yes' == $wcast_enable_delivered_email['enabled'] || 1 == $wcast_enable_delivered_email['enabled'] ) {
											$checked = 'checked';
										} else {
											$checked = '';									
										}
										
									} elseif ( 'wcast_enable_partial_shipped_email' === $id ) {
										$wcast_enable_partial_shipped_email = get_option('woocommerce_customer_partial_shipped_order_settings');
								
										if ( 'yes' == $wcast_enable_partial_shipped_email['enabled'] || 1 == $wcast_enable_partial_shipped_email['enabled'] ) {
											$checked = 'checked';
										} else {
											$checked = '';									
										}								
									} else {																		
										if ( get_option( $id) ) {
											$checked = 'checked';
										} else {
											$checked = '';
										} 
									} 
									if ( isset( $array['disabled'] ) && true == $array['disabled'] ) {
										$disabled = 'disabled';
										$checked = '';
									} else {
										$disabled = '';
									}							
									?>
								<input type="hidden" name="<?php echo esc_html( $id ); ?>" value="0"/>
								<input class="tgl tgl-flat" id="<?php echo esc_html( $id ); ?>" name="<?php echo esc_html( $id ); ?>" type="checkbox" <?php echo esc_html( $checked ); ?> value="1" <?php echo esc_html( $disabled ); ?>/>
								<label class="tgl-btn" for="<?php echo esc_html( $id ); ?>"></label>
							<?php } elseif ( isset( $array['type'] ) && 'dropdown' == $array['type'] ) { ?>
								<?php
									if ( isset( $array['multiple'] ) ) {
										$multiple = 'multiple';
										$field_id = $array['multiple'];
									} else {
										$multiple = '';
										$field_id = $id;
									}
									?>
								<fieldset>
									<select class="select select2" id="<?php echo esc_html( $field_id ); ?>" name="<?php echo esc_html( $id ); ?>" <?php echo esc_html( $multiple ); ?>>
										<?php foreach ( (array) $array['options'] as $key => $val ) { ?>
											<?php
											$selected = '';
											if ( isset( $array['multiple'] ) ) {
												if ( in_array( $key, (array) $this->data->$field_id ) ) {
													$selected = 'selected';
												}
											} else {
												if ( get_option($id) == (string) $key ) {
													$selected = 'selected';
												}
											}
											?>
											<option value="<?php echo esc_html( $key ); ?>" <?php echo esc_html( $selected ); ?> ><?php echo esc_html( $val ); ?></option>
										<?php } ?>
									</select>
								</fieldset>
							<?php } elseif ( 'label' == $array['type'] ) { ?>
								<fieldset>
									<label><?php echo esc_html( $array['value'] ); ?></label>
								</fieldset>
							<?php } elseif ( 'tooltip_button' == $array['type'] ) { ?>
								<fieldset>
									<a href="<?php echo esc_html( $array['link'] ); ?>" class="button-primary" target="<?php echo esc_html( $array['target'] ); ?>"><?php echo esc_html( $array['link_label'] ); ?></a>
								</fieldset>
							<?php } elseif ( 'button' == $array['type'] ) { ?>
								<fieldset>
									<button class="button-primary btn_green2 <?php echo esc_html( $array['button_class'] ); ?>" <?php echo 1 == $array['disable'] ? 'disabled' : ''; ?>>
										<?php echo esc_html( $array['label'] ); ?>
									</button>
								</fieldset>
							<?php } else { ?>
								<fieldset>
									<input class="input-text regular-input " type="text" name="<?php echo esc_html( $id ); ?>" id="<?php echo esc_html( $id ); ?>" style="" value="<?php echo esc_html( get_option($id) ); ?>" placeholder="<?php echo !empty( $array['placeholder'] ) ? esc_html( $array['placeholder'] ) : ''; ?>">
								</fieldset>
							<?php } ?>
							</th>
							<?php if ( 'desc' != $array['type'] ) { ?>										
								<th class="forminp">
									<label for="">
									<span>
										<?php echo esc_html( $array['title'] ); ?>
										<?php if ( isset( $array['tooltip'] ) ) { ?>
											<span class="woocommerce-help-tip tipTip" title="<?php echo esc_html( $array['tooltip'] ); ?>"></span>
										<?php } ?>
									</span>
									<span class="html2_title1"><?php echo esc_html( $array['title1'] ); ?></span>
									</label>						
								</th>
							<?php } ?>
						</tr>
						<?php if ( isset($array['desc']) && '' != $array['desc'] ) { ?>
							<tr class="<?php echo esc_html( $array['class'] ); ?>">
								<td colspan="2" style="">
									<p class="description"><?php echo esc_html( isset( $array['desc'] ) ? $array['desc'] : '' ); ?></p>
								</td>
							</tr>
						<?php } ?>				
					<?php } ?>
				<?php } ?>
			</tbody>
		</table>
	<?php 
	}
	
	/*
	* get html of fields
	*/
	public function get_html_ul( $arrays ) {
		?>
		<ul class="settings_ul">
		<?php foreach ( (array) $arrays as $id => $array ) { ?>
			<?php
			if ( $array['show'] ) { 
				if ( 'checkbox' == $array['type'] ) {
					$checked = get_option($id) ? 'checked' : '';
					?>
					<li>
						<input type="hidden" name="<?php echo esc_html( $id ); ?>" value="0"/>
						<input class="" id="<?php echo esc_html( $id ); ?>" name="<?php echo esc_html( $id ); ?>" type="checkbox" <?php echo esc_html( $checked ); ?> value="1"/>
											
						<label class="setting_ul_checkbox_label" for="<?php echo esc_html( $id ); ?>"><?php echo esc_html( $array['title'] ); ?>
						<?php if ( isset( $array['tooltip'] ) ) { ?>
							<span class="woocommerce-help-tip tipTip" title="<?php echo esc_html( $array['tooltip'] ); ?>"></span>
						<?php } ?>
						</label>						
					</li>	
				<?php
				} else if ( 'tgl_checkbox' == $array['type'] ) {
					if ( get_option($id) ) {
						$checked = 'checked';
					} else {
						$checked = '';
					}
					$tgl_class = '';	
					if ( isset( $array['tgl_color'] ) ) {
						$tgl_class = 'ast-tgl-btn-green';
					}
					?>
					<li>
						<input type="hidden" name="<?php echo esc_html( $id ); ?>" value="0"/>
						<input class="ast-tgl ast-tgl-flat" id="<?php echo esc_html( $id ); ?>" name="<?php echo esc_html( $id ); ?>" type="checkbox" <?php echo esc_html( $checked ); ?> value="1"/>
						<label class="ast-tgl-btn <?php echo esc_html( $tgl_class ); ?>" for="<?php echo esc_html( $id ); ?>"></label>
											
						<label class="setting_ul_tgl_checkbox_label" for="<?php echo esc_html( $id ); ?>"><?php echo esc_html( $array['title'] ); ?>
						<?php if ( isset( $array['tooltip'] ) ) { ?>
							<span class="woocommerce-help-tip tipTip" title="<?php echo esc_html( $array['tooltip'] ); ?>"></span>
						<?php } ?>
						</label>
						<?php if ( isset( $array['customize_link'] ) ) { ?>
							<a href="<?php echo esc_url( $array['customize_link'] ); ?>" class="button-primary btn_outline"><?php esc_html_e( 'Customize', 'trackship-for-woocommerce' ); ?></a>	
						<?php } ?>
					</li>	
				<?php } else if ( 'radio' == $array['type'] ) { ?>
					<li class="settings_radio_li">
						<label><strong><?php esc_html_e( $array['title'] ); ?></strong>
							<?php if ( isset($array['tooltip'] ) ) { ?>
								<span class="woocommerce-help-tip tipTip" title="<?php echo esc_html( $array['tooltip'] ); ?>"></span>
							<?php } ?>
						</label>	
						<?php 
						foreach ( (array) $array['options'] as $key => $val ) {
							$selected = ( get_option( $id, $array['default'] ) == (string) $key ) ? 'checked' : '';							
							?>
							<span class="radio_section">
								<label class="" for="<?php echo esc_html( $id ); ?>_<?php echo esc_html( $key ); ?>">												
									<input type="radio" id="<?php echo esc_html( $id ); ?>_<?php echo esc_html( $key ); ?>" name="<?php echo esc_html( $id ); ?>" class="<?php echo esc_html( $id ); ?>"  value="<?php echo esc_html( $key ); ?>" <?php echo esc_html( $selected ); ?>/>
									<span class=""><?php echo esc_html( $val ); ?></span>	
									</br>
								</label>																		
							</span>
						<?php } ?>
					</li>					
				<?php } else if ( 'multiple_select' == $array['type'] ) { ?>
					<li class="multiple_select_li">
						<label><?php esc_html_e( $array['title'] ); ?>
							<?php if ( isset($array['tooltip']) ) { ?>
								<span class="woocommerce-help-tip tipTip" title="<?php esc_html_e( $array['tooltip'] ); ?>"></span>
							<?php } ?>
						</label>
						<div class="multiple_select_container">	
							<select multiple class="wc-enhanced-select" name="<?php echo esc_html( $id ); ?>[]" id="<?php echo esc_html( $id ); ?>">
							<?php
							foreach ( (array) $array['options'] as $key => $val ) { 
								$multi_checkbox_data = get_option($id);
								$selected = in_array( $key, $multi_checkbox_data ) ? 'selected' : '';
								?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php echo esc_html( $selected ); ?>><?php echo esc_html( $val ); ?></option>
							<?php } ?>
							</select>	
						</div>
					</li>	
				<?php } else if ( 'dropdown_tpage' == $array['type'] ) { ?>
					<li class="li_<?php esc_html_e( $id ); ?>">
						<label class="left_label"><b><?php esc_html_e( $array['title'] ); ?></b>
							<?php if ( isset( $array['tooltip'] ) ) { ?>
								<span class="woocommerce-help-tip tipTip" title="<?php esc_html_e( $array['tooltip'] ); ?>"></span>
							<?php } ?>
						</label>
						<span style="display: block; padding-top: 10px;">
							<select class="select select2 tracking_page_select" id="<?php echo esc_html( $id ); ?>" name="<?php echo esc_html( $id ); ?>">
								<?php foreach ( (array) $array['options'] as $page_id => $page_name ) { ?>
									<option <?php echo get_option( $id ) == $page_id ? 'selected' : ''; ?> value="<?php echo esc_html( $page_id ); ?>"><?php esc_html_e( $page_name ); ?></option>
								<?php } ?>
								<option <?php echo 'other' == get_option( $id ) ? 'selected' : ''; ?> value="other"><?php esc_html_e( 'Other', 'trackship-for-woocommerce' ); ?>
								</option>	
							</select>
							<fieldset style="<?php echo 'other' != get_option( $id ) ? 'display:none;' : 'padding-top: 10px;'; ?>" class="trackship_other_page_fieldset">
								<input type="text" name="wc_ast_trackship_other_page" id="wc_ast_trackship_other_page" value="<?php echo esc_html( get_option('wc_ast_trackship_other_page') ); ?>">
							</fieldset>
							<p class="tracking_page_desc"><?php esc_html_e( 'Add the [trackship-track-order] shortcode in the selected page.', 'trackship-for-woocommerce' ); ?> <a href="https://www.zorem.com/docs/woocommerce-advanced-shipment-tracking/integration/" target="blank"><?php esc_html_e( 'more info', 'trackship-for-woocommerce' ); ?></a></p>
						</span>
					</li>	
				<?php } else if ( 'button' == $array['type'] ) { ?>
					<li>
						<?php if ( $array['title'] ) { ?>
							<label class="left_label"><?php echo esc_html( $array['title'] ); ?>
								<?php if ( isset($array['tooltip']) ) { ?>
								<span class="woocommerce-help-tip tipTip" title="<?php echo esc_html( $array['tooltip'] ); ?>"></span>
								<?php } ?>
							</label>
						<?php } ?>
						<?php if ( isset($array['customize_link']) ) { ?>
							<a href="<?php echo esc_url( $array['customize_link'] ); ?>" class="button-primary btn_ts_sidebar ts_customizer_btn"><?php esc_html_e( 'Customize the Tracking Widget', 'trackship-for-woocommerce' ); ?></a>	
						<?php } ?>	
					</li>	
				<?php } elseif ( isset( $array['type'] ) && 'dropdown' == $array['type'] ) { ?>
						<?php
							$field_id = $id;
						?>
						<li>
						<label class="left_label"><?php esc_html_e( $array['title'] ); ?>
							<?php if ( isset($array['tooltip']) ) { ?>
								<span class="woocommerce-help-tip tipTip" title="<?php esc_html_e( $array['tooltip'] ); ?>"></span>
							<?php } ?>
						</label>
						<fieldset style="display: inline-block;vertical-align: top;">
							<select class="select select2" id="<?php esc_html_e( $field_id ); ?>" name="<?php esc_html_e( $id ); ?>" >
								<?php foreach ( (array) $array['options'] as $key => $val ) { ?>
									<?php
									$selected = '';
									if ( isset( $array['multiple'] ) ) {
										if ( in_array( $key, (array) $this->data->$field_id ) ) {
											$selected = 'selected';
										}
									} else {
										if ( get_option($id) == (string) $key ) {
											$selected = 'selected';
										}
									}
									?>
									<option value="<?php esc_html_e( $key ); ?>" <?php esc_html_e( $selected ); ?> ><?php esc_html_e( $val ); ?></option>
								<?php } ?>
							</select>
						</fieldset>
					</li>
				<?php
				}
			}
		}
		?>
		</ul>
	<?php
	}	
	
	/*
	* get settings tab array data
	* return array
	*/
	public function get_trackship_general_data() {
		$order_statuses = wc_get_order_statuses();
		
		$status_array = array();
		foreach ( $order_statuses as $key => $val ) {

			if ( 'wc-cancelled' == $key ) {
				continue;
			}
			if ( 'wc-failed' == $key ) {
				continue;
			}
			if ( 'wc-pending' == $key ) {
				continue;
			}

			$status_slug = ( 'wc-' === substr( $key, 0, 3 ) ) ? substr( $key, 3 ) : $key;
			$status_array[$status_slug] = $val;
		}
		
		$form_data = array(
			'trackship_trigger_order_statuses' => array(
				'type'		=> 'multiple_select',
				'title'		=> __( 'Order statuses to trigger TrackShip ', 'trackship-for-woocommerce' ),
				'tooltip'	=> __( 'Choose on which order emails to include the shipment tracking info', 'trackship-for-woocommerce' ),
				'options'   => $status_array,					
				'show'		=> true,
				'class'     => '',
			),
			'wc_ast_show_shipment_status_filter' => array(
				'type'		=> 'tgl_checkbox',
				'title'		=> __( 'Enable a shipment status filter on orders admin', 'trackship-for-woocommerce' ),				
				'show'		=> true,
				'class'     => '',				
			),
		);

		if ( ( is_plugin_active( 'wp-lister-for-amazon/wp-lister-amazon.php' ) || is_plugin_active( 'wp-lister-amazon/wp-lister-amazon.php' ) ) && !in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ) {
			$form_data[ 'enable_notification_for_amazon_order' ] = array(
				'type'		=> 'tgl_checkbox',
				'title'		=> __( 'Enable shipment status notification for order created by Amazon', 'trackship-for-woocommerce' ),				
				'show'		=> true,
				'class'     => '',				
			);
		}

		return $form_data;
	}

	public function get_trackship_notice_msg() {
		$completed_order_with_tracking = $this->completed_order_with_tracking();
		$completed_order_with_zero_balance = $this->completed_order_with_zero_balance();
		$completed_order_with_do_connection = $this->completed_order_with_do_connection();
		$total_orders = $completed_order_with_tracking + $completed_order_with_zero_balance + $completed_order_with_do_connection;
		$cookie = isset( $_COOKIE['Notice'] ) ? sanitize_text_field( $_COOKIE['Notice'] ) : '';
		if ( 'delete' != $cookie && $total_orders > 0 ) {
			?>
			<div class="trackship_notice_msg tools_tab_ts4wc">
				<div class="trackship-notice" style="border: 0;">
					<?php /* translators: %s: search for a total_orders */ ?>
					<p><?php printf( esc_html__( 'We detected %s Shipped orders from the last 30 days that were not sent to TrackShip, you can bulk send them to TrackShip', 'trackship-for-woocommerce' ), esc_html( $total_orders ) ); ?><span class="dashicons remove-icon dashicons-no-alt"></span></p>
					<button class="button-primary button-trackship bulk_shipment_status_button" <?php echo 0 == $total_orders ? 'disabled' : ''; ?>><?php esc_html_e( 'Get Shipment Status', 'trackship-for-woocommerce' ); ?></button>
				</div>
			</div>
			<?php
		}
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
		
		foreach ( $orders as $order ) {
			$order_id = $order->get_id();
			$order = wc_get_order( $order_id );
			$tracking_items = $order->get_meta( '_wc_shipment_tracking_items', true );			
			
			if ( $tracking_items ) {
				$shipment_status = $order->get_meta( 'shipment_status', true );
				foreach ( $tracking_items as $key => $tracking_item ) { 				
					if ( !isset($shipment_status[$key]) ) {
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
			$order = wc_get_order( $order_id );
			$tracking_items = $order->get_meta( '_wc_shipment_tracking_items', true );			
			
			if ( $tracking_items ) {
				$shipment_status = $order->get_meta( 'shipment_status', true );
				foreach ( $tracking_items as $key => $tracking_item ) {
					if ( isset( $shipment_status[$key]['pending_status']) && 'insufficient_balance' == $shipment_status[$key]['pending_status'] ) {
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
			$order = wc_get_order( $order_id );
			$tracking_items = $order->get_meta( '_wc_shipment_tracking_items', true );			
			
			if ( $tracking_items ) {	
				$shipment_status = $order->get_meta( 'shipment_status', true );
				foreach ( $tracking_items as $key => $tracking_item ) { 					
					if ( isset( $shipment_status[$key]['pending_status'] ) && in_array( $shipment_status[$key]['pending_status'], array( 'connection_issue', 'unauthorized' ) ) ) {
						$completed_order_with_do_connection++;		
					}
				}									
			}			
		}				
		return $completed_order_with_do_connection;
	}
	
	/*
	 * get_zorem_pluginlist
	 * 
	 * return array
	*/
	public function get_zorem_pluginlist() {
		
		if ( !empty( $this->zorem_pluginlist ) ) { 
			return $this->zorem_pluginlist; 
		}
		
		$plugin_list = get_transient( 'zorem_pluginlist' );
		if ( false === $plugin_list ) {
			
			$response = wp_remote_get( 'https://www.zorem.com/wp-json/pluginlist/v1/' );
			
			if ( is_array( $response ) && ! is_wp_error( $response ) ) {
				$body    = $response['body']; // use the content
				$plugin_list = json_decode( $body );
				set_transient( 'zorem_pluginlist', $plugin_list, 60*60*24 );
			} else {
				$plugin_list = array();
			}
		}
		return $this->zorem_pluginlist;
		
	}	
	
	/** 
	* Register new status : Delivered
	**/
	public function register_order_status() {						
		register_post_status( 'wc-delivered', array(
			'label'                     => __( 'Delivered', 'trackship-for-woocommerce' ),
			'public'                    => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list'    => true,
			'exclude_from_search'       => false,
			/* translators: %s: search number of order */
			'label_count'               => _n_noop( 'Delivered <span class="count">(%s)</span>', 'Delivered <span class="count">(%s)</span>', 'trackship-for-woocommerce' )
		) );
	}
	
	/*
	* add status after completed
	*/
	public function add_delivered_to_order_statuses( $order_statuses ) {							
		$new_order_statuses = array();
		foreach ( $order_statuses as $key => $status ) {
			$new_order_statuses[ $key ] = $status;
			if ( 'wc-completed' === $key ) {
				$new_order_statuses['wc-delivered'] = __( 'Delivered', 'trackship-for-woocommerce' );				
			}
		}
		
		return $new_order_statuses;
	}
	
	/*
	* Adding the custom order status to the default woocommerce order statuses
	*/
	public function include_custom_order_status_to_reports( $statuses ) {
		if ( $statuses ) {
			$statuses[] = 'delivered';
		}
		return $statuses;
	}
	
	/*
	* mark status as a paid.
	*/
	public function delivered_woocommerce_order_is_paid_statuses( $statuses ) { 
		$statuses[] = 'delivered';
		return $statuses; 
	}
	
	/*
	* add bulk action
	* Change order status to delivered
	*/
	public function add_bulk_actions( $bulk_actions ) {
		$lable = wc_get_order_status_name( 'delivered' );
		$bulk_actions['mark_delivered'] = __( 'Change status to ' . $lable . '', 'trackship-for-woocommerce' );	
		return $bulk_actions;		
	}
	
	/*
	* add order again button for delivered order status	
	*/
	public function add_reorder_button_delivered( $statuses ) {
		$statuses[] = 'delivered';
		return $statuses;	
	}

	/*
	* Add delivered action button in preview order list to change order status from completed to delivered
	*/
	public function additional_admin_order_preview_buttons_actions( $actions, $order ) {
		
		$wc_ast_status_delivered = get_option( 'wc_ast_status_delivered' );
		if ( $wc_ast_status_delivered ) {
			// Below set your custom order statuses (key / label / allowed statuses) that needs a button
			$custom_statuses = array(
				'delivered' => array( // The key (slug without "wc-")
					'label'     => __( 'Delivered', 'ast-pro' ), // Label name
					'allowed'   => array( 'completed'), // Button displayed for this statuses (slugs without "wc-")
				),
			);
		
			// Loop through your custom orders Statuses
			foreach ( $custom_statuses as $status_slug => $values ) {
				if ( $order->has_status( $values['allowed'] ) ) {
					$actions[ 'status' ][ 'group' ] = __( 'Change status: ', 'woocommerce' );
					$actions[ 'status' ][ 'actions' ][ $status_slug ] = array(
						'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=' . $status_slug . '&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' ),
						'name'   => $values['label'],
						'title'  => __( 'Change order status to', 'ast-pro' ) . ' ' . strtolower( $values['label'] ),
						'action' => $status_slug,
					);
				}
			}
		}		
		return $actions;
	}
	
	/*
	* Add action button in order list to change order status from completed to delivered
	*/
	public function add_delivered_order_status_actions_button( $actions, $order ) {
		
		$wc_ast_status_delivered = get_option( 'wc_ast_status_delivered' );
		
		if ( $wc_ast_status_delivered ) {
			if ( $order->has_status( array( 'completed' ) ) || $order->has_status( array( 'shipped' ) ) ) {
				
				// Get Order ID (compatibility all WC versions)
				$order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
				
				// Set the action button
				$actions['delivered'] = array(
					'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=delivered&order_id=' . $order_id ), 'woocommerce-mark-order-status' ),
					'name'      => __( 'Mark order as delivered', 'ast-pro' ),
					'icon' => '<i class="fa fa-truck">&nbsp;</i>',
					'action'    => 'delivered_icon', // keep "view" class for a clean button CSS
				);
			}	
		}
		
		return $actions;
	}

	/*
	* change style of delivered order label
	*/	
	public function footer_function() {
		if ( !is_plugin_active( 'woocommerce-order-status-manager/woocommerce-order-status-manager.php' ) ) {
			$bg_color = get_option('wc_ast_status_label_color', '#09d3ac');
			$color = get_option('wc_ast_status_label_font_color', '#000');
			?>
			<style>
			.order-status.status-delivered,.status-label-li .order-label.wc-delivered{
				background: <?php echo esc_html( $bg_color ); ?>;
				color: <?php echo esc_html( $color ); ?>;
			}
			</style>
		<?php } ?>
		<style> #toplevel_page_trackship_customizer { display: none !important; } </style>
		<?php echo '<div id=admin_tracking_widget class=popupwrapper style="display:none;"><div class=popuprow></div><div class=popupclose></div></div>'; ?>
		<div id=admin_error_more_info_widget class=popupwrapper style="display:none;">
			<div class="more_info_popup popuprow">
				<?php
				$ssl = '<a href="https://docs.trackship.com/docs/trackship-for-woocommerce/getting-started/requirements/#ssl-certificate">SSL Requirements</a>';
				$array_more_info = array(
					'pending_trackShip' => array(
						'heading'	=> 'Pending TrackShip',
						'detail'	=> 'This is a temporary status that indicated that the tracking info was sent to TrackShip and the first tracking data will update shortly. please try to refresh the orders admin in a few minutes.',
						'image'		=> 'pending-trackship.png',
					),
					'invalid_tracking' => array(
						'heading'	=> 'Invalid Tracking',
						'detail'	=> 'The Tracking number you entered for this order does not match the shipping carrier.',
						'image'		=> 'unknown.png',
					),
					'carrier_unsupported' => array(
						'heading'	=> 'Carrier Unsupported',
						'detail'	=> 'This message indicates that TrackShip does not support the shipping provider you used.',
						'image'		=> 'label_cancelled.png',
					),
					'insufficient_balance' => array(
						'heading'	=> 'Insufficient Balance',
						'detail'	=> 'The Shipment Tracking balance in your TrackShip account is 0, please upgrade your billing plan.',
						'image'		=> 'failure.png',
					),
					'ssl_error' => array(
						'heading'	=> 'SSL Error',
						'detail'	=> 'This means that you got SSL issue on your store, check the ' . $ssl . ' for more details.',
						'image'		=> 'failure.png',
					),
					'Unauthorized' => array(
						'heading'	=> 'Unauthorized',
						'detail'	=> 'Your store connection key does not exist or the user is not found on TrackShip. Please log in to your TrackShip account, disconnect your store, and re-connect it.',
						'image'		=> 'failure.png',
					),
				);
				?>
				<div class="more_info_error_detail">
					<?php foreach ( $array_more_info as $key => $value ) { ?>
						<div class="error_details">
							<div class="">
								<img src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/css/icons/<?php echo esc_html( $value['image'] ); ?>">
								<span class="shipment_status_label <?php echo esc_html( $key ); ?>"><?php echo esc_html( $value['heading'] ); ?></span>
							</div>
							<p><?php echo wp_kses_post( $value['detail'] ); ?></p>
						</div>
					<?php } ?>
				</div>
			</div>
			<div class=popupclose></div>
		</div>
		<div id="free_user_popup" class="popupwrapper" style="display:none;">
			<div class="free_user_popup popuprow" style="padding:20px">
				<h1 style="text-align: center;"><?php esc_html_e( 'Upgrade to TrackShip Pro', 'trackship-for-woocommerce' ); ?></h1>
				<div style="margin-top: 30px;display:flex;">
					<div style="position: relative; width: 100%;">
						<ul>
							<li><?php esc_html_e( 'Priority Support', 'trackship-for-woocommerce' ); ?></li>
							<li><?php esc_html_e( 'SMS Notifications', 'trackship-for-woocommerce' ); ?></li>
							<li><?php esc_html_e( 'Remove TrackShip’s branding', 'trackship-for-woocommerce' ); ?></li>
							<li><?php esc_html_e( 'Shipments Dashboard', 'trackship-for-woocommerce' ); ?></li>
							<li><?php esc_html_e( 'Late Shipments Notifications', 'trackship-for-woocommerce' ); ?></li>
							<li><?php esc_html_e( 'Shipping & Delivery Analytics', 'trackship-for-woocommerce' ); ?></li>
							<p style="font-size: 16px;"><?php esc_html_e( 'Starting from $9 a month', 'trackship-for-woocommerce' ); ?></p>
						</ul>
						<div>
							<a href="https://my.trackship.com/?utm_source=wpadmin&utm_medium=TS4WC&utm_campaign=shipment"><button class="button-primary button-trackship btn_large" style="font-size: 17px; padding: 8px 30px; background-color: #09d3ac;border-color:#09d3ac;"><?php esc_html_e( 'UPGRADE TO PRO', 'trackship-for-woocommerce' ); ?><span style="line-height: 18px;" class="dashicons dashicons-arrow-right-alt2"></span></button></a>
						</div>
					</div>
					<div style="position: relative; width: 100%;">
						<img src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/images/popup-free-user.png" style="">
					</div>
				</div>
			</div>
			<?php $page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : ''; ?>
			<?php if ( !in_array( $page, array( 'trackship-shipments' ) ) ) { ?>
				<div class="popupclose"></div>
			<?php } ?>
		</div>
        <div id="" class="popupwrapper sync_trackship_provider_popup" style="display:none;">
			<div class="popuprow trackship_provider">
				<div class="popup_header">
					<h3 class="popup_title"><?php esc_html_e( 'Sync TrackShip Providers', 'trackship-for-woocommerce'); ?></h3>						
					<span class="dashicons dashicons-no-alt popup_close_icon"></span>
				</div>	
				<div class="popup_body">	
					<p class="sync_message"><?php esc_html_e( 'Syncing the TrackShip providers list add or updates the pre-set TrackShip providers and will not effect custom shipping providers.', 'trackship-for-woocommerce' ); ?></p>
					<ul class="synch_result">
						<li class="providers_updated"><?php esc_html_e( 'Providers list Updated', 'trackship-for-woocommerce' ); ?></li>
					</ul>
					<button class="sync_trackship_providers_btn button-primary button-trackship"><?php esc_html_e( 'Sync TrackShip Providers', 'trackship-for-woocommerce' ); ?></button>
					<div class="spinner"></div>
				</div>
                <input type="hidden" id="nonce_trackship_provider" value="<?php esc_html_e( wp_create_nonce( 'nonce_trackship_provider' ) ); ?>">
			</div>	
			<div class="popupclose"></div>
		</div>
		<div class="popupwrapper trackship_logs_details" style="display:none;">
			<div class="popuprow">
				<div class="popup_header">
					<h3 class="popup_title"><?php esc_html_e( 'Notifications detail', 'trackship-for-woocommerce'); ?></h3>						
					<span class="dashicons dashicons-no-alt popup_close_icon"></span>
				</div>
				<div class="popup_body">
					<div class="order_id"><strong><?php esc_html_e( 'Order Number', 'trackship-for-woocommerce' ); ?></strong><span></span></div>
					<div class="shipment_status"><strong><?php esc_html_e( 'Shipment status', 'trackship-for-woocommerce' ); ?></strong><span></span></div>
					<div class="tracking_number"><strong><?php esc_html_e( 'Tracking Number', 'trackship-for-woocommerce' ); ?></strong><span></span></div>
					<div class="time"><strong><?php esc_html_e( 'Time', 'trackship-for-woocommerce' ); ?></strong><span></span></div>
					<div class="to"><strong><?php esc_html_e( 'To', 'trackship-for-woocommerce' ); ?></strong><span></span></div>
					<div class="type"><strong><?php esc_html_e( 'Type', 'trackship-for-woocommerce' ); ?></strong><span></span></div>
					<div class="status"><strong><?php esc_html_e( 'Status', 'trackship-for-woocommerce' ); ?></strong><span></span></div>
				</div>
			</div>
			<div class="popupclose"></div>
		</div>
	<?php
	}
	
	public function get_trackship_provider() {
		
		global $wpdb;
		$ts_shippment_providers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}trackship_shipping_provider" );
		return $ts_shippment_providers ;
		
	}
	
	/*
	* Return add maping table row
	*/
	public function add_trackship_mapping_row() {		
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			exit( 'You are not allowed' );
		}
		$ts_shippment_providers = $this->get_trackship_provider();
		
		ob_start();
		?>
		<tr>
			<td><input type="text" class="map_shipping_provider_text" name="detected_provider[]"></td>
			<td>
				<select name="ts_provider[]" class="select2">
					<option value=""><?php esc_html_e( 'Select', 'woocommerce' ); ?></option>
					<?php foreach ( $ts_shippment_providers as $ts_provider ) { ?>
						<option value="<?php echo esc_html( $ts_provider->ts_slug ); ?>"><?php echo esc_html( $ts_provider->provider_name ); ?></option>	
					<?php } ?>
				</select>
				<span class="dashicons dashicons-trash remove_custom_maping_row"></span>
			</td>
		</tr>
		
		<?php 
		$html = ob_get_clean();	
		wp_send_json( array( 'table_row' => $html) );
	}
	
	/*
	* Return add maping table row
	*/
	public function remove_tracking_event() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			exit( 'You are not allowed' );
		}
		check_ajax_referer( 'wc_ast_tools', 'security' );
		$days = isset( $_POST['days'] ) ? sanitize_text_field($_POST['days']) : false;
		$args = array(
			'post_type'  => 'shop_order',
			'posts_per_page' => '1000',
			'meta_query' => array(
				'relation' => 'AND',
				'shipment_status' => array(
					'key' => 'shipment_status',
					'value' => 'delivered',
					'compare' => 'LIKE',
				),
				array(
					'key' => 'shipment_events_deleted',
					'compare' => 'NOT EXISTS'
				),
			),
			'date_query' => array(
				array(
					'before' => '-' . $days . ' days',
					'column' => 'post_date',
				),
			),
			'post_status' => array_keys( wc_get_order_statuses() )
		);
		$query = new WP_Query( $args );
		while ( $query->have_posts() ) {
			$query->the_post();
			$order_id = get_the_id();
			$order = wc_get_order( $order_id );
			$shipment_status = $order->get_meta( 'shipment_status', true );
			foreach ( $shipment_status as $key => $val ) {
				$shipment_status[$key]['tracking_events'] = array();
				$shipment_status[$key]['tracking_destination_events'] = array();
			}
			$order = wc_get_order( $order_id );

			$order->update_meta_data( 'shipment_status', $shipment_status );
			$order->update_meta_data( 'shipment_events_deleted', 1 );
			$order->save();
		}
		$json = array(
			'order_count' => $query->post_count,
			'found_orders' => $query->found_posts
		);
		wp_send_json($json);
	}
	
	public function remove_trackship_logs() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			exit( 'You are not allowed' );
		}
		check_ajax_referer( 'wc_ast_tools', 'security' );
		global $wpdb;
		$log_table = $this->log_table;
		$row_query = $wpdb->get_results("
			DELETE
				FROM {$log_table}
			WHERE ( `type` = 'Email' OR `sms_type` = 'shipment_status' ) AND `date` < NOW() - INTERVAL 30 DAY;
		");
		wp_send_json( array( 'success' => 'true' ) );
	}

	/*
	* Save Custom Mapping data
	*/
	public function trackship_custom_mapping_form_update() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			exit( 'You are not allowed' );
		}
		if ( ! empty( $_POST ) && check_admin_referer( 'trackship_mapping_form', 'trackship_mapping_form_nonce' ) ) {
			
			$map_provider_array = array();
			if ( !empty( $_POST['detected_provider'] ) ) {
				foreach ( wc_clean( $_POST['detected_provider'] ) as $key => $provider ) {
					if ( isset( $_POST[ 'ts_provider' ][ $key ] ) ) {
						$map_provider_array[$provider] = wc_clean( $_POST['ts_provider'][$key] );
					}
								
				}		
			}
			update_option( 'trackship_map_provider', $map_provider_array );		
			wp_send_json( array( 'success' => 'true' ) );
		}
	}

	public function detect_custom_mapping_provider( $tracking_provider ) {
		$map_provider_array = get_option( 'trackship_map_provider', [] );

		// $map_provider_array key replace space to '-' and lower case for WooCommerce Shipment tracking plugin
		if ( trackship_for_woocommerce()->is_st_active() ) {
			$map_provider_array = array_change_key_case( $map_provider_array, CASE_LOWER );
			$keys = str_replace( ' ', '-', array_keys( $map_provider_array ) );
			$map_provider_array = array_combine( $keys, array_values( $map_provider_array ) );
		}

		if ( isset( $map_provider_array[ $tracking_provider ] ) ) {
			return $map_provider_array[ $tracking_provider ];
		}
		return $tracking_provider;
	}

	/*
	* number of days
	*/
	public function get_num_of_days( $first_date, $last_date ) {
		$date1 = strtotime($first_date);
		$date2 = strtotime($last_date);
		$diff = abs($date2 - $date1);
		return gmdate( 'd', $diff );
	}
	
	/*
	* late shipments form save
	*/
	public function ts_late_shipments_email_form_update_callback() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			exit( 'You are not allowed' );
		}
		if ( ! empty( $_POST ) && check_admin_referer( 'ts_late_shipments_email_form', 'ts_late_shipments_email_form_nonce' ) ) {
			
			$wcast_late_shipments_email_to = isset( $_POST['wcast_late_shipments_email_to'] ) ? sanitize_text_field( $_POST['wcast_late_shipments_email_to'] ) : '';			
			$wcast_late_shipments_daily_digest_time = isset( $_POST['wcast_late_shipments_daily_digest_time'] ) ? sanitize_text_field( $_POST['wcast_late_shipments_daily_digest_time'] ) : '';
			$wcast_enable_late_shipments_admin_email = isset( $_POST['wcast_enable_late_shipments_admin_email'] ) ? sanitize_text_field( $_POST['wcast_enable_late_shipments_admin_email'] ) : '';

			$late_shipments_email_settings = get_option( 'late_shipments_email_settings', array() );
			$late_shipments_email_settings[ 'wcast_enable_late_shipments_admin_email' ] = $wcast_enable_late_shipments_admin_email;
			$late_shipments_email_settings[ 'wcast_late_shipments_email_to' ] = $wcast_late_shipments_email_to;
			$late_shipments_email_settings[ 'wcast_late_shipments_daily_digest_time' ] = $wcast_late_shipments_daily_digest_time;
			update_option( 'late_shipments_email_settings', $late_shipments_email_settings );
						
			$Late_Shipments = new WC_TrackShip_Late_Shipments();
			$Late_Shipments->remove_cron();
			$Late_Shipments->setup_cron();
			$return = array(
				'message'	=> 'success',
				'data'		=> $late_shipments_email_settings,
			);
			wp_send_json_success( $return );
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
		);
		return $form_data;
	}
	
	/*
	* get settings tab array data
	* return array
	*/
	public function get_tracking_page_data() {		
		$page_list = wp_list_pluck( get_pages(), 'post_title', 'ID' );
		
		$slug = '';
		
		$wc_ast_trackship_page_id = get_option('wc_ast_trackship_page_id');
		$post = get_post($wc_ast_trackship_page_id); 
		if ( $post ) {
			$slug = $post->post_name;
		}
		
		if ( 'ts-shipment-tracking' != $slug ) {
			$page_desc = '';
		} else {
			$page_desc = '';
		}
										
		$form_data = array(
			'wc_ast_use_tracking_page' => array(
				'type'		=> 'tgl_checkbox',
				'title'		=> __( 'Enable Tracking Page', 'trackship-for-woocommerce' ),				
				'show'		=> true,
				'class'     => 'wc_ast_use_tracking_page',				
			),											
			'wc_ast_trackship_page_id' => array(
				'type'		=> 'dropdown_tpage',
				'title'		=> __( 'Select tracking page:', 'trackship-for-woocommerce' ),
				'options'   => $page_list,				
				'show'		=> true,
				'desc'		=> $page_desc,
				'class'     => '',
			),
			'wc_ast_trackship_other_page' => array(
				'type'		=> 'text',
				'title'		=> __( 'Other', '' ),						
				'show'		=> true,				
				'class'     => '',
			),
			'wc_ast_tracking_page_customize_btn' => array(
				'type'		=> 'button',
				'title'		=> '',						
				'show'		=> true,				
				'class'     => '',
				'customize_link' => admin_url( 'admin.php?page=trackship_customizer' ),
			),	
		);
		return $form_data;
	}
	
	public function trackship_shipment_status_notifications_data() {
		$notifications_data = array(
			'in_transit' => array(					
				'title'			=> __( 'In Transit', 'trackship-for-woocommerce' ),
				'slug' => 'in-transit',
				'option_name'	=> 'wcast_intransit_email_settings',
				'enable_status_name' => 'wcast_enable_intransit_email',		
				'customizer_url' => admin_url( 'admin.php?page=trackship_customizer&type=shipment_email&status=in_transit' ),	
			),
			'available_for_pickup' => array(					
				'title'	=> __( 'Available For Pickup', 'trackship-for-woocommerce' ),
				'slug'  => 'available-for-pickup',
				'option_name'	=> 'wcast_availableforpickup_email_settings',
				'enable_status_name' => 'wcast_enable_availableforpickup_email',		
				'customizer_url' => admin_url( 'admin.php?page=trackship_customizer&type=shipment_email&status=available_for_pickup' ),	
			),
			'out_for_delivery' => array(					
				'title'	=> __( 'Out For Delivery', 'trackship-for-woocommerce' ),
				'slug'  => 'out-for-delivery',
				'option_name'	=> 'wcast_outfordelivery_email_settings',
				'enable_status_name' => 'wcast_enable_outfordelivery_email',		
				'customizer_url' => admin_url( 'admin.php?page=trackship_customizer&type=shipment_email&status=out_for_delivery' ),	
			),
			'failure' => array(					
				'title'	=> __( 'Failed Attempt', 'trackship-for-woocommerce' ),
				'slug'  => 'failed-attempt',
				'option_name'	=> 'wcast_failure_email_settings',
				'enable_status_name' => 'wcast_enable_failure_email',		
				'customizer_url' => admin_url( 'admin.php?page=trackship_customizer&type=shipment_email&status=failure' ),	
			),
			'on_hold' => array(					
				'title'	=> __( 'On Hold', 'trackship-for-woocommerce' ),
				'slug'  => 'on-hold',
				'option_name'	=> 'wcast_onhold_email_settings',
				'enable_status_name' => 'wcast_enable_onhold_email',		
				'customizer_url' => admin_url( 'admin.php?page=trackship_customizer&type=shipment_email&status=on_hold' ),	
			),
			'exception' => array(					
				'title'	=> __( 'Exception', 'trackship-for-woocommerce' ),
				'slug'  => 'exception',
				'option_name'	=> 'wcast_exception_email_settings',
				'enable_status_name' => 'wcast_enable_exception_email',		
				'customizer_url' => admin_url( 'admin.php?page=trackship_customizer&type=shipment_email&status=exception' ),	
			),
			'return_to_sender' => array(					
				'title'	=> __( 'Return To Sender', 'trackship-for-woocommerce' ),
				'slug'  => 'return-to-sender',
				'option_name'	=> 'wcast_returntosender_email_settings',
				'enable_status_name' => 'wcast_enable_returntosender_email',		
				'customizer_url' => admin_url( 'admin.php?page=trackship_customizer&type=shipment_email&status=return_to_sender' ),	
			),
			'delivered' => array(					
				'title'	=> __( 'Delivered', 'trackship-for-woocommerce' ),
				'title2'=> __( 'Send only when all shipments for the order are delivered', 'trackship-for-woocommerce' ),
				'slug'  => 'delivered-status',
				'option_name'	=> 'wcast_delivered_status_email_settings',
				'enable_status_name' => 'wcast_enable_delivered_status_email',		
				'customizer_url' => admin_url( 'admin.php?page=trackship_customizer&type=shipment_email&status=delivered' ),
			),
		);
		return $notifications_data;
	}
	
	public function calculate_percent( $first, $second ) {
		if ( 0 == $second ) {
			return '';
		}
		$percent = $first * 100 / $second;
		return '(' . round( $percent, 2 ) . '%)';
	}

	/*
	* transaltion function for loco generater
	* this function is not called from any function
	*/
	public function translation_func() {
		__( 'Tracking Analytics', 'trackship-for-woocommerce');
		__( 'SMS Settings', 'trackship-for-woocommerce');
	}
}
