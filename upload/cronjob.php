<?php

	require_once('wp-config.php');
	require_once('wp-admin/includes/image.php');

	ini_set('max_execution_time', 300);

	$mysqli = new mysqli('localhost', constant('DB_USER'), constant('DB_PASSWORD'), constant('DB_NAME'));
	if ($mysqli->connect_error) {
	    die('Connection Error (' . $mysqli->connect_errno . ') '
	            . $mysqli->connect_error);
	}

	$mysqli->query("ALTER TABLE " . $table_prefix . "posts ADD oudid INT(11) NOT NULL AFTER comment_count");
	$mysqli->query("ALTER TABLE " . $table_prefix . "posts ADD ferti INT(11) NOT NULL AFTER oudid");

	$apicode = $mysqli->query("SELECT option_value FROM " . $table_prefix . "options WHERE option_name = 'fertiplantfulfilment'");
	while($apiobject = mysqli_fetch_assoc($apicode)){
		$option_value = $apiobject['option_value'];

		$scccsarray = unserialize($option_value);
	}

	$api = $scccsarray['sccss-content'];



	$datumvandaag = date('Y-m-d H:i:s');
	$datevandaag = date('Y-m-d');

	$url = 'https://lev.fertiplant-fulfilment.nl/api/assortment/'.''.$api;
	$json = file_get_contents($url);
	$obj = json_decode($json);


	$deleteArray = array();

	foreach($obj->assortment as $artikel) {
	  $id = $artikel->product_id;
	  array_push($deleteArray, $id);
	}


	$deletequery = $mysqli->query("SELECT * FROM " . $table_prefix . "posts WHERE oudid != 0");
	while($deleteobject = mysqli_fetch_assoc($deletequery)){
		$product_id = $deleteobject['ID'];
		$oudid = $deleteobject['oudid'];

		if(!in_array($oudid, $deleteArray)){
			$mysqli->query("DELETE FROM " . $table_prefix . "posts WHERE ID = '".$product_id."' ");
			$mysqli->query("DELETE FROM " . $table_prefix . "post_meta WHERE post_id = '".$product_id."' ");
		}
	}


	foreach($obj->assortment as $artikel) {

		$id = $artikel->product_id;
		$product_name = addslashes($artikel->product_name);
		$product_description = addslashes($artikel->product_description);
		$quantity = $artikel->amount_available;
		$price = $artikel->price_info->price;

	  	$slug = preg_replace('/\s+/', '-', $product_name);

	  	$aantalproductenquery = $mysqli->query("SELECT ID FROM " . $table_prefix . "posts WHERE oudid = '".$id."' LIMIT 0,1");
	  	$aantal = $aantalproductenquery->num_rows;

	  	if($aantal == 0){
			$mysqli->query("INSERT INTO " . $table_prefix . "posts (post_author, post_date, post_date_gmt, post_content, post_title, post_status, post_name, post_modified, post_modified_gmt, post_type, oudid) VALUES('1', '".$datumvandaag."', '".$datumvandaag."', '".$product_description."', '".$product_name."', 'pending', '".$slug."', '".$datumvandaag."', '".$datumvandaag."',  'product', '".$id."')");

			$query = $mysqli->query("SELECT ID FROM " . $table_prefix . "posts ORDER BY ID DESC LIMIT 0,1");
			while($object = mysqli_fetch_assoc($query)){
			  	$post_id = $object['ID'];
			}

			$path = $_SERVER['HTTP_HOST'];
			$guid = $path.'/?post_type=product&#038;p='.''.$post_id;

			$mysqli->query("UPDATE " . $table_prefix . "posts SET guid = '".$guid."' WHERE id = '".$post_id."'");

			$stock_status = "outofstock";
			if($quantity > 0){
			  	$stock_status = "instock";
			}
		  	
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_wc_review_count', '1')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_wc_rating_count', 'a:0:{}')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_wc_average_rating', '0')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_edit_last', '1')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_edit_lock', '1511528338')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_sku', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_regular_price', '".$price."')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_sale_price', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_sale_price_dates_from', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_sale_price_dates_to', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', 'total_sales', '0')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_tax_status', 'taxable')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_tax_class', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_manage_stock', 'yes')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_backorders', 'no')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_sold_individually', 'no')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_weight', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_length', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_width', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_height', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_upsell_ids', 'a:0:{}')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_crosssell_ids', 'a:0:{}')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_purchase_note', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_default_attributes', 'a:0:{}')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_downloadable', 'no')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_thumbnail_id', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_product_image_gallery', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_download_limit', '-1')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_stock', '".$quantity."')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_stock_status', '".$stock_status."')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_product_version', '3.2.5')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_price', '".$price."')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_download_expiry', '-1')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_virtual', 'no')");



			foreach($artikel->extra_information as $info) {

		    	$naam = addslashes($info->name);
		        $attribute_description = addslashes($info->description);

				$test = array(
		          	$naam => array(
				    'name' => $naam,
				    'value' => $attribute_description,
				    'position' => 1,
				    'is_visible' => 1,
				    'is_variation' => 0,
				    'is_taxonomy' => 0
					)
				);
		          
		          break;
			}

	        $teller = 0;
		  	//extra_information
	        foreach($artikel->extra_information as $info) {

		        if($teller > 0){
		         	$naam = addslashes($info->name);
		          	$attribute_description = addslashes($info->description);

			        $product_attributes = array(
			          	$naam => array(
					    'name' => $naam,
					    'value' => $attribute_description,
					    'position' => 1,
					    'is_visible' => 1,
					    'is_variation' => 0,
					    'is_taxonomy' => 0
						)
					);

		          	$test = $test + $product_attributes;
		      	}
	      		$teller++;
	        }

	      	$insert_attirubtes = serialize($test);

		    $mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$post_id."', '_product_attributes', '".$insert_attirubtes."')");	  

			$imageteller = 0;
			$fotos = array();
		  	foreach($artikel->images as $image) {

			  	$url = $image->uri;
			  	$naam = $post_id.'-'.$imageteller;
			  	$filename = $naam.'.jpg';

			  	$jaar = date('Y');
			  	$maand = date('m');

			  	copy($url, 'wp-content/uploads/'.$jaar.'/'.$maand.'/'.$naam.'.jpg');

			  	$uploadfile = ''.$jaar.'/'.$maand.'/'.$naam.'.jpg';

				$contents= file_get_contents('wp-content/uploads/'.$jaar.'/'.$maand.'/'.$filename.'');
				$savefile = fopen($uploadfile, 'w');
				fwrite($savefile, $contents);
				fclose($savefile);

				$wp_filetype = wp_check_filetype(basename($filename), null );

				$attachment = array(
				    'post_mime_type' => $wp_filetype['type'],
				    'post_title' => $filename,
				    'post_content' => '',
				    'post_status' => 'inherit'
				);

				$attach_id = wp_insert_attachment( $attachment, $uploadfile );

				$imagenew = get_post( $attach_id );
				$fullsizepath = get_attached_file( $imagenew->ID );
				$attach_data = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
				wp_update_attachment_metadata( $attach_id, $attach_data );

				$foto_ophalen = $mysqli->query("SELECT post_id FROM " . $table_prefix . "postmeta WHERE meta_value = '".$uploadfile."'");
				while($object = mysqli_fetch_assoc($foto_ophalen)){
					$foto_id = $object['post_id'];
				}

		  		$fotos[$imageteller] = $foto_id;

		  		$imageteller++;
		  	}

			$foto_ophalen = $mysqli->query("SELECT post_id FROM " . $table_prefix . "postmeta WHERE meta_value = '".$jaar."/".$maand."/".$post_id."-1.jpg'");
			while($object = mysqli_fetch_assoc($foto_ophalen)){
				$foto_id = $object['post_id'];
			}

		  	$waardes = "";
			foreach ($fotos as $value) {
			    $waardes = $waardes.",".$value;
			}

		  	$foto_gallerij = substr($waardes, 1);


		  	$mysqli->query("UPDATE " . $table_prefix . "postmeta SET meta_value = '".$foto_id."' WHERE post_id = '".$post_id."' AND meta_key = '_thumbnail_id'");
		  	$mysqli->query("UPDATE " . $table_prefix . "postmeta SET meta_value = '".$foto_gallerij."' WHERE post_id = '".$post_id."' AND meta_key = '_product_image_gallery'");

	  	}else{
	  		$query = $mysqli->query("SELECT ID FROM " . $table_prefix . "posts WHERE oudid = '".$id."' LIMIT 0,1");
		  	while($object = mysqli_fetch_assoc($query)){
		  		$post_id = $object['ID'];
		  	}

		  	$stock_status = "outofstock";
		  	if($quantity > 0){
		  		$stock_status = "instock";
		  	}

		  	$mysqli->query("UPDATE " . $table_prefix . "posts SET post_content = '".$product_description."', post_title = '".$product_name."', post_name = '".$slug."', post_modified = '".$datumvandaag."', post_modified_gmt = '".$datumvandaag."' WHERE oudid = '".$id."'");


		  	$mysqli->query("UPDATE " . $table_prefix . "postmeta SET meta_value = '".$quantity."' WHERE post_id = '".$post_id."' AND meta_key = '_stock'");
		  	$mysqli->query("UPDATE " . $table_prefix . "postmeta SET meta_value = '".$stock_status."' WHERE post_id = '".$post_id."' AND meta_key = '_stock_status'");
		  

			foreach($artikel->extra_information as $info) {

	          	$naam = addslashes($info->name);
	          	$attribute_description = addslashes($info->description);

		        $test = array(
		          	$naam => array(
				    'name' => $naam,
				    'value' => $attribute_description,
				    'position' => 1,
				    'is_visible' => 1,
				    'is_variation' => 0,
				    'is_taxonomy' => 0
					)
				);
	          
	          	break;
	        }

	        $teller = 0;
		  	//extra_information
	        foreach($artikel->extra_information as $info) {

		        if($teller > 0){
		          	$naam = addslashes($info->name);
		          	$attribute_description = addslashes($info->description);

			        $product_attributes = array(
			          	$naam => array(
					    'name' => $naam,
					    'value' => $attribute_description,
					    'position' => 1,
					    'is_visible' => 1,
					    'is_variation' => 0,
					    'is_taxonomy' => 0
						)
					);

		          	$test = $test + $product_attributes;
		      	}
	      		$teller++;
	        }

	      	$insert_attirubtes = serialize($test);

	      	$mysqli->query("UPDATE " . $table_prefix . "postmeta SET meta_value = '".$insert_attirubtes."' WHERE post_id = '".$post_id."' AND meta_key = '_product_attributes'");
		}
	}

?>