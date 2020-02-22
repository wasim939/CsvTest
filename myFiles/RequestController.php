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

//        return self::$rawResponse;

//        parse xml to json
//        method1
        return $this->XMLtoArray(self::$rawResponse);

//        method2
//        return $this->parse_xml_into_array(self::$rawResponse);

//        method3
//        return $this->xml2array(self::$rawResponse, $get_attributes = 3, $priority = 'tag'); // it will work 100% if not ping me @skype: sapan.mohannty

    }

    public function XMLtoArray($xml) {
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

    public function DOMtoArray($root) {
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

    function parse_xml_into_array($xml_string, $options = array()) {
        /*
        DESCRIPTION:
        - parse an XML string into an array
        INPUT:
        - $xml_string
        - $options : associative array with any of these keys:
            - 'flatten_cdata' : set to true to flatten CDATA elements
            - 'use_objects' : set to true to parse into objects instead of associative arrays
            - 'convert_booleans' : set to true to cast string values 'true' and 'false' into booleans
        OUTPUT:
        - associative array
        */

        // Remove namespaces by replacing ":" with "_"
        if (preg_match_all("|</([\\w\\-]+):([\\w\\-]+)>|", $xml_string, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $xml_string = str_replace('<'. $match[1] .':'. $match[2], '<'. $match[1] .'_'. $match[2], $xml_string);
                $xml_string = str_replace('</'. $match[1] .':'. $match[2], '</'. $match[1] .'_'. $match[2], $xml_string);
            }
        }

        $output = json_decode(json_encode(@simplexml_load_string($xml_string, 'SimpleXMLElement', ($options['flatten_cdata'] ? LIBXML_NOCDATA : 0))), (@$options['use_objects'] ? false : true));

        // Cast string values "true" and "false" to booleans
        if (@$options['convert_booleans']) {
            $bool = function(&$item, $key) {
                if (in_array($item, array('true', 'TRUE', 'True'), true)) {
                    $item = true;
                } elseif (in_array($item, array('false', 'FALSE', 'False'), true)) {
                    $item = false;
                }
            };
            array_walk_recursive($output, $bool);
        }

        return $output;
    }


    function xml2array($contents, $get_attributes = 1, $priority = 'tag')
    {
        if (!$contents) return array();
        if (!function_exists('xml_parser_create')) {
            // print "'xml_parser_create()' function not found!";
            return array();
        }
        // Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); // http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents) , $xml_values);
        xml_parser_free($parser);
        if (!$xml_values) return; //Hmm...
        // Initializations
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();
        $current = & $xml_array; //Refference
        // Go through the tags.
        $repeated_tag_index = array(); //Multiple tags with same name will be turned into an array
        foreach($xml_values as $data) {
            unset($attributes, $value); //Remove existing values, or there will be trouble
            // This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data); //We could use the array by itself, but this cooler.
            $result = array();
            $attributes_data = array();
            if (isset($value)) {
                if ($priority == 'tag') $result = $value;
                else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
            }
            // Set the attributes too.
            if (isset($attributes) and $get_attributes) {
                foreach($attributes as $attr => $val) {
                    if ( $attr == 'ResStatus' ) {
                        $current[$attr][] = $val;
                    }
                    if ($priority == 'tag') $attributes_data[$attr] = $val;
                    else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                }
            }
            // See tag status and do the needed.
            //echo"<br/> Type:".$type;
            if ($type == "open") { //The starting of the tag '<tag>'
                $parent[$level - 1] = & $current;
                if (!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    if ($attributes_data) $current[$tag . '_attr'] = $attributes_data;
                    //print_r($current[$tag . '_attr']);
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    $current = & $current[$tag];
                }
                else { //There was another element with the same tag name
                    if (isset($current[$tag][0])) { //If there is a 0th element it is already an array
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        $repeated_tag_index[$tag . '_' . $level]++;
                    }
                    else { //This section will make the value an array if multiple tags with the same name appear together
                        $current[$tag] = array(
                            $current[$tag],
                            $result
                        ); //This will combine the existing item and the new item together to make an array
                        $repeated_tag_index[$tag . '_' . $level] = 2;
                        if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset($current[$tag . '_attr']);
                        }
                    }
                    $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                    $current = & $current[$tag][$last_item_index];
                }
            }
            elseif ($type == "complete") { //Tags that ends in 1 line '<tag />'
                // See if the key is already taken.
                if (!isset($current[$tag])) { //New Key
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $attributes_data) $current[$tag . '_attr'] = $attributes_data;
                }
                else { //If taken, put all things inside a list(array)
                    if (isset($current[$tag][0]) and is_array($current[$tag])) { //If it is already an array...
                        // ...push the new element into that array.
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        if ($priority == 'tag' and $get_attributes and $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag . '_' . $level]++;
                    }
                    else { //If it is not an array...
                        $current[$tag] = array(
                            $current[$tag],
                            $result
                        ); //...Make it an array using using the existing value and the new value
                        $repeated_tag_index[$tag . '_' . $level] = 1;
                        if ($priority == 'tag' and $get_attributes) {
                            if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well
                                $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                                unset($current[$tag . '_attr']);
                            }
                            if ($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
                    }
                }
            }
            elseif ($type == 'close') { //End of tag '</tag>'
                $current = & $parent[$level - 1];
            }
        }
        return ($xml_array);
    }

    // Let's call the this above function xml2array


//  Enjoy coding
}
