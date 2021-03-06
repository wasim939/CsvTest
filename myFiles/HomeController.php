<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

use Config;

class HomeController extends RequestController
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

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

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

        $xmlResponse = $this->makeRequest($request);
//        return $xmlResponse;


        return $this->makeData($xmlResponse);
    }

    public function makeData($data) {

        $finalArray = [];

        $finalArray['ReferencePoint'] = $data['SOAP:Envelope']['SOAP:Body']['hotel:HotelSearchAvailabilityRsp']['hotel:ReferencePoint'];
//        $finalArray['']

        $HotelSearchResult = $data['SOAP:Envelope']['SOAP:Body']['hotel:HotelSearchAvailabilityRsp']['hotel:HotelSearchResult'];

        foreach ($HotelSearchResult as $item) {

            $finalArray['hoteInfo'][] = $item['hotel:HotelProperty']['@attributes'];


            $finalArray['hoteInfo'][]['address'] = $item['hotel:HotelProperty']['hotel:PropertyAddress']['hotel:Address'];
            $finalArray['hoteInfo'][]['distance'] = $item['hotel:HotelProperty']['common_v34_0:Distance']['@attributes']['Value'].'KM';
        }
        return $finalArray;

    }
}
