<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Config;
use Sunra\PhpSimple\HtmlDomParser;

class HomeController extends RequestController
{

    protected static $rawResponse;
    protected static $responseArray = [];
    protected static $cacheFile;

    public function xmlTest()
    {
        $request = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
  <soapenv:Header />
  <soapenv:Body>
    <hot:HotelSearchAvailabilityReq xmlns:hot="http://www.travelport.com/schema/hotel_v34_0" TargetBranch="P7119574">
      <com:BillingPointOfSaleInfo xmlns:com="http://www.travelport.com/schema/common_v34_0" OriginApplication="UAPI" />
      <hot:HotelSearchLocation>
        <hot:HotelLocation Location="SYD" />
      </hot:HotelSearchLocation>
      <hot:HotelSearchModifiers MaxWait="50000" NumberOfAdults="1">
        <com:PermittedProviders xmlns:com="http://www.travelport.com/schema/common_v34_0">
          <com:Provider Code="1G" />
        </com:PermittedProviders>
      </hot:HotelSearchModifiers>
      <hot:HotelStay>
        <hot:CheckinDate>2020-12-10</hot:CheckinDate>
        <hot:CheckoutDate>2020-12-20</hot:CheckoutDate>
      </hot:HotelStay>
    </hot:HotelSearchAvailabilityReq>
  </soapenv:Body>
</soapenv:Envelope>';

      //  echo phpinfo();exit;

        $finalArray = [];
        $sXML = $this->makeRequest($request);
        $myData = $this->XMLtoArray($sXML);

        /*foreach ($myData['SOAP:Envelope']['SOAP:Body']['hotel:HotelSearchAvailabilityRsp']['hotel:HotelSearchResult'] as $data) {

            $additional_info = [
                'address' =>    $data['hotel:HotelProperty']['hotel:PropertyAddress']['hotel:Address'],
                'distance' => $data['hotel:HotelProperty']['common_v34_0:Distance']['@attributes']['Value'].' '.$data['hotel:HotelProperty']['common_v34_0:Distance']['@attributes']['Units']
            ];
            $hoteInfo = [
                'hoteInfo' => $data['hotel:HotelProperty']['@attributes'],
                'additionalInfo' => $additional_info
            ];
            $finalArray['data'][] = $hoteInfo;
        }*/

        /*foreach ($myData['SOAP:Envelope']['SOAP:Body']['hotel:HotelSearchAvailabilityRsp']['hotel:HotelSearchResult'] as $data) {

            $hotelInfo['hoteInfo'] =$data['hotel:HotelProperty']['@attributes'];

            $hotelInfo['additionalInfo'] = $additional_info = [
                'address' =>    $data['hotel:HotelProperty']['hotel:PropertyAddress']['hotel:Address'],
                'distance' => $data['hotel:HotelProperty']['common_v34_0:Distance']['@attributes']['Value'].' '.$data['hotel:HotelProperty']['common_v34_0:Distance']['@attributes']['Units']
            ];;
            $finalArray['data'][] = $hotelInfo;
        }*/

        foreach ($myData['SOAP:Envelope']['SOAP:Body']['hotel:HotelSearchAvailabilityRsp']['hotel:HotelSearchResult'] as $data) {

            $hotelInfo['hoteInfo'] = $data['hotel:HotelProperty']['@attributes'];
            $hotelInfo['addressInfo'] = ['address' => ['streetInfo' => $data['hotel:HotelProperty']['hotel:PropertyAddress']['hotel:Address']]];

            $finalArray['data'][] = $hotelInfo;
        }

        return $finalArray;


    }

    public function prepareData($data) {
        $finalArray = [];
        dd($data['SOAP:Envelope']);
    }


    function XMLtoArray($xml) {
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

    function DOMtoArray($root) {
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




    private function xml_to_array($contents, $get_attributes=1){

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
    } // end XML to array function


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
}
