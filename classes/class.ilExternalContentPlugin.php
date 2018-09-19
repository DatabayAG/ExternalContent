<?php
/**
 * Copyright (c) 2018 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg
 * GPLv2, see LICENSE 
 */

include_once('./Services/Repository/classes/class.ilRepositoryObjectPlugin.php');

/**
 * External Content plugin
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 */
class ilExternalContentPlugin extends ilRepositoryObjectPlugin
{
	const BIG_ICON_SIZE = "45x45";
	const SMALL_ICON_SIZE = "35x35";
	const TINY_ICON_SIZE = "22x22";


	/**
	 * Returns name of the plugin
	 *
	 * @return <string
	 * @access public
	 */
	public function getPluginName()
	{
		return 'ExternalContent';
	}

	/**
	 * Remove all custom tables when plugin is uninstalled
	 */
	protected function uninstallCustom()
	{
		global $DIC;
		$ilDB = $DIC->database();

		$ilDB->dropTable('xxco_data_settings');
		$ilDB->dropTable('xxco_data_token');
		$ilDB->dropTable('xxco_data_types');
		$ilDB->dropTable('xxco_data_values');
		$ilDB->dropTable('xxco_results');
		$ilDB->dropTable('xxco_type_values');
	}
	

	/**
	* Create webspace directory for the plugin
	* 
	* @param	string		level ("plugin", "type" or "object")
	* @param	integer		type id or object id 
	* 
	* @return	string		webspace directory
	*/
	static function _createWebspaceDir($a_level = "plugin", $a_id = 0)
	{
		switch($a_level)
		{
			case "plugin":
				$plugin_dir = self::_getWebspaceDir('plugin');
				if (!is_dir($plugin_dir))
				{
					ilUtil::makeDir($plugin_dir);
				}
				return $plugin_dir;
								
			case "type":
				$plugin_dir = self::_createWebspaceDir("plugin");
				$type_dir = $plugin_dir . "/type_". $a_id;
				if (!is_dir($type_dir))
				{
					ilUtil::makeDir($type_dir);
				}
				return $type_dir;
								
			case "object":
				$plugin_dir = self::_createWebspaceDir("plugin");
				$object_dir = $plugin_dir . "/object_". $a_id;
				if (!is_dir($object_dir))
				{
					ilUtil::makeDir($object_dir);
				}
				return $object_dir;
		}
	}	
	
	/**
	* Get a webspace directory
	*
	* @param	string		level ("plugin", "type" or "object")
	* @param	integer		type id or object id 
	* 
	* @return	string		webspace directory
	*/
	static function _getWebspaceDir($a_level = "plugin", $a_id = 0)
	{
		switch($a_level)
		{
			case "plugin":
				return ilUtil::getWebspaceDir()."/xxco";
				
			case "type":
				return ilUtil::getWebspaceDir()."/xxco/type_".$a_id;
				
			case "object":
				return ilUtil::getWebspaceDir()."/xxco/object_".$a_id;
		}
	}	
	
	/**
	* Delete a webspace directory
	*
	* @param	string		level ("plugin", "type" or "object")
	* @param	integer		type id or object id 
	*/
	static function _deleteWebspaceDir($a_level = "plugin", $a_id = 0)
	{
		return ilUtil::delDir(self::_getWebspaceDir($a_level, $a_id));
	}	
	
	
	/**
	 * Get the file name used for a type or object specific icon

	 * @param 	string		size ("big", "small", "tiny" or "svg")
	 * @return 	string		file name
	 */
	static function _getIconName($a_size)
	{
		switch($a_size)
		{
			case "svg":		return "icon.svg";
			case "small": 	return "icon.png"; 
			case "tiny":	return "icon_s.png";
			case "big":		return "icon_b.png";
			default:		return "icon_b.png";
		}
	}
	
	
	/**
	* Get Icon (object, type or plugin specific)
	* (this function should be called wherever an icon has to be displyed)
	* 
	* @param 	string		object type ("xxco")
	* @param 	string		size ("big", "small", "tiny" or "svg")
	* @param	int			object id (optional)
	* @param	int			content type id (optional)
	* @param	string		get icon of a specific level ("plugin", "type" or "object")
	* 
	* @return	string		icon path
	*/	
	static function _getIcon($a_type, $a_size, $a_obj_id = 0, $a_type_id = 0, $a_level = "")
	{
		// first try to use an object specific icon
		if ($a_level == "object" or $a_level == "")
		{		
			if ($a_obj_id)
			{
				// always try svg version first
				$name = self::_getIconName("svg");
				$path = self::_getWebspaceDir("object", $a_obj_id) . "/" . $name;
				if (is_file($path))
				{
					return $path;
				}

				// then try older versions (big is default)
				$name = self::_getIconName($a_size);		
				$path = self::_getWebspaceDir("object", $a_obj_id) . "/" . $name;						
				if (is_file($path))
				{
					return $path;
				}
			}
		}
		
		// then try to get a content type specific icon
		if ($a_level == "type" or $a_level == "")
		{				
			if ($a_obj_id and !$a_type_id)
			{
				require_once('Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/classes/class.ilObjExternalContentAccess.php');
				$a_type_id = ilObjExternalContentAccess::_lookupTypeId($a_obj_id);	
			}
			
			if ($a_type_id)
			{
				// always try svg version first
				$name = self::_getIconName("svg");
				$path = self::_getWebspaceDir("type", $a_type_id) . "/" . $name;
				if (is_file($path))
				{
					return $path;
				}

				// then try older versions (big is default)
				$name = self::_getIconName($a_size);		
				$path = self::_getWebspaceDir("type", $a_type_id) . "/" . $name;
				if (is_file($path))
				{
					return $path;
				}
			}
		}
		
		// finally get the plugin icon
		if ($a_level == "plugin" or $a_level == "")
		{				
			return parent::_getIcon($a_type, $a_size);
		}
	}

	
	/**
	* Save an icon
	* 
	* @param 	string		path to the uploaded file
	* @param 	string		size ("big", "small", "tiny" or "svg")
	* @param	string		level ("type" or "object")
	* @param	integer		type id or object id 
	*/
	static function _saveIcon($a_upload_path, $a_size, $a_level, $a_id)
	{
		if (is_file($a_upload_path))
		{
			$icon_path = self::_createWebspaceDir($a_level, $a_id)
				 . "/" . self::_getIconName($a_size);

			if ($a_size == "svg")
			{
				ilUtil::moveUploadedFile($a_upload_path, 'icon', $icon_path);
			}
			else
			{
				switch($a_size)
				{
					case "small":
						$geom = self::SMALL_ICON_SIZE;
						break;

					case "tiny":
						$geom = self::TINY_ICON_SIZE;
						break;

					case "big":
					default:
						$geom = self::BIG_ICON_SIZE;
						break;
				}

				$a_upload_path = ilUtil::escapeShellArg($a_upload_path);
				$icon_path = ilUtil::escapeShellArg($icon_path);
				ilUtil::execConvert($a_upload_path."[0] -geometry ".$geom." PNG:".$icon_path);
			}
		}
	}
	
	
	/**
	* Remove an icon
	* 
	* @param 	string		size ("big", "small", "tiny" or "svg")
	* @param	string		level ("type" or "object")
	* @param	integer		type id or object id 
	*/ 
	static function _removeIcon($a_size, $a_level, $a_id)
	{
		$name = self::_getIconName($a_size);		
		$path = self::_getWebspaceDir($a_level, $a_id) . "/" . $name;
		@unlink($path);				
	}

	/**
	 * decides if this repository plugin can be copied
	 *
	 * @return bool
	 */
	public function allowCopy()
	{
		return true;
	}

}
?>