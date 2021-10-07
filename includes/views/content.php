<?php
$menu_tab = isset( $_GET[ 'tab' ] ) ? sanitize_text_field( $_GET[ 'tab' ] ) : 'settings';
?>
<div class="woocommerce trackship_admin_layout">
	<div class="trackship_admin_content" >
		<div class="trackship_nav_div">	
			<?php if ( trackship_for_woocommerce()->is_trackship_connected() ) { ?>
				<?php
				$array = array(
					array(
						'label'	=> __( 'Settings', 'trackship-for-woocommerce' ),
						'slug'	=> 'settings',
						'show'	=> true,
					),
					array(
						'label'	=> __( 'Notifications', 'trackship-for-woocommerce' ),
						'slug'	=> 'notifications',
						'show'	=> true,
					),
					array(
						'label'	=> __( 'Status', 'trackship-for-woocommerce' ),
						'slug'	=> 'status',
						'show'	=> true,
					),
					array(
						'label'	=> __( 'Fulfillment Dashboard', 'trackship-for-woocommerce' ),
						'link'	=> admin_url( 'admin.php?page=trackship-dashboard' ),
						'show'	=> false,
					),
				);
				?>
				<?php foreach ( $array as $key => $val ) { ?>
                	<?php if ( isset( $val[ 'link' ] ) && $val[ 'link' ] ) { ?>
                    	<label class="tab_label"><a href="<?php echo esc_url( $val[ 'link' ] ); ?>"><?php esc_html_e( $val[ 'label' ] ); ?></a></label>
                    <?php } ?>
					<?php if ( $val[ 'show' ] ) { ?>
						<input id="tab_trackship_<?php esc_html_e( $val[ 'slug' ] ); ?>" type="radio" name="tabs" class="tab_input" data-label="<?php esc_html_e( $val[ 'label' ] ); ?>" data-tab="<?php esc_html_e( $val[ 'slug' ] ); ?>" <?php echo $val[ 'slug' ] == $menu_tab ? 'checked' : ''; ?> >
						<label for="tab_trackship_<?php esc_html_e( $val[ 'slug' ] ); ?>" class="tab_label <?php echo 'settings' == $val[ 'slug' ] ? 'first_label' : ''; ?>">
							<?php esc_html_e( $val[ 'label' ] ); ?>
						</label>
					<?php } ?>
				<?php } ?>
				<div class="menu_devider"></div>
				<?php foreach ( $array as $key => $val ) { ?>
					<?php if ( $val[ 'show' ] ) { ?>
						<section id="content_trackship_<?php esc_html_e( $val[ 'slug' ] ); ?>" class="inner_tab_section">
							<div class="tab_inner_container">
								<?php include __DIR__ . '/' . $val[ 'slug' ] . '.php'; ?>
							</div>
						</section>
					<?php } ?>
				<?php } ?>

			<?php
			} else {
				include 'trackship-integration.php';
			}
			?>
		</div>                   					
   </div>				
</div>
