<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class tswc_smswoo_admin {
	
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
			self::$instance = new self;
		}

		return self::$instance;
	}
	
	/*
	 * init function
	*/
	public function init(){
		
		//register admin menu
		add_action( 'after_trackship_settings', array( $this, 'smswoo_settings' ) );
		
		//ajax save admin api settings
		add_action( 'wp_ajax_smswoo_settings_tab_save', array( $this, 'smswoo_settings_tab_save_callback' ) );

		if ( ! function_exists( 'SMSWOO' ) && !is_plugin_active( 'zorem-sms-for-woocommerce/zorem-sms-for-woocommerce.php' ) ) {
			//hook into AST for shipment SMS notification
			add_action( 'shipment_status_sms_section', array( $this, 'shipment_status_notification_tab'), 10, 1 );
			
			//Ajax save delivered email
			add_action( 'wp_ajax_update_all_shipment_status_sms_delivered', array( $this, 'update_all_shipment_status_sms_delivered') );
		}
	}
	
	public function build_html($template,$data = NULL) {
		global $wpdb;
		$t = new \stdclass();
		$t->data = $data;
		ob_start();
		include(dirname(__FILE__)."/admin-html/".$template.".php");
		$s = ob_get_contents();
		ob_end_clean();
		return $s;
	}
	
	function smswoo_settings(){
		if ( in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ) {
			return;
		}
		echo $this->build_html( "settings_tab" );
	}
	
	/*
	* get html of fields
	*/
	public function get_html( $arrays ){
		$checked = '';
		?>
		<table class="form-table">
			<tbody>
				<?php foreach( (array)$arrays as $id => $array ){ ?>
					
					<?php if( $array['type'] == 'title' ){ ?>
						<tr valign="top" class="<?php echo $array['type']?>_row <?php echo isset( $array['class'] ) ? $array['class'] : ''?>">
							<th colspan="2">
									<?php if ( ( $button == 'true' ) ) {?>
										<div style="float:right;">
											<div class="spinner workflow_spinner"></div>
											<button name="save" class="button-primary button-trackship btn_large button-primary woocommerce-save-button button-smswoo" type="submit" ><?php esc_html_e( 'Save Changes', 'trackship-for-woocommerce' )?></button>
										</div>
									<?php } ?>
								<h3><?php echo $array['title'] ?></h3>
							</th>
						</tr>
                        <?php continue; ?>
					<?php } ?>
                    
                    <?php if( $array['type'] == 'dropdown_button' ){ ?>
						<tr valign="top" class="<?php echo $array['type']?>_row <?php echo $array['class']; ?>">
							<th><?php echo $array['title']?></th>
								<?php
									$value = get_option($id);
								?>
							<td>
								<select id="<?php echo $id?>" name="<?php echo $id?>" >
									<?php foreach((array)$array['options'] as $key => $val ){?>
										<?php $imgpath = isset( $array[ 'img_path_24x24' ][ $key ] ) ? $array[ 'img_path_24x24' ][ $key ] : '' ?>
										<option value="<?php echo $key?>" image_path="<?php echo $imgpath?>" <?php echo ( $value == (string)$key ) ? 'selected' : '' ?> ><?php echo $val?></option>
									<?php } ?>
								</select>
								<br>
								<?php foreach($array['link'] as $key1 => $links) {?>
								<strong valign="top" class="link_row smswoo_sms_provider <?php echo $key1; ?>_sms_provider">
									<a href= "<?php echo $links['link']?>" target="_blank"><?php echo $links['title']?></a>
								</strong>
								<?php } ?>
							</td>
						</tr>
                        <?php continue; ?>
					<?php } ?>
                    
                    <?php if( $array['type'] == 'link' ){ ?>
						<tr valign="top" class="<?php echo $array['type']?>_row <?php echo $array['class']; ?>">
							<th colspan="2"><a href="<?php echo $array['link']?>" target="_blank"><?php echo $array['title']?></a></th>
						</tr>
                        <?php continue; ?>
					<?php } ?>
                    
                    <?php if( $array['type'] == 'button' ){ ?>
						<tr valign="top" class="<?php echo $array['type']?>_row <?php echo $array['class']; ?>">
							<td colspan="2">
                                <fieldset>
                                    <button class="button-primary btn_green2 button-smswoo <?php echo $array['button_class'];?>" id="<?php echo $id?>" type="button"><?php echo $array['title'];?></button>
                                    <div class="spinner test_sms_spinner"></div>
                                </fieldset>
                            </td>
						</tr>
                        <?php continue; ?>
					<?php } ?>

                	
				<tr valign="top" class="<?php echo $array['type']?>_row <?php echo $array['class']; ?>">
					<?php if($array['type'] != 'desc'){ ?>										
					<th scope="row" class="titledesc"  >
						<label for=""><?php echo $array['title']?><?php if(isset($array['title_link'])){ echo $array['title_link']; } ?>
							<?php if( isset($array['tooltip']) ){?>
                            	<span class="woocommerce-help-tip tipTip" title="<?php echo $array['tooltip']?>"></span>
                            <?php } ?>
                        </label>
					</th>
					<?php } ?>
					<td class="forminp"  <?php if($array['type'] == 'desc'){ ?> colspan=2 <?php } ?>>
                    	<?php if( $array['type'] == 'checkbox' ){
							
								$default = isset( $array['default'] ) ? 1 : 0;

								if( get_option( $id, $default ) ){
									$checked = 'checked';
								} else{
									$checked = '';
								} 
							
							if(isset($array['disabled']) && $array['disabled'] == true){
								$disabled = 'disabled';
								$checked = '';
							} else{
								$disabled = '';
							}							
							?>
							<input type="hidden" name="<?php echo $id?>" value="0"/>
							<input class="tgl tgl-flat" type="checkbox" id="<?php echo $id?>" name="<?php echo $id?>" <?php echo $checked ?> value="1" <?php echo $disabled; ?>/>
							<label class="tgl-btn" for="<?php echo $id?>">
							</label><p class="description"><?php echo (isset($array['desc']))? $array['desc']: ''?></p>
						<?php } elseif( $array['type'] == 'textarea' ){ ?>
							<fieldset>
								<textarea rows="3" cols="20" class="input-text regular-input" type="textarea" name="<?php echo $id?>" id="<?php echo $id?>" placeholder="<?php if(!empty($array['placeholder'])){echo $array['placeholder'];} ?>"><?php echo get_option( $id, isset($array['default']) ? $array['default'] : false )?></textarea>
							</fieldset>
                        <?php }  elseif( isset( $array['type'] ) && $array['type'] == 'dropdown' ){?>
                        	<?php
								if( isset($array['multiple']) ){
									$multiple = 'multiple';
									$field_id = $array['multiple'];
								} else {
									$multiple = '';
									$field_id = $id;
								}
								
							?>
                        	<fieldset>
								<select class="select select2" id="<?php echo $field_id?>" name="<?php echo $id?>" <?php echo $multiple;?>>    <?php foreach((array)$array['options'] as $key => $val ){?>
									<?php 
                                        $selected = '';
                                        if( isset($array['multiple']) ){
                                            if (in_array($key, (array)$this->data->$field_id ))$selected = 'selected';
                                        } else {
                                            if( get_option($id) == (string)$key )$selected = 'selected';
                                        }
                                    ?>
									<option value="<?php echo $key?>" <?php echo $selected?> ><?php echo $val?></option>
                                    <?php } ?><p class="description"><?php echo (isset($array['desc']))? $array['desc']: ''?></p>
								</select> 
								<br>
								<?php if(isset($array['desc']) && !empty($array['desc'])){?>
								<p class="description"><?php echo $array['desc'];?></p>
								<?php } ?>
								<?php if(isset($array['link'])){ ?>
									<?php foreach($array['link'] as $key1 => $links) {?>
									<strong valign="top" class="link_row <?php echo $links['class']?>">
										<a href= "<?php echo $links['link']?>" target="_blank"><?php echo $links['title']?></a>
									</strong>
									<?php } ?>
								<?php } ?>
							</fieldset>
                        <?php } elseif ( $array['type'] == 'title' ) { ?>
						<?php } elseif ( $array['type'] == 'label' ) { ?>
							<fieldset>
                               <label><?php echo $array['value']; ?></label>
                            </fieldset>
						<?php } elseif( $array['type'] == 'radio' ) { ?>
							<fieldset>
                            	<ul>
									<?php foreach ( (array) $array['options'] as $key => $val ) { ?>
										<li><label class="label_product_visibility"><input name="product_visibility" value="<?php echo $key; ?>" type="radio" style="" class="product_visibility" <?php if( $product_visibility == $key ) { echo 'checked'; } ?> /><?php echo $val;?><br></label></li>
                                    <?php } ?>
                                 </ul>
							</fieldset>
						<?php } elseif ( $array['type'] == 'dummyfield' ) { ?>
                        <?php } elseif ( $array['type'] == 'time' ) { ?>
							<fieldset>
                            	<input id="time_schedule_from" name="time_schedule_from" type="text" class="time" value="<?php echo get_option('time_schedule_from');?>" /> - 
                                <input id="time_schedule_to" name="time_schedule_to" type="text" class="time" value="<?php echo get_option('time_schedule_to');?>" />
							</fieldset>
						<?php } else { ?>
                                                    
                        	<fieldset>
                                <input class="input-text regular-input " type="text" name="<?php echo $id?>" id="<?php echo $id?>" style="" value="<?php echo get_option( $id, isset($array['default']) ? $array['default'] : false )?>" placeholder="<?php if(!empty($array['placeholder'])){echo $array['placeholder'];} ?>">
								<?php if(isset($array['desc']) && !empty($array['desc'])){?>
                                <p class="description"><?php echo (isset($array['desc']))? $array['desc']: ''?></p>
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
	function get_sms_provider_data() {
        $settings = array(
			/*'title1' => array(
				'title'			=> __( 'SMS Service Provider', 'trackship-for-woocommerce' ),
				'type'			=> 'title',
				'id'			=> 'title1',
			),*/
			'smswoo_sms_provider' => array(
				'title'		=> __( 'Select SMS Integration', 'trackship-for-woocommerce' ),
				'desc'		=> __( "Please choose SMS provider from Dropown.", 'trackship-for-woocommerce' ),
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
					'smswoo_fast2sms'	=> 'Fast2sms',
					'smswoo_msg91'		=> 'Msg91',
					'smswoo_smsalert'	=> 'SMS Alert',
				),
				'link' => array(
					'smswoo_nexmo' => array(
						'title' => sprintf( __( 'How to find your %s credential', 'trackship-for-woocommerce' ), 'Nexmo' ),
						'link' => 'https://docs.trackship.com/docs/trackship-for-woocommerce/setup/sms-notifications/vonage/?utm_source=ts4wc&utm_medium=SMS&utm_campaign=settings',
					),
					'smswoo_twilio' => array(
						'title' => sprintf( __( 'How to find your %s credential', 'trackship-for-woocommerce' ), 'Twilio' ),
						'link' => 'https://docs.trackship.com/docs/trackship-for-woocommerce/setup/sms-notifications/twilio/?utm_source=ts4wc&utm_medium=SMS&utm_campaign=settings',
					),
					'smswoo_clicksend' => array(
						'title' => sprintf( __( 'How to find your %s credential', 'trackship-for-woocommerce' ), 'ClickSend' ),
						'link' => 'https://docs.trackship.com/docs/trackship-for-woocommerce/setup/sms-notifications/clicksend/?utm_source=ts4wc&utm_medium=SMS&utm_campaign=settings',
					),
					'smswoo_fast2sms' => array(
						'title' => sprintf( __( 'How to find your %s credential', 'trackship-for-woocommerce' ), 'Fast2sms' ),
						'link' => 'https://docs.trackship.com/docs/trackship-for-woocommerce/setup/sms-notifications/fast2sms/?utm_source=ts4wc&utm_medium=SMS&utm_campaign=settings',
					),	
					'smswoo_msg91' => array(
						'title' => sprintf( __( 'How to find your %s credential', 'trackship-for-woocommerce' ), 'MSG91' ),
						'link' => 'https://docs.trackship.com/docs/trackship-for-woocommerce/setup/sms-notifications/msg91/?utm_source=ts4wc&utm_medium=SMS&utm_campaign=settings',
					),
					'smswoo_smsalert' => array(
						'title' => sprintf( __( 'How to find your %s credential', 'trackship-for-woocommerce' ), 'SMS Alert' ),
						'link' => 'https://docs.trackship.com/docs/trackship-for-woocommerce/setup/sms-notifications/sms-alert/?utm_source=ts4wc&utm_medium=SMS&utm_campaign=settings',
					),
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
			'smswoo_fast2sms_key' => array(
				'title'		=> __( 'Fast2sms API Authorization Key', 'smswoo' ),
				//'desc'		=> __( "Fast2sms API Authorization Key", 'smswoo'),
				'type'		=> 'text',
				'show'		=> true,
				'id'		=> 'smswoo_fast2sms_key',
				'class'		=> 'smswoo_sms_provider smswoo_fast2sms_sms_provider',
			),
			'smswoo_msg91_authkey' => array(
				'title'		=> __( 'MSG91 Authentication Key', 'smswoo' ),
				'type'		=> 'text',
				'show'		=> true,
				'id'		=> 'smswoo_msg91_authkey',
				'class'		=> 'smswoo_sms_provider smswoo_msg91_sms_provider',
			),
			'smswoo_smsalert_key' => array(
				'title'		=> __( 'SMS Alert API Authorization Key', 'smswoo' ),
				//'desc'		=> __( "Fast2sms API Authorization Key", 'smswoo'),
				'type'		=> 'text',
				'show'		=> true,
				'id'		=> 'smswoo_smsalert_key',
				'class'		=> 'smswoo_sms_provider smswoo_smsalert_sms_provider',
			),
			'smswoo_sender_phone_number' => array(
				'title'		=> __( "Sender phone number / Sender ID", 'trackship-for-woocommerce' ),
				'desc'		=> __( 'This field appears as a from or Sender ID', 'trackship-for-woocommerce'),
				'type'		=> 'text',
				'show'		=> true,
				'id'		=> 'smswoo_sender_phone_number',
				'class'		=> 'smswoo_sms_provider smswoo_nexmo_sms_provider smswoo_twilio_sms_provider smswoo_clicksend_sms_provider smswoo_smsalert_sms_provider smswoo_msg91_sms_provider', //add provider class if need this field in another provider
			),
        );
		$settings = apply_filters( "smswoo_sms_provider_array", $settings );
        return $settings;
    }
	
	/*
	* settings form save
	* save settings of all tab
	*
	* @since   1.0
	*/
	public function smswoo_settings_tab_save_callback(){
		
		check_ajax_referer( 'smswoo_settings_tab', 'smswoo_settings_tab_nonce' );
		
		$data = $this->get_sms_provider_data();
		foreach( $data as $key => $val ){
			if(isset($_POST[ $key ])){
				update_option( $key, $_POST[ $key ] );
			}
		}
		
		$data = $this->get_customer_tracking_status_settings();
		
		foreach( $data as $key => $val ){
			if(isset($_POST[ $val['id'] ])){
				
				update_option( $val['id'], $_POST[ $val['id'] ] );
				
				$enabled_customer = $val['id'] . "_enabled_customer";
				$enabled_admin = $val['id'] . "_enabled_admin";
				
				update_option( $enabled_customer, $_POST[ $enabled_customer ] );
				update_option( $enabled_admin, $_POST[ $enabled_admin ] );
				
			}
		}
		
		return;
		
		$data = $this->get_settings_data();
		foreach( $data as $key => $val ){
			if(isset($_POST[ $key ])){
				update_option( $key, $_POST[ $key ] );
			}
		}
		
		$data = $this->get_url_shortening_data();
		foreach( $data as $key => $val ){
			if(isset($_POST[ $key ])){
				update_option( $key, $_POST[ $key ] );
			}
		}
		
		$data = $this->get_customer_orderstatus_settings();
		
		foreach( $data as $key => $val ){
			if(isset($_POST[ $val['id'] ])){
				update_option( $val['id'], $_POST[ $val['id'] ] );
				
				$enabled_id = $val['id'] . "_enabled";
				update_option( $enabled_id, $_POST[ $enabled_id ] );
			}
		}
		
		
		
		$data = $this->get_admin_notification_settings();
		foreach( $data as $key => $val ){
			if(isset($_POST[ $val['id'] ])){
				update_option( $val['id'], $_POST[ $val['id'] ] );
				
				$enabled_id = $val['id'] . "_enabled";
				update_option( $enabled_id, $_POST[ $enabled_id ] );
			}
		}
		
		echo json_encode( array('success' => 'true') );die();
	}
	
	/*
	* Save delivered email setting
	*/
	public function update_all_shipment_status_sms_delivered() {
		check_ajax_referer( 'all_shipment_delivered', 'security' );
		$all_status = isset( $_POST['sms_delivered'] ) ? wc_clean( $_POST['sms_delivered'] ) : '';
		update_option( 'all-shipment-status-sms-delivered', $all_status );
		exit;
	}
	
	/*
	*
	*/
	public function shipment_status_notification_tab(){
		echo $this->build_html( "shipment_status_sms_tab" );
	}
	
	/*
	* get html of fields
	*/
	public function get_shipment_template_html( $arrays ){
		$checked = '';
		?>
		<div class="smswoo-container">
			<?php foreach ( (array) $arrays as $id => $array ) {
				$enabled_customer = $array['id'] . "_enabled_customer";
				$enabled_admin = $array['id'] . "_enabled_admin";
				
				$checked_customer = get_option( $enabled_customer );
				$checked_admin = get_option( $enabled_admin );
				?>
				<div class="smswoo-row smswoo-shipment-row <?php echo ( $checked_customer ) ? 'enable_customer' : ''?> <?php echo ( $checked_admin ) ? 'enable_admin' : ''?>">
					<div class="smswoo-top">
						<div class="smswoo-top-click"></div>
						<div>
							<?php $image_name = 'in_transit' == $array['slug'] ? 'in-transit' : $array['slug']; ?>
							<?php $image_name = 'available_for_pickup' == $image_name ? 'available-for-pickup' : $image_name; ?>
							<?php $image_name = 'out_for_delivery' == $image_name ? 'out-for-delivery' : $image_name; ?>
							<?php $image_name = in_array( $image_name, array( 'failure', 'exception' ) ) ? 'failure' : $image_name; ?> 
							<?php $image_name = 'on_hold' == $image_name ? 'on-hold' : $image_name; ?>
							<?php $image_name = 'return_to_sender' == $image_name ? 'return-to-sender' : $image_name; ?>
							<?php $image_name = 'delivered' == $image_name ? 'delivered' : $image_name; ?>
							<img src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/css/icons/<?php echo esc_html( $image_name ); ?>.png">
							<span class="smswoo-label <?php echo $array['id']?>"><?php echo $array['label'];?></span>
							<?php if ( 'delivered' == $array['slug'] ) { ?>
								<label style="position:relative;">
									<input type="hidden" name="all-shipment-status-sms-delivered" value="no">
									<input name="all-shipment-status-sms-delivered" type="checkbox" id="all-shipment-status-sms-delivered" value="yes" <?php echo get_option( 'all-shipment-status-sms-delivered' ) == 1 ? 'checked' : '' ?> >
									<?php echo __( 'Send only when all shipments for the order are delivered', 'trackship-for-woocommerce' ); ?>
									<?php $nonce = wp_create_nonce( 'all_shipment_delivered'); ?>
									<input type="hidden" id="delivered_sms" name="delivered_sms" value="<?php echo esc_attr( $nonce ); ?>" />
								</label>
						<?php } ?>
						</div>
                        <span class="smswoo-right smswoo-mr20 smswoo-shipment-sendto">
							<button name="save" class="button-primary woocommerce-save-button button-smswoo hide button-trackship" type="submit" value="Save changes"><?php echo __( 'Save & close', 'trackship-for-woocommerce' ); ?></button>
							<span class="smswoo-inlineblock">
								<input type="hidden" name="<?php echo $enabled_customer?>" value="0"/>
								<input type="checkbox" id="<?php echo $enabled_customer?>" name="<?php echo $enabled_customer?>" class="tgl tgl-flat smswoo-shipment-checkbox" value="1" <?php echo $checked_customer ? 'checked' : ''?> data-row_class="enable_customer" />
								<label class="tgl-btn" for="<?php echo $enabled_customer?>"></label>
							</span>
							<span class="smswoo-shipment-sendto-customer dashicons dashicons-admin-generic"></span>
						</span>
					</div>
					<div class="smswoo-bottom">
						<div class="smswoo-ast-textarea">
							<div class="smawoo-textarea-placeholder">
								<textarea class="smswoo-textarea" name="<?php echo $array['id']?>" id="<?php echo $array['id']?>" cols="30" rows="5"><?php echo get_option( $array['id'], $array['default'] )?></textarea>
								<span class="mdl-list__item-secondary-action smswoo-inlineblock">
								<label class="mdl-switch " for="<?php echo $enabled_admin?>" >
									<?php echo __( 'Send to admin', 'trackship-for-woocommerce' ); ?>
									<input type="hidden" name="<?php echo $enabled_admin?>" value="0"/>
									<input type="checkbox" id="<?php echo $enabled_admin?>" name="<?php echo $enabled_admin?>" class="mdl-switch__input smswoo-shipment-checkbox" value="1" <?php echo $checked_admin ? 'checked' : ''?> data-row_class="enable_admin" />
								</label>
							</span>
							</div>
							<div class="zorem_plugin_sidebar smswoo_sidebar">
								<?php echo $this->build_html( 'plugin_sidebar_placeholders' ); ?>
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
			'available_for_pickup'	=> __( 'Available For Pickup', 'trackship-for-woocommerce' ),
			'out_for_delivery'		=> __( 'Out For Delivery', 'trackship-for-woocommerce' ),
			'failure'				=> __( 'Failed Attempt', 'trackship-for-woocommerce' ),
			'on_hold'				=> __( 'On Hold', 'trackship-for-woocommerce' ),
			'exception'				=> __( 'Exception', 'trackship-for-woocommerce' ),
			'return_to_sender'		=> __( 'Return To Sender', 'trackship-for-woocommerce' ),
			'delivered'				=> __( 'Delivered', 'trackship-for-woocommerce' ),
		);
				
		// Display a textarea setting for each available order status
		foreach ( $tracking_status as $slug => $label ) {

			$slug = 'wc-' === substr( $slug, 0, 3 ) ? substr( $slug, 3 ) : $slug;

			$settings[] = [
				'slug'		=> $slug,
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
