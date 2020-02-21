<?php

/**
 * @Author: Umar Hayat
 * @Date:   2019-09-16 14:16:34
 * @Last Modified by:   Muhammad Umar Hayat
 * @Last Modified time: 2019-11-01 23:38:24
 */
if(!defined('AIR_ENVIROMENT'))
	die("Air Enviroment Not Defined.");

if(!defined('ENVIROMENT'))
	die("Enviroment Not Defined.");

function registerAirEnviroment()
{
	$config = array('travelport' => [
		'pre_production' => array(
			'username'		=> "Universal API/uAPI5164233131-8f975dd6",
			'password'		=> "j+2A7wT{F4",
			'target_branch'	=> "P7119574",
			'url'			=> "https://apac.universal-api.pp.travelport.com/B2BGateway/connect/uAPI/AirService",
			'gds_code'		=> "1G"
		),
		'production' => array(
			'username'		=> "Universal API/uAPI5887624131-353671c4",
			'password'		=> "eJ$9&8Nf7b",
			'target_branch'	=> "P3468561",
			'url'			=> "https://apac.universal-api.travelport.com/B2BGateway/connect/uAPI/AirService",
			'gds_code'		=> "1G"
		)
	], 
	'database' => [
		'local' => array(
			'host'		=> "localhost",
			'username'	=> "root",
			'password'	=> "",
			'db_name'	=> "bookme",
		),
		'live' => array(
			'host'		=> "localhost",
			'username'	=> "root",
			'password'	=> "",
			'db_name'	=> "bookme",
		)
	]);

	//Travelport
	$travelport = isset($config['travelport'][AIR_ENVIROMENT]) ? $config['travelport'][AIR_ENVIROMENT] : die("Invalid Enviroment Settings.");
	define('TP_USERNAME', $travelport['username']);
	define('TP_PASSWORD', $travelport['password']);
	define('TP_TARGET_BRANCH', $travelport['target_branch']);
	define('TP_URL', $travelport['url']);
	define('TP_GDS_CODE', $travelport['gds_code']);

	//Database
	$database 	= isset($config['database'][ENVIROMENT]) ? $config['database'][ENVIROMENT] : die("Invalid Enviroment Settings.");
	define('DB_HOST', $database['host']);
	define('DB_USERNAME', $database['username']);
	define('DB_PASSWORD', $database['password']);
	define('DB_NAME', $database['db_name']);
}

registerAirEnviroment();