<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;


class MainController extends Controller
{
    public function scrape(Request $request) {

        $url = 'https://www.autoscout24.de/lst/audi/a4?sort=standard&desc=0&ustate=N%2CU&cy=D&priceto=15000&fregfrom=2014&atype=C';
    	// $url = $request->get('url');
    	// dd($url);

        $guzzleCLient = new GuzzleClient(array(
            'timeout' => 60
        ));
        
        $response = $guzzleCLient->request('GET', $url);
        $response_status_code = $response->getStatusCode();
        if ($response_status_code == 200) {
            $goutteClient = new Client();
            $goutteClient->setClient($guzzleCLient);
            $crawler = $goutteClient->request('GET', $url);
            $car_items = array();
            // Working code! While using closures you need to pass the array by reference to use the same array
            // inside closure
            $crawler->filter('.cl-list-elements')->each(function($node) use (&$car_items) {

                $car_items = $node->filter('.cldt-summary-full-item')->each(function($nested_node) {

                    $id = $nested_node->attr('id');

                    $make_model = trim($nested_node->filter('.cldt-summary-headline .cldt-summary-makemodel')->text());
                    $make_model_array = explode(' ', $make_model);
                    $make = $make_model_array[0];
                    $model = $make_model_array[1];

                    $version = $nested_node->filter('.cldt-summary-headline .cldt-summary-version')->text();
                    $version_array = explode('|', $version);
                    $version = trim($version_array[0]);

                    $carequipments = array();
                    try {
                        $subheadline = $nested_node->filter('.cldt-summary-headline .cldt-summary-subheadline')->text();
                        $carequipments = explode(', ', $subheadline);
                    } catch (\InvalidArgumentException $e) {
                        $subheadline = $e;
                    }

                    $price = trim($nested_node->filter('.cldt-price')->text());
                    $price_array = explode(',', $price);
                    $price = trim($price_array[0]);
                    $price_with_currency_arr = explode(' ', $price_array[0]);
                    $currency = trim($price_with_currency_arr[0]);
                    $price_value = trim(str_replace('.', '', $price_with_currency_arr[1]));

                    $mileage = trim($nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(0)->text());
                    $mileage_array = explode(' ', $mileage);
                    $mileage_value = trim(str_replace('.', '', $mileage_array[0]));
                    // $mileage_unit = trim($mileage_array[1]);

                    $production_month_year = trim($nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(1)->text());
                    $production_month_year_arr = explode('/', $production_month_year);
                    $production_month = $production_month_year_arr[0];
                    $production_year = $production_month_year_arr[1];

                    $engine_power = trim($nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(2)->text());
                    // remove the last character
                    $engine_power_array = explode('(', $engine_power);
                    $kw = explode(' ', $engine_power_array[0]);
                    $kw = trim($kw[0]);
                    $hp = explode(' ', $engine_power_array[1]);
                    $hp = trim($hp[0]);

                    $condition = trim($nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(3)->text());

                    $number_of_car_owners = trim($nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(4)->text());

                    $transmission = trim($nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(5)->text());

                    $fuel_type = trim($nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(6)->text());

                    $fuel_consumption = $nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(7)->html();
                    $fuel_consumption_array = explode('<as24-footnote-item>', $fuel_consumption);
                    $fuel_consumption = trim($fuel_consumption_array[0]);
                    $fuel_consumption_only = explode(' ', $fuel_consumption);
                    $fuel_consumption_only = trim(str_replace(',', '.', $fuel_consumption_only[0]));

                    $co2_emission = $nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(8)->html();
                    $co2_emission_array = explode('<as24-footnote-item>', $co2_emission);
                    $co2_emission = trim($co2_emission_array[0]);

                    // getting image paths
                    $car_images_string = $nested_node->filter('.cldt-summary-gallery > as24-listing-summary-image')->attr('data-images');
                    $car_images_raw = explode(',', $car_images_string);
                    $car_images_normal = array();
                    foreach ($car_images_raw as $car_image) {
                        $normal_car_image_array = explode('{', $car_image);
                        $normal_car_image = rtrim($normal_car_image_array[0], '/');
                        array_push($car_images_normal, $normal_car_image);
                    }
                    // $post_date =$node->filter('div.searchResultsGallerySubContent > div:nth-child(1)')->text();
                    return array (
                        'id'    => $id,
                        'make' => $make,
                        'model' => $model,
                        'subheadline'   => $subheadline,
                        'carequipments'  => $carequipments,
                        'version' => $version,
                        'price' => $price,
                        'currency'  => $currency,
                        'price_value' => $price_value,
                        'mileage' => $mileage,
                        'mileage_value' => $mileage_value,
                        'car_images' => $car_images_normal,
                        'engine_power' => $engine_power,
                        'hp'    => $hp,
                        'kw'    => $kw,
                        'production_month' => $production_month,
                        'production_year'   => $production_year,
                        'production_month_year' => $production_month_year,
                        'condition' => $condition,
                        'number_of_car_owners'  => $number_of_car_owners,
                        'transmission' => $transmission,
                        'fuel_type' => $fuel_type,
                        'fuel_consumption' => $fuel_consumption,
                        'fuel_consumption_only' => $fuel_consumption_only,
                        'co2_emission'  => $co2_emission,
                    );
                });

            });

            // dd($car_items);

            return view('autoscout24.autoscout-24')->with('car_items', $car_items);

        }else{
            echo "Response error";
        }
    }
}
