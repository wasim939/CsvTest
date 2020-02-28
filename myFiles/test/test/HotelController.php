<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use SoapClient;
use Config;
use Mtownsend\XmlToArray\XmlToArray;

class HotelController extends RequestController
{
    protected static $rawResponse;
    protected static $responseArray = [];
    protected static $cacheFile;

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct()
    {

    }

    public function hotelSearch(Request $request) {

        $validator = Validator::make($request->all(), [
            'from'          => 'required',
            'to'            => 'required',
            'dep_date'      => 'required|date',
            'return_date'   => 'required|date|after_or_equal:dep_date',
            'no_of_adults'  => 'required|numeric|min:1|between:1,9',
            'no_of_children'=> 'numeric|between:1,9|min:1',
            'no_of_infants' => 'numeric|between:1,9|lt:no_of_adults',
            'rooms'         => 'required|between:1,9|numeric|min:1',
            'cribs'         => 'numeric|between:1,9|min:1',
            'beds'          => 'numeric|between:1,9|min:1',
        ]);

        if ($validator->fails()) {
            $response = $validator->messages()->first();
            return response()->json(['status' => false, 'message' => $response]);
        }

        //Getting XML ready for API request
        ob_start();
        require public_path().'\xml_requests\low_fare_search.php';
        $requestXML = ob_get_clean();
        //End

        try
        {
//            get xml response
            $sXML = $this->makeRequest($requestXML);

//            parse xml to array
//            $myData = $this->XMLtoArray($sXML);
            $myData = XmlToArray::convert($sXML);

//            prepare data for hotelSearchApi
            $apiData = $this->hotelSearchApi($myData);

            return [ 'status' => true, 'count' => count($apiData),'data' => $apiData];
        }
        catch (\Exception $e)
        {
            return [ 'status' => false,'message' => $e->errorMessage()];
        }
    }

    public function hotelMedia(Request $request) {

        $validator = Validator::make($request->all(), [
            'refId'         => 'required',
            'hotelRefId'    => 'required',
        ]);

        if ($validator->fails()) {
            $response = $validator->messages()->first();
            return response()->json(['status' => false, 'message' => $response]);
        }

        $refId = $request->refId;
        $hotelRefId = $request->hotelRefId;

        if(!file_exists(public_path() . '/cache/hotel/reference_data/' . $refId . '_raw.dat') || !file_exists(public_path() . '/cache/hotel/reference_data/' . $refId . '_parsed.dat'))
        {
            $response['status']	= 'error';
            $response['msg']	= 'Invalid Reference ID.';
            return $response;
        }

        /*Stored JSON Parsing*/
        try
        {
            $reference_response = json_decode(file_get_contents(public_path() . '/cache/hotel/reference_data/' . $refId . '_parsed.dat'), true);
            if(json_last_error() != JSON_ERROR_NONE)
            {
                return ['status' => false, 'msg' => 'Something went wrong.'];
            }

            $index = 0;
            $airSegmentRefKeys		= [];
            $availabilitySources 	= [];
            $planes 				= [];
            $fareBasises 			= [];
            $availabilityDisplayTypes = [];
            $groups	 				= [];
            $carriers 				= [];
            $flightNumbers			= [];
            $origins 				= [];
            $destinations 			= [];
            $departs 				= [];
            $arrivals 				= [];
            $flightTimes 			= [];
            $distances 				= [];
            $classes				= [];
            $connections 			= [];
            $tripType 				= "one_way";

            $flight_info 			= [];

            foreach($reference_response as $hotel)
            {
                if($hotel['hotelRefId'] == $hotelRefId)
                {
                    if($isSerene)
                    {
                        $index = 1;
                        $flight_info = $flight;
                        break;
                    }

                    foreach($flight['outbound_route'] as $route)
                    {
                        if(!isset($route['air_segment_ref_key']))
                            continue;
                        $airSegmentRefKeys[$index]		= $route['air_segment_ref_key'];
                        $availabilitySources[$index] 	= $route['availability_source'];
                        $planes[$index] 				= $route['plane'];
                        $fareBasises[$index] 			= $route['fare_basis'];
                        $availabilityDisplayTypes[$index] = $route['availability_display_type'];
                        $groups[$index]	 				= $route['group'];
                        $carriers[$index] 				= $route['carrier'];
                        $flightNumbers[$index]			= $route['flight_number'];
                        $origins[$index] 				= $route['from'];
                        $destinations[$index] 			= $route['to'];
                        $departs[$index] 				= $route['depart'];
                        $arrivals[$index] 				= $route['arrival'];
                        $flightTimes[$index] 			= $route['flight_time'];
                        $distances[$index] 				= $route['distance'];
                        $classes[$index]				= $route['class'];
                        $index++;
                        $airline 						= $route['airline'];
                        $class 							= $route['cabin_class'];
                    }

                    $carrier 		= $carriers[0];
                    $from 			= $origins[0];
                    $to 			= $destinations[$index - 1];

                    if(isset($flight['inbound_route']))
                    {
                        foreach($flight['inbound_route'] as $route)
                        {
                            if(!isset($route['air_segment_ref_key']))
                                continue;
                            $airSegmentRefKeys[$index]		= $route['air_segment_ref_key'];
                            $availabilitySources[$index] 	= $route['availability_source'];
                            $planes[$index] 				= $route['plane'];
                            $fareBasises[$index] 			= $route['fare_basis'];
                            $availabilityDisplayTypes[$index] = $route['availability_display_type'];
                            $groups[$index]	 				= $route['group'];
                            $carriers[$index] 				= $route['carrier'];
                            $flightNumbers[$index]			= $route['flight_number'];
                            $origins[$index] 				= $route['from'];
                            $destinations[$index] 			= $route['to'];
                            $departs[$index] 				= $route['depart'];
                            $arrivals[$index] 				= $route['arrival'];
                            $flightTimes[$index] 			= $route['flight_time'];
                            $distances[$index] 				= $route['distance'];
                            $classes[$index]				= $route['class'];
                            $index++;
                        }
                    }
                    $connections 	= isset($flight['connection']) ? $flight['connection'] : [];
                    $tripType 		= isset($flight['direction']) ? $flight['direction'] : "one_way";
                    break;
                }
            }
            if($index == 0)
            {
                throw new AirException("Invalid Journey Reference ID.");
            }
        }
        catch (AirException $e)
        {
            $response['status'] = "error";
            $response['msg'] 	= $e->errorMessage();
            die(json_encode($response));
        }
        /*End Parsing*/

        if($isSerene && !empty($flight_info))
        {
            require_once getcwd() . DS . "AirModels" . DS . "SereneAirPrice.php";
        }
        else
        {
            //Getting XML ready for API request
            ob_start();
            require getcwd() . DS . REQUEST_DIR . DS . AIR_PRICE_REQUEST_FILE;
            $requestXML = ob_get_clean();
            //End

            require_once getcwd() . DS . "AirModels" . DS . "AirPrice.php";
        }

        try
        {
            if($isSerene && !empty($flight_info))
            {
                $flight_info['no_of_adults'] 	= $getAdultNo;
                $flight_info['no_of_children'] 	= $getChildNo;
                $flight_info['no_of_infants'] 	= $getInfantNo;

                $data = SereneAirPrice::makeRequest($flight_info);

                if(!copy(getcwd() . '/cache/air/' . SereneAirPrice::getCacheFilename() . '_raw.dat', getcwd() . '/cache/air/reference_data/' . $refId . '_raw_price.dat') || !copy(getcwd() . '/cache/air/' . SereneAirPrice::getCacheFilename() . '_parsed.dat', getcwd() . '/cache/air/reference_data/' . $refId . '_parsed_price.dat'))
                {
                    throw new AirException("Something Went Wrong. Please Try Again".SereneAirPrice::getCacheFilename());
                }
            }
            else
            {
                $requestXML = preg_replace('/FareBasisCode=\".+\"/', '', $requestXML);

                AirPrice::makeRequest($requestXML);
                $data 	= AirPrice::parseResponse(['direction' => $tripType, 'carrier' => $carrier, 'airline' => $airline, 'class' => $class, 'from' => $from, 'to' => $to, 'no_of_adults' => $getAdultNo, 'no_of_children' => $getChildNo, 'no_of_infants' => $getInfantNo]);

                if(!copy(getcwd() . '/cache/air/' . AirPrice::getCacheFilename() . '_raw.dat', getcwd() . '/cache/air/reference_data/' . $refId . '_raw_price.dat') || !copy(getcwd() . '/cache/air/' . AirPrice::getCacheFilename() . '_parsed.dat', getcwd() . '/cache/air/reference_data/' . $refId . '_parsed_price.dat'))
                {
                    throw new AirException("Something Went Wrong. Please Try Again");
                }
            }

            $response['status'] = "success";
            $response['msg'] 	= count($data) . " matching results.";
            $response['data'] 	= $data;
        }
        catch (AirException $e)
        {
            $response['status'] = "error";
            $response['msg'] 	= $e->errorMessage();
        }

        die(json_encode($response));
    }

}
