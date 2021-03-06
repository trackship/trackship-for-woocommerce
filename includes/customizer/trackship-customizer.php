<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TS4WC_Admin_Customizer {

    /**
	 * Get the class instance
	 *
	 * @since  1.2.5
	 * @return TS4WC_Admin_Customizer
	*/
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

    /**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	*/
	private static $instance;

    /**
	 * Initialize the main plugin function
	 * 
	 * @since  1.2.5
	*/
	public function __construct() {
		$this->init();
	}

    /*
	 * init function
	 *
	 * @since  1.2.5
	*/
	public function init() {
		$this->defaults = $this->wcast_generate_defaults();	
        //adding hooks
		add_action( 'admin_menu', array( $this, 'register_woocommerce_menu' ), 99 );

        //save of settings hook
		add_action( 'wp_ajax_save_trackship_customizer', array( $this, 'customizer_save_trackship_customizer' ) );
		
		//load javascript in admin
		add_action('admin_enqueue_scripts', array( $this, 'customizer_enqueue_scripts' ) );
    }

    /*
	 * Admin Menu add function
	 *
	 * @since  1.2.5
	 * WC sub menu 
	*/
	public function register_woocommerce_menu() {
		add_menu_page( 'TrackShip Customizer', 'TrackShip Customizer', 'manage_options', 'trackship_customizer', array( $this, 'settingsPage' ) );
	}

    /*
	 * callback for settingsPage
	 *
	 * @since  1.2.5
	*/
	public function settingsPage() {
        $page = isset( $_GET['page'] ) ? $_GET['page'] : '' ;
		// Add condition for css & js include for admin page  
		if ( $page != 'trackship_customizer' ) {
			return;
		}

		$type = isset( $_GET['type'] ) ? $_GET['type'] : 'tracking_page' ;
		$shipmentStatus = isset( $_GET["status"] ) ? $_GET["status"] : 'in_transit' ;
		$iframe_url = 'shipment_email' == $type ? $this->get_email_preview_url( $shipmentStatus ) : $this->get_tracking_preview_url( $shipmentStatus ) ;
		$shipment_status = array(
			'in_transit'			=> esc_html__( 'In Transit', 'trackship-for-woocommerce' ),
			'available_for_pickup'	=> esc_html__( 'Available For Pickup', 'trackship-for-woocommerce' ),
			'out_for_delivery'		=> esc_html__( 'Out For Delivery', 'trackship-for-woocommerce' ),
			'failure'				=> esc_html__( 'Failed Attempt', 'trackship-for-woocommerce' ),
			'on_hold'				=> esc_html__( 'On Hold', 'trackship-for-woocommerce' ),
			'exception'				=> esc_html__( 'Exception', 'trackship-for-woocommerce' ),
			'return_to_sender'		=> esc_html__( 'Return To Sender', 'trackship-for-woocommerce' ),
			'delivered'				=> esc_html__( 'Delivered', 'trackship-for-woocommerce' ),
		);
		?>
		<style type="text/css">
			#wpcontent, #wpbody-content, .wp-toolbar {margin: 0 !important;padding: 0 !important;}
			#adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter, .notice, div.error, div.updated, div#query-monitor-main, .wpml-ls-statics-footer.wpml-ls.wpml-ls-legacy-list-horizontal { display: none !important; }
		</style>
		<script type="text/javascript" id="zoremmail-onload">
			jQuery(document).ready( function() {
				jQuery('#adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter, div#query-monitor-main').remove();
			});
		</script>
		<section class="zoremmail-layout zoremmail-layout-has-sider">
			<form method="post" id="zoremmail_email_options" class="zoremmail_email_options" style="display: contents;">
				<section class="zoremmail-layout zoremmail-layout-has-content zoremmail-layout-sider">
					<div class="zoremmail-layout-slider-header">
						<div class="zoremmail-layout-sider-heading">
							<img class="trackship_logo" src="<?php echo trackship_for_woocommerce()->plugin_dir_url() . 'assets/images/trackship-logo-icon.png'; ?>" width="30px" height="30px">
							<button type="button" class="customize-section-back" tabindex="0">
								<span class="dashicons dashicons-arrow-left-alt2"></span>
							</button>
							<h5 class="sider-heading"><?php esc_html_e( 'TrackShip', 'trackship-for-woocommerce' ); ?><span class="customizer_Breadcrumb"> > <?php esc_html_e( 'Customizer', 'trackship-for-woocommerce' ); ?></span></h5>
						</div>
					</div>
					<div class="zoremmail-layout-slider-content">
						<div class="zoremmail-layout-sider-container">
							<?php $this->get_html( $this->shipment_statuses_settings( $shipmentStatus ) ); ?>
						</div>
					</div>
					<aside class="zoremmail-layout-content-media">
						<a data-width="600px" data-iframe-width="100%"><span class="dashicons dashicons-desktop"></span></a>
						<a data-width="600px" data-iframe-width="610px"><span class="dashicons dashicons-tablet"></span></a>
						<a data-width="400px" data-iframe-width="410px"><span class="dashicons dashicons-smartphone"></span></a>
					</aside>
					<div class="zoremmail-back-wordpress">
						<?php $back_link = 'shipment_email' == $type ? admin_url( 'admin.php?page=trackship-for-woocommerce&tab=notifications' ) : admin_url( 'admin.php?page=trackship-for-woocommerce' ); ?>
						<a class="zoremmail-back-wordpress-link" href="<?php echo esc_html( $back_link ); ?>"><span class="zoremmail-back-wordpress-title"><span class="dashicons dashicons-arrow-left-alt"></span><?php esc_html_e( "Back To TrackShip's Settings", 'trackship-for-woocommerce' ); ?></span></a>
					</div>
				</section>
				<section class="zoremmail-layout zoremmail-layout-has-content">
					<div class="zoremmail-layout-content-header">
						<div class="header-panel options-content">
							<?php /*<span class="header_shipment_status">
								<select name="shipmentStatus" id="shipmentStatus" class="select">
									<?php foreach( $shipment_status as $slug => $status) { ?>
										<option value="<?php echo esc_html($slug); ?>" <?php echo $shipmentStatus == $slug ? 'selected' : ''; ?>><?php echo esc_html($status); ?></option>
									<?php } ?>
								</select>
							</span> */?>
							<?php $preview_id = get_option( 'email_preview', 'mockup' ); ?>
							<span class="header_mockup_order" style="padding: 0 17px;">
								<select name="email_preview" id="email_preview" class="select">
									<?php foreach( $this->get_order_ids() as $key => $label ) { ?>
										<option value="<?php echo esc_html( $key ); ?>" <?php echo $preview_id == $key ? 'selected' : ''; ?>><?php echo esc_html( $label ); ?></option>
									<?php } ?>
								</select>
							</span>
							<input type="hidden" name="customizer_type" id="customizer_type" value="<?php echo esc_html( $type ); ?>">
							<span class="" style="float: right;">
								<?php /*<span class="tgl-btn-parent" style="display:none;">
									<?php foreach ( $shipment_status as $key => $value ) { ?>
										<span class="tgl_<?php esc_attr_e( $key ); ?>" <?php echo $shipmentStatus == $key ? '' : 'style="display:none;"'; ?>>
											<?php $slug_status = str_replace( '_', '',$key ); ?>
											<?php $slug_status = 'delivered' == $slug_status ? 'delivered_status' : $slug_status; ?>
											<?php $id =  'wcast_enable_' . $slug_status . '_email'; ?>
											<?php $enable_email = $this->get_value( 'wcast_' . $slug_status . '_email_settings', $id, $slug_status ); ?>
											<input type="hidden" name="<?php esc_attr_e( $id ); ?>" value="0">
											<input type="checkbox" id="<?php esc_attr_e( $id ); ?>" name="<?php esc_attr_e( $id ); ?>" class="tgl tgl-flat" <?php echo $enable_email ? 'checked' : ''; ?> value="1">
											<label class="tgl-btn" for="<?php esc_attr_e( $id ); ?>"></label>
											<label for="<?php esc_attr_e( $id ); ?>"><?php esc_html_e( 'Enable email', 'trackship-for-woocommerce' ); ?></label>
										</span>
									<?php } ?>
								</span> */ ?>
								<button name="save" class="button-primary button-trackship btn_large woocommerce-save-button" type="submit" value="Saved" disabled><?php esc_html_e( 'Save Changes', 'trackship-for-woocommerce' ); ?></button>
								<?php wp_nonce_field( 'trackship_customizer_options_actions', 'trackship_customizer_options_nonce_field' ); ?>
								<input type="hidden" name="action" value="save_trackship_customizer">
							</span>
						</div>
					</div>
					<div class="zoremmail-layout-content-container">
						<section class="zoremmail-layout-content-preview customize-preview">
							<div id="overlay"></div>
							<iframe id="tracking_widget_privew" src="<?php echo esc_url( $iframe_url ); ?>"></iframe>
						</section>
					</div>
				</section>
			</form>
		</section>
		<?php
    }

    /*
	* Add admin javascript
	*
	* @since 1.2.5
	*/	
	public function customizer_enqueue_scripts() {
		
		$page = isset( $_GET["page"] ) ? $_GET["page"] : "" ;
		
		// Add condition for css & js include for admin page 
		if ( $page != 'trackship_customizer' ) {
			return;
		}
		
		wp_register_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
		
		// Add tiptip js and css file		
		wp_enqueue_style( 'trackship-customizer', plugin_dir_url(__FILE__) . 'assets/customizer.css', array(), trackship_for_woocommerce()->version );
		wp_enqueue_script( 'trackship-customizer', plugin_dir_url(__FILE__) . 'assets/customizer.js', array( 'jquery', 'wp-util', 'wp-color-picker','jquery-tiptip' ), trackship_for_woocommerce()->version, true );

		wp_localize_script('trackship-customizer', 'trackship_customizer', array(
			'site_title'			=> get_bloginfo( 'name' ),
			'order_number'			=> 1,
			'customer_first_name'	=> 'Sherlock',
			'customer_last_name'	=> 'Holmes',
			'customer_company_name' => 'Detectives Ltd.',
			'customer_username'		=> 'sher_lock',
			'customer_email'		=> 'sherlock@holmes.co.uk',
			'est_delivery_date'		=> '2021-07-30 15:28:02',
			'email_iframe_url'		=> add_query_arg( array( 'shipment-email-customizer-preview' => '1' ), home_url( '' ) ),
			'tracking_iframe_url'	=> add_query_arg( array( 'action' => 'preview_tracking_page' ), home_url( '' ) ),
		));
	}

	/**
	 * Code for initialize default value for customizer
	*/	
	public function wcast_generate_defaults() {
		$customizer_defaults = array(
			'wc_ast_select_tracking_page_layout'	=> 't_layout_1',
			'wc_ast_select_border_color'			=> '#cccccc',
			'wc_ast_select_bg_color'				=> '#fafafa',
			'wc_ast_hide_tracking_provider_image'	=> 0,
			'wc_ast_link_to_shipping_provider'		=> 1,
			'wc_ast_remove_trackship_branding'		=> 0,
			'wc_ast_hide_tracking_events'			=> 2,
			'wc_ast_select_font_color'				=> '#333',
			'wc_ast_select_widget_padding'			=> 15,
			'wc_ast_select_link_color'				=> '#2271b1',
			'wc_ast_hide_from_to'					=> 1,
			'wc_ast_hide_list_mile_tracking'		=> 1,
		);

		return apply_filters( 'ast_customizer_defaults', $customizer_defaults );
	}

	/**
	 * Code for initialize default value for customizer
	*/
	public function wcast_shipment_settings_defaults($status) {
		$name = $status;
		$name = 'intransit' == $status ? 'In transit' : $name;
		$name = 'availableforpickup' == $status ? 'Available For Pickup' : $name;
		$name = 'outfordelivery' == $status ? 'Out For Delivery' : $name;
		$name = 'failure' == $status ? 'Failed Attempt' : $name;
		$name = 'onhold' == $status ? 'On hold' : $name;
		$name = 'exception' == $status ? 'Exception' : $name;
		$name = 'returntosender' == $status ? 'Return To Sender' : $name;
		$name = 'delivered_status' == $status ? 'Delivered' : $name;
		
		$customizer_defaults = array(			
			'wcast_' . $status . '_email_subject' => __( 'Your order #{order_number} is ' . $name, 'trackship-for-woocommerce' ),
			'wcast_' . $status . '_email_heading' => __( $name, 'trackship-for-woocommerce' ),
			'wcast_' . $status . '_email_content' => __( "Hi there. we thought you'd like to know that your recent order from {site_title} is {$name}", 'trackship-for-woocommerce' ),				
			'wcast_enable_' . $status . '_email'  => '',
			'wcast_' . $status . '_email_to'  => 	'{customer_email}',			
			'wcast_' . $status . '_show_order_details' => 1,			
			'wcast_' . $status . '_hide_shipping_item_price' => 1,	
			'wcast_' . $status . '_show_shipping_address' => 1,
			'wcast_' . $status . '_show_product_image' => 1,
			'wcast_' . $status . '_analytics_link' => '',
			'wcast_' . $status . '_show_tracking_details' => 1,
			'border_color'					=> '#e8e8e8',
			'link_color'					=> '',
			'bg_color'						=> '#fff',
			'font_color'					=> '#333',
			'tracking_page_layout'			=> 't_layout_2',
			'widget_padding'				=> 20,
			'track_button_Text'				=> __( 'Track your order', 'trackship-for-woocommerce' ),
			'track_button_color'			=> '#3c4858',
			'track_button_text_color'		=> '#fff',
			'track_button_border_radius'	=> 0,
			'track_button_font_size'		=> 15,
		);
		return $customizer_defaults;
	}

	public function get_value ( $email_settings, $key, $status = '' ) {
		//echo $email_settings;
		//echo '  ' . $key;
		$value = trackship_for_woocommerce()->ts_actions->get_option_value_from_array( $email_settings, $key, $this->wcast_shipment_settings_defaults($status)[$key] );
		return $value;
	}

	public function shipment_statuses_settings( $status ) {
		$email_iframe_url = $this->get_email_preview_url( $status );
		$tracking_pageiframe_url = $this->get_tracking_preview_url( $status );
		$status = 'in_transit' == $status ? 'intransit' : $status;
		$status = 'available_for_pickup' == $status ? 'availableforpickup' : $status;
		$status = 'out_for_delivery' == $status ? 'outfordelivery' : $status;
		$status = 'on_hold' == $status ? 'onhold' : $status;
		$status = 'return_to_sender' == $status ? 'returntosender' : $status;
		$status = 'delivered' == $status ? 'delivered_status' : $status;

		//Email saved/default vaule
		$border_color = $this->get_value( 'shipment_email_settings', 'border_color', $status );
		$link_color = $this->get_value( 'shipment_email_settings', 'link_color', $status );
		$bg_color = $this->get_value( 'shipment_email_settings', 'bg_color', $status );
		$font_color = $this->get_value( 'shipment_email_settings', 'font_color', $status );
		$tracking_page_layout = $this->get_value( 'shipment_email_settings', 'tracking_page_layout', $status );
		$widget_padding = $this->get_value( 'shipment_email_settings', 'widget_padding', $status );
		$track_button_Text = $this->get_value( 'shipment_email_settings', 'track_button_Text', $status );
		$track_button_color = $this->get_value( 'shipment_email_settings', 'track_button_color', $status );
		$track_button_text_color = $this->get_value( 'shipment_email_settings', 'track_button_text_color', $status );
		$track_button_border_radius = $this->get_value( 'shipment_email_settings', 'track_button_border_radius', $status );
		$track_button_font_size = $this->get_value( 'shipment_email_settings', 'track_button_font_size', $status );

		//Tracking page saved/default vaule
		$wc_ast_select_bg_color = get_option( 'wc_ast_select_bg_color', $this->defaults['wc_ast_select_bg_color'] );
		$wc_ast_select_font_color = get_option( 'wc_ast_select_font_color', $this->defaults['wc_ast_select_font_color'] );
		$wc_ast_select_border_color = get_option( 'wc_ast_select_border_color', $this->defaults['wc_ast_select_border_color'] );
		$wc_ast_select_link_color = get_option( 'wc_ast_select_link_color', $this->defaults['wc_ast_select_link_color'] );
		$wc_ast_select_widget_padding = get_option( 'wc_ast_select_widget_padding', $this->defaults['wc_ast_select_widget_padding'] );
		$tracking_events = get_option( 'wc_ast_hide_tracking_events', $this->defaults['wc_ast_hide_tracking_events'] );
		$link_to_provider = get_option( 'wc_ast_link_to_shipping_provider', $this->defaults['wc_ast_link_to_shipping_provider'] );
		$hide_provider_image = get_option( 'wc_ast_hide_tracking_provider_image', $this->defaults['wc_ast_hide_tracking_provider_image'] );
		$remove_trackship_branding = get_option( 'wc_ast_remove_trackship_branding', $this->defaults['wc_ast_remove_trackship_branding'] );
		$wc_ast_select_tracking_page_layout = get_option( 'wc_ast_select_tracking_page_layout', $this->defaults['wc_ast_select_tracking_page_layout'] );
		$hide_shipping_from_to = get_option( 'wc_ast_hide_from_to', $this->defaults['wc_ast_hide_from_to'] );
		$hide_last_mile = get_option( 'wc_ast_hide_list_mile_tracking', $this->defaults['wc_ast_hide_list_mile_tracking'] );
		$shipped_product_label = get_option( 'shipped_product_label', __( 'Items in this shipment', 'trackship-for-woocommerce' ) );
		$shipping_address_label = get_option( 'shipping_address_label', __( 'Shipping address', 'trackship-for-woocommerce' ) );

		$settings = array(
			'email_preview'	=> array(
				'id'	=> 'email_preview',
				'class' => '',
				'label' => '',
				'title'	=> '',
				'type'	=> 'text',
				'option_name'=> 'email_preview',
				'option_type'=> 'key',
				'show'	=> false,
			),

			//panels
			'email_notifications'	=> array(
				'id'	=> 'email_notifications',
				'class' => 'shipment_email_panel',
				'title'	=> esc_html__( 'Email Notifications', 'trackship-for-woocommerce' ),
				'label' => esc_html__( 'Email Notifications', 'trackship-for-woocommerce' ),
				'type'	=> 'panel',
				'iframe_url' => $email_iframe_url,
				'show'	=> true,
			),
			'email_design'	=> array(
				'id'	=> 'email_design',
				'class' => 'shipment_email_panel',
				'title'	=> esc_html__( 'Email Design', 'trackship-for-woocommerce' ),
				'label' => esc_html__( 'Email Design', 'trackship-for-woocommerce' ),
				'type'	=> 'panel',
				'iframe_url' => $email_iframe_url,
				'show'	=> true,
			),
			'tracking_page'	=> array(
				'id'	=> 'tracking_page',
				'class' => 'tracking_page_panel',
				'title'	=> esc_html__( 'Tracking Page Widget', 'trackship-for-woocommerce' ),
				'label'	=> esc_html__( 'Tracking Page Widget', 'trackship-for-woocommerce' ),
				'type'	=> 'panel',
				'iframe_url' => $tracking_pageiframe_url,
				'show'	=> true,
			),
			
			//sub-panels
			'email_content' => array(
				'id'	=> 'email_content',
				'title'	=> esc_html__( 'Email Content', 'trackship-for-woocommerce' ),
				'type'	=> 'sub-panel',
				'parent'=> 'email_notifications',
				'show'	=> true,
				'class' => 'sub_options_panel',
			),
			'tracking_widget' => array(
				'id'	=> 'tracking_widget',
				'title'	=> esc_html__( 'Tracking Widget', 'trackship-for-woocommerce' ),
				'type'	=> 'sub-panel',
				'parent'=> 'email_design',
				'show'	=> true,
				'class' => 'sub_options_panel',
			),
			'tracking_button' => array(
				'id'	=> 'tracking_button',
				'title'	=> esc_html__( 'Track Button', 'trackship-for-woocommerce' ),
				'type'	=> 'sub-panel',
				'parent'=> 'email_design',
				'show'	=> true,
				'class' => 'sub_options_panel',
			),
			'content_display' => array(
				'id'	=> 'content_display',
				'title'	=> esc_html__( 'Content Display', 'trackship-for-woocommerce' ),
				'type'	=> 'sub-panel',
				'parent'=> 'email_design',
				'show'	=> true,
				'class' => 'sub_options_panel',
			),

			'widget_style' => array(
				'id'	=> 'widget_style',
				'title'	=> esc_html__( 'Widget Style', 'trackship-for-woocommerce' ),
				'type'	=> 'sub-panel',
				'parent'=> 'tracking_page',
				'show'	=> true,
				'class' => 'sub_options_panel',
			),
			'widget_layout' => array(
				'id'	=> 'widget_layout',
				'title'	=> esc_html__( 'Widget Layout', 'trackship-for-woocommerce' ),
				'type'	=> 'sub-panel',
				'parent'=> 'tracking_page',
				'show'	=> true,
				'class' => 'sub_options_panel',
			),
			'tracking_link' => array(
				'id'	=> 'tracking_link',
				'title'	=> esc_html__( 'Tracking Link', 'trackship-for-woocommerce' ),
				'type'	=> 'sub-panel',
				'parent'=> 'tracking_page',
				'show'	=> true,
				'class' => 'sub_options_panel',
			),


			//section
			'heading3'	=> array(
				'id'	=> 'tracking_widget',
				'class' => 'email_design_first_section',
				'title'	=> esc_html__( 'Tracking Widget', 'trackship-for-woocommerce' ),
				'type'	=> 'section',
				'parent'=> 'tracking_widget',
				'show'	=> true,
			),
			'tracking_page_layout' => array(
				'title'    => __( 'Tracker type', 'trackship-for-woocommerce' ),
				'type'     => 'select',
				'option_name'=> 'shipment_email_settings',
				'option_type'=> 'array',
				'default'  => $tracking_page_layout,
				'show'     => true,
				'options'  => array(
					't_layout_2' => __( 'Progress bar', 'trackship-for-woocommerce' ),
					't_layout_1' => __( 'Icons', 'trackship-for-woocommerce' ),
					't_layout_3' => __( 'Single icon', 'trackship-for-woocommerce' ),
				)
			),
			'bg_color' => array(
				'title'    => esc_html__( 'Widget background color', 'trackship-for-woocommerce' ),
				'type'     => 'color',
				'option_name'=> 'shipment_email_settings',
				'option_type'=> 'array',
				'default'  => $bg_color,
				'show'     => true,
				'class'		=> 'colorset',
			),
			'font_color' => array(
				'title'    => esc_html__( 'Widget font color', 'trackship-for-woocommerce' ),
				'type'     => 'color',
				'option_name'=> 'shipment_email_settings',
				'option_type'=> 'array',
				'default'  => $font_color,
				'show'     => true,
				'class'		=> 'colorset',
			),
			'border_color' => array(
				'title'    => esc_html__( 'Widget border color', 'trackship-for-woocommerce' ),
				'type'     => 'color',
				'option_name'=> 'shipment_email_settings',
				'option_type'=> 'array',
				'default'  => $border_color,
				'show'     => true,
				'class'		=> 'colorset',
			),
			'link_color' => array(
				'title'    => esc_html__( 'Links color', 'trackship-for-woocommerce' ),
				'type'     => 'color',
				'option_name'=> 'shipment_email_settings',
				'option_type'=> 'array',
				'default'  => $link_color,
				'show'     => true,
				'class'		=> 'colorset',
			),
			'widget_padding' => array(
				'title'    => esc_html__( 'Widget padding', 'trackship-for-woocommerce' ),
				'type'     => 'range',
				'option_name'=> 'shipment_email_settings',
				'option_type'=> 'array',
				'default'  => $widget_padding,
				'show'     => true,
				'min'		=> 10,
				'max'		=> 30,
			),
			'heading4'	=> array(
				'id'	=> 'tracking_button',
				'title'	=> esc_html__( 'Track Button', 'trackship-for-woocommerce' ),
				'type'	=> 'section',
				'parent'=> 'tracking_button',
				'show'	=> true,
			),
			'track_button_Text' => array(
				'title'    => esc_html__( 'Track button text', 'trackship-for-woocommerce' ),
				'default'  => $track_button_Text,
				'placeholder' => $track_button_Text,
				'type'     => 'text',
				'option_name'=> 'shipment_email_settings',
				'option_type'=> 'array',
				'show'     => true,
				'class' 	=> 'track_button_Text',
			),
			'track_button_color' => array(
				'title'    => esc_html__( 'Button color', 'trackship-for-woocommerce' ),
				'type'     => 'color',
				'option_name'=> 'shipment_email_settings',
				'option_type'=> 'array',
				'default'  => $track_button_color,
				'show'     => true,
				'class'		=> 'colorset',
			),
			'track_button_font_size' => array(
				'title'    => esc_html__( 'Button size', 'trackship-for-woocommerce' ),
				'type'     => 'radio_butoon',
				'option_name'=> 'shipment_email_settings',
				'option_type'=> 'array',
				'default'  => $track_button_font_size,
				'show'     => true,
				'choices' => array(
					15 => __( 'Normal', 'trackship-for-woocommerce' ),
					20 => __( 'Large', 'trackship-for-woocommerce'  )
				),
			),
			'track_button_text_color' => array(
				'title'    => esc_html__( 'Button font color', 'trackship-for-woocommerce' ),
				'type'     => 'color',
				'option_name'=> 'shipment_email_settings',
				'option_type'=> 'array',
				'default'  => $track_button_text_color,
				'show'     => true,
				'class'		=> 'colorset',
			),
			'track_button_border_radius' => array(
				'title'    => esc_html__( 'Border radius', 'trackship-for-woocommerce' ),
				'type'     => 'range',
				'option_name'=> 'shipment_email_settings',
				'option_type'=> 'array',
				'default'  => $track_button_border_radius,
				'show'     => true,
				'min'		=> 0,
				'max'		=> 10,
			),
			//Tracking Page Settings
			'heading5'	=> array(
				'id'	=> 'widget_style',
				'class' => 'tracking_page_first_section',
				'title'	=> esc_html__( 'Widget Style', 'trackship-for-woocommerce' ),
				'type'	=> 'section',
				'parent'=> 'widget_style',
				'show'	=> true,
			),
			'wc_ast_select_bg_color' => array(
				'title'    => esc_html__( 'Background color', 'trackship-for-woocommerce' ),
				'type'     => 'color',
				'default'  => $wc_ast_select_bg_color,
				'show'     => true,
				'class'		=> 'colorset',
				'option_name'=> 'wc_ast_select_bg_color',
				'option_type'=> 'key',
			),
			'wc_ast_select_font_color' => array(
				'title'    => esc_html__( 'Font color', 'trackship-for-woocommerce' ),
				'type'     => 'color',
				'default'  => $wc_ast_select_font_color,
				'show'     => true,
				'class'		=> 'colorset',
				'option_name'=> 'wc_ast_select_font_color',
				'option_type'=> 'key',
			),
			'wc_ast_select_border_color' => array(
				'title'    => esc_html__( 'Border color', 'trackship-for-woocommerce' ),
				'type'     => 'color',
				'default'  => $wc_ast_select_border_color,
				'show'     => true,
				'class'		=> 'colorset',
				'option_name'=> 'wc_ast_select_border_color',
				'option_type'=> 'key',
			),
			'wc_ast_select_link_color' => array(
				'title'    => esc_html__( 'Links color', 'trackship-for-woocommerce' ),
				'type'     => 'color',
				'default'  => $wc_ast_select_link_color,
				'show'     => true,
				'class'		=> 'colorset',
				'option_name'=> 'wc_ast_select_link_color',
				'option_type'=> 'key',
			),
			'wc_ast_select_widget_padding' => array(
				'title'    => esc_html__( 'Padding', 'trackship-for-woocommerce' ),
				'type'     => 'range',
				'default'  => $wc_ast_select_widget_padding,
				'show'     => true,
				'min'		=> 10,
				'max'		=> 30,
				'option_name'=> 'wc_ast_select_widget_padding',
				'option_type'=> 'key',
			),
			'heading6'		=> array(
				'id'		=> 'widget_layout',
				'title'		=> esc_html__( 'Widget Layout', 'trackship-for-woocommerce' ),
				'type'		=> 'section',
				'parent'	=> 'widget_layout',
				'show'		=> true,
			),
			'wc_ast_hide_tracking_events' => array(
				'title'    => esc_html__( 'Tracking event display', 'trackship-for-woocommerce' ),
				'type'     => 'select',
				'default'  => $tracking_events,
				'show'     => true,
				'options'  => array(
					0 => __( 'Show all events', 'trackship-for-woocommerce' ),
					1 => __( 'Hide tracking events', 'trackship-for-woocommerce' ),					
					2 => __( 'Show last events & expand', 'trackship-for-woocommerce' ),
				),
				'option_name'=> 'wc_ast_hide_tracking_events',
				'option_type'=> 'key',
			),
			'wc_ast_select_tracking_page_layout' => array(
				'title'    => __( 'Tracker type', 'trackship-for-woocommerce' ),
				'type'     => 'select',
				'default'  => $wc_ast_select_tracking_page_layout,
				'show'     => true,
				'options'  => array(
					't_layout_2' => __( 'Progress bar', 'trackship-for-woocommerce' ),
					't_layout_1' => __( 'Icons', 'trackship-for-woocommerce' ),
					't_layout_3' => __( 'Single icon', 'trackship-for-woocommerce' ),
				),
				'option_name'=> 'wc_ast_select_tracking_page_layout',
				'option_type'=> 'key',
			),
			'wc_ast_link_to_shipping_provider' => array(
				'title'    => __( 'Enable tracking # link to carrier', 'trackship-for-woocommerce' ),
				'default'  => $link_to_provider,
				'type'     => 'checkbox',
				'show'     => true,
				'option_name'=> 'wc_ast_link_to_shipping_provider',
				'option_type'=> 'key',
			),
			'wc_ast_hide_tracking_provider_image' => array(
				'title'    => __( 'Hide the shipping provider logo', 'trackship-for-woocommerce' ),
				'default'  => $hide_provider_image,
				'type'     => 'checkbox',
				'show'     => true,
				'option_name'=> 'wc_ast_hide_tracking_provider_image',
				'option_type'=> 'key',
			),
			'wc_ast_hide_from_to' => array(
				'title'    => __( 'Hide shipping from-to', 'trackship-for-woocommerce' ),
				'default'  => $hide_shipping_from_to,
				'type'     => 'checkbox',
				'show'     => true,
				'option_name'=> 'wc_ast_hide_from_to',
				'option_type'=> 'key',
			),
			'wc_ast_hide_list_mile_tracking' => array(
				'title'    => __( 'Hide delivery tracking number', 'trackship-for-woocommerce' ),
				'default'  => $hide_last_mile,
				'type'     => 'checkbox',
				'tip-tip'  => __( 'The delivery tracking number will display if the shipment is getting a different tracking number at the destination country from the local postal service (i.e 4PX -> USPS)', 'trackship-for-woocommerce' ),
				'show'     => true,
				'option_name'=> 'wc_ast_hide_list_mile_tracking',
				'option_type'=> 'key',
			),
			'heading7'		=> array(
				'id'		=> 'tracking_link',
				'title'		=> esc_html__( 'Tracking Link', 'trackship-for-woocommerce' ),
				'type'		=> 'section',
				'parent'	=> 'tracking_link',
				'show'		=> true,
			),
			'wc_ast_remove_trackship_branding' => array(
				'title'    => __( 'Hide TrackShip branding', 'trackship-for-woocommerce' ),
				'default'  => $remove_trackship_branding,
				'type'     => 'checkbox',
				'show'     => true,
				'option_name'=> 'wc_ast_remove_trackship_branding',
				'option_type'=> 'key',
			),
		);
		
		$all_statuses = array(
			'intransit' => 'in_transit',
			'availableforpickup' => 'available_for_pickup',
			'outfordelivery' => 'out_for_delivery',
			'failure' => 'failure',
			'onhold' => 'on_hold',
			'exception' => 'exception',
			'returntosender' => 'return_to_sender',
			'delivered_status' => 'delivered',
		);

		$settings[ 'heading1' ] = array(
			'id'	=> 'email_settings',
			'class' => 'email_content_first_section ',
			'title'	=> esc_html__( 'Email Content', 'trackship-for-woocommerce' ),
			'type'	=> 'section',
			'parent'=> 'email_content',
			'show'	=> true,
		);

		$settings[ 'shipmentStatus' ] = array(
			'title'    => __( 'Shipment status', 'trackship-for-woocommerce' ),
			'type'     => 'select',
			'default'  => isset( $_GET['status'] ) ? $_GET['status'] : 'in_transit',
			'show'     => true,
			'options'  => array(
				'in_transit' => __( 'In Transit', 'trackship-for-woocommerce' ),
				'available_for_pickup' => __( 'Available For Pickup', 'trackship-for-woocommerce' ),
				'out_for_delivery' => __( 'Out For Delivery', 'trackship-for-woocommerce' ),
				'failure' => __( 'Failed Attempt', 'trackship-for-woocommerce' ),
				'on_hold' => __( 'On Hold', 'trackship-for-woocommerce' ),
				'exception' => __( 'Exception', 'trackship-for-woocommerce' ),
				'return_to_sender' => __( 'Return To Sender', 'trackship-for-woocommerce' ),
				'delivered' => __( 'Delivered', 'trackship-for-woocommerce' ),
			),
		);

		foreach ( $all_statuses as $key => $value ) {
			$email_settings = 'wcast_' . $key . '_email_settings';			
			
			$settings[ 'wcast_enable_' . $key . '_email' ] = array(
				'type'		=> 'tgl-btn',
				'option_name'=> $email_settings,
				'option_type'=> 'array',
				'show'		=> true,
				'default'	=> $this->get_value( $email_settings, 'wcast_enable_' . $key . '_email', $key ),
				'class'		=> $value . '_sub_menu all_status_submenu',
			);
			$settings[ 'wcast_'.$key.'_email_to' ] = array(
				'title'    => esc_html__( 'Recipients', 'trackship-for-woocommerce' ),
				'desc'  => esc_html__( 'add comma-seperated emails, defaults to placeholder {customer_email} ', 'trackship-for-woocommerce' ),
				'default'  => $this->get_value( $email_settings, 'wcast_' . $key . '_email_to', $key ),
				'type'     => 'text',
				'option_name'=> $email_settings,
				'option_type'=> 'array',
				'show'     => true,
				'class'		=> $value . '_sub_menu all_status_submenu',
			);
			$settings[ 'wcast_'.$key.'_email_subject' ] = array(
				'title'    => esc_html__( 'Email subject', 'trackship-for-woocommerce' ),
				'desc'  => esc_html__( 'Available variables:', 'trackship-for-woocommerce' ) . ' {site_title}, {order_number}',
				'default'  => $this->get_value( $email_settings, 'wcast_' . $key . '_email_subject', $key ),
				'type'     => 'text',
				'option_name'=> $email_settings,
				'option_type'=> 'array',
				'show'     => true,
				'class'		=> $value . '_sub_menu all_status_submenu',
			);
			$settings[ 'wcast_'.$key.'_email_heading' ] = array(
				'title'    => esc_html__( 'Email heading', 'trackship-for-woocommerce' ),
				'desc'  => esc_html__( 'Available variables:', 'trackship-for-woocommerce' ) . ' {site_title}, {order_number}',
				'default'  => $this->get_value( $email_settings, 'wcast_' . $key . '_email_heading', $key ),
				'type'     => 'text',
				'option_name'=> $email_settings,
				'option_type'=> 'array',
				'show'     => true,
				'class'	=> 'heading ' . $value . '_sub_menu all_status_submenu',
			);
			$settings[ 'wcast_'.$key.'_email_content' ] = array(
				'title'		=> esc_html__( 'Email Content', 'trackship-for-woocommerce' ),
				'desc'		=> '',
				'default'	=> $this->get_value( $email_settings, 'wcast_' . $key . '_email_content', $key ),
				'type'		=> 'textarea',
				'option_name'=> $email_settings,
				'option_type'=> 'array',
				'show'		=> true,
				'class'		=> 'email_content ' . $value . '_sub_menu all_status_submenu',
			);
			$settings[ 'codeinfoblock '. $key ] = array(
				'title'    => esc_html__( 'Available placeholders:', 'trackship-for-woocommerce' ),
				'default'  => '<code>{customer_first_name}<br>{customer_last_name}<br>{site_title}<br>{order_number}<br>{customer_company_name}<br>{customer_username}<br>{customer_email}<br>{est_delivery_date}</code>',
				'type'     => 'codeinfo',
				'show'     => true,
				'class'		=> $value . '_sub_menu all_status_submenu',
			);
			$settings[ 'wcast_'.$key.'_analytics_link' ] = array(
				'title'		=> esc_html__( 'Google analytics link tracking', 'trackship-for-woocommerce' ),
				'desc'		=> esc_html__( 'This will be appended to URL in the email content', 'trackship-for-woocommerce' ),
				'default'	=> $this->get_value( $email_settings, 'wcast_' . $key . '_analytics_link', $key ),
				'type'		=> 'text',
				'option_name'=> $email_settings,
				'option_type'=> 'array',
				'show'		=> true,
				'class'		=> $value . '_sub_menu all_status_submenu',
			);
		}

		$settings[ 'heading2' ] = array(
			'id'	=> 'content_display',
			'class' => '',
			'title'	=> esc_html__( 'Content Display', 'trackship-for-woocommerce' ),
			'type'	=> 'section',
			'parent'=> 'content_display',
			'show'	=> true,
		);

		foreach ( $all_statuses as $key => $value ) {
			$email_settings = 'wcast_' . $key . '_email_settings';
			
			$settings[ 'wcast_'.$key.'_show_order_details' ] = array(
				'title'    => esc_html__( 'Display shipped products', 'trackship-for-woocommerce' ),
				'default'  => $this->get_value( $email_settings, 'wcast_' . $key . '_show_order_details', $key ),
				'type'     => 'checkbox',
				'option_name'=> $email_settings,
				'option_type'=> 'array',
				'show'     => true,
				'class'		=> $value . '_sub_menu all_status_submenu',
			);
			$settings[ 'wcast_'.$key.'_shipped_product_label' ] = array(
				'title'    => esc_html__( 'Shipped products header text', 'trackship-for-woocommerce' ),
				'default'  => $shipped_product_label,
				'type'     => 'text',
				'option_name'=> 'shipped_product_label',
				'option_type'=> 'key',
				'show'     => true,
				'class'		=> $value . '_sub_menu all_status_submenu shipped_product_label',
			);
			$settings[ 'wcast_'.$key.'_show_product_image' ] = array(
				'title'    => esc_html__( 'Display product image', 'trackship-for-woocommerce' ),
				'default'  => $this->get_value( $email_settings, 'wcast_' . $key . '_show_product_image', $key ),
				'type'     => 'checkbox',
				'option_name'=> $email_settings,
				'option_type'=> 'array',
				'show'     => true,
				'class'		=> $value . '_sub_menu all_status_submenu',
			);
			$settings[ 'wcast_'.$key.'_show_shipping_address' ] = array(
				'title'    => esc_html__( 'Display shipping address', 'trackship-for-woocommerce' ),
				'default'  => $this->get_value( $email_settings, 'wcast_' . $key . '_show_shipping_address', $key ),
				'type'     => 'checkbox',
				'option_name'=> $email_settings,
				'option_type'=> 'array',
				'show'     => true,
				'class'		=> $value . '_sub_menu all_status_submenu',
			);
			$settings[ 'wcast_'.$key.'_shipping_address_label' ] = array(
				'title'    => esc_html__( 'Shipping address header text', 'trackship-for-woocommerce' ),
				'default'  => $shipping_address_label,
				'type'     => 'text',
				'option_name'=> 'shipping_address_label',
				'option_type'=> 'key',
				'show'     => true,
				'class'		=> $value . '_sub_menu all_status_submenu shipping_address_label',
			);
		}
		return $settings;
	}

	/*
	* Get html of fields
	*/
	public function get_html( $arrays ) {
		//echo '<pre>';print_r($arrays);echo '</pre>';
		echo '<ul class="zoremmail-panels">';
		foreach ( (array) $arrays as $id => $array ) {
			
			if ( isset($array['show']) && $array['show'] != true ) {
				continue; 
			}

			if ( isset($array['type']) && $array['type'] == 'panel' ) {
				?>
				<li id="<?php isset($array['id']) ? esc_attr_e($array['id']) : ''; ?>" data-label="<?php isset($array['label']) ? esc_attr_e($array['label']) : ''; ?>" data-iframe_url="<?php isset($array['iframe_url']) ? esc_attr_e($array['iframe_url']) : ''; ?>" class="zoremmail-panel-title <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?>">
					<span><?php isset($array['title']) ? esc_html_e($array['title']) : ''; ?></span>
					<span class="dashicons dashicons-arrow-right-alt2"></span>
				</li>
				<?php
			}
		}
		echo '</ul>';

		echo '<ul class="zoremmail-sub-panels" style="display:none;">';
		foreach ( (array) $arrays as $id => $array ) {
			
			if ( isset($array['show']) && true != $array['show'] ) {
				continue; 
			}
			
			if ( isset($array['type']) && 'sub-panel' == $array['type'] ) {
				?>
				<li id="<?php isset($array['id']) ? esc_attr_e($array['id']) : ''; ?>"  data-type="<?php isset($array['parent']) ? esc_html_e($array['parent']) : ''; ?>" data-label="<?php isset($array['title']) ? esc_html_e($array['title']) : ''; ?>" class="zoremmail-sub-panel-title <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?> <?php isset($array['parent']) ? esc_attr_e($array['parent']) : ''; ?>">
					<span><?php isset($array['title']) ? esc_html_e($array['title']) : ''; ?></span>
					<span class="dashicons dashicons-arrow-right-alt2"></span>
				</li>
				<?php
			}
		}
		echo '</ul>';

		foreach ( (array) $arrays as $id => $array ) {

			if ( isset($array['show']) && $array['show'] != true ) {
				continue; 
			}

			if ( isset($array['type']) && $array['type'] == 'panel' ) {
				continue; 
			}

			if ( isset($array['type']) && 'sub-panel' == $array['type'] ) {
				continue; 
			}
			
			if ( isset($array['type']) && 'section' == $array['type'] ) {
				echo $id != 'heading' ? '</div>' : '';
				?>
				<div data-id="<?php isset($array['parent']) ? esc_attr_e($array['parent']) : ''; ?>" class="zoremmail-menu-submenu-title <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?>">
					<span><?php esc_html_e( $array['title'] ); ?></span>
					<span class="dashicons dashicons-arrow-right-alt2"></span>
				</div>
				<div class="zoremmail-menu-contain">
				<?php
			} else {
				$array_default = isset( $array['default'] ) ? $array['default'] : '';
				?>
				<div class="zoremmail-menu zoremmail-menu-inline zoremmail-menu-sub <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?>">
					<div class="zoremmail-menu-item">
						<div class="<?php esc_attr_e( $id ); ?> <?php esc_attr_e( $array['type'] ); ?>">
							<?php if ( isset($array['title']) && $array['type'] != 'checkbox' ) { ?>
								<div class="menu-sub-title"><?php esc_html_e( $array['title'] ); ?></div>
							<?php } ?>
							<?php if ( isset($array['type']) && $array['type'] == 'text' ) { ?>
								<?php //echo '<pre>';print_r($array);echo '</pre>'; ?>
								<?php $field_name = isset( $array['option_type'] ) && 'key' == $array['option_type'] ? $array['option_name'] : $id; ?>
								<div class="menu-sub-field">
									<input type="text" name="<?php esc_attr_e( $field_name ); ?>" placeholder="<?php isset($array['placeholder']) ? esc_attr_e($array['placeholder']) : ''; ?>" value="<?php echo esc_html( $array_default ); ?>" class="zoremmail-input <?php esc_html_e($array['type']); ?> <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?>">
									<br>
									<span class="menu-sub-tooltip"><?php isset($array['desc']) ? esc_html_e($array['desc']) : ''; ?></span>
								</div>
							<?php } else if ( isset($array['type']) && $array['type'] == 'textarea' ) { ?>
								<div class="menu-sub-field">
									<textarea id="<?php esc_attr_e( $id ); ?>" rows="4" name="<?php esc_attr_e( $id ); ?>" placeholder="<?php isset($array['placeholder']) ? esc_attr_e($array['placeholder']) : ''; ?>" class="zoremmail-input <?php esc_html_e($array['type']); ?> <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?>"><?php echo esc_html( $array_default ); ?></textarea>
									<br>
									<span class="menu-sub-tooltip"><?php isset($array['desc']) ? esc_html_e($array['desc']) : ''; ?></span>
								</div>
							<?php } else if ( isset($array['type']) && $array['type'] == 'codeinfo' ) { ?>
								<div class="menu-sub-field">
									<span class="menu-sub-codeinfo <?php esc_html_e($array['type']); ?>"><?php echo isset($array['default']) ? wp_kses_post($array['default']) : ''; ?></span>
								</div>
							<?php } else if ( isset($array['type']) && $array['type'] == 'select' ) { ?>
								<div class="menu-sub-field">
									<select name="<?php esc_attr_e( $id ); ?>" id="<?php esc_attr_e( $id ); ?>" class="zoremmail-input <?php esc_html_e($array['type']); ?> <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?>">
										<?php foreach ( (array) $array['options'] as $key => $val ) { ?>
											<option value="<?php echo esc_html($key); ?>" <?php echo $array_default == $key ? 'selected' : ''; ?>><?php echo esc_html($val); ?></option>
										<?php } ?>
									</select>
									<br>
									<span class="menu-sub-tooltip"><?php isset($array['desc']) ? esc_html_e($array['desc']) : ''; ?></span>
								</div>
							<?php } else if ( isset($array['type']) && $array['type'] == 'color' ) { ?>
								<div class="menu-sub-field">
									<input type="text" name="<?php esc_attr_e( $id ); ?>" id="<?php esc_attr_e( $id ); ?>" class="input-text regular-input zoremmail-input <?php esc_html_e($array['type']); ?> <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?>" value="<?php echo esc_html( $array_default ); ?>" placeholder="<?php isset($array['placeholder']) ? esc_attr_e($array['placeholder']) : ''; ?>">
									<br>
									<span class="menu-sub-tooltip"><?php isset($array['desc']) ? esc_html_e($array['desc']) : ''; ?></span>
								</div>
							<?php } else if ( isset($array['type']) && $array['type'] == 'checkbox' ) { ?>
								<?php //echo '<pre>';print_r($array);echo '</pre>'; ?>
								<div class="menu-sub-field">
									<label class="menu-sub-title">
										<input type="hidden" name="<?php esc_attr_e( $id ); ?>" value="0"/>
										<input type="checkbox" id="<?php esc_attr_e( $id ); ?>" name="<?php esc_attr_e( $id ); ?>" class="zoremmail-checkbox <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?>" value="1" <?php echo $array_default ? 'checked' : ''; ?>/>
										<?php esc_html_e( $array['title'] ); ?>
										<?php if ( isset($array['tip-tip'] ) ) { ?>
											<span class="woocommerce-help-tip tipTip" title="<?php echo esc_html( $array['tip-tip'] ); ?>"></span>
										<?php } ?>
									</label>
								</div>
							<?php } else if ( isset($array['type']) && $array['type'] == 'radio_butoon' ) { ?>
								<div class="menu-sub-field">
									<label class="menu-sub-title">
										<?php foreach ( $array['choices'] as $key => $value ) { ?>
											<label class="radio-button-label">
												<input type="radio" name="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php echo $array_default == $key ? 'checked' : ''; ?>/>
												<span><?php echo esc_html( $value ); ?></span>
											</label>
										<?php } ?>
									</label>
								</div>
							<?php } else if ( isset($array['type']) && $array['type'] == 'tgl-btn' ) { ?>
								<div class="menu-sub-field">
									<?php //echo $array_default; ?>
									<label class="menu-sub-title">
										<span class="tgl-btn-parent">
											<input type="hidden" name="<?php esc_attr_e( $id ); ?>" value="0">
											<input type="checkbox" id="<?php esc_attr_e( $id ); ?>" name="<?php esc_attr_e( $id ); ?>" class="tgl tgl-flat" <?php echo $array_default ? 'checked' : ''; ?> value="1">
											<label class="tgl-btn" for="<?php esc_attr_e( $id ); ?>"></label>
										</span>
										<label for="<?php esc_attr_e( $id ); ?>" class="shipment_email_label"><?php esc_html_e( 'Enable email', 'trackship-for-woocommerce' ); ?></label>
									</label>
								</div>
							<?php } else if ( isset($array['type']) && $array['type'] == 'range' ) { ?>
								<div class="menu-sub-field">
									<label class="menu-sub-title">
										<input type="range" class="zoremmail-range" id="<?php esc_attr_e( $id ); ?>" name="<?php esc_attr_e( $id ); ?>" value="<?php echo esc_html( $array_default ); ?>" min="<?php esc_html_e( $array['min'] ); ?>" max="<?php esc_html_e( $array['max'] ); ?>" oninput="this.nextElementSibling.value = this.value">
										<input style="width:50px;" class="slider__value" type="number" min="<?php esc_attr_e( $array['min'] ); ?>" max="<?php esc_attr_e( $array['max'] ); ?>" value="<?php echo esc_html( $array_default ); ?>">
									</label>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
				<?php
			}
		}
	}

	/**
	 * Get Order Ids
	 *
	 * @return array
	 */
	public static function get_order_ids() {		
		$order_array = array();
		$order_array['mockup'] = __( 'Mockup Order', 'trackship-for-woocommerce' );
		
		$orders = wc_get_orders( array(
			'limit'        => 20,
			'orderby'      => 'date',
			'order'        => 'DESC',
			'meta_key'     => '_wc_shipment_tracking_items', // The postmeta key field
			'meta_compare' => 'EXISTS', // The comparison argument
		));	
			
		foreach ( $orders as $order ) {				
			$order_array[ $order->get_id() ] = $order->get_id() . ' - ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
		}
		return $order_array;
	}

	/**
	 * Get Customizer URL
	 *
	 */
	public static function get_tracking_preview_url( $status ) {		
		$tracking_preview_url = add_query_arg( array(
			'action'	=> 'preview_tracking_page',
			'status'	=> $status
		), home_url( '' ) );		

		return $tracking_preview_url;
	}

	/**
	 * Get Shipment status preview URL
	 *
	 */
	public function get_email_preview_url( $status ) {
		return add_query_arg( array(
			'shipment-email-customizer-preview' => '1',
			'status'	=> $status
		), home_url( '' ) );
	}

	public function my_allowed_tags( $tags ) {
		$tags['style'] = array( 'type' => true, );
		return $tags;
	}
	
	public function safe_style_css_callback( $styles ) {
		$styles[] = 'display';
		return $styles;
	}

	public function shipment_email_preview_css( $css, $email ) { 
		$css .= '
			#wrapper { padding: 30px 0 30px 0 !important; }
		';
		return $css;
	}
	
	/**
	 * Get WooCommerce order for preview
	 *	 
	 * @param string $order_status
	 * @return object
	 */
	public function get_wc_order_for_preview( $order_id ) {
		if ( ! empty( $order_id ) && 'mockup' != $order_id ) {
			return wc_get_order( $order_id );
		} else {			
			// Instantiate order object
			$order = new WC_Order();			
			
			// Other order properties
			$order->set_props( array(
				'id'                 => 1,
				'status'             => ( 'processing' ),
				'shipping_first_name' => 'Sherlock',
				'shipping_last_name'  => 'Holmes',
				'shipping_company'    => 'Detectives Ltd.',
				'shipping_address_1'  => '221B Baker Street',
				'shipping_city'       => 'London',
				'shipping_postcode'   => 'NW1 6XE',
				'shipping_country'    => 'GB',
				'billing_first_name' => 'Sherlock',
				'billing_last_name'  => 'Holmes',
				'billing_company'    => 'Detectives Ltd.',
				'billing_address_1'  => '221B Baker Street',
				'billing_city'       => 'London',
				'billing_postcode'   => 'NW1 6XE',
				'billing_country'    => 'GB',
				'billing_email'      => 'sherlock@holmes.co.uk',
				'billing_phone'      => '02079304832',
				'date_created'       => gmdate( 'Y-m-d H:i:s' ),
				'total'              => 24.90,				
			) );

			// Item #1
			$order_item = new WC_Order_Item_Product();
			$order_item->set_props( array(
				'name'     => 'A Study in Scarlet',
				'subtotal' => '9.95',
				'sku'      => 'kwd_ex_1',
			) );
			$order->add_item( $order_item );

			// Item #2
			$order_item = new WC_Order_Item_Product();
			$order_item->set_props( array(
				'name'     => 'The Hound of the Baskervilles',
				'subtotal' => '14.95',
				'sku'      => 'kwd_ex_2',
			) );
			$order->add_item( $order_item );						

			// Return mockup order
			return $order;
		}
	}
	
	public function get_wc_shipment_status_for_preview( $status = 'in_transit', $order_id = null ) {
		$shipment_status = array();
		if ( ! empty( $order_id ) && 'mockup' != $order_id ) {
			$array = get_post_meta( $order_id, 'shipment_status', true );
			$shipment_status[] = $array[0];
		} else {
			$shipment_status[] = array(
				'status_date' => '2021-07-27 15:28:02',
				'est_delivery_date' => '2021-07-30 15:28:02',
				'status' => $status,
				'tracking_events' => array(),
				'tracking_page' => '',
			);
		}
		return $shipment_status;
	}
	
	public function get_tracking_items_for_preview( $order_id = null ) {
		$tracking_items = array();
		if ( ! empty( $order_id ) && 'mockup' != $order_id ) {
			$array = trackship_for_woocommerce()->get_tracking_items( $order_id );
			$tracking_items[] = $array[0];
		} else {
			$tracking_items[] = array(
				'tracking_provider' => 'usps',
				'tracking_number' => '4208001392612927',
				'formatted_tracking_provider' => 'USPS',
				'formatted_tracking_link' => 'https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1=4208001392612927'
			);
		}
		return $tracking_items;
	}
	
	public function customizer_save_trackship_customizer() {
		if ( !current_user_can( 'manage_options' ) ) {
			echo json_encode( array('permission' => 'false') );
			die();
		}
		
		if ( !empty($_POST) && check_admin_referer( 'trackship_customizer_options_actions', 'trackship_customizer_options_nonce_field' ) ) {

			$customizer_type = isset( $_POST['customizer_type'] ) ? sanitize_text_field( $_POST['customizer_type'] ) : '';
			$status = isset( $_POST['shipmentStatus'] ) ? sanitize_text_field( $_POST['shipmentStatus'] ) : '';
			$settings = $this->shipment_statuses_settings($status);

			foreach ( $settings as $key => $val ) {
				if ( isset( $val['type'] ) && 'textarea' == $val['type'] ) {
					$option_data = get_option( $val['option_name'], array() );
					$option_data[$key] = htmlentities( wp_unslash( $_POST[$key] ) );
					update_option( $val['option_name'], $option_data );
				} elseif ( isset( $val['option_type'] ) && 'key' == $val['option_type'] ) {
					update_option( $val['option_name'], wc_clean( $_POST[ $val['option_name'] ] ) );
				} elseif ( isset( $val['option_type'] ) && 'array' == $val['option_type'] ) {
					$option_data = get_option( $val['option_name'], array() );
					$option_data[$key] = wc_clean( wp_unslash( $_POST[$key] ) );
					update_option( $val['option_name'], $option_data );
				}
			}
			echo json_encode( array('success' => 'true' ) );
			die();
		}
	}
}

/**
 * Returns an instance of WC_Trackship_Customizer.
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 * @return WC_Trackship_Customizer
*/
function trackship_admin_customizer() {
	static $instance;

	if ( ! isset( $instance ) ) {		
		$instance = new TS4WC_Admin_Customizer();
	}

	return $instance;
}

/**
 * Register this class globally.
 *
 * Backward compatibility.
*/
trackship_admin_customizer();
