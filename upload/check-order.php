<?php 

	require_once('wp-config.php');

	ini_set('max_execution_time', 1); //300 seconds = 5 minutes

	$mysqli = new mysqli('localhost', constant('DB_USER'), constant('DB_PASSWORD'), constant('DB_NAME'));
	if ($mysqli->connect_error) {
	    die('Connection Error (' . $mysqli->connect_errno . ') '
	            . $mysqli->connect_error);
	}


	$datumvandaag = date('d-m-Y');
	$apicode = $mysqli->query("SELECT option_value FROM " . $table_prefix . "options WHERE option_name = 'fertiplantfulfilment'");
	while($apiobject = mysqli_fetch_assoc($apicode)){
		$option_value = $apiobject['option_value'];
		$scccsarray = unserialize($option_value);
	}

	$api = $scccsarray['sccss-content'];

	$orders = $mysqli->query("SELECT ID FROM " . $table_prefix . "posts WHERE post_type = 'shop_order' AND ferti = '0' AND post_status = 'wc-processing'");
	while($orderobject = mysqli_fetch_assoc($orders)){


		$orderid = $orderobject['ID'];
		$eersteArray['orderlines'] = array();
		$arrayteller = 0;

		$itemidquery = $mysqli->query("SELECT order_item_id FROM " . $table_prefix . "woocommerce_order_items WHERE order_id = '".$orderid."' AND order_item_type = 'line_item'");
		while($orderitemidobject = mysqli_fetch_assoc($itemidquery)){
			$order_item_id = $orderitemidobject['order_item_id'];
			$productidquery = $mysqli->query("SELECT meta_value FROM " . $table_prefix . "woocommerce_order_itemmeta WHERE order_item_id = '".$order_item_id."' AND meta_key = '_product_id'");
			while($productidobject = mysqli_fetch_assoc($productidquery)){
				$product_id = $productidobject['meta_value'];

			}

			$productidquery = $mysqli->query("SELECT meta_value FROM " . $table_prefix . "woocommerce_order_itemmeta WHERE order_item_id = '".$order_item_id."' AND meta_key = '_qty'");
			while($productidobject = mysqli_fetch_assoc($productidquery)){
				$quantity = $productidobject['meta_value'];
			}

			$checkproduct = $mysqli->query("SELECT oudid FROM " . $table_prefix . "posts WHERE ID = '".$product_id."'");
			while($checkproductobject = mysqli_fetch_assoc($checkproduct)){
				$oudid = $checkproductobject['oudid'];
			}

			//Fertiplant Fulfilment producten in orderlines zetten
			if($oudid != 0){
				$producten[$arrayteller] = array(
		        	'productcode'   => $oudid,
		            'amount'    => $quantity,
		        );
		           
		    	$arrayteller++;
			}

		}

		$eersteArray['orderlines'] = array_merge($producten);

		$clientvalues = $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderid."' AND meta_key = '_shipping_first_name'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$voornaam = $clientobject['meta_value'];
		}
		$clientvalues = $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderid."' AND meta_key = '_billing_first_name'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$voornaam2 = $clientobject['meta_value'];
		}
		$clientvalues = $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderid."' AND meta_key = '_shipping_last_name'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$achternaam = $clientobject['meta_value'];
		}
		$clientvalues = $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderid."' AND meta_key = '_billing_last_name'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$achternaam2 = $clientobject['meta_value'];
		}
		$clientvalues = $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderid."' AND meta_key = '_shipping_address_1'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$adres = $clientobject['meta_value'];
		}
		$clientvalues = $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderid."' AND meta_key = '_billing_address_1'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$adres2 = $clientobject['meta_value'];
		}
		$clientvalues = $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderid."' AND meta_key = '_shipping_postcode'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$postcode = $clientobject['meta_value'];
		}
		$clientvalues = $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderid."' AND meta_key = '_billing_postcode'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$postcode2 = $clientobject['meta_value'];
		}
		$clientvalues = $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderid."' AND meta_key = '_shipping_city'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$city = $clientobject['meta_value'];
		}
		$clientvalues = $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderid."' AND meta_key = '_billing_city'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$city2 = $clientobject['meta_value'];
		}

		$clientvalues = $mysqli->query("SELECT meta_value FROM " . $table_prefix . "postmeta WHERE post_id = '".$orderid."' AND meta_key = '_delivery_date'");
		while($clientobject = mysqli_fetch_assoc($clientvalues)){
			$delivery_date = $clientobject['meta_value'];

			$verzend_datum = date('d-m-Y', strtotime($delivery_date));
		}

		if($adres == ''){
			$adres = $adres2;
		}
		if($postcode == ''){
			$postcode = $postcode2;
		}
		if($voornaam == ''){
			$voornaam = $voornaam2;
		}
		if($achternaam == ''){
			$achternaam = $achternaam2;
		}
		if($city == ''){
			$city = $city2;
		}

		$input_string = $adres;
	    $address = "";
	    $houseNumber = "";
	    $matches = array();
	    if(preg_match('/(?P<address>[^\d]+) (?P<number>\d+.?)/', $input_string, $matches)){
	        $address = $matches['address'];
	        $houseNumber = $matches['number'];
	    } else { // no number found, it is only address
	        $address = $input_string;
	    }


		$delivery_array["delivery"] = array(
            'name'            => $voornaam." ".$achternaam,
            'street'          => $address,
            'housenumber'         => $houseNumber,
            'housenumber_additional'  => 'int',
            'zipcode'           => $postcode,
            'city'            => $city,
            'delivery_date'       => $verzend_datum,
            'delivery_note'       => '',
	    );

		$kaartje["card"] = array(
            'id'    => '1',
            'message' => '',
	    );

		$samenvoegen = array_merge($eersteArray, $delivery_array, $kaartje);
		$post_data = json_encode($samenvoegen);

		print_r($post_data);


		$url = 'https://lev.fertiplant-fulfilment.nl/api/order/'.''.$api.'/false';
	    $curl = curl_init($url);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_POST, true);
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
	    $json_response = curl_exec($curl);
	    $json_response = json_decode($json_response);
	    curl_close($curl);

	    print_r($json_response);

	    if(!empty($json_response)){
	      	if($json_response->status == "success"){
	      		$mysqli->query("UPDATE " . $table_prefix . "posts SET ferti = '1' WHERE ID = '".$orderid."'");
	      	}
	    }
	}

?>