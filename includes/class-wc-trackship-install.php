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
		// add_action( 'admin_init', array( $this, 'update_database_check' ) );
		add_action( 'wp_ajax_update_trackship_providers', array( $this, 'update_trackship_providers' ) );
	}
	
	/*
	* database update
	*/
	public function update_database_check() {
			
		if ( version_compare( get_option( 'trackship_db' ), '1.0', '<' ) ) {
			update_option( 'trackship_trigger_order_statuses', array( 'completed', 'shipped' ) );
			update_option( 'trackship_db', '1.0' );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.5', '<' ) ) {
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
			update_option( 'trackship_db', '1.6' );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.8', '<' ) ) {
			$this->create_shipment_meta_table();
			$delivered_settings = get_option( 'wcast_delivered_email_settings' );
			update_option( 'wcast_delivered_status_email_settings', $delivered_settings );
			delete_option( 'wcast_delivered_email_settings' );
			update_option( 'trackship_db', '1.8' );
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
		if ( version_compare( get_option( 'trackship_db' ), '1.13', '<' ) ) {
			// migration to change api key name 
			$trackship_apikey = get_option( 'wc_ast_api_key' );			
			update_option( 'trackship_apikey', $trackship_apikey );
			delete_option( 'wc_ast_api_enabled' );
			
			update_option( 'trackship_db', '1.13' );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.14', '<' ) ) {
			global $wpdb;
			$shipment_table = $this->shipment_table;
			$sql = "ALTER TABLE {$shipment_table} CHANGE shipping_date shipping_date DATE NULL DEFAULT CURRENT_TIMESTAMP";
			$wpdb->query($sql);
			$this->check_column_exists();
			$wpdb->query( "ALTER TABLE $shipment_table ADD INDEX last_event (last_event);" );
			update_option( 'trackship_db', '1.14' );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.17', '<' ) ) {
			$this->create_shipping_provider_table();
			$this->update_shipping_providers();
			$this->create_shipment_table();
			$this->create_shipment_meta_table();
			$this->create_email_log_table();
			$this->check_column_exists();
			update_option( 'trackship_db', '1.17' );
		}

		if ( version_compare( get_option( 'trackship_db' ), '1.18', '<' ) ) {
			global $wpdb;
			$shipment_meta_table = $this->shipment_table_meta;
			$wpdb->query("ALTER TABLE $shipment_meta_table MODIFY COLUMN shipping_service varchar(60);");
			update_option( 'trackship_db', '1.18' );
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
	
	/**
	 * Create TrackShip notifications logs table
	*/
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
				`pending_status` VARCHAR(30) ,
				`shipping_date` date ,
				`shipping_country` TEXT ,
				`shipping_length` VARCHAR(10) ,
				`updated_date` DATE ,
				`late_shipment_email` TINYINT DEFAULT 0,
				`est_delivery_date` DATE,
				`new_shipping_provider` VARCHAR(50),
				`last_event` LONGTEXT ,
				`last_event_time` DATETIME ,
				PRIMARY KEY (`id`),
				INDEX `shipping_date` (`shipping_date`),
				INDEX `status` (`shipment_status`),
				INDEX `tracking_number` (`tracking_number`),
				INDEX `shipping_length` (`shipping_length`),
				INDEX `order_id` (`order_id`),
				INDEX `order_id_tracking_number` (`order_id`,`tracking_number`),
				INDEX `updated_date` (`updated_date`),
				INDEX `late_shipment_email` (`late_shipment_email`),
				INDEX `est_delivery_date` (`est_delivery_date`),
				INDEX `new_shipping_provider` (`new_shipping_provider`)
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
		$table = $this->shipment_table_meta;
		if ( !$wpdb->query( $wpdb->prepare( 'show tables like %s', $table ) ) ) {
			$charset_collate = $wpdb->get_charset_collate();			
			$sql = "CREATE TABLE $table (
				`meta_id` BIGINT(20),
				`origin_country` VARCHAR(20) ,
				`destination_country` VARCHAR(20) ,
				`delivery_number` VARCHAR(80) ,
				`delivery_provider` VARCHAR(30) ,
				`shipping_service` VARCHAR(60) ,
				`tracking_events` LONGTEXT ,
				`destination_events` LONGTEXT ,
				PRIMARY KEY (`meta_id`),
				INDEX `meta_id` (`meta_id`)
			) $charset_collate;";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}

	/**
	 * check column exists in TrackShip table
	*/
	public function check_column_exists() {
		global $wpdb;

		$shipment_table = array(
			'id'					=> ' BIGINT(20) NOT NULL AUTO_INCREMENT',
			'order_id'				=> ' BIGINT(20)',
			'order_number'			=> ' VARCHAR(20)',
			'tracking_number'		=> ' VARCHAR(80)',
			'shipping_provider'		=> ' VARCHAR(50)',
			'shipment_status'		=> ' VARCHAR(30)',
			'pending_status'		=> ' VARCHAR(30)',
			'shipping_date'			=> ' DATE NOT NULL CURRENT_TIMESTAMP',
			'shipping_country'		=> ' TEXT',
			'shipping_length'		=> ' VARCHAR(10)',
			'updated_date'			=> ' DATE',
			'late_shipment_email'	=> ' TINYINT DEFAULT 0',
			'est_delivery_date'		=> ' DATE',
			'new_shipping_provider'	=> ' VARCHAR(50)',
			'last_event'			=> ' LONGTEXT',
			'last_event_time'		=> ' DATETIME',
		);
		foreach( $shipment_table as $column_name => $type ) {
			$columns = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '%1s' AND COLUMN_NAME = '%2s' ", $this->shipment_table, $column_name ), ARRAY_A );
			if ( ! $columns ) {
				$wpdb->query( $wpdb->prepare( "ALTER TABLE %1s ADD %2s %3s", $this->shipment_table, $column_name, $type ) );
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
		);
		foreach( $shipment_table_meta as $column_name => $type ) {
			$columns = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '%1s' AND COLUMN_NAME = '%2s' ", $this->shipment_table_meta, $column_name ), ARRAY_A );
			if ( ! $columns ) {
				$wpdb->query( $wpdb->prepare( "ALTER TABLE %1s ADD %2s %3s", $this->shipment_table_meta, $column_name, $type ) );
			}
		}

		$log_table = array( 
			'id' => ' BIGINT(20) NOT NULL AUTO_INCREMENT',
			'order_id' => ' BIGINT(20)',
			'order_number' => ' VARCHAR(20)',
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
		foreach( $log_table as $column_name => $type ) {
			$columns = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '%1s' AND COLUMN_NAME = '%2s' ", $this->log_table, $column_name ), ARRAY_A );
			if ( ! $columns ) {
				$wpdb->query( $wpdb->prepare( "ALTER TABLE %1s ADD %2s %3s", $this->log_table, $column_name, $type ) );
			}
		}
	}
}
