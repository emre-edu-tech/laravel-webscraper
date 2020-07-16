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
                    $title = $nested_node->filter('.cldt-summary-headline .cldt-summary-makemodel')->text();
                    $version = $nested_node->filter('.cldt-summary-headline .cldt-summary-version')->text();
                    $price = $nested_node->filter('.cldt-price')->text();
                    $mileage = $nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(0)->text();
                    $production_month_year = $nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(1)->text();
                    $horse_power = $nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(2)->text();
                    $condition = $nested_node->filter('.cldt-summary-vehicle-data ul li')->eq(3)->text();
                    $car_images_string = $nested_node->filter('.cldt-summary-gallery > as24-listing-summary-image')->attr('data-images');
                    $car_images_raw = explode(',', $car_images_string);
                    $car_images_normal = array();
                    foreach ($car_images_raw as $car_image) {
                        $normal_car_image_array = explode('{', $car_image);
                        $normal_car_image = rtrim($normal_car_image_array[0], '/');
                        array_push($car_images_normal, $normal_car_image);
                    }
                    // $seller_logo = $nested_node->filter('.cldt-summary-seller > .cldf-summary-seller-data .cldf-summary-seller-logo > img')->attr('src');
                    // $post_date =$node->filter('div.searchResultsGallerySubContent > div:nth-child(1)')->text();
                    // $place = $node->filter('div.searchResultsGallerySubContent > div:nth-child(2)')->text();
                    return array (
                        'title' => $title,
                        'version' => $version,
                        'price' => $price,
                        'mileage' => $mileage,
                        'car_images' => $car_images_normal,
                        'horse_power' => $horse_power,
                        'production_month_year' => $production_month_year,
                        'condition' => $condition,
                        // 'place' => $place,
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
