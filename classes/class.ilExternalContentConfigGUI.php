<?php
/**
 * Copyright (c) 2013 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg 
 * GPLv2, see LICENSE 
 */

/**
 * ExternalContent plugin: configuration GUI
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 * @ilCtrl_IsCalledBy ilExternalContentConfigGUI: ilObjComponentSettingsGUI
 */ 
class ilExternalContentConfigGUI extends ilPluginConfigGUI
{
    use ilExternalContentGUIBase;

    /** @var ilExternalContentType */
    protected $type;

    /** @var ilPropertyFormGUI */
    protected $form;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->initGlobals();
    }

    /**
     * perform command
     * @param string $cmd
     */
    public function performCommand(string $cmd): void
    {
        // control flow
        $cmd = $this->ctrl->getCmd();
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
            	$this->tpl->setDescription($this->type->getName());
            	
            	$this->ctrl->saveParameter($this, 'type_id');
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
     * @param string $a_var language var
     * @return string
     */
    protected function txt($a_var)
    {
    	return $this->plugin_object->txt($a_var);
    }
    
    /**
     * Init Tabs
     * 
     * @param string $a_mode	mode ('edit_type' or '')
     */
    protected function initTabs($a_mode = "")
    {
    	switch ($a_mode)
    	{
    		case "edit_type":
    			$this->tabs->clearTargets();

                $this->tabs->setBackTarget(
					$this->plugin_object->txt('content_types'),
					$this->ctrl->getLinkTarget($this, 'listTypes')
				);

                $this->tabs->addTab("edit_type",
    				$this->plugin_object->txt('xxco_edit_type'),
                    $this->ctrl->getLinkTarget($this, 'editType')
    			);
                $this->tabs->activateTab('edit_type');

                $this->tabs->addSubTab("type_settings",
    				$this->plugin_object->txt('type_settings'),
                    $this->ctrl->getLinkTarget($this, 'editType')
    			);

                $this->tabs->addSubTab("type_icons",
    				$this->plugin_object->txt('icons'),
                    $this->ctrl->getLinkTarget($this, 'editIcons')
    			);

                $this->tabs->addSubTab("type_definition",
    				$this->plugin_object->txt('type_definition'),
                    $this->ctrl->getLinkTarget($this, 'editDefinition')
    			);
    			
    			break;
    			
    		default:
                $this->tabs->addTab("types",
    				$this->plugin_object->txt('content_types'),
                    $this->ctrl->getLinkTarget($this, 'listTypes')
    			);
				break;	
    	}
    }

    /**
     * Entry point for configuring the module
     */
    protected function configure()
    {
        $this->listTypes();
    }

    /**
     * Show a list of the xxco types
     */
    protected function listTypes()
    {
        $table_gui = new ilExternalContentTypesTableGUI($this, 'listTypes');
        $table_gui->init($this);
        $this->tpl->setContent($table_gui->getHTML());
    }

    /**
     * Show the form to add a new type
     */
    protected function createType()
    {
        $this->initCreateForm();
        $this->tpl->setContent($this->form->getHTML());
    }
    
    /**
     * Init the type creation form
     */
    protected function initCreateForm()
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->txt('create_type'));
        
        $item1 = new ilTextInputGUI($this->txt('type_name'), 'name');
        $item1->setInfo($this->txt('type_name_info'));
        $item1->setRequired(true);
        $item1->setMaxLength(32);
        $form->addItem($item1);
        
        $item = new ilRadioGroupInputGUI($this->txt('model'), 'model');
        $item->setRequired(true);
        $models = ilExternalContentModel::_getModelsList();
        $value = '';
        foreach ($models as $model)
        {
        	$value = $value ?? $model['name'];
        	$option = new ilRadioOption($model['title'], $model['name'], $model['description']);
            $item->addOption($option);
        }
        $item->setValue($value);
        $form->addItem($item);
        
        $form->addCommandButton('submitNewType', $this->lng->txt('save'));
        $form->addCommandButton('listTypes', $this->lng->txt('cancel'));
        
        $this->form = $form;
    }
    
    /**
     * Submit a new type
     */
    protected function submitNewType()
    {
        $this->initCreateForm();
    	if (!$this->form->checkInput())
        {
            $this->form->setValuesByPost();
            $this->tpl->setContent($this->form->getHTML());
        } 
        else
        {
        	$type_id = ilExternalContentModel::_createTypeFromModel(
        		$this->form->getInput("model"),
        		$this->form->getInput("name")
        		);
            $this->ctrl->setParameter($this, 'type_id', $type_id);
            $this->ctrl->redirect($this, 'editType');
        }
    	
    }

    /**
     * Show the form to edit an existing type
     */
    protected function editType()
    {
        $this->tabs->activateSubTab('type_settings');
        $this->initFormSettings($this->loadTypeSettings());
        $this->tpl->setContent($this->form->getHTML());
    }
    

    /**
     * Init the form to edit the type settings
     * 
     * @param	array	$a_values values to set
     */
    protected function initFormSettings($a_values = array())
    {
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt('settings'));

        $item = new ilTextInputGUI($this->txt('type_name'), 'name');
        $item->setValue((string) ($a_values['name'] ?? ''));
        $item->setInfo($this->txt('type_name_info'));
        $item->setRequired(true);
        $item->setMaxLength(32);
        $form->addItem($item);

        $item = new ilTextInputGUI($this->lng->txt('title'), 'title');
        $item->setValue((string) ($a_values['title'] ?? ''));
        $item->setInfo($this->txt('type_title_info'));
        $item->setRequired(true);
        $item->setMaxLength(255);
        $form->addItem($item);

        $item = new ilTextInputGUI($this->lng->txt('description'), 'description');
        $item->setValue((string) ($a_values['description'] ?? ''));
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
        $item->setValue((int) ($a_values['availability'] ?? ilExternalContentType::AVAILABILITY_CREATE));
        $item->setInfo($this->txt('type_availability_info'));
        $item->setRequired(true);
        $form->addItem($item);

        $item = new ilTextAreaInputGUI($this->txt('type_remarks'), 'remarks');
        $item->setInfo($this->txt('type_remarks_info'));
        $item->setValue((string) ($a_values['remarks'] ?? ''));
        $item->setRows(5);
        $form->addItem($item);

        // add the type specific fields
        $this->type->addFormElements($form, $a_values, "type");

        $form->addCommandButton('submitFormSettings', $this->lng->txt('save'));
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
    protected function submitFormSettings()
    {
        $this->tabs->activateSubTab('type_settings');
        
        $this->initFormSettings();
        if (!$this->form->checkInput())
        {
            $this->form->setValuesByPost();
            $this->tpl->setContent($this->form->getHTML());
            return;
        } 
        else
        {
            $this->type->setName($this->form->getInput("name"));
            $this->type->setTitle($this->form->getInput("title"));
            $this->type->setDescription($this->form->getInput("description"));
            $this->type->setAvailability($this->form->getInput("availability"));
            $this->type->setRemarks($this->form->getInput("remarks"));
            $this->type->update();

            foreach ($this->type->getFormValues($this->form, 'type') as $field_name => $field_value)
	        {
	            $this->type->saveInputValue($field_name, $field_value);
	        }
            
            $this->tpl->setOnScreenMessage('success', $this->txt('type_saved'), true);
        }

        $this->ctrl->redirect($this, 'editType');
    }
    
    /**
     * Show the form to edit an existing type
     */
    protected function editIcons()
    {
        $this->tabs->activateSubTab('type_icons');
        $this->initFormIcons();
        $this->tpl->setContent($this->form->getHTML());
    }
    
    /**
     * Init the form to set the type icons
     * 
     * @param	integer		$a_values type id 
     */
    protected function initFormIcons()
    {
        $type_id = $this->type->getTypeId();

        $svg = ilExternalContentPlugin::_getContentIcon("xxco", "svg", 0, $type_id, "type");
        
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->txt('icons'));

		$caption = $this->txt("svg_icon");
		$item = new ilImageFileInputGUI($caption, "svg_icon");
		$item->setSuffixes(array("svg"));
		$item->setImage($svg);
		$form->addItem($item);
        
        $form->addCommandButton('submitFormIcons', $this->lng->txt('save'));
        $this->form = $form;
    }
    

    /**
     * Submit the icons form
     */
    protected function submitFormIcons()
    {
        $this->tabs->activateSubTab('type_icons');
        
        $type_id = $this->type->getTypeId();
        $this->initFormIcons();
        if (!$this->form->checkInput())
        {
            $this->form->setValuesByPost();
            $this->tpl->setContent($this->form->getHTML());
            return;
        }

		if ($_POST["svg_icon_delete"])
		{
			ilExternalContentPlugin::_removeIcon("svg", "type", $type_id);
		}

		ilExternalContentPlugin::_saveIcon($_FILES["svg_icon"]['tmp_name'], "svg", "type", $type_id);

        $this->tpl->setOnScreenMessage('success', $this->plugin_object->txt('icons_saved'), true);
        $this->ctrl->redirect($this, 'editIcons');
    }
    
    
    /**
     * Show the form to edit an XML definition
     */
    function editDefinition()
    {
        $this->tabs->activateSubTab('type_definition');
        $this->initFormDefinition();
        $style="<style>#form_edit_xml label, div.ilFormOption {display:none;}</style>";
        $this->tpl->setContent($style. $this->form->getHTML());
    }
    
    
    /**
     * Init the form to add or edit a type
     * @param	string	$a_xml	xml data of input
     */
    protected function initFormDefinition($a_xml = null)
    {
        if (!isset($a_xml))
        {
            $a_xml = $this->type->getXML();
        }
        
        $form = new ilPropertyFormGUI();
        $form->setId('edit_xml');
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->txt('type_definition'));

        $item = new ilCustomInputGUI('');
        $tpl = new ilTemplate('tpl.edit_xml.html', true, true, "Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/");
        $tpl->setVariable("CONTENT", ilLegacyFormElementsUtil::prepareFormOutput($a_xml));
        $item->setHTML($tpl->get());
        $item->setInfo($this->txt('type_definition_info'));
        $form->addItem($item);

        $form->addCommandButton('submitFormDefinition', $this->lng->txt('save'));
        
        $this->form = $form;
    }

    
    /**
     * Submit the definition form
     */
    protected function submitFormDefinition()
    {
        $this->ctrl->saveParameter($this, 'type_id');
        $this->tabs->activateSubTab('type_definition');
        
        $xml = ilUtil::stripOnlySlashes($_POST['xml']);
        $this->initFormDefinition($xml);
 		$this->form->checkInput();

        $message= "";
        if (!$this->type->setXML($xml, $message))
        {
            $this->form->setValuesByPost();
            $this->tpl->setOnScreenMessage('failure', $this->txt('type_failure_xml') . '<br />' . $message, false);
        	$style="<style>#il_prop_cont_ {display:none;}</style>";
            $this->tpl->setContent($style.$this->form->getHTML());
            return;
        } 
                
        $this->type->update();
        $this->tpl->setOnScreenMessage('success', $this->txt('type_updated'), true);
        $this->ctrl->redirect($this, 'editDefinition');
    }
    
    

    /**
     * Show a confirmation screen to delete a type
     */
    protected function deleteType()
    {
        $gui = new ilConfirmationGUI();
        $gui->setFormAction($this->ctrl->getFormAction($this));
        $gui->setHeaderText($this->txt('delete_type'));
        $gui->addItem('type_id', $this->type->getTypeId(), $this->type->getName());
        $gui->setConfirm($this->lng->txt('delete'), 'deleteTypeConfirmed');
        $gui->setCancel($this->lng->txt('cancel'), 'listTypes');

        $this->tpl->setContent($gui->getHTML());
    }

    /**
     * Delete a type after confirmation
     */
    protected function deleteTypeConfirmed()
    {
        $this->type->delete();
        $this->tpl->setOnScreenMessage('success', $this->txt('type_deleted'), true);
        $this->ctrl->redirect($this, 'listTypes');
    }
}