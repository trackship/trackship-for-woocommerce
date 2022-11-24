<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Trackship_Install {
	
	/**
	 * Initialize the main plugin function
	*/
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'trackship_shipping_provider';
		$this->shipment_table = $wpdb->prefix . 'trackship_shipment';
		$this->shipment_table_meta = $wpdb->prefix . 'trackship_shipment_meta';
		$this->log_table = $wpdb->prefix . 'zorem_email_sms_log';

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
		add_action( 'update_ts_shipment_status_order_mete', array( $this, 'update_ts_shipment_status_order_mete' ), 10, 1 );
		add_action( 'migrate_trackship_shipment_table', array( $this, 'migrate_trackship_shipment_table' ) );
		add_action( 'wp_ajax_update_trackship_providers', array( $this, 'update_trackship_providers' ) );
	}
	
	/*
	* database update
	*/
	public function update_database_check() {
			
		if ( version_compare( get_option( 'trackship_db' ), '1.0', '<' ) ) {
			update_option( 'trackship_trigger_order_statuses', array( 'completed' ) );
			update_option( 'trackship_db', '1.0' );
		}
		
		if ( version_compare( get_option( 'trackship_db' ), '1.2', '<' ) ) {

			global $wpdb;
			$woo_ts_shipment_table_name = $this->table;
			if ( !$wpdb->query( $wpdb->prepare( 'show tables like %s', $woo_ts_shipment_table_name ) ) ) {
				$charset_collate = $wpdb->get_charset_collate();			
				$sql = "CREATE TABLE $woo_ts_shipment_table_name (
					id mediumint(9) NOT NULL AUTO_INCREMENT,
					provider_name varchar(500) DEFAULT '' NOT NULL,
					ts_slug text NULL DEFAULT NULL,
					PRIMARY KEY  (id)
				) $charset_collate;";			
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );
				$this->update_shipping_providers();												
			}
			
			$this->update_shipping_providers();

			update_option( 'trackship_db', '1.2' );
		}
		
		if ( version_compare( get_option( 'trackship_db' ), '1.3', '<' ) ) {
		
			as_schedule_single_action( time(), 'update_ts_shipment_status_order_mete' , array( 'order_page' => 1 ), '' );
			as_schedule_single_action( time(), 'update_ts_shipment_status_order_mete' , array( 'order_page' => 2 ), '' );
			as_schedule_single_action( time(), 'update_ts_shipment_status_order_mete' , array( 'order_page' => 3 ), '' );
			as_schedule_single_action( time(), 'update_ts_shipment_status_order_mete' , array( 'order_page' => 4 ), '' );
			as_schedule_single_action( time(), 'update_ts_shipment_status_order_mete' , array( 'order_page' => 5 ), '' );
			as_schedule_single_action( time(), 'update_ts_shipment_status_order_mete' , array( 'order_page' => 6 ), '' );
			as_schedule_single_action( time(), 'update_ts_shipment_status_order_mete' , array( 'order_page' => 7 ), '' );
			as_schedule_single_action( time(), 'update_ts_shipment_status_order_mete' , array( 'order_page' => 8 ), '' );
			as_schedule_single_action( time(), 'update_ts_shipment_status_order_mete' , array( 'order_page' => 9 ), '' );
			as_schedule_single_action( time(), 'update_ts_shipment_status_order_mete' , array( 'order_page' => 10 ), '' );				

			update_option( 'trackship_db', '1.3' );
		}
		
		if ( version_compare( get_option( 'trackship_db' ), '1.4', '<' ) ) {
			global $wpdb;
			$wpdb->query("ALTER TABLE $this->table 
				DROP COLUMN provider_url,
				DROP COLUMN shipping_country");
			$this->update_shipping_providers();
			update_option( 'trackship_db', '1.4' );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.5', '<' ) ) {
			global $wpdb;
			$woo_trackship_shipment = $this->shipment_table;
			if ( !$wpdb->query( $wpdb->prepare( 'show tables like %s', $woo_trackship_shipment ) ) ) {
				
				$charset_collate = $wpdb->get_charset_collate();			
				$sql = "CREATE TABLE $woo_trackship_shipment (
					`id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
					`order_id` BIGINT(20) ,
					`order_number` VARCHAR(20) ,
					`tracking_number` VARCHAR(80) ,
					`shipping_provider` VARCHAR(50) ,
					`shipment_status` VARCHAR(30) ,
					`shipping_date` DATE ,
					`shipping_country` TEXT ,
					`shipping_length` VARCHAR(10) ,
					`updated_date` DATE ,
					`late_shipment_email` TINYINT DEFAULT 0,
					PRIMARY KEY (`id`),
					INDEX `shipping_date` (`shipping_date`),
					INDEX `status` (`shipment_status`),
					INDEX `tracking_number` (`tracking_number`),
					INDEX `shipping_length` (`shipping_length`),
					INDEX `order_id` (`order_id`),
					INDEX `order_id_tracking_number` (`order_id`,`tracking_number`),
					INDEX `updated_date` (`updated_date`),
					INDEX `late_shipment_email` (`late_shipment_email`)
				) $charset_collate;";
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );
			}
			$this->update_analytics_table();
			trackship_for_woocommerce()->wc_admin_notice->admin_notices_for_TrackShip_pro();
			update_option( 'trackship_db', '1.5' );
		}
		
		if ( version_compare( get_option( 'trackship_db' ), '1.6', '<' ) ) {
			
			$border_color = get_option('wc_ast_select_border_color', '#cccccc' );
			$background_color = get_option('wc_ast_select_bg_color', '#fafafa' );
			$font_color = get_option('wc_ast_select_font_color', '#333' );
			$tracking_page_layout = get_option('wc_ast_select_tracking_page_layout', '#333' );
			
			$shipment_email_settings = get_option( 'shipment_email_settings' );
			
			$shipment_email_settings['border_color'] = $border_color;
			$shipment_email_settings['bg_color'] = $background_color;
			$shipment_email_settings['font_color'] = $font_color;
			$shipment_email_settings['tracking_page_layout'] = $tracking_page_layout;
			
			update_option( 'shipment_email_settings', $shipment_email_settings );

			global $wpdb;
			$woo_trackship_shipment = $this->shipment_table;
			$wpdb->query("ALTER TABLE $woo_trackship_shipment
				ADD est_delivery_date DATE");
			update_option( 'trackship_db', '1.6' );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.8', '<' ) ) {
			global $wpdb;
			$table = $this->shipment_table_meta;
			if ( !$wpdb->query( $wpdb->prepare( 'show tables like %s', $table ) ) ) {
				$charset_collate = $wpdb->get_charset_collate();			
				$sql = "CREATE TABLE $table (
					`meta_id` BIGINT(20),
					`origin_country` VARCHAR(20) ,
					`destination_country` VARCHAR(20) ,
					`delivery_number` VARCHAR(80) ,
					`delivery_provider` VARCHAR(30) ,
					`shipping_service` VARCHAR(30) ,
					`tracking_events` LONGTEXT ,
					PRIMARY KEY (`meta_id`),
					INDEX `meta_id` (`meta_id`)
				) $charset_collate;";
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );
			}
			
			$delivered_settings = get_option( 'wcast_delivered_email_settings' );
			update_option( 'wcast_delivered_status_email_settings', $delivered_settings );
			delete_option( 'wcast_delivered_email_settings' );
			update_option( 'trackship_db', '1.8' );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.9', '<' ) ) {
			$this->create_email_log_table();
			update_option( 'trackship_db', '1.9' );
		}
		if ( version_compare( get_option( 'trackship_db' ), '1.10', '<' ) ) {
			global $wpdb;
			$woo_trackship_shipment = $this->shipment_table;
			$wpdb->query("ALTER TABLE $woo_trackship_shipment
				ADD new_shipping_provider VARCHAR(50)");
			update_option( 'trackship_db', '1.10' );
		}
		if ( version_compare( get_option( 'trackship_db' ), '1.11', '<' ) ) {
			update_option( 'enable_notification_for_amazon_order', 1 );
			update_option( 'trackship_db', '1.11' );
		}
		if ( wp_next_scheduled( 'ast_late_shipments_cron_hook' ) ) {
			$Late_Shipments = new WC_TrackShip_Late_Shipments();
			$Late_Shipments->remove_cron();
			$Late_Shipments->setup_cron();
		}
		if ( version_compare( get_option( 'trackship_db' ), '1.12', '<' ) ) {
			global $wpdb;
			$columns = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '%1s' AND COLUMN_NAME = 'status_msg' ", $this->log_table ), ARRAY_A );
			if ( ! $columns ) {
				$log_table = $this->log_table;
				$wpdb->query( $wpdb->prepare( "ALTER TABLE %1s ADD status_msg varchar(500) AFTER status", $log_table ) );
				$rows = $wpdb->get_results( "SELECT * FROM {$log_table}" );
				$all_lang = array( 'Sent', 'נשלח', 'Verzonden', 'Enviado', 'مرسل', 'Изпратено', 'Sendt', 'Geschickt', 'Envoyé', 'મોકલેલો', 'भेज दिया', 'Inviato', 'Nosūtīts', 'Wysłane', 'Enviei', 'Отправил', 'Skickat', 'gönderildi', 'أرسلت', 'મોકલેલ', 'Spedito', 'Verstuurd', 'Wysłano', 'Gönderilmiş' );
				foreach ( $rows as $row ) {
					$status_msg = $row->status;
					$status = in_array( $status_msg, $all_lang ) ? true : false;
					$args = array(
						'status' => $status,
						'status_msg' => $status_msg
					);
					$where = array(
						'id' => $row->id
					);
					$wpdb->update( $log_table, $args, $where );
				}
			}
			update_option( 'trackship_db', '1.12' );
		}
	}
	
	public function update_analytics_table() {
		global $wpdb;
		$woo_trackship_shipment = $this->shipment_table;
		$start_date = gmdate( 'Y-m-d 00:00:00', strtotime( 'today - ' . 60 . ' days' ) );
		$total_order = $wpdb->get_var("
			SELECT 				
				COUNT(*)
				FROM    {$wpdb->posts} AS posts				
				LEFT JOIN {$wpdb->postmeta} AS shipment_status ON(posts.ID = shipment_status.post_id)
									
			WHERE 
				posts.post_status IN ('wc-completed','wc-delivered', 'wc-shipped', 'wc-partial-shipped')
				AND posts.post_type IN ( 'shop_order' )
				AND shipment_status.meta_key IN ( 'shipment_status')
				AND shipment_status.meta_key IS NOT NULL
				AND posts.post_date > '{$start_date}'
		");
		$total_cron = ( int ) ( $total_order/300 ) + 1;
		for ( $i = 1; $i <= $total_cron; $i++ ) {
			as_schedule_single_action( time(), 'migrate_trackship_shipment_table' );
		}
	}
	
	public function migrate_trackship_shipment_table() {
		
		global $wpdb;
		$woo_trackship_shipment = $wpdb->prefix . 'trackship_shipment';
		$args = array(
			'post_type'			=> 'shop_order',
			'posts_per_page'	=> '300',
			'post_status'		=> array( 'wc-completed','wc-delivered', 'wc-shipped', 'wc-partial-shipped' ),
			'meta_query'		=> array(
				'relation'		=> 'AND',
				'shipment_status' => array(
					'key'		=> 'shipment_status',
					'compare'	=> 'EXISTS',
				),
				'shipment_table_updated' => array(
					'key'		=> 'shipment_table_updated',
					'value'		=> 1,
					'compare' => 'NOT EXISTS'
				),
			),
			'date_query' => array(
				 array(
					 'after' => '-60 days',
					 'column' => 'post_date',
				 ),
			 ),
		);
		$query = new WP_Query( $args );
		
		while ( $query->have_posts() ) {
			$query->the_post();
			$order_id = get_the_id();
			$order = wc_get_order( $order_id );
			$tracking_items = trackship_for_woocommerce()->get_tracking_items($order_id);
			$shipment_status = $order->get_meta( 'shipment_status' );
			foreach ( (array) $tracking_items as $key => $item ) {
				if ( isset( $shipment_status[$key]['pending_status'] ) ) {
					$ship_status = $shipment_status[$key]['pending_status'];
				} else {
					$ship_status = $shipment_status[$key]['status'];
				}
				
				if ( !empty( $item['date_shipped'] ) ) {
					$shipping_date = gmdate('Y-m-d', $item['date_shipped'] );
				}
				$shipment_length = trackship_for_woocommerce()->shipments->get_shipment_length( $shipment_status[$key] );
				
				$data = array(
					'order_id'			=> $order_id,
					'order_number'		=> $order->get_order_number(),
					'tracking_number'	=> $item['tracking_number'],
					'shipping_provider'	=> $item['tracking_provider'],
					'shipment_status'	=> $ship_status,
					'shipping_date'		=> $shipping_date,
					'shipping_length'	=> $shipment_length,
					'shipping_country'	=> WC()->countries->countries[ $order->get_shipping_country() ],
				);
				$wpdb->insert( $woo_trackship_shipment, $data );
			}
			$order = wc_get_order( $order_id );
			$order->update_meta_data( 'shipment_table_updated', 1 );
			$order->save();
		}
	}
	
	/*
	* function for update order meta from shipment_status to ts_shipment_status for filter order by shipment status
	*/
	public function update_ts_shipment_status_order_mete( $order_page ) {
		
		$wc_ast_api_key = get_option( 'wc_ast_api_key' ); 
		if ( !$wc_ast_api_key ) {
			return;
		}	
		
		$args = array(			
			'limit' => 100,
			'paged' => $order_page['order_page'],
			'return' => 'ids',
			'date_created' => '>' . ( time() - 1296000 ),
		);
		
		$orders = wc_get_orders( $args );
		
		foreach ( $orders as $order_id ) {
			$order = wc_get_order( $order_id );
			$shipment_status = $order->get_meta( 'shipment_status', true );
			if ( !empty( $shipment_status ) ) {
				foreach ( $shipment_status as $key => $shipment ) {
					$ts_shipment_status[ $key ][ 'status' ] = $shipment[ 'status' ];
					$order = wc_get_order( $order_id );
					$order->update_meta_data( 'ts_shipment_status', $ts_shipment_status );
					$order->save();
				}
			}			
		}		
	}
	
	public function update_trackship_providers() {
		if ( check_ajax_referer( 'nonce_trackship_provider', 'security' ) ) {
			$this->update_shipping_providers();
			wp_send_json( array('success' => 'true') );
		}
	}
	
	/**
	 * Get providers list from trackship and update providers in database
	*/
	public function update_shipping_providers() {
		global $wpdb;
		$url = 'https://my.trackship.com/api/WCAST/v1/Provider';
		$resp = wp_remote_get( $url );
		
		if ( is_array( $resp ) && ! is_wp_error( $resp ) ) {
		
			$providers = json_decode($resp['body'], true );
			
			$wpdb->query("TRUNCATE TABLE $this->table;");
			foreach ( $providers as $provider ) {
				if ( 1 != $provider[ 'trackship_supported' ] ) {
					continue;
				}
				
				$provider_name = $provider['shipping_provider'];
				$ts_slug = $provider['shipping_provider_slug'];
				
				$data_array = array(
					'provider_name' => $provider_name,
					'ts_slug' => $ts_slug,
				);
				$wpdb->insert( $this->table, $data_array );
			}
		}
	}
	
	public function create_email_log_table() {
		global $wpdb;
		$log_table = $this->log_table;
		if ( !$wpdb->query( $wpdb->prepare( 'show tables like %s', $log_table ) ) ) {
			$charset_collate = $wpdb->get_charset_collate();			
			$sql = "CREATE TABLE $log_table (
				`id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
				`order_id` BIGINT(20) ,
				`order_number` VARCHAR(20) ,
				`user_id` BIGINT(20) ,
				`tracking_number` VARCHAR(50) ,
				`date` DATETIME NOT NULL,
				`to` VARCHAR(50) ,
				`shipment_status` VARCHAR(30) ,
				`status` LONGTEXT ,
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
}
