<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TS4WC_Admin_Customizer {

	public $defaults;

	/**
	 * Get the class instance
	 *
	 * @since 1.2.5
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
	 * @since 1.2.5
	*/
	public function __construct() {
		$this->init();
	}

	/*
	 * init function
	 *
	 * @since 1.2.5
	*/
	public function init() {
		$this->defaults = $this->wcast_generate_defaults();	
		//adding hooks
		add_action( 'admin_menu', array( $this, 'register_woocommerce_menu' ), 99 );

		//save of settings hook
		add_action( 'wp_ajax_save_trackship_customizer', array( $this, 'customizer_save_trackship_customizer' ) );

		add_action( 'wp_ajax_ts_email_preview', array( $this, 'ts_email_preview_callback' ) );
		
		//load javascript in admin
		add_action('admin_enqueue_scripts', array( $this, 'customizer_enqueue_scripts' ) );
	}

	/*
	 * Admin Menu add function
	 *
	 * @since 1.2.5
	 * WC sub menu 
	*/
	public function register_woocommerce_menu() {
		add_menu_page( 'TrackShip Customizer', 'TrackShip Customizer', 'manage_options', 'trackship_customizer', array( $this, 'settingsPage' ) );
	}

	/*
	 * callback for settingsPage
	 *
	 * @since 1.2.5
	*/
	public function settingsPage() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field($_GET['page']) : '' ;
		if ( 'trackship_customizer' != $page ) {
			return;
		}

		$type = isset( $_GET['type'] ) ? sanitize_text_field($_GET['type']) : 'tracking_page';
		$shipmentStatus = isset( $_GET['status'] ) ? sanitize_text_field($_GET['status']) : 'in_transit' ;
		$iframe_url = 'shipment_email' == $type ? $this->get_email_preview_url( $shipmentStatus ) : $this->get_tracking_preview_url( $shipmentStatus ) ;
		$shipment_status = $this->shipment_status();
		?>
		<style type="text/css">
			#wpcontent, #wpbody-content, .wp-toolbar {margin: 0 !important;padding: 0 !important;}
			#adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter, .notice, div.error, div.updated, div#query-monitor-main, .wpml-ls-statics-footer.wpml-ls.wpml-ls-legacy-list-horizontal { display: none !important; }
		</style>
		<script type="text/javascript" id="zoremmail-onload">
			jQuery(document).ready( function() {
				jQuery('#adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter, div#query-monitor-main, #cookie-law-info-bar').remove();
			});
		</script>
		<section class="zoremmail-layout zoremmail-layout-has-sider">
			<form method="post" id="zoremmail_email_options" class="zoremmail_email_options" style="display: contents;">
				<section class="zoremmail-layout zoremmail-layout-has-content zoremmail-layout-sider">
					<aside class="zoremmail-layout-slider-header">
						<button type="button" class="wordpress-to-back" tabindex="0">
							<?php $back_link = 'shipment_email' == $type ? admin_url( 'admin.php?page=trackship-for-woocommerce&tab=notifications' ) : admin_url( 'admin.php?page=trackship-for-woocommerce' ); ?>
							<a class="zoremmail-back-wordpress-link" href="<?php echo esc_html( $back_link ); ?>"><span class="zoremmail-back-wordpress-title dashicons dashicons-no-alt"></span></a>
						</button>
						<span class="wcts-save-content" style="float: right;">
							<button name="save" class="button-primary button-trackship btn_large woocommerce-save-button" type="submit" value="Saved" disabled><?php esc_html_e( 'Saved', 'trackship-for-woocommerce' ); ?></button>
							<?php wp_nonce_field( 'trackship_customizer_options_actions', 'trackship_customizer_options_nonce_field' ); ?>
							<input type="hidden" name="action" value="save_trackship_customizer">
						</span>
					</aside>
					<aside class="zoremmail-layout-slider-content">
						<div class="zoremmail-layout-sider-container">
							<?php $this->get_html( $this->shipment_statuses_settings( $shipmentStatus ) ); ?>
						</div>
					</aside>
					<aside class="zoremmail-layout-content-collapse">
						<div class="zoremmail-layout-content-media">
							<a data-width="600px" data-iframe-width="100%" class="last-checked"><span class="dashicons dashicons-desktop"></span></a>
							<a data-width="600px" data-iframe-width="610px"><span class="dashicons dashicons-tablet"></span></a>
							<a data-width="400px" data-iframe-width="410px"><span class="dashicons dashicons-smartphone"></span></a>
						</div>
					</aside>
				</section>
				<section class="zoremmail-layout zoremmail-layout-has-content">
					<div class="zoremmail-layout-content-header">
						<div class="header-panel options-content">
							<input type="hidden" name="customizer_type" id="customizer_type" value="<?php echo esc_html( $type ); ?>">
							<?php
							/*<span class="header_shipment_status">
								<select name="shipmentStatus" id="shipmentStatus" class="select">
									<?php foreach( $shipment_status as $slug => $status) { ?>
										<option value="<?php echo esc_html($slug); ?>" <?php echo $shipmentStatus == $slug ? 'selected' : ''; ?>><?php echo esc_html($status); ?></option>
									<?php } ?>
								</select>
							</span> */
							?>
							<?php $preview_id = get_option( 'email_preview', 'mockup' ); ?>
							<span class="header_mockup_order" style="padding: 0 17px;">
								<select name="email_preview" id="email_preview" class="select">
									<?php foreach ( $this->get_order_ids() as $key => $label ) { ?>
										<option value="<?php echo esc_html( $key ); ?>" <?php echo $preview_id == $key ? 'selected' : ''; ?>><?php echo esc_html( $label ); ?></option>
									<?php } ?>
								</select>
								<span class="tgl-btn-parent" style="margin: 20px;float: right;">
									<?php foreach ( $shipment_status as $key => $value ) { ?>
										<span class="tgl_<?php esc_attr_e( $key ); ?>" <?php echo $shipmentStatus == $key ? '' : 'style="display:none;"'; ?>>
											<?php $id = 'email_settings[' . $key . '][enable]'; ?>
											<?php $enable_email = get_trackship_email_settings( $key, 'enable' ); ?>
											<label style="vertical-align: middle;" for="<?php esc_attr_e( $id ); ?>"><?php esc_html_e( 'Enable email', 'trackship-for-woocommerce' ); ?></label>
											<input type="hidden" name="<?php esc_attr_e( $id ); ?>" value="0">
											<input type="checkbox" id="<?php esc_attr_e( $id ); ?>" name="<?php esc_attr_e( $id ); ?>" class="tgl tgl-flat" <?php echo $enable_email ? 'checked' : ''; ?> value="1">
											<label style="vertical-align: middle;" class="tgl-btn" for="<?php esc_attr_e( $id ); ?>"></label>
										</span>
									<?php } ?>
								</span>
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
			<div class="pending_color_event"></div>
			<div class="pending_change_event"></div>
			<div class="pending_keyup_event"></div>
		</section>
		<?php
	}

	/*
	* Add admin javascript
	*
	* @since 1.2.5
	*/	
	public function customizer_enqueue_scripts() {
		
		$page = isset( $_GET['page'] ) ? sanitize_text_field($_GET['page']) : '' ;
		
		// Add condition for css & js include for admin page 
		if ( 'trackship_customizer' != $page ) {
			return;
		}
		
		wp_register_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
		
		// Add tiptip js and css file		
		wp_enqueue_style( 'trackship-customizer', plugin_dir_url(__FILE__) . 'assets/customizer.css', array(), time() );
		wp_enqueue_script( 'trackship-customizer', plugin_dir_url(__FILE__) . 'assets/customizer.js', array( 'jquery', 'wp-util', 'wp-color-picker','jquery-tiptip' ), time(), true );

		wp_localize_script('trackship-customizer', 'trackship_customizer', array(
			'site_title'			=> get_bloginfo( 'name' ),
			'order_number'			=> 1,
			'customer_first_name'	=> 'Sherlock',
			'customer_last_name'	=> 'Holmes',
			'customer_company_name' => 'Detectives Ltd.',
			'customer_username'		=> 'sher_lock',
			'customer_email'		=> 'sherlock@holmes.co.uk',
			'est_delivery_date'		=> '2021-07-30 15:28:02',
			'email_iframe_url'		=> add_query_arg( array( 'action' => 'ts_email_preview', 'nonce' => wp_create_nonce( 'ts_email') ), admin_url( 'admin-ajax.php' ) ),
			'tracking_iframe_url'	=> add_query_arg( array( 'action' => 'preview_tracking_page' ), home_url( '' ) ),
			'form_iframe_url'		=> $this->get_tracking_form_preview_url(),
			'user_plan'				=> get_option( 'user_plan' ),
			'unsubscribe'			=> get_trackship_settings( 'enable_email_widget' ),
		));
	}

	public function shipment_status () {
		return array(
			'in_transit'			=> esc_html__( 'In Transit', 'trackship-for-woocommerce' ),
			'available_for_pickup'	=> esc_html__( 'Available For Pickup', 'trackship-for-woocommerce' ),
			'out_for_delivery'		=> esc_html__( 'Out For Delivery', 'trackship-for-woocommerce' ),
			'failure'				=> esc_html__( 'Delivery Failure', 'trackship-for-woocommerce' ),
			'on_hold'				=> esc_html__( 'On Hold', 'trackship-for-woocommerce' ),
			'exception'				=> esc_html__( 'Exception', 'trackship-for-woocommerce' ),
			'return_to_sender'		=> esc_html__( 'Return To Sender', 'trackship-for-woocommerce' ),
			'delivered'				=> esc_html__( 'Delivered', 'trackship-for-woocommerce' ),
			'pickup_reminder'		=> esc_html__( 'Pickup Reminder', 'trackship-for-woocommerce' ),
		);
	}

	/**
	 * Code for initialize default value for customizer
	*/	
	public function wcast_generate_defaults() {
		$customizer_defaults = array(
			'tracking_page_type'			=> 'modern',
			'ts_tracking_page_layout'		=> 't_layout_1',
			'wc_ts_border_color'			=> '#cccccc',
			'wc_ts_border_radius'			=> 0,
			'wc_ts_bg_color'				=> '#fafafa',
			'hide_provider_image'			=> 0,
			'ts_link_to_carrier'			=> 1,
			'ts_tracking_events'			=> 2,
			'wc_ts_font_color'				=> '#333',
			'wc_ts_link_color'				=> '#2271b1',
			'ts_hide_from_to'				=> 1,
			'ts_hide_list_mile_tracking'	=> 1,
		);

		return $customizer_defaults;
	}

	/**
	 * Code for initialize default value for customizer
	*/
	public function wcast_shipment_settings_defaults( $status ) {
		_deprecated_function( __FUNCTION__, '1.9.2', 'Use_nothing' );
		return [];
	}

	public function shipment_statuses_settings( $status ) {
		$type = isset( $_GET['type'] ) ? sanitize_text_field($_GET['type']) : 'tracking_page';

		$email_iframe_url = $this->get_email_preview_url( $status );
		$tracking_pageiframe_url = $this->get_tracking_preview_url( $status );

		//Email saved/default vaule
		$border_color = get_trackship_email_settings( 'common_settings', 'border_color' );
		$link_color = get_trackship_email_settings( 'common_settings', 'link_color' );
		$bg_color = get_trackship_email_settings( 'common_settings', 'bg_color' );
		$font_color = get_trackship_email_settings( 'common_settings', 'font_color' );
		$tracking_page_layout = get_trackship_email_settings( 'common_settings', 'tracking_page_layout' );
		$track_button_Text = get_trackship_email_settings( 'common_settings', 'track_button_Text' );
		$track_button_color = get_trackship_email_settings( 'common_settings', 'track_button_color' );
		$track_button_text_color = get_trackship_email_settings( 'common_settings', 'track_button_text_color' );
		$track_button_border_radius = get_trackship_email_settings( 'common_settings', 'track_button_border_radius' );
		$show_trackship_branding = get_trackship_email_settings( 'common_settings', 'show_trackship_branding' );
		$shipping_provider_logo = get_trackship_email_settings( 'common_settings', 'shipping_provider_logo' );

		//Tracking page saved/default vaule
		$wc_ts_bg_color = get_trackship_settings( 'wc_ts_bg_color', $this->defaults['wc_ts_bg_color'] );
		$wc_ts_font_color = get_trackship_settings( 'wc_ts_font_color', $this->defaults['wc_ts_font_color'] );
		$wc_ts_border_color = get_trackship_settings( 'wc_ts_border_color', $this->defaults['wc_ts_border_color'] );
		$wc_ts_border_radius = get_trackship_settings( 'wc_ts_border_radius', $this->defaults['wc_ts_border_radius'] );
		$wc_ts_link_color = get_trackship_settings( 'wc_ts_link_color', $this->defaults['wc_ts_link_color'] );
		$tracking_events = get_trackship_settings( 'ts_tracking_events', $this->defaults['ts_tracking_events'] );
		$link_to_provider = get_trackship_settings( 'ts_link_to_carrier', $this->defaults['ts_link_to_carrier'] );
		$hide_provider_image = get_trackship_settings( 'hide_provider_image', $this->defaults['hide_provider_image'] );
		$ts_tracking_page_layout = get_trackship_settings( 'ts_tracking_page_layout', $this->defaults['ts_tracking_page_layout'] );
		$tracking_page_type = get_trackship_settings( 'tracking_page_type', $this->defaults['tracking_page_type'] );
		$hide_shipping_from_to = get_trackship_settings( 'ts_hide_from_to', $this->defaults['ts_hide_from_to'] );
		$hide_last_mile = get_trackship_settings( 'ts_hide_list_mile_tracking', $this->defaults['ts_hide_list_mile_tracking'] );
		$shipped_product_label = get_trackship_email_settings( 'common_settings', 'shipped_product_label' );
		$shipping_address_label = get_trackship_email_settings( 'common_settings', 'shipping_address_label' );

		// Tracking form saved/default vaule
		$form_tab_view = get_trackship_settings( 'form_tab_view' );
		$form_button_Text = get_trackship_settings( 'form_button_Text' );
		$form_button_color = get_trackship_settings( 'form_button_color' );
		$form_button_text_color = get_trackship_settings( 'form_button_text_color');
		$form_button_border_radius = get_trackship_settings( 'form_button_border_radius');

		$settings = array(

			// MAIN PANELS
			// Email Notifications main panel
			'email_notifications'	=> array(
				'id'	=> 'email_notifications',
				'class' => 'shipment_email_panel',
				'title'	=> esc_html__( 'Email Notifications', 'trackship-for-woocommerce' ),
				'label' => esc_html__( 'Email Notifications', 'trackship-for-woocommerce' ),
				'type'	=> 'panel',
				'iframe_url' => $email_iframe_url,
				'show'	=> true,
			),
			// Tracking Page main panel
			'tracking_page'	=> array(
				'id'	=> 'tracking_page',
				'class' => 'tracking_page_panel',
				'title'	=> esc_html__( 'Tracking Page Widget', 'trackship-for-woocommerce' ),
				'label'	=> esc_html__( 'Tracking Page Widget', 'trackship-for-woocommerce' ),
				'type'	=> 'panel',
				'iframe_url' => $tracking_pageiframe_url,
				'show'	=> true,
			),
			
			// SUB-PANELS
			// Email Content sub panel : Back
			'back_section1' => array(
				'id'		=> 'email_notifications',
				'title'		=> esc_html__( 'Email Content', 'trackship-for-woocommerce' ),
				'type'		=> 'sub-panel-heading',
				'parent'	=> 'email_notifications',
				'show'		=> true,
				'class'		=> 'sub_options_panel',
			),
			// Tracking Page sub panel : Back
			'back_section3' => array(
				'id'		=> 'tracking_page',
				'title'		=> esc_html__( 'Tracking Page', 'trackship-for-woocommerce' ),
				'type'		=> 'sub-panel-heading',
				'parent'	=> 'tracking_page',
				'show'		=> true,
				'class'		=> 'sub_options_panel',
			),

			// Email Notifications sub panel
			'email_content' => array(
				'id'	=> 'email_content',
				'title'	=> esc_html__( 'Content Type & Text', 'trackship-for-woocommerce' ),
				'type'	=> 'sub-panel',
				'parent'=> 'email_notifications',
				'show'	=> true,
				'class' => 'sub_options_panel',
			),
			// Email Notifications sub panel
			'tracking_widget' => array(
				'id'	=> 'tracking_widget',
				'title'	=> esc_html__( 'Tracking Widget', 'trackship-for-woocommerce' ),
				'type'	=> 'sub-panel',
				'parent'=> 'email_notifications',
				'show'	=> true,
				'class' => 'sub_options_panel',
			),
			// Email Notifications sub panel
			'content_display' => array(
				'id'	=> 'content_display',
				'title'	=> esc_html__( 'Display Options', 'trackship-for-woocommerce' ),
				'type'	=> 'sub-panel',
				'parent'=> 'email_notifications',
				'show'	=> true,
				'class' => 'sub_options_panel',
			),

			// Tracking Widget sub panel
			'widget_style' => array(
				'id'	=> 'widget_style',
				'title'	=> esc_html__( 'Style & Colors', 'trackship-for-woocommerce' ),
				'type'	=> 'sub-panel',
				'parent'=> 'tracking_page',
				'show'	=> true,
				'class' => 'sub_options_panel',
			),
			// Tracking Widget sub panel
			'widget_layout' => array(
				'id'	=> 'widget_layout',
				'title'	=> esc_html__( 'Display Options', 'trackship-for-woocommerce' ),
				'type'	=> 'sub-panel',
				'parent'=> 'tracking_page',
				'show'	=> true,
				'class' => 'sub_options_panel',
			),
			// Tracking Widget sub panel
			'form_content' => array(
				'id'	=> 'form_content',
				'title'	=> esc_html__( 'Widget Form', 'trackship-for-woocommerce' ),
				'type'	=> 'sub-panel',
				'parent'=> 'tracking_page',
				'show'	=> true,
				'class' => 'sub_options_panel',
			),

			//section
			'heading3'	=> array(
				'id'	=> 'tracking_widget',
				'class' => '',
				'title'	=> esc_html__( 'Tracking Widget', 'trackship-for-woocommerce' ),
				'type'	=> 'section',
				'parent'=> 'tracking_widget',
				'show'	=> true,
			),
			'email_settings[common_settings][tracking_page_layout]' => array(
				'title'		=> __( 'Tracker type', 'trackship-for-woocommerce' ),
				'type'		=> 'select',
				'id_name'	=> 'tracking_page_layout',
				'option_name'=> 'trackship_email_settings',
				'option_type'=> 'array',
				'default'	=> $tracking_page_layout,
				'show'		=> true,
				'options'	=> array(
					't_layout_2' => __( 'Progress bar', 'trackship-for-woocommerce' ),
					't_layout_1' => __( 'Icons', 'trackship-for-woocommerce' ),
					't_layout_3' => __( 'Single icon', 'trackship-for-woocommerce' ),
				)
			),
			'email_settings[common_settings][bg_color]' => array(
				'title'		=> esc_html__( 'Widget background color', 'trackship-for-woocommerce' ),
				'type'		=> 'color',
				'id_name'	=> 'bg_color',
				'option_name'=> 'trackship_email_settings',
				'option_type'=> 'array',
				'default'	=> $bg_color,
				'show'		=> true,
				'class'		=> 'colorset',
			),
			'email_settings[common_settings][font_color]' => array(
				'title'		=> esc_html__( 'Widget font color', 'trackship-for-woocommerce' ),
				'id_name'	=> 'font_color',
				'type'		=> 'color',
				'option_name'=> 'trackship_email_settings',
				'option_type'=> 'array',
				'default'	=> $font_color,
				'show'		=> true,
				'class'		=> 'colorset',
			),
			'email_settings[common_settings][border_color]' => array(
				'title'		=> esc_html__( 'Widget border color', 'trackship-for-woocommerce' ),
				'id_name'	=> 'border_color',
				'type'		=> 'color',
				'option_name'=> 'trackship_email_settings',
				'option_type'=> 'array',
				'default'	=> $border_color,
				'show'		=> true,
				'class'		=> 'colorset',
			),
			'email_settings[common_settings][link_color]' => array(
				'title'		=> esc_html__( 'Links color', 'trackship-for-woocommerce' ),
				'type'		=> 'color',
				'id_name'	=> 'link_color',
				'option_name'=> 'trackship_email_settings',
				'option_type'=> 'array',
				'default'	=> $link_color,
				'show'		=> true,
				'class'		=> 'colorset',
			),
			// 'heading4'	=> array(
			// 	'id'	=> 'tracking_button',
			// 	'title'	=> esc_html__( 'Track Button', 'trackship-for-woocommerce' ),
			// 	'type'	=> 'section',
			// 	'parent'=> 'tracking_button',
			// 	'show'	=> true,
			// ),
			'email_settings[common_settings][track_button_Text]' => array(
				'title'		=> esc_html__( 'Track button text', 'trackship-for-woocommerce' ),
				'default'	=> $track_button_Text,
				'placeholder' => $track_button_Text,
				'type'		=> 'text',
				'id_name'	=> 'track_button_Text',
				'option_name'=> 'trackship_email_settings',
				'option_type'=> 'array',
				'show'		=> true,
				'class' 	=> 'track_button_Text',
			),
			'email_settings[common_settings][track_button_color]' => array(
				'title'		=> esc_html__( 'Button color', 'trackship-for-woocommerce' ),
				'type'		=> 'color',
				'id_name'	=> 'track_button_color',
				'option_name'=> 'trackship_email_settings',
				'option_type'=> 'array',
				'default'	=> $track_button_color,
				'show'		=> true,
				'class'		=> 'colorset',
			),
			'email_settings[common_settings][track_button_text_color]' => array(
				'title'		=> esc_html__( 'Button font color', 'trackship-for-woocommerce' ),
				'type'		=> 'color',
				'id_name'	=> 'track_button_text_color',
				'option_name'=> 'trackship_email_settings',
				'option_type'=> 'array',
				'default'	=> $track_button_text_color,
				'show'		=> true,
				'class'		=> 'colorset',
			),
			'email_settings[common_settings][track_button_border_radius]' => array(
				'title'		=> esc_html__( 'Track Button radius', 'trackship-for-woocommerce' ),
				'type'		=> 'range',
				'id_name'	=> 'track_button_border_radius',
				'option_name'=> 'trackship_email_settings',
				'option_type'=> 'array',
				'default'	=> $track_button_border_radius,
				'show'		=> true,
				'min'		=> 0,
				'max'		=> 10,
			),
			'email_settings[common_settings][shipping_provider_logo]' => array(
				'title'		=> esc_html__( 'Display Shipping provider logo', 'trackship-for-woocommerce' ),
				'default'	=> $shipping_provider_logo,
				'type'		=> 'checkbox',
				'id_name'	=> 'shipping_provider_logo',
				'option_name'=> 'trackship_email_settings',
				'option_type'=> 'array',
				'show'		=> true,
				'class'		=> 'ts4wc_provider_logo',
			),
			// Tracking Page Settings
			'heading5'	=> array(
				'id'	=> 'widget_style',
				'class' => 'tracking_page_first_section',
				'title'	=> esc_html__( 'Widget Style', 'trackship-for-woocommerce' ),
				'type'	=> 'section',
				'parent'=> 'widget_style',
				'show'	=> true,
			),
			'trackship_settings[wc_ts_bg_color]' => array(
				'title'		=> esc_html__( 'Widget Background color', 'trackship-for-woocommerce' ),
				'type'		=> 'color',
				'default'	=> $wc_ts_bg_color,
				'show'		=> true,
				'class'		=> 'colorset',
				'id_name'	=> 'wc_ts_bg_color',
				'option_name'=> 'trackship_settings',
				'option_type'=> 'array',
			),
			'trackship_settings[wc_ts_font_color]' => array(
				'title'		=> esc_html__( 'Font color', 'trackship-for-woocommerce' ),
				'type'		=> 'color',
				'default'	=> $wc_ts_font_color,
				'show'		=> true,
				'class'		=> 'colorset',
				'id_name'	=> 'wc_ts_font_color',
				'option_name'=> 'trackship_settings',
				'option_type'=> 'array',
			),
			'trackship_settings[wc_ts_border_color]' => array(
				'title'		=> esc_html__( 'Widget Border color', 'trackship-for-woocommerce' ),
				'type'		=> 'color',
				'default'	=> $wc_ts_border_color,
				'show'		=> true,
				'class'		=> 'colorset',
				'id_name'	=> 'wc_ts_border_color',
				'option_name'=> 'trackship_settings',
				'option_type'=> 'array',
			),
			'trackship_settings[wc_ts_border_radius]' => array(
				'title'		=> esc_html__( 'Widget Border radius', 'trackship-for-woocommerce' ),
				'type'		=> 'range',
				'default'	=> $wc_ts_border_radius,
				'show'		=> true,
				'min'		=> 0,
				'max'		=> 10,
				'id_name'	=> 'wc_ts_border_radius',
				'option_name'=> 'trackship_settings',
				'option_type'=> 'array',
			),
			'trackship_settings[wc_ts_link_color]' => array(
				'title'		=> esc_html__( 'Links color', 'trackship-for-woocommerce' ),
				'type'		=> 'color',
				'default'	=> $wc_ts_link_color,
				'show'		=> true,
				'class'		=> 'colorset',
				'id_name'	=> 'wc_ts_link_color',
				'option_name'=> 'trackship_settings',
				'option_type'=> 'array',
			),
			'trackship_settings[form_button_color]' => array(
				'title'		=> esc_html__( 'Button color', 'trackship-for-woocommerce' ),
				'type'		=> 'color',
				'option_name'=> 'trackship_settings',
				'option_type'=> 'array',
				'id_name'	=> 'form_button_color',
				'default'	=> $form_button_color,
				'show'		=> true,
				'class'		=> 'colorset',
			),
			'trackship_settings[form_button_text_color]' => array(
				'title'		=> esc_html__( 'Button font color', 'trackship-for-woocommerce' ),
				'type'		=> 'color',
				'option_name'=> 'trackship_settings',
				'option_type'=> 'array',
				'id_name'	=> 'form_button_text_color',
				'default'	=> $form_button_text_color,
				'show'		=> true,
				'class'		=> 'colorset',
			),
			'trackship_settings[form_button_border_radius]' => array(
				'title'		=> esc_html__( 'Button radius', 'trackship-for-woocommerce' ),
				'type'		=> 'range',
				'option_name'=> 'trackship_settings',
				'option_type'=> 'array',
				'id_name'	=> 'form_button_border_radius',
				'default'	=> $form_button_border_radius,
				'show'		=> true,
				'min'		=> 0,
				'max'		=> 10,
			),
			'heading6'		=> array(
				'id'		=> 'widget_layout',
				'title'		=> esc_html__( 'Widget Layout', 'trackship-for-woocommerce' ),
				'type'		=> 'section',
				'parent'	=> 'widget_layout',
				'show'		=> true,
			),
			'trackship_settings[tracking_page_type]' => array(
				'title'		=> __( 'Tracking Page', 'trackship-for-woocommerce' ),
				'type'		=> 'select',
				'default'	=> $tracking_page_type,
				'show'		=> true,
				'options'	=> array(
					'classic' => __( 'Classic', 'trackship-for-woocommerce' ),
					'modern' => __( 'Modern', 'trackship-for-woocommerce' ),
				),
				'id_name'	=> 'tracking_page_type',
				'option_name'=> 'trackship_settings',
				'option_type'=> 'array',
			),
			'trackship_settings[ts_tracking_events]' => array(
				'title'		=> esc_html__( 'Tracking event display', 'trackship-for-woocommerce' ),
				'type'		=> 'select',
				'default'	=> $tracking_events,
				'show'		=> true,
				'options'	=> array(
					0 => __( 'Show all events', 'trackship-for-woocommerce' ),
					1 => __( 'Hide tracking events', 'trackship-for-woocommerce' ),
					2 => __( 'Show last events & expand', 'trackship-for-woocommerce' ),
				),
				'id_name'	=> 'ts_tracking_events',
				'option_name'=> 'trackship_settings',
				'option_type'=> 'array',
				'class'	=> 'ts_tracking_events',
			),
			'trackship_settings[ts_tracking_page_layout]' => array(
				'title'		=> __( 'Tracker type', 'trackship-for-woocommerce' ),
				'type'		=> 'select',
				'default'	=> $ts_tracking_page_layout,
				'show'		=> true,
				'options'	=> array(
					't_layout_2' => __( 'Progress bar', 'trackship-for-woocommerce' ),
					't_layout_1' => __( 'Icons', 'trackship-for-woocommerce' ),
					't_layout_3' => __( 'Single icon', 'trackship-for-woocommerce' ),
				),
				'id_name'	=> 'ts_tracking_page_layout',
				'option_name'=> 'trackship_settings',
				'option_type'=> 'array',
				'class'	=> 'ts_tracking_page_layout',
			),
			'trackship_settings[ts_link_to_carrier]' => array(
				'title'		=> __( 'Enable tracking # link to carrier', 'trackship-for-woocommerce' ),
				'default'	=> $link_to_provider,
				'type'		=> 'checkbox',
				'show'		=> true,
				'id_name'	=> 'ts_link_to_carrier',
				'option_name'=> 'trackship_settings',
				'option_type'=> 'array',
			),
			'trackship_settings[hide_provider_image]' => array(
				'title'		=> __( 'Hide the shipping provider logo', 'trackship-for-woocommerce' ),
				'default'	=> $hide_provider_image,
				'type'		=> 'checkbox',
				'show'		=> true,
				'id_name'	=> 'hide_provider_image',
				'option_name'=> 'trackship_settings',
				'option_type'=> 'array',
			),
			'trackship_settings[ts_hide_from_to]' => array(
				'title'		=> __( 'Hide shipping from-to', 'trackship-for-woocommerce' ),
				'default'	=> $hide_shipping_from_to,
				'type'		=> 'checkbox',
				'show'		=> true,
				'id_name'	=> 'ts_hide_from_to',
				'option_name'=> 'trackship_settings',
				'option_type'=> 'array',
			),
			'trackship_settings[ts_hide_list_mile_tracking]' => array(
				'title'		=> __( 'Hide delivery tracking number', 'trackship-for-woocommerce' ),
				'default'	=> $hide_last_mile,
				'type'		=> 'checkbox',
				'tip-tip'	=> __( 'The delivery tracking number will display if the shipment is getting a different tracking number at the destination country from the local postal service (i.e 4PX -> USPS)', 'trackship-for-woocommerce' ),
				'show'		=> true,
				'id_name'	=> 'ts_hide_list_mile_tracking',
				'option_name'=> 'trackship_settings',
				'option_type'=> 'array',
			),
			'email_settings[common_settings][show_trackship_branding]' => array(
				'title'		=> __( 'Display TrackShip branding', 'trackship-for-woocommerce' ),
				'default'	=> $show_trackship_branding,
				'type'		=> 'checkbox',
				'show'		=> true,
				'id_name'	=> 'show_trackship_branding',
				'option_name'=> 'trackship_email_settings',
				'option_type'=> 'array',
				'required' 	=> 'pro',
				'plan'		=> in_array( get_option( 'user_plan' ), array( 'Free 50', 'No active plan', 'Trial Ended' ) ),
			),
			// Tracking widget form sections from below
			'heading7'	=> array(
				'id'	=> 'form_content',
				'title'	=> esc_html__( 'General options', 'trackship-for-woocommerce' ),
				'type'	=> 'section',
				'parent'=> 'form_content',
				'show'	=> true,
			),
			'trackship_settings[form_tab_view]' => array(
				'title'		=> __( 'Tabs display', 'trackship-for-woocommerce' ),
				'type'		=> 'select',
				'default'	=> $form_tab_view,
				'show'		=> true,
				'options'	=> array(
					'both'				=> __( 'Show both order details and Tracking number', 'trackship-for-woocommerce' ),
					'order_details'		=> __( 'show only Order details', 'trackship-for-woocommerce' ),
					'tracking_details'	=> __( 'show only Tracking details', 'trackship-for-woocommerce' ),
				),
				'id_name'	=> 'form_tab_view',
				'option_name'=> 'trackship_settings',
				'option_type'=> 'array',
			),
			'trackship_settings[form_button_Text]' => array(
				'title'		=> esc_html__( 'Button text', 'trackship-for-woocommerce' ),
				'default'	=> $form_button_Text,
				'placeholder' => 'Track Order',
				'type'		=> 'text',
				'id_name'	=> 'form_button_Text',
				'option_name'=> 'trackship_settings',
				'option_type'=> 'array',
				'show'		=> true,
				'class'		=> 'form_button_Text',
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
			'pickupreminder' => 'pickup_reminder',
		);

		$settings[ 'heading1' ] = array(
			'id'	=> 'email_settings',
			'class' => 'email_content_first_section ',
			'title'	=> esc_html__( 'Email Content', 'trackship-for-woocommerce' ),
			'type'	=> 'section',
			'parent'=> 'email_content',
			'show'	=> true,
		);

		$settings[ 'email_preview' ] = array(
			// for add in section 
			// 'title'		=> __( 'Mockup order', 'trackship-for-woocommerce' ),
			// 'type'		=> 'select',
			// 'default'	=> get_option( 'email_preview', 'mockup' ),
			// 'show'		=> true,
			// 'options'	=> $this->get_order_ids(),
			// 'option_name'=> 'email_preview',
			// 'option_type'=> 'key',

			// only for save mockup 
			'id'	=> 'email_preview',
			'class' => '',
			'label' => '',
			'title'	=> '',
			'type'	=> 'text',
			'option_name'=> 'email_preview',
			'option_type'=> 'key',
			'show'	=> false,
		);

		$settings[ 'shipmentStatus' ] = array(
			'title'		=> __( 'Email type', 'trackship-for-woocommerce' ),
			'type'		=> 'select',
			'default'	=> sanitize_text_field($_GET['status'] ?? 'in_transit'),
			'show'		=> true,
			'id_name'	=> 'shipmentStatus',
			'options'	=> array(
				'in_transit' => __( 'In Transit', 'trackship-for-woocommerce' ),
				'available_for_pickup' => __( 'Available For Pickup', 'trackship-for-woocommerce' ),
				'out_for_delivery' => __( 'Out For Delivery', 'trackship-for-woocommerce' ),
				'failure' => __( 'Delivery Failure', 'trackship-for-woocommerce' ),
				'on_hold' => __( 'On Hold', 'trackship-for-woocommerce' ),
				'exception' => __( 'Exception', 'trackship-for-woocommerce' ),
				'return_to_sender' => __( 'Return To Sender', 'trackship-for-woocommerce' ),
				'delivered' => __( 'Delivered', 'trackship-for-woocommerce' ),
				'pickup_reminder' => __( 'Available for Pickup Reminder', 'trackship-for-woocommerce' ),
			),
		);

		foreach ( $all_statuses as $key => $value ) {
			$settings[ 'email_settings[' . $value . '][enable]' ] = array(
				'type'		=> 'tgl-btn',
				'id_name'	=> $value . '_enable',
				'option_name'=> 'trackship_email_settings',
				'option_type'=> 'array',
				'show'		=> false,
				'default'	=> get_trackship_email_settings( $value, 'enable' ),
				'class'		=> $value . '_sub_menu all_status_submenu',
				'name'		=> $this->shipment_status()[$value]
			);
			$settings[ 'email_settings[' . $value . '][subject]' ] = array(
				'title'		=> esc_html__( 'Email subject', 'trackship-for-woocommerce' ),
				'desc'		=> esc_html__( 'Available variables:', 'trackship-for-woocommerce' ) . ' {site_title}, {order_number}',
				'default'	=> get_trackship_email_settings( $value, 'subject' ),
				'type'		=> 'text',
				'id_name'	=> $value . '_subject',
				'option_name'=> 'trackship_email_settings',
				'option_type'=> 'array',
				'show'		=> true,
				'class'		=> $value . '_sub_menu all_status_submenu',
			);
			$settings[ 'email_settings[' . $value . '][heading]' ] = array(
				'title'	=> esc_html__( 'Email heading', 'trackship-for-woocommerce' ),
				'desc'	=> esc_html__( 'Available variables:', 'trackship-for-woocommerce' ) . ' {site_title}, {order_number}',
				'default'	=> get_trackship_email_settings( $value, 'heading' ),
				'type'	=> 'text',
				'id_name'	=> $value . '_heading',
				'option_name'=> 'trackship_email_settings',
				'option_type'=> 'array',
				'show'	=> true,
				'class'	=> 'heading ' . $value . '_sub_menu all_status_submenu',
			);
			$settings[ 'email_settings[' . $value . '][content]' ] = array(
				'title'		=> esc_html__( 'Email Content', 'trackship-for-woocommerce' ),
				'desc'		=> '',
				'default'	=> get_trackship_email_settings( $value, 'content' ),
				'type'		=> 'textarea',
				'id_name'	=> $value . '_content',
				'option_name'=> 'trackship_email_settings',
				'option_type'=> 'array',
				'show'		=> true,
				'class'		=> 'email_content ' . $value . '_sub_menu all_status_submenu',
			);
			$settings[ 'codeinfoblock ' . $key ] = array(
				'title'		=> esc_html__( 'Available placeholders:', 'trackship-for-woocommerce' ),
				'default'	=> array('{customer_first_name}', '{customer_last_name}', '{site_title}', '{order_number}', '{customer_company_name}', '{customer_username}', '{customer_email}'),
				'type'		=> 'codeinfo',
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
			$settings[ 'email_settings[' . $value . '][show_order_details]' ] = array(
				'title'		=> esc_html__( 'Display shipped products', 'trackship-for-woocommerce' ),
				'default'	=> get_trackship_email_settings( $value, 'show_order_details' ),
				'type'		=> 'checkbox',
				'id_name'	=> $value . '_show_order_details',
				'option_name'=> 'trackship_email_settings',
				'option_type'=> 'array',
				'show'		=> true,
				'class'		=> $value . '_sub_menu all_status_submenu ts4wc_shipped_products',
			);
		}

		$settings[ 'email_settings[common_settings][shipped_product_label]' ] = array(
			'title'		=> esc_html__( 'Shipped products header text', 'trackship-for-woocommerce' ),
			'default'	=> $shipped_product_label,
			'type'		=> 'text',
			'id_name'	=> 'shipped_product_label',
			'option_name'=> 'trackship_email_settings',
			'option_type'=> 'array',
			'show'		=> true,
			'class'		=> 'shipped_product_label',
		);

		foreach ( $all_statuses as $key => $value ) {
			$settings[ 'email_settings[' . $value . '][show_product_image]' ] = array(
				'title'		=> esc_html__( 'Display product image', 'trackship-for-woocommerce' ),
				'default'	=> get_trackship_email_settings( $value, 'show_product_image' ),
				'type'		=> 'checkbox',
				'id_name'	=> $value . '_show_product_image',
				'option_name'=> 'trackship_email_settings',
				'option_type'=> 'array',
				'show'		=> true,
				'class'		=> $value . '_sub_menu all_status_submenu ts4wc_shipped_product_image',
			);
			if ( 'pickup_reminder' != $value ) {
				$settings[ 'email_settings[' . $value . '][show_shipping_address]' ] = array(
					'title'		=> esc_html__( 'Display shipping address', 'trackship-for-woocommerce' ),
					'default'	=> get_trackship_email_settings( $value, 'show_shipping_address' ),
					'type'		=> 'checkbox',
					'id_name'	=> $value . '_show_shipping_address',
					'option_name'=> 'trackship_email_settings',
					'option_type'=> 'array',
					'show'		=> true,
					'class'		=> $value . '_sub_menu all_status_submenu ts4wc_shipping_address',
				);
			}
		}
		$settings[ 'email_settings[common_settings][shipping_address_label]' ] = array(
			'title'		=> esc_html__( 'Shipping address header text', 'trackship-for-woocommerce' ),
			'default'	=> $shipping_address_label,
			'type'		=> 'text',
			'id_name'	=> $value . '_shipping_address_label',
			'option_name'=> 'trackship_email_settings',
			'option_type'=> 'array',
			'show'		=> true,
			'class'		=> 'shipping_address_label',
		);
		$settings[ 'email_settings[pickup_reminder][days]' ] = array(
			'title'		=> esc_html__( 'Set Pickup reminder notifications(in days)', 'trackship-for-woocommerce' ),
			'default'	=> get_trackship_email_settings( $value, 'days' ),
			'type'		=> 'number',
			'min'		=> 0,
			'id_name'	=> 'pickup_reminder_days',
			'option_name'=> 'trackship_email_settings',
			'option_type'=> 'array',
			'show'		=> true,
			'class'		=> 'pickup_reminder_sub_menu all_status_submenu pickup_reminder_days',
		);
		$settings[ 'email_settings[common_settings][email_trackship_branding]' ] = array(
			'title'		=> esc_html__( 'Display TrackShip branding', 'trackship-for-woocommerce' ),
			'default'	=> $show_trackship_branding,
			'type'		=> 'checkbox',
			'id_name'	=> 'email_trackship_branding',
			'option_name'=> 'trackship_email_settings',
			'option_type'=> 'array',
			'show'		=> true,
			'class'		=> '',
			'required' 	=> 'pro',
			'plan'		=> in_array( get_option( 'user_plan' ), array( 'Free 50', 'No active plan', 'Trial Ended' ) ),
		);

		if ( 'tracking_page' == $type ) {
			unset( $settings['email_notifications'] );
		} else {
			unset( $settings['tracking_page'] );
		}
		if ( 'modern' == $tracking_page_type ) {
			unset( $settings['trackship_settings[tracking_page_type]'] );
			unset( $settings['trackship_settings[ts_tracking_events]'] );
			unset( $settings['trackship_settings[ts_tracking_page_layout]'] );
		}
		return $settings;
	}

	/*
	* Get html of fields
	*/
	public function get_html( $arrays ) {
		//echo '<pre>';print_r($arrays);echo '</pre>';
		echo '<ul class="zoremmail-panels">';
		?>
		<div class="customize-section-title">
			<h3>
				<span class="customize-action-default">
					<?php esc_html_e( 'You are customizing', 'trackship-for-woocommerce' ); ?>
				</span>
				<span style="font-weight: 500;"><?php esc_html_e( 'TrackShip', 'trackship-for-woocommerce' ); ?></span>
			</h3>
		</div>
		<?php
		foreach ( (array) $arrays as $id => $array ) {
			
			if ( isset($array['show']) && true != $array['show'] ) {
				continue; 
			}

			if ( isset($array['type']) && 'panel' == $array['type'] ) {
				?>
				<li id="<?php isset($array['id']) ? esc_attr_e($array['id']) : ''; ?>" data-label="<?php isset($array['label']) ? esc_attr_e($array['label']) : ''; ?>" data-iframe_url="<?php isset($array['iframe_url']) ? esc_attr_e($array['iframe_url']) : ''; ?>" class="zoremmail-panel-title <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?>" style="display:none;">
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

			if ( isset($array['type']) && 'sub-panel-heading' == $array['type'] ) {
				?>
				<li data-id="<?php isset($array['parent']) ? esc_attr_e($array['parent']) : ''; ?>" class="zoremmail-sub-panel-heading <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?> <?php isset($array['parent']) ? esc_attr_e($array['parent']) : ''; ?>">
					<div class="customize-section-title">
						<button type="button" class="customize-section-back" tabindex="0">
							<span class="screen-reader-text">Back</span>
						</button>
						<h3>
							<span class="customize-action-default">
								<?php esc_html_e( 'You are customizing', 'trackship-for-woocommerce' ); ?>
							</span>
							<span class="customize-action-changed"></span>
							<span class="sub_heading"><?php esc_html_e( $array['title'] ); ?></span>
						</h3>
					</div>
				</li>
				<?php
			}

			if ( isset($array['type']) && 'sub-panel' == $array['type'] ) {
				?>
				<li id="<?php isset($array['id']) ? esc_attr_e($array['id']) : ''; ?>" data-type="<?php isset($array['parent']) ? esc_html_e($array['parent']) : ''; ?>" data-label="<?php isset($array['title']) ? esc_html_e($array['title']) : ''; ?>" class="zoremmail-sub-panel-title <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?> <?php isset($array['parent']) ? esc_attr_e($array['parent']) : ''; ?>">
					<span><?php isset($array['title']) ? esc_html_e($array['title']) : ''; ?></span>
					<span class="dashicons dashicons-arrow-right-alt2"></span>
				</li>
				<?php
			}
		}
		echo '</ul>';

		foreach ( (array) $arrays as $id => $array ) {

			if ( isset($array['show']) && true != $array['show'] ) {
				continue; 
			}

			if ( isset($array['type']) && 'panel' == $array['type'] ) {
				continue; 
			}
			
			if ( isset($array['type']) && 'sub-panel-heading' == $array['type'] ) {
				continue; 
			}

			if ( isset($array['type']) && 'sub-panel' == $array['type'] ) {
				continue; 
			}
			
			if ( isset($array['type']) && 'section' == $array['type'] ) {
				echo 'heading' != $id ? '</div>' : '';
				?>
				<div data-id="<?php isset($array['parent']) ? esc_attr_e($array['parent']) : ''; ?>" class="zoremmail-menu-submenu-title <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?>">
					<span><?php esc_html_e( $array['title'] ); ?></span>
					<span class="dashicons dashicons-arrow-right-alt2"></span>
				</div>
				<div class="zoremmail-menu-contain">
				<?php
			} else {
				$array_default = $array['default'] ?? '';
				$id_name = $array['id_name'] ?? '';
				?>
				<div class="zoremmail-menu zoremmail-menu-inline zoremmail-menu-sub <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?>">
					<div class="zoremmail-menu-item">
						<div class="<?php esc_attr_e( $array['type'] ); ?>">
							<?php if ( isset($array['title']) && 'checkbox' != $array['type'] ) { ?>
								<div class="menu-sub-title"><?php esc_html_e( $array['title'] ); ?></div>
							<?php } ?>
							<?php if ( isset($array['type']) && 'text' == $array['type'] ) { ?>
								<?php //echo '<pre>';print_r($array);echo '</pre>'; ?>
								<div class="menu-sub-field">
									<input type="text" name="<?php esc_attr_e( $id ); ?>" placeholder="<?php isset($array['placeholder']) ? esc_attr_e($array['placeholder']) : ''; ?>" value="<?php echo esc_html( $array_default ); ?>" class="zoremmail-input <?php esc_html_e($array['type']); ?> <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?>">
									<br>
									<span class="menu-sub-tooltip"><?php isset($array['desc']) ? esc_html_e($array['desc']) : ''; ?></span>
								</div>
							<?php } else if ( isset($array['type']) && 'textarea' == $array['type'] ) { ?>
								<div class="menu-sub-field">
									<textarea id="<?php esc_attr_e( $id_name ); ?>" rows="4" name="<?php esc_attr_e( $id ); ?>" placeholder="<?php isset($array['placeholder']) ? esc_attr_e($array['placeholder']) : ''; ?>" class="zoremmail-input <?php esc_html_e($array['type']); ?> <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?>"><?php echo esc_html( $array_default ); ?></textarea>
									<br>
									<span class="menu-sub-tooltip"><?php isset($array['desc']) ? esc_html_e($array['desc']) : ''; ?></span>
								</div>
							<?php } else if ( isset($array['type']) && 'codeinfo' == $array['type'] ) { ?>
								<div class="menu-sub-field">
									<span class="menu-sub-codeinfo <?php esc_html_e($array['type']); ?>">
										<?php
										foreach ( $array['default'] as $place_key => $placeholder ) {
											echo '<span class="email_placeholder" data-clipboard-text="' . esc_html($placeholder) . '">' . esc_html($placeholder) . '</span>';
										}
										?>
									</span>
								</div>
							<?php } else if ( isset($array['type']) && 'select' == $array['type'] ) { ?>
								<div class="menu-sub-field">
									<select name="<?php esc_attr_e( $id ); ?>" id="<?php esc_attr_e( $id_name ); ?>" class="zoremmail-input <?php esc_html_e($array['type']); ?> <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?>">
										<?php foreach ( (array) $array['options'] as $key => $val ) { ?>
											<option value="<?php echo esc_html($key); ?>" <?php echo $array_default == $key ? 'selected' : ''; ?>><?php echo esc_html($val); ?></option>
										<?php } ?>
									</select>
									<br>
									<span class="menu-sub-tooltip"><?php isset($array['desc']) ? esc_html_e($array['desc']) : ''; ?></span>
								</div>
							<?php } else if ( isset($array['type']) && 'color' == $array['type'] ) { ?>
								<div class="menu-sub-field">
									<input type="text" name="<?php esc_attr_e( $id ); ?>" id="<?php esc_attr_e( $id_name ); ?>" class="input-text regular-input zoremmail-input <?php esc_html_e($array['type']); ?> <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?>" value="<?php echo esc_html( $array_default ); ?>" placeholder="<?php isset($array['placeholder']) ? esc_attr_e($array['placeholder']) : ''; ?>">
									<br>
									<span class="menu-sub-tooltip"><?php isset($array['desc']) ? esc_html_e($array['desc']) : ''; ?></span>
								</div>
							<?php } else if ( isset($array['type']) && 'checkbox' == $array['type'] ) { ?>
								<?php //echo '<pre>';print_r($array);echo '</pre>'; ?>
								<div class="menu-sub-field">
									<label class="menu-sub-title <?php echo ( isset($array['required']) && 'pro' == $array['required'] ) && ( isset($array['plan']) && $array['plan'] ) ? 'free_plan' : ''; ?>">
										<input type="hidden" name="<?php esc_attr_e( $id ); ?>" value="0"/>
										<input type="checkbox" id="<?php esc_attr_e( $id_name ); ?>" name="<?php esc_attr_e( $id ); ?>" class="zoremmail-checkbox <?php isset($array['class']) ? esc_attr_e($array['class']) : ''; ?>" value="1" <?php echo $array_default ? 'checked' : ''; ?>/>
										<?php esc_html_e( $array['title'] ); ?>
										<?php if ( isset($array['tip-tip'] ) ) { ?>
											<span class="woocommerce-help-tip tipTip" title="<?php echo esc_html( $array['tip-tip'] ); ?>"></span>
										<?php } ?>
									</label>
									<?php if ( ( isset($array['required']) && 'pro' == $array['required'] ) && ( isset($array['plan']) && $array['plan'] ) ) { ?>
										<a class="updgrade_feature" href="https://trackship.com/pricing/" target="_blank">
											<span class="dashicons dashicons-arrow-up-alt"></span> Unlock Feature
										</a>
									<?php } ?>
								</div>
							<?php } else if ( isset($array['type']) && 'radio_butoon' == $array['type'] ) { ?>
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
							<?php } else if ( isset($array['type']) && 'tgl-btn' == $array['type'] ) { ?>
								<div class="menu-sub-field">
									<?php //echo $array_default; ?>
									<label class="menu-sub-title">
										<span class="tgl-btn-parent">
											<input type="hidden" name="<?php esc_attr_e( $id ); ?>" value="0">
											<input type="checkbox" id="<?php esc_attr_e( $id_name ); ?>" name="<?php esc_attr_e( $id ); ?>" class="tgl tgl-flat" <?php echo $array_default ? 'checked' : ''; ?> value="1">
											<label class="tgl-btn" for="<?php esc_attr_e( $id_name ); ?>"></label>
										</span>
										<?php /* translators: %s: search for a tag */ ?>
										<label for="<?php esc_attr_e( $id ); ?>" class="shipment_email_label"><?php printf( esc_html__( 'Enable %1$s email', 'trackship-for-woocommerce' ), esc_html($array['name']) ); ?></label>
									</label>
								</div>
							<?php } else if ( isset($array['type']) && 'range' == $array['type'] ) { ?>
								<div class="menu-sub-field">
									<label class="menu-sub-title">
										<input type="range" class="zoremmail-range" id="<?php esc_attr_e( $id_name ); ?>" name="<?php esc_attr_e( $id ); ?>" value="<?php echo esc_html( $array_default ); ?>" min="<?php esc_html_e( $array['min'] ); ?>" max="<?php esc_html_e( $array['max'] ); ?>" oninput="this.nextElementSibling.value = this.value">
										<input style="width:50px;" class="<?php esc_attr_e( $id_name ); ?> slider__value" type="number" min="<?php esc_attr_e( $array['min'] ); ?>" max="<?php esc_attr_e( $array['max'] ); ?>" value="<?php echo esc_html( $array_default ); ?>">
									</label>
								</div>
							<?php } else if ( isset($array['type']) && 'number' == $array['type'] ) { ?>
								<div class="menu-sub-field">
									<label class="menu-sub-title">
										<input style="width:50px;" name="<?php esc_attr_e( $id ); ?>" class="slider__value" type="number" min="<?php esc_attr_e( $array['min'] ); ?>" value="<?php echo esc_html( $array_default ); ?>">
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

		global $wpdb;
		$ids = $wpdb->get_results( "SELECT order_id FROM {$wpdb->prefix}trackship_shipment GROUP BY order_id ORDER BY order_id DESC LIMIT 20", 'ARRAY_A' );
		
		foreach ( $ids as $value ) {
			$order_id = $value['order_id'];
			$order = wc_get_order($order_id);
			if ( $order ) {
				$order_array[ $order_id ] = $order_id . ' - ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			}
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
			'action'	=> 'ts_email_preview',
			'status'	=> $status,
			'nonce'		=> wp_create_nonce('ts_email')
		), admin_url( 'admin-ajax.php' ) );
	}

	/**
	 * Get Tracking Form status preview URL
	 *
	 */
	public function get_tracking_form_preview_url() {
		return add_query_arg( array(
			'action'	=> 'tracking-form-preview'
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
				'id'				=> 1,
				'status'			=> ( 'processing' ),
				'shipping_first_name'=> 'Sherlock',
				'shipping_last_name'=> 'Holmes',
				'shipping_company'	=> 'Detectives Ltd.',
				'shipping_address_1'=> '221B Baker Street',
				'shipping_city'		=> 'London',
				'shipping_postcode'	=> 'NW1 6XE',
				'shipping_country'	=> 'GB',
				'billing_first_name'=> 'Sherlock',
				'billing_last_name'	=> 'Holmes',
				'billing_company'	=> 'Detectives Ltd.',
				'billing_address_1'	=> '221B Baker Street',
				'billing_city'		=> 'London',
				'billing_postcode'	=> 'NW1 6XE',
				'billing_country'	=> 'GB',
				'billing_email'		=> 'sherlock@holmes.co.uk',
				'billing_phone'		=> '02079304832',
				'date_created'		=> gmdate( 'Y-m-d H:i:s' ),
				'total'				=> 24.90,
			) );

			// Item #1
			$order_item = new WC_Order_Item_Product();
			$order_item->set_props( array(
				'name'		=> 'A Study in Scarlet',
				'subtotal'	=> '9.95',
				'sku'		=> 'kwd_ex_1',
			) );
			$order->add_item( $order_item );

			// Item #2
			$order_item = new WC_Order_Item_Product();
			$order_item->set_props( array(
				'name'		=> 'The Hound of the Baskervilles',
				'subtotal'	=> '14.95',
				'sku'		=> 'kwd_ex_2',
			) );
			$order->add_item( $order_item );

			// Return mockup order
			return $order;
		}
	}
	
	public function get_wc_shipment_row_for_preview( $status = 'in_transit', $order_id = null ) {
		$order = wc_get_order( $order_id );
		$row = (object) [];
		if ( ! empty( $order_id ) && 'mockup' != $order_id ) {
			$rows = trackship_for_woocommerce()->actions->get_shipment_rows( $order_id );
			$row = $rows[0];
		} else {
			$row = (object) array(
				'est_delivery_date'	=> '2021-07-30 15:28:02',
				'shipment_status'	=> $status,
			);
		}
		return $row;
	}
	
	public function get_tracking_items_for_preview( $order_id = null ) {
		$tracking_items = array();
		if ( ! empty( $order_id ) && 'mockup' != $order_id ) {
			$array = trackship_for_woocommerce()->get_tracking_items( $order_id );
			$tracking_items[] = $array[0];
		} else {
			$tracking_items[] = array(
				'tracking_provider'				=> 'usps',
				'tracking_number'				=> '4208001392612927',
				'formatted_tracking_provider'	=> 'USPS',
				'formatted_tracking_link'		=> 'https://tools.usps.com/go/TrackConfirmAction_input?qtc_tLabels1=4208001392612927',
				'tracking_provider_image'		=> trackship_for_woocommerce()->plugin_dir_url() . 'assets/images/usps.png',
				'tracking_page_link'			=> '',
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
			
			$email_settings = wc_clean($_POST['email_settings'] ?? []);
			unset( $email_settings['common_settings']['email_trackship_branding'] );
			foreach ( $email_settings['common_settings'] as $key => $val ) {
				update_trackship_email_settings( 'common_settings', $key, $val );
			}
			
			$shipment_status = $this->shipment_status();
			foreach ( $shipment_status as $slug => $label ) {
				foreach ( $email_settings[$slug] as $key => $val ) {
					$val = in_array( $key, array( 'content', 'heading' ) ) ?  wp_kses_post( wp_unslash( $_POST['email_settings'][$slug][$key] ) ) : $val;
					update_trackship_email_settings( $slug, $key, $val );
				}
			}

			$trackship_settings = wc_clean($_POST['trackship_settings'] ?? []);
			foreach ( $trackship_settings as $key => $val ) {
				update_trackship_settings( $key, $val );
			}

			if ( isset($_POST[ 'email_preview' ]) ) {
				update_option( 'email_preview', wc_clean( $_POST[ 'email_preview' ] ) );
			}
			
			wp_send_json(['success' => 'true']);
		}
	}

	public function ts_email_preview_callback() {

		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( $_REQUEST['nonce'] ) : '' ;
		if ( !wp_verify_nonce( $nonce, 'ts_email' ) ) {
			wp_die('Please refresh the page, nonce verification failed.');
		}

		$status = isset( $_REQUEST['status'] ) ? sanitize_text_field($_REQUEST['status']) : false;

		if ( $status && in_array( $status, [ 'in_transit', 'available_for_pickup', 'out_for_delivery', 'failure', 'on_hold', 'exception', 'return_to_sender', 'delivered', 'pickup_reminder' ]) ) {
			$preview = new TSWC_Email_Customizer_Preview( $status );
			$preview->set_up_preview();
		} else {
			wp_die('Please close this window and reopen TrackShip email customizer.');
		}
		wp_die();
	}

	/**
	 * Deprecated since @1.9.2
	 */
	public function get_value ( $email_settings, $key, $status = '' ) {
		_deprecated_function( __FUNCTION__, '1.9.2', 'get_trackship_email_settings' );
		$value = trackship_for_woocommerce()->ts_actions->get_option_value_from_array( $email_settings, $key, '' );
		return $value;
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
