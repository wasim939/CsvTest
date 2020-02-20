<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

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
        $username         = Config::get('constants.travelport.pre_production.username');
        $password         = Config::get('constants.travelport.pre_production.password');
        $target_branch    = Config::get('constants.travelport.pre_production.target_branch');
        $url              = Config::get('constants.travelport.pre_production.url');
        $gds_code         = Config::get('constants.travelport.pre_production.gds_code');

//        self::$responseArray 	= [];

        self::$cacheFile 		= md5($request);

        //file_put_contents(getcwd() . '/cache/air/' . self::$cacheFile . '_request.dat', $request);

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

        //file_put_contents(getcwd() . '/cache/air/' . self::$cacheFile . '_raw.dat', self::prettyPrint(self::$rawResponse));

        return self::$rawResponse;
    }
}
