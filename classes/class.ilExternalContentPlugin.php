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
    public const PLUGIN_PATH = 'public/Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent';
    private const DEFAULT_ICON_URL = 'assets/images/standard/icon_xxco.svg';
    private const ICON_NAME = 'icon.svg';

    /** @var self */
    protected static $instance;

    /**
	 * Returns name of the plugin
	 * @return string
	 */
	public function getPluginName(): string
	{
		return 'ExternalContent';
	}

    /**
     * Get the icon for the ILIAS object creation modal
     */
    public static function _getIcon(string $a_type): string
    {
        return self::DEFAULT_ICON_URL;
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
	private static function createWebspaceDir($a_level = "plugin", $a_id = 0)
	{
        global $DIC;
        $fs = $DIC->filesystem()->web();
        
		switch($a_level)
		{
			case "type":
				$plugin_dir = self::createWebspaceDir("plugin");
				$type_dir = $plugin_dir . "/type_". $a_id;
				if (!is_dir($type_dir))
				{
					$fs->createDir($type_dir);
				}
				return $type_dir;
								
			case "object":
				$plugin_dir = self::createWebspaceDir("plugin");
				$object_dir = $plugin_dir . "/object_". $a_id;
				if (!is_dir($object_dir))
				{
                    $fs->createDir($object_dir);
				}
				return $object_dir;

            case "plugin":
            default:
                $plugin_dir = self::getRelativeWebspaceDir('plugin');
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
	private static function getRelativeWebspaceDir($a_level = "plugin", $a_id = 0)
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
    private static function getWebspaceURL($a_level = "plugin", $a_id = 0)
    {
        return './data/' . CLIENT_ID . '/' . self::getRelativeWebspaceDir($a_level, $a_id);
    }


    /**
	* Delete a webspace directory
	*
	* @param	string	$a_level	level ("plugin", "type" or "object")
	* @param	integer	$a_id	type id or object id
	*/
	public static function deleteWebspaceDir($a_level = "plugin", $a_id = 0)
	{
        global $DIC;
        $fs = $DIC->filesystem()->web();
        if ($fs->hasDir(self::getRelativeWebspaceDir($a_level, $a_id))) {
            $fs->deleteDir(self::getRelativeWebspaceDir($a_level, $a_id));
        }
	}	

	
	/**
	* Get Icon (object, type or plugin specific)
	* (this function should be called wherever an icon has to be displyed)
	*
	* @param	int			$a_obj_id   object id (optional)
	* @param	int			$a_type_id  content type id (optional)
	* @param	string		$a_level    get icon of a specific level ("plugin", "type" or "object")
	* @return	string		icon path
	*/
    public static function getContentIcon(int $a_obj_id = 0, $a_type_id = 0, $a_level = ""): string
    {
        global $DIC;
        $fs = $DIC->filesystem()->web();

        // first try to use an object specific icon
        if ($a_level == "object" or $a_level == "") {
            if ($a_obj_id) {
                $path = self::getRelativeWebspaceDir("object", $a_obj_id) . "/" . self::ICON_NAME;
                if ($fs->has($path)) {
                    return self::getWebspaceURL("object", $a_obj_id) . "/" . self::ICON_NAME;
                }
            }
            if ($a_level == "object") {
                // object icon is requested explicit
                return "";
            }
        }

        // then try to get a content type specific icon
        if ($a_level == "type" or $a_level == "") {
            if ($a_obj_id and !$a_type_id) {
                $a_type_id = ilObjExternalContentAccess::_lookupTypeId($a_obj_id);
            }
            if ($a_type_id) {
                $path = self::getRelativeWebspaceDir("type", $a_type_id) . "/" . self::ICON_NAME;
                if ($fs->has($path)) {
                    return self::getWebspaceURL("type", $a_type_id) . "/" . self::ICON_NAME;
                }
            }
            if ($a_level == "type") {
                // type icon is requested explicit
                return "";
            }
        }

        // finally get the plugin icon
        return self::DEFAULT_ICON_URL;
	}

	
	/**
	* Save an icon
	* 
	* @param 	string		$a_upload_path  temp path to the uploaded file
	* @param	string		$a_level        level ("type" or "object")
	* @param	integer		$a_id           type id or object id
	*/
	public static function saveIcon($a_upload_path, $a_level, $a_id) : void
	{
        global $DIC;

        $path = self::createWebspaceDir($a_level, $a_id);

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

        $upload->moveOneFileTo($upload_result, $path, Location::WEB, self::ICON_NAME, true);
	}
	
	
	/**
	* Remove an icon
	*
	* @param	string		$a_level    level ("type" or "object")
	* @param	integer		$a_id       type id or object id
	*/ 
	public static function removeIcon($a_level, $a_id)
	{
        global $DIC;
        
        $fs = $DIC->filesystem()->web();
        $name = self::ICON_NAME;
        if ($fs->has(self::getRelativeWebspaceDir($a_level, $a_id) . "/" . $name)) {
            $fs->delete(self::getRelativeWebspaceDir($a_level, $a_id) . "/" . $name);
        }
	}

    /**
     * Get a template of the plugin
     * @param string $a_template
     */
    public function getTemplate(string $a_template, bool $a_par1 = true, bool $a_par2 = true): ilTemplate
    {
        return new ilTemplate( $a_template, $a_par1, $a_par2, self::PLUGIN_PATH);
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