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
		if ( is_multisite() ) {
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
			if ( is_plugin_active_for_network( 'trackship-for-woocommerce/trackship-for-woocommerce.php' ) ) {
				$main_blog_prefix = $wpdb->get_blog_prefix( BLOG_ID_CURRENT_SITE );
				$this->table = $main_blog_prefix . 'trackship_shipping_provider';	
			} else {
				$this->table = $wpdb->prefix . 'trackship_shipping_provider';
			}
		} else {
			$this->table = $wpdb->prefix . 'trackship_shipping_provider';	
		}
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
		add_action( 'init', array( $this, 'update_database_check' ) );	
	}
	
	/*
	* database update
	*/
	public function update_database_check() {
		if ( is_admin() ) {
			
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
						provider_url varchar(500) DEFAULT '' NULL,
						shipping_country varchar(45) DEFAULT '' NULL,
						PRIMARY KEY  (id)
					) $charset_collate;";			
					require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
					dbDelta( $sql );
					$this->update_shipping_providers();												
				}
				
				$this->update_shipping_providers();

				update_option( 'trackship_db', '1.2' );
			}
		}
	}
	
	/**
	 * Get providers list from trackship and update providers in database
	*/
	public function update_shipping_providers() {
		global $wpdb;		
		$url = 'https://trackship.info/wp-json/WCAST/v1/Provider';		
		$resp = wp_remote_get( $url );				
				
		if ( is_array( $resp ) && ! is_wp_error( $resp ) ) {
		
			$providers = json_decode($resp['body'], true);
			
			$providers_name = array();
			
			$default_shippment_providers = $wpdb->get_results( 'SELECT * FROM $this->table' );			
			foreach ( $default_shippment_providers as $key => $val ) {
				$shippment_providers[ $val->provider_name ] = $val;						
			}

			foreach ( $providers as $key => $val ) {
				$providers_name[ $val[ 'provider_name' ] ] = $val;						
			}					
			
			foreach ( $providers as $provider ) {
				
				if ( 1 != $provider[ 'trackship_supported' ] ) {
					continue;
				}
				
				$provider_name = $provider['shipping_provider'];
				$provider_url = $provider['provider_url'];
				$shipping_country = $provider['shipping_country'];
				$ts_slug = $provider['shipping_provider_slug'];				
				
				if ( isset( $shippment_providers[ $provider_name ] ) ) {
					$db_provider_url = $shippment_providers[$provider_name]->provider_url;
					$db_shipping_country = $shippment_providers[$provider_name]->shipping_country;
					$db_ts_slug = $shippment_providers[$provider_name]->ts_slug;
					
					if ( $db_provider_url != $provider_url || $db_shipping_country != $shipping_country || $db_ts_slug != $ts_slug ) {
						$data_array = array(
							'ts_slug' => $ts_slug,
							'provider_url' => $provider_url,
							'shipping_country' => $shipping_country,						
						);
						$where_array = array(
							'provider_name' => $provider_name,			
						);					
						$wpdb->update( $this->table, $data_array, $where_array );					
					}
				} else {																							
					$data_array = array(
						'shipping_country' => sanitize_text_field($shipping_country),
						'provider_name' => sanitize_text_field($provider_name),
						'ts_slug' => $ts_slug,
						'provider_url' => sanitize_text_field($provider_url),															
					);
					$result = $wpdb->insert( $this->table, $data_array );				
				}		
			}		
			foreach ( $default_shippment_providers as $db_provider ) {
	
				if ( !isset( $providers_name[ $db_provider->provider_name ] ) ) {
					$where = array(
						'provider_name' => $db_provider->provider_name,						
					);
					$wpdb->delete( $this->table, $where );					
				}
			}
		}	
	}
}
