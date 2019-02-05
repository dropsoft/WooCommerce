<?php
/**
 * Plugin Name: Fertiplant Fulfilment importer
 * Description: Met deze plugin kan je via een api producten van Fertiplant Fulfilment importeren
 * Version: 1.0
*/

// Prevent direct file access
if( ! defined( 'ABSPATH' ) ) {
	die();
}

define( 'SCCSS_FILE', __FILE__ );

if( ! is_admin() ) {
	require_once dirname( SCCSS_FILE ) . '/includes/public.php';
} elseif( ! defined( 'DOING_AJAX' ) ) {
	require_once dirname( SCCSS_FILE ) . '/includes/admin.php';
}



add_action('woocommerce_after_checkout_billing_form', 'customise_checkout_field' , 10, 1);
 
function customise_checkout_field($checkout)
{
  echo '<div id="customise_checkout_field">';
  woocommerce_form_field('datepicker', array(
    'type' => 'text',
    'class' => array(
      'my-field-class form-row-wide'
    ) ,
    'label' => __('Delivery date') ,
    'required' => true,
  ) , $checkout->get_value('datepicker'));
  echo '</div>';
}



add_action( 'wp_head', 'my_header_scripts' );
function my_header_scripts(){
  ?>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
  <script type="text/javascript" src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <script type="text/javascript">
  	function noSundays(date) {
      return [date.getDay() != 0, ''];
}
	  	jQuery( function() {
	  		var skipDate = new Date();
            var date = new Date();
            var day = date.getDay();

            var skipDagen =  0;
            if(day == 5){
              skipDagen = 4;
            }else if(day == 6){
              skipDagen = 3
            }else{
              skipDagen = 2;
            }
            skipDate.setDate(skipDate.getDate() + skipDagen);
            normaalDatum = (skipDate.getMonth() + 1) + '/' + skipDate.getDate() + '/' +  skipDate.getFullYear();
        jQuery("#datepicker").val(normaalDatum);
	    	jQuery( "#datepicker" ).datepicker({
				beforeShowDay: noSundays,
				minDate: skipDate,
	       defaultDate: skipDate,
			});
	  } );
  </script>
  <?php
}


add_action('woocommerce_checkout_update_order_meta',function( $order_id, $posted ) {
	$delivery_date = sanitize_text_field( $_POST['datepicker'] );
    $order = wc_get_order( $order_id );
    $order->update_meta_data( '_delivery_date', $delivery_date );
    $order->save();
} , 10, 2);

