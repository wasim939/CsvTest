<?php

if(!defined('AIR_ENVIROMENT') && !defined('ENVIROMENT'))
{
    define('AIR_ENVIROMENT', 'production');
    define('ENVIROMENT', 'live');
    define('REQUEST_DIR', '');
    require_once "config.php";
}
 
if(!defined('AIR_ENVIROMENT'))
	die("Air Enviroment Not Defined.");
/**
 * @Author: Umar Hayat
 * @Date:   2019-08-02 16:28:03
 * @Last Modified by:   Umar Hayat
 * @Last Modified time: 2019-09-17 15:18:12
 */
require_once "AirException.php";
require_once "DB.php";

class AirModel
{
	const TP_USERNAME 		= TP_USERNAME;
	const TP_PASSWORD 		= TP_PASSWORD;
	const TP_TARGET_BRANCH 	= TP_TARGET_BRANCH;
	const TP_URL 			= TP_URL;
	const TP_GDS_CODE 		= TP_GDS_CODE;

	protected static $rawResponse;
	protected static $responseArray = [];
	protected static $cacheFile;

	public static function makeRequest($request)
	{
		self::$responseArray 	= [];

		self::$cacheFile 		= md5($request);

		// self::$rawResponse = file_get_contents(getcwd() . '/cache/air/' . self::$cacheFile . '_raw.dat');
		// return self::$rawResponse;

		// if(file_exists(getcwd() . '/cache/air/' . self::$cacheFile . '_raw.dat') )//&& file_exists(getcwd() . '/cache/air/' . self::$cacheFile . '_parsed.dat'))
		// {
		// 	self::$rawResponse 	 = file_get_contents(getcwd() . '/cache/air/' . self::$cacheFile . '_raw.dat');
		// 	// self::$responseArray = json_decode(file_get_contents(getcwd() . '/cache/air/' . self::$cacheFile . '_parsed.dat'));

		// 	return self::$rawResponse;
		// }

		file_put_contents(getcwd() . '/cache/air/' . self::$cacheFile . '_request.dat', $request);

		$auth 		= base64_encode(self::TP_USERNAME . ':' . self::TP_PASSWORD); 
		try
		{
			$soap_do 	= curl_init(self::TP_URL);
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
				throw new AirException("Unable to make request to server. Error: " . curl_errno($soap_do));
			}

			curl_close($soap_do);
		}
		catch(Exception $e)
		{
			throw new AirException("Unable to make request to server.");
		}

		$xml 	= simplexml_load_String(self::$rawResponse, null, null, 'SOAP', true);	
		
		if(empty($xml))
		{
			throw new AirException("Encoding Error.");
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
						throw new AirException($child);
						return false;
					}
				}
			}
		}

		file_put_contents(getcwd() . '/cache/air/' . self::$cacheFile . '_raw.dat', self::prettyPrint(self::$rawResponse));

		return self::$rawResponse;
	}

	protected static function prettyPrint($xml)
	{
		$dom 						= new DOMDocument;
		$dom->preserveWhiteSpace 	= false;
		$dom->loadXML($xml);
		$dom->formatOutput 			= true;

		return $dom->saveXML();	
	}

	protected static function airlineByCode($iata)
	{
		$query 	= "SELECT * FROM `airlines` where iata = :iata LIMIT 1";
		$conds 	= ['iata' => (string) $iata];
		try
		{
			$stmt 	= DB::getConnection()->prepare($query);
			$stmt->execute($conds);

			if($stmt->rowCount() > 0)
				return $stmt->fetch()['name'];

			return (string) $iata;
		}
		catch(PDOException $e)
		{
			return (string) $iata;
		}
	}

	protected static function planeByCode($iata)
	{
		$query 	= "SELECT * FROM `planes` where iata = :iata LIMIT 1";
		$conds 	= ['iata' => (string) $iata];
		try
		{
			$stmt 	= DB::getConnection()->prepare($query);
			$stmt->execute($conds);

			if($stmt->rowCount() > 0)
				return $stmt->fetch()['name'];

			return (string) $iata;
		}
		catch(PDOException $e)
		{
			return (string) $iata;
		}
	}

	public static function getCacheFilename()
	{
		return self::$cacheFile;
	}
}