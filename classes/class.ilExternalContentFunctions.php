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
				return "ERROR: unsupported signature method!";
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
	 */
	private function showValues($a_params)
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
	 * @param 	array		assoc array (the select value has the key "value")
	 * @return 	string		return value
	 */
	private function selectByName($a_params)
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
	}
}