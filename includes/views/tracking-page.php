<form method="post" id="trackship_tracking_page_form" action="" enctype="multipart/form-data">
	<div class="outer_form_table">
        <table class="form-table heading-table">
            <tbody>
                <tr valign="top">
                    <td>
                        <h3 style=""><?php _e( 'Tracking Page', 'trackship-for-woocommerce' ); ?></h3>
                        <?php wp_nonce_field( 'trackship_tracking_page_form', 'trackship_tracking_page_form_nonce' );?>
                        <input type="hidden" name="action" value="trackship_tracking_page_form_update">
                    </td>							
                </tr>
            </tbody>
        </table>	
        <?php $this->get_html_ul( $this->get_tracking_page_data() ); ?>															
    </div>	
</form>	
