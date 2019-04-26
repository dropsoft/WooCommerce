<?php

	require_once('wp-config.php');
	require_once('wp-admin/includes/image.php');
	 require_once('wp-admin/includes/taxonomy.php'); 

// 	ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

	// Cronjob max time is set to 5 min
	ini_set('max_execution_time', 300);

	// Make db connection
	$mysqli = new mysqli('localhost', constant('DB_USER'), constant('DB_PASSWORD'), constant('DB_NAME'));
	if ($mysqli->connect_error) {
	    die('Connection Error (' . $mysqli->connect_errno . ') '
	            . $mysqli->connect_error);
	}

	// Add customfields to db if they dont exist
	// ds_product_id field to check if product already exist. To prevent duplicate products
	// Dropsoft field is a custom field to check orders for Dropsoft
	$mysqli->query("ALTER TABLE " . $table_prefix . "posts ADD ds_product_id INT(11) NOT NULL AFTER comment_count");
	$mysqli->query("ALTER TABLE " . $table_prefix . "posts ADD dropsoft INT(11) NOT NULL AFTER ds_product_id");

	// Get plugin settings
	$dropSoftSettings 	= $mysqli->query("SELECT option_value FROM " . $table_prefix . "options WHERE option_name = 'dropsoft'");
	while($settings 	= mysqli_fetch_assoc($dropSoftSettings)){
		$option_value 	= $settings['option_value'];
		$getValues 		= unserialize($option_value);
	}

	$bearer 		= $getValues['your-bearer'];
	$margeType 		= $getValues['marge-type'];
	$marge 			= $getValues['your-marge'];
	$autoPublish 	= $getValues['auto-publish'];

	// More settings
	$url 			= 'https://dropsoft.nl/api/assortment/'.''.$bearer;
	$dateToday 		= date('Y-m-d H:i:s');

	// Get assortment
	$json 			= file_get_contents($url);
	$jsonDecoded 	= json_decode($json);
	$aSelected	 	= array();
	foreach($jsonDecoded->assortment as $product) {
	  $id = $product->product_id;
	  // Add id's to array
	  array_push($aSelected, $id);
	}

	foreach($jsonDecoded->categories as $categorie) {
		
		$addCategoryLevel1 	= wp_insert_term(
		  $categorie->name,
		  'product_cat',
		  array(
		    'description'	=> $categorie->description,
		    'slug' 			=> $categorie->slug
		  )
		);

		$errorDataLevel1 	= $addCategoryLevel1->error_data;
		if(isset($errorDataLevel1)){
			$termIdLevel1 		= $errorDataLevel1['term_exists'];
		}else{
			$termIdLevel1 		= $addCategoryLevel1['term_id'];
		}

		$newDataLevel1 	=  array (
	      'api_id' 		=> $categorie->id,
	      'cat_id' 		=> $termIdLevel1
	    );
	    $usedCats[] 			= $newDataLevel1;


		foreach ($categorie->cat2 as $categorieLevel2) {
			$addCatLevel2 		= wp_insert_term(
			  $categorieLevel2->name,
			  'product_cat', 
			  array(
			    'description'	=> $categorieLevel2->description,
			    'parent' 		=> $termIdLevel1,
			    'slug' 			=> $categorieLevel2->slug
			  )
			);

			$errorDataLevel2 	= $addCatLevel2->error_data;
			if(isset($errorDataLevel2)){
				$termIdLevel2 		= $errorDataLevel2['term_exists'];
			}else{
				$termIdLevel2 		= $addCatLevel2['term_id'];
			}

			$newDataLevel2 	=  array (
		      'api_id' 		=> $categorieLevel2->id,
		      'cat_id' 		=> $termIdLevel2
		    );
		    $usedCats[] 	= $newDataLevel2;

		    foreach ($categorieLevel2->cat3 as $categorie3) {
		    	$addCategoryLevel3 	= wp_insert_term(
				$categorie3->name,
				  'product_cat',
				  array(
				    'description'	=> $categorie3->description,
				    'parent' 		=> $termIdLevel2,
				    'slug' 			=> $categorie3->slug
				  )
				);

				$errorDataLevel3 	= $addCategoryLevel3->error_data;
				if(isset($errorDataLevel3)){
					$termIdLevel3 		= $errorDataLevel3['term_exists'];
				}else{
					$termIdLevel3 		= $addCategoryLevel3['term_id'];
				}

				$newDataLevel3 	=  array (
			      'api_id' 		=> $categorie3->id,
			      'cat_id' 		=> $termIdLevel3
			    );
			    $usedCats[] 	= $newDataLevel3;
		    }
		}
	}


	// Get all Dropsoft product id's that are in db
	$activeProducts 		= $mysqli->query("SELECT * FROM " . $table_prefix . "posts WHERE ds_product_id != 0");
	while($activeProduct 	= mysqli_fetch_assoc($activeProducts)){

		$productId 			= $activeProduct['ID'];
		$dsProductId 		= $activeProduct['ds_product_id'];

		// If products from the database are not included in the new collected assortment. Delete these products.
		if(!in_array($dsProductId, $aSelected)){
			$mysqli->query("UPDATE " . $table_prefix . "posts SET post_status = 'pending' WHERE ID = '".$productId."'");
			//$mysqli->query("DELETE FROM " . $table_prefix . "posts WHERE ID = '".$productId."' ");
			//$mysqli->query("DELETE FROM " . $table_prefix . "post_meta WHERE post_id = '".$productId."' ");
		}else{

			$post_status = get_post_status($productId);
			if($post_status!="publish"){
				$mysqli->query("UPDATE " . $table_prefix . "posts SET post_status = 'publish' WHERE ID = '".$productId."'");
			}
		}
	}


	foreach($jsonDecoded->assortment as $product) {

		$id 					= $product->product_id;
		$category 				= $product->category;
		foreach ($usedCats as $catRow) {
			if($catRow['api_id'] == $category){
				$insertCategory = $catRow['cat_id'];
			}
		}

		$productName 			= addslashes($product->product_name);
		$productDescription 	= addslashes($product->product_description);
		$quantity 				= $product->amount_available;
		$pricePurchase 			= $product->price_info->purchase_price;
		$priceAdvise 			= $product->price_info->advise_price;

		// Count price if adding marge is active
		if($margeType==1){
			$countMarge = 100 + $marge;
			$price = $priceAdvise / 100;
			$price = $price * $countMarge;
			$price = number_format((float)$price, 2, '.', '');
		}else if($margeType==2){
			$price = $priceAdvise + $marge;
			$price = number_format((float)$price, 2, '.', '');
		}else{
			$price = $priceAdvise;
		}

		// Set publish status
		if($autoPublish==0){
			$publishType = 'pending';
		}else{
			$publishType = 'publish';
		}

	  	
	  	$slug 					= strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $productName)));
	  	$slug 					= str_replace('---', '-', $slug);
	  	$slug 					= str_replace('--', '-', $slug);
	  	$slug 					= preg_replace('/(.*)[^a-zA-Z0-9]$/', '$1', $slug);

	  	$getProduct 			= $mysqli->query("SELECT ID FROM " . $table_prefix . "posts WHERE ds_product_id = '".$id."' LIMIT 0,1");
	  	$exists 				= $getProduct->num_rows;

	  	if($exists == 0){
	
	  		// If product doesent excists. Add it
			$mysqli->query("INSERT INTO " . $table_prefix . "posts (post_author, post_date, post_date_gmt, post_content, post_title, post_status, post_name, post_modified, post_modified_gmt, post_type, ds_product_id) VALUES('1', '".$dateToday."', '".$dateToday."', '".$productDescription."', '".$productName."', '".$publishType."', '".$slug."', '".$dateToday."', '".$dateToday."',  'product', '".$id."')");


			$getPostId = $mysqli->query("SELECT ID FROM " . $table_prefix . "posts ORDER BY ID DESC LIMIT 0,1");
			while($object = mysqli_fetch_assoc($getPostId)){
			  	$postId = $object['ID'];
			}

			// Nieuwe post id hierboven opgehaald. Deze koppelen aan met category relatie
			wp_set_object_terms($postId, $insertCategory, 'product_cat');

			$path = $_SERVER['HTTP_HOST'];
			$guid = $path.'/?post_type=product&#038;p='.''.$postId;

			$mysqli->query("UPDATE " . $table_prefix . "posts SET guid = '".$guid."' WHERE id = '".$postId."'");

			$stockStatus = "outofstock";
			if($quantity > 0){
			  	$stockStatus = "instock";
			}
		  	
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_wc_review_count', '1')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_wc_rating_count', 'a:0:{}')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_wc_average_rating', '0')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_edit_last', '1')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_edit_lock', '1511528338')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_sku', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_regular_price', '".$price."')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_sale_price', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_sale_price_dates_from', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_sale_price_dates_to', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', 'total_sales', '0')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_tax_status', 'taxable')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_tax_class', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_manage_stock', 'yes')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_backorders', 'no')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_sold_individually', 'no')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_weight', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_length', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_width', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_height', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_upsell_ids', 'a:0:{}')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_crosssell_ids', 'a:0:{}')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_purchase_note', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_default_attributes', 'a:0:{}')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_downloadable', 'no')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_thumbnail_id', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_product_image_gallery', '')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_download_limit', '-1')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_stock', '".$quantity."')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_stock_status', '".$stockStatus."')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_product_version', '3.2.5')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_price', '".$price."')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_download_expiry', '-1')");
			$mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_virtual', 'no')");


	        // Add extra information
			$aInfo = array();
			foreach($product->extra_information as $info) {
		    	$infoName 			= addslashes($info->name);
		        $infoDescription 	= addslashes($info->description);
				$items = array(
		          	$infoName => array(
				    'name' => $infoName,
				    'value' => $infoDescription,
				    'position' => 1,
				    'is_visible' => 1,
				    'is_variation' => 0,
				    'is_taxonomy' => 0
					)
				);
				$aInfo = $aInfo + $items;
			}
	     	$insert_attributes = serialize($aInfo);
		    $mysqli->query("INSERT INTO " . $table_prefix . "postmeta (post_id, meta_key, meta_value) VALUES ('".$postId."', '_product_attributes', '".$insert_attributes."')");	  



			$imageCounter 		= 0;
			$images 			= array();
		  	foreach($product->images as $image) {

			  	$url 			= $image->uri;
			  	$name 			= $postId.'-'.$imageCounter;
			  	$filename 		= $name.'.jpg';
			  	$year 			= date('Y');
			  	$month 			= date('m');

			  	copy($url, 'wp-content/uploads/'.$year.'/'.$month.'/'.$name.'.jpg');

			  	$uploadfile 	= ''.$year.'/'.$month.'/'.$name.'.jpg';
				$contents 		= file_get_contents('wp-content/uploads/'.$year.'/'.$month.'/'.$filename.'');
				$savefile 		= fopen($uploadfile, 'w');
				
				fwrite($savefile, $contents);
				fclose($savefile);

				$wp_filetype 	= wp_check_filetype(basename($filename), null );
				$attachment = array(
				    'post_mime_type' => $wp_filetype['type'],
				    'post_title' => $filename,
				    'post_content' => '',
				    'post_status' => 'inherit'
				);

				$attachId 		= wp_insert_attachment( $attachment, $uploadfile );
				$imagenew 		= get_post( $attachId );
				$fullsizepath 	= get_attached_file( $imagenew->ID );
				$attach_data 	= wp_generate_attachment_metadata( $attachId, $fullsizepath );
				wp_update_attachment_metadata( $attachId, $attach_data );

				$getPhoto 	= $mysqli->query("SELECT post_id FROM " . $table_prefix . "postmeta WHERE meta_value = '".$uploadfile."'");
				while($object 	= mysqli_fetch_assoc($getPhoto)){
					$photoId 	= $object['post_id'];
				}
		  		$images[$imageCounter] = $photoId;
		  		$imageCounter++;
		  	}

			$getPhoto = $mysqli->query("SELECT post_id FROM " . $table_prefix . "postmeta WHERE meta_value = '".$year."/".$month."/".$postId."-0.jpg'");
			while($object = mysqli_fetch_assoc($getPhoto)){
				$photoId = $object['post_id'];
			}

		  	$imageValues = "";
			foreach ($images as $value) {
			    $imageValues = $imageValues.",".$value;
			}
		  	$photoGallery = substr($imageValues, 1);

		  	$mysqli->query("UPDATE " . $table_prefix . "postmeta SET meta_value = '".$photoId."' WHERE post_id = '".$postId."' AND meta_key = '_thumbnail_id'");
		  	$mysqli->query("UPDATE " . $table_prefix . "postmeta SET meta_value = '".$photoGallery."' WHERE post_id = '".$postId."' AND meta_key = '_product_image_gallery'");

	  	}else{
	  		// Product already excists, but check for changes
	  		$query = $mysqli->query("SELECT ID FROM " . $table_prefix . "posts WHERE ds_product_id = '".$id."' LIMIT 0,1");
		  	while($object = mysqli_fetch_assoc($query)){
		  		$postId = $object['ID'];
		  	}

		  	$stockStatus = "outofstock";
		  	if($quantity > 0){
		  		$stockStatus = "instock";
		  	}
		  	$mysqli->query("UPDATE " . $table_prefix . "posts SET post_content = '".$productDescription."', post_title = '".$productName."', post_name = '".$slug."', post_modified = '".$dateToday."', post_modified_gmt = '".$dateToday."' WHERE ds_product_id = '".$id."'");


		  	$mysqli->query("UPDATE " . $table_prefix . "postmeta SET meta_value = '".$quantity."' WHERE post_id = '".$postId."' AND meta_key = '_stock'");
		  	$mysqli->query("UPDATE " . $table_prefix . "postmeta SET meta_value = '".$stockStatus."' WHERE post_id = '".$postId."' AND meta_key = '_stock_status'");
		  

			 // Add extra information
		  	$aInfo = array();
			foreach($product->extra_information as $info) {
		    	$infoName 			= addslashes($info->name);
		        $infoDescription 	= addslashes($info->description);
				$items = array(
		          	$infoName => array(
				    'name' => $infoName,
				    'value' => $infoDescription,
				    'position' => 1,
				    'is_visible' => 1,
				    'is_variation' => 0,
				    'is_taxonomy' => 0
					)
				);
				$aInfo = $aInfo + $items;
			}
	     	$insert_attributes = serialize($aInfo);
	      	$mysqli->query("UPDATE " . $table_prefix . "postmeta SET meta_value = '".$insert_attributes."' WHERE post_id = '".$postId."' AND meta_key = '_product_attributes'");
		}
	}

?>