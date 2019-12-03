<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;


class MainController extends Controller
{
    public function scrape(Request $request) {

    	$url = $request->get('url');
    	// dd($url);

        $guzzleCLient = new GuzzleClient(array(
            'timeout' => 60
        ));
        
        $response = $guzzleCLient->request('GET',$url);
        $response_status_code = $response->getStatusCode();
        if ($response_status_code == 200) {
            $goutteClient = new Client();
            $goutteClient->setClient($guzzleCLient);
            $crawler = $goutteClient->request('GET', $url);

            

            $car_items = $crawler->filter('.searchResultsGalleryItem')->each(function($node){

                        $img_src = $node->filter('.galleryThumbWrapper > a > img')->attr('src');
                        $title = $node->filter('.classifiedTitle')->text();
                        $price = $node->filter('.searchResultsPriceValue')->text();
                        $post_date =$node->filter('div.searchResultsGallerySubContent > div:nth-child(1)')->text();
                        $place = $node->filter('div.searchResultsGallerySubContent > div:nth-child(2)')->text();

                        return $cars[] = array (
                                    'img_src' => $img_src,
                                    'title' => $title,
                                    'price' => $price,
                                    'post_date' => $post_date,
                                    'place' => $place,
                                );
                    });

            return view('sahibindencom.son-48-saat')->with('car_items', $car_items);

        }else{
            echo "Response error";
        }
    }
}
