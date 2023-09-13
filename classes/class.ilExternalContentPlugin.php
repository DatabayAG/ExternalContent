<?php
/**
 * Copyright (c) 2018 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg
 * GPLv2, see LICENSE 
 */

use ILIAS\Filesystem\Filesystem;
use ILIAS\FileUpload\DTO\ProcessingStatus;
use ILIAS\FileUpload\Location;

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

    /** @var Filesystem */
    protected $fs;
    
    public function __construct(ilDBInterface $db, ilComponentRepositoryWrite $component_repository, string $id)
    {
        global $DIC;
        parent::__construct($db, $component_repository, $id);
        $this->fs = $DIC->filesystem()->web();
    }

    /**
	 * Returns name of the plugin
	 * @return string
	 */
	public function getPluginName(): string
	{
		return 'ExternalContent';
	}

    /**
     * Get the plugin instance
     * @return self
     */
    public static function getInstance() 
    {
        global $DIC;
        
        if (!isset(self::$instance)) {
            /** @var ilComponentFactory $factory */
            $factory = $DIC["component.factory"];
            self::$instance = $factory->getPlugin('xxco');
        }
        return self::$instance;
    }

    
    public function install(): void
    {
        parent::install();

        if (!$this->fs->hasDir('xxco/cache')) {
            $this->fs->createDir('xxco/cache');
        }
    }


    /**
	 * Remove all custom tables when plugin is uninstalled
	 */
	protected function uninstallCustom(): void
	{
		$this->db->dropTable('xxco_data_settings');
        $this->db->dropTable('xxco_data_types');
        $this->db->dropTable('xxco_data_values');
        $this->db->dropTable('xxco_results');
        $this->db->dropTable('xxco_type_values');
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
        global $DIC;
        $fs = $DIC->filesystem()->web();
        
		switch($a_level)
		{
			case "type":
				$plugin_dir = self::_createWebspaceDir("plugin");
				$type_dir = $plugin_dir . "/type_". $a_id;
				if (!is_dir($type_dir))
				{
					$fs->createDir($type_dir);
				}
				return $type_dir;
								
			case "object":
				$plugin_dir = self::_createWebspaceDir("plugin");
				$object_dir = $plugin_dir . "/object_". $a_id;
				if (!is_dir($object_dir))
				{
                    $fs->createDir($object_dir);
				}
				return $object_dir;

            case "plugin":
            default:
                $plugin_dir = self::_getRelativeWebspaceDir('plugin');
                if (!is_dir($plugin_dir))
                {
                    $fs->createDir($plugin_dir);
                }
                return $plugin_dir;
        }
	}	
	
	/**
	* Get a relative webspace directory
	*
	* @param	string	$a_level	level ("plugin", "type" or "object")
	* @param	integer	$a_id	type id or object id
	* 
	* @return	string		webspace directory
	*/
	static function _getRelativeWebspaceDir($a_level = "plugin", $a_id = 0)
	{
		switch($a_level)
		{
			case "type":
				return "xxco/type_".$a_id;
				
			case "object":
				return "xxco/object_".$a_id;

            case "plugin":
            default:
                return "xxco";
        }
	}

    /**
     * Get an absolute webspace directory
     * 
     * @param	string	$a_level	level ("plugin", "type" or "object")
     * @param	integer	$a_id	type id or object id
     *
     * @return	string		webspace directory
     */
    static function _getAbsoluteWebspaceDir($a_level = "plugin", $a_id = 0) 
    {
        return CLIENT_WEB_DIR . '/' . self::_getRelativeWebspaceDir($a_level, $a_id);
    }


    /**
     * Get an absolute webspace directory
     *
     * @param	string	$a_level	level ("plugin", "type" or "object")
     * @param	integer	$a_id	type id or object id
     *
     * @return	string		webspace directory
     */
    static function _getWebspaceURL($a_level = "plugin", $a_id = 0)
    {
        return './data/' . CLIENT_ID . '/' . self::_getRelativeWebspaceDir($a_level, $a_id);
    }


    /**
	* Delete a webspace directory
	*
	* @param	string	$a_level	level ("plugin", "type" or "object")
	* @param	integer	$a_id	type id or object id
	*/
	static function _deleteWebspaceDir($a_level = "plugin", $a_id = 0)
	{
        global $DIC;
        $fs = $DIC->filesystem()->web();
        if ($fs->hasDir(self::_getRelativeWebspaceDir($a_level, $a_id))) {
            $fs->deleteDir(self::_getRelativeWebspaceDir($a_level, $a_id));
        }
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
			case "big":		
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
    static function _getContentIcon(string $a_type, $a_size, $a_obj_id = 0, $a_type_id = 0, $a_level = ""): string
	{
        global $DIC;
        $fs = $DIC->filesystem()->web();
        
		// first try to use an object specific icon
		if ($a_level == "object" or $a_level == "")
		{		
			if ($a_obj_id)
			{
				// always try svg version first
				$name = self::_getIconName("svg");
				$path = self::_getRelativeWebspaceDir("object", $a_obj_id) . "/" . $name;
				if ($fs->has($path))
				{
					return self::_getWebspaceURL("object", $a_obj_id) . "/" . $name;
				}

				// then try older versions (big is default)
				$name = self::_getIconName($a_size);		
				$path = self::_getRelativeWebspaceDir("object", $a_obj_id) . "/" . $name;						
				if ($fs->has($path))
				{
					return self::_getWebspaceURL("object", $a_obj_id) . "/" . $name;
                }
			}
		}
		
		// then try to get a content type specific icon
		if ($a_level == "type" or $a_level == "")
		{				
			if ($a_obj_id and !$a_type_id)
			{
				$a_type_id = ilObjExternalContentAccess::_lookupTypeId($a_obj_id);	
			}
			
			if ($a_type_id)
			{
				// always try svg version first
				$name = self::_getIconName("svg");
				$path = self::_getRelativeWebspaceDir("type", $a_type_id) . "/" . $name;
				if ($fs->has($path))
				{
					return self::_getWebspaceURL("type", $a_type_id) . "/" . $name;
				}

				// then try older versions (big is default)
				$name = self::_getIconName($a_size);		
				$path = self::_getRelativeWebspaceDir("type", $a_type_id) . "/" . $name;
				if ($fs->has($path))
				{
                    return self::_getWebspaceURL("type", $a_type_id) . "/" . $name;
				}
			}
		}
		
		// finally get the plugin icon
        return parent::_getIcon($a_type);
	}

	
	/**
	* Save an icon
	* 
	* @param 	string		$a_upload_path  temp path to the uploaded file
	* @param 	string		$a_size         size ("big", "small", "tiny" or "svg")
	* @param	string		$a_level        level ("type" or "object")
	* @param	integer		$a_id           type id or object id
	*/
	static function _saveIcon($a_upload_path, $a_size, $a_level, $a_id) : void
	{
        global $DIC;

        $path = self::_createWebspaceDir($a_level, $a_id);

        $upload = $DIC->upload();
        if (!$upload->hasBeenProcessed()) {
            $upload->process();
        }
        if (!$upload->hasUploads()) {
            return;
        }
        // index is the path of the uploaded file
        $upload_result = $upload->getResults()[$a_upload_path] ?? null;
        if (!isset($upload_result)) {
            return;
        }
        $processing_status = $upload_result->getStatus();
        if ($processing_status->getCode() === ProcessingStatus::REJECTED
            || $processing_status->getCode() === ProcessingStatus::DENIED) {
            throw new ilException($processing_status->getMessage());
        }

        $upload->moveOneFileTo($upload_result, $path, Location::WEB, self::_getIconName($a_size), true);
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
        global $DIC;
        
        $fs = $DIC->filesystem()->web();
        $name = self::_getIconName($a_size);
        if ($fs->has(self::_getRelativeWebspaceDir($a_level, $a_id) . "/" . $name)) {
            $fs->delete(self::_getRelativeWebspaceDir($a_level, $a_id) . "/" . $name);
        }
	}

	/**
	 * decides if this repository plugin can be copied
	 *
	 * @return bool
	 */
    public function allowCopy(): bool
	{
		return true;
	}

}