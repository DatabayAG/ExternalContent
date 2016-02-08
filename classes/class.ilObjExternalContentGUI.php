<?php 
/**
 * Copyright (c) 2013 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg 
 * GPLv2, see LICENSE 
 */
include_once('./Services/Repository/classes/class.ilObjectPluginGUI.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/classes/class.ilObjExternalContent.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/classes/class.ilExternalContentType.php');

/**
 * External Content plugin: repository object GUI
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 * 
 * @ilCtrl_isCalledBy ilObjExternalContentGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilObjExternalContentGUI: ilPermissionGUI, ilExternalContentLogGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilLearningProgressGUI
 */
class ilObjExternalContentGUI extends ilObjectPluginGUI
{
    const META_TIMEOUT_INFO = 1;
    const META_TIMEOUT_REFRESH = 60;

    /**
     * Valid meta data groups for displaying
     */
    var $meta_groups = array('General', 'LifeCycle', 'Technical', 'Rights');

    /**
     * Initialisation
     *
     * @access protected
     */
    protected function afterConstructor()
    {
        // anything needed after object has been constructed
    }

    /**
     * Get type.
     */
    final function getType()
    {
        return "xxco";
    }

    function getTitle()
    {
        return $this->object->getTitle();
    }

    /**
     * After object has been created -> jump to this command
     */
    function getAfterCreationCmd()
    {
        return "edit";
    }

    /**
     * Get standard command
     */
    function getStandardCmd()
    {
        return "view";
    }

    
    /*
     * TODO (UGLY): Overwritten execute command of ilObjectPluginGUI
     * 
     * needed to properly handle the creation mode and to set the title icon
     * should be solved in ILIAS 4.4!
     */
	function &executeCommand()
	{
		global $ilCtrl, $tpl, $ilAccess, $lng, $ilNavigationHistory, $ilTabs;
                
               

		// EXTENSION: set the proper creation mode for the newly implemented creation form
		// this could be followed by a call of the parent function if the icon issue is solved
        $cmd = $ilCtrl->getCmd();
        
        if ($cmd == "create" or $cmd == "cancelCreate" or $cmd == "save")
        {
            $this->setCreationMode(true);
        }
        else
        {
            $this->setCreationMode(false);
        }
        // EXTENSION.
		
		// get standard template (includes main menu and general layout)
		$tpl->getStandardTemplate();

		// set title
		if (!$this->getCreationMode())
		{
			$tpl->setTitle($this->object->getTitle());
			
			// MODIFICATION: use an object or content type specific icon
			$tpl->setTitleIcon(ilExternalContentPlugin::_getIcon("xxco", "big", $this->object->getId()),
				$lng->txt("icon")." ".$this->txt("obj_".$this->object->getType()));
			// MODIFICATION.
				
			// set tabs
			if (strtolower($_GET["baseClass"]) != "iladministrationgui")
			{
				$this->setTabs();
				$this->setLocator();
			}
			else
			{
				$this->addAdminLocatorItems();
				$tpl->setLocator();
				$this->setAdminTabs();
			}
			
			// add entry to navigation history
			if ($ilAccess->checkAccess("read", "", $_GET["ref_id"]))
			{
				$ilNavigationHistory->addItem($_GET["ref_id"],
					$ilCtrl->getLinkTarget($this, $this->getStandardCmd()), $this->getType());
			}

		}
		else
		{
			// show info of parent
			$tpl->setTitle(ilObject::_lookupTitle(ilObject::_lookupObjId($_GET["ref_id"])));
			$tpl->setTitleIcon(
				ilObject::_getIcon(ilObject::_lookupObjId($_GET["ref_id"]), "big"),
				$lng->txt("obj_".ilObject::_lookupType($_GET["ref_id"], true)));
			$this->setLocator();

		}

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			case "ilinfoscreengui":
				$this->checkPermission("visible");
				$this->infoScreen();	// forwards command
				break;

            // EXTENSION: add learning progress tab
            case "illearningprogressgui":
                global $ilUser, $ilTabs;
                $ilTabs->setTabActive('learning_progress');
                include_once './Services/Tracking/classes/class.ilLearningProgressGUI.php';
                $new_gui =& new ilLearningProgressGUI(ilLearningProgressGUI::LP_CONTEXT_REPOSITORY,
                    $this->object->getRefId(),
                    $_GET['user_id'] ? $_GET['user_id'] : $ilUser->getId());
                $this->ctrl->forwardCommand($new_gui);
                if ($this->checkPermissionBool('edit_learning_progress'))
                {
                    $ilTabs->addSubTab("lp_settings", $this->txt('settings'), $ilCtrl->getLinkTarget($this, 'editLPSettings'));
                }
               break;
            // EXTENSION.

            case 'ilpermissiongui':
				include_once("Services/AccessControl/classes/class.ilPermissionGUI.php");
				$perm_gui = new ilPermissionGUI($this);
				$ilTabs->setTabActive("perm_settings");
				$ret = $ilCtrl->forwardCommand($perm_gui);
				break;
		
			case 'ilobjectcopygui':
				include_once './Services/Object/classes/class.ilObjectCopyGUI.php';
				$cp = new ilObjectCopyGUI($this);
				$cp->setType($this->getType());
				$this->ctrl->forwardCommand($cp);
				break;

			default:
				if (strtolower($_GET["baseClass"]) == "iladministrationgui")
				{
					$this->viewObject();
					return;
				}
				if(!$cmd)
				{
					$cmd = $this->getStandardCmd();
				}
				if ($cmd == "infoScreen")
				{
					$ilCtrl->setCmd("showSummary");
					$ilCtrl->setCmdClass("ilinfoscreengui");
					$this->infoScreen();
				}
				else
				{
					if ($this->getCreationMode())
					{
						$this->$cmd();
					}
					else
					{
						$this->performCommand($cmd);
					}
				}
				break;
		}

		if (!$this->getCreationMode())
		{
			$tpl->show();
		}
	}

	/**
     * Perform command
     *
     * @access public
     */
    public function performCommand($cmd)
    {
    	global $ilCtrl, $ilTabs;

    	// set a return URL
		// IMPORTANT: the last parameter prevents an encoding of & to &amp;
		// Otherwise the OAuth signatore is calculated wrongly!
    	$this->object->setReturnURL(ILIAS_HTTP_PATH . "/". $ilCtrl->getLinkTarget($this, "view", "", true));
        
        switch ($cmd)
        {        	
        	case "edit":
        	case "update":
        	case "refreshMeta":
        	case "editIcons":
        	case "updateIcons":
        	case "editInstructions":
        	case "updateInstructions":
        		$this->checkPermission("write");
            	$this->setSubTabs("edit");
            	
                $cmd .= "Object";
                $this->$cmd();
                break;

            case "editLPSettings":
                $this->checkPermission("edit_learning_progress");
                $this->setSubTabs("learning_progress");

                $cmd .= "Object";
                $this->$cmd();
                break;

            case "checkToken":
               	$this->$cmd();
                break;
            
            default:
            	
            	$this->checkPermission("read");
            	           	
            	if ($this->object->typedef->getAvailability() == ilExternalContentType::AVAILABILITY_NONE)
		        {
		            $ilErr->raiseError($this->txt('xxco_message_type_not_available'), $ilErr->MESSAGE);
		        }
            	
            	if (!$cmd)
                {
                    $cmd = "viewObject";
                }
                $cmd .= "Object";
                $this->$cmd();
        }
    }
	
	
    /**
     * Set tabs
     */
    function setTabs()
    {
        global $ilTabs, $ilCtrl, $lng;
        
        $type = new ilExternalContentType($this->object->getTypeId());
       
        $cmd = $ilCtrl->getCmd();
        if (!($cmd == "create" or $cmd == "cancelCreate" or $cmd == "save" or $cmd == "Save"))
        {
            if ($this->object->typedef->getLaunchType() == ilExternalContentType::LAUNCH_TYPE_EMBED)
            {
                $ilTabs->addTab("viewEmbed", $this->lng->txt("content"), $ilCtrl->getLinkTarget($this, "viewEmbed"));
            }
        }
        //  info screen tab
        $ilTabs->addTab("infoScreen", $this->lng->txt("info_short"), $ilCtrl->getLinkTarget($this, "infoScreen"));

        // add "edit" tab
        if ($this->checkPermissionBool("write"))
        {
            $ilTabs->addTab("edit", $this->lng->txt("edit"), $ilCtrl->getLinkTarget($this, "edit"));           
        }
        

        /*
        //Display Log tab if logs are used
        if($type->getUseLogs() == "ON")
        {
            if ($this->checkPermissionBool("write"))
            {
                $ilTabs->addTab("xxco_view_log", $this->txt("view_log"), $this->ctrl->getLinkTargetByClass(array(get_class($this),'ilexternalcontentloggui'), "viewLog"));
            }
        }
        */

        include_once("Services/Tracking/classes/class.ilObjUserTracking.php");
        if (ilObjUserTracking::_enabledLearningProgress() &&
            ($this->checkPermissionBool("edit_learning_progress") || $this->checkPermissionBool("read_learning_progress")))
        {
            if ($this->object->getLPMode() == ilObjExternalContent::LP_ACTIVE && $this->checkPermissionBool("read_learning_progress"))
            {
                if (ilObjUserTracking::_enabledUserRelatedData())
                {
                    $ilTabs->addTab("learning_progress", $lng->txt('learning_progress'), $ilCtrl->getLinkTargetByClass(array('ilObjExternalContentGUI','ilLearningProgressGUI','ilLPListOfObjectsGUI')));
                }
                else
                {
                    $ilTabs->addTab("learning_progress", $lng->txt('learning_progress'), $ilCtrl->getLinkTargetByClass(array('ilObjExternalContentGUI','ilLearningProgressGUI', 'ilLPListOfObjectsGUI'), 'showObjectSummary'));
                }
            }
            elseif ($this->checkPermissionBool("edit_learning_progress"))
            {
                $ilTabs->addTab('learning_progress',
                    $lng->txt('learning_progress'),
                    $ilCtrl->getLinkTarget($this,'editLPSettings'));
            }
        }

        // standard permission tab
        $this->addPermissionTab();
    }
    
    /**
     * Set the sub tabs
     * 
     * @param string	main tab identifier
     */
    function setSubTabs($a_tab)
    {
    	global $ilUser, $ilTabs, $ilCtrl, $lng;
    	
    	switch ($a_tab)
    	{
    		case "edit":
           		$ilTabs->addSubTab("settings", $lng->txt('settings'), $ilCtrl->getLinkTarget($this, 'edit'));    			
           		$ilTabs->addSubTab("instructions", $this->txt('instructions'), $ilCtrl->getLinkTarget($this, 'editInstructions'));    			
           		$ilTabs->addSubTab("icons", $this->txt('icons'), $ilCtrl->getLinkTarget($this, 'editIcons'));
    			break;

            case "learning_progress":
                $lng->loadLanguageModule('trac');
                if ($this->object->getLPMode() == ilObjExternalContent::LP_ACTIVE && $this->checkPermissionBool("read_learning_progress"))
                {

                    include_once("Services/Tracking/classes/class.ilObjUserTracking.php");
                    if (ilObjUserTracking::_enabledUserRelatedData())
                    {
                        $ilTabs->addSubTab("trac_objects", $lng->txt('trac_objects'), $ilCtrl->getLinkTargetByClass(array('ilObjExternalContentGUI','ilLearningProgressGUI','ilLPListOfObjectsGUI')));
                    }
                    $ilTabs->addSubTab("trac_summary", $lng->txt('trac_summary'), $ilCtrl->getLinkTargetByClass(array('ilObjExternalContentGUI','ilLearningProgressGUI', 'ilLPListOfObjectsGUI'), 'showObjectSummary'));
                }
                if ($this->checkPermissionBool("edit_learning_progress"))
                {
                    $ilTabs->addSubTab("lp_settings", $this->txt('settings'), $ilCtrl->getLinkTargetByClass(array('ilObjExternalContentGUI'), 'editLPSettings'));
                }
                break;
        }
    }

    /**
     * show info screen
     *
     * @access public
     */
    public function infoScreen() 
    {
		global $ilCtrl;

        $this->tabs_gui->activateTab('infoScreen');

        include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
        $info = new ilInfoScreenGUI($this);
        
        $info->addSection($this->txt('xxco_instructions'));
        $info->addProperty("", $this->object->getInstructions());
        
        // meta data
        $xml_obj = $this->object->fetchMetaData(self::META_TIMEOUT_INFO);
        if($xml_obj)
        {
            foreach ($xml_obj->group as $group)
            {
                if (in_array($group['name'], $this->meta_groups))
                {
                    $info->addSection(utf8_decode($group->title));
                    foreach ($group->fields->field as $field)
                    {
                        $info->addProperty(utf8_decode($field->title), $field->content);
                    }
                }
            }
        }

        $info->enablePrivateNotes();
        
        // add view button
        if ($this->object->typedef->getAvailability() == ilExternalContentType::AVAILABILITY_NONE)
        {
            ilUtil::sendFailure($this->lng->txt('xxco_message_type_not_available'), false);
        } elseif ($this->object->getOnline())
        {
            if ($this->object->typedef->getLaunchType() == ilExternalContentType::LAUNCH_TYPE_LINK)
            {
                $info->addButton($this->lng->txt("view"), $ilCtrl->getLinkTarget($this, "view"));
            } elseif ($this->object->typedef->getLaunchType() == ilExternalContentType::LAUNCH_TYPE_PAGE)
             {
                $info->addButton($this->lng->txt("view"), $ilCtrl->getLinkTarget($this, "viewPage"));
            }
        }
		$ilCtrl->forwardCommand($info);
    }

    
    /**
     * view the object (default command)
     *
     * @access public
     */
    function viewObject() 
    {
        global $ilErr;

        switch ($this->object->typedef->getLaunchType())
        {
            case ilExternalContentType::LAUNCH_TYPE_LINK:
                $this->object->trackAccess();
                ilUtil::redirect($this->object->getLaunchLink());
                break;

            case ilExternalContentType::LAUNCH_TYPE_PAGE:
                $this->ctrl->redirect($this, "viewPage");
                break;

            case ilExternalContentType::LAUNCH_TYPE_EMBED:
    			if ($_GET['lti_msg'])
    			{
    				ilUtil::sendInfo(ilUtil::stripSlashes($_GET['lti_msg']), true);
    			}
    			if ($_GET['lti_errormsg'])
    			{
    				ilUtil::sendFailure(ilUtil::stripSlashes($_GET['lti_errormsg']), true);
    			}
    			$this->ctrl->redirect($this, "viewEmbed");
                break;

            default:
                $this->ctrl->redirect($this, "infoScreen");
                break;
        }
    }

    /**
     * view the embedded object
     *
     * @access public
     */
    function viewEmbedObject()
    {
        global $ilErr, $ilUser;

        $this->object->trackAccess();
        $this->tabs_gui->activateTab('viewEmbed');
        $this->tpl->setVariable('ADM_CONTENT', $this->object->getEmbedCode());
    }

    /**
     * view the object as a page
     *
     * @access public
     */
    function viewPageObject()
    {
        global $ilErr;

        $this->object->trackAccess();
        echo $this->object->getPageCode();
        exit;
    }

    /**
     * create new object form
     *
     * @access	public
     */
    function create()
    {
        global $rbacsystem, $ilErr;
        
        $this->create_mode = true;
        if (!$rbacsystem->checkAccess("create", $_GET["ref_id"], $this->type))
        {
            $ilErr->raiseError($this->lng->txt("permission_denied"), $ilErr->MESSAGE);
        }else
        {
            $this->setCreationMode(true);
            $this->initForm("create");
            $this->tpl->setVariable('ADM_CONTENT', $this->form->getHTML());
        }
    }
    
    /**
     * cancel creation of a new object
     *
     * @access	public
     */
    function cancelCreate()
    {
        $this->ctrl->returnToParent($this);
    }

    /**
     * save the data of a new object
     *
     * @access	public
     */
    function save()
    {
        global $rbacsystem, $ilErr;
        
        
            $new_type = $this->getType();
            $_REQUEST["new_type"] = $new_type;
            if (!$rbacsystem->checkAccess("create", $_GET["ref_id"], $new_type))
            {
                $ilErr->raiseError($this->lng->txt("permission_denied"), $ilErr->MESSAGE);
            }
            $this->initForm("create");

            if ($this->form->checkInput())
            {
                $this->object = new ilObjExternalContent;
                $this->object->setType($this->type);
                $this->object->create();
                $this->object->createReference();
                $this->object->putInTree($_GET["ref_id"]);
                $this->object->setPermissions($_GET["ref_id"]);
                $this->saveFormValues();

                $this->ctrl->setParameter($this, "ref_id", $this->object->getRefId());
                $this->afterSave($this->object);
            } 
            else
            {
                $this->form->setValuesByPost();
                $this->tpl->setContent($this->form->getHTML());              
            }
    }

    /**
     * Edit object
     *
     * @access protected
     */
    public function editObject()
    {
        global $ilErr, $ilAccess;

        $this->tabs_gui->activateTab('edit');
        $this->tabs_gui->activateSubTab('settings');

        $this->initForm('edit', $this->loadFormValues());
        // $this->loadFormValues();
        $this->tpl->setContent($this->form->getHTML());
    }

    /**
     * update object
     *
     * @access public
     */
    public function updateObject()
    {
        $this->tabs_gui->activateTab('edit');
        $this->tabs_gui->activateSubTab('settings');
        
        $this->initForm("edit");
        if ($this->form->checkInput())
        {
            $this->saveFormValues();
            ilUtil::sendInfo($this->lng->txt("settings_saved"), true);
            $this->ctrl->redirect($this, "edit");
        }
        else
        {
            $this->form->setValuesByPost();
            $this->tpl->setVariable('ADM_CONTENT', $this->form->getHTML());
        }
    }

    /**
     * Init properties form
     *
     * @param        int        $a_mode        Form Edit Mode (IL_FORM_EDIT | IL_FORM_CREATE)
     * @param		 array		(assoc) form values
     * @access       protected
     */
    protected function initForm($a_mode, $a_values = array())
    {
        if (is_object($this->form))
        {
            return true;
        }

        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $this->form = new ilPropertyFormGUI();
        $this->form->setFormAction($this->ctrl->getFormAction($this));

        if ($a_mode != "create")
        {
	        $item = new ilCustomInputGUI($this->lng->txt('type'), '');
	        $item->setHtml($this->object->typedef->getTitle());
	        $item->setInfo($this->object->typedef->getDescription());
	        $this->form->addItem($item);
        }
        
        $item = new ilTextInputGUI($this->lng->txt('title'), 'title');
        $item->setSize(40);
        $item->setMaxLength(128);
        $item->setRequired(true);
        $item->setInfo($this->txt('xxco_title_info'));
		$item->setValue($a_values['title']);        
        $this->form->addItem($item);

        $item = new ilTextAreaInputGUI($this->lng->txt('description'), 'description');
        $item->setInfo($this->txt('xxco_description_info'));
        $item->setRows(2);
        $item->setCols(80);
		$item->setValue($a_values['description']);        
        $this->form->addItem($item);
       
        if ($a_mode == "create")
        {
            $item = new ilRadioGroupInputGUI($this->lng->txt('type'), 'type_id');
            $item->setRequired(true);
            $types = ilExternalContentType::_getTypesData(false, ilExternalContentType::AVAILABILITY_CREATE);
            foreach ($types as $type)
            {
                $option = new ilRadioOption($type['title'], $type['type_id'], $type['description']);
                $item->addOption($option);
            }
            $this->form->addItem($item);

            $this->form->setTitle($this->txt('xxco_new'));
            $this->form->addCommandButton((!$this->getCreationMode() ? 'update' : 'save'), $this->lng->txt('save'));
            $this->form->addCommandButton('cancelCreate', $this->lng->txt("cancel"));
        }
        else
        {
            $item = new ilCheckboxInputGUI($this->lng->txt('online'), 'online');
            $item->setInfo($this->txt("xxco_online_info"));
			$item->setValue("1");
			if ($a_values['online'])
			{
				$item->setChecked(true);
			}        
          	$this->form->addItem($item);
            
          	// add the type specific fields
        	$this->object->typedef->addFormElements($this->form, $a_values, "object");
        	            
            $this->form->setTitle($this->lng->txt('settings'));
            $this->form->addCommandButton("update", $this->lng->txt("save"));
            $this->form->addCommandButton("view", $this->lng->txt("cancel"));

            if ($this->object->typedef->getMetaDataUrl())
            {
                $this->form->addCommandButton("refreshMeta", $this->lng->txt("xxco_refresh_meta_data"));
            }
        }
    }
    

    /**
     * Fill the properties form with database values
     *
     * @access   protected
     */
    protected function loadFormValues()
    {
        $values = array();

        $values['title'] = $this->object->getTitle();
        $values['description'] = $this->object->getDescription();
        $values['type_id'] = $this->object->getTypeId();
        $values['type'] = $this->object->typedef->getTitle();
        $values['instructions'] = $this->object->getInstructions();
        if ($this->object->getAvailabilityType() == ilObjExternalContent::ACTIVATION_UNLIMITED)
        {
            $values['online'] = '1';
        }
        foreach ($this->object->getInputValues("object") as $field_name => $field_value)
        {
            $values['field_' . $field_name] = $field_value;
        }
        return $values;
    }

    
    /**
     * Save the property form values to the object
     *
     * @access   protected
     */
    protected function saveFormValues() 
    {

        $this->object->setTitle($this->form->getInput("title"));
        $this->object->setDescription($this->form->getInput("description"));
        if ($this->form->getInput("type_id"))
        {
            $this->object->setTypeId($this->form->getInput("type_id"));
        }
        $this->object->setAvailabilityType($this->form->getInput('online') ? ilObjExternalContent::ACTIVATION_UNLIMITED : ilObjExternalContent::ACTIVATION_OFFLINE);
        $this->object->update();
        
        foreach ($this->object->typedef->getInputFields("object") as $field)
        {
        	$value = trim($this->form->getInput("field_" . $field->field_name));
            $this->object->saveFieldValue($field->field_name, $value ? $value : $field->default);
        }
    }
    
    /**
     * Show the form to edit the instructions
     */
    function editInstructionsObject()
    {
        $this->tabs_gui->activateTab('edit');
        $this->tabs_gui->activateSubTab('instructions');
        
        $this->initFormInstructions();
        $this->tpl->setContent($this->form->getHTML());
    }
    
    
    /**
     * Init the form to edit the instructions
     */
    protected function initFormInstructions()
    {
        global $ilSetting, $lng, $ilCtrl;
        
      	include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();
        $form->setFormAction($ilCtrl->getFormAction($this));
        $form->setTitle($this->txt('edit_instructions'));

		$item = new ilTextAreaInputGUI($this->txt('xxco_instructions'), 'instructions');
		$item->setInfo($this->txt('xxco_instructions_info'));
		$item->setRows(10);
		$item->setCols(80);
		$item->setUseRte(true);
		$item->setValue($this->object->getInstructions());
		$form->addItem($item);
                
        $form->addCommandButton('updateInstructions', $lng->txt('save'));  
        $this->form = $form;
    }
    

    /**
     * Submit the Instructions form
     */
    function updateInstructionsObject()
    {
        global $ilCtrl, $lng, $tpl;

        $this->tabs_gui->activateTab('edit');
        $this->tabs_gui->activateSubTab('instuctions');
        
        $this->initFormInstructions();
        if (!$this->form->checkInput())
        {
            $this->form->setValuesByPost();
            $tpl->setContent($this->form->getHTML());
            return;
        } 
        
        $this->object->setInstructions($this->form->getInput("instructions"));
        $this->object->update();
        
		ilUtil::sendSuccess($this->txt('instructions_saved'), true);
        $ilCtrl->redirect($this, 'editInstructions');
    }
    
    
    /**
     * Show the form to edit an existing type
     */
    function editIconsObject()
    {
        $this->tabs_gui->activateTab('edit');
        $this->tabs_gui->activateSubTab('icons');
        
        $this->initFormIcons();
        $this->tpl->setContent($this->form->getHTML());
    }
    
    
    /**
     * Init the form to set the object icons
     */
    protected function initFormIcons()
    {
        global $ilSetting, $lng, $ilCtrl;
        
      	include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();
        $form->setFormAction($ilCtrl->getFormAction($this));
        $form->setTitle($this->txt('icons'));

		$caption = $this->txt("svg_icon");
		$item = new ilImageFileInputGUI($caption, "svg_icon");
		$item->setSuffixes(array("svg"));
		$item->setImage(ilExternalContentPlugin::_getIcon("xxco", "svg", 0, $type_id, "object"));
		$form->addItem($item);

		$caption = $lng->txt("big_icon")." (".ilExternalContentPlugin::BIG_ICON_SIZE.")";
		$item = new ilImageFileInputGUI($caption, "big_icon");
		$item->setImage(ilExternalContentPlugin::_getIcon("xxco", "big", 0, $type_id, "object"));
		$form->addItem($item);

		$caption = $lng->txt("standard_icon")." (".ilExternalContentPlugin::SMALL_ICON_SIZE.")";
		$item = new ilImageFileInputGUI($caption, "small_icon");
		$item->setImage(ilExternalContentPlugin::_getIcon("xxco", "small", 0, $type_id, "object"));
		$form->addItem($item);

		$caption = $lng->txt("tiny_icon")." (".ilExternalContentPlugin::TINY_ICON_SIZE.")";
		$item = new ilImageFileInputGUI($caption, "tiny_icon");
		$item->setImage(ilExternalContentPlugin::_getIcon("xxco", "tiny", 0, $type_id, "object"));
		$form->addItem($item);
                
        $form->addCommandButton('updateIcons', $lng->txt('save'));  
        $this->form = $form;
    }
    

    /**
     * Submit the icons form
     */
    function updateIconsObject()
    {
        global $ilCtrl, $lng, $tpl;

        $this->tabs_gui->activateTab('edit');
        $this->tabs_gui->activateSubTab('icons');
                
        $this->initFormIcons();
        if (!$this->form->checkInput())
        {
            $this->form->setValuesByPost();
            $tpl->setContent($this->form->getHTML());
            return;
        }

		if($_POST["svg_icon_delete"])
		{
			ilExternalContentPlugin::_removeIcon("svg", "object", $this->object->getId());
		}
		if($_POST["big_icon_delete"])
		{
			ilExternalContentPlugin::_removeIcon("big", "object", $this->object->getId());
		}
		if($_POST["small_icon_delete"])
		{
			ilExternalContentPlugin::_removeIcon("small", "object", $this->object->getId());
		}
		if($_POST["tiny_icon_delete"])
		{
			ilExternalContentPlugin::_removeIcon("tiny", "object", $this->object->getId());
		}

		ilExternalContentPlugin::_saveIcon($_FILES["svg_icon"]['tmp_name'], "svg", "object", $this->object->getId());
		ilExternalContentPlugin::_saveIcon($_FILES["big_icon"]['tmp_name'], "big", "object", $this->object->getId());
		ilExternalContentPlugin::_saveIcon($_FILES["small_icon"]['tmp_name'], "small", "object", $this->object->getId());
		ilExternalContentPlugin::_saveIcon($_FILES["tiny_icon"]['tmp_name'], "tiny", "object", $this->object->getId());

		ilUtil::sendSuccess($this->txt('icons_saved'), true);
        $ilCtrl->redirect($this, 'editIcons');
    }

    /**
     * Edit the learning progress settings
     */
    protected function editLPSettingsObject()
    {
        $this->tabs_gui->activateTab('learning_progress');
        $this->tabs_gui->activateSubTab('lp_settings');

        $this->initFormLPSettings();
        $this->tpl->setContent($this->form->getHTML());
    }

    /**
     * Init the form for Learning progress settings
     */
    protected function initFormLPSettings()
    {
        global $ilSetting, $lng, $ilCtrl;

        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();
        $form->setFormAction($ilCtrl->getFormAction($this));
        $form->setTitle($this->txt('lp_settings'));

        $rg = new ilRadioGroupInputGUI($this->txt('lp_mode'), 'lp_mode');
        $rg->setRequired(true);
        $rg->setValue($this->object->getLPMode());
        $ro = new ilRadioOption($this->txt('lp_inactive'),ilObjExternalContent::LP_INACTIVE, $this->txt('lp_inactive_info'));
        $rg->addOption($ro);
        $ro = new ilRadioOption($this->txt('lp_active'),ilObjExternalContent::LP_ACTIVE, $this->txt('lp_active_info'));

        $ni = new ilNumberInputGUI($this->txt('lp_threshold'),'lp_threshold');
        $ni->setMinValue(0);
        $ni->setMaxValue(1);
        $ni->setDecimals(2);
        $ni->setSize(4);
        $ni->setRequired(true);
        $ni->setValue($this->object->getLPThreshold());
        $ni->setInfo($this->txt('lp_threshold_info'));
        $ro->addSubItem($ni);

        $rg->addOption($ro);
        $form->addItem($rg);

        $form->addCommandButton('updateLPSettings', $lng->txt('save'));
        $this->form = $form;

    }

    /**
     * Update the LP settings
     */
    protected function updateLPSettingsObject()
    {
        $this->tabs_gui->activateTab('learning_progress');
        $this->tabs_gui->activateSubTab('lp_settings');

        $this->initFormLPSettings();
        if (!$this->form->checkInput())
        {
            $this->form->setValuesByPost();
            $tpl->setContent($this->form->getHTML());
            return;
        }

        $this->object->setLPMode($this->form->getInput('lp_mode'));
        $this->object->setLPThreshold($this->form->getInput('lp_threshold'));
        $this->object->update();
        $this->ctrl->redirect($this, 'editLPSettings');
    }

     /**
     * Refresh the meta data
     *
     * @access   public
     */
    public function refreshMetaObject()
    {
        $this->object->fetchMetaData(self::META_TIMEOUT_REFRESH);
        $this->ctrl->redirect($this, "infoScreen");
    }

    /**
     * check a token for validity
     * 
     * @return boolean	check is ok
     */
    function checkToken()
    {
        $obj = new ilObjExternalContent();
        $value = $obj->checkToken();
        echo $value;
    }
}

?>