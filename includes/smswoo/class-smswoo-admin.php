<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TSWC_SMSWoo_Admin {
	
	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	*/
	private static $instance;
	
	/**
	 * Initialize the main plugin function
	*/
	public function __construct() {
		$this->init();
	}

	/**
	 * Get the class instance
	 *
	 * @return smswoo_admin
	*/
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	
	/*
	 * init function
	*/
	public function init() {
		
		//register admin menu
		add_action( 'after_trackship_settings', array( $this, 'smswoo_settings' ) );
		
		//ajax save admin api settings
		add_action( 'wp_ajax_smswoo_settings_tab_save', array( $this, 'smswoo_settings_tab_save_callback' ) );
		
		//hook into AST for shipment SMS notification
		add_action( 'shipment_status_sms_section', array( $this, 'shipment_status_notification_tab'), 10, 1 );
		
	}
	
	public function build_html( $template, $data = null, $echo = false ) {
		$t = new \stdclass();
		$t->data = $data;
		if ( $echo ) {
			include(dirname(__FILE__) . '/admin-html/' . $template . '.phtml');
		} else {
			ob_start();
			include(dirname(__FILE__) . '/admin-html/' . $template . '.phtml');
			$s = ob_get_contents();
			ob_end_clean();
			return $s;
		}
	}
	
	public function smswoo_settings() {
		$this->build_html( 'settings_tab', null, true );
	}
	
	/*
	* get html of fields
	*/
	public function get_html( $arrays ) {
		$checked = '';
		?>
		<table class="form-table">
			<tbody>
				<?php foreach ( (array) $arrays as $id => $array ) { ?>
					<?php if ( 'title' == $array['type'] ) { ?>
						<tr valign="top" class="<?php echo esc_html( $array['type'] ); ?>_row <?php echo esc_html( isset( $array['class'] ) ? $array['class'] : '' ); ?>">
							<th colspan="2">
								<?php $button = isset( $array['submit-button'] ) ? $array['submit-button'] : '' ; ?>
									<?php if ( ( 'true' == $button ) ) { ?>
										<div style="float:right;">
											<div class="spinner workflow_spinner" style="float:none"></div>
											<button name="save" class="button-primary button-trackship btn_large button-primary woocommerce-save-button button-smswoo" type="submit" ><?php esc_html_e( 'Save Changes', 'trackship-for-woocommerce' ); ?></button>
										</div>
									<?php } ?>
								<h3><?php echo esc_html( $array['title'] ); ?></h3>
							</th>
						</tr>
						<?php continue; ?>
					<?php } ?>
					<?php if ( 'dropdown_button' == $array['type'] ) { ?>
						<tr valign="top" class="<?php echo esc_html( $array['type'] ); ?>_row <?php echo esc_html( $array['class'] ); ?>">
							<th><?php echo esc_html( $array['title'] ); ?></th>
								<?php $value = get_option($id); ?>
							<td>
								<select id="<?php echo esc_html( $id ); ?>" name="<?php echo esc_html( $id ); ?>" >
									<?php foreach ( (array) $array['options'] as $key => $val ) { ?>
										<?php $imgpath = isset( $array[ 'img_path_24x24' ][ $key ] ) ? $array[ 'img_path_24x24' ][ $key ] : '' ; ?>
										<option value="<?php echo esc_html( $key ); ?>" image_path="<?php echo esc_html( $imgpath ); ?>" <?php echo esc_html( ( $value == (string) $key ) ? 'selected' : '' ); ?> ><?php echo esc_html( $val ); ?></option>
									<?php } ?>
								</select>
								<br>
								<?php foreach ( $array['link'] as $key1 => $links ) { ?>
								<strong valign="top" class="link_row smswoo_sms_provider <?php echo esc_html( $key1 ); ?>_sms_provider">
									<a href= "<?php echo esc_url( $links['link'] ); ?>" target="_blank"><?php echo esc_html( $links['title'] ); ?></a>
								</strong>
								<?php } ?>
							</td>
						</tr>
						<?php continue; ?>
					<?php } ?>
					<?php if ( 'link' == $array['type'] ) { ?>
						<tr valign="top" class="<?php echo esc_html( $array['type'] ); ?>_row <?php echo esc_html( $array['class'] ); ?>">
							<th colspan="2"><a href="<?php echo esc_url( $array['link'] ); ?>" target="_blank"><?php echo esc_html( $array['title'] ); ?></a></th>
						</tr>
						<?php continue; ?>
					<?php } ?>
					<?php if ( 'button' == $array['type'] ) { ?>
						<tr valign="top" class="<?php echo esc_html( $array['type'] ); ?>_row <?php echo esc_html( $array['class'] ); ?>">
							<td colspan="2">
								<fieldset>
									<button class="button-primary btn_green2 button-smswoo <?php echo esc_html( $array['button_class'] ); ?>" id="<?php echo esc_html( $id ); ?>" type="button"><?php echo esc_html( $array['title'] ); ?></button>
									<div class="spinner test_sms_spinner" style="float:none"></div>
								</fieldset>
							</td>
						</tr>
						<?php continue; ?>
					<?php } ?>
				<tr valign="top" class="<?php echo esc_html( $array['type'] ); ?>_row <?php echo esc_html( $array['class'] ); ?>">
					<?php if ( 'desc' != $array['type'] ) { ?>										
					<th scope="row" class="titledesc"  >
						<label for=""><?php echo esc_html( $array['title'] ); ?>
							<?php 
							if ( isset( $array['title_link'] ) ) { 
								echo esc_html( $array['title_link'] );
							}
							?>
							<?php if ( isset( $array['tooltip'] ) ) { ?>
								<span class="woocommerce-help-tip tipTip" title="<?php echo esc_html( $array['tooltip'] ); ?>"></span>
							<?php } ?>
						</label>
					</th>
					<?php } ?>
					<td class="forminp" <?php echo 'desc' == $array['type'] ? 'colspan=2' : ''; ?>>
						<?php 
						if ( 'checkbox' == $array['type'] ) {
							
							$default = isset( $array['default'] ) ? 1 : 0;

							if ( get_option( $id, $default ) ) {
								$checked = 'checked';
							} else {
								$checked = '';
							} 
							
							if ( isset($array['disabled']) && true == $array['disabled'] ) {
								$disabled = 'disabled';
								$checked = '';
							} else {
								$disabled = '';
							}							
							?>
							<input type="hidden" name="<?php echo esc_html( $id ); ?>" value="0"/>
							<input class="tgl tgl-flat" type="checkbox" id="<?php echo esc_html( $id ); ?>" name="<?php echo esc_html( $id ); ?>" <?php echo esc_html( $checked ); ?> value="1" <?php echo esc_html( $disabled ); ?>/>
							<label class="tgl-btn" for="<?php echo esc_html( $id ); ?>">
							</label><p class="description"><?php echo esc_html( ( isset( $array['desc'] ) )? $array['desc']: '' ); ?></p>
						<?php } elseif ( 'textarea' == $array['type'] ) { ?>
							<fieldset>
								<textarea rows="3" cols="20" class="input-text regular-input" type="textarea" name="<?php echo esc_html( $id ); ?>" id="<?php echo esc_html( $id ); ?>" style="" placeholder="<?php echo !empty( $array['placeholder'] ) ? esc_html( $array['placeholder'] ) : ''; ?>"><?php echo esc_html( get_option( $id, isset($array['default']) ? $array['default'] : false ) ); ?></textarea>
							</fieldset>
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
									<?php
									foreach ( (array) $array['options'] as $key => $val ) {
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
									<?php } ?><p class="description"><?php echo esc_html( ( isset( $array['desc'] ) ) ? $array['desc'] : '' ); ?></p>
								</select> 
								<br>
								<?php if ( isset( $array['desc'] ) && !empty( $array['desc'] ) ) { ?>
								<p class="description"><?php echo esc_html( $array['desc'] ); ?></p>
								<?php } ?>
								<?php if ( isset( $array['link'] ) ) { ?>
									<?php foreach ( $array['link'] as $key1 => $links ) { ?>
									<strong valign="top" class="link_row <?php echo esc_html( $links['class'] ); ?>">
										<a href= "<?php echo esc_url( $links['link'] ); ?>" target="_blank"><?php echo esc_html( $links['title'] ); ?></a>
									</strong>
									<?php } ?>
								<?php } ?>
							</fieldset>
						<?php } elseif ( 'title' == $array['type'] ) { ?>
						<?php } elseif ( 'label' == $array['type'] ) { ?>
							<fieldset>
								<label><?php echo esc_html( $array['value'] ); ?></label>
							</fieldset>
						
						<?php } elseif ( 'radio' == $array['type'] ) { ?>

							<fieldset>
								<ul>
									<?php foreach ( (array) $array['options'] as $key => $val ) { ?>
									<li>
										<label class="label_product_visibility"><input name="product_visibility" value="<?php echo esc_html( $key ); ?>" type="radio" style="" class="product_visibility" <?php echo $product_visibility == $key ? 'checked' : ''; ?> />
										<?php echo esc_html( $val ); ?>
										<br>
										</label>
									</li>
									<?php } ?>
								</ul>
							</fieldset>
						<?php } elseif ( 'dummyfield' == $array['type'] ) { ?>
						<?php } elseif ( 'time' == $array['type'] ) { ?>
					<fieldset>
						<input id="time_schedule_from" name="time_schedule_from" type="text" class="time" value="<?php echo esc_html( get_option('time_schedule_from') ); ?>" /> - 
						<input id="time_schedule_to" name="time_schedule_to" type="text" class="time" value="<?php echo esc_html( get_option('time_schedule_to') ); ?>" />
					</fieldset>
						<?php } else { ?>
							<fieldset>
								<input class="input-text regular-input " type="text" name="<?php echo esc_html( $id ); ?>" id="<?php echo esc_html( $id ); ?>" style="" value="<?php echo esc_html( get_option( $id, isset($array['default']) ? $array['default'] : false ) ); ?>" placeholder="<?php echo !empty( $array['placeholder'] ) ? esc_html( $array['placeholder'] ) : ''; ?>">
								<?php if ( isset( $array['desc']) && !empty($array['desc'] ) ) { ?>
									<p class="description"><?php echo esc_html( ( isset( $array['desc'] ) )? $array['desc'] : '' ); ?></p>
								<?php } ?>
							</fieldset>
						<?php } ?>
					</td>
				</tr>
	<?php } ?>
			</tbody>
		</table>
	<?php 
	}
	
	/**
	* Get the settings for sms_provider.
	*
	* @return array Array of settings sms_provider.
	*/
	public function get_sms_provider_data() {
		$settings = array(
			'title1' => array(
				'title'			=> __( 'SMS Service Provider', 'trackship-for-woocommerce' ),
				'type'			=> 'title',
				'id'			=> 'title1',
				'submit-button'	=> 'true'
			),
			'smswoo_sms_provider' => array(
				'title'		=> __( 'Choose SMS Provider', 'trackship-for-woocommerce' ),
				'desc'		=> __( 'Please choose SMS provider from Dropown.', 'trackship-for-woocommerce' ),
				'type'		=> 'dropdown_button',
				'show'		=> true,
				'id'		=> 'smswoo_sms_provider',
				'class'		=> '',
				'default'	=> '',
				'options'	=> array(
					''					=> __( 'Select SMS provider', 'trackship-for-woocommerce' ),
					'smswoo_nexmo'		=> 'Nexmo',
					'smswoo_twilio'		=> 'Twilio',
					'smswoo_clicksend'	=> 'ClickSend',
				),
				'link' => array(
					'smswoo_nexmo' => array(
						/* translators: %s: search Nexmo */
						'title' => sprintf( __( 'How to find your %s credential', 'trackship-for-woocommerce' ), 'Nexmo' ),
						'link' => 'https://www.zorem.com/docs/sms-for-woocommerce/sms-api-providers/nexmo/?utm_source=wp-admin&utm_medium=SMSWOO&utm_campaign=settings',
					),
					'smswoo_twilio' => array(
						/* translators: %s: search Twilio */
						'title' => sprintf( __( 'How to find your %s credential', 'trackship-for-woocommerce' ), 'Twilio' ),
						'link' => 'https://www.zorem.com/docs/sms-for-woocommerce/sms-api-providers/twilio/?utm_source=wp-admin&utm_medium=SMSWOO&utm_campaign=settings',
					),
					'smswoo_clicksend' => array(
						/* translators: %s: search ClickSend */
						'title' => sprintf( __( 'How to find your %s credential', 'trackship-for-woocommerce' ), 'ClickSend' ),
						'link' => 'https://www.zorem.com/docs/sms-for-woocommerce/sms-api-providers/clicksend/?utm_source=wp-admin&utm_medium=SMSWOO&utm_campaign=settings',
					)
				),
			),
			'smswoo_nexmo_key' => array(
				'title'		=> __( 'Nexmo key', 'trackship-for-woocommerce' ),
				'type'		=> 'text',
				'show'		=> true,
				'id'		=> 'smswoo_nexmo_key',
				'class'		=> 'smswoo_sms_provider smswoo_nexmo_sms_provider',
			),
			'smswoo_nexmo_secret' => array(
				'title'		=> __( 'Nexmo Secret', 'trackship-for-woocommerce' ),
				'type'		=> 'text',
				'show'		=> true,
				'id'		=> 'smswoo_nexmo_secret',
				'class'		=> 'smswoo_sms_provider smswoo_nexmo_sms_provider',
			),
			'smswoo_twilio_account_sid' => array(
				'title'		=> __( 'Twilio Account SID', 'trackship-for-woocommerce' ),
				'type'		=> 'text',
				'show'		=> true,
				'id'		=> 'smswoo_twilio_account_sid',
				'class'		=> 'smswoo_sms_provider smswoo_twilio_sms_provider',
			),
			'smswoo_twilio_auth_token' => array(
				'title'		=> __( 'Twilio Auth Token', 'trackship-for-woocommerce' ),
				'type'		=> 'text',
				'show'		=> true,
				'id'		=> 'smswoo_twilio_auth_token',
				'class'		=> 'smswoo_sms_provider smswoo_twilio_sms_provider',
			),
			'smswoo_clicksend_username' => array(
				'title'		=> __( 'Clicksend API Username', 'trackship-for-woocommerce' ),
				'type'		=> 'text',
				'show'		=> true,
				'id'		=> 'smswoo_clicksend_username',
				'class'		=> 'smswoo_sms_provider smswoo_clicksend_sms_provider',
			),
			'smswoo_clicksend_key' => array(
				'title'		=> __( 'Clicksend API key', 'trackship-for-woocommerce' ),
				'type'		=> 'text',
				'show'		=> true,
				'id'		=> 'smswoo_clicksend_key',
				'class'		=> 'smswoo_sms_provider smswoo_clicksend_sms_provider',
			),
			'smswoo_sender_phone_number' => array(
				'title'		=> __( 'Sender phone number / Sender ID', 'trackship-for-woocommerce' ),
				'desc'		=> __( 'This field appears as a from or Sender ID', 'trackship-for-woocommerce'),
				'type'		=> 'text',
				'show'		=> true,
				'id'		=> 'smswoo_sender_phone_number',
				'class'		=> 'smswoo_sms_provider smswoo_nexmo_sms_provider smswoo_twilio_sms_provider smswoo_clicksend_sms_provider', //add provider class if need this field in another provider
			),
		);
		$settings = apply_filters( 'smswoo_sms_provider_array', $settings );
		return $settings;
	}
	
	/*
	* settings form save
	* save settings of all tab
	*
	* @since   1.0
	*/
	public function smswoo_settings_tab_save_callback() {
		
		check_ajax_referer( 'smswoo_settings_tab', 'smswoo_settings_tab_nonce' );
		
		$data = $this->get_sms_provider_data();
		foreach ( $data as $key => $val ) {
			if ( isset($_POST[ $key ] ) ) {
				update_option( $key, sanitize_text_field( $_POST[ $key ] ) );
			}
		}
		
		$data = $this->get_customer_tracking_status_settings();
		
		foreach ( $data as $key => $val ) {
			if ( isset($_POST[ $val['id'] ] ) ) {
				
				update_option( $val['id'], sanitize_text_field( $_POST[ $val['id'] ] ) );
				
				$enabled_customer = $val['id'] . '_enabled_customer';
				$enabled_admin = $val['id'] . '_enabled_admin';
				
				$e_customer = isset( $_POST[ $enabled_customer ] ) ? sanitize_text_field( $_POST[ $enabled_customer ] ) : '';
				$e_admin = isset( $_POST[ $enabled_admin ] ) ? sanitize_text_field( $_POST[ $enabled_admin ] ) : '';
				update_option( $enabled_customer, $e_customer );
				update_option( $enabled_admin, $e_admin );
			}
		}
		echo json_encode( array('success' => 'true') );
		die();
	}
	
	/*
	*
	*/
	public function shipment_status_notification_tab() {
		$this->build_html( 'shipment_status_sms_tab', null, true );
	}
	
	/*
	* get html of fields
	*/
	public function get_shipment_template_html( $arrays ) {
		$checked = '';
		?>
		<div class="smswoo-container">
			<?php 
			foreach ( (array) $arrays as $id => $array ) {
				$enabled_customer = $array['id'] . '_enabled_customer';
				$enabled_admin = $array['id'] . '_enabled_admin';
				  
				$checked_customer = get_option( $enabled_customer );
				$checked_admin = get_option( $enabled_admin );
				?>
				<div class="smswoo-row smswoo-shipment-row <?php echo esc_html( $checked_customer ? 'enable_customer' : '' ); ?> <?php echo esc_html( $checked_admin ? 'enable_admin' : '' ); ?>">
					<div class="smswoo-top">
						<div class="smswoo-top-click"></div>
						<div>
							<span class="smswoo-inlineblock">
								<input type="hidden" name="<?php echo esc_html( $enabled_customer ); ?>" value="0"/>
								<input type="checkbox" id="<?php echo esc_html( $enabled_customer ); ?>" name="<?php echo esc_html( $enabled_customer ); ?>" class="tgl tgl-flat smswoo-shipment-checkbox" value="1" <?php echo esc_html( $checked_customer ? 'checked' : '' ); ?> data-row_class="enable_customer" />
								<label class="tgl-btn" for="<?php echo esc_html( $enabled_customer ); ?>"></label>
							</span>
							<span class="smswoo-label <?php echo esc_html( $array['id'] ); ?>"><?php echo esc_html( $array['label'] ); ?></span>
						</div>
						<span class="smswoo-right smswoo-mr20 smswoo-shipment-sendto">
							<button type="button" class="smswoo-shipment-sendto-customer btn_ts_transparent btn_outline"><?php esc_html_e( 'Customize', 'trackship-for-woocommerce' ); ?></span>
							<button name="save" class="button-primary woocommerce-save-button button-smswoo hide button-trackship" type="submit" value="Save changes"><?php esc_html_e( 'Save & Close', 'trackship-for-woocommerce' ); ?></button>
						</span>
					</div>
					<div class="smswoo-bottom">
						<div class="smswoo-ast-textarea">
							<div class="smawoo-textarea-placeholder">
								<textarea class="smswoo-textarea" name="<?php echo esc_html( $array['id'] ); ?>" id="<?php echo esc_html( $array['id'] ); ?>" cols="30" rows="5"><?php echo esc_html( get_option( $array['id'], $array['default'] ) ); ?></textarea>
								<span class="mdl-list__item-secondary-action smswoo-inlineblock">
								<label class="mdl-switch " for="<?php echo esc_html( $enabled_admin ); ?>" >
									<?php esc_html_e( 'Send to admin', 'trackship-for-woocommerce' ); ?>
									<input type="hidden" name="<?php echo esc_html( $enabled_admin ); ?>" value="0"/>
									<input type="checkbox" id="<?php echo esc_html( $enabled_admin ); ?>" name="<?php echo esc_html( $enabled_admin ); ?>" class="mdl-switch__input smswoo-shipment-checkbox" value="1" <?php echo esc_html( $checked_admin ? 'checked' : '' ); ?> data-row_class="enable_admin" />
								</label>
								</span>
								</div>
								<div class="zorem_plugin_sidebar smswoo_sidebar">
								<?php $this->build_html( 'plugin_sidebar_placeholders', null, true ); ?>
							</div>
						</div>  
					</div>
				</div>
			<?php } ?>
		</div>
	<?php 
	}
	
	/*
	* get customer tracking status settings
	*
	* @since   1.0
	*/
	public function get_customer_tracking_status_settings() {
		
		$tracking_status = array(
			'in_transit'			=> __( 'In Transit', 'trackship-for-woocommerce' ),
			'on_hold'				=> __( 'On Hold', 'trackship-for-woocommerce' ),
			'return_to_sender'		=> __( 'Return To Sender', 'trackship-for-woocommerce' ),
			'available_for_pickup'	=> __( 'Available For Pickup', 'trackship-for-woocommerce' ),
			'out_for_delivery'		=> __( 'Out For Delivery', 'trackship-for-woocommerce' ),
			'delivered'				=> __( 'Delivered', 'trackship-for-woocommerce' ),
			'failure'				=> __( 'Failed Attempt', 'trackship-for-woocommerce' ),
			'exception'				=> __( 'Exception', 'trackship-for-woocommerce' ),
		);
				
		// Display a textarea setting for each available order status
		foreach ( $tracking_status as $slug => $label ) {

			$slug = 'wc-' === substr( $slug, 0, 3 ) ? substr( $slug, 3 ) : $slug;

			$settings[] = [
				'id'		=> 'smswoo_trackship_status_' . $slug . '_sms_template',
				'label'		=> sprintf( '%s', $label ),
				'css'		=> 'min-width:500px;',
				'type'		=> 'textarea',
				'default'	=> "Hi, Your order no %order_id% on %shop_name% is now {$label}.",
			];
		}
		return $settings;
	}
	
}
