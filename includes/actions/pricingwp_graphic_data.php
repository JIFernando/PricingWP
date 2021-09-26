
<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//require_once (__DIR__ . '\..\pricing-settings.php');

//Get the product to be display
$productId =  isset($_GET['product_id'] ) ? $_GET['product_id'] : '';
$pricing_setting = new Pricing_Settings();
$rows = $pricing_setting->get_grphic_display_data($productId);

$dates = []; 
$data_sales = []; 
$data_prices = []; 

for ($index = 0; $index < $end;  ++$index) {

    $row = $rows[$index];
    //Get row values
    $date  = $row->date; 
    $price = $row->price; 
    $sales = $row->sales; 
 
    $dates[]       = $date;
    $data_sales[]  = $sales; 
    $data_prices[] = $price; 
};


$response = [
    "dates" => $dates,
    "data_sales" => $data_sales,
    "data_prices" => $data_prices,
];
echo json_encode($response);

?>