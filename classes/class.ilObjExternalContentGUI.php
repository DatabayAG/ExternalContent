<?php 
/**
 * Copyright (c) 2013 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg 
 * GPLv2, see LICENSE 
 */

require_once(__DIR__ . '/class.ilObjExternalContent.php');

/**
 * External Content plugin: repository object GUI
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 * 
 * @ilCtrl_isCalledBy ilObjExternalContentGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls ilObjExternalContentGUI: ilPermissionGUI, ilExternalContentLogGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonactionDispatcherGUI, ilLearningProgressGUI
 */
class ilObjExternalContentGUI extends ilObjectPluginGUI
{
    /** @var ilPropertyFormGUI */
    protected $form;

    /** @var ilObjExternalContent */
    public $object;

    /**
     * Goto redirection
     * Overridden to provide the goto suffix
     */
    public static function _goto($a_target)
    {
        global $DIC;

        $t = explode("_", $a_target[0]);
        $ref_id = (int) $t[0];
        $class_name = $a_target[1];
        $goto_suffix = (string) $t[1];

        if ($DIC->access()->checkAccess("read", "", $ref_id))
        {
            $DIC->ctrl()->initBaseClass("ilObjPluginDispatchGUI");
            $DIC->ctrl()->setTargetScript("ilias.php");
            $DIC->ctrl()->getCallStructure(strtolower("ilObjPluginDispatchGUI"));
            $DIC->ctrl()->setParameterByClass($class_name, "ref_id", $ref_id);
            $DIC->ctrl()->setParameterByClass($class_name, "goto_suffix", $goto_suffix);
            $DIC->ctrl()->redirectByClass(array("ilobjplugindispatchgui", $class_name), "");
        }
        else {
            parent::_goto($a_target);
        }
    }


    /**
     * Get type.
     */
    final function getType()
    {
        return "xxco";
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


	/**
 	 * Extended check for being in creation mode
	 *
	 * Use this instead of getCreationMode() because ilRepositoryGUI sets it weakly
	 * The creation form for external contents is extended and has different commands
	 * In creation mode $this->object is the parent container and can't be used
	 *
	 * @return bool		creation mode
	 */
	protected function checkCreationMode()
	{
		$cmd = $this->ctrl->getCmd();
		if ($cmd == "create" or $cmd == "cancelCreate" or $cmd == "save" or $cmd == "Save")
		{
			$this->setCreationMode(true);
		}
		return $this->getCreationMode();
	}

	/**
     * Perform command
     */
    public function performCommand($cmd)
    {
		if (!$this->checkCreationMode())
		{
			// set a return URL
			// IMPORTANT: the last parameter prevents an encoding of & to &amp;
			// Otherwise the OAuth signatore is calculated wrongly!
			$this->object->setReturnURL(ILIAS_HTTP_PATH . "/". $this->ctrl->getLinkTarget($this, "view", "", true));

			// set the goto suffix, e.g. autostart
            $this->object->setGotoSuffix($_GET['goto_suffix']);
		}

        switch ($cmd)
        {
        	case "edit":
        	case "update":
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

            default:
            	
				if ($this->checkCreationMode())
				{
					$this->$cmd();
				}
				else
				{
					$this->checkPermission("read");

					if ($this->object->getTypeDef()->getAvailability() == ilExternalContentType::AVAILABILITY_NONE)
					{
						$this->ilErr->raiseError($this->txt('xxco_message_type_not_available'), $this->ilErr->MESSAGE);
					}

					if (!$cmd)
					{
						$cmd = "viewObject";
					}
					$cmd .= "Object";
					$this->$cmd();
				}
        }
    }
	
	
    /**
     * Set tabs
     */
    function setTabs()
    {
 		if ($this->checkCreationMode())
		{
			return;
		}

		// view tab
		if ($this->object->getTypeDef()->getLaunchType() == ilExternalContentType::LAUNCH_TYPE_EMBED)
		{
			$this->tabs->addTab("viewEmbed", $this->lng->txt("content"), $this->ctrl->getLinkTarget($this, "viewEmbed"));
		}

        //  info screen tab
        $this->tabs->addTab("infoScreen", $this->lng->txt("info_short"), $this->ctrl->getLinkTarget($this, "infoScreen"));

        // add "edit" tab
        if ($this->checkPermissionBool("write"))
        {
            $this->tabs->addTab("edit", $this->lng->txt("settings"), $this->ctrl->getLinkTarget($this, "edit"));
        }

        include_once("Services/Tracking/classes/class.ilObjUserTracking.php");
        if (ilObjUserTracking::_enabledLearningProgress() &&
            ($this->checkPermissionBool("edit_learning_progress") || $this->checkPermissionBool("read_learning_progress")))
        {
            if ($this->object->getSettings()->getLPMode() == ilExternalContentSettings::LP_ACTIVE && $this->checkPermissionBool("read_learning_progress"))
            {
                if (ilObjUserTracking::_enabledUserRelatedData())
                {
                    $this->tabs->addTab("learning_progress", $this->lng->txt('learning_progress'), $this->ctrl->getLinkTargetByClass(array('ilObjExternalContentGUI','ilLearningProgressGUI','ilLPListOfObjectsGUI')));
                }
                else
                {
                    $this->tabs->addTab("learning_progress", $this->lng->txt('learning_progress'), $this->ctrl->getLinkTargetByClass(array('ilObjExternalContentGUI','ilLearningProgressGUI', 'ilLPListOfObjectsGUI'), 'showObjectSummary'));
                }
            }
            elseif ($this->checkPermissionBool("edit_learning_progress"))
            {
                $this->tabs->addTab('learning_progress', $this->lng->txt('learning_progress'), $this->ctrl->getLinkTarget($this,'editLPSettings'));
            }

			if (in_array($this->ctrl->getCmdClass(), array('illearningprogressgui', 'illplistofobjectsgui')))
			{
                $this->tabs->addSubTab("lp_settings", $this->lng->txt('settings'), $this->ctrl->getLinkTargetByClass(array('ilObjExternalContentGUI'), 'editLPSettings'));
			}
        }

        // standard permission tab
        $this->addPermissionTab();
    }
    
    /**
     * Set the sub tabs
     * @param string	main tab identifier
     */
    function setSubTabs($a_tab)
    {
    	switch ($a_tab)
    	{
    		case "edit":
           		$this->tabs->addSubTab("settings", $this->lng->txt('settings'), $this->ctrl->getLinkTarget($this, 'edit'));
                $this->tabs->addSubTab("instructions", $this->txt('instructions'), $this->ctrl->getLinkTarget($this, 'editInstructions'));
                $this->tabs->addSubTab("icons", $this->txt('icons'), $this->ctrl->getLinkTarget($this, 'editIcons'));
    			break;

            case "learning_progress":
                $this->lng->loadLanguageModule('trac');
				if ($this->checkPermissionBool("edit_learning_progress"))
				{
                    $this->tabs->addSubTab("lp_settings", $this->txt('settings'), $this->ctrl->getLinkTargetByClass(array('ilObjExternalContentGUI'), 'editLPSettings'));
				}
                if ($this->object->getSettings()->getLPMode() == ilExternalContentSettings::LP_ACTIVE && $this->checkPermissionBool("read_learning_progress"))
                {

                    include_once("Services/Tracking/classes/class.ilObjUserTracking.php");
                    if (ilObjUserTracking::_enabledUserRelatedData())
                    {
                        $this->tabs->addSubTab("trac_objects", $this->lng->txt('trac_objects'), $this->ctrl->getLinkTargetByClass(array('ilObjExternalContentGUI','ilLearningProgressGUI','ilLPListOfObjectsGUI')));
                    }
                    $this->tabs->addSubTab("trac_summary", $this->lng->txt('trac_summary'), $this->ctrl->getLinkTargetByClass(array('ilObjExternalContentGUI','ilLearningProgressGUI', 'ilLPListOfObjectsGUI'), 'showObjectSummary'));
                }
                break;
        }
    }

    /**
     * show info screen
     */
    public function infoScreen() 
    {
        $this->tabs_gui->activateTab('infoScreen');

        include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
        $info = new ilInfoScreenGUI($this);

        if (!empty( $this->object->getSettings()->getInstructions())) {
            $info->addSection($this->txt('xxco_instructions'));
            $info->addProperty("", $this->object->getSettings()->getInstructions());
        }

        $info->enablePrivateNotes();
        
        // add view button
        if ($this->object->getTypeDef()->getAvailability() == ilExternalContentType::AVAILABILITY_NONE)
        {
            ilUtil::sendFailure($this->lng->txt('xxco_message_type_not_available'), false);
        } elseif ($this->object->getOnline())
        {
            if ($this->object->getTypeDef()->getLaunchType() == ilExternalContentType::LAUNCH_TYPE_LINK)
            {
                $info->addButton($this->lng->txt("view"), $this->ctrl->getLinkTarget($this, "view"));
            } elseif ($this->object->getTypeDef()->getLaunchType() == ilExternalContentType::LAUNCH_TYPE_PAGE)
             {
                $info->addButton($this->lng->txt("view"), $this->ctrl->getLinkTarget($this, "viewPage"));
            }
        }
		$this->ctrl->forwardCommand($info);
    }

    
    /**
     * view the object (default command)
     */
    function viewObject() 
    {
        $this->ctrl->saveParameter($this, 'goto_suffix');

        switch ($this->object->getTypeDef()->getLaunchType())
        {
            case ilExternalContentType::LAUNCH_TYPE_LINK:
                $this->object->trackAccess();
                ilUtil::redirect($this->object->getRenderer()->render());
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
        $this->object->trackAccess();
        $this->tabs_gui->activateTab('viewEmbed');
        $this->tpl->setVariable('ADM_CONTENT', $this->object->getRenderer()->render());
    }

    /**
     * view the object as a page
     *
     * @access public
     */
    function viewPageObject()
    {
        $this->object->trackAccess();
        echo $this->object->getRenderer()->render();
        exit;
    }

    /**
     * create new object form
     */
    function create()
    {
       $this->setCreationMode(true);
       if (!$this->access->checkAccess("create", '', $_GET["ref_id"], $this->getType())) {
           $this->ilErr->raiseError($this->lng->txt("permission_denied"), $this->ilErr->MESSAGE);
        }
		else
        {
            $this->initForm("create");
            $this->tpl->setVariable('ADM_CONTENT', $this->form->getHTML());
        }
    }
    
    /**
     * cancel creation of a new object
     */
    function cancelCreate()
    {
        $this->ctrl->returnToParent($this);
    }

    /**
     * save the data of a new object
     */
    function save()
    {
            $_REQUEST["new_type"] = $this->getType();
            if (!$this->access->checkAccess("create", '', $_GET["ref_id"], $this->getType()))
            {
                $this->ilErr->raiseError($this->lng->txt("permission_denied"), $this->ilErr->MESSAGE);
            }
            $this->initForm("create");

            if ($this->form->checkInput())
            {
                $this->object = new ilObjExternalContent;
                $this->object->setType($this->getType());
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
     */
    public function editObject()
    {
        $this->tabs_gui->activateTab('edit');
        $this->tabs_gui->activateSubTab('settings');

        $this->initForm('edit', $this->loadFormValues());
        $this->tpl->setContent($this->form->getHTML());
    }

    /**
     * update object
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
     * @param		 array		$a_values       (assoc) form values
     */
    protected function initForm($a_mode, $a_values = array())
    {
        if (is_object($this->form))
        {
            return;
        }

        $this->form = new ilPropertyFormGUI();
        $this->form->setFormAction($this->ctrl->getFormAction($this));

        if ($a_mode != "create")
        {
	        $item = new ilNonEditableValueGUI($this->lng->txt('type'), '');
	        $item->setValue($this->object->getTypeDef()->getTitle());
	        $item->setInfo($this->object->getTypeDef()->getDescription());
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
            $this->form->addCommandButton((!$this->checkCreationMode() ? 'update' : 'save'), $this->lng->txt('save'));
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
        	$this->object->getTypeDef()->addFormElements($this->form, $a_values, "object");
        	            
            $this->form->setTitle($this->lng->txt('settings'));
            $this->form->addCommandButton("update", $this->lng->txt("save"));
            $this->form->addCommandButton("view", $this->lng->txt("cancel"));
        }
    }
    

    /**
     * Fill the properties form with database values
     */
    protected function loadFormValues()
    {
        $values = array();

        $values['title'] = $this->object->getTitle();
        $values['description'] = $this->object->getDescription();
        $values['type_id'] = $this->object->getTypeDef()->getTypeId();
        $values['type'] = $this->object->getTypeDef()->getTitle();
        $values['instructions'] = $this->object->getSettings()->getInstructions();
        if ($this->object->getSettings()->getAvailabilityType() == ilExternalContentSettings::ACTIVATION_UNLIMITED)
        {
            $values['online'] = '1';
        }
        foreach ($this->object->getSettings()->getInputValues() as $field_name => $field_value)
        {
            $values['field_' . $field_name] = $field_value;
        }
        return $values;
    }

    
    /**
     * Save the property form values to the object
     */
    protected function saveFormValues() 
    {
        $this->object->setTitle($this->form->getInput("title"));
        $this->object->setDescription($this->form->getInput("description"));
        if ($this->form->getInput("type_id"))
        {
            $this->object->getSettings()->setTypeId($this->form->getInput("type_id"));
        }
        $this->object->getSettings()->setAvailabilityType($this->form->getInput('online') ? ilExternalContentSettings::ACTIVATION_UNLIMITED : ilExternalContentSettings::ACTIVATION_OFFLINE);
        $this->object->update();


        foreach ($this->object->getTypeDef()->getFormValues($this->form, 'object') as $field_name => $field_value)
        {
            $this->object->getSettings()->saveInputValue($field_name, $field_value);
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
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->txt('edit_instructions'));

		$item = new ilTextAreaInputGUI($this->txt('xxco_instructions'), 'instructions');
		$item->setInfo($this->txt('xxco_instructions_info'));
		$item->setRows(10);
		$item->setUseRte(true);
		$item->setValue($this->object->getSettings()->getInstructions());
		$form->addItem($item);
                
        $form->addCommandButton('updateInstructions', $this->lng->txt('save'));
        $this->form = $form;
    }
    

    /**
     * Submit the Instructions form
     */
    function updateInstructionsObject()
    {
        $this->tabs_gui->activateTab('edit');
        $this->tabs_gui->activateSubTab('instuctions');
        
        $this->initFormInstructions();
        if (!$this->form->checkInput())
        {
            $this->form->setValuesByPost();
            $this->tpl->setContent($this->form->getHTML());
            return;
        } 
        
        $this->object->getSettings()->setInstructions($this->form->getInput("instructions"));
        $this->object->getSettings()->save();
        
		ilUtil::sendSuccess($this->txt('instructions_saved'), true);
        $this->ctrl->redirect($this, 'editInstructions');
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
        $type_id = $this->object->getTypeDef()->getTypeId();
        $obj_id = $this->object->getId();

        $svg = ilExternalContentPlugin::_getIcon("xxco", "svg", $obj_id, $type_id, "object");

        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->txt('icons'));

		$caption = $this->txt("svg_icon");
		$item = new ilImageFileInputGUI($caption, "svg_icon");
		$item->setSuffixes(array("svg"));
		$item->setImage($svg);
		$form->addItem($item);

        if (empty($svg))
        {
            $caption = $this->txt("big_icon")." (".ilExternalContentPlugin::BIG_ICON_SIZE.")";
            $item = new ilImageFileInputGUI($caption, "big_icon");
            $item->setImage(ilExternalContentPlugin::_getIcon("xxco", "big", $obj_id, $type_id, "object"));
            $form->addItem($item);

            $caption = $this->txt("standard_icon")." (".ilExternalContentPlugin::SMALL_ICON_SIZE.")";
            $item = new ilImageFileInputGUI($caption, "small_icon");
            $item->setImage(ilExternalContentPlugin::_getIcon("xxco", "small", $obj_id, $type_id, "object"));
            $form->addItem($item);

            $caption = $this->txt("tiny_icon")." (".ilExternalContentPlugin::TINY_ICON_SIZE.")";
            $item = new ilImageFileInputGUI($caption, "tiny_icon");
            $item->setImage(ilExternalContentPlugin::_getIcon("xxco", "tiny", $obj_id, $type_id, "object"));
            $form->addItem($item);
        }

        $form->addCommandButton('updateIcons', $this->lng->txt('save'));
        $this->form = $form;
    }
    

    /**
     * Submit the icons form
     */
    function updateIconsObject()
    {
        $this->tabs_gui->activateTab('edit');
        $this->tabs_gui->activateSubTab('icons');
                
        $this->initFormIcons();
        if (!$this->form->checkInput())
        {
            $this->form->setValuesByPost();
            $this->tpl->setContent($this->form->getHTML());
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
        $this->ctrl->redirect($this, 'editIcons');
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
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->txt('lp_settings'));

        $rg = new ilRadioGroupInputGUI($this->txt('lp_mode'), 'lp_mode');
        $rg->setRequired(true);
        $rg->setValue($this->object->getSettings()->getLPMode());
        $ro = new ilRadioOption($this->txt('lp_inactive'),ilExternalContentSettings::LP_INACTIVE, $this->txt('lp_inactive_info'));
        $rg->addOption($ro);
        $ro = new ilRadioOption($this->txt('lp_active'),ilExternalContentSettings::LP_ACTIVE, $this->txt('lp_active_info'));

        $ni = new ilNumberInputGUI($this->txt('lp_threshold'),'lp_threshold');
        $ni->setMinValue(0);
        $ni->setMaxValue(1);
        $ni->setDecimals(2);
        $ni->setSize(4);
        $ni->setRequired(true);
        $ni->setValue($this->object->getSettings()->getLPThreshold());
        $ni->setInfo($this->txt('lp_threshold_info'));
        $ro->addSubItem($ni);

        $rg->addOption($ro);
        $form->addItem($rg);

        $form->addCommandButton('updateLPSettings', $this->lng->txt('save'));
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
            $this->tpl->setContent($this->form->getHTML());
            return;
        }

        $this->object->getSettings()->setLPMode($this->form->getInput('lp_mode'));
        $this->object->getSettings()->setLPThreshold($this->form->getInput('lp_threshold'));
        $this->object->getSettings()->save();
        $this->ctrl->redirect($this, 'editLPSettings');
    }
}