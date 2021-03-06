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
        $request = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
<soapenv:Header/>
<soapenv:Body>
<hot:HotelDetailsReq xmlns:hot="http://www.travelport.com/schema/hotel_v34_0" TargetBranch="P7119574">
<com:BillingPointOfSaleInfo xmlns:com="http://www.travelport.com/schema/common_v34_0" OriginApplication="UAPI"/>
<hot:HotelProperty HotelChain="HI" HotelCode="43163" Name="HOLIDAY INN SYDNEY AIRPORT"/>
<hot:HotelDetailsModifiers NumberOfAdults="1" RateRuleDetail="None">
<com:PermittedProviders xmlns:com="http://www.travelport.com/schema/common_v34_0">
<com:Provider Code="1G"/>
</com:PermittedProviders>
<hot:HotelStay>
<hot:CheckinDate>2020-12-10</hot:CheckinDate>
<hot:CheckoutDate>2020-12-20</hot:CheckoutDate>
</hot:HotelStay>
</hot:HotelDetailsModifiers>
</hot:HotelDetailsReq>
</soapenv:Body>
</soapenv:Envelope>';

      //  echo phpinfo();exit;

        $finalArray = [];
        $sXML = $this->makeRequest($request);
        $myData = $this->XMLtoArray($sXML);

        dd($myData);
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

            $sXML = $this->makeRequest($requestXML, true);

//            parse xml to array
            $myData = $this->XMLtoArray($sXML);

//            prepare data for hotelSearchApi
            $apiData = $this->hotelMediaApi($myData, true);

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

            return $myData;

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
