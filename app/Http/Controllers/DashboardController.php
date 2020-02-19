<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use SoapClient;
class DashboardController extends Controller
{
    public function index() {
        return view('admin.index');
    }

    public function tests() {

        $wsdl_url = 'http://www.dataaccess.com/webservicesserver/numberconversion.wso?WSDL';
//        method1
        /*try{
            $clinet     =   new SoapClient($wsdl_url);
            $ver        =   array("dNum"=>"2002");
            $response=$clinet->NumberToDollars($ver);
            dd($response);
        }
        catch(SoapFault $e)
        {
            echo $e->getMessage();
        }*/

//        method2
        /*$soapClient   = new SoapClient($wsdl_url,['trace'=>true,'cache_wsdl'=>WSDL_CACHE_MEMORY]);
        $soapRequest    = '<NumberToDollars>
                                <dNum>20</dNum>
                          </NumberToDollars>';
        $xmlr1          = simplexml_load_string($soapRequest);
        $response = $soapClient->NumberToDollars($xmlr1);
        dd($response);*/

//        method3
        /*$soap_request  = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:web=\"http://www.dataaccess.com/webservicesserver/\">\n";
        $soap_request .= "<soapenv:Header/>\n";
        $soap_request .= "  <soapenv:Body>\n";
        $soap_request .= "    <web:NumberToDollars>\n";
        $soap_request .= "      <web:dNum>10</web:dNum>\n";
        $soap_request .= "    </web:NumberToDollars>\n";
        $soap_request .= "  </soapenv:Body>\n";
        $soap_request .= "</soapenv:Envelope>";

        $header = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: \"run\"",
            "Content-length: ".strlen($soap_request),
        );

        $soap_do = curl_init();
        curl_setopt($soap_do, CURLOPT_URL, $wsdl_url );
        curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($soap_do, CURLOPT_TIMEOUT,        10);
        curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($soap_do, CURLOPT_POST,           true );
        curl_setopt($soap_do, CURLOPT_POSTFIELDS,     $soap_request);
        curl_setopt($soap_do, CURLOPT_HTTPHEADER,     $header);

        $output =   curl_exec($soap_do);

        if($output === false) {
            $err = 'Curl error: ' . curl_error($soap_do);
            curl_close($soap_do);
            print $err;
        } else {
            curl_close($soap_do);
            return $output;
        }*/
    }

    public function test() {

        $wsdl_url = 'http://www.dataaccess.com/webservicesserver/numberconversion.wso?WSDL';
        $soapClient   = new SoapClient($wsdl_url,['trace'=>true,'cache_wsdl'=>WSDL_CACHE_MEMORY]);
        $soapRequest    = '<NumberToDollars>
                                <dNum>20</dNum>
                          </NumberToDollars>';
        $xmlr1          = simplexml_load_string($soapRequest);

        $response = $soapClient->NumberToDollars($xmlr1);
        dd($response);

    }
}
