<?php

// env config
ini_set('display_errors', 1);
umask(0);

// mage setup
require_once './app/Mage.php';
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);


// input values
define('CATEGORY_ID', 20);
define('PRODUCT_QTY', 20);
define('IMAGES_PER_PRODUCT', 1);
define('LIPSUM_API', 'https://baconipsum.com/api/?type=meat-and-filler&format=text');
define('IMAGE_API', 'http://lorempixel.com/700/700/technics/');

// make an array with all websites
$website_ids = array();
foreach (Mage::app()->getWebsites() as $website)
	$website_ids[] = $website->getId();

$categoy_ids = Mage::getModel('catalog/category')->getTreeModel()->load()->getCollection()->getAllIds();

for ($i = 0; $i < PRODUCT_QTY; $i++)
{
	$dummy_text 		= file_get_contents(LIPSUM_API);
	$dummy_title 		= substr($dummy_text, 0, 30); 
	$dummy_shortdescr 	= substr($dummy_text, 31, 220); 
	$dummy_descr 		= substr($dummy_text, 221); 
	$dummy_sku 		= substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10); 
	
	$rand_price 		= rand(10, 999);
	$cost_price 		= rand(10, ($rand_price/2));
	$special_price 		= rand($cost_price, ($rand_price/2));
	
	
	$product = Mage::getModel('catalog/product');
	$product->setData(array(
		'name'				=> $dummy_title,
		'sku'				=> $dummy_sku,
		'short_description'	=> $dummy_shortdescr,
		'description'		=> $dummy_descr,
		'price'				=> number_format($rand_price / 10, 2, '.', ''),
		'cost'				=> number_format($cost_price / 10, 2, '.', ''),
		'weight'			=> number_format(rand(1,10), 2, '.',''),
		'status'			=> Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
		'visibility'		=> Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
		'type_id'			=> Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
		'tax_class_id'		=> 2,
		'attribute_set_id'	=> 4,
		'category_ids'		=> array_rand($categoy_ids, random_int(1,20)),
		'website_ids'		=> $website_ids,
	));
	
	
	if ($i%4 == 0) // give some products a special price
	{
		$product->setSpecialPrice(number_format($special_price / 10, 2, '.', ''));
	}
	
	$product->setStockData(array( 
		'is_in_stock' => 1, 
		'qty' => 1000,
		'manage_stock' => 1,
	)); 
	
	try {
		$product->save();
	} catch (Exception $e) {
		echo "ERROR: {$e}\n";
		die();
	}
	
	$product_id = $product->getIdBySku($dummy_sku);
	$product = Mage::getModel('catalog/product')->load($product_id);
	
	mkdir('tmpimage', 0777);
	for ($j = 0; $j < IMAGES_PER_PRODUCT; $j++)
	{
		// download and save file
		$image_source 	= file_get_contents(IMAGE_API);
		$image_name 	= dirname(__FILE__)."/tmpimage/{$dummy_sku}}_{$j}.jpg";
		file_put_contents($image_name , $image_source);
		chmod($image_source, 0777);
		
		try {
			$product->addImageToMediaGallery($image_name , ($j==0) ? array( 'thumbnail', 'small_image', 'image' ) : null, true, false );
		} catch (Exception $e){
			echo "ERROR: {$e}\n";
		}
		
		unlink($image_name);
		unset($download);
	}
	
	try {
		$product->save();
	} catch (Exception $e) {
		echo "ERROR: {$e}\n";
	}
}
