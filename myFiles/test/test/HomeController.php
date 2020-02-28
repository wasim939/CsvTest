<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use SoapClient;
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
        return $this->makeData($xmlResponse);
    }

    public function makeData($data) {

        foreach ($data['SOAP:Envelope']['SOAP:Body']['hotel:HotelSearchAvailabilityRsp']['hotel:HotelSearchResult'] as $data) {

            $hotelInfo['hoteInfo'] = $data['hotel:HotelProperty']['@attributes'];
            $hotelInfo['addressInfo'] = ['address' => ['streetInfo' => $data['hotel:HotelProperty']['hotel:PropertyAddress']['hotel:Address']]];

            $finalArray['data'][] = $hotelInfo;
        }
        return $finalArray;
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
}
