<?php
/**
 * Copyright (c) 2013 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg 
 * GPLv2, see LICENSE 
 */

include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");

/**
 * ExternalContent plugin: configuration GUI
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 */ 
class ilExternalContentConfigGUI extends ilPluginConfigGUI
{
    /** @var ilExternalContentType */
    protected $type;

    /** @var ilPropertyFormGUI */
    protected $form;

    /**
     * perform command
     */
    public function performCommand($cmd)
    {
		global $tree, $rbacsystem, $ilErr, $lng, $ilCtrl, $tpl;
		
		$this->plugin_object->includeClass('class.ilExternalContentType.php');
		$this->plugin_object->includeClass('class.ilObjExternalContent.php');
		
        // control flow
        $cmd = $ilCtrl->getCmd($this);
        switch ($cmd)
        {
            case 'editType':
           	case 'editIcons':
            case 'editDefinition':
           	case 'submitFormSettings':
            case 'submitFormIcons':
            case 'submitFormDefinition':
            case 'deleteType':
            case 'deleteTypeConfirmed':
            	$this->type = new ilExternalContentType($_GET['type_id']);
            	$tpl->setDescription($this->type->getName());
            	
            	$ilCtrl->saveParameter($this, 'type_id');
            	$this->initTabs('edit_type');
           		$this->$cmd();
            	break;
            	
            default:
            	$this->initTabs();
            	if (!$cmd)
                {
                    $cmd = "configure";
                }
                $this->$cmd();
                break;
        }
    }
    
    /**
     * Get a plugin specific language text
     * 
     * @param 	string	language var
     */
    function txt($a_var)
    {
    	return $this->plugin_object->txt($a_var);
    }
    
    /**
     * Init Tabs
     * 
     * @param string	mode ('edit_type' or '')
     */
    function initTabs($a_mode = "")
    {
    	global $ilCtrl, $ilTabs, $lng;

    	switch ($a_mode)
    	{
    		case "edit_type":
    			$ilTabs->clearTargets();
    			
    			/*
				$ilTabs->setBack2Target(
					$lng->txt("cmps_plugin_slot"),
					$ilCtrl->getLinkTargetByClass("ilobjcomponentsettingsgui", "showPluginSlot")
				);
				*/

				$ilTabs->setBackTarget(
					$this->plugin_object->txt('content_types'),
					$ilCtrl->getLinkTarget($this, 'listTypes')
				);

				$ilTabs->addTab("edit_type", 
    				$this->plugin_object->txt('xxco_edit_type'), 
    				$ilCtrl->getLinkTarget($this, 'editType')
    			);
    			
    			$ilTabs->addSubTab("type_settings", 
    				$this->plugin_object->txt('type_settings'), 
    				$ilCtrl->getLinkTarget($this, 'editType')
    			);
    			
    			$ilTabs->addSubTab("type_icons", 
    				$this->plugin_object->txt('icons'), 
    				$ilCtrl->getLinkTarget($this, 'editIcons')
    			);

    		 	$ilTabs->addSubTab("type_definition", 
    				$this->plugin_object->txt('type_definition'), 
    				$ilCtrl->getLinkTarget($this, 'editDefinition')
    			);
    			
    			break;
    			
    		default:
    			$ilTabs->addTab("types", 
    				$this->plugin_object->txt('content_types'), 
    				$ilCtrl->getLinkTarget($this, 'listTypes')
    			);
				break;	
    	}
    }

    /**
     * Entry point for configuring the module
     */
    function configure()
    {
        $this->listTypes();
    }

    /**
     * Show a list of the xxco types
     */
    function listTypes()
    {
        global $tpl;

        require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/classes/class.ilExternalContentTypesTableGUI.php');
        $table_gui = new ilExternalContentTypesTableGUI($this, 'listTypes');
        $table_gui->init($this);
        $tpl->setContent($table_gui->getHTML());
    }

    /**
     * Show the form to add a new type
     */
    function createType()
    {
        global $tpl;
        $this->initCreateForm();
        $tpl->setContent($this->form->getHTML());
    }
    
    /**
     * 
     * 
     * @return unknown_type
     */
    private function initCreateForm()
    {
    	global $ilCtrl, $lng;
    	
        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        
        $this->plugin_object->includeClass('class.ilExternalContentModel.php');
    	
        $form = new ilPropertyFormGUI();
        $form->setFormAction($ilCtrl->getFormAction($this));
        $form->setTitle($this->txt('create_type'));
        
        $item1 = new ilTextInputGUI($this->txt('type_name'), 'name');
        $item1->setInfo($this->txt('type_name_info'));
        $item1->setRequired(true);
        $item1->setMaxLength(32);
        $form->addItem($item1);
        
        $item = new ilRadioGroupInputGUI($this->txt('model'), 'model');
        $item->setRequired(true);
        $models = ilExternalContentModel::_getModelsList();
        foreach ($models as $model)
        {
        	$value = $value ? $value : $model['name'];
        	$option = new ilRadioOption($model['title'], $model['name'], $model['description']);
            $item->addOption($option);
        }
        $item->setValue($value);
        $form->addItem($item);
        
        $form->addCommandButton('submitNewType', $lng->txt('save'));
        $form->addCommandButton('listTypes', $lng->txt('cancel'));
        
        $this->form = $form;
    }
    
    /**
     * Submit a new type
     * 
     * @return unknown_type
     */
    private function submitNewType()
    {
    	global $lng, $ilCtrl, $tpl;
    	
        $this->initCreateForm();
    	if (!$this->form->checkInput())
        {
            $this->form->setValuesByPost();
            $tpl->setContent($this->form->getHTML());
            return;
        } 
        else
        {
        	$type_id = ilExternalContentModel::_createTypeFromModel(
        		$this->form->getInput("model"),
        		$this->form->getInput("name")
        		);
            $ilCtrl->setParameter($this, 'type_id', $type_id);
            $ilCtrl->redirect($this, 'editType');	
        }
    	
    }

    /**
     * Show the form to edit an existing type
     */
    function editType()
    {
        global $ilCtrl, $ilTabs, $tpl;

        $ilTabs->activateSubTab('type_settings');
        $this->initFormSettings($this->loadTypeSettings());
        $tpl->setContent($this->form->getHTML());
    }
    

    /**
     * Init the form to edit the type settings
     * 
     * @param	array	values to set
     */
    private function initFormSettings($a_values = array())
    {
        global $lng, $ilCtrl;

        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");

        $form = new ilPropertyFormGUI();
        $form->setFormAction($ilCtrl->getFormAction($this));
        $form->setTitle($lng->txt('settings'));

        $item = new ilTextInputGUI($this->txt('type_name'), 'name');
        $item->setValue($a_values['name']);
        $item->setInfo($this->txt('type_name_info'));
        $item->setRequired(true);
        $item->setMaxLength(32);
        $form->addItem($item);

        $item = new ilTextInputGUI($lng->txt('title'), 'title');
        $item->setValue($a_values['title']);
        $item->setInfo($this->txt('type_title_info'));
        $item->setRequired(true);
        $item->setMaxLength(255);
        $form->addItem($item);

        $item = new ilTextInputGUI($lng->txt('description'), 'description');
        $item->setValue($a_values['description']);
        $item->setInfo($this->txt('type_description_info'));
        $form->addItem($item);
        
        $item = new ilSelectInputGUI($this->txt('type_availability'), 'availability');
        $item->setOptions (
                array(
                    ilExternalContentType::AVAILABILITY_CREATE => $this->txt('availability_' . ilExternalContentType::AVAILABILITY_CREATE),
                    ilExternalContentType::AVAILABILITY_EXISTING => $this->txt('availability_' . ilExternalContentType::AVAILABILITY_EXISTING),
                    ilExternalContentType::AVAILABILITY_NONE => $this->txt('availability_' . ilExternalContentType::AVAILABILITY_NONE)
                )
        );
        $item->setValue($a_values['availability']);
        $item->setInfo($this->txt('type_availability_info'));
        $item->setRequired(true);
        $form->addItem($item);

        $item = new ilTextAreaInputGUI($this->txt('type_remarks'), 'remarks');
        $item->setInfo($this->txt('type_remarks_info'));
        $item->setValue($a_values['remarks']);
        $item->setRows(5);
        $item->setCols(80);
        $form->addItem($item);

        // add the type specific fields
        $this->type->addFormElements($form, $a_values, "type");
        
        /* 
		
		// TOKEN MANAGEMENT
        $item5 = new ilTextInputGUI($this->txt('type_time_to_delete'), 'time_to_delete');
        $item5->setInfo($this->txt('type_time_to_delete_info'));
        if(!$type->getTimeToDelete()){
            $item5->setValue(10);
        }else{
            $item5->setValue($this->type->getTimeToDelete());
        }
        $item5->setRequired(true);
        $item5->setMaxLength(32);
        $form->addItem($item5);
        
        
       //LOG MANAGEMENT
        $item9 = new ilSelectInputGUI($this->txt('log_set'), 'use_logs');
        $item9->setOptions(
                array(
                    "ON" => $this->txt('log_on'),
                    "OFF" => $this->txt('log_off')
                )
        );
        $item9->setInfo($this->txt('logs_info'));
        $item9->setRequired(true);
        $item9->setValue($this->type->getUseLogs());
        $form->addItem($item9);
        
        
        //LEARNING PROGRESS MANAGEMENT
        $item10 = new ilSelectInputGUI($this->txt('learning_progress_set'), 'use_learning_progress');
        $item10->setOptions(
                array(
                    "ON" => $this->txt('lp_on'),
                    "OFF" => $this->txt('lp_off')
                )
        );
        $item10->setInfo($this->txt('lp_info'));
        $item10->setRequired(true);
        $item10->setValue($this->type->getUseLearningProgress());
        $form->addItem($item10);

        */
        
        $form->addCommandButton('submitFormSettings', $lng->txt('save'));  
        $this->form = $form;
    }
    
    /**
     * Get the values for filling the settings form
     *
     * @return   array	type settings
     */
    protected function loadTypeSettings()
    {
        $values = array();

        $values['name'] = $this->type->getName();
        $values['title'] = $this->type->getTitle();
        $values['description'] = $this->type->getDescription();
        $values['availability'] = $this->type->getAvailability();
        $values['remarks'] = $this->type->getRemarks();
        foreach ($this->type->getInputValues() as $field_name => $field_value)
        {
            $values['field_' . $field_name] = $field_value;
        }
        return $values;
    }
    

    /**
     * Submit the form to save or update
     */
    function submitFormSettings()
    {
        global $ilCtrl, $ilTabs, $lng, $tpl;

        $ilTabs->activateSubTab('type_settings');
        
        $this->initFormSettings();
        if (!$this->form->checkInput())
        {
            $this->form->setValuesByPost();
            $tpl->setContent($this->form->getHTML());
            return;
        } 
        else
        {
            $this->type->setName($this->form->getInput("name"));
            $this->type->setTitle($this->form->getInput("title"));
            $this->type->setDescription($this->form->getInput("description"));
            $this->type->setAvailability($this->form->getInput("availability"));
            $this->type->setRemarks($this->form->getInput("remarks"));
            
            /*
            $this->type->setTimeToDelete($this->form->getInput("time_to_delete"));
            $this->type->setUseLogs($this->form->getInput("use_logs"));
            $this->type->setUseLearningProgress($this->form->getInput("use_learning_progress"));
            */
            $this->type->update();

            foreach ($this->type->getFormValues($this->form, 'type') as $field_name => $field_value)
	        {
	            $this->type->saveFieldValue($field_name->field_name, $field_value);
	        }
            
            ilUtil::sendSuccess($this->txt('type_saved'), true);
        }

        $ilCtrl->redirect($this, 'editType');
    }
    
    /**
     * Show the form to edit an existing type
     */
    function editIcons()
    {
        global $ilCtrl, $ilTabs, $tpl;

        $ilTabs->activateSubTab('type_icons');
        $this->initFormIcons();
        $tpl->setContent($this->form->getHTML());
    }
    
    /**
     * Init the form to set the type icons
     * 
     * @param	integer		type id 
     */
    private function initFormIcons()
    {
        global $ilSetting, $lng, $ilCtrl;

        $type_id = $this->type->getTypeId();

        $svg = ilExternalContentPlugin::_getIcon("xxco", "svg", 0, $type_id, "type");

        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();
        $form->setFormAction($ilCtrl->getFormAction($this));
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
            $item->setImage(ilExternalContentPlugin::_getIcon("xxco", "big", 0, $type_id, "type"));
            $form->addItem($item);

            $caption = $this->txt("standard_icon")." (".ilExternalContentPlugin::SMALL_ICON_SIZE.")";
            $item = new ilImageFileInputGUI($caption, "small_icon");
            $item->setImage(ilExternalContentPlugin::_getIcon("xxco", "small", 0, $type_id, "type"));
            $form->addItem($item);

            $caption = $this->txt("tiny_icon")." (".ilExternalContentPlugin::TINY_ICON_SIZE.")";
            $item = new ilImageFileInputGUI($caption, "tiny_icon");
            $item->setImage(ilExternalContentPlugin::_getIcon("xxco", "tiny", 0, $type_id, "type"));
            $form->addItem($item);
        }

        $form->addCommandButton('submitFormIcons', $lng->txt('save'));
        $this->form = $form;
    }
    

    /**
     * Submit the icons form
     */
    function submitFormIcons()
    {
        global $ilCtrl, $ilTabs, $lng, $tpl;

        $ilTabs->activateSubTab('type_icons');
        
        $type_id = $this->type->getTypeId();
        $this->initFormIcons();
        if (!$this->form->checkInput())
        {
            $this->form->setValuesByPost();
            $tpl->setContent($this->form->getHTML());
            return;
        }

		if($_POST["svg_icon_delete"])
		{
			ilExternalContentPlugin::_removeIcon("svg", "type", $type_id);
		}
  		if($_POST["big_icon_delete"])
		{
			ilExternalContentPlugin::_removeIcon("big", "type", $type_id);
		}
		if($_POST["small_icon_delete"])
		{
			ilExternalContentPlugin::_removeIcon("small", "type", $type_id);
		}
		if($_POST["tiny_icon_delete"])
		{
			ilExternalContentPlugin::_removeIcon("tiny", "type", $type_id);
		}

		ilExternalContentPlugin::_saveIcon($_FILES["svg_icon"]['tmp_name'], "svg", "type", $type_id);
		ilExternalContentPlugin::_saveIcon($_FILES["big_icon"]['tmp_name'], "big", "type", $type_id);
		ilExternalContentPlugin::_saveIcon($_FILES["small_icon"]['tmp_name'], "small", "type", $type_id);
		ilExternalContentPlugin::_saveIcon($_FILES["tiny_icon"]['tmp_name'], "tiny", "type", $type_id);

		ilUtil::sendSuccess($this->plugin_object->txt('icons_saved'), true);
        $ilCtrl->redirect($this, 'editIcons');
    }
    
    
    /**
     * Show the form to edit an XML definition
     */
    function editDefinition()
    {
        global $ilCtrl, $ilTabs, $tpl;

        $ilTabs->activateSubTab('type_definition');
        $this->initFormDefinition();
        $style="<style>#form_edit_xml label, div.ilFormOption {display:none;}</style>";
        $tpl->setContent($style. $this->form->getHTML());
    }
    
    
    /**
     * Init the form to add or edit a type
     * 
     * @param	string		xml data of input		
     */
    private function initFormDefinition($a_xml = null)
    {
        global $lng, $ilCtrl;

        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");

       if (!isset($a_xml))
        {
            $a_xml = $this->type->getXML();
        }
        
        $form = new ilPropertyFormGUI();
        $form->setId('edit_xml');
        $form->setFormAction($ilCtrl->getFormAction($this));
        $form->setTitle($this->txt('type_definition'));

        $item = new ilCustomInputGUI('');
        $tpl = new ilTemplate('tpl.edit_xml.html', true, true, "Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/");
        $tpl->setVariable("CONTENT", ilUtil::prepareFormOutput($a_xml));
        $item->setHTML($tpl->get());
        $item->setInfo($this->txt('type_definition_info'));
        $form->addItem($item);

        $form->addCommandButton('submitFormDefinition', $lng->txt('save'));
        
        $this->form = $form;
    }

    
    /**
     * Submit the definition form
     */
    function submitFormDefinition()
    {
        global $ilCtrl, $ilTabs, $lng, $tpl;

        $ilCtrl->saveParameter($this, 'type_id');
        $ilTabs->activateSubTab('type_definition');
        
        $xml = ilUtil::stripOnlySlashes($_POST['xml']);
        $this->initFormDefinition($xml);
 		$this->form->checkInput();

        $message= "";
        if (!$this->type->setXML($xml, $message))
        {
            $this->form->setValuesByPost();
            ilUtil::sendFailure($this->txt('type_failure_xml') . '<br />' . $message, false);
        	$style="<style>#il_prop_cont_ {display:none;}</style>";
            $tpl->setContent($style.$this->form->getHTML());
            return;
        } 
                
        $this->type->update();
        ilUtil::sendSuccess($this->txt('type_updated'), true);
        $ilCtrl->redirect($this, 'editDefinition');
    }
    
    

    /**
     * Show a confirmation screen to delete a type
     */
    function deleteType()
    {
        global $ilCtrl, $lng, $tpl;

        require_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');

        $gui = new ilConfirmationGUI();
        $gui->setFormAction($ilCtrl->getFormAction($this));
        $gui->setHeaderText($this->txt('delete_type'));
        $gui->addItem('type_id', $this->type->getTypeId(), $this->type->getName());
        $gui->setConfirm($lng->txt('delete'), 'deleteTypeConfirmed');
        $gui->setCancel($lng->txt('cancel'), 'listTypes');

        $tpl->setContent($gui->getHTML());
    }

    /**
     * Delete a type after confirmation
     */
    function deleteTypeConfirmed()
    {
        global $ilCtrl, $lng;

        $this->type->delete();
        ilUtil::sendSuccess($this->txt('type_deleted'), true);
        $ilCtrl->redirect($this, 'listTypes');
    }
}
?>