<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Config;
use mysql_xdevapi\Exception;
use Sunra\PhpSimple\HtmlDomParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HomeController extends RequestController
{

    protected static $rawResponse;
    protected static $responseArray = [];
    protected static $cacheFile;

    function __construct() {
        $this->client = new Client();
    }

    public function testGuzzle(){

        $username         = 'Universal API/uAPI5164233131-8f975dd6';
        $password         = 'j+2A7wT{F4';
        $url              = 'https://apac.universal-api.pp.travelport.com/B2BGateway/connect/uAPI/HotelService';
        $request = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
	<soapenv:Header />
	<soapenv:Body>
		<hot:HotelDetailsReq xmlns:com="http://www.travelport.com/schema/common_v34_0"
			xmlns:hot="http://www.travelport.com/schema/hotel_v34_0" ReturnMediaLinks="true" TargetBranch="P7119574">
			<com:BillingPointOfSaleInfo OriginApplication="UAPI" />
			<hot:HotelProperty HotelChain="XV" HotelCode="81750" Name="SPRINGHILL STES SIOUX MARRIOTT" />
			<hot:HotelDetailsModifiers NumberOfAdults="1" RateRuleDetail="Complete">
				<com:PermittedProviders>
					<com:Provider Code="1G" />
				</com:PermittedProviders>
				<hot:HotelStay>
					<hot:CheckinDate>2020-12-10</hot:CheckinDate>
					<hot:CheckoutDate>2020-12-20</hot:CheckoutDate>
				</hot:HotelStay>
			</hot:HotelDetailsModifiers>
		</hot:HotelDetailsReq>
	</soapenv:Body>
</soapenv:Envelope>';

//        self::$responseArray 	= [];

        $auth 		= base64_encode($username . ':' . $password);

        $header = [
            "Content-Type" => "text/xml;charset=UTF-8",
            "Accept" => "gzip,deflate",
            "Cache-Control" => "gzip,deflate",
            "Pragma" => "no-cache",
            "SOAPAction" => "\"\"",
            "Authorization" => "Basic $auth",
            "Content-length" => strlen($request),
        ];

        try {
            /*$res = (new Client())->request('POST', $url, [
                'headers' => $header,
                'body' => $request
            ]);*/

            $res = $this->client->post($url, [
                'headers' => $header,
                'body' => $request
            ]);

            //            parse xml to array
            $myData = $this->XMLtoArray($res->getBody());

//            prepare data for hotelSearchApi
            $apiData = $this->hotelRateInfoApi($myData);

            return [ 'status' => true,'data' => $apiData];

        } catch (GuzzleException $e) {
            return [self::STATUS => false, 'message' => 'Server error' . $e->getMessage()];
        }//..... end of try-catch( )......//


    }


    public function xmlTest()
    {
        /*$request = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
<soapenv:Header/>
<soapenv:Body>
<hot:HotelDetailsReq xmlns:com="http://www.travelport.com/schema/common_v34_0" xmlns:hot="http://www.travelport.com/schema/hotel_v34_0" ReturnMediaLinks="true" TargetBranch="P7119574">
<com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
<hot:HotelProperty HotelChain="HI" HotelCode="43163" Name="HOLIDAY INN SYDNEY AIRPORT"/>
<hot:HotelDetailsModifiers NumberOfAdults="1" RateRuleDetail="Complete">
<com:PermittedProviders>
<com:Provider Code="1G"/>
</com:PermittedProviders>
<hot:HotelStay>
<hot:CheckinDate>2020-12-10</hot:CheckinDate>
<hot:CheckoutDate>2020-12-20</hot:CheckoutDate>
</hot:HotelStay>
</hot:HotelDetailsModifiers>
</hot:HotelDetailsReq>
</soapenv:Body>
</soapenv:Envelope>';*/

        $request = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:com="http://www.travelport.com/schema/common_v34_0" xmlns:hot="http://www.travelport.com/schema/hotel_v34_0">
<soapenv:Header/>
<soapenv:Body>
<hot:HotelRulesReq AuthorizedBy="user" TargetBranch="P7119574" TraceId="trace">
<com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
<hot:HotelRulesLookup Base="" RatePlanType="N1QGOV">
<hot:HotelProperty HotelChain="HI" HotelCode="43163" Name="HOLIDAY INN SYDNEY AIRPORT"/>
<hot:HotelStay>
<hot:CheckinDate>2020-12-10</hot:CheckinDate>
<hot:CheckoutDate>2020-12-20</hot:CheckoutDate>
</hot:HotelStay>
</hot:HotelRulesLookup>
</hot:HotelRulesReq>
</soapenv:Body>
</soapenv:Envelope>';

      //  echo phpinfo();exit;

        $finalArray = [];
        $sXML = $this->makeRequest($request);
        $myData = $this->XMLtoArray($sXML);

        return $myData;

        /*return $myData['SOAP:Envelope']['SOAP:Body']['hotel:HotelDetailsRsp']['hotel:RequestedHotelDetails']['hotel:HotelRateDetail'];

        return $myData;*/

        foreach ($myData['SOAP:Envelope']['SOAP:Body']['hotel:HotelDetailsRsp']['hotel:RequestedHotelDetails']['hotel:HotelRateDetail'] as $data) {

            $additional_info = [
                'ratePlanInfo'          => $data['@attributes'],
                'hotelRateByDateInfo'   => $data['hotel:HotelRateByDate']['@attributes'],
                'cancellationInfo'      => $data['hotel:CancelInfo']['@attributes'],
                'hotelGuaranteeInfo'    => $data['hotel:GuaranteeInfo']['@attributes']

            ];
            $hotelInfo = [
                'hotelRoomRateInfo'     => $additional_info
            ];
            $finalArray['data'][] = $hotelInfo;
        }
        return $finalArray;

        /*foreach ($myData['SOAP:Envelope']['SOAP:Body']['hotel:HotelSearchAvailabilityRsp']['hotel:HotelSearchResult'] as $data) {

            $hotelInfo['hotelInfo'] =$data['hotel:HotelProperty']['@attributes'];

            $hotelInfo['additionalInfo'] = $additional_info = [
                'address' =>    $data['hotel:HotelProperty']['hotel:PropertyAddress']['hotel:Address'],
                'distance' => $data['hotel:HotelProperty']['common_v34_0:Distance']['@attributes']['Value'].' '.$data['hotel:HotelProperty']['common_v34_0:Distance']['@attributes']['Units']
            ];;
            $finalArray['data'][] = $hotelInfo;
        }*/

        foreach ($myData['SOAP:Envelope']['SOAP:Body']['hotel:HotelSearchAvailabilityRsp']['hotel:HotelSearchResult'] as $data) {

            $hotelInfo['hotelInfo'] = $data['hotel:HotelProperty']['@attributes'];
            $hotelInfo['addressInfo'] = ['address' => ['streetInfo' => $data['hotel:HotelProperty']['hotel:PropertyAddress']['hotel:Address']]];

            $finalArray['data'][] = $hotelInfo;
        }

        return $finalArray;


    }

    /*private function xml_to_array($contents, $get_attributes=1){

        if(!$contents) return array();

        if(!function_exists('xml_parser_create')) {

            return array();
        }

        $parser = xml_parser_create();

        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );

        xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 );

        xml_parse_into_struct( $parser, $contents, $xml_values );

        xml_parser_free( $parser );

        if(!$xml_values) return;

        $xml_array = array();

        $parents = array();

        $opened_tags = array();

        $arr = array();

        $current = &$xml_array;

        foreach($xml_values as $data) {

            unset($attributes,$value);

            extract($data);

            $result = '';

            if($get_attributes) {

                $result = array();

                if(isset($value)) $result['value'] = $value;

                if(isset($attributes)) {

                    foreach($attributes as $attr => $val) {

                        if($get_attributes == 1) $result['attr'][$attr] = $val;
                    }
                }

            }elseif(isset($value)) {

                $result = $value;
            }

            if($type == "open") {

                $parent[$level-1] = &$current;

                if(!is_array($current) or (!in_array($tag, array_keys($current)))) {

                    $current[$tag] = $result;

                    $current = &$current[$tag];

                } else {

                    if(isset($current[$tag][0])) {

                        array_push($current[$tag], $result);

                    } else {

                        $current[$tag] = array($current[$tag],$result);
                    }

                    $last = count($current[$tag]) - 1;

                    $current = &$current[$tag][$last];

                }

            }elseif($type == "complete") {

                if(!isset($current[$tag])) {

                    $current[$tag] = $result;
                }else {

                    if((is_array($current[$tag]) and $get_attributes == 0) or (isset($current[$tag][0]) and is_array($current[$tag][0]) and $get_attributes == 1)) {

                        array_push($current[$tag],$result);

                    } else {
                        $current[$tag] = array($current[$tag],$result);
                    }
                }

            }elseif($type == 'close') {

                $current = &$parent[$level-1];
            }
        }
        return($xml_array);
    } // end XML to array function*/


    /**
     * Read csv file data
     *
     * @return void
     */
    public function ReadCsvFile($file)
    {
        $file = fopen($file,"r");

        $i = 0;
        $head_array = array();
        $data_array = array();
        while(! feof($file))
        {
            if($i == 0){
                $head_array = fgetcsv($file);
            }else{
                $temp = array();
                $data = fgetcsv($file);
                if($data !=''){
                    for($k = 0; $k <count($head_array); $k++){
                        $temp[$head_array[$k]] = $data[$k];
                    }
                    $data_array[] = $temp;
                }
            }
            $i++;
        }
        return $data_array;
    }

    /**
     * cache csv file data and retrieve
     *
     * @return void
     */
    public function cacheCsvData()
    {
        $file      =   PUBLIC_PATH('/uploads/transactions.csv');

        /*read csv data*/
        $allData = $this->ReadCsvFile($file);

        /*store data in cache*/
        /*Cache::rememberForever('csvData', function () use($allData) {
            return $allData;
        });
        $csvData = Cache::get('csvData');*/
        Redis::set('csvData', json_encode($allData));
        $csvData = Redis::get('csvData');
        $csvData = json_decode($csvData);

        return response()->json(['status' => true, 'message' => 'csv data retrieved from cache successfully.', 'data' => $csvData], 200) ;
    }

    public function myXml(Request $request) {

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
            $myData = $this->XMLtoArray($sXML);

//            prepare data for hotelSearchApi
            $apiData = $this->hotelSearchApi($myData);

            return [ 'status' => true,'data' => $apiData];
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
            $reference_response = collect($reference_response);
            $filtered = $reference_response->filter(function ($value) use ($hotelRefId) {
                return $value['hotelRefId'] == $hotelRefId;
            });
            $hotelInfo = $filtered->first();

            /*foreach($reference_response as $hotel)
            {
                if($hotel['hotelRefId'] == $hotelRefId)
                {
                    $hotelInfo = $hotel;
                }
            }*/
//            dd($hotelInfo);

            //Getting XML ready for API request
            ob_start();
            require public_path().'\xml_requests\hotel_media_req.php';
            $requestXML = ob_get_clean();
            //End

            //            get xml response

            /*if(!copy(getcwd() . '/cache/air/' . AirPrice::getCacheFilename() . '_raw.dat', getcwd() . '/cache/air/reference_data/' . $refId . '_raw_price.dat') || !copy(getcwd() . '/cache/air/' . AirPrice::getCacheFilename() . '_parsed.dat', getcwd() . '/cache/air/reference_data/' . $refId . '_parsed_price.dat'))
            {
                throw new AirException("Something Went Wrong. Please Try Again");
            }*/

            $sXML = $this->makeRequest($requestXML);

//            parse xml to array
            $myData = $this->XMLtoArray($sXML);

//            prepare data for hotelSearchApi
            $apiData = $this->hotelMediaApi($myData);

            return [ 'status' => true,'data' => $apiData];

        }
        catch (\Exception $e)
        {
            $response['status'] = "error";
            $response['msg'] 	= $e->errorMessage();
           return $response;
        }
        /*End Parsing*/


    }

    public function hotelRateInfo(Request $request) {

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
            $reference_response = collect($reference_response);
            $filtered = $reference_response->filter(function ($value) use ($hotelRefId) {
                return $value['hotelRefId'] == $hotelRefId;
            });
            $hotelInfo = $filtered->first();

            //Getting XML ready for API request
            ob_start();
            require public_path().'\xml_requests\hotel_rate_req.php';
            $requestXML = ob_get_clean();
            //End
//            dd($requestXML);

            //            get xml response

            /*if(!copy(getcwd() . '/cache/air/' . AirPrice::getCacheFilename() . '_raw.dat', getcwd() . '/cache/air/reference_data/' . $refId . '_raw_price.dat') || !copy(getcwd() . '/cache/air/' . AirPrice::getCacheFilename() . '_parsed.dat', getcwd() . '/cache/air/reference_data/' . $refId . '_parsed_price.dat'))
            {
                throw new AirException("Something Went Wrong. Please Try Again");
            }*/

            $sXML = $this->makeRequest($requestXML);
//            $sXML = $this->makeRequestGuzzle($requestXML);

//            parse xml to array
            $myData = $this->XMLtoArray($sXML);

//            prepare data for hotelSearchApi
            $apiData = $this->hotelRateInfoApi($myData);

            return [ 'status' => true,'data' => $apiData];

        }
        catch (\Exception $e)
        {
            $response['status'] = "error";
            $response['msg'] 	= $e->errorMessage();
            return $response;
        }
        /*End Parsing*/


    }

    public function hotelRuleInfo(Request $request) {

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
            $reference_response = collect($reference_response);
            $filtered = $reference_response->filter(function ($value) use ($hotelRefId) {
                return $value['hotelRefId'] == $hotelRefId;
            });
            $hotelInfo = $filtered->first();

            //Getting XML ready for API request
            ob_start();
            require public_path().'\xml_requests\hotel_rule_req.php';
            $requestXML = ob_get_clean();
            //End
//            dd($requestXML);

            //            get xml response

            /*if(!copy(getcwd() . '/cache/air/' . AirPrice::getCacheFilename() . '_raw.dat', getcwd() . '/cache/air/reference_data/' . $refId . '_raw_price.dat') || !copy(getcwd() . '/cache/air/' . AirPrice::getCacheFilename() . '_parsed.dat', getcwd() . '/cache/air/reference_data/' . $refId . '_parsed_price.dat'))
            {
                throw new AirException("Something Went Wrong. Please Try Again");
            }*/

            $sXML = $this->makeRequest($requestXML);
//            $sXML = $this->makeRequestGuzzle($requestXML);

//            parse xml to array
            $myData = $this->XMLtoArray($sXML);

//            prepare data for hotelSearchApi
            $apiData = $this->hotelRuleInfoApi($myData);

            return [ 'status' => true,'data' => $apiData];

        }
        catch (\Exception $e)
        {
            $response['status'] = "error";
            $response['msg'] 	= $e->errorMessage();
            return $response;
        }
        /*End Parsing*/


    }

}
