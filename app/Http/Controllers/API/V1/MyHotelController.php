<?php

namespace App\Http\Controllers\API\V1;
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
use App\Http\Controllers\Controller;
use Mtownsend\XmlToArray\XmlToArray;

class MyHotelController extends MyRequestController
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

//        Getting XML ready for API request
        ob_start();
        require public_path().'\xml_requests\low_fare_search.php';
        $requestXML = ob_get_clean();
        //End

//        Get XML response
        $sXML = MyRequestController::makeServerRequest($requestXML);

        if(!$sXML['status']) {
            return [ 'status' => false,'message' => 'Server Error Occured'];
        }

        try
        {
//            convert XML to Array
            $myData = XmlToArray::convert($sXML['data']);

//            Prepare Data
            $apiData = MyRequestController::hotelSearchApi($myData);
            if(!$apiData['status']) {
                return [ 'status' => false,'message' => 'Error Occured in preparing data'];
            }
            $refId = MyRequestController::getCacheFilename();

            return [ 'status' => true, 'refId' => $refId ,'data' => $apiData];
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
//            dd($hotelInfo);

        if($hotelInfo) {
            //Getting XML ready for API request
            ob_start();
            require public_path().'\xml_requests\hotel_media_req.php';
            $requestXML = ob_get_clean();
            //End

            $sXML = MyRequestController::makeServerRequest($requestXML, true);

            if(!$sXML['status']) {
                return [ 'status' => false,'message' => 'Server Error Occured'];
            }

            try
            {
//            convert XML to Array
                $myData = XmlToArray::convert($sXML['data']);

//            Prepare Data
                $apiData = MyRequestController::hotelMediaApi($myData);
                if(!$apiData['status']) {
                    return [ 'status' => false,'message' => 'Error Occured in preparing data'];
                }
                return [ 'status' => true,'data' => $apiData];
            }
            catch (\Exception $e)
            {
                return [ 'status' => false,'message' => $e->errorMessage()];
            }
        } else {
            return ['status' => false, 'msg' => 'Invalid Hotel Reference Id'];
        }
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

        $refId = $request->refId;//1b66b3d94fd4039107a0d008d707821f
        $hotelRefId = $request->hotelRefId;//078c8a3b6d79b1b0da33c136413f1568

        if(!file_exists(public_path() . '/cache/hotel/reference_data/' . $refId . '_raw.dat') || !file_exists(public_path() . '/cache/hotel/reference_data/' . $refId . '_parsed.dat'))
        {
            $response['status']	= 'error';
            $response['msg']	= 'Invalid Reference ID.';
            return $response;
        }

        /*Stored JSON Parsing*/
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
//            dd($hotelInfo);

        if($hotelInfo) {
            //Getting XML ready for API request
            ob_start();
            require public_path().'\xml_requests\hotel_rate_req.php';
            $requestXML = ob_get_clean();
            //End

            $sXML = MyRequestController::makeServerRequest($requestXML, true);

            if(!$sXML['status']) {
                return [ 'status' => false,'message' => 'Server Error Occured'];
            }

            try
            {
//            convert XML to Array
                $myData = XmlToArray::convert($sXML['data']);

//            Prepare Data
                $apiData = MyRequestController::hotelRateInfoApi($myData);
                if(!$apiData['status']) {
                    return [ 'status' => false,'message' => 'Error Occured in preparing data'];
                }
                return [ 'status' => true,'data' => $apiData];
            }
            catch (\Exception $e)
            {
                return [ 'status' => false,'message' => $e->errorMessage()];
            }
        } else {
            return ['status' => false, 'msg' => 'Invalid Hotel Reference Id'];
        }
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
//            dd($hotelInfo);

        if($hotelInfo) {
            //Getting XML ready for API request
            ob_start();
            require public_path().'\xml_requests\hotel_rule_req.php';
            $requestXML = ob_get_clean();
            //End

            $sXML = MyRequestController::makeServerRequest($requestXML, true);

            if(!$sXML['status']) {
                return [ 'status' => false,'message' => 'Server Error Occured'];
            }

            try
            {
//            convert XML to Array
                $myData = XmlToArray::convert($sXML['data']);

//            Prepare Data
                $apiData = MyRequestController::hotelRuleInfoApi($myData);
                if(!$apiData['status']) {
                    return [ 'status' => false,'message' => 'Error Occured in preparing data'];
                }
                return [ 'status' => true,'data' => $apiData];
            }
            catch (\Exception $e)
            {
                return [ 'status' => false,'message' => $e->errorMessage()];
            }
        } else {
            return ['status' => false, 'msg' => 'Invalid Hotel Reference Id'];
        }


    }

    public function hotelDescInfo(Request $request) {

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
//            dd($hotelInfo);

        if($hotelInfo) {
            //Getting XML ready for API request
            ob_start();
            require public_path().'\xml_requests\hotel_description_req.php';
            $requestXML = ob_get_clean();
            //End

            $sXML = MyRequestController::makeServerRequest($requestXML, true);

            if(!$sXML['status']) {
                return [ 'status' => false,'message' => 'Server Error Occured'];
            }

            try
            {
//            convert XML to Array
                $myData = XmlToArray::convert($sXML['data']);

//            Prepare Data
                $apiData = MyRequestController::hotelDescInfoApi($myData);
                if(!$apiData['status']) {
                    return [ 'status' => false,'message' => 'Error Occured in preparing data'];
                }
                return [ 'status' => true,'data' => $apiData];
            }
            catch (\Exception $e)
            {
                return [ 'status' => false,'message' => $e->errorMessage()];
            }
        } else {
            return ['status' => false, 'msg' => 'Invalid Hotel Reference Id'];
        }

    }

    public function hotelBookingInfo(Request $request) {

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
//            dd($hotelInfo);

        if($hotelInfo) {
            //Getting XML ready for API request
            ob_start();
            require public_path().'\xml_requests\hotel_booking_req.php';
            $requestXML = ob_get_clean();
//            dd($requestXML);
            //End

            $sXML = MyRequestController::makeServerRequest($requestXML, true);

            if(!$sXML['status']) {
                return [ 'status' => false,'message' => 'Server Error Occured'];
            }

            try
            {
//            convert XML to Array
                $myData = XmlToArray::convert($sXML['data']);
                dd($myData);

//            Prepare Data
                $apiData = MyRequestController::hotelBookingInfoApi($myData);
                if(!$apiData['status']) {
                    return [ 'status' => false,'message' => 'Error Occured in preparing data'];
                }
                return [ 'status' => true,'data' => $apiData];
            }
            catch (\Exception $e)
            {
                return [ 'status' => false,'message' => $e->errorMessage()];
            }
        } else {
            return ['status' => false, 'msg' => 'Invalid Hotel Reference Id'];
        }

    }

//https://support.travelport.com/webhelp/GWS/Content/TRANSACTIONHELP/1API_Dev_Notes/HotelShoppingandBooking.pdf


}
