<?php

/**
 * Plugin Name: Dropsoft
 * Description: Met deze plugin kunt u de Dropsoft instellingen beheren
 * Version: 2.0
*/

// Make sure we don't expose any info if called directly
if( ! defined( 'ABSPATH' ) ) {
  die();
}

// Set DIR
define( 'DROPSOFT_FILE', __FILE__ );

if( ! is_admin() ) {
  require_once dirname( DROPSOFT_FILE ) . '/includes/public.php';
} elseif( ! defined( 'DOING_AJAX' ) ) {
  require_once dirname( DROPSOFT_FILE ) . '/includes/admin.php';
}





