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

                    // $img_src = $node->filter('.galleryThumbWrapper > a > img')->attr('src');
                    $title = trim($nested_node->filter('.cldt-summary-headline .cldt-summary-makemodel')->text());

                    $version = $nested_node->filter('.cldt-summary-headline .cldt-summary-version')->text();
                    $version_array = explode('|', $version);
                    $version = trim($version_array[0]);

                    $price = trim($nested_node->filter('.cldt-price')->text());
                    $price_array = explode(',', $price);
                    $price = trim($price_array[0]);

                    $mileage = trim($nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(0)->text());

                    $production_month_year = trim($nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(1)->text());

                    $horse_power = trim($nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(2)->text());

                    $condition = trim($nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(3)->text());

                    $transmission = trim($nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(5)->text());

                    $fuel_type = trim($nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(6)->text());

                    $fuel_consumption = $nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(7)->html();
                    $fuel_consumption_array = explode('<as24-footnote-item>', $fuel_consumption);
                    $fuel_consumption = trim($fuel_consumption_array[0]);

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
                        'title' => $title,
                        'version' => $version,
                        'price' => $price,
                        'mileage' => $mileage,
                        'car_images' => $car_images_normal,
                        'horse_power' => $horse_power,
                        'production_month_year' => $production_month_year,
                        'condition' => $condition,
                        'transmission' => $transmission,
                        'fuel_type' => $fuel_type,
                        'fuel_consumption' => $fuel_consumption,
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
