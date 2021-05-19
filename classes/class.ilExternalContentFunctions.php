<?php
/**
 * Copyright (c) 2013 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg 
 * GPLv2, see LICENSE 
 */

/**
 * External Content plugin: functions for calculated fields
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 */ 
class ilExternalContentFunctions
{
	private static $last_oauth_base_string = "";
	
	/**
	 * single entry point
	 * 
	 * @param string	function name
	 * @param array		(assoc) parameters	
	 * @return mixed	return value (can be any type)
	 */
	public static function applyFunction($a_function, $a_params = array())
	{
		// apply the function
        switch ($a_function) 
        {
        	case "createArray":
        		return $a_params;
        		
        	case "getBasename":
        		return basename($a_params['url']);
        		
        	case "signOAuth":
        		return self::signOAuth($a_params);
        		
        	case "getLastOAuthBaseString":
        		return self::$last_oauth_base_string;
        		
        	case "createHtmlInputFields":
        		return self::createInputs($a_params);	
        		
        	case "showValues":
        		return self::showValues($a_params);	  

        	case "selectByName":
        		return self::selectByName($a_params);

			case "splitToArray":
				return self::splitToArray($a_params);

			case "mergeArrays":
				return self::mergeArrays($a_params);

        	default:
        		return "";
        }		
	}
	
	
	/**
	 * sign request data with OAuth
	 * 
	 * @param array (	"method => signature methos
	 * 					"key" => consumer key
	 * 					"secret" => shared secret
	 * 					"token"	=> request token
	 * 					"url" => request url
	 * 					data => array (key => value)
	 * 				)
	 * 						
	 * @return array	signed data
	 */
	private static function signOAuth($a_params)
	{
		require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/lib/OAuth.php');

		switch ($a_params['sign_method'])
		{
			case "HMAC_SHA1":
				$method = new OAuthSignatureMethod_HMAC_SHA1();
				break;
			case "PLAINTEXT":	
				$method = new OAuthSignatureMethod_PLAINTEXT();
				break;
			case "RSA_SHA1":	
				$method = new OAuthSignatureMethod_RSA_SHA1();
				break;
				
			default:
				return ["ERROR: unsupported signature method!"];
		}
		
		$consumer = new OAuthConsumer($a_params["key"], $a_params["secret"], $a_params["callback"]);
		$request = OAuthRequest::from_consumer_and_token($consumer, $a_params["token"], $a_params["http_method"], $a_params["url"], $a_params["data"]);
		$request->sign_request($method, $consumer, $a_params["token"]);
		
		// Pass this back up "out of band" for debugging
		self::$last_oauth_base_string = $request->get_signature_base_string();
		
		return $request->get_parameters();
	}
		
	
	/**
	 * create simple HTML input fields
	 * @param 	array		['type' => string, 'data' => [ 'name' => 'value', ... ]]
	 * @return	string		HTML with input fields
	 */
	private static function createInputs($a_params)
	{
		$html = "";	
		$type = strtolower($a_params['type']);
		foreach ($a_params["data"] as $name => $value)
		{
			if ($type != 'hidden')
			{
				$html .= sprintf('<br /><label for="%s">%s</label>', $name, $name);
			}
			$html .= sprintf('<input type="%s" name="%s" value="%s" />', $type, $name, $value) . "\n";
		}			
		return $html;
	}
	
	
	/**
	 * show parameter values
	 * @param 	array		['type' => string, 'data' => [ 'name' => 'value', ... ]]
	 * @return	string		HTML with name = value texts
	 */
	private static function showValues($a_params)
	{
		$html = "";
		foreach ($a_params["data"] as $name => $value)
		{
			$html .= sprintf('%s = %s', $name, $value) . "<br>\n";
		}				
		return $html;
	}
	
	/**
	 * Select a value from a list of params by matching the parameter name
	 * 
	 * @param 	array		['' => default_value, 'value' => selection_name, 'name1' => value1, name2 => value2, ...]
	 * @return 	string		return value
	 */
	private static function selectByName($a_params)
	{
		// get the value to be seleted
		$select = $a_params["value"];
		
		foreach ($a_params as $name => $value)
		{
			if ($name == $select)
			{
				return $value;
			}
		}
		
		// default value (should be the last defined value)
		if (isset($a_params['']))
		{
			return ($a_params['']);
		}
		return '';
	}

	/**
	 * Split a string of key/value pairs to an array of keys and values
	 * @param 	array 	['source => string, 'key_delimiter' => string, 'entry_delimiter => string ]
	 * @return	array	[key => value, ... ]
	 */
	private static function splitToArray($a_params)
	{
		$result = array();

		if (!empty($a_params['entry_delimiter']))
		{
			$entries = explode($a_params['entry_delimiter'], $a_params['source']);
			foreach ($entries as $entry)
			{
				if (!empty($a_params['key_delimiter']))
				{
					$pair = explode($a_params['key_delimiter'], $entry);
					if (!empty($pair[0]))
					{
						$key = $pair[0];
						$value = $pair[1];
						$result[$key] = $value;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Merge key/value arrays.
	 * Values of following array set will overwrite those of previous array with the same key.
	 * Other values will be added.
     * Params that are not arrays will be ignored
	 * @param array	['name1' => array, 'name2' => array, ... ]
     * @return array
	 */
	private static function mergeArrays($a_params)
	{
	    $merged = array();
	    foreach ($a_params as $param) {
	        if (is_array($param)) {
                $merged = array_merge($merged, $param);
            }
        }
	    return $merged;
	}
}