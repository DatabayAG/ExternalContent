<?php
/**
 * Copyright (c) 2018 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg
 * GPLv2, see LICENSE 
 */

/**
 * External Content plugin: object acccess check
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 */
class ilObjExternalContentAccess extends ilObjectPluginAccess
{
	const ACTIVATION_OFFLINE = 0;
	const ACTIVATION_UNLIMITED = 1;
	
	private static $settings_cache = array();

	/**
	* checks wether a user may invoke a command or not
	* (this method is called by ilAccessHandler::checkAccess)
	*
	* @param	string		$a_cmd		command (not permission!)
	* @param	string		$a_permission	permission
	* @param	int			$a_ref_id	reference id
	* @param	int			$a_obj_id	object id
	* @param	int			$a_user_id	user id (if not provided, current user is taken)
	*
	* @return	boolean		true, if everything is ok
	*/
	function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = "")
	{
		global $DIC;

		if (empty($a_user_id))
		{
			$a_user_id = $DIC->user()->getId();
		}

		switch ($a_permission)
		{
			case "visible":
			case "read":
				if (!self::_lookupOnline($a_obj_id) &&
					(!$DIC->access()->checkAccessOfUser($a_user_id,'write', '', $a_ref_id)))
				{
					return false;
				}
				break;
		}

		return true;
	}

	/*
	* check wether content is online
	*/
	static function _lookupOnline($a_obj_id)
	{
		$row = self::fetchSettings($a_obj_id);
 
		switch($row["availability_type"])
		{
			case self::ACTIVATION_UNLIMITED:
				return true;

			case self::ACTIVATION_OFFLINE:
				return false;

			default:
				return false;
		}
	}


	/**
	 * Get the type
	 * @param int $a_obj_id
	 * @return int
	 */
	static function _lookupTypeId($a_obj_id)
	{
		$row = self::fetchSettings($a_obj_id);
        return $row['type_id'];
	}
	
	
	/**
	 * fetch the settings of an object that are needed for list views
	 * (the settings are cached)
	 * 
	 * @param 	integer		object id
	 * @return	array		fetched row
	 */
	private static function fetchSettings($a_obj_id)
	{
		if (!is_array(self::$settings_cache[$a_obj_id]))
		{
	       	global $DIC;
	       	$ilDB = $DIC->database();
	       	
	       	// include only the columns necessary for object listings
	        $query = 'SELECT type_id, availability_type '
	        		. ' FROM xxco_data_settings '
	        		. ' WHERE obj_id = ' . $ilDB->quote($a_obj_id, 'integer');
	        		
	        $result = $ilDB->query($query);
	        if (!$row = $ilDB->fetchAssoc($result))
	        {
	        	$row = array();
	        }
	        self::$settings_cache[$a_obj_id] = $row;
		}
		
		return self::$settings_cache[$a_obj_id];
	}
} 

?>
