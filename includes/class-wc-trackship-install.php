<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Trackship_Install {
	
	/**
	 * Initialize the main plugin function
	*/
	public function __construct() {
		$this->init();
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
	 * @return WC_Trackship_Install
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
		add_action( 'admin_init', array( $this, 'update_database_check' ) );
		add_action( 'wp_ajax_update_trackship_providers', array( $this, 'update_trackship_providers' ) );
	}
	
	/*
	* database update
	*/
	public function update_database_check() {
			
		if ( version_compare( get_option( 'trackship_db' ), '1.0', '<' ) ) {
			update_trackship_settings( 'ts_tracking_page', 1 );

			update_trackship_email_settings( 'available_for_pickup', 'enable', 1 );
			update_trackship_email_settings( 'out_for_delivery', 'enable', 1 );
			update_trackship_email_settings( 'delivered', 'enable', 1 );

			$this->create_shipping_provider_table();
			$this->update_shipping_providers();
			$this->create_shipment_table();
			$this->create_shipment_meta_table();
			$this->create_email_log_table();
			update_option( 'trackship_db', '1.0' );
		}

		global $wpdb;
		if ( version_compare( get_option( 'trackship_db' ), '1.19', '<' ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}trackship_shipment CHANGE shipping_date shipping_date DATE NULL" );
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}trackship_shipment_meta MODIFY COLUMN shipping_service varchar(60);" );
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}trackship_shipment MODIFY COLUMN order_number varchar(40);" );
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}zorem_email_sms_log MODIFY COLUMN order_number varchar(40);" );

			update_trackship_settings( 'trackship_db', '1.19' );
			update_option( 'trackship_db', '1.19' );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.20', '<' ) ) {
			
			$wc_ts_shipment_status_filter = get_option( 'wc_ast_show_shipment_status_filter' );
			if ( $wc_ts_shipment_status_filter ) {
				update_trackship_settings( 'wc_ts_shipment_status_filter', $wc_ts_shipment_status_filter );
			}

			$enable_email_widget = get_option( 'enable_email_widget' );
			if ( $enable_email_widget ) {
				update_trackship_settings( 'enable_email_widget', $enable_email_widget );
			}

			$enable_notification_for_amazon_order = get_option( 'enable_notification_for_amazon_order', 1 );
			if ( $enable_notification_for_amazon_order ) {
				update_trackship_settings( 'enable_notification_for_amazon_order', $enable_notification_for_amazon_order );
			}

			$late_shipments_days = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('late_shipments_email_settings', 'wcast_late_shipments_days', 7 );
			if ( $late_shipments_days ) {
				update_trackship_settings( 'late_shipments_days', $late_shipments_days );
			}

			$wc_ast_use_tracking_page = get_option( 'wc_ast_use_tracking_page', '' );
			if ( $wc_ast_use_tracking_page ) {
				update_trackship_settings( 'wc_ast_use_tracking_page', $wc_ast_use_tracking_page );
			}

			$wc_ast_trackship_page_id = get_option( 'wc_ast_trackship_page_id', '' );
			if ( $wc_ast_trackship_page_id ) {
				update_trackship_settings( 'wc_ast_trackship_page_id', $wc_ast_trackship_page_id );
			}

			$wc_ast_trackship_other_page = get_option( 'wc_ast_trackship_other_page', '' );
			if ( $wc_ast_trackship_other_page ) {
				update_trackship_settings( 'wc_ast_trackship_other_page', $wc_ast_trackship_other_page );
			}

			delete_option( 'trackship_trigger_order_statuses' );
			delete_option( 'wc_ast_show_shipment_status_filter' );
			delete_option( 'enable_email_widget' );
			delete_option( 'enable_notification_for_amazon_order' );
			delete_option( 'wc_ast_use_tracking_page' );
			delete_option( 'wc_ast_trackship_page_id' );
			delete_option( 'wc_ast_trackship_other_page' );
			$late_shipments_settings = get_option( 'late_shipments_email_settings', [] );
			unset($late_shipments_settings['wcast_late_shipments_days']);
			update_option( 'late_shipments_email_settings', $late_shipments_settings );

			update_trackship_settings( 'trackship_db', '1.20' );
			update_option( 'trackship_db', '1.20' );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.21', '<' ) ) {
			$late_email_enable = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('late_shipments_email_settings', 'wcast_enable_late_shipments_admin_email', '' );
			if ( $late_email_enable ) {
				update_trackship_settings( 'late_shipments_email_enable', $late_email_enable );
			}
			$late_email_to = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('late_shipments_email_settings', 'wcast_late_shipments_email_to', '' );
			if ( $late_email_to ) {
				update_trackship_settings( 'late_shipments_email_to', $late_email_to );
			}
			$late_digest_time = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('late_shipments_email_settings', 'wcast_late_shipments_daily_digest_time', '' );
			if ( $late_digest_time ) {
				update_trackship_settings( 'late_shipments_digest_time', $late_digest_time );
			}
			delete_option( 'late_shipments_email_settings' );
			update_option( 'trackship_db', '1.21' );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.23', '<' ) ) {
			$email_trackship_branding = trackship_for_woocommerce()->ts_actions->get_option_value_from_array( 'shipment_email_settings', 'email_trackship_branding', 1 );
			$tp_trackship_branding = get_option( 'wc_ast_remove_trackship_branding', 0 );
			$value = 1;
			if ( 1 != $email_trackship_branding || $tp_trackship_branding ) {
				$value = 0;
			}
			$option_data = get_option( 'shipment_email_settings', array() );
			unset( $option_data['email_trackship_branding'] );
			$option_data['show_trackship_branding'] = $value;
			update_option( 'shipment_email_settings', $option_data );
			delete_option( 'wc_ast_remove_trackship_branding' );

			update_trackship_settings( 'trackship_db', '1.23' );
			update_option( 'trackship_db', '1.23' );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.24', '<' ) ) {
			$Exception_Shipments = new WC_TrackShip_Exception_Shipments();
			$Exception_Shipments->remove_cron();
			$Exception_Shipments->setup_cron();

			$On_Hold_Shipments = new WC_TrackShip_On_Hold_Shipments();
			$On_Hold_Shipments->remove_cron();
			$On_Hold_Shipments->setup_cron();

			update_trackship_settings( 'trackship_db', '1.24' );
			update_option( 'trackship_db', '1.24' );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.25', '<' ) ) {
			$wc_ast_select_bg_color = get_option( 'wc_ast_select_bg_color', '#fafafa' );
			$wc_ast_select_font_color = get_option( 'wc_ast_select_font_color', '#333' );
			$wc_ast_select_border_color = get_option( 'wc_ast_select_border_color', '#cccccc' );
			$wc_ast_select_border_radius = get_option( 'wc_ast_select_border_radius', 0 );
			$wc_ast_select_link_color = get_option( 'wc_ast_select_link_color', '#2271b1' );
			$tracking_page_type = get_option( 'tracking_page_type', 'modern' );
			$wc_ast_hide_tracking_events = get_option( 'wc_ast_hide_tracking_events', 2 );
			$wc_ast_select_tracking_page_layout = get_option( 'wc_ast_select_tracking_page_layout', 't_layout_1' );
			$wc_ast_link_to_shipping_provider = get_option( 'wc_ast_link_to_shipping_provider', 1 );
			$wc_ast_hide_tracking_provider_image = get_option( 'wc_ast_hide_tracking_provider_image', 0 );
			$wc_ast_hide_from_to = get_option( 'wc_ast_hide_from_to', 1 );
			$wc_ast_hide_list_mile_tracking = get_option( 'wc_ast_hide_list_mile_tracking', 1 );

			update_trackship_settings( 'wc_ts_bg_color', $wc_ast_select_bg_color );
			update_trackship_settings( 'wc_ts_font_color', $wc_ast_select_font_color );
			update_trackship_settings( 'wc_ts_border_color', $wc_ast_select_border_color );
			update_trackship_settings( 'wc_ts_border_radius', $wc_ast_select_border_radius );
			update_trackship_settings( 'wc_ts_link_color', $wc_ast_select_link_color );
			update_trackship_settings( 'tracking_page_type', $tracking_page_type );
			update_trackship_settings( 'ts_tracking_events', $wc_ast_hide_tracking_events );
			update_trackship_settings( 'ts_tracking_page_layout', $wc_ast_select_tracking_page_layout );
			update_trackship_settings( 'ts_link_to_carrier', $wc_ast_link_to_shipping_provider );
			update_trackship_settings( 'hide_provider_image', $wc_ast_hide_tracking_provider_image );
			update_trackship_settings( 'ts_hide_from_to', $wc_ast_hide_from_to );
			update_trackship_settings( 'ts_hide_list_mile_tracking', $wc_ast_hide_list_mile_tracking );

			delete_option( 'wc_ast_select_bg_color' );
			delete_option( 'wc_ast_select_font_color' );
			delete_option( 'wc_ast_select_border_color' );
			delete_option( 'wc_ast_select_border_radius' );
			delete_option( 'wc_ast_select_link_color' );
			delete_option( 'tracking_page_type' );
			delete_option( 'wc_ast_hide_tracking_events' );
			delete_option( 'wc_ast_select_tracking_page_layout' );
			delete_option( 'wc_ast_link_to_shipping_provider' );
			delete_option( 'wc_ast_hide_tracking_provider_image' );
			delete_option( 'wc_ast_hide_from_to' );
			delete_option( 'wc_ast_hide_list_mile_tracking' );
			update_option( 'trackship_db', '1.25' );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.26', '<' ) ) {

			if ( $wpdb->get_var( "SHOW COLUMNS FROM {$wpdb->prefix}trackship_shipment LIKE 'updated_date';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}trackship_shipment CHANGE COLUMN updated_date ship_length_updated DATE;" );
			}

			update_trackship_settings( 'trackship_db', '1.26' );
			update_option( 'trackship_db', '1.26' );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.30', '<' ) ) {
			update_trackship_settings( 'trackship_db', '1.30' );
			update_option( 'trackship_db', '1.30' );

			$ts_delivered_status = get_option( 'wc_ast_status_delivered', 1 );
			update_trackship_settings( 'ts_delivered_status', $ts_delivered_status );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.31', '<' ) ) {
			update_trackship_settings( 'trackship_db', '1.31' );
			update_option( 'trackship_db', '1.31' );

			$ts_tracking_page = get_trackship_settings( 'wc_ast_use_tracking_page');
			update_trackship_settings( 'ts_tracking_page', $ts_tracking_page );

			$tracking_page_id = get_trackship_settings( 'wc_ast_trackship_page_id');
			update_trackship_settings( 'tracking_page_id', $tracking_page_id );

			$tracking_other_page = get_trackship_settings( 'wc_ast_trackship_other_page');
			update_trackship_settings( 'tracking_other_page', $tracking_other_page );

			delete_trackship_settings( 'wc_ast_use_tracking_page' );
			delete_trackship_settings( 'wc_ast_trackship_page_id' );
			delete_trackship_settings( 'wc_ast_trackship_other_page' );
		}
		
		if ( version_compare( get_option( 'trackship_db' ), '1.33', '<' ) ) {
			update_trackship_settings( 'trackship_db', '1.33' );
			update_option( 'trackship_db', '1.33' );
			
			delete_trackship_settings( 'ts_review_ignore' );
			delete_trackship_settings( 'ts_bulk_send_ignore' );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.34', '<' ) ) {
			update_trackship_settings( 'trackship_db', '1.34' );
			update_option( 'trackship_db', '1.34' );

			if ( $wpdb->get_var( "SHOW COLUMNS FROM {$wpdb->prefix}trackship_shipment LIKE 'shipping_date';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}trackship_shipment MODIFY `shipping_date` DATE DEFAULT NULL;" );
			}
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.35', '<' ) ) {
			update_trackship_settings( 'trackship_db', '1.35' );
			update_option( 'trackship_db', '1.35' );

			$valid_order_statuses = get_trackship_settings( 'trackship_trigger_order_statuses' );
			if ( empty( $valid_order_statuses ) ) {
				update_trackship_settings( 'trackship_trigger_order_statuses', ['completed', 'partial-shipped', 'shipped'] );
			}
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.36', '<' ) ) {
			update_trackship_settings( 'trackship_db', '1.36' );
			update_option( 'trackship_db', '1.36' );
			delete_trackship_settings( 'ts_review_ignore_132' );
			delete_trackship_settings( 'ts_popup_ignore' );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.37', '<' ) ) {
			update_trackship_settings( 'trackship_db', '1.37' );
			update_option( 'trackship_db', '1.37' );
			
			delete_trackship_settings( 'ts_review_ignore_136' );
			delete_trackship_settings( 'ts_popup_ignore136' );
		}

		// TS4WC version 1.9.1
		if ( version_compare( get_option( 'trackship_db' ), '1.38', '<' ) ) {
			update_trackship_settings( 'trackship_db', '1.38' );
			update_option( 'trackship_db', '1.38' );

			delete_option( 'wc_ast_api_key' );
			delete_option( 'wc_ast_api_enabled' );
		}

		// TS4WC version 1.9.2
		if ( version_compare( get_option( 'trackship_db' ), '1.39', '<' ) ) {
			update_trackship_settings( 'trackship_db', '1.39' );
			update_option( 'trackship_db', '1.39' );
			delete_trackship_settings( 'ts_review_ignore_137' );
			delete_trackship_settings( 'ts_popup_ignore137' );

			$statuses = array(
				'in_transit' => __( 'In Transit', 'trackship-for-woocommerce' ),
				'available_for_pickup' => __( 'Available For Pickup', 'trackship-for-woocommerce' ),
				'out_for_delivery' => __( 'Out For Delivery', 'trackship-for-woocommerce' ),
				'failure' => __( 'Delivery Failure', 'trackship-for-woocommerce' ),
				'on_hold' => __( 'On Hold', 'trackship-for-woocommerce' ),
				'exception' => __( 'Exception', 'trackship-for-woocommerce' ),
				'return_to_sender' => __( 'Return To Sender', 'trackship-for-woocommerce' ),
				'delivered' => __( 'Delivered', 'trackship-for-woocommerce' ),
				'pickup_reminder' => __( 'Available for Pickup Reminder', 'trackship-for-woocommerce' ),
			);

			$emails_keys = [
				'enable',
				'subject',
				'heading',
				'content',
				'show_order_details',
				'show_product_image',
				'show_shipping_address',
				'days',
			];

			foreach ( $statuses as $key => $status ) {
				foreach ( $emails_keys as $key1 ) {
					$value = '';
					$value = 'subject' == $key1 ? sprintf( __( 'Your order #%s is %s', 'trackship-for-woocommerce' ), '{order_number}', $status ) : $value;
					$value = 'heading' == $key1 ? $status : $value;
					$value = 'content' == $key1 ? sprintf( __( "Hi there. We thought you'd like to know that your recent order from %s is %s", 'trackship-for-woocommerce' ), '{site_title}', $status ) : $value;
					$value = 'content' == $key1 && 'pickup_reminder' == $key ? __( "Hi there. we thought you'd like to know that your recent order from {site_title} is pending for pickup", 'trackship-for-woocommerce' ) : $value;
					$value = 'show_order_details' == $key1 ? 1 : $value;
					$value = 'show_product_image' == $key1 ? 1 : $value;
					$value = 'show_shipping_address' == $key1 ? 1 : $value;
					if ( 'pickup_reminder' == $key && 'days' == $key1 ) {
						$value = 2;
					} elseif ( 'pickup_reminder' != $key && 'days' == $key1 ) {
						continue;
					} elseif ( 'pickup_reminder' == $key && 'show_shipping_address' == $key1 ) {
						continue;
					}
					update_trackship_email_settings( $key, $key1, $value );
				}
			}

			$all_statuses = array(
				'intransit' => 'in_transit',
				'availableforpickup' => 'available_for_pickup',
				'outfordelivery' => 'out_for_delivery',
				'failure' => 'failure',
				'onhold' => 'on_hold',
				'exception' => 'exception',
				'returntosender' => 'return_to_sender',
				'delivered_status' => 'delivered',
				'pickupreminder' => 'pickup_reminder',
			);

			foreach	( $all_statuses as $key2 => $slug ) {
				$email_settings = 'wcast_' . $key2 . '_email_settings';
				$value = '';
				$enable = trackship_for_woocommerce()->actions->get_option_value_from_array( $email_settings, 'wcast_enable_' . $key2 . '_email', '' );
				if ( $enable ) {
					update_trackship_email_settings( $slug, 'enable', $enable );
				}
				$subject = trackship_for_woocommerce()->actions->get_option_value_from_array( $email_settings, 'wcast_' . $key2 . '_email_subject', '' );
				if ( $subject ) {
					update_trackship_email_settings( $slug, 'subject', $subject );
				}
				$heading = trackship_for_woocommerce()->actions->get_option_value_from_array( $email_settings, 'wcast_' . $key2 . '_email_heading', '' );
				if ( $heading ) {
					update_trackship_email_settings( $slug, 'heading', $heading );
				}
				$content = trackship_for_woocommerce()->actions->get_option_value_from_array( $email_settings, 'wcast_' . $key2 . '_email_content', '' );
				if ( $content ) {
					update_trackship_email_settings( $slug, 'content', $content );
				}
				$show_order_details = trackship_for_woocommerce()->actions->get_option_value_from_array( $email_settings, 'wcast_' . $key2 . '_show_order_details', '' );
				if ( $show_order_details ) {
					update_trackship_email_settings( $slug, 'show_order_details', $show_order_details );
				}
				$show_product_image = trackship_for_woocommerce()->actions->get_option_value_from_array( $email_settings, 'wcast_' . $key2 . '_show_product_image', '' );
				if ( $show_product_image ) {
					update_trackship_email_settings( $slug, 'show_product_image', $show_product_image );
				}
				if ( 'pickupreminder' != $key2 ) {
					$show_shipping_address = trackship_for_woocommerce()->actions->get_option_value_from_array( $email_settings, 'wcast_' . $key2 . '_show_shipping_address', '' );
					if ( $show_shipping_address ) {
						update_trackship_email_settings( $slug, 'show_shipping_address', $show_shipping_address );
					}
				} else {
					$days = trackship_for_woocommerce()->actions->get_option_value_from_array( $email_settings, $key2 . '_days', '' );
					if ( $days ) {
						update_trackship_email_settings( $slug, 'days', $days );
					}
				}
			}

			$shipment_email_default_settings = [
				'common_settings' => [
					'border_color' => '#e8e8e8',
					'link_color' => '',
					'bg_color' => '#fff',
					'font_color' => '#333',
					'tracking_page_layout' => 't_layout_2',
					'track_button_Text' => __( 'Track your order', 'trackship-for-woocommerce' ),
					'track_button_color' => '#3c4858',
					'track_button_text_color' => '#fff',
					'track_button_border_radius' => 0,
					'show_trackship_branding' => 1,
					'shipping_provider_logo' => 1,
				]
			];
			foreach ( $shipment_email_default_settings['common_settings'] as $key => $value ) {
				update_trackship_email_settings( 'common_settings', $key, $value );
			}

			$shipment_email_settings = get_option( 'shipment_email_settings', [] );
			foreach ( $shipment_email_settings as $key => $value ) {
				update_trackship_email_settings( 'common_settings', $key, $value );
			}

			$array_data = get_option('tracking_form_settings');
			$form_tab_view = $array_data['form_tab_view'] ?? 'both';
			update_trackship_settings( 'form_tab_view', $form_tab_view );
			
			$form_button_Text = $array_data['form_button_Text'] ?? __( 'Track your order', 'trackship-for-woocommerce' );
			update_trackship_settings( 'form_button_Text', $form_button_Text );
			
			$form_button_color = $array_data['form_button_color'] ?? '#3c4858';
			update_trackship_settings( 'form_button_color', $form_button_color );
			
			$form_button_text_color = $array_data['form_button_text_color'] ?? '#fff';
			update_trackship_settings( 'form_button_text_color', $form_button_text_color );
			
			$form_button_border_radius = $array_data['form_button_border_radius'] ?? 0;
			update_trackship_settings( 'form_button_border_radius', $form_button_border_radius );

			$shipped_product_label = get_option( 'shipped_product_label', __( 'Items in this shipment', 'trackship-for-woocommerce' ) );
			$shipping_address_label = get_option( 'shipping_address_label', __( 'Shipping address', 'trackship-for-woocommerce' ) );

			update_trackship_email_settings( 'common_settings', 'shipped_product_label', $shipped_product_label );
			update_trackship_email_settings( 'common_settings', 'shipping_address_label', $shipping_address_label );

		}

		// TS4WC version 1.9.7
		if ( version_compare( get_option( 'trackship_db' ), '1.41', '<' ) ) {
			update_trackship_settings( 'trackship_db', '1.41' );
			update_option( 'trackship_db', '1.41' );
			$this->create_shipment_table();
			$this->create_shipment_meta_table();
			$this->check_column_exists();

			$this->create_shipping_provider_table();
			$this->update_shipping_providers();
			
			// Indexes to check and create
			$indexes_to_check = [
				'last_event' => 'ADD INDEX `last_event` (`last_event`(100))',
				'first_event_time' => 'ADD INDEX `first_event_time` (`first_event_time`)',
				'shipment_status_first_event_time' => 'ADD INDEX `shipment_status_first_event_time` (`shipment_status`, `first_event_time`)',
			];

			foreach ( $indexes_to_check as $index => $value ) {
				$index_exists = $wpdb->get_results( $wpdb->prepare( "SHOW INDEX FROM {$wpdb->prefix}trackship_shipment WHERE Key_name = %s", $index ) );
				if ( empty( $index_exists ) ) {
					$wpdb->query( "ALTER TABLE {$wpdb->prefix}trackship_shipment {$value}" );
				}
			}
			update_trackship_settings( 'ts_use_villa_email_template', 1 );

			delete_option('tracking_form_settings');
			delete_option('shipment_email_settings');
			delete_option('shipped_product_label');
			delete_option('shipping_address_label');
			delete_option('wcast_pickupreminder_email_settings');
			delete_option('wcast_intransit_email_settings');
			delete_option('wcast_returntosender_email_settings');
			delete_option('wcast_availableforpickup_email_settings');
			delete_option('wcast_exception_email_settings');
			delete_option('wcast_onhold_email_settings');
			delete_option('wcast_failure_email_settings');
			delete_option('wcast_delivered_status_email_settings');
			delete_option('wcast_outfordelivery_email_settings');

			delete_trackship_settings( 'ts_review_ignore_139' );
			delete_trackship_settings( 'ts_popup_ignore139' );
		}
	}

	public function update_trackship_providers() {
		if ( check_ajax_referer( 'nonce_trackship_provider', 'security' ) ) {
			$this->create_shipping_provider_table();
			$this->update_shipping_providers();
			wp_send_json( array('success' => 'true') );
		}
	}
	
	/**
	 * Create TrackShip Shipping provider table
	*/
	public function create_shipping_provider_table() {
		global $wpdb;
		$woo_ts_shipment_table_name = $wpdb->prefix . 'trackship_shipping_provider';
		if ( !$wpdb->query( $wpdb->prepare( 'show tables like %s', $woo_ts_shipment_table_name ) ) ) {
			$charset_collate = $wpdb->get_charset_collate();
			$sql = "CREATE TABLE {$wpdb->prefix}trackship_shipping_provider (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				provider_name varchar(500) DEFAULT '' NOT NULL,
				ts_slug text NULL DEFAULT NULL,
				PRIMARY KEY (id)
			) $charset_collate;";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}

	/**
	 * Get providers list from trackship and update providers in database
	*/
	public function update_shipping_providers() {
		global $wpdb;
		// added in version 1.7.6
		$url = 'https://api.trackship.com/v1/shipping_carriers/supported';
		$resp = wp_remote_get( $url );
		
		if ( is_array( $resp ) && ! is_wp_error( $resp ) ) {
		
			$providers = json_decode($resp['body'], true );
			
			$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}trackship_shipping_provider" );
			foreach ( $providers['data'] as $provider ) {
				$data_array = array(
					'provider_name' => $provider['label'],
					'ts_slug' => $provider['slug'],
				);
				$wpdb->insert( $wpdb->prefix . 'trackship_shipping_provider', $data_array );
			}
		}
	}
	
	/**
	 * Create TrackShip notifications logs table
	*/
	public function create_email_log_table() {
		global $wpdb;
		$log_table = $wpdb->prefix . 'zorem_email_sms_log';
		if ( !$wpdb->query( $wpdb->prepare( 'show tables like %s', $log_table ) ) ) {
			$charset_collate = $wpdb->get_charset_collate();
			$sql = "CREATE TABLE {$wpdb->prefix}zorem_email_sms_log (
				`id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
				`order_id` BIGINT(20) ,
				`order_number` VARCHAR(40) ,
				`user_id` BIGINT(20) ,
				`tracking_number` VARCHAR(50) ,
				`date` DATETIME NOT NULL,
				`to` VARCHAR(50) ,
				`shipment_status` VARCHAR(30) ,
				`status` LONGTEXT ,
				`status_msg` varchar(500),
				`type` VARCHAR(20) ,
				`sms_type` VARCHAR(30) ,
				PRIMARY KEY (`id`),
				INDEX `order_id` (`order_id`),
				INDEX `order_number` (`order_number`),
				INDEX `date` (`date`),
				INDEX `to` (`to`),
				INDEX `shipment_status` (`shipment_status`),
				INDEX `type` (`type`),
				INDEX `sms_type` (`sms_type`)
			) $charset_collate;";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}

	/**
	 * Create TrackShip Shipment table
	*/
	public function create_shipment_table() {
		global $wpdb;
		$woo_trackship_shipment = $wpdb->prefix . 'trackship_shipment';
		if ( !$wpdb->query( $wpdb->prepare( 'show tables like %s', $woo_trackship_shipment ) ) ) {
			
			$charset_collate = $wpdb->get_charset_collate();
			$sql = "CREATE TABLE {$wpdb->prefix}trackship_shipment (
				`id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
				`order_id` BIGINT(20) ,
				`order_number` VARCHAR(40) ,
				`tracking_number` VARCHAR(80) ,
				`shipping_provider` VARCHAR(50) ,
				`shipment_status` VARCHAR(30) ,
				`pending_status` VARCHAR(30) ,
				`shipping_date` date ,
				`shipping_country` TEXT ,
				`shipping_length` VARCHAR(10) ,
				`ship_length_updated` DATE ,
				`late_shipment_email` TINYINT DEFAULT 0,
				`exception_email` TINYINT DEFAULT 0,
				`on_hold_email` TINYINT DEFAULT 0,
				`est_delivery_date` DATE,
				`last_event` LONGTEXT ,
				`last_event_time` DATETIME ,
				`first_event_time` DATETIME ,
				`updated_at` DATETIME ,
				PRIMARY KEY (`id`),
				INDEX `shipping_date` (`shipping_date`),
				INDEX `updated_at` (`updated_at`),
				INDEX `status` (`shipment_status`),
				INDEX `tracking_number` (`tracking_number`),
				INDEX `shipping_length` (`shipping_length`),
				INDEX `order_id` (`order_id`),
				INDEX `order_id_tracking_number` (`order_id`,`tracking_number`),
				INDEX `ship_length_updated` (`ship_length_updated`),
				INDEX `late_shipment_email` (`late_shipment_email`),
				INDEX `on_hold_email` (`on_hold_email`),
				INDEX `exception_email` (`exception_email`),
				INDEX `est_delivery_date` (`est_delivery_date`),
				INDEX `last_event` (`last_event`(100)),
				INDEX `first_event_time` (`first_event_time`),
				INDEX `shipment_status_first_event_time` (`shipment_status`,`first_event_time`)
			) $charset_collate;";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}

	/**
	 * Create TrackShip Shipment meta table
	*/
	public function create_shipment_meta_table() {
		global $wpdb;
		$table = $wpdb->prefix . 'trackship_shipment_meta';
		if ( !$wpdb->query( $wpdb->prepare( 'show tables like %s', $table ) ) ) {
			$charset_collate = $wpdb->get_charset_collate();			
			$sql = "CREATE TABLE {$wpdb->prefix}trackship_shipment_meta (
				`meta_id` BIGINT(20),
				`origin_country` VARCHAR(20) ,
				`destination_country` VARCHAR(20) ,
				`delivery_number` VARCHAR(80) ,
				`delivery_provider` VARCHAR(30) ,
				`shipping_service` VARCHAR(60) ,
				`tracking_events` LONGTEXT ,
				`destination_events` LONGTEXT ,
				`destination_state` VARCHAR(40) ,
				`destination_city` VARCHAR(40) ,
				PRIMARY KEY (`meta_id`),
				INDEX `meta_id` (`meta_id`)
			) $charset_collate;";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}

	/**
	 * Check column exists in TrackShip table
	*/
	public function check_column_exists() {
		global $wpdb;

		$shipment_table = array(
			'id'					=> ' BIGINT(20) NOT NULL AUTO_INCREMENT',
			'order_id'				=> ' BIGINT(20)',
			'order_number'			=> ' VARCHAR(40)',
			'tracking_number'		=> ' VARCHAR(80)',
			'shipping_provider'		=> ' VARCHAR(50)',
			'shipment_status'		=> ' VARCHAR(30)',
			'pending_status'		=> ' VARCHAR(30)',
			'shipping_date'			=> ' DATE ',
			'shipping_country'		=> ' TEXT',
			'shipping_length'		=> ' VARCHAR(10)',
			'ship_length_updated'	=> ' DATE',
			'late_shipment_email'	=> ' TINYINT DEFAULT 0',
			'exception_email'=> ' TINYINT DEFAULT 0',
			'on_hold_email'=> ' TINYINT DEFAULT 0',
			'est_delivery_date'		=> ' DATE',
			'last_event'			=> ' LONGTEXT',
			'last_event_time'		=> ' DATETIME',
			'first_event_time'		=> ' DATETIME',
			'updated_at'			=> ' DATETIME',
		);
		foreach ( $shipment_table as $column_name => $type ) {
			$columns = $wpdb->get_var( $wpdb->prepare( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$wpdb->prefix}trackship_shipment' AND COLUMN_NAME = %s", $column_name ));
			if ( ! $columns ) {
				$wpdb->query("ALTER TABLE `{$wpdb->prefix}trackship_shipment` ADD `$column_name` $type");
			}
		}

		$shipment_table_meta = array( 
			'meta_id'				=> ' BIGINT(20)',
			'origin_country'		=> ' VARCHAR(20)',
			'destination_country'	=> ' VARCHAR(20)',
			'delivery_number'		=> ' VARCHAR(80)',
			'delivery_provider'		=> ' VARCHAR(30)',
			'shipping_service'		=> ' VARCHAR(60)',
			'tracking_events'		=> ' LONGTEXT',
			'destination_events'	=> ' LONGTEXT',
			'destination_state'		=> ' VARCHAR(40)',
			'destination_city'		=> ' VARCHAR(40)',
		);
		foreach ( $shipment_table_meta as $column_name => $type ) {
			$columns = $wpdb->get_var( $wpdb->prepare( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$wpdb->prefix}trackship_shipment_meta' AND COLUMN_NAME = %s", $column_name ));
			if ( ! $columns ) {
				$wpdb->query("ALTER TABLE `{$wpdb->prefix}trackship_shipment_meta` ADD `$column_name` $type");
			}
		}

		$log_table = array( 
			'id' => ' BIGINT(20) NOT NULL AUTO_INCREMENT',
			'order_id' => ' BIGINT(20)',
			'order_number' => ' VARCHAR(40)',
			'user_id' => ' BIGINT(20)',
			'tracking_number' => ' VARCHAR(50)',
			'date' => ' DATETIME NOT NULL',
			'to' => ' VARCHAR(50)',
			'shipment_status' => ' VARCHAR(30)',
			'status' => ' LONGTEXT',
			'status_msg' => ' varchar(500)',
			'type' => ' VARCHAR(20)',
			'sms_type' => ' VARCHAR(30)',
		);
		foreach ( $log_table as $column_name => $type ) {
			$columns = $wpdb->get_var( $wpdb->prepare( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$wpdb->prefix}zorem_email_sms_log' AND COLUMN_NAME = %s", $column_name ));
			if ( ! $columns ) {
				$wpdb->query("ALTER TABLE `{$wpdb->prefix}zorem_email_sms_log` ADD `$column_name` $type");
			}
		}
	}

	/**
	 * Check TrackShip database status
	*/
	public function check_tsdb_status () {
		global $wpdb;
		$missing_tables = array();
		$missing_columns = array();
		// Check for trackship_shipment table
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}trackship_shipment'" ) != $wpdb->prefix . 'trackship_shipment' ) {
			$missing_tables[] = $wpdb->prefix . 'trackship_shipment';
		}
	
		// Check for trackship_shipment_meta table
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}trackship_shipment_meta'" ) != $wpdb->prefix . 'trackship_shipment_meta' ) {
			$missing_tables[] = $wpdb->prefix . 'trackship_shipment_meta';
		}

		// Check for columns in trackship_shipment table
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}trackship_shipment'" ) ) {
			$shipment_columns = array(
				'id',
				'order_id',
				'order_number',
				'tracking_number',
				'shipping_provider',
				'shipment_status',
				'pending_status',
				'shipping_date',
				'shipping_country',
				'shipping_length',
				'ship_length_updated',
				'late_shipment_email',
				'exception_email',
				'on_hold_email',
				'est_delivery_date',
				'last_event',
				'last_event_time',
				'first_event_time',
				'updated_at'
			);
			foreach ($shipment_columns as $column) {
				if ( $wpdb->get_var( "SHOW COLUMNS FROM {$wpdb->prefix}trackship_shipment LIKE '{$column}'" ) != $column ) {
					$missing_columns[] = 'Shipment table: ' . $column;
				}
			}
		}

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}trackship_shipment_meta'" ) ) {
			// Check for columns in trackship_shipment_meta table
			$meta_columns = array(
				'meta_id',
				'origin_country',
				'destination_country',
				'delivery_number',
				'delivery_provider',
				'shipping_service',
				'tracking_events',
				'destination_events',
				'destination_state',
				'destination_city',
			);
			foreach ($meta_columns as $column) {
				if ( $wpdb->get_var( "SHOW COLUMNS FROM {$wpdb->prefix}trackship_shipment_meta LIKE '{$column}'" ) != $column ) {
					$missing_columns[] = 'Shipment meta table: ' . $column;
				}
			}
		}

		return array(
			'missing_tables' => $missing_tables,
			'missing_columns' => $missing_columns
		);
	}
}
