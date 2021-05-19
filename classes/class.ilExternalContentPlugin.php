<?php
/**
 * Copyright (c) 2018 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg
 * GPLv2, see LICENSE 
 */

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

    /** @var self */
    protected static $instance;

	/**
	 * Returns name of the plugin
	 * @return string
	 */
	public function getPluginName()
	{
		return 'ExternalContent';
	}

    /**
     * Get the plugin instance
     * @return self
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
	 * Remove all custom tables when plugin is uninstalled
	 */
	protected function uninstallCustom()
	{
		global $DIC;
		$ilDB = $DIC->database();

		$ilDB->dropTable('xxco_data_settings');
		$ilDB->dropTable('xxco_data_types');
		$ilDB->dropTable('xxco_data_values');
		$ilDB->dropTable('xxco_results');
		$ilDB->dropTable('xxco_type_values');
	}
	

	/**
	* Create webspace directory for the plugin
	* 
	* @param	string	$a_level	level ("plugin", "type" or "object")
	* @param	integer	$a_id	    type id or object id
	* 
	* @return	string		webspace directory
	*/
	static function _createWebspaceDir($a_level = "plugin", $a_id = 0)
	{
		switch($a_level)
		{
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

            case "plugin":
            default:
                $plugin_dir = self::_getWebspaceDir('plugin');
                if (!is_dir($plugin_dir))
                {
                    ilUtil::makeDir($plugin_dir);
                }
                return $plugin_dir;
        }
	}	
	
	/**
	* Get a webspace directory
	*
	* @param	string	$a_level	level ("plugin", "type" or "object")
	* @param	integer	$a_id	type id or object id
	* 
	* @return	string		webspace directory
	*/
	static function _getWebspaceDir($a_level = "plugin", $a_id = 0)
	{
		switch($a_level)
		{
			case "type":
				return ilUtil::getWebspaceDir()."/xxco/type_".$a_id;
				
			case "object":
				return ilUtil::getWebspaceDir()."/xxco/object_".$a_id;

            case "plugin":
            default:
                return ilUtil::getWebspaceDir()."/xxco";
        }
	}	
	
	/**
	* Delete a webspace directory
	*
	* @param	string	$a_level	level ("plugin", "type" or "object")
	* @param	integer	$a_id	type id or object id
	*/
	static function _deleteWebspaceDir($a_level = "plugin", $a_id = 0)
	{
		ilUtil::delDir(self::_getWebspaceDir($a_level, $a_id));
	}	
	
	
	/**
	 * Get the file name used for a type or object specific icon

	 * @param 	string	$a_size	size ("big", "small", "tiny" or "svg")
	 * @return 	string		    file name
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
	* @param 	string		$a_type     object type ("xxco")
	* @param 	string		$a_size     size ("big", "small", "tiny" or "svg")
	* @param	int			$a_obj_id   object id (optional)
	* @param	int			$a_type_id  content type id (optional)
	* @param	string		$a_level    get icon of a specific level ("plugin", "type" or "object")
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
				require_once(__DIR__ . '/class.ilObjExternalContentAccess.php');
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
        return parent::_getIcon($a_type, $a_size);
	}

	
	/**
	* Save an icon
	* 
	* @param 	string		$a_upload_path  path to the uploaded file
	* @param 	string		$a_size         size ("big", "small", "tiny" or "svg")
	* @param	string		$a_level        level ("type" or "object")
	* @param	integer		$a_id           type id or object id
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
	* @param 	string		$a_size     size ("big", "small", "tiny" or "svg")
	* @param	string		$a_level    level ("type" or "object")
	* @param	integer		$a_id       type id or object id
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