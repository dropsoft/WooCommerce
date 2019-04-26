<?php

// Make sure we don't expose any info if called directly
if( ! defined( 'DROPSOFT_FILE' ) ) {
	die();
}

// Links plugin page
function dropsoft_link( $links ) {
	return array_merge(
		array(
			'settings' => '<a href="' . admin_url( 'themes.php?page=dropsoft.php' ) . '">' . __( 'Instellingen', 'dropsoft' ) . '</a>'
		),
		$links
	);
}
add_filter( 'plugin_action_links_' . plugin_basename( DROPSOFT_FILE ), 'dropsoft_link' );


// Set admin menu link
function dropsoft_register_submenu_page() {
	add_theme_page( __( 'Dropsoft', 'dropsoft' ), __( 'Dropsoft', 'dropsoft' ), 'edit_theme_options', basename( DROPSOFT_FILE ), 'dropsoft_render_submenu_page' );
}
add_action( 'admin_menu', 'dropsoft_register_submenu_page' );


// Removing a named option/value pair from the options database table.
function dropsoft_uninstall() {
	delete_option( 'dropsoft' );
}
register_uninstall_hook( DROPSOFT_FILE, 'dropsoft_uninstall' );


// Wijzigingen opslaan
function dropsoft_register_settings() {
	register_setting( 'dropsoft_group', 'dropsoft' );
}
add_action( 'admin_init', 'dropsoft_register_settings' );


// Render admin page
function dropsoft_render_submenu_page() {

	$options 		= get_option( 'dropsoft' );
	$bearer 		= $options['your-bearer'];
	$margeType 		= $options['marge-type'];
	$marge 			= $options['your-marge'];
	$autoPublish 	= $options['auto-publish'];

	// Update message
	if ( isset( $_GET['settings-updated'] ) ) : ?>
	<div id="message" class="updated"><p><?php _e( 'Instellingen gewijzigd.', 'dropsoft' ); ?></p></div>
	<?php endif; ?>

	<div class="wrap">
		<h2 style="margin-bottom: 1em;"><?php _e( 'Dropsoft', 'dropsoft' ); ?></h2>
		<form name="dropsoft-form" action="options.php" method="post" style="max-width: 900px;" enctype="multipart/form-data">
			<?php settings_fields( 'dropsoft_group' ); ?>
			<div id="template">
				<div style="margin-bottom: 20px;">
					<p style="margin-bottom: 5px; font-weight: 700; font-size: 15px;">Voer uw persoonlijke API bearer hier in:</p>
					<input type="text" style="width: 100%" id="api" name="dropsoft[your-bearer]" value="<?php echo esc_html( $bearer ); ?>">
				</div>
				<div style="margin-bottom: 20px;">
					<p style="margin-bottom: 5px; font-weight: 700; font-size: 15px;">Adviesprijs verhogen:</p>
					<p>In onze API zit standaard een adviesverkoopprijs. Op deze prijs zit al een marge zoals met Dropsoft afgesproken. Wilt u deze prijs toch nog verhogen dan kunt u dat hier doen. Kies 'Percentage' om de prijs met een x percentage te verhogen. Kies 'Fixed' om de prijs met een x vast bedrag te verhogen. Het bedrag of percentage vult u hieronder in bij 'Voer hier uw marge in'.</p>
					<select name="dropsoft[marge-type]" style=" width: 100%; margin-top: 20px;">
						<option value='0' <?php if($margeType == 0){ echo 'selected';} ?>>Disabled</option>
						<option value='1' <?php if($margeType == 1){ echo 'selected';} ?>>Percentage</option>
						<option value='2' <?php if($margeType == 2){ echo 'selected';} ?>>Fixed</option>
					</select>
				</div>
				<div style="margin-bottom: 20px;">
					<p style="margin-bottom: 5px; font-weight: 700; font-size: 15px;">Voer hier uw marge in</p>
					<input type="number" style="width: 100%" id="api" name="dropsoft[your-marge]" min="0" value="<?php echo esc_html( $marge ); ?>">
				</div>
				<div style="margin-bottom: 20px;">
					<p style="margin-bottom: 5px; font-weight: 700; font-size: 15px;">Auto publish products</p>
					<p>Nieuwe producten kunnen wij automatisch publishen voor u. Zo hoeft u enkel producten te selecteren in onze (<a style="text-decoration: underline;" href="https://dropsoft.nl/assortiment" target="_blank">Retailer module</a>) en de producten verschijnen automatisch op uw webshop.</p>
					<select name="dropsoft[auto-publish]" style="width: 100%; margin-top: 20px;">
						<option value='0' <?php if($autoPublish == 0){ echo 'selected';} ?>>Nee</option>
						<option value='1' <?php if($autoPublish == 1){ echo 'selected';} ?>>Ja</option>
					</select>
				</div>

				<div>
					<?php submit_button( __( 'Verzenden', 'dropsoft' ), 'primary', 'submit', true ); ?>
				</div>
					
			</div>
		</form>	
	</div>
<?php
}


