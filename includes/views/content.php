<div class="woocommerce trackship_admin_layout">
	<div class="trackship_admin_content" >
		<div class="trackship_nav_div">	
			<?php if( trackship_for_woocommerce()->is_trackship_connected() ) { ?>
				<?php
                $array = array(
                    array(
                        'label'	=> __( 'Dashboard', 'trackship-for-woocommerce' ),
                        'slug'	=> 'dashboard'
                    ),
                    array(
                        'label'	=> __( 'Settings', 'trackship-for-woocommerce' ),
                        'slug'	=> 'settings'
                    ),
                    array(
                        'label'	=> __( 'Tracking Page', 'trackship-for-woocommerce' ),
                        'slug'	=> 'tracking-page'
                    ),
                    array(
                        'label'	=> __( 'Notifications', 'trackship-for-woocommerce' ),
                        'slug'	=> 'notifications'
                    ),
					array(
                        'label'	=> __( 'License' ),
                        'slug'	=> 'license'
                    )
                );
                
                $tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'dashboard';
                
                ?>
                <?php foreach( $array as $key => $val ) { ?>
                
                    <input id="tab_trackship_<?php esc_html_e( $val[ 'slug' ] ); ?>" type="radio" name="tabs" class="tab_input" data-label="<?php esc_html_e( $val[ 'label' ] ); ?>" data-tab="<?php esc_html_e( $val[ 'slug' ] ); ?>" <?php if( $val[ 'slug' ] == $tab ){ esc_html_e( 'checked' ); } ?> >
                    <label for="tab_trackship_<?php esc_html_e( $val[ 'slug' ] ); ?>" class="tab_label">
                        <?php esc_html_e( $val[ 'label' ] ); ?>
                    </label>
                    
				<?php } ?>
                
				<?php foreach( $array as $key => $val ) { ?>
                    <section id="content_trackship_<?php esc_html_e( $val[ 'slug' ] ); ?>" class="inner_tab_section">
                        <div class="tab_inner_container">
                        	<?php include $val[ 'slug' ] . '.php'?>
                        </div>
                    </section>
                <?php } ?>

			<?php } else {
				include 'trackship-integration.php';
			} ?>
					
		</div>                   					
   </div>				
</div>
