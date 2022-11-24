<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WC_TrackShip_Email_Manager {

	private static $instance;
	
	/**
	 * Constructor sets up actions
	 */
	public function __construct() {		

	}

	/**
	 * Code for send shipment status email
	 */
	public function shippment_email_trigger( $order_id, $order, $old_status, $new_status, $tracking_item, $shipment_status ) {
		$order = wc_get_order( $order_id );
		$this->shipment_status = $shipment_status;
		$status = str_replace('_', '', $new_status);

		$enable = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_' . $status . '_email_settings', 'wcast_enable_' . $status . '_email', '');
		$for_amazon_order = trackship_for_woocommerce()->ts_actions->is_notification_on_for_amazon( $order_id );
		$receive_email = $order->get_meta( '_receive_shipment_emails', true );

		if ( ! $enable || ! $for_amazon_order || '0' == $receive_email ) {
			return;
		}

		global $sitepress;
		if ( $sitepress ) {
			$old_lan = $sitepress->get_current_language();
			$new_lan = $order->get_meta( 'wpml_language', true );
			$sitepress->switch_lang($new_lan);
		}

		$default = trackship_admin_customizer()->wcast_shipment_settings_defaults( $status );

		$email_to = $order ? $order->get_billing_email() : '';

		$email_subject = trackship_for_woocommerce()->ts_actions->get_option_value_from_array( 'wcast_' . $status . '_email_settings', 'wcast_' . $status . '_email_subject', $default['wcast_' . $status . '_email_subject']);
		$email_heading = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_' . $status . '_email_settings', 'wcast_' . $status . '_email_heading', $default['wcast_' . $status . '_email_heading']);
							
		$email_content = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_' . $status . '_email_settings', 'wcast_' . $status . '_email_content', $default['wcast_' . $status . '_email_content']);
		$email_content = html_entity_decode( $email_content );
		
		$wcast_show_order_details = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array('wcast_' . $status . '_email_settings', 'wcast_' . $status . '_show_order_details', $default['wcast_' . $status . '_show_order_details'] );
		
		$wcast_show_product_image = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array('wcast_' . $status . '_email_settings', 'wcast_' . $status . '_show_product_image', $default['wcast_' . $status . '_show_product_image']);

		$wcast_show_shipping_address = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array( 'wcast_' . $status . '_email_settings', 'wcast_' . $status . '_show_shipping_address', $default['wcast_' . $status . '_show_shipping_address']);
		
		$sent_to_admin = false;
		$plain_text = false;

		$recipient = $this->email_to($email_to, $order, $order_id);
		
		$subject = $this->email_subject($email_subject, $order_id, $order);

		$email_content = $this->email_content($email_content, $order_id, $order);
		
		$mailer = WC()->mailer();
		
		$email_heading = $this->email_heading($email_heading, $order_id, $order);
							
		$message = $this->append_analytics_link($email_content, $status);								
				
		$local_template	= get_stylesheet_directory() . '/woocommerce/emails/tracking-info.php';			
		if ( file_exists( $local_template ) && is_writable( $local_template ) ) {				
			$message .= wc_get_template_html( 'emails/tracking-info.php', array( 
				'tracking_items' => array($tracking_item),
				'shipment_status' => array($shipment_status),
				'order_id' => $order_id,
				'show_shipment_status' => false,
				'new_status' => $new_status,
			), 'woocommerce-advanced-shipment-tracking/', get_stylesheet_directory() . '/woocommerce/' );
		} else {
			$message .= wc_get_template_html( 'emails/tracking-info.php', array( 
				'tracking_items' => array($tracking_item),
				'shipment_status' => array($shipment_status),
				'order_id' => $order_id,
				'show_shipment_status' => false,
				'new_status' => $new_status,	
			), 'woocommerce-advanced-shipment-tracking/', trackship_for_woocommerce()->get_plugin_path() . '/templates/' );
		}
		
		if ( $wcast_show_order_details ) {
			$tpi_order = false;
			$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
			if ( function_exists( 'ast_pro' ) ) {
				$tpi_order = ast_pro()->ast_tpi->check_if_tpi_order( $tracking_items, $order );
			}
			
			if ( $tpi_order ) {
				
				$message.= wc_get_template_html(
					'emails/tswc-tpi-email-order-details.php',
					array(
						'order'         => $order,
						'sent_to_admin' => $sent_to_admin,
						'plain_text'    => $plain_text,
						'tracking_items'=> array($tracking_item),
						'email'         => '',
						'wcast_show_product_image' => $wcast_show_product_image,
					),
					'woocommerce-advanced-shipment-tracking/', 
					trackship_for_woocommerce()->get_plugin_path() . '/templates/'
				);
			} else {
				$message.= wc_get_template_html(
					'emails/tswc-email-order-details.php',
					array(
						'order'         => $order,
						'sent_to_admin' => $sent_to_admin,
						'plain_text'    => $plain_text,
						'email'         => '',
						'wcast_show_product_image' => $wcast_show_product_image,
					),
					'woocommerce-advanced-shipment-tracking/', 
					trackship_for_woocommerce()->get_plugin_path() . '/templates/'
				);
			}

		}
		
		if ( $wcast_show_shipping_address ) {
			$message.= wc_get_template_html(
				'emails/shipping-email-addresses.php', array(
					'order'         => $order,
					'sent_to_admin' => $sent_to_admin,
				),
				'woocommerce-advanced-shipment-tracking/', 
				trackship_for_woocommerce()->get_plugin_path() . '/templates/'
			);	
		}
						
		// create a new email
		$email_class = new WC_Email();
	
		if ( get_option( 'enable_email_widget' ) ) {
			$track_link = isset( $tracking_item[ 'ast_tracking_link' ] ) && get_option( 'wc_ast_use_tracking_page', 1 ) ? $tracking_item[ 'ast_tracking_link' ] : $order->get_view_order_url();
			$track_link = add_query_arg( array( 'unsubscribe' => 'true' ), $track_link );
			$message .= '<div style="text-align:center;"><a href="' . $track_link . '">' . esc_html__( 'Unsubscribe Shipment emails', 'trackship-for-woocommerce' ) . '</a></div>';
		}

		// wrap the content with the email template and then add styles
		$message = apply_filters( 'woocommerce_mail_content', $email_class->style_inline( $mailer->wrap_message( $email_heading, $message ) ) );
		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		
		$email_send = wp_mail( $recipient, $subject, $message, $email_class->get_headers() );
		$arg = array(
			'order_id'			=> $order_id,
			'order_number'		=> wc_get_order( $order_id )->get_order_number(),
			'user_id'			=> wc_get_order( $order_id )->get_user_id(),
			'tracking_number'	=> $tracking_item['tracking_number'],
			'date'				=> current_time( 'Y-m-d H:i:s' ),
			'to'				=> $recipient,
			'shipment_status'	=> $new_status,
			'status'			=> $email_send,
			'status_msg'		=> $email_send ? 'Sent' : 'Not Sent',
			'type'				=> 'Email',
		);
		trackship_for_woocommerce()->ts_actions->update_notification_table( $arg );

		if ( $sitepress ) {
			$sitepress->switch_lang($old_lan);
		}
	}
		
	/**
	 * Code for send delivered shipment status email
	 */
	public function delivered_email_trigger( $order_id, $order, $old_status, $new_status, $tracking_item, $shipment_status ) {
		$order = wc_get_order( $order_id );
		$toggle = get_option( 'all-shipment-status-delivered' );
		$all_delivered = trackship_for_woocommerce()->ts_actions->is_all_shipments_delivered( $order_id );
		
		if ( $toggle && !$all_delivered ) {
			return;
		}
		
		$enable = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_delivered_status_email_settings', 'wcast_enable_delivered_status_email', '');
		$for_amazon_order = trackship_for_woocommerce()->ts_actions->is_notification_on_for_amazon( $order_id );
		$receive_email = $order->get_meta( '_receive_shipment_emails', true );

		if ( ! $enable || ! $for_amazon_order || '0' == $receive_email ) {
			return;
		}

		global $sitepress;
		if ( $sitepress ) {
			$old_lan = $sitepress->get_current_language();
			$new_lan = $order->get_meta( 'wpml_language', true );
			$sitepress->switch_lang($new_lan);
		}
		
		$default = trackship_admin_customizer()->wcast_shipment_settings_defaults( 'delivered_status' );
		$email_to = $order ? $order->get_billing_email() : '';
		
		$email_subject = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_delivered_status_email_settings', 'wcast_delivered_status_email_subject', $default['wcast_delivered_status_email_subject']);													
		
		$email_heading = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_delivered_status_email_settings', 'wcast_delivered_status_email_heading', $default['wcast_delivered_status_email_heading']);				
		
		$email_content = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_delivered_status_email_settings', 'wcast_delivered_status_email_content', $default['wcast_delivered_status_email_content']);
		$email_content = html_entity_decode( $email_content );
		
		$wcast_show_tracking_details = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array('wcast_delivered_status_email_settings', 'wcast_delivered_status_show_tracking_details', $default['wcast_delivered_status_show_tracking_details']);
		
		$wcast_show_order_details = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array('wcast_delivered_status_email_settings', 'wcast_delivered_status_show_order_details', $default['wcast_delivered_status_show_order_details']);
		
		$wcast_show_product_image = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array('wcast_delivered_status_email_settings', 'wcast_delivered_status_show_product_image', $default['wcast_delivered_status_show_product_image']);
		
		$wcast_show_shipping_address = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array('wcast_delivered_status_email_settings', 'wcast_delivered_status_show_shipping_address', $default['wcast_delivered_status_show_shipping_address']);
		
		$sent_to_admin = false;
		$plain_text = false;				
			
		$recipient = $this->email_to($email_to, $order, $order_id);
		$subject = $this->email_subject($email_subject, $order_id, $order);
		
		$email_content = $this->email_content($email_content, $order_id, $order);
		
		$mailer = WC()->mailer();
		
		$email_heading = $this->email_heading($email_heading, $order_id, $order);
		
		$status = 'delivered_status';	
		$message = $this->append_analytics_link($email_content, $status);
		
		$tracking_items = array($tracking_item);
		$shipment_statuses = array($shipment_status);
		
		if ( $toggle && $all_delivered ) {
			$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id, false );
			$shipment_statuses = $order->get_meta( 'shipment_status', true );
		}
		
		if ( $wcast_show_tracking_details ) {
			$local_template	= get_stylesheet_directory() . '/woocommerce/emails/tracking-info.php';			
			if ( file_exists( $local_template ) && is_writable( $local_template ) ) {				
				$message .= wc_get_template_html( 'emails/tracking-info.php', array( 
					'tracking_items' => $tracking_items,
					'shipment_status' => $shipment_statuses,
					'order_id' => $order_id,
					'show_shipment_status' => false,
					'new_status' => $new_status,
				), 'woocommerce-advanced-shipment-tracking/', get_stylesheet_directory() . '/woocommerce/' );
			} else {
				$message .= wc_get_template_html( 'emails/tracking-info.php', array( 
					'tracking_items' => $tracking_items,
					'shipment_status' => $shipment_statuses,
					'order_id' => $order_id,
					'show_shipment_status' => false,
					'new_status' => $new_status,
				), 'woocommerce-advanced-shipment-tracking/', trackship_for_woocommerce()->get_plugin_path() . '/templates/' );
			}
		}					
		
		if ( $wcast_show_order_details ) {
			
			$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
			$tpi_order = function_exists( 'ast_pro' ) ? ast_pro()->ast_tpi->check_if_tpi_order( $tracking_items, $order ) : false;
			
			$tracking_items = array( $tracking_item );
			if ( $tpi_order ) {
				if ( $toggle && $all_delivered ) {
					$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id, false );
				}
				$message.= wc_get_template_html(
					'emails/tswc-tpi-email-order-details.php',
					array(
						'order'         => $order,
						'sent_to_admin' => $sent_to_admin,
						'plain_text'    => $plain_text,
						'tracking_items'=> $tracking_items,
						'email'         => '',
					),
					'woocommerce-advanced-shipment-tracking/', 
					trackship_for_woocommerce()->get_plugin_path() . '/templates/'
				);
				
			} else {
				$message.= wc_get_template_html(
					'emails/tswc-email-order-details.php',
					array(
						'order'         => $order,
						'sent_to_admin' => $sent_to_admin,
						'plain_text'    => $plain_text,
						'email'         => '',
						'wcast_show_product_image' => $wcast_show_product_image,
					),
					'woocommerce-advanced-shipment-tracking/', 
					trackship_for_woocommerce()->get_plugin_path() . '/templates/'
				);
			}	
		}
		
		if ( $wcast_show_shipping_address ) {
			$message.= wc_get_template_html(
				'emails/shipping-email-addresses.php', array(
					'order'         => $order,
					'sent_to_admin' => $sent_to_admin,
				),
				'woocommerce-advanced-shipment-tracking/', 
				trackship_for_woocommerce()->get_plugin_path() . '/templates/'
			);	
		}
						
		// create a new email
		$email_class = new WC_Email();

		if ( get_option( 'enable_email_widget' ) ) {
			$track_link = isset( $tracking_item[ 'ast_tracking_link' ] ) && get_option( 'wc_ast_use_tracking_page', 1 ) ? $tracking_item[ 'ast_tracking_link' ] : $order->get_view_order_url();
			$track_link = add_query_arg( array( 'unsubscribe' => 'true' ), $track_link );
			$message .= '<div style="text-align:center;"><a href="' . $track_link . '">' . esc_html__( 'Unsubscribe Shipment emails', 'trackship-for-woocommerce' ) . '</a></div>';
		}

		// wrap the content with the email template and then add styles
		$message = apply_filters( 'woocommerce_mail_content', $email_class->style_inline( $mailer->wrap_message( $email_heading, $message ) ) );
		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		
		$email_send = wp_mail( $recipient, $subject, $message, $email_class->get_headers() );
		$arg = array(
			'order_id'			=> $order_id,
			'order_number'		=> wc_get_order( $order_id )->get_order_number(),
			'user_id'			=> wc_get_order( $order_id )->get_user_id(),
			'tracking_number'	=> $tracking_item['tracking_number'],
			'date'				=> current_time( 'Y-m-d H:i:s' ),
			'to'				=> $recipient,
			'shipment_status'	=> $new_status,
			'status'			=> $email_send,
			'status_msg'		=> $email_send ? 'Sent' : 'Not Sent',
			'type'				=> 'Email',
		);
		trackship_for_woocommerce()->ts_actions->update_notification_table( $arg );

		if ( $sitepress ) {
			$sitepress->switch_lang($old_lan);
		}
	}

	/**
	 * Code for format email subject
	*/
	public function email_subject( $string, $order_id, $order ) {
		$customer_email = $order->get_billing_email();
		$first_name = $order->get_billing_first_name();
		$last_name = $order->get_billing_last_name();
		$user = $order->get_user();
		if ( $user ) {
			$username = $user->user_login;
		}
		$string =  str_replace( '{order_number}', $order->get_order_number(), $string );
		$string =  str_replace( '{customer_email}', $customer_email, $string );
		$string =  str_replace( '{customer_first_name}', $first_name, $string );
		$string =  str_replace( '{customer_last_name}', $last_name, $string );
		if ( isset( $username ) ) {
			$string = str_replace( '{customer_username}', $username, $string );
		} else {
			$string = str_replace( '{customer_username}', '', $string );
		}
		$string =  str_replace( '{site_title}', $this->get_blogname(), $string );
		return $string;
	} 
	
	/**
	 * Code for format email heading
	 */	
	public function email_heading( $string, $order_id, $order ) {
		$customer_email = $order->get_billing_email();
		$first_name = $order->get_billing_first_name();
		$last_name = $order->get_billing_last_name();
		$user = $order->get_user();
		if ( $user ) {
			$username = $user->user_login;
		}
		$string =  str_replace( '{order_number}', $order->get_order_number(), $string );
		$string =  str_replace( '{customer_email}', $customer_email, $string );
		$string =  str_replace( '{customer_first_name}', $first_name, $string );
		$string =  str_replace( '{customer_last_name}', $last_name, $string );
		if ( isset( $username ) ) {
			$string = str_replace( '{customer_username}', $username, $string );
		} else {
			$string = str_replace( '{customer_username}', '', $string );
		}
		$string =  str_replace( '{site_title}', $this->get_blogname(), $string );
		return $string;
	} 
	
	/**
	 * Code for format recipients 
	 */	
	public function email_to( $string, $order, $order_id ) {
		$customer_email = $order ? $order->get_billing_email() : '';
		$admin_email = get_option('admin_email');
		$string =  str_replace( '{admin_email}', $admin_email, $string );
		$string =  str_replace( '{customer_email}', $customer_email, $string );
		return $string;
	} 
	
	/**
	 * Code for format email content 
	 */
	public function email_content( $email_content, $order_id, $order ) {						
		$customer_email = $order->get_billing_email();
		$first_name = $order->get_billing_first_name();
		$last_name = $order->get_billing_last_name();
		$company_name = $order->get_billing_company();
		$user = $order->get_user();
		if ( $user ) {
			$username = $user->user_login;
		}
		
		$wc_ast_api_key = get_option('wc_ast_api_key');
		$api_enabled = get_option( 'wc_ast_api_enabled', 0);
		if ( $wc_ast_api_key && $api_enabled ) {
			$est_delivery_date = $this->get_est_delivery_date($order_id, $order);
		}
		
		$email_content = str_replace( '{customer_email}', $customer_email, $email_content );
		$email_content = str_replace( '{site_title}', $this->get_blogname(), $email_content );
		$email_content = str_replace( '{customer_first_name}', $first_name, $email_content );
		$email_content = str_replace( '{customer_last_name}', $last_name, $email_content );
		
		if ( isset( $company_name ) ) {
			$email_content = str_replace( '{customer_company_name}', $company_name, $email_content );	
		} else {
			$email_content = str_replace( '{customer_company_name}', '', $email_content );	
		}	 
		
		if ( isset( $username ) ) {
			$email_content = str_replace( '{customer_username}', $username, $email_content );
		} else {
			$email_content = str_replace( '{customer_username}', '', $email_content );
		}
		$email_content = str_replace( '{order_number}', $order->get_order_number(), $email_content );
		if ( $wc_ast_api_key && $api_enabled ) {		
			$email_content = str_replace( '{est_delivery_date}', $est_delivery_date, $email_content );		
		}
		
		return '<div class="shipment_email_content">' . $email_content . '</div>';
	}
	
	/**
	 * Code for append analytics link
	 */
	public function append_analytics_link( $message, $status ) {
		if ( 'delivered_status' == $status ) {
			$analytics_link = trackship_for_woocommerce()->ts_actions->get_option_value_from_array( 'wcast_delivered_status_email_settings', 'wcast_delivered_status_analytics_link', '' );	
		} else {
			$analytics_link = trackship_for_woocommerce()->ts_actions->get_option_value_from_array( 'wcast_' . $status . '_email_settings', 'wcast_' . $status . '_analytics_link', '' );
		}		
	
		if ( $analytics_link ) {	
			$regex = '#(<a href=")([^"]*)("[^>]*?>)#i';
			$message = preg_replace_callback( $regex, function ( $match ) use ( $status ) {
				$url = $match[2];
				if ( strpos($url, '?') === false ) {
					$url .= '?';
				}
				$url .= $analytics_link;
				return $match[1] . $url . $match[3];
			}, $message);	
		}
		return $message;	
	}	

	/**
	 * Code for get estimate delivery date
	 */
	public function get_est_delivery_date( $order_id, $order ) {
		
		$shipment_status = isset( $this->shipment_status ) && $this->shipment_status ? $this->shipment_status : array();
		$est_delivery_date = isset( $shipment_status['est_delivery_date'] ) && $shipment_status['est_delivery_date'] ? $shipment_status['est_delivery_date'] : '';
		return $est_delivery_date ? date_i18n( 'l, M d', strtotime( $est_delivery_date ) ) : 'Not Available';
	}
	
	/**
	 * Get blog name formatted for emails.
	 *
	 * @return string
	 */
	public function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}
	
	/**
	 * Get the from name for outgoing emails.
	 *
	 * @return string
	 */
	public function get_from_name( $from_name = '' ) {
		$from_name = apply_filters( 'woocommerce_email_from_name', get_option( 'woocommerce_email_from_name' ), $this );
		return wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES );
	}

	/**
	 * Get the from address for outgoing emails.
	 *
	 * @return string
	 */
	public function get_from_address( $from_email = '' ) {
		$from_address = apply_filters( 'woocommerce_email_from_address', get_option( 'woocommerce_email_from_address' ), $this );
		return sanitize_email( $from_address );
	}		
	
}

function WC_TrackShip_Email_Manager() {
	static $instance;

	if ( ! isset( $instance ) ) {
		$instance = new WC_TrackShip_Email_Manager();
	}

	return $instance;
}
return new WC_TrackShip_Email_Manager();