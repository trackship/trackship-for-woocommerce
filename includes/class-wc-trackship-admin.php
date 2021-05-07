<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Trackship_Admin {
	
	/**
	 * Initialize the main plugin function
	*/
    public function __construct() {
		
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
	 * @return WC_Advanced_Shipment_Tracking_Admin
	*/
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
	
	/*
	* init from parent mail class
	*/
	public function init(){
		
		add_action( 'admin_menu', array( $this, 'register_woocommerce_menu' ), 110 );
		
		add_action( 'admin_footer', array( $this, 'footer_function'), 1 );		
		add_filter( 'trackship_get_provider_name_from_slug', array( $this, 'trackship_get_provider_name_from_slug_callback') );		
		add_action( 'wp_ajax_add_trackship_mapping_row', array( $this, 'add_trackship_mapping_row' ) );
		add_action( 'wp_ajax_trackship_mapping_form_update', array( $this, 'trackship_custom_mapping_form_update') );
		add_filter( 'convert_provider_name_to_slug', array( $this, 'detect_custom_mapping_provider') );	
		add_action( 'wp_ajax_ts_late_shipments_email_form_update', array( $this, 'ts_late_shipments_email_form_update_callback' ) );
		
		add_action( 'wp_ajax_wc_ast_trackship_automation_form_update', array( $this, 'wc_ast_trackship_automation_form_update') );
		
		add_action( 'add_meta_boxes', array( $this, 'register_metabox') );
		
		add_action( 'wp_ajax_wc_shipment_tracking_delete_item', array( $this, 'meta_box_delete_tracking' ), 20 );

	    add_action( 'wp_ajax_metabox_get_shipment_status', array( $this, 'metabox_get_shipment_status_cb' ) );
		
		add_action( 'wp_ajax_get_admin_tracking_widget', array( $this, 'get_admin_tracking_widget_cb' ) );
		
		add_action( 'woocommerce_auth_page_footer', array( $this, 'remove_connect_store_border' ), 5 );

	}
	
	public function remove_connect_store_border(){
		?>
        	<style>body.wc-auth.wp-core-ui {border: 0;}</style>
        <?php
	}
	
	public function get_admin_tracking_widget_cb() {
		
		if( current_user_can( 'manage_woocommerce' ) ) {
		
			$order_id    = wc_clean( $_POST['order_id'] );
			$tracking_page_link = trackship_for_woocommerce()->actions->get_tracking_page_link( $order_id );
			?>
			<div style="text-align:right;">
				<button class="button btn_outline copy_tracking_page" data-tracking_page_link=<?php echo esc_url( $tracking_page_link ); ?> >
					<span class="dashicons dashicons-media-default" style="vertical-align: middle;"></span>
					<span style="vertical-align: middle;">Copy Tracking page</span>
				</button>
			</div>
			<?php
			trackship_for_woocommerce()->front->show_tracking_page_widget( $order_id );
			
		} else {
			esc_html_e( 'Please refresh the page and try again.' );
		}
		die();
	}
	
	public function metabox_get_shipment_status_cb() {
		$order_id    = wc_clean( $_POST['order_id'] );
		if ( isset( $_REQUEST['security'] ) && wp_verify_nonce( $_REQUEST['security'], 'update-post_' . $order_id ) ) {
			$bool = trackship_for_woocommerce()->actions->schedule_trackship_trigger( $order_id );
			if ( $bool ) {
				$data = array(
					'msg' => 'Tracking information has been sent to TrackShip.'
				);
				wp_send_json_success( $data );
			} else {
				$data = array(
					'msg' => 'Tracking information was not sent to TrackShip.'
				);
				wp_send_json_error( $data );
			}
		} else {
			$data = array(
				'msg' => 'Please refresh the page and try again.'
			);
			wp_send_json_error( $data );
		}
		die();
	}
	
	public function meta_box_delete_tracking() {
		$order_id    = wc_clean( $_POST['order_id'] );
		$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
		
		$shipment_statuses = get_post_meta( $order_id, 'shipment_status', true );
		if ( ! is_array( $shipment_statuses ) ) {
			$shipment_statuses = array();
		}
		
		foreach( $shipment_statuses as $key => $shipment_status ){
			if( ! isset( $tracking_items[$key] ) ){
				unset( $shipment_statuses[$key] );
			}
		}
		
		update_post_meta( $order_id, 'shipment_status', $shipment_statuses );
		
		return;
		
	}
	
	public function register_metabox() {
		if ( ! trackship_for_woocommerce()->is_ast_active() && trackship_for_woocommerce()->is_st_active() ) {
			add_meta_box( 'trackship', 'TrackShip', array( $this, 'trackship_metabox_cb'), 'shop_order', 'side', 'high' );
		}
	}
	
	public function trackship_metabox_cb( $post ) {
		$order_id = $post->ID;
		$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
		$shipment_status = trackship_for_woocommerce()->actions->get_shipment_status( $order_id );
		?>
		<div id="trackship-tracking-items">
        	<?php foreach ( $tracking_items as $key => $tracking_item ) { ?>
            	<?php
                $tracking_provider = ! empty( $tracking_item['formatted_tracking_provider'] ) ? $tracking_item['formatted_tracking_provider'] : ( !empty( $tracking_item['tracking_provider'] ) ? $tracking_item['tracking_provider'] : $tracking_item['custom_tracking_provider'] ) ;
				
				$tracking_number = $tracking_item['tracking_number'];
				
				$tracking_link = isset( $shipment_status[ $key ]['tracking_page'] ) ?  $shipment_status[ $key ]['tracking_page'] : $tracking_item['formatted_tracking_link'];
				?>
				<div class="ts-tracking-item">
                	<div class="tracking-content">
                    	<div>
                        	<strong><?php esc_html_e( $tracking_provider ); ?></strong> - 
                            <?php if ( $tracking_link ) { ?>
                                <?php 							
                                echo sprintf( '<a href="%s" target="_blank" title="' . esc_attr( __( 'Track Shipment', 'trackship-for-woocommerce' ) ) . '">' . esc_html( $tracking_number ) . '</a>', esc_url( $tracking_link ) ); ?>
                            <?php } else { ?>
                                <span><?php esc_html_e( $tracking_number ); ?></span>
                            <?php } ?>
                        </div>
                        <?php 
						do_action(	'ast_after_tracking_number', $order_id, $tracking_item['tracking_id'] );
						do_action(	'ast_shipment_tracking_end', $order_id, $tracking_item ); 
						?>
                    </div>
					<?php
                        if ( isset( $shipment_status[ $key ]['status_date'] ) ) {
							echo '<small class="last-update-on">';
								esc_html_e( 'Last Update on', 'trackship-for-woocommerce' );
								esc_html_e( date( ' M d, Y', strtotime( $shipment_status[ $key ]['status_date'] ) ) );
							echo '</small>';
                        }
                    ?>
                </div>
			<?php } ?>
        </div>
		<?php
		//echo '<pre>';print_r($tracking_items);echo '</pre>';
		//echo '<pre>';print_r($shipment_status);echo '</pre>';
		wp_enqueue_style( 'trackshipcss' );
		wp_enqueue_script( 'trackship_script' );
		
		//front_style for tracking widget
		wp_register_style( 'front_style',  trackship_for_woocommerce()->plugin_dir_url() . 'assets/css/front.css', array(), trackship_for_woocommerce()->version );
		wp_enqueue_style( 'front_style' );
	}
	
	public function build_html( $template, $data = NULL ) {
		global $wpdb;
		$t = new \stdclass();
		$t->data = $data;
		ob_start();
		include(dirname(__FILE__)."/admin-html/".$template.".phtml");
		$s = ob_get_contents();
		ob_end_clean();
		return $s;
	}
	
	/*
	* Admin Menu add function
	* WC sub menu
	*/
	public function register_woocommerce_menu() {
		add_submenu_page( 'woocommerce', 'TrackShip', 'TrackShip',  'manage_options', 'trackship-for-woocommerce', array( $this, 'settings_page_callback' ) );
	}
	
	/*
	* callback for Settings
	*/
	public function settings_page_callback(){ ?>
		<div class="zorem-layout">
			<?php include 'views/header.php'; ?>
			<?php do_action('ast_settings_admin_notice'); ?>
			<?php include 'views/content.php'; ?>
		</div>
	<?php }
	
	/*
	* get html of fields
	*/
	public function get_html( $arrays ){
		
		$checked = '';
		?>
		<table class="form-table">
			<tbody>
            	<?php foreach( (array)$arrays as $id => $array ){
				
					if($array['show']){
					?>
                	<?php if($array['type'] == 'title'){ ?>
                		<tr valign="top titlerow">
                        	<th colspan="2"><h3><?php echo $array['title']?></h3></th>
                        </tr>    	
                    <?php continue;} ?>
				<tr valign="top" class="<?php echo $array['class']; ?>">
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
							if($id === 'wcast_enable_delivered_email'){
								$wcast_enable_delivered_email = get_option('woocommerce_customer_delivered_order_settings');
								
								if($wcast_enable_delivered_email['enabled'] == 'yes' || $wcast_enable_delivered_email['enabled'] == 1){
									$checked = 'checked';
								} else{
									$checked = '';									
								}
								
							} elseif($id === 'wcast_enable_partial_shipped_email'){
								$wcast_enable_partial_shipped_email = get_option('woocommerce_customer_partial_shipped_order_settings');

								if($wcast_enable_partial_shipped_email['enabled'] == 'yes' || $wcast_enable_partial_shipped_email['enabled'] == 1){
									$checked = 'checked';
								} else{
									$checked = '';									
								}								
							} else{																		
								if(get_option($id)){
									$checked = 'checked';
								} else{
									$checked = '';
								} 
							} 
							
							if(isset($array['disabled']) && $array['disabled'] == true){
								$disabled = 'disabled';
								$checked = '';
							} else{
								$disabled = '';
							}							
							?>
						<input type="hidden" name="<?php echo $id?>" value="0"/>
						<input class="tgl tgl-flat" id="<?php echo $id?>" name="<?php echo $id?>" type="checkbox" <?php echo $checked ?> value="1" <?php echo $disabled; ?>/>
						<label class="tgl-btn" for="<?php echo $id?>"></label>	
                        
						<?php } elseif( isset( $array['type'] ) && $array['type'] == 'dropdown' ){?>
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
                                    <?php } ?>
								</select>
							</fieldset>
                        <?php } elseif( isset( $array['type'] ) && $array['type'] == 'radio' ){ ?>                        	
                        	<fieldset>
								<?php foreach((array)$array['options'] as $key => $val ){
									$selected = '';									
									if( get_option($id,$array['default']) == (string)$key )$selected = 'checked'; ?>
									<span class="radio_section">
										<label class="" for="<?php echo $id?>_<?php echo $key?>">												
											<input type="radio" id="<?php echo $id?>_<?php echo $key?>" name="<?php echo $id?>" class="<?php echo $id?>"  value="<?php echo $key?>" <?php echo $selected?>/>
											<span class=""><?php echo $val; ?></span>	
											</br>
										</label>																		
									</span></br>	
                                <?php } ?>								
							</fieldset>
                        <?php } elseif( $array['type'] == 'key_field' ){ ?>
							<fieldset>                                
								<?php if($array['connected'] == true){ ?>
									<a href="https://my.trackship.info/" target="_blank">
										<span class="api_connected"><label><?php _e( 'Connected', 'trackship-for-woocommerce' ); ?></label><span class="dashicons dashicons-yes"></span></span></a>
								<?php } ?>								
                            </fieldset>
						<?php }
						elseif( $array['type'] == 'label' ){ ?>
							<fieldset>
                               <label><?php echo $array['value']; ?></label>
                            </fieldset>
						<?php }
						elseif( $array['type'] == 'tooltip_button' ){ ?>
							<fieldset>
								<a href="<?php echo $array['link']; ?>" class="button-primary" target="<?php echo $array['target'];?>"><?php echo $array['link_label'];?></a>
                            </fieldset>
						<?php }
						elseif( $array['type'] == 'button' ){ ?>
							<fieldset>
								<button class="button-primary btn_green2 <?php echo $array['button_class'];?>" <?php if($array['disable']  == 1){ echo 'disabled'; }?>><?php echo $array['label'];?></button>
							</fieldset>
						<?php }
						else { ?>
                                                    
                        	<fieldset>
                                <input class="input-text regular-input " type="text" name="<?php echo $id?>" id="<?php echo $id?>" style="" value="<?php echo get_option($id)?>" placeholder="<?php if(!empty($array['placeholder'])){echo $array['placeholder'];} ?>">
                            </fieldset>
                        <?php } ?>
                        
					</td>
				</tr>
				<?php if(isset($array['desc']) && $array['desc'] != ''){ ?>
					<tr class="<?php echo $array['class']; ?>"><td colspan="2" style=""><p class="description"><?php echo (isset($array['desc']))? $array['desc']: ''?></p></td></tr>
				<?php } ?>				
	<?php } } ?>
			</tbody>
		</table>
	<?php 
	}
	
	/*
	* get html of fields
	*/
	public function get_html_2( $arrays ){
		
		$checked = '';
		?>
		<table class="form-table table-layout-2">
			<tbody>
            	<?php foreach( (array)$arrays as $id => $array ){
				
				if($array['show']){ ?>                						
				<tr valign="top" class="<?php echo $array['class']; ?>">
				
					<th scope="row" class="titledesc"  <?php if($array['type'] == 'desc'){ ?> colspan=2 <?php } ?>>
                    	<?php if( $array['type'] == 'checkbox' ){								
							if($id === 'wcast_enable_delivered_email'){
								$wcast_enable_delivered_email = get_option('woocommerce_customer_delivered_order_settings');
								
								if($wcast_enable_delivered_email['enabled'] == 'yes' || $wcast_enable_delivered_email['enabled'] == 1){
									$checked = 'checked';
								} else{
									$checked = '';									
								}
								
							} elseif($id === 'wcast_enable_partial_shipped_email'){
								$wcast_enable_partial_shipped_email = get_option('woocommerce_customer_partial_shipped_order_settings');

								if($wcast_enable_partial_shipped_email['enabled'] == 'yes' || $wcast_enable_partial_shipped_email['enabled'] == 1){
									$checked = 'checked';
								} else{
									$checked = '';									
								}								
							} else{																		
								if(get_option($id)){
									$checked = 'checked';
								} else{
									$checked = '';
								} 
							} 
							
							if(isset($array['disabled']) && $array['disabled'] == true){
								$disabled = 'disabled';
								$checked = '';
							} else{
								$disabled = '';
							}							
							?>
							<input type="hidden" name="<?php echo $id?>" value="0"/>
							<input class="tgl tgl-flat" id="<?php echo $id?>" name="<?php echo $id?>" type="checkbox" <?php echo $checked ?> value="1" <?php echo $disabled; ?>/>
							<label class="tgl-btn" for="<?php echo $id?>"></label>
						
                        <?php } elseif( isset( $array['type'] ) && $array['type'] == 'dropdown' ){?>
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
                                    <?php } ?>
								</select>
							</fieldset>
                        <?php }						
						elseif( $array['type'] == 'label' ){ ?>
							<fieldset>
                               <label><?php echo $array['value']; ?></label>
                            </fieldset>
						<?php }
						elseif( $array['type'] == 'tooltip_button' ){ ?>
							<fieldset>
								<a href="<?php echo $array['link']; ?>" class="button-primary" target="<?php echo $array['target'];?>"><?php echo $array['link_label'];?></a>
                            </fieldset>
						<?php }
						elseif( $array['type'] == 'button' ){ ?>
							<fieldset>
								<button class="button-primary btn_green2 <?php echo $array['button_class'];?>" <?php if($array['disable']  == 1){ echo 'disabled'; }?>><?php echo $array['label'];?></button>
							</fieldset>
						<?php }
						else { ?>
                                                    
                        	<fieldset>
                                <input class="input-text regular-input " type="text" name="<?php echo $id?>" id="<?php echo $id?>" style="" value="<?php echo get_option($id)?>" placeholder="<?php if(!empty($array['placeholder'])){echo $array['placeholder'];} ?>">
                            </fieldset>
                        <?php } ?>
                        
					</th>
					<?php if($array['type'] != 'desc'){ ?>										
					<th class="forminp">
						<label for=""><span><?php echo $array['title']?><?php if( isset($array['tooltip']) ){?>
                            	<span class="woocommerce-help-tip tipTip" title="<?php echo $array['tooltip']?>"></span>
                            <?php } ?></span><span class="html2_title1"><?php echo $array['title1']?></span></label>						
					</th>
					<?php } ?>
				</tr>
				<?php if(isset($array['desc']) && $array['desc'] != ''){ ?>
					<tr class="<?php echo $array['class']; ?>"><td colspan="2" style=""><p class="description"><?php echo (isset($array['desc']))? $array['desc']: ''?></p></td></tr>
				<?php } ?>				
	<?php } } ?>
			</tbody>
		</table>
	<?php 
	}
	
	/*
	* get html of fields
	*/
	public function get_html_ul( $arrays ){ ?>
		<ul class="settings_ul">
		<?php foreach( (array)$arrays as $id => $array ){ ?>
        	<?php
			if($array['show']){ 
				if( $array['type'] == 'checkbox' ){
					if(get_option($id)){
						$checked = 'checked';
					} else{
						$checked = '';
					}
				?>
					<li>
						<input type="hidden" name="<?php echo $id?>" value="0"/>
						<input class="" id="<?php echo $id?>" name="<?php echo $id?>" type="checkbox" <?php echo $checked ?> value="1"/>
											
						<label class="setting_ul_checkbox_label"><?php echo $array['title']?>
						<?php if( isset($array['tooltip']) ){?>
							<span class="woocommerce-help-tip tipTip" title="<?php echo $array['tooltip']?>"></span>
						<?php } ?>
						</label>						
					</li>	
				<?php } else if( $array['type'] == 'tgl_checkbox' ){
					if(get_option($id)){
						$checked = 'checked';
					} else{
						$checked = '';
					}
					$tgl_class = '';	
					if( isset($array['tgl_color']) ){
						$tgl_class = 'ast-tgl-btn-green';
					} ?>
					<li>
						<input type="hidden" name="<?php echo $id?>" value="0"/>
						<input class="ast-tgl ast-tgl-flat" id="<?php echo $id?>" name="<?php echo $id?>" type="checkbox" <?php echo $checked ?> value="1"/>
						<label class="ast-tgl-btn <?php echo $tgl_class; ?>" for="<?php echo $id?>"></label>
											
						<label class="setting_ul_tgl_checkbox_label"><?php echo $array['title']?>
						<?php if( isset($array['tooltip']) ){?>
							<span class="woocommerce-help-tip tipTip" title="<?php echo $array['tooltip']?>"></span>
						<?php } ?>
						</label>
						<?php if( isset($array['customize_link']) ){?>
							<a href="<?php echo $array['customize_link']?>" class="button-primary btn_outline"><?php _e( 'Customize', 'trackship-for-woocommerce' ); ?></a>	
						<?php } ?>
					</li>	
				<?php } else if( $array['type'] == 'radio' ){ ?>
					<li class="settings_radio_li">
						<label><strong><?php echo $array['title']?></strong>
							<?php if( isset($array['tooltip']) ){?>
								<span class="woocommerce-help-tip tipTip" title="<?php echo $array['tooltip']?>"></span>
							<?php } ?>
						</label>	
						<?php foreach((array)$array['options'] as $key => $val ){
							$selected = '';									
							if( get_option($id,$array['default']) == (string)$key )$selected = 'checked'; ?>
							<span class="radio_section">
								<label class="" for="<?php echo $id?>_<?php echo $key?>">												
									<input type="radio" id="<?php echo $id?>_<?php echo $key?>" name="<?php echo $id?>" class="<?php echo $id?>"  value="<?php echo $key?>" <?php echo $selected?>/>
									<span class=""><?php echo $val; ?></span>	
									</br>
								</label>																		
							</span>
                        <?php } ?>
					</li>					
				<?php } else if($array['type'] == 'multiple_select'){ ?>
					<li class="multiple_select_li">
						<label><?php echo $array['title']?>
							<?php if( isset($array['tooltip']) ){?>
								<span class="woocommerce-help-tip tipTip" title="<?php echo $array['tooltip']?>"></span>
							<?php } ?>
						</label>
						<div class="multiple_select_container">	
							<select multiple class="wc-enhanced-select" name="<?php echo $id?>[]" id="<?php echo $id?>">
							<?php foreach((array)$array['options'] as $key => $val ){ 
								$multi_checkbox_data = get_option($id);
								$selected = in_array( $key, $multi_checkbox_data ) ? 'selected' : '';
								?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php echo $selected; ?>><?php echo $val; ?></option>
							<?php } ?>
							</select>	
						</div>
					</li>	
				<?php } else if ( $array['type'] == 'dropdown_tpage' ) { ?>
					<li class="li_<?php esc_html_e( $id ); ?>">
						<label class="left_label"><?php echo $array['title']?>
							<?php if( isset($array['tooltip']) ){?>
								<span class="woocommerce-help-tip tipTip" title="<?php echo $array['tooltip']?>"></span>
							<?php } ?>
						</label>
                        <span style="display: inline-block;vertical-align: top;">
                            <select class="select select2 tracking_page_select" id="<?php echo $id?>" name="<?php echo $id?>">
                                <?php
                                    foreach((array)$array['options'] as $page_id => $page_name){ ?>
                                        <option <?php if(get_option( $id ) == $page_id){ echo 'selected'; }?> value="<?php echo $page_id; ?>"><?php echo $page_name; ?></option>
                                    <?php } ?>
                                    <option <?php if(get_option( $id ) == 'other'){ echo 'selected'; }?> value="other"><?php _e( 'Other', 'trackship-for-woocommerce' ); ?></option>	
                            </select>
                            <fieldset style="<?php if(get_option( $id ) != 'other'){ echo 'display:none;'; }?>" class="trackship_other_page_fieldset">
                                <input type="text" name="wc_ast_trackship_other_page" id="wc_ast_trackship_other_page" value="<?php echo get_option('wc_ast_trackship_other_page'); ?>">
                            </fieldset>
                            <p class="tracking_page_desc"><?php esc_html_e( 'Add the [wcast-track-order] shortcode in the selected page.', 'trackship-for-woocommerce' ); ?> <a href="https://www.zorem.com/docs/woocommerce-advanced-shipment-tracking/integration/" target="blank"><?php esc_html_e( 'More info', 'trackship-for-woocommerce' ); ?></a></p>
						</span>	
					</li>	
				<?php }  else if($array['type'] == 'button'){ ?>
					<li>
						<label class="left_label"><?php echo $array['title']?>
							<?php if( isset($array['tooltip']) ){?>
								<span class="woocommerce-help-tip tipTip" title="<?php echo $array['tooltip']?>"></span>
							<?php } ?>
						</label>	
						<?php if( isset($array['customize_link']) ){?>
							<a href="<?php echo $array['customize_link']?>" class="button-primary btn_ts_sidebar ts_customizer_btn"><?php _e( 'Customize', 'trackship-for-woocommerce' ); ?></a>	
						<?php } ?>	
					</li>	
				
				<?php } elseif( isset( $array['type'] ) && $array['type'] == 'dropdown' ){?>
					<?php
                        $field_id = $id
                    ?>
                    <li>
						<label class="left_label"><?php echo $array['title']?>
							<?php if( isset($array['tooltip']) ){?>
								<span class="woocommerce-help-tip tipTip" title="<?php echo $array['tooltip']?>"></span>
							<?php } ?>
						</label>
                        <fieldset style="display: inline-block;vertical-align: top;">
                            <select class="select select2" id="<?php echo $field_id?>" name="<?php echo $id?>" >
                            	<?php foreach((array)$array['options'] as $key => $val ){?>
                                    <?php
                                        $selected = '';
                                        if( isset($array['multiple']) ){
                                            if (in_array($key, (array)$this->data->$field_id ))$selected = 'selected';
                                        } else {
                                            if( get_option($id) == (string)$key )$selected = 'selected';
                                        }
                                    
                                    ?>
                                    <option value="<?php echo $key?>" <?php echo $selected?> ><?php echo $val?></option>
                                <?php } ?>
                            </select>
                        </fieldset>
					</li>
				<?php }	
			}
		} ?>                
		</ul>
	<?php }	
	
	/*
	* get settings tab array data
	* return array
	*/
	function get_trackship_general_data(){
		$order_statuses = wc_get_order_statuses();
		
		$status_array = array();
		foreach ( $order_statuses as $key => $val ) {

			if( $key == 'wc-cancelled' )continue;
			if( $key == 'wc-failed' )continue;
			if( $key == 'wc-pending' )continue;

			$status_slug = ( 'wc-' === substr( $key, 0, 3 ) ) ? substr( $key, 3 ) : $key;
			$status_array[$status_slug] = $val;
		}
		
		$form_data = array(
			'trackship_trigger_order_statuses' => array(
				'type'		=> 'multiple_select',
				'title'		=> __( 'Order statuses to trigger TrackShip ', 'trackship-for-woocommerce' ),
				'tooltip'	=> __( 'Choose on which order emails to include the shipment tracking info', 'trackship-for-woocommerce' ),
				'options'   => $status_array,					
				'show'		=> true,
				'class'     => '',
			),
			'wc_ast_show_shipment_status_filter' => array(
				'type'		=> 'checkbox',
				'title'		=> __( 'Display Shipment Status Filter on Orders admin', 'trackship-for-woocommerce' ),				
				'show'		=> true,
				'class'     => '',				
			),
		);
		return $form_data;
	}

	/*
	* get completed order with tracking that not sent to TrackShip
	* return number
	*/
	function completed_order_with_tracking(){
		// Get orders completed.
		$args = array(
			'status' => 'wc-completed',
			'limit'	 => 100,	
			'date_created' => '>' . ( time() - 2592000 ),
		);
		
		$orders = wc_get_orders( $args );
		
		$completed_order_with_tracking = 0;
		
		foreach($orders as $order){
			$order_id = $order->get_id();
			
			if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
				$tracking_items = get_post_meta( $order_id, '_wc_shipment_tracking_items', true );			
			} else {				
				$tracking_items = $order->get_meta( '_wc_shipment_tracking_items', true );			
			}
			
			if($tracking_items){
				$shipment_status = get_post_meta( $order_id, "shipment_status", true);
				foreach ( $tracking_items as $key => $tracking_item ) { 				
					if( !isset($shipment_status[$key]) ){						
						$completed_order_with_tracking++;		
					}
				}									
			}			
		}
		return $completed_order_with_tracking;
	}
	
	/*
	* get completed order with Trackship Balance 0 status
	* return number
	*/
	function completed_order_with_zero_balance(){
		
		// Get orders completed.
		$args = array(
			'status' => 'wc-completed',
			'limit'	 => 100,	
			'date_created' => '>' . ( time() - 2592000 ),
		);		
		
		$orders = wc_get_orders( $args );
		
		$completed_order_with_zero_balance = 0;
		
		foreach($orders as $order){
			$order_id = $order->get_id();
			
			if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
				$tracking_items = get_post_meta( $order_id, '_wc_shipment_tracking_items', true );			
			} else {				
				$tracking_items = $order->get_meta( '_wc_shipment_tracking_items', true );			
			}
			
			if($tracking_items){				
				$shipment_status = get_post_meta( $order_id, "shipment_status", true);				
				foreach ( $tracking_items as $key => $tracking_item ) { 					
					if(isset($shipment_status[$key]['status']) && $shipment_status[$key]['status'] == 'TrackShip balance is 0'){
						$completed_order_with_zero_balance++;		
					}
				}									
			}			
		}				
		return $completed_order_with_zero_balance;
	}
	
	/*
	* get completed order with Trackship connection issue status
	* return number
	*/
	function completed_order_with_do_connection(){
		
		// Get orders completed.
		$args = array(
			'status' => 'wc-completed',
			'limit'	 => 100,	
			'date_created' => '>' . ( time() - 2592000 ),
		);		
		
		$orders = wc_get_orders( $args );
		
		$completed_order_with_do_connection = 0;
		
		foreach($orders as $order){
			$order_id = $order->get_id();
			
			if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
				$tracking_items = get_post_meta( $order_id, '_wc_shipment_tracking_items', true );			
			} else {				
				$tracking_items = $order->get_meta( '_wc_shipment_tracking_items', true );			
			}
			
			if($tracking_items){				
				$shipment_status = get_post_meta( $order_id, "shipment_status", true);				
				foreach ( $tracking_items as $key => $tracking_item ) { 					
					if(isset($shipment_status[$key]['pending_status']) && $shipment_status[$key]['pending_status'] == 'TrackShip connection issue'){
						$completed_order_with_do_connection++;		
					}
				}									
			}			
		}				
		return $completed_order_with_do_connection;
	}
	
	/*
     * get_zorem_pluginlist
     * 
     * return array
    */
    public function get_zorem_pluginlist(){
        
        if ( !empty( $this->zorem_pluginlist ) ) return $this->zorem_pluginlist;
        
        if ( false === ( $plugin_list = get_transient( 'zorem_pluginlist' ) ) ) {
            
            $response = wp_remote_get( 'https://www.zorem.com/wp-json/pluginlist/v1/' );
            
            if ( is_array( $response ) && ! is_wp_error( $response ) ) {
                $body    = $response['body']; // use the content
                $plugin_list = json_decode( $body );
                set_transient( 'zorem_pluginlist', $plugin_list, 60*60*24 );
            } else {
                $plugin_list = array();
            }
        }
        return $this->zorem_pluginlist = $plugin_list;
    }	
	
	/** 
	* Register new status : Delivered
	**/
	function register_order_status() {						
		register_post_status( 'wc-delivered', array(
			'label'                     => __( 'Delivered', 'trackship-for-woocommerce' ),
			'public'                    => true,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list'    => true,
			'exclude_from_search'       => false,
			'label_count'               => _n_noop( 'Delivered <span class="count">(%s)</span>', 'Delivered <span class="count">(%s)</span>', 'trackship-for-woocommerce' )
		) );		
	}
	
	/*
	* add status after completed
	*/
	function add_delivered_to_order_statuses( $order_statuses ) {							
		$new_order_statuses = array();
		foreach ( $order_statuses as $key => $status ) {
			$new_order_statuses[ $key ] = $status;
			if ( 'wc-completed' === $key ) {
				$new_order_statuses['wc-delivered'] = __( 'Delivered', 'trackship-for-woocommerce' );				
			}
		}
		
		return $new_order_statuses;
	}
	
	/*
	* Adding the custom order status to the default woocommerce order statuses
	*/
	function include_custom_order_status_to_reports( $statuses ){
		if($statuses)$statuses[] = 'delivered';		
		return $statuses;
	}
	
	/*
	* mark status as a paid.
	*/
	function delivered_woocommerce_order_is_paid_statuses( $statuses ) { 
		$statuses[] = 'delivered';		
		return $statuses; 
	}
	
	/*
	* add bulk action
	* Change order status to delivered
	*/
	function add_bulk_actions( $bulk_actions ){
		$lable = wc_get_order_status_name( 'delivered' );
		$bulk_actions['mark_delivered'] = __( 'Change status to '.$lable.'', 'trackship-for-woocommerce' );	
		return $bulk_actions;		
	}
	
	/*
	* add order again button for delivered order status	
	*/
	function add_reorder_button_delivered( $statuses ){
		$statuses[] = 'delivered';
		return $statuses;	
	}

	/*
	* change style of delivered order label
	*/	
	function footer_function(){
		if ( !is_plugin_active( 'woocommerce-order-status-manager/woocommerce-order-status-manager.php' ) ) {
			$bg_color = get_option('wc_ast_status_label_color','#c9d7e1');
			$color = get_option('wc_ast_status_label_font_color','#000');
			?>
			<style>
			.order-status.status-delivered,.status-label-li .order-label.wc-delivered{
				background: <?php echo $bg_color; ?>;
				color: <?php echo $color; ?>;
			}		
			</style>
			<?php
		}
		echo '<div id=admin_tracking_widget class=popupwrapper style="display:none;"><div class=popuprow></div><div class=popupclose></div></div>';
	}

	/*
	* get formated tracking url form tracking number and provider
	*/	
	public function get_formated_tracking_url( $order_id, $item, $shipment_status ){
		$link_format = '';
		
		$order = wc_get_order( $order_id );
		
		$tracking_page = get_option('wc_ast_trackship_page_id');
		$wc_ast_api_key = get_option('wc_ast_api_key');
		$use_tracking_page = get_option('wc_ast_use_tracking_page');
		
		if( $wc_ast_api_key && $use_tracking_page && $shipment_status != 'carrier_unsupported'){		
			$order_key = $order->get_order_key();				
			if($tracking_page == 'other'){
				$trackship_other_page = get_option('wc_ast_trackship_other_page');
				$link_format = $trackship_other_page.'?order_id='.$order_id.'&order_key='.$order_key;
			} else{
				$link_format = get_permalink( $tracking_page ).'?order_id='.$order_id.'&order_key='.$order_key;	
			}
			
		}
		return $link_format;
	}
	
	/*
	* Return trackship provider name from provider slug
	*/
	public function trackship_get_provider_name_from_slug_callback( $provider ){
		
		global $wpdb;
		
		$install = WC_Trackship_Install::get_instance();
		$ts_shippment_provider = $wpdb->get_results( "SELECT * FROM $install->table where ts_slug = '$provider'" );	
		if($ts_shippment_provider){
			$provider = $ts_shippment_provider[0]->provider_name;	
		}
		
		return $provider;
	}
	
	public function get_trackship_provider() {
		
		global $wpdb;
		return $ts_shippment_providers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}trackship_shipping_provider" );
		
	}
	
	/*
	* Return add maping table row
	*/
	public function add_trackship_mapping_row() {		
		
		$ts_shippment_providers = $this->get_trackship_provider();
		
		ob_start(); ?>
		<tr>
			<td><input type="text" class="map_shipping_provider_text" name="detected_provider[]"></td>
			<td>
				<select name="ts_provider[]" class="select2">
					<option value=""><?php _e( 'Select', 'woocommerce' ); ?></option>
					<?php foreach( $ts_shippment_providers as $ts_provider ) { ?>
						<option value="<?php echo $ts_provider->ts_slug; ?>"><?php echo $ts_provider->provider_name; ?></option>	
					<?php } ?>
				</select>
				<span class="dashicons dashicons-no-alt remove_custom_maping_row"></span>
			</td>
		</tr>
		
		<?php 
		$html = ob_get_clean();	
		wp_send_json( array( 'table_row' => $html) );
	}
	
	/*
	* Save Custom Mapping data
	*/
	public function trackship_custom_mapping_form_update(){
		if ( ! empty( $_POST ) && check_admin_referer( 'trackship_mapping_form', 'trackship_mapping_form_nonce' ) ) {
			
			$map_provider_array = array();
			if ( !empty( $_POST['detected_provider'] ) ) {
				foreach ( wc_clean( $_POST['detected_provider'] ) as $key => $provider ) {
					if ( isset( $_POST[ 'ts_provider' ][ $key ] ) ) {
						$map_provider_array[$provider] = wc_clean( $_POST['ts_provider'][$key] );
					}
								
				}		
			}
			update_option( 'trackship_map_provider', $map_provider_array );		
			wp_send_json( array( 'success' => 'true' ) );
		}
	}

	public function detect_custom_mapping_provider( $tracking_provider ) {
		$map_provider_array = get_option( 'trackship_map_provider' );
		if ( isset( $map_provider_array[ $tracking_provider ] ) ){
			return $map_provider_array[ $tracking_provider ];
		}
		return $tracking_provider;
	}

	public function get_latest_shipments( $count = 5 ){
		global $wpdb;
		$order_query = "
			SELECT 				
				posts.post_status as ordr_status,  				
				shipping_country_meta.meta_value as shipping_country,
				shipment_tracking_items.meta_value as shipment_tracking_items,
				shipment_status.meta_value as shipment_status,				
				posts.ID AS ID
				
				FROM    {$wpdb->posts} AS posts				
				LEFT JOIN {$wpdb->postmeta} AS shipping_country_meta ON(posts.ID = shipping_country_meta.post_id)
				LEFT JOIN {$wpdb->postmeta} AS shipment_tracking_items ON(posts.ID = shipment_tracking_items.post_id)
				LEFT JOIN {$wpdb->postmeta} AS shipment_status ON(posts.ID = shipment_status.post_id)				
			WHERE 
				posts.post_status IN ('wc-completed')
				AND posts.post_type IN ( 'shop_order' )								
				AND shipping_country_meta.meta_key IN ( '_shipping_country')
				AND shipment_tracking_items.meta_key IN ( '_wc_shipment_tracking_items')
				AND shipment_tracking_items.meta_key IS NOT NULL
				AND shipment_status.meta_key IN ( 'shipment_status')																
			ORDER BY
				posts.ID DESC
		";
		
		$orders_data = $wpdb->get_results($order_query,ARRAY_A);
		
		$result = array();
		$i = 0;
		$total_data = 1;
		
		foreach( $orders_data as $key => $order ){			
			if( $total_data  > $count ) break;	
			if(is_array(unserialize($order['shipment_tracking_items']))){
				$tracking_items = unserialize($order['shipment_tracking_items']);
				
				$shipment_status = unserialize($order['shipment_status']);			
				foreach( (array)$tracking_items as $key => $item ){

					$date_shipped = '';
					if(!empty($item['date_shipped'])){
						$date_shipped = date_i18n('Y-m-d', $item['date_shipped'] );
					}
					
					if( !empty($_POST['shipping_date']) ){
						$shipping_date = explode(" - ", $_POST['shipping_date'] );
						
						if (!(($date_shipped >= $shipping_date['0']) && ($date_shipped <= $shipping_date['1']))){
							continue;
						}
					}
					$result[$i] = new \stdClass();
					$result[$i]->order_id = $order['ID'];
					$result[$i]->order_number = apply_filters( 'woocommerce_order_number', $order['ID'], wc_get_order( $order['ID'] ) );
					$result[$i]->ordr_status = wc_get_order_status_name( $order['ordr_status'] );
					$result[$i]->tracking_number = $item['tracking_number'];
					$result[$i]->tracking_provider = $item['tracking_provider'];
					//echo '<pre>';print_r($shipment_status);echo '</pre>';
					$result[$i]->shipment_status = '';
					if(isset($shipment_status[$key]['pending_status'])){
						$result[$i]->shipment_status = apply_filters("trackship_status_filter",$shipment_status[$key]['pending_status']);	
					} elseif(isset($shipment_status[$key]['status'])){						
						$result[$i]->shipment_status = apply_filters("trackship_status_filter",$shipment_status[$key]['status']);
					}
					
					$result[$i]->shipment_status_id = '';
					if(isset($shipment_status[$key]['pending_status'])){
						$result[$i]->shipment_status_id = $shipment_status[$key]['pending_status'];
					} elseif(isset($shipment_status[$key]['status'])){	
						$result[$i]->shipment_status_id = $shipment_status[$key]['status'];
					}
															
					
					
					if( trackship_for_woocommerce()->is_ast_active() ){
						$ast = WC_Advanced_Shipment_Tracking_Actions::get_instance();
						$formated = $ast->get_formatted_tracking_item( $order['ID'], $item );
						$result[$i]->tracking_url = $formated['formatted_tracking_link'];
						$result[$i]->formated_tracking_provider = $formated['formatted_tracking_provider'];	
					} else{
						$admin = WC_Trackship_Admin::get_instance();
						$result[$i]->tracking_url = $admin->get_formated_tracking_url( $order['ID'], $item, $result[$i]->shipment_status );
						$result[$i]->formated_tracking_provider = $item['tracking_provider'];	
					}	
					
					$wp_date_format = get_option( 'date_format' );
					if($wp_date_format == 'd/m/Y'){
						$date_format = 'd/m/Y'; 
					} else{
						$date_format = 'm/d/Y';
					}
					
					if(!empty($shipment_status[$key]['est_delivery_date'])){
						$result[$i]->est_delivery_date = date_i18n( $date_format, strtotime($shipment_status[$key]['est_delivery_date']) );
					} else{
						$result[$i]->est_delivery_date = '';
					}															
										
					if(!empty($item['date_shipped'])){
						$result[$i]->et_shipped_at = date_i18n( $date_format, $item['date_shipped'] );						
					} else{
						$result[$i]->et_shipped_at = '';
					}
					
					if(!empty($shipment_status[$key]['status_date'])){
						$result[$i]->ep_updated_at = date_i18n( $date_format, strtotime($shipment_status[$key]['status_date']) );
					} else{
						$result[$i]->ep_updated_at = '';
					}	
					
					$result[$i]->ship_to = WC()->countries->countries[ $order['shipping_country'] ];	
					$result[$i]->action = '';			
					$i++;
					$total_data++;
				}				
			}			
		}
		
		return $result;
	}

	public function get_tracking_analytics_overview(){
		global $wpdb;		
		$paid_order_statuses =  array('completed','delivered','shipped');
		
		$end_date = date('Y-m-d', strtotime('today - 30 days'));
		$start_date = date('Y-m-d');				
		
		global $wpdb;		
		$paid_order_statuses =  array('completed','delivered','shipped');				

		$order_query = "
			SELECT 				
				posts.post_status as ordr_status,  								
				shipment_tracking_items.meta_value as shipment_tracking_items,
				shipment_status.meta_value as shipment_status,				
				posts.ID AS ID
				
				FROM    {$wpdb->posts} AS posts								
				LEFT JOIN {$wpdb->postmeta} AS shipment_tracking_items ON(posts.ID = shipment_tracking_items.post_id)
				LEFT JOIN {$wpdb->postmeta} AS shipment_status ON(posts.ID = shipment_status.post_id)				
			WHERE 
				posts.post_status IN ( 'wc-" . implode( "','wc-", $paid_order_statuses ) . "' )
				AND posts.post_type IN ( 'shop_order' )											
				AND shipment_tracking_items.meta_key IN ( '_wc_shipment_tracking_items')
				AND shipment_tracking_items.meta_key IS NOT NULL	
				AND shipment_status.meta_key IN ( 'shipment_status')	
				AND post_date < '".$start_date."'
				AND post_date > '".$end_date."'	
				
			ORDER BY
				posts.ID DESC
		";	
		$shipment_status_results = $wpdb->get_results($order_query);			
		$shipment_status = array();
		$shipment_status_merge = array();
		$tracking_item_merge = array();
		
		foreach($shipment_status_results as $order){
			$order_id = $order->ID;														
			$shipment_status = unserialize($order->shipment_status);			
						
			if(is_array($shipment_status)){
				$shipment_status_merge = array_merge($shipment_status_merge, $shipment_status);				
			}					
		}
		
				
		$tracking_issues = 0;
		$active_shipments = 0;
		$delivered_shipments = 0;
		$avg_shipment_days_array = array();
		$avg_shipment_length = '';
		
		foreach($shipment_status_merge as $key => $val){					
			
			$first = ( isset($val['tracking_events']) ) ? reset($val['tracking_events']) : '';
			$first_date = ( isset($first->datetime) ) ? $first->datetime : '';
			
			if( isset( $val['tracking_destination_events'] ) && count( $val['tracking_destination_events'] ) > 0 ){
				$last = end($val['tracking_destination_events']);
			} elseif( isset( $val['tracking_events'] ) ){
				$last = end($val['tracking_events']);
			} else{
				$last = '';
			}   
			$last_date = ( isset($last->datetime) ) ? $last->datetime : '';
			
			$status = isset( $val['status'] ) ? $val['status'] : '';
			
			if( $status != 'delivered' ){
				$last_date = date("Y-m-d H:i:s");
			}
			
			$days = NULL;
			$days = $this->get_num_of_days( $first_date, $last_date );	
			
			$avg_shipment_days_array[] = $days;
			
			$avg_shipment_length = $this->get_average( $avg_shipment_days_array );						
			
			if( $status == 'carrier_unsupported' || $status == 'INVALID_TRACKING_NUM' || $status == 'unknown' || $status == 'wrong_shipping_provider' )$tracking_issues ++;
			if( $status == 'delivered' )$delivered_shipments ++;
			if( $status != 'delivered' )$active_shipments ++;
		}		
			
		$result = array();
		
		$result['total_shipments'] = count($shipment_status_merge);
		$result['tracking_issues'] = $tracking_issues;
		$result['active_shipments'] = $active_shipments;
		$result['delivered_shipments'] = $delivered_shipments;
		$result['avg_shipment_length'] = $avg_shipment_length;
		$result['total_orders'] = count($shipment_status_results);
		return $result;
	}

	/*
	*
	*/
	function get_num_of_days( $first_date, $last_date ){
		$date1 = strtotime($first_date);
		$date2 = strtotime($last_date);
		$diff = abs($date2 - $date1);
		return date( "d", $diff );
	}
	
	function get_average( $array ){
		return round(array_sum($array) / count($array), 2);
	}
	
	/*
	* late shipmenta form save
	*/
	public function ts_late_shipments_email_form_update_callback(){
		if ( ! empty( $_POST ) && check_admin_referer( 'ts_late_shipments_email_form', 'ts_late_shipments_email_form_nonce' ) ) {
			
			$wcast_late_shipments_days = isset( $_POST['wcast_late_shipments_days'] ) ? $_POST['wcast_late_shipments_days'] : '';
			$wcast_late_shipments_email_to = isset( $_POST['wcast_late_shipments_email_to'] ) ? $_POST['wcast_late_shipments_email_to'] : '';			
			$wcast_late_shipments_trigger_alert = isset( $_POST['wcast_late_shipments_trigger_alert'] ) ? $_POST['wcast_late_shipments_trigger_alert'] : '';			
			$wcast_late_shipments_daily_digest_time = isset( $_POST['wcast_late_shipments_daily_digest_time'] ) ? $_POST['wcast_late_shipments_daily_digest_time'] : '';
			$wcast_enable_late_shipments_admin_email = isset( $_POST['wcast_enable_late_shipments_admin_email'] ) ? $_POST['wcast_enable_late_shipments_admin_email'] : '';

			$late_shipments_email_settings = array(
				'wcast_enable_late_shipments_admin_email' => wc_clean($wcast_enable_late_shipments_admin_email),
				'wcast_late_shipments_days' => wc_clean($wcast_late_shipments_days),
				'wcast_late_shipments_email_to' => wc_clean($wcast_late_shipments_email_to),
				'wcast_late_shipments_trigger_alert' => wc_clean($wcast_late_shipments_trigger_alert),
				'wcast_late_shipments_daily_digest_time' => wc_clean($wcast_late_shipments_daily_digest_time),
			);
			
			update_option( 'late_shipments_email_settings', $late_shipments_email_settings );
			
			$Late_Shipments = new WC_TrackShip_Late_Shipments();
			$Late_Shipments->remove_cron();
			$Late_Shipments->setup_cron();
		}
	}
	
	/*
	* get settings tab array data
	* return array
	*/
	function get_delivered_data(){		
		$form_data = array(			
			'wc_ast_status_delivered' => array(
				'type'		=> 'checkbox',
				'title'		=> __( 'Enable custom order status “Delivered"', '' ),				
				'show'		=> true,
				'class'     => '',
			),			
			'wc_ast_status_label_color' => array(
				'type'		=> 'color',
				'title'		=> __( 'Delivered Label color', '' ),				
				'class'		=> 'status_label_color_th',
				'show'		=> true,
			),
			'wc_ast_status_label_font_color' => array(
				'type'		=> 'dropdown',
				'title'		=> __( 'Delivered Label font color', '' ),
				'options'   => array( 
					"" =>__( 'Select', 'woocommerce' ),
					"#fff" =>__( 'Light', '' ),
					"#000" =>__( 'Dark', '' ),
				),			
				'class'		=> 'status_label_color_th',
				'show'		=> true,
			),							
		);
		return $form_data;
	}
	
	/*
	* Trackship Automation form save
	*/	
	public function wc_ast_trackship_automation_form_update(){
		if ( ! empty( $_POST ) && check_admin_referer( 'wc_ast_trackship_automation_form', 'wc_ast_trackship_automation_form_nonce' ) ) {
				
			$data = $this->get_delivered_data();						
			
			foreach( $data as $key => $val ){																							
				if(isset($_POST[ $key ])){						
					update_option( $key, wc_clean($_POST[ $key ]) );
				}
			}
		}
	}
	
	/*
	* get settings tab array data
	* return array
	*/
	function get_tracking_page_data(){		
		$page_list = wp_list_pluck( get_pages(), 'post_title', 'ID' );
		
		$slug = '';
		
		$wc_ast_trackship_page_id = get_option('wc_ast_trackship_page_id');
		$post = get_post($wc_ast_trackship_page_id); 
		if( $post )$slug = $post->post_name;
		
		if($slug != 'ts-shipment-tracking'){
			$page_desc = '';
		} else{
			$page_desc = '';
		}
		
		$ts_tracking_page_customizer = new ts_tracking_page_customizer();
								
		$form_data = array(												
			'wc_ast_trackship_page_id' => array(
				'type'		=> 'dropdown_tpage',
				'title'		=> __( 'Select tracking page', 'trackship-for-woocommerce' ),
				'options'   => $page_list,				
				'show'		=> true,
				'desc'		=> $page_desc,
				'class'     => '',
			),
			'wc_ast_trackship_other_page' => array(
				'type'		=> 'text',
				'title'		=> __( 'Other', '' ),						
				'show'		=> true,				
				'class'     => '',
			),
			'wc_ast_tracking_page_customize_btn' => array(
				'type'		=> 'button',
				'title'		=> __( 'Tracking Widget Customizer', 'trackship-for-woocommerce' ),						
				'show'		=> true,				
				'class'     => '',
				'customize_link' => $ts_tracking_page_customizer->get_customizer_url('ast_tracking_page_section','trackship'),
			),	
		);
		return $form_data;
	}
	
	public function trackship_shipment_status_notifications_data(){
		$notifications_data = array(
			'in_transit' => array(					
				'title'			=> __( 'In Transit', 'trackship-for-woocommerce' ),
				'slug' => 'in-transit',
				'option_name'	=> 'wcast_intransit_email_settings',
				'enable_status_name' => 'wcast_enable_intransit_email',		
				'customizer_url' => tswc_intransit_customizer_email::get_customizer_url('trackship_shipment_status_email','in_transit','trackship'),	
			),
			'on_hold' => array(					
				'title'	=> __( 'On Hold', 'trackship-for-woocommerce' ),
				'slug'  => 'on-hold',
				'option_name'	=> 'wcast_onhold_email_settings',
				'enable_status_name' => 'wcast_enable_onhold_email',		
				'customizer_url' => tswc_onhold_customizer_email::get_customizer_url('trackship_shipment_status_email','on_hold','trackship'),	
			),
			'return_to_sender' => array(					
				'title'	=> __( 'Return To Sender', 'trackship-for-woocommerce' ),
				'slug'  => 'return-to-sender',
				'option_name'	=> 'wcast_returntosender_email_settings',
				'enable_status_name' => 'wcast_enable_returntosender_email',		
				'customizer_url' => tswc_returntosender_customizer_email::get_customizer_url('trackship_shipment_status_email','return_to_sender','trackship'),	
			),
			'available_for_pickup' => array(					
				'title'	=> __( 'Available For Pickup', 'trackship-for-woocommerce' ),
				'slug'  => 'available-for-pickup',
				'option_name'	=> 'wcast_availableforpickup_email_settings',
				'enable_status_name' => 'wcast_enable_availableforpickup_email',		
				'customizer_url' => tswc_availableforpickup_customizer_email::get_customizer_url('trackship_shipment_status_email','available_for_pickup','trackship'),	
			),
			'out_for_delivery' => array(					
				'title'	=> __( 'Out For Delivery', 'trackship-for-woocommerce' ),
				'slug'  => 'out-for-delivery',
				'option_name'	=> 'wcast_outfordelivery_email_settings',
				'enable_status_name' => 'wcast_enable_outfordelivery_email',		
				'customizer_url' => tswc_outfordelivery_customizer_email::get_customizer_url('trackship_shipment_status_email','out_for_delivery','trackship'),	
			),
			'delivered' => array(					
				'title'	=> __( 'Delivered', 'trackship-for-woocommerce' ),
				'slug'  => 'delivered-status',
				'option_name'	=> 'wcast_delivered_email_settings',
				'enable_status_name' => 'wcast_enable_delivered_status_email',		
				'customizer_url' => tswc_delivered_customizer_email::get_customizer_url('trackship_shipment_status_email','delivered','trackship'),	
			),
			'failure' => array(					
				'title'	=> __( 'Failed Attempt', 'trackship-for-woocommerce' ),
				'slug'  => 'failed-attempt',
				'option_name'	=> 'wcast_failure_email_settings',
				'enable_status_name' => 'wcast_enable_failure_email',		
				'customizer_url' => tswc_failure_customizer_email::get_customizer_url('trackship_shipment_status_email','failure','trackship'),	
			),
			'exception' => array(					
				'title'	=> __( 'Exception', 'trackship-for-woocommerce' ),
				'slug'  => 'exception',
				'option_name'	=> 'wcast_exception_email_settings',
				'enable_status_name' => 'wcast_enable_exception_email',		
				'customizer_url' => tswc_exception_customizer_email::get_customizer_url('trackship_shipment_status_email','exception','trackship'),	
			),	
		);
		return $notifications_data;
	}
}
