<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

use Config;

class RequestController extends Controller
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

    public function makeRequest($request)
    {
        $username         = 'Universal API/uAPI5164233131-8f975dd6';
        $password         = 'j+2A7wT{F4';
        $target_branch    = 'P7119574';
        $url              = 'https://apac.universal-api.pp.travelport.com/B2BGateway/connect/uAPI/HotelService';
        $gds_code         = '1G';

//        self::$responseArray 	= [];

        self::$cacheFile 		= md5($request);

        file_put_contents(public_path() . '/cache/hotel/reference_data/' . self::$cacheFile . '_request.dat', $request);

        $auth 		= base64_encode($username . ':' . $password);
        try
        {
            $soap_do 	= curl_init($url);
            $header 	= array(
                "Content-Type: text/xml;charset=UTF-8",
                "Accept: gzip,deflate",
                "Cache-Control: no-cache",
                "Pragma: no-cache",
                "SOAPAction: \"\"",
                "Authorization: Basic $auth",
                "Content-length: " . strlen($request),
            );

            //curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 30);
            //curl_setopt($soap_do, CURLOPT_TIMEOUT, 30);
            curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($soap_do, CURLOPT_POST, true );
            curl_setopt($soap_do, CURLOPT_POSTFIELDS, $request);
            curl_setopt($soap_do, CURLOPT_HTTPHEADER, $header);
            curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true);
            self::$rawResponse = curl_exec($soap_do);


            if (curl_errno($soap_do))
            {
                //throw new AirException("Unable to make request to server. Error: " . curl_errno($soap_do));
                echo "error";
            }

            curl_close($soap_do);
        }
        catch(\Exception $e)
        {
            //throw new AirException("Unable to make request to server.");
            echo $e->getMessage();
        }

        $xml 	= simplexml_load_String(self::$rawResponse, null, null, 'SOAP', true);

        if(empty($xml))
        {
            //throw new AirException("Encoding Error.");
            return false;
        }

        $Results = $xml->children('SOAP',true);
        foreach($Results->children('SOAP',true) as $fault)
        {
            if(strcmp($fault->getName(), 'Fault') == 0)
            {
                foreach ($fault->children() as $child)
                {
                    if(strcmp($child->getName(), 'faultstring') == 0)
                    {
//                        throw new AirException($child);
                        return false;
                    }
                }
            }
        }
        file_put_contents(public_path() . '/cache/hotel/reference_data/' . self::$cacheFile . '_raw.dat', self::prettyPrint(self::$rawResponse));
        return self::$rawResponse;
    }

    protected static function prettyPrint($xml)
    {
        $dom 						= new \DOMDocument();
        $dom->preserveWhiteSpace 	= false;
        $dom->loadXML($xml);
        $dom->formatOutput 			= true;

        return $dom->saveXML();
    }

    protected function XMLtoArray($xml) {

        $previous_value = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->loadXml($xml);
        libxml_use_internal_errors($previous_value);
        if (libxml_get_errors()) {
            return [];
        }
        return $this->DOMtoArray($dom);
    }

    protected function DOMtoArray($root) {
        $result = array();

        if ($root->hasAttributes()) {
            $attrs = $root->attributes;
            foreach ($attrs as $attr) {
                $result['@attributes'][$attr->name] = $attr->value;
            }
        }

        if ($root->hasChildNodes()) {
            $children = $root->childNodes;
            if ($children->length == 1) {
                $child = $children->item(0);
                if (in_array($child->nodeType,[XML_TEXT_NODE,XML_CDATA_SECTION_NODE])) {
                    $result['_value'] = $child->nodeValue;
                    return count($result) == 1
                        ? $result['_value']
                        : $result;
                }

            }
            $groups = array();
            foreach ($children as $child) {
                if (!isset($result[$child->nodeName])) {
                    $result[$child->nodeName] = $this->DOMtoArray($child);
                } else {
                    if (!isset($groups[$child->nodeName])) {
                        $result[$child->nodeName] = array($result[$child->nodeName]);
                        $groups[$child->nodeName] = 1;
                    }
                    $result[$child->nodeName][] = $this->DOMtoArray($child);
                }
            }
        }
        return $result;
    }

    public static function getCacheFilename()
    {
        return self::$cacheFile;
    }

    protected function hotelSearchApi($myData) {

        $finalArray = [];
        foreach ($myData['SOAP:Envelope']['SOAP:Body']['hotel:HotelSearchAvailabilityRsp']['hotel:HotelSearchResult'] as $data) {

            $hotelInfo['hotelRefId']    = md5(time() . uniqid() . rand(99, 99999));
            $hotelInfo['hotelInfo']     = $data['hotel:HotelProperty']['@attributes'];
            $hotelInfo['addressInfo']   = [
                'address' => [
                    'streetInfo'    => $data['hotel:HotelProperty']['hotel:PropertyAddress']['hotel:Address'],
                    'distance'      => $data['hotel:HotelProperty']['common_v34_0:Distance']['@attributes']['Value'].' '.$data['hotel:HotelProperty']['common_v34_0:Distance']['@attributes']['Units']
                ]
            ];
            $hotelInfo['hotelRating'] = $data['hotel:HotelProperty']['hotel:HotelRating']['hotel:Rating'];

            //            hote amenities
            /*..............*/
            /*$amenityArray = [];
            foreach ($data['hotel:HotelProperty']['hotel:Amenities']['hotel:Amenity'] as $amenity) {
                $amenityArray[] = $amenity['@attributes']['Code'];
            }
            $hotelInfo['hotelAmenities'] = $amenityArray;*/
            /*..............*/

            $hotelInfo['hotelRateInfo'] = $data['hotel:RateInfo']['@attributes']??'';
            $hotelInfo['vendorLocationInfo'] = $data['common_v34_0:VendorLocation']['@attributes'];
            $finalArray[] = $hotelInfo;
        }
        file_put_contents(public_path() . '/cache/hotel/reference_data/' . self::$cacheFile . '_parsed.dat', json_encode($finalArray));
        return $finalArray;
    }

    protected function hotelMediaApi($myData) {

        $finalArray = [];
        $i = 0;
        foreach ($myData['SOAP:Envelope']['SOAP:Body']['hotel:HotelMediaLinksRsp']['hotel:HotelPropertyWithMediaItems']['common_v34_0:MediaItem'] as $data) {

            if($i <=4){
                $hotelMedia['hotelMediaInfo']     = $data['@attributes'];
                $finalArray[] = $hotelMedia;
                $i++;
            }
        }
        file_put_contents(public_path() . '/cache/hotel/reference_data/' . self::$cacheFile . '_parsed.dat', json_encode($finalArray));
        return $finalArray;
    }

    protected function hotelRateInfoApi($myData) {


        $finalArray = [];
        foreach ($myData['SOAP:Envelope']['SOAP:Body']['hotel:HotelDetailsRsp']['hotel:RequestedHotelDetails']['hotel:HotelRateDetail'] as $data) {
            $additional_info = [
                'ratePlanInfo'          => $data['@attributes'],
                'hotelRateByDateInfo'   => $data['hotel:HotelRateByDate']['@attributes'],
                'cancellationInfo'      => $data['hotel:CancelInfo']['@attributes']??'',
                'hotelGuaranteeInfo'    => $data['hotel:GuaranteeInfo']['@attributes']

            ];
            $hotelInfo = [
                'hotelRoomRateInfo'     => $additional_info
            ];
            $finalArray[] = $hotelInfo;
        }
        return $finalArray;


    }

    protected function hotelRuleInfoApi($myData) {

        $response = $myData['SOAP:Envelope']['SOAP:Body']['hotel:HotelRulesRsp'];

        $finalArray = [];

//        dd($response['hotel:HotelRuleItem']);
        if(isset($response['common_v34_0:ResponseMessage'])) {
            $finalArray['message']  = $response['common_v34_0:ResponseMessage']['_value'];
        } else {
            foreach($response['hotel:HotelRuleItem'] as $data) {

                $additional_info = [
                    Str::slug($data['@attributes']['Name'], '-')          => $data['hotel:Text'],
                ];
                $finalArray['rule_info'][] = $additional_info;
            }

            foreach($response['hotel:HotelRateDetail']['hotel:RoomRateDescription'] as $data) {

                $additional_info = [
                    Str::slug($data['@attributes']['Name'], '-')          => $data['hotel:Text'],
                ];
                $finalArray['room_rate_description'][] = $additional_info;
            }

            foreach($response['hotel:HotelRateDetail']['hotel:GuaranteeInfo']['hotel:GuaranteePaymentType'] as $data) {

                $additional_info = [
                    $data['@attributes']['Type']         => $data['@attributes']['Description'],
                ];
                $finalArray['guarantee_payment_info'][] = $additional_info;
            }

            $finalArray['rate_by_date'] = $response['hotel:HotelRateDetail']['hotel:HotelRateByDate']['@attributes'];
        }
        return $finalArray;
    }
}
