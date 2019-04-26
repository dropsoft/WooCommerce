<?php 

	require_once('wp-config.php');

	ini_set('max_execution_time', 60);

	// Make db connection
	$mysqli = new mysqli('localhost', constant('DB_USER'), constant('DB_PASSWORD'), constant('DB_NAME'));
	if ($mysqli->connect_error) {
	    die('Connection Error (' . $mysqli->connect_errno . ') '
	            . $mysqli->connect_error);
	}

	

	// Get plugin settings
	$dropSoftSettings 	= $mysqli->query("SELECT option_value FROM " . $table_prefix . "options WHERE option_name = 'dropsoft'");
	while($settings 	= mysqli_fetch_assoc($dropSoftSettings)){
		$option_value 	= $settings['option_value'];
		$getValues 		= unserialize($option_value);
	}
	$bearer 		= $getValues['your-bearer'];

	$orders = $mysqli->query("SELECT ID FROM " . $table_prefix . "posts WHERE post_type = 'shop_order' AND dropsoft = '0' AND post_status = 'wc-processing'");
	while($orderobject = mysqli_fetch_assoc($orders)){

		$orderId 					= $orderobject['ID'];
		$orderlines['orderlines'] 	= array();
		$arrayCounter 					= 0;

		$itemidquery = $mysqli->query("SELECT order_item_id FROM " . $table_prefix . "woocommerce_order_items WHERE order_id = '".$orderId."' AND order_item_type = 'line_item'");
		while($orderitemidobject = mysqli_fetch_assoc($itemidquery)){
			$orderItemId = $orderitemidobject['order_item_id'];

			$productidquery = $mysqli->query("SELECT meta_value FROM " . $table_prefix . "woocommerce_order_itemmeta WHERE order_item_id = '".$orderItemId."' AND meta_key = '_product_id'");
			while($productidobject = mysqli_fetch_assoc($productidquery)){
				$productId = $productidobject['meta_value'];
			}

			$productidquery = $mysqli->query("SELECT meta_value FROM " . $table_prefix . "woocommerce_order_itemmeta WHERE order_item_id = '".$orderItemId."' AND meta_key = '_qty'");
			while($productidobject = mysqli_fetch_assoc($productidquery)){
				$quantity = $productidobject['meta_value'];
			}

			$checkproduct = $mysqli->query("SELECT ds_product_id FROM " . $table_prefix . "posts WHERE ID = '".$productId."'");
			while($checkproductobject = mysqli_fetch_assoc($checkproduct)){
				$dsProductId = $checkproductobject['ds_product_id'];
			}

			//Dropsoft producten in orderlines zetten
			if($dsProductId != 0){
				$products[$arrayCounter] = array(
		        	'productcode'   => $dsProductId,
		            'amount'    	=> $quantity,
		        );
		    	$arrayCounter++;
			}
		}
		$orderlines['orderlines'] = array_merge($products);

		$clientvalues 		= $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderId."' AND meta_key = '_shipping_first_name'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$firstName 		= $clientobject['meta_value'];
		}
		$clientvalues 		= $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderId."' AND meta_key = '_shipping_last_name'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$lastName 		= $clientobject['meta_value'];
		}
		$clientvalues 		= $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderId."' AND meta_key = '_shipping_address_1'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$street 	= $clientobject['meta_value'];
		}
		$clientvalues 		= $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderId."' AND meta_key = '_shipping_address_2'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$numberAndAddi 	= $clientobject['meta_value'];
		}
		$clientvalues 		= $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderId."' AND meta_key = '_shipping_postcode'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$zipcode 		= $clientobject['meta_value'];
		}
		$clientvalues 		= $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderId."' AND meta_key = '_shipping_city'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$city 			= $clientobject['meta_value'];
		}
		$clientvalues = $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderId."' AND meta_key = '_delivery_date'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$deliveryDate 	= $clientobject['meta_value'];
			$deliveryDate 	= date('d-m-Y', strtotime($deliveryDate));
		}


		$deliveryArray["delivery"] = array(
            'name'            			=> $firstName." ".$lastName,
            'street'          			=> $street,
            'housenumber'         		=> $numberAndAddi,
            'housenumber_additional'  	=> '',
            'zipcode'           		=> $zipcode,
            'city'            			=> $city,
            'delivery_date'       		=> $deliveryDate,
            'delivery_note'       		=> '',
	    );

		$cart["card"] = array(
            'id'    => '1',
            'message' => '',
	    );

		$aCombined 		= array_merge($orderlines, $deliveryArray, $cart);
		$postData 		= json_encode($aCombined);

		$url = 'https://dropsoft.nl/api/order/'.''.$bearer.'/false';
	    $curl = curl_init($url);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_POST, true);
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
	    $json_response = curl_exec($curl);
	    print_r($json_response);
	    $json_response = json_decode($json_response);
	    curl_close($curl);


	    if(!empty($json_response)){
	      	if($json_response->status == "success"){
	      		$mysqli->query("UPDATE " . $table_prefix . "posts SET dropsoft = '1' WHERE ID = '".$orderId."'");
	      	}
	    }
	}

?>