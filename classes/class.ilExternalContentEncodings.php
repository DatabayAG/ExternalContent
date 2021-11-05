<?php
/**
 * Copyright (c) 2013 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg 
 * GPLv2, see LICENSE 
 */

/**
 * External Content plugin: encoding of fields
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 */ 
class ilExternalContentEncodings
{
	static $allowed_encodings = array (
		'',
		'plain',
		'url',
		'url_rfc3986',
		'base64',
		'dechex',
		'md5',
		'sha1',
		'strip_tags',
		'no_break',
		'singlequotes',
		'doublequotes',
		'nl2br',
		'trim',
		'htmlentities',
		'html_entity_decode',
		'htmlspecialchars',
		'htmlspecialchars_decode'	
	);
	    
    
	/**
	 * check if an encoding is defined
	 * 
	 * @param  string	encoding (may also be comma separated list of encodings)
	 * @return bool		is defined
	 */
	public static function _encodingExists($a_encoding = '')
	{
		foreach (explode(',', $a_encoding) as $encoding)
		{
        	if (in_array(trim($encoding), self::$allowed_encodings))
        	{
            	continue;
        	}
	
            // security: allow only characters and numbers
           	$classname = preg_replace('/[^A-Za-z0-9]/', '', trim($encoding));
           	
            if (file_exists('./Customizing/global/encodings/class.'. $classname.'.php'))
            {
				continue;
            }       

            // no applicable encoding found
            return false;
		}
		return true;
	}
	
	/**
	 * apply an encoding to a value
	 * 
	 * @param 	string	encoding (may also be comma separated list of encodings)
	 * @param 	string	value
	 * @return	string	encoded value
	 */
	public static function _applyEncoding($a_encoding = "", $a_value = "")
	{
		if (is_array($a_value))
		{
			$values = array();
			foreach ($a_value as $key => $value)
			{
				if (!is_numeric($key))
				{
					$key = self::_applyEncoding($a_encoding, $key);
				}
				$values[$key] = self::_applyEncoding($a_encoding, $value);
			}
			return $values;
		}

		$value = $a_value;
		
		foreach (explode(',', $a_encoding) as $encoding)
		{
			switch (trim($encoding)) 
	        {	                
				case '':
	            case 'plain':
	                break;
	      
	            case 'base64':
	                $value = base64_encode($value);
	                break;
	
	            case 'dechex':
	                $value = dechex($value);
	                break;
	                
	            case 'url':
	                $value = urlencode($value);
	                break;

	            case 'url_rfc3986':
	                $value = rawurlencode($value);
	                break;

	            case 'md5':
	                $value = md5($value);
	                break;
	
	            case 'sha1':
	                $value = sha1($value);
	                break;
	                
	            case 'strip_tags':
	            	$value = strip_tags($value);
	            	break;
	            	
	            case 'no_break':
					$value =str_replace("\r\n", " ", $value);
					$value =str_replace("\n", " ", $value);
					break;
					
	            case 'singlequotes':
					$value =str_replace('"', "'", $value);
					break;
				
				case 'doublequotes':
					$value =str_replace("'", '"', $value);
					break;	
					
	            case 'nl2br':
	            	$value = nl2br($value);
	            	break;
	            	
	            case 'trim':
	            	$value = trim($value);
	            	break;
					
				case 'htmlentitles':
	            	$value = htmlentities($value, ENT_COMPAT, 'UTF-8');
	            	break;
	            	
	            case 'html_entity_decode':
	            	$value = html_entity_decode($value, ENT_COMPAT, 'UTF-8');
	            	break;
	            	
	            case 'htmlspecialchars':
	            	$value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8', false);
	            	break;
	            	
	            case 'htmlspecialchars_decode':
	            	$value = htmlspecialchars_decode($value, ENT_COMPAT);
	            	break;       	
	            		                
	            default:
	              	// security: allow only characters and numbers
	               	$classname = preg_replace('/[^A-Za-z0-9]/', '', trim($encoding));
	                  	
		            if (file_exists('./Customizing/global/encodings/class.'. $classname.'.php'))
		            {
		               	// use an encoding class with that name
		               	require_once('./Customizing/global/encodings/class.'.$classname.'.php');	
		              	$value = call_user_func(array($classname,'encode'), $value);
		            }
	              	break;
	        }
        }
        
        return $value;
	}
}
