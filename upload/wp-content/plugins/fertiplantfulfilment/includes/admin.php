<?php

// Prevent direct file access
if( ! defined( 'SCCSS_FILE' ) ) {
	die();
}

/*
*
* @since  1.0
* @param  array $links Array of links generated by WP in Plugin Admin page.
* @return array        Array of links to be output on Plugin Admin page.
*/
function fertiplantfulfilment_link( $links ) {
	return array_merge(
		array(
			'settings' => '<a href="' . admin_url( 'themes.php?page=fertiplantfulfilment.php' ) . '">' . __( 'Api code invoeren', 'fertiplantfulfilment' ) . '</a>'
		),
		$links
	);
}
add_filter( 'plugin_action_links_' . plugin_basename( SCCSS_FILE ), 'fertiplantfulfilment_link' );



/**
* Delete Options on Uninstall
*
* @since 1.1
*/
function sccss_uninstall() {
	delete_option( 'fertiplantfulfilment' );
}
register_uninstall_hook( SCCSS_FILE, 'sccss_uninstall' );



/**
 * Register "FertiplantFulfilment" submenu in "Appearance" Admin Menu
 *
 * @since 1.0
 */
function sccss_register_submenu_page() {
	add_theme_page( __( 'Fertiplant Fulfilment Importer', 'fertiplantfulfilment' ), __( 'Fertiplant Fulfilment import', 'fertiplantfulfilment' ), 'edit_theme_options', basename( SCCSS_FILE ), 'sccss_render_submenu_page' );
}
add_action( 'admin_menu', 'sccss_register_submenu_page' );


/**
 * Register settings
 *
 * @since 1.0
 */
function sccss_register_settings() {
	register_setting( 'fertiplantfulfilment_group', 'fertiplantfulfilment' );
}
add_action( 'admin_init', 'sccss_register_settings' );


/**
 * Render Admin Menu page
 *
 * @since 1.0
 */
function sccss_render_submenu_page() {

	$options = get_option( 'fertiplantfulfilment' );
	$content = isset( $options['sccss-content'] ) && ! empty( $options['sccss-content'] ) ? $options['sccss-content'] : __( '/* Voer uw persoonlijke api code hier in */', 'fertiplantfulfilment' );

	if ( isset( $_GET['settings-updated'] ) ) : ?>
		<div id="message" class="updated"><p><?php _e( 'Fertiplant Fulfilment is gewijzigd.', 'fertiplantfulfilment' ); ?></p></div>
	<?php endif; ?>
	<div class="wrap">
		<h2 style="margin-bottom: 1em;"><?php _e( 'Fertiplant Fulfilment product import', 'fertiplantfulfilment' ); ?></h2>
		<form name="sccss-form" action="options.php" method="post" enctype="multipart/form-data">
			<?php settings_fields( 'fertiplantfulfilment_group' ); ?>
			<div id="template">
				<?php do_action( 'sccss-form-top' ); ?>
				<div>
					<input type="text" style="width: 400px" id="api" name="fertiplantfulfilment[sccss-content]" value="<?php echo esc_html( $content ); ?>">
				</div>
				<?php do_action( 'sccss-textarea-bottom' ); ?>
				<div>
					<?php submit_button( __( 'Verzenden', 'fertiplantfulfilment' ), 'primary', 'submit', true ); ?>
				</div>
				<?php do_action( 'sccss-form-bottom' ); ?>
			</div>
		</form>

		<script type="text/javascript">
			jQuery(document).ready(function(){
				var tourl = window.location.host;
				var data = '';
				jQuery('#submit').click(function(){
					var api = jQuery("#api").val();
					jQuery.ajax({
					    url: "http://"+tourl+"/import.php?api="+api+"",
					    success: function(result){}
					});//end ajax
					console.log(api);
				});
			});
		</script>		

	</div>
<?php
}


