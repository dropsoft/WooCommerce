<?php

// Prevent direct file access
if( ! defined( 'SCCSS_FILE' ) ) {
	die();
}

function fertiplantfulfilment_import() {

	if( ! isset( $_GET['sccss'] ) || intval( $_GET['sccss'] ) !== 1 ) {
		return;
	}

	ob_start();
	header( 'Content-type: text/css' );
	$options     = get_option( 'sccss_settings' );
	$raw_content = isset( $options['sccss-content'] ) ? $options['sccss-content'] : '';
	$content     = wp_kses( $raw_content, array( '\'', '\"' ) );
	$content     = str_replace( '&gt;', '>', $content );
	echo $content;
	die();
}

add_action( 'plugins_loaded', 'fertiplantfulfilment_import' );
