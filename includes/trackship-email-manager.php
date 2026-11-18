<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WC_TrackShip_Email_Manager {

	private static $instance;
	public $order;
	public $shipment_row;
	public $tracking_item;
	public $tracking_number;
	
	/**
	 * Constructor sets up actions
	 */
	public function __construct() {
		add_action( 'ts_status_change_trigger', array( $this, 'ts_status_change_trigger' ), 10, 4 );
		add_action( 'trigger_pickup_reminder_email', array( $this, 'trigger_pickup_reminder_email' ), 10, 4 );
		add_filter( 'trackship_mail_content', array( $this, 'add_schema_markup' ), 20, 5 );
	}

	public function ts_status_change_trigger ( $order_id, $old_status, $new_status, $tracking_number ) {
		$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );

		foreach ( ( array ) $tracking_items as $key => $tracking_item ) {
			if ( trim( $tracking_item['tracking_number'] ) != trim($tracking_number) ) {
				continue;
			}
			$row = trackship_for_woocommerce()->actions->get_shipment_row( $order_id , $tracking_item['tracking_number'] );
			$this->shippment_email_trigger( $order_id, $old_status, $new_status, $tracking_item, $row );
			break;
		}
	}
	
	/**
	 * Code for send shipment status email
	 */
	public function shippment_email_trigger( $order_id, $old_status, $new_status, $tracking_item, $shipment_row ) {
		$order = wc_get_order( $order_id );
		if ( !$order ) {
			return;
		}
		$this->order = $order;
		$this->shipment_row = $shipment_row;
		$this->tracking_item = $tracking_item;
		$this->tracking_number = $tracking_item['tracking_number'];
		$status = str_replace('_', '', $new_status);
		$status = 'delivered' == $status ? 'delivered_status' : $status;

		$enable = get_trackship_email_settings( $new_status, 'enable' );
		$for_amazon_order = trackship_for_woocommerce()->ts_actions->is_notification_on_for_amazon( $order_id );
		$receive_email = $order->get_meta( '_receive_shipment_emails', true );

		$arg = array(
			'order_id'			=> $order_id,
			'order_number'		=> wc_get_order( $order_id )->get_order_number(),
			'tracking_number'	=> $tracking_item['tracking_number'],
			'date'				=> current_time( 'Y-m-d H:i:s' ),
			'shipment_status'	=> $new_status,
			'status_msg'		=> 'Settings disabled',
		);

		$email_to = [];
		$email_address = apply_filters( 'trackship_shipment_email_recipient', $order->get_billing_email(), $order );

		if ( $enable && $for_amazon_order && '0' != $receive_email && $email_address ) {
			$email_to[] = $email_address;
		}
		$email_to = apply_filters( 'add_multiple_emails_to_shipment_email', $email_to, $new_status );

		$logger = wc_get_logger();
		if ( !$email_to ) {
			$arg['status_msg'] = 'Empty recipient';
			$logger->info( print_r($arg, true), array( 'source' => 'trackship_email_log' ) );
			return;
		}
		
		if ( 'delivered' == $new_status ) {
			$toggle = get_option( 'all-shipment-status-delivered' );
			$all_delivered = trackship_for_woocommerce()->ts_actions->is_all_shipments_delivered( $order_id );
			
			if ( $toggle && !$all_delivered ) {
				$logger->info( print_r($arg, true), array( 'source' => 'trackship_email_log' ) );
				return;
			}
		}

		global $sitepress;
		if ( $sitepress ) {
			$old_lan = $sitepress->get_current_language();
			$new_lan = $order->get_meta( 'wpml_language', true );
			$sitepress->switch_lang($new_lan);
		}

		$email_subject = get_trackship_email_settings( $new_status, 'subject' );
		$email_heading = get_trackship_email_settings( $new_status, 'heading' );
		$email_content = get_trackship_email_settings( $new_status, 'content' );
		$email_content = html_entity_decode( $email_content );
		
		$wcast_show_order_details = get_trackship_email_settings( $new_status, 'show_order_details' );
		$wcast_show_product_image = get_trackship_email_settings( $new_status, 'show_product_image' );
		$wcast_show_shipping_address = get_trackship_email_settings( $new_status, 'show_shipping_address' );
		
		$sent_to_admin = false;
		$plain_text = false;

		$recipients = $this->email_to($email_to, $order, $order_id);
		
		$subject = $this->email_subject($email_subject, $order_id, $order);

		$message = $this->email_content($email_content, $order_id, $order);
		
		$email_heading = $this->email_heading($email_heading, $order_id, $order);

		$message .= $this->get_tracking_info_template( $tracking_item, $shipment_row, $order_id, $new_status );

		$tpi_order = false;
		$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
		$tpi_order = trackship_for_woocommerce()->front->check_if_tpi_order( $tracking_items, $order );
		
		if ( $tpi_order ) {
			$message .= $this->get_order_details_template( 'emails/tswc-tpi-email-order-details.php', $order, $sent_to_admin, $plain_text, $tracking_item, $wcast_show_product_image, $wcast_show_order_details, $new_status );
		} else {
			$message .= $this->get_order_details_template( 'emails/tswc-email-order-details.php', $order, $sent_to_admin, $plain_text, $tracking_item, $wcast_show_product_image, $wcast_show_order_details, $new_status );
		}

		$message.= wc_get_template_html(
			'emails/shipping-email-addresses.php', array(
				'order' => $order,
				'sent_to_admin' => $sent_to_admin,
				'ts4wc_preview' => false,
				'wcast_show_shipping_address' => $wcast_show_shipping_address,
			),
			'woocommerce-advanced-shipment-tracking/', 
			trackship_for_woocommerce()->get_plugin_path() . '/templates/'
		);

		// create a new email
		$mailer = WC()->mailer();
		$email_class = new WC_Email();
		$email_class->id = 'shipment_email';

		add_filter( 'wp_kses_allowed_html', array( trackship_admin_customizer(), 'my_allowed_tags' ) );
		add_filter( 'safe_style_css', array( trackship_admin_customizer(), 'safe_style_css_callback' ), 10, 1 );

		add_filter( 'woocommerce_email_footer_text', array( $this, 'email_footer_text' ) );

		if ( trackship_for_woocommerce()->is_active_villa_TEC() && get_trackship_settings( 'ts_use_villa_email_template' ) ) {
			// Exception: Villa Theme Email Customizer (Premium) does not follow WooCommerce standards.
			// It is incompatible with WC()->mailer()->wrap_message(), so we skip wrapping to preserve layout.
			// This ensures email content renders correctly with their custom structure.
			$message = $message; // Intentionally left unchanged for compatibility.
		} else {
			// Compatible with standard-compliant email customizer plugins:
			// Kadence, YayMail, Zorem, WP HTML Mail, etc.
			// Wrap the content using WooCommerce's default template, then styles are applied.
			$message = $mailer->wrap_message( $email_heading, $message );
		}
		$message = apply_filters( 'trackship_mail_content', $message, $email_heading, $order_id, $email_class->id, $new_status );

		foreach ( $recipients as $recipient ) {
			$email_send = $email_class->send( $recipient, $subject, $message, $email_class->get_headers(), [] );
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
		}

		if ( $sitepress ) {
			$sitepress->switch_lang($old_lan);
		}
	}

	public function trigger_pickup_reminder_email( $order_id, $old_status, $new_status, $tracking_number ) {
		$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );

		foreach ( ( array ) $tracking_items as $key => $tracking_item ) {
			if ( trim( $tracking_item['tracking_number'] ) != trim($tracking_number) ) {
				continue;
			}
			$shipment_row = trackship_for_woocommerce()->actions->get_shipment_row( $order_id , $tracking_item['tracking_number'] );
			$order = wc_get_order( $order_id );
			$this->order = $order;
			$this->shipment_row = $shipment_row;
			$this->tracking_item = $tracking_item;
			$this->tracking_number = $tracking_item['tracking_number'];

			$enable = get_trackship_email_settings( 'pickup_reminder', 'enable' );
	
			$arg = array(
				'order_id'			=> $order_id,
				'order_number'		=> wc_get_order( $order_id )->get_order_number(),
				'tracking_number'	=> $tracking_item['tracking_number'],
				'date'				=> current_time( 'Y-m-d H:i:s' ),
				'shipment_status'	=> 'pickup_reminder',
				'status_msg'		=> 'available_for_pickup' != $shipment_row->shipment_status ? 'Shipment is not available for pickup.' : 'Settings disabled',
			);
	
			$logger = wc_get_logger();
			if ( ! $enable || 'available_for_pickup' != $shipment_row->shipment_status ) {
				$logger->info( print_r($arg, true), array( 'source' => 'trackship_email_log' ) );
				return;
			}
	
			global $sitepress;
			if ( $sitepress ) {
				$old_lan = $sitepress->get_current_language();
				$new_lan = $order->get_meta( 'wpml_language', true );
				$sitepress->switch_lang($new_lan);
			}

			$email_to = [];
			$email_to[] = $order ? $order->get_billing_email() : '';
			$email_to = apply_filters( 'add_multiple_emails_to_shipment_email', $email_to, $new_status );
	
			$email_subject = get_trackship_email_settings( 'pickup_reminder', 'subject' );
			$email_heading = get_trackship_email_settings( 'pickup_reminder', 'heading' );
			$email_content = get_trackship_email_settings( 'pickup_reminder', 'content' );
			$email_content = html_entity_decode( $email_content );
			
			$wcast_show_order_details = get_trackship_email_settings( 'pickup_reminder', 'show_order_details' );
			$wcast_show_product_image = get_trackship_email_settings( 'pickup_reminder', 'show_product_image' );
			
			$sent_to_admin = false;
			$plain_text = false;
	
			$recipients = $this->email_to($email_to, $order, $order_id);
			
			$subject = $this->email_subject($email_subject, $order_id, $order);
	
			$message = $this->email_content($email_content, $order_id, $order);
			
			$email_heading = $this->email_heading($email_heading, $order_id, $order);

			$message .= $this->get_tracking_info_template( $tracking_item, $shipment_row, $order_id, $new_status );

			$tpi_order = false;
			$tpi_order = trackship_for_woocommerce()->front->check_if_tpi_order( $tracking_items, $order );
			
			if ( $tpi_order ) {
				$message .= $this->get_order_details_template( 'emails/tswc-tpi-email-order-details.php', $order, $sent_to_admin, $plain_text, $tracking_item, $wcast_show_product_image, $wcast_show_order_details, $new_status );
			} else {
				$message .= $this->get_order_details_template( 'emails/tswc-email-order-details.php', $order, $sent_to_admin, $plain_text, $tracking_item, $wcast_show_product_image, $wcast_show_order_details, $new_status );
			}
	
			// create a new email
			$mailer = WC()->mailer();
			$email_class = new WC_Email();
			$email_class->id = 'pickup_reminder';

			add_filter( 'wp_kses_allowed_html', array( trackship_admin_customizer(), 'my_allowed_tags' ) );
			add_filter( 'safe_style_css', array( trackship_admin_customizer(), 'safe_style_css_callback' ), 10, 1 );
			// wrap the content with the email template and then add styles
			$message = $mailer->wrap_message( $email_heading, $message );
			$message = apply_filters( 'trackship_mail_content', $message, $email_heading, $order_id, $email_class->id, $new_status );
	
			$email_send = $email_class->send( implode(', ', $recipients), $subject, $message, $email_class->get_headers(), [] );
			$arg = array(
				'order_id'			=> $order_id,
				'order_number'		=> wc_get_order( $order_id )->get_order_number(),
				'user_id'			=> wc_get_order( $order_id )->get_user_id(),
				'tracking_number'	=> $tracking_item['tracking_number'],
				'date'				=> current_time( 'Y-m-d H:i:s' ),
				'to'				=> implode(', ', $recipients),
				'shipment_status'	=> 'pickup_reminder',
				'status'			=> $email_send,
				'status_msg'		=> $email_send ? 'Sent' : 'Not Sent',
				'type'				=> 'Email',
			);
			trackship_for_woocommerce()->ts_actions->update_notification_table( $arg );
	
			if ( $sitepress ) {
				$sitepress->switch_lang($old_lan);
			}
			break;
		}

	}

	/*
	* Gmail and Yahoo read this markup and show a package card above the email.
	* The card only appears once the sending domain is registered with Google.
	*/
	public function add_schema_markup( $message, $email_heading, $order_id, $email_id, $new_status ) {
		if ( ! in_array( $email_id, array( 'shipment_email', 'pickup_reminder' ) ) ) {
			return $message;
		}

		$markup = $this->get_parcel_delivery_markup( $order_id, $new_status );
		if ( ! $markup ) {
			return $message;
		}

		$script = '<script type="application/ld+json">' . $markup . '</script>';

		if ( preg_match( '/<\/head>/i', $message ) ) {
			return preg_replace( '/<\/head>/i', $script . '</head>', $message, 1 );
		}

		if ( preg_match( '/<body[^>]*>/i', $message, $body_tag ) ) {
			return str_replace( $body_tag[0], $body_tag[0] . $script, $message );
		}

		return $script . $message;
	}

	/*
	* Build the schema.org ParcelDelivery for the shipment this email is about
	*/
	public function get_parcel_delivery_markup( $order_id, $new_status ) {
		$order = $this->order;
		$row = $this->shipment_row;
		$tracking_item = $this->tracking_item;

		if ( ! $order || ! $row || empty( $tracking_item['tracking_number'] ) ) {
			return '';
		}

		$tracking_number = $tracking_item['tracking_number'];
		$provider = ! empty( $tracking_item['formatted_tracking_provider'] ) ? $tracking_item['formatted_tracking_provider'] : ( ! empty( $tracking_item['tracking_provider'] ) ? $tracking_item['tracking_provider'] : ( $tracking_item['custom_tracking_provider'] ?? '' ) );

		$tracking_url = trackship_for_woocommerce()->actions->get_tracking_page_link( $order_id, $tracking_number );
		if ( ! $tracking_url ) {
			$tracking_url = ! empty( $tracking_item['formatted_tracking_link'] ) ? $tracking_item['formatted_tracking_link'] : '';
		}

		$data = array(
			'@context'			=> 'http://schema.org',
			'@type'				=> 'ParcelDelivery',
			'trackingNumber'	=> $tracking_number,
		);

		if ( $tracking_url ) {
			$data['trackingUrl'] = $tracking_url;
		}

		if ( $provider ) {
			$data['carrier'] = array(
				'@type'	=> 'Organization',
				'name'	=> trackship_for_woocommerce()->actions->get_provider_name( $provider ),
			);
		}

		$show_est_delivery_date = apply_filters( 'show_est_delivery_date', true, $row->shipping_provider );
		if ( ! empty( $row->est_delivery_date ) && $show_est_delivery_date ) {
			$raw_date = trim( (string) $row->est_delivery_date );
			// Gmail rejects a bare date, so a date with no time becomes the end of that day
			$has_time = (bool) preg_match( '/\d{2}:\d{2}/', $raw_date );
			$when = $has_time ? $raw_date : substr( $raw_date, 0, 10 ) . ' 23:59:59';

			try {
				$arrival = new DateTime( $when, wp_timezone() );
				$data['expectedArrivalUntil'] = $arrival->format( 'c' );
			} catch ( Exception $e ) {
				// an unparseable date simply leaves the field out
			}
		}

		$address = $this->get_schema_delivery_address( $order );
		if ( $address ) {
			$data['deliveryAddress'] = $address;
		}

		$items = $this->get_schema_items_shipped( $order_id, $row, $tracking_item );
		if ( $items ) {
			$data['itemShipped'] = $items;
		}

		$data['partOfOrder'] = array(
			'@type'			=> 'Order',
			'orderNumber'	=> (string) $order->get_order_number(),
			'merchant'		=> array(
				'@type'	=> 'Organization',
				'name'	=> $this->get_blogname(),
			),
		);

		$order_status = $this->get_schema_order_status( $new_status );
		if ( $order_status ) {
			$data['partOfOrder']['orderStatus'] = $order_status;
		}

		if ( $order->get_customer_id() ) {
			$data['partOfOrder']['url'] = $order->get_view_order_url();
		}

		$data = apply_filters( 'trackship_email_schema_markup', $data, $order_id, $new_status, $tracking_item, $row );
		if ( empty( $data ) ) {
			return '';
		}

		// the HEX flags keep a stray tag in a product name from closing the script element
		return wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
	}

	/*
	* Shipment status as one of the schema.org OrderStatus values
	*/
	public function get_schema_order_status( $new_status ) {
		$map = array(
			'in_transit'			=> 'OrderInTransit',
			'out_for_delivery'		=> 'OrderInTransit',
			'available_for_pickup'	=> 'OrderPickupAvailable',
			'pickup_reminder'		=> 'OrderPickupAvailable',
			'delivered'				=> 'OrderDelivered',
			'return_to_sender'		=> 'OrderReturned',
			'failure'				=> 'OrderProblem',
			'on_hold'				=> 'OrderProblem',
			'exception'				=> 'OrderProblem',
		);

		return isset( $map[ $new_status ] ) ? 'http://schema.org/' . $map[ $new_status ] : '';
	}

	/*
	* Delivery address of the order. The shipping address is used whole, or the billing
	* address is used whole, never a mix of fields from both.
	*/
	public function get_schema_delivery_address( $order ) {
		$has_shipping = $order->get_shipping_address_1() || $order->get_shipping_city() || $order->get_shipping_postcode();

		$street = $has_shipping ? $order->get_shipping_address_1() : $order->get_billing_address_1();
		$city = $has_shipping ? $order->get_shipping_city() : $order->get_billing_city();
		$state = $has_shipping ? $order->get_shipping_state() : $order->get_billing_state();
		$postcode = $has_shipping ? $order->get_shipping_postcode() : $order->get_billing_postcode();
		$country = $has_shipping ? $order->get_shipping_country() : $order->get_billing_country();
		$recipient = $has_shipping ? $order->get_formatted_shipping_full_name() : $order->get_formatted_billing_full_name();

		if ( ! $city && ! $country ) {
			return array();
		}

		$address = array( '@type' => 'PostalAddress' );

		// Gmail requires a name on the address
		if ( $recipient ) {
			$address['name'] = $recipient;
		}

		if ( $street ) {
			$address['streetAddress'] = $street;
		}
		if ( $city ) {
			$address['addressLocality'] = $city;
		}
		if ( $state ) {
			$address['addressRegion'] = $state;
		}
		if ( $postcode ) {
			$address['postalCode'] = $postcode;
		}
		if ( $country ) {
			$address['addressCountry'] = $country;
		}

		return $address;
	}

	/*
	* Products in this shipment, as schema.org Product entries
	*/
	public function get_schema_items_shipped( $order_id, $row, $tracking_item ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array();
		}

		$products = array();
		foreach ( $order->get_items() as $item_id => $item ) {
			$variation_id = $item->get_variation_id();
			$product_id = $variation_id ? $variation_id : $item->get_product_id();

			$products[] = array(
				'product_id' => $product_id,
				'product_name' => $item->get_name(),
				'product_qty' => $item->get_quantity(),
				'items_total_price' => $item->get_total(),
				'item_unit_price' => $item->get_quantity() ? $item->get_subtotal() / $item->get_quantity() : $item->get_subtotal(),
				'product_image' => get_the_post_thumbnail_url( $product_id, 'thumbnail' ),
			);
		}

		// the filter the tracking page uses as well, so a shipment that carries only part of
		// the order lists only those items here too
		$products = apply_filters( 'tracking_widget_product_array', $products, $order_id, $row, $row->shipping_provider, $tracking_item['tracking_number'] );
		if ( empty( $products ) ) {
			return array();
		}

		$items = array();
		foreach ( $products as $product ) {
			$item = array(
				'@type'	=> 'Product',
				'name'	=> $product['product_name'],
			);

			if ( ! empty( $product['product_image'] ) ) {
				$item['image'] = $product['product_image'];
			}

			$_product = wc_get_product( $product['product_id'] );
			if ( $_product && $_product->get_sku() ) {
				$item['sku'] = $_product->get_sku();
			}

			$permalink = get_permalink( $product['product_id'] );
			if ( $permalink ) {
				$item['url'] = $permalink;
			}

			$items[] = $item;

			if ( count( $items ) >= 10 ) {
				break;
			}
		}

		return $items;
	}

	public function get_tracking_info_template( $tracking_item, $shipment_row, $order_id, $new_status ) {
		$message = wc_get_template_html( 'emails/tracking-info.php', array( 
			'tracking_items' => array($tracking_item),
			'shipment_row' => $shipment_row,
			'order_id' => $order_id,
			'show_shipment_status' => false,
			'new_status' => $new_status,
			'ts4wc_preview' => false,
		), 'woocommerce-advanced-shipment-tracking/', trackship_for_woocommerce()->get_plugin_path() . '/templates/' );
		return $message;
	}

	public function get_order_details_template( $template_path, $order, $sent_to_admin, $plain_text, $tracking_item, $wcast_show_product_image, $wcast_show_order_details, $new_status ) {
		$message = wc_get_template_html(
			$template_path,
			array(
				'order'			=> $order,
				'sent_to_admin' => $sent_to_admin,
				'plain_text'	=> $plain_text,
				'tracking_items'=> array($tracking_item),
				'email'			=> '',
				'wcast_show_product_image' => $wcast_show_product_image,
				'wcast_show_order_details' => $wcast_show_order_details,
				'ts4wc_preview' => false,
				'new_status'	=> $new_status
			),
			'woocommerce-advanced-shipment-tracking/', 
			trackship_for_woocommerce()->get_plugin_path() . '/templates/'
		);
		return $message;
	}

	/**
	 * Code for format email subject
	*/
	public function email_footer_text( $footer_text ) {

		// TrackShip branding is intentionally not shown in emails; it remains on the tracking page
		// (controlled by show_trackship_branding there).

		$unsubscribe = '';
		if ( get_trackship_settings( 'enable_email_widget' ) ) {
			$tracking_item = isset( $this->tracking_item ) && $this->tracking_item ? $this->tracking_item : [];
			$track_link = $tracking_item['tracking_page_link'] ? $tracking_item['tracking_page_link'] : $this->order->get_view_order_url();
			$track_link = add_query_arg( array( 'unsubscribe' => 'true' ), $track_link );
			$unsubscribe = '<div style="text-align:center;padding-bottom: 10px; margin-bottom: 20px;"><a href="' . $track_link . '">' . esc_html__( 'Unsubscribe', 'trackship-for-woocommerce' ) . '</a></div>';
		}

		return $unsubscribe . $footer_text;
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
		$string = str_replace( '{order_number}', $order->get_order_number(), $string );
		$string = str_replace( '{customer_email}', $customer_email, $string );
		$string = str_replace( '{customer_first_name}', $first_name, $string );
		$string = str_replace( '{customer_last_name}', $last_name, $string );
		if ( isset( $username ) ) {
			$string = str_replace( '{customer_username}', $username, $string );
		} else {
			$string = str_replace( '{customer_username}', '', $string );
		}
		$string = str_replace( '{site_title}', $this->get_blogname(), $string );
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
		$string = str_replace( '{order_number}', $order->get_order_number(), $string );
		$string = str_replace( '{customer_email}', $customer_email, $string );
		$string = str_replace( '{customer_first_name}', $first_name, $string );
		$string = str_replace( '{customer_last_name}', $last_name, $string );
		if ( isset( $username ) ) {
			$string = str_replace( '{customer_username}', $username, $string );
		} else {
			$string = str_replace( '{customer_username}', '', $string );
		}
		$string = str_replace( '{site_title}', $this->get_blogname(), $string );
		return $string;
	} 
	
	/**
	 * Code for format recipients 
	 */ 
	public function email_to( $string, $order, $order_id ) {
		$customer_email = $order ? $order->get_billing_email() : '';
		$admin_email = get_option('admin_email');
		$string = str_replace( '{admin_email}', $admin_email, $string );
		$string = str_replace( '{customer_email}', $customer_email, $string );
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
		
		$trackship_apikey = is_trackship_connected();
		if ( $trackship_apikey ) {
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
		if ( $trackship_apikey ) {
			$email_content = str_replace( '{est_delivery_date}', $est_delivery_date, $email_content );
		}
		return '<div class="shipment_email_content">' . $email_content . '</div>';
	}

	/**
	 * Code for get estimate delivery date
	 */
	public function get_est_delivery_date( $order_id, $order ) {
		
		$row = isset( $this->shipment_row ) && $this->shipment_row ? $this->shipment_row : (object) [];
		$est_delivery_date = isset( $row->est_delivery_date ) ? $row->est_delivery_date : '';

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
}

function WC_TrackShip_Email_Manager() {
	static $instance;

	if ( ! isset( $instance ) ) {
		$instance = new WC_TrackShip_Email_Manager();
	}

	return $instance;
}
WC_TrackShip_Email_Manager();
