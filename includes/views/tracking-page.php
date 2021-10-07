<form method="post" id="trackship_tracking_page_form" action="" enctype="multipart/form-data">
	<div class="outer_form_table">
		<table class="form-table heading-table">
			<tbody>
				<tr valign="top">
                    <td><h1 style=""><?php esc_html_e( 'Tracking Page', 'trackship-for-woocommerce' ); ?></h1></td>						
                </tr>
            </tbody>
		</table>	
		<?php $this->get_html_ul( $this->get_tracking_page_data() ); ?>
        <div class="settings_ul_submit" style="margin-top: 20px;">
            <button name="save" class="button-primary button-trackship btn_large" type="submit" value="Save changes">
                <?php esc_html_e( 'Save Changes', 'trackship-for-woocommerce' ); ?>
            </button>
            <div class="spinner"></div>
			<?php wp_nonce_field( 'trackship_tracking_page_form', 'trackship_tracking_page_form_nonce' ); ?>
            <input type="hidden" name="action" value="trackship_tracking_page_form_update">
        </div>														
	</div>
</form>
