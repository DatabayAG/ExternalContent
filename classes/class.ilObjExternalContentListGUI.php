<?php
/**
 * Copyright (c) 2013 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg 
 * GPLv2, see LICENSE 
 */

include_once "./Services/Repository/classes/class.ilObjectPluginListGUI.php";

/**
 * External Content plugin: repository object list
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 */ 
class ilObjExternalContentListGUI extends ilObjectPluginListGUI 
{
	/**
	*  all XXCO type definitions
	*/
	static $xxco_types = array();

    /**
     * Init type
     */
    function initType() 
    {
        $this->setType("xxco");
    }

    /**
     * Get name of gui class handling the commands
     */
    function getGuiClass() 
    {
        return "ilObjExternalContentGUI";
    }

    /**
     * Get commands
     */
    function initCommands() 
    {
        return array
            (
            array(
                "permission" => "read",
                "cmd" => "view",
                "default" => true),
            array(
                "permission" => "write",
                "cmd" => "edit",
                "txt" => $this->txt("edit"),
                "default" => false),
        );
    }

    /**
     * get properties (offline)
     *
     * @access public
     * @param
     * 
     */
    public function getProperties() 
    {
        global $DIC;
        $lng = $DIC->language();

        $this->plugin->includeClass("class.ilObjExternalContentAccess.php");
        if (!ilObjExternalContentAccess::_lookupOnline($this->obj_id)) 
        {
            $props[] = array("alert" => true, "property" => $lng->txt("status"),
                "value" => $lng->txt("offline"));
        }
        return $props ? $props : array();
    }

}
// END class.ilObjExternalContentListGUI
?>