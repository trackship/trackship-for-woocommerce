<?php
/**
 * Skyrocket Customizer Custom Controls
 *
 */
if ( class_exists( 'WP_Customize_Control' ) ) {
	class TrackShip_Heading_Control extends WP_Customize_Control {		

		public function render_content() {
			?>
			<label>
				<h3 class="control_heading"><?php esc_html_e( $this->label, 'trackship-for-woocommerce' ); ?></h3>
				<?php if ( ! empty( $this->description ) ) : ?>
				<span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
				<?php endif; ?>
			</label>
			<?php
		}
	}
		
	class TrackShip_Codeinfoblock_Control extends WP_Customize_Control {		

		public function render_content() {
			?>
			<label>
				<h3 class="customize-control-title"><?php esc_html_e( $this->label, 'trackship-for-woocommerce' ); ?></h3>
				<?php if ( ! empty( $this->description ) ) : ?>
				<span class="description customize-control-description"><?php echo wp_kses_post( $this->description ); ?></span>
				<?php endif; ?>
			</label>
			<?php
		}
	}
	
	/**
	 * Custom Control Base Class
	 *
	 * @link https://github.com/maddisondesigns
	 */
	class TrackShip_Custom_Control extends WP_Customize_Control {
		protected function get_skyrocket_resource_url() {
			if ( strpos( wp_normalize_path( __DIR__ ), wp_normalize_path( WP_PLUGIN_DIR ) ) === 0 ) {
				// We're in a plugin directory and need to determine the url accordingly.
				return plugin_dir_url( __DIR__ );
			}

			return trailingslashit( get_template_directory_uri() );
		}
	}		

	/**
	 * Dropdown Select2 Custom Control
	 *
	 * @link https://github.com/maddisondesigns
	 */
	class TrackShip_Select_Custom_Control extends TrackShip_Custom_Control {
		/**
		 * The type of control being rendered
		 */
		public $type = 'dropdown_select';		
		/**
		 * The Placeholder value to display. Select2 requires a Placeholder value to be set when using the clearall option. Default = 'Please select...'
		 */
		private $placeholder = 'Please select...';
		/**
		 * Constructor
		 */
		public function __construct( $manager, $id, $args = array(), $options = array() ) {
			parent::__construct( $manager, $id, $args );
			// Check if this is a multi-select field
			// Check if a placeholder string has been specified
			if ( isset( $this->input_attrs['placeholder'] ) && $this->input_attrs['placeholder'] ) {
				$this->placeholder = $this->input_attrs['placeholder'];
			}
		}		
		/**
		 * Render the control in the customizer
		 */
		public function render_content() {
			$defaultValue = $this->value();			
			?>
			<div class="dropdown_select_control">
				<?php if ( !empty( $this->label ) ) { ?>
					<label for="<?php echo esc_attr( $this->id ); ?>" class="customize-control-title">
						<?php echo esc_html( $this->label ); ?>
					</label>
				<?php } ?>
				<?php if ( !empty( $this->description ) ) { ?>
					<span class="customize-control-description"><?php echo esc_html( $this->description ); ?></span>
				<?php } ?>				
				<select name="<?php echo esc_attr( $this->id ); ?>" id="<?php echo esc_attr( $this->id ); ?>" <?php $this->link(); ?> class="<?php echo esc_html( $this->input_attrs['class'] ); ?>" data-placeholder="<?php echo esc_html( $this->placeholder ); ?>">
					<?php						
					foreach ( $this->choices as $key => $value ) {	
						echo '<option value="' . esc_attr( $key ) . '" ' . selected( esc_attr( $key ), $defaultValue, false ) . '>' . esc_attr( $value ) . '</option>';
					}	 					
					?>
				</select>
			</div>
		<?php
		}	
	}	

	/**
	 * Slider Custom Control	 
	 */
	class TrackShip_Slider_Custom_Control extends TrackShip_Custom_Control {
		/**
		 * The type of control being rendered
		 */
		public $type = 'slider_control';
		/**
		 * Enqueue our scripts and styles
		 */
		public function enqueue() {
			wp_enqueue_script( 'trackship-custom-controls-js', trackship_for_woocommerce()->plugin_dir_url() . 'assets/js/customizer.js', array( 'jquery', 'jquery-ui-core' ), trackship_for_woocommerce()->version, true );
			wp_enqueue_style( 'trackship-custom-controls-css', trackship_for_woocommerce()->plugin_dir_url() . 'assets/css/customizer.css', array(), trackship_for_woocommerce()->version, 'all' );
		}
		/**
		 * Render the control in the customizer
		 */
		public function render_content() {
			?>
			<div class="slider-custom-control">
				<span class="customize-control-title"><?php esc_html_e( $this->label, 'trackship-for-woocommerce' ); ?></span>				
				<div class="slider" slider-min-value="<?php echo esc_attr( $this->input_attrs['min'] ); ?>" slider-max-value="<?php echo esc_attr( $this->input_attrs['max'] ); ?>" slider-step-value="<?php echo esc_attr( $this->input_attrs['step'] ); ?>">
				</div>				
				<span class="slider-reset dashicons dashicons-image-rotate" slider-reset-value="<?php echo esc_attr( $this->input_attrs['default'] ); ?>"></span>
				<input type="number" id="<?php echo esc_attr( $this->id ); ?>" name="<?php echo esc_attr( $this->id ); ?>" value="<?php echo esc_attr( $this->value() ); ?>" class="customize-control-slider-value" <?php $this->link(); ?> />
			</div>
		<?php
		}
	}	

	/**
	 * TinyMCE Custom Control
	 *
	 * @link https://github.com/maddisondesigns
	 */
	class TrackShip_TinyMCE_Custom_Control extends TrackShip_Custom_Control {	
		/**
		 * The type of control being rendered
		 */
		public $type = 'tinymce_editor';
		/**
		 * Enqueue our scripts and styles
		 */
		public function enqueue() {
			wp_enqueue_script( 'skyrocket-custom-controls-js', trackship_for_woocommerce()->plugin_dir_url() . 'assets/js/customizer.js', array( 'jquery', 'jquery-ui-core' ), trackship_for_woocommerce()->version, true );
			wp_enqueue_style( 'skyrocket-custom-controls-css', trackship_for_woocommerce()->plugin_dir_url() . 'assets/css/customizer.css', array(), trackship_for_woocommerce()->version, 'all' );			
			wp_enqueue_editor();
		}
		/**
		 * Pass our TinyMCE toolbar string to JavaScript
		 */
		public function to_json() {
			parent::to_json();
			$this->json['skyrockettinymcetoolbar1'] = isset( $this->input_attrs['toolbar1'] ) ? esc_attr( $this->input_attrs['toolbar1'] ) : 'bold italic bullist numlist alignleft aligncenter alignright link';
			$this->json['skyrockettinymcetoolbar2'] = isset( $this->input_attrs['toolbar2'] ) ? esc_attr( $this->input_attrs['toolbar2'] ) : '';
			$this->json['skyrocketmediabuttons'] = isset( $this->input_attrs['mediaButtons'] ) && ( true == $this->input_attrs['mediaButtons'] ) ? true : false;
		}
		/**
		 * Render the control in the customizer
		 */
		public function render_content() {
			?>
			<div class="tinymce-control">
				<span class="customize-control-title"><?php esc_html_e( $this->label, 'trackship-for-woocommerce' ); ?></span>
				<?php if ( !empty( $this->description ) ) { ?>
					<span class="customize-control-description"><?php echo esc_html( $this->description ); ?></span>
				<?php } ?>
				<textarea id="<?php echo esc_attr( $this->id ); ?>" placeholder="<?php echo esc_attr( $this->input_attrs['placeholder'] ); ?>" class="" <?php $this->link(); ?>><?php echo esc_attr( $this->value() ); ?></textarea>					
			</div>
		<?php
		}
	}
	
	/**
	 * Text Radio Button Custom Control
	 *
	 * @link https://github.com/maddisondesigns
	 */
	class TrackShip_Text_Radio_Button_Custom_Control extends TrackShip_Custom_Control {
		/**
		 * The type of control being rendered
		 */
		public $type = 'text_radio_button';
		/**
		 * Enqueue our scripts and styles
		 */
		public function enqueue() {
			wp_enqueue_style( 'trackship-custom-controls-css', trackship_for_woocommerce()->plugin_dir_url() . 'assets/css/customizer.css', array(), trackship_for_woocommerce()->version, 'all' );
		}
		/**
		 * Render the control in the customizer
		 */
		public function render_content() {
			?>
			<div class="text_radio_button_control">
				<?php if ( !empty( $this->label ) ) { ?>
					<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<?php } ?>
				<?php if ( !empty( $this->description ) ) { ?>
					<span class="customize-control-description"><?php echo esc_html( $this->description ); ?></span>
				<?php } ?>

				<div class="radio-buttons">
					<?php foreach ( $this->choices as $key => $value ) { ?>
						<label class="radio-button-label">
							<input type="radio" name="<?php echo esc_attr( $this->id ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php $this->link(); ?> <?php checked( esc_attr( $key ), $this->value() ); ?>/>
							<span><?php echo esc_html( $value ); ?></span>
						</label>
					<?php	} ?>
				</div>
			</div>
		<?php
		}
	}
	
	/**
	* Checkbox Custom Control
	**/
	class TrackShip_checkbox_Custom_Control extends TrackShip_Custom_Control {
		
		/**
		 * The type of control being rendered
		 */
		public $type = 'checkbox';		
		
		/**
		 * Constructor
		 */
		public function __construct( $manager, $id, $args = array(), $options = array() ) {
			parent::__construct( $manager, $id, $args );
			// Check if this is a multi-select field
			// Check if a class string has been specified
			if ( isset( $this->input_attrs['class'] ) && $this->input_attrs['class'] ) {
				$this->class1 = $this->input_attrs['class'];
			}
		}		
		/**
		 * Render the control in the customizer
		 */
		public function render_content() {
			$defaultValue = $this->value();			
			?>
            
            <span class="customize-inside-control-row">
                <input
                id="<?php echo esc_attr( $this->id ); ?>"
                class="<?php echo esc_attr( $this->class1 ); ?>"
                type="checkbox"
                value="<?php echo esc_attr( $defaultValue ); ?>"
                <?php $this->link(); ?>
                <?php checked( $defaultValue ); ?>
                />
                <label for="<?php echo esc_attr( $this->id ); ?>"><?php echo esc_html( $this->label ); ?></label>
            </span>
		<?php
		}	
	}
}

/**
 * Radio Button and Select sanitization
 *
 * @param  string		Radio Button value
 * @return integer	Sanitized value
 */
if ( ! function_exists( 'ast_radio_sanitization' ) ) {
	function ast_radio_sanitization( $input, $setting ) {
		//get the list of possible radio box or select options
		$choices = $setting->manager->get_control( $setting->id )->choices;
	
		if ( array_key_exists( $input, $choices ) ) {
			return $input;
		} else {
			return $setting->default;
		}
	}
}
