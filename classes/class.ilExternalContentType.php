<?php
/**
 * Copyright (c) 2013 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg 
 * GPLv2, see LICENSE 
 */

/**
 * External Content plugin: type definition
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 */ 
class ilExternalContentType
{

    const AVAILABILITY_NONE = 0;  // Type is not longer available (error message)
    const AVAILABILITY_EXISTING = 1; // Existing objects of the can be used, but no new created
    const AVAILABILITY_CREATE = 2;  // New objects of this type can be created
    
    const FIELDTYPE_ILIAS = "ilias";
    const FIELDTYPE_TEMPLATE = "template";
    const FIELDTYPE_CALCULATED = "calculated";
    
    const FIELDTYPE_TEXT = "text";
    const FIELDTYPE_TEXTAREA = "textarea";
    const FIELDTYPE_PASSWORD = "password";
    const FIELDTYPE_CHECKBOX = "checkbox";
    const FIELDTYPE_RADIO = "radio";
    const FIELDTYPE_HEADER = "header";
    const FIELDTYPE_DESCRIPTION = "description";
    
    
    const LAUNCH_TYPE_PAGE = "page";
    const LAUNCH_TYPE_LINK = "link";
    const LAUNCH_TYPE_EMBED = "embed";
    
    private $type_id;
    private $name;
    private $xml = '';

    /**
     * These data are also in the interface XML
     * 
     * title and description can be set with methods
     * the others are set by xml
     */
    private $title;
    private $description;
    private $template;
    private $launch_type = self::LAUNCH_TYPE_LINK;
    private $meta_data_url;
    
    /**
     * These data are set separately from the interface XML
     */
    private $availability = self::AVAILABILITY_CREATE;
    private $remarks;
    private $placeholder_start = "{";
    private $placeholder_end = "}";
    private $time_to_delete;
    private $use_logs;
    private $use_learning_progress;

    /**
     * Array of fields
     *   
     * @var array 	list of field objects with properties
     */
    private $fields = array();

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct($a_type_id = 0)
    {
    	// this uses the cached plugin object
		$this->plugin_object = ilPlugin::getPluginObject(IL_COMP_SERVICE, 'Repository', 'robj', 'ExternalContent');

        if ($a_type_id)
        {
            $this->type_id = $a_type_id;
            $this->read();
        }
    }

    /**
     * Set Type Id
     * @param int id
     */
    public function setTypeId($a_type_id)
    {
        $this->type_id = $a_type_id;
    }

    /**
     * Get Type Id
     * @return int id
     */
    public function getTypeId()
    {
        return $this->type_id;
    }

    /**
     * Set Name
     * @param string name
     */
    public function setName($a_name)
    {
        $this->name = $a_name;
    }

    /**
     * Get Name
     * @return string name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set Title
     * @param string title
     */
    public function setTitle($a_title)
    {
        $this->title = $a_title;
    }

    /**
     * Get Title
     * @return string title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set Description
     * @param string description
     */
    public function setDescription($a_description)
    {
        $this->description = $a_description;
    }

    /**
     * Get Description
     * @return string description
     */
    public function getDescription()
    {
        return $this->description;
    }


    /**
     * Get Template
     * @return string template
     */
    public function getTemplate()
    {
        return $this->template;
    }


    /**
     * Get Launch Tape
     * @return string launch_type
     */
    public function getLaunchType()
    {
        return $this->launch_type;
    }

    /**
     * get Mata DataURL
     * 
     * @param string url
     */
    public function getMetaDataUrl()
    {
        return $this->meta_data_url;
    }

    /**
     * Set Availability
     *
     * @param integer availability
     */
    public function setAvailability($a_availability)
    {
        $this->availability = $a_availability;
    }

    /**
     * get Availability
     *
     * @return integer availability
     */
    public function getAvailability()
    {
        return $this->availability;
    }

    /**
     * Set Remarks
     *
     * @param string remarks
     */
    public function setRemarks($a_remarks)
    {
        $this->remarks = $a_remarks;
    }

    /**
     * Get Remarks
     *
     * @return string remarks
     */
    public function getRemarks()
    {
        return $this->remarks;
    }
    
    /**
     * Set time to delete
     *
     * @param string time_to_delete
     */
    public function setTimeToDelete($a_time_to_delete)
    {
        $this->time_to_delete = $a_time_to_delete;
    }

    /**
     * Get time to time_to_delete
     *
     * @return string time_to_delete
     */
    public function getTimeToDelete()
    {
        return $this->time_to_delete;
    }
    
    /**
     * Set use logs
     *
     * @param string $a_option
     */
    public function setUseLogs($a_option)
    {
        $this->use_logs = $a_option;
    }

    /**
     * Get use logs
     *
     * @return string use_logs
     */
    public function getUseLogs()
    {
        return $this->use_logs;
    }
    
    /**
     * Set use lm
     *
     * @param string $a_option
     */
    public function setUseLearningProgress($a_option)
    {
        $this->use_learning_progress = $a_option;
    }

    /**
     * Get use lm
     *
     * @return string use_learning_progress
     */
    public function getUseLearningProgress()
    {
        return $this->use_learning_progress;
    }
    
    
    /**
     * get the type definition as an XML structure
     * (refreshes the title and description) 
     * 
     * @return	string	xml definition
     */
    public function getXML()
    {
        $doc = new DOMDocument('1.0');
        $doc->formatOutput = true;
        @$doc->loadXML($this->xml);

        // create interface element, if not existing
        if (!$interface = $this->getDomChildByName($doc, 'interface'))
        {
        	$interface = $doc->appendChild($doc->createElement('interface', ''));
        	$interface->setAttribute('type', self::LAUNCH_TYPE_LINK);
        }
        
        
        // set the title in xml according to the title property
        if ($old = $this->getDomChildByName($interface, 'title'))
        {
        	$interface->replaceChild($doc->createElement('title', $this->getTitle()), $old);
        }
        else
        {
        	$interface->appendChild($doc->createElement('title', $this->getTitle()));
        }

        // set the description in xml according to the description property
        if ($old = $this->getDomChildByName($interface, 'description'))
        {
        	$interface->replaceChild($doc->createElement('description', $this->getDescription()), $old);
        }
        else
        {
        	$interface->appendChild($doc->createElement('description', $this->getDescription()));
        }
        
        // TODO: save field values according to the type configuration

        $this->xml = $doc->saveXML();
        
        return $this->xml;
    }

    /**
     * set the type definition from an xml structure
     * 
     * @param	string	xml definition
     * @param	string	(byref) variable for failure message
     * @return	boolean setting successful
     */
    public function setXML($a_xml, &$a_failure_message)
    {
        global $lng;

        $this->plugin_object->includeClass('class.ilExternalContentEncodings.php');
        
        $doc = new DOMDocument('1.0');
        $doc->formatOutput = true;

        if (!@$doc->loadXML($a_xml))
        {
            $err = error_get_last();
            $a_failure_message = $err[message];
            return false;
        }

        if (!$interface = $this->getDomChildByName($doc, 'interface'))
        {
            $a_failure_message = $this->txt('type_failure_interfaces');
            return false;
       	}
        
        $launch_type = $interface->getAttribute('type');
        switch ($launch_type)
        {
            case self::LAUNCH_TYPE_EMBED:
            case self::LAUNCH_TYPE_LINK:
            case self::LAUNCH_TYPE_PAGE:
                break;
            default:
                $a_failure_message = sprintf($this->txt('type_failure_launch_type'), $launch_type);
	            return false;
        }

        if ($title_element = $this->getDomChildByName($interface, 'title'))
        {
            $title = $title_element->textContent;
        }
        if ($description_element = $this->getDomChildByName($interface, 'description'))
        {
            $description = $description_element->textContent;
        }
        if ($template_element = $this->getDomChildByName($interface, 'template'))
        {
            $template = $template_element->textContent;
        }
        if ($metasource_element = $this->getDomChildByName($interface, 'metasource'))
        {
            $metasource = $metasource_element->textContent;
        }
        
        $tmp_fields = array();
        $fields = $interface->getElementsByTagName('field');
        foreach ($fields as $field)
        {
            $tmp = (object) null;
            
            // basic properties
            $tmp->field_name = $field->getAttribute('name');
            $tmp->field_type = $field->getAttribute('type');
            
            // properties for input fields
            $tmp->required = $field->getAttribute('required');
            $tmp->size = $field->getAttribute('size');
           	$tmp->rows = $field->getAttribute('rows');
           	$tmp->cols = $field->getAttribute('cols');
           	$tmp->richtext = $field->getAttribute('richtext');
           	$tmp->default = $field->getAttribute('default');

           	// appearance of input fields
           	$tmp->parentfield = $field->getAttribute('parentfield');
            $tmp->parentvalue = $field->getAttribute('parentvalue');
            $tmp->level = $field->getAttribute('level') ? $field->getAttribute('level') : "object";
            
            // processing properties
           	$tmp->encoding = $field->getAttribute('encoding');
            $tmp->function = $field->getAttribute('function');

            // optional sub elements (field type specific)
            if ($title_element = $this->getDomChildByName($field, 'title'))
            {
            	$tmp->title = $title_element->textContent;
            }
            if ($description_element = $this->getDomChildByName($field, 'description'))
            {
            	$tmp->description = $description_element->textContent;
            }
            if ($template_element = $this->getDomChildByName($field, 'template'))
            {
            	$tmp->template = $template_element->textContent;
            }
            
            // set options for radio fields
            $tmp->options = array();
           foreach ($field->getElementsByTagName('option') as $option)
            {
            	$opt = (object) null;
            	$opt->value = $option->getAttribute('value');
            	$opt->title = $this->getDomChildByName($option, 'title')->textContent;
            	$opt->description = $this->getDomChildByName($option, 'description')->textContent;
            	$tmp->options[$opt->value] = $opt;
            }
            
            // set parameters for function fields
            $tmp->params = array();
            foreach ($field->getElementsByTagName('param') as $param)
            {
          		$tmp->params[$param->getAttribute('name')] = $param->textContent;
            }
                        
            // checks field name
            if (!$tmp->field_name)
            {
                $a_failure_message = $this->txt('type_failure_field_name');
	            return false;
            }
            
            // check the field type
            switch ($tmp->field_type)
            {
                case self::FIELDTYPE_TEMPLATE:
               	case self::FIELDTYPE_CALCULATED:
                case self::FIELDTYPE_TEXT:
                case self::FIELDTYPE_TEXTAREA:
                case self::FIELDTYPE_PASSWORD:
                case self::FIELDTYPE_CHECKBOX:
                case self::FIELDTYPE_RADIO:
                case self::FIELDTYPE_HEADER:
                case self::FIELDTYPE_DESCRIPTION:
                	break;

                default:
                    $a_failure_message = sprintf($this->txt('type_failure_field_type'), $tmp->field_type);
		            return false;
            }
            
            // check the encoding
            if (!ilExternalContentEncodings::_encodingExists($tmp->encoding))
            {
                $a_failure_message = sprintf($this->txt('type_failure_encoding'), $tmp->encoding);
	            return false;
            }

			// all checks are ok	            
            $tmp_fields[] = $tmp;
        }

        // set the data if no error occurred
        $this->xml = $a_xml;
        $this->title = $title;
        $this->description = $description;
        $this->launch_type = $launch_type;
        $this->template = $template;
        $this->meta_data_url = $metasource;
        $this->fields = $tmp_fields;
        
        return true;
    }
    
    /**
     * get a DOM child elemet with a specific name
     * 
     * @param 	DOMNode		node
     * @param 	string		child name
     * @return 	mixed		DomElement	or false if not found
     */
    private function getDomChildByName($a_node, $a_name)
    {
    	foreach ($a_node->childNodes as $child_node)
    	{
    		if ($child_node->nodeType == XML_ELEMENT_NODE 
    			and $child_node->nodeName == $a_name)
    		{
    			// return the first found child
    			return $child_node;
    		}
    	}
    	// element was not found
    	return false;
    }

    /**
     * Read function
     *
     * @access public
     */
    public function read()
    {
        global $ilDB, $ilErr;

        $query = 'SELECT * FROM xxco_data_types WHERE type_id = '
                . $ilDB->quote($this->getTypeId(), 'integer');

        $res = $ilDB->query($query);
        $row = $ilDB->fetchObject($res);
        if ($row) 
        {
            $this->type_id = $row->type_id;
            $this->name= $row->type_name;
            $this->title = $row->title;
            $this->description = $row->description;
                        
            $this->availability = $row->availability;
            $this->remarks = $row->remarks;
            $this->time_to_delete = $row->time_to_delete;
            $this->use_logs = $row->use_logs;
            $this->use_learning_progress = $row->use_learning_progress;

           	if ($this->setXML($row->interface_xml, $void))
           	{
           		return $this->xml; 	
           	}            
        }
        return false;
    }

    /**
     * Create a new type
     *
     * @access public
     */
    public function create() {
        global $ilDB;

        $this->type_id = $ilDB->nextId('xxco_data_types');
        $this->update();
    }

    /**
     * Update function
     *
     * @access public
     */
    public function update() {
        global $ilDB;

        $ilDB->replace('xxco_data_types', 
        	 array(
            	'type_id' => array('integer', $this->getTypeId())
             ), 
             array(
	            'type_name' => array('text', $this->getName()),
	            'title' => array('text', $this->getTitle()),
	            'description' => array('clob', $this->getDescription()),
	            'availability' => array('integer', $this->getAvailability()),
	            'remarks' => array('clob', $this->getRemarks()),
	            'time_to_delete' => array('integer', $this->getTimeToDelete()),
	            'use_logs' => array('text', $this->getUseLogs()),
	            'use_learning_progress' => array('text', $this->getUseLearningProgress()),
             	'interface_xml' => array('clob', $this->getXML())
             )
        );
        return true;
    }

    /**
     * Delete
     *
     * @access public
     */
    public function delete() {
        global $ilDB;

        ilExternalContentPlugin::_deleteWebspaceDir("type", $this->getTypeId());
        
        $query = "DELETE FROM xxco_data_types " .
                "WHERE type_id = " . $ilDB->quote($this->getTypeId(), 'integer');
        $ilDB->manipulate($query);

        return true;
    }

    
    /**
     * Save field values
     *
     * @access public
     */
    public function saveFieldValue($a_field_name, $a_field_value) 
    {
        global $ilDB;

        $ilDB->replace('xxco_type_values', array(
            'type_id' => array('integer', $this->getTypeId()),
            'field_name' => array('text', $a_field_name)
                ), array(
            'field_value' => array('text', $a_field_value)
                )
        );

        return true;
    }

    /**
     * Get array of input values
     */
    function getInputValues() 
    {
        global $ilDB;

        $query = 'SELECT * FROM xxco_type_values WHERE type_id = '
                . $ilDB->quote($this->getTypeId(), 'integer');
        $res = $ilDB->query($query);

        $values = array();
        while ($row = $ilDB->fetchObject($res)) 
        {
            $values[$row->field_name] = $row->field_value;
        }
        return $values;
    }
    
    
    /**
     * add type specific input fields to a form  
     * 
     * @param object	form, property or radio option
     * @param array		(assoc) input values
     * @param string	configuration level ("type" or "object")
     * @param string	parent field value
     * @param string	parent option value
     * @param int		maximum recursion depth
     */
    function addFormElements($a_object, $a_values = array(), 
    	$a_level = "object", $a_parentfield = '', $a_parentvalue = '', 
    	$a_maxdepth = "3")
    {
    	// recursion end
    	if ($a_maxdepth == 0)
    	{
    		return;
    	}   	

		foreach ($this->getInputFields($a_level, $a_parentfield, $a_parentvalue) as $field)
		{
			$value = $a_values['field_' . $field->field_name];
			$value = $value ? $value : $field->default;
			 
			switch($field->field_type)
			{
			    case self::FIELDTYPE_HEADER:
			    	$item = new ilFormSectionHeaderGUI();
			    	$item->setTitle($field->title);
			    	break;
			    	
			    case self::FIELDTYPE_DESCRIPTION:			    	
		    		$item = new ilCustomInputGUI($field->title);
		    		$item->setHtml(nl2br($field->description));
			    	break;
			    	
				case self::FIELDTYPE_TEXT:
					$item = new ilTextInputGUI($field->title, 'field_' . $field->field_name);
					$item->setInfo($field->description);
					$item->setRequired($field->required ? true : false);
					$item->setSize($field->size);
			    	$item->setValue($value);
					break;
					
				case self::FIELDTYPE_TEXTAREA:
					$item = new ilTextAreaInputGUI($field->title, 'field_' . $field->field_name);
					$item->setInfo($field->description);
					$item->setRequired($field->required ? true : false);
					$item->setUseRte($field->richtext ? true : false);
					$item->setRows($field->rows);
					$item->setCols($field->cols);
			    	$item->setValue($value);
					break;
				
				case self::FIELDTYPE_PASSWORD:
					$item = new ilPasswordInputGUI($field->title, 'field_' . $field->field_name);
					$item->setInfo($field->description);
					$item->setRequired($field->required ? true : false);
					$item->setSkipSyntaxCheck(true);
			    	$item->setValue($value);
					break;
								    	
			    case self::FIELDTYPE_CHECKBOX:
					$item = new ilCheckboxInputGUI($field->title, 'field_' . $field->field_name);
					$item->setInfo($field->description);
					if ($value)
					{
						$item->setChecked(true);
					}
					break;
			    				    	
			    case self::FIELDTYPE_RADIO:
					$item = new ilRadioGroupInputGUI($field->title, 'field_' . $field->field_name);
					$item->setInfo($field->description);
			    	$item->setValue($value);
					
					foreach ($field->options as $option)
					{
						$ropt = new ilRadioOption($option->title, $option->value);
						$ropt->setInfo($option->description);

						// add the sub items to the option
						$item->addOption($ropt);
						$this->addFormElements($ropt, $a_values, $a_level, $field->field_name, $option->value, $a_maxdepth - 1);
					}
					break;
				
			    default:
			    	continue 2;	
			}

			// add the item to the form or to the parent item
			if (is_a($a_object, 'ilPropertyFormGUI'))
	    	{
				$a_object->addItem($item);
	    	}
			else
	    	{
				$a_object->addSubItem($item);
	    	}
			
			// add the sub items to the item
			if (is_a($item, 'ilSubEnabledFormPropertyGUI'))
			{
				$this->addFormElements($item, $a_level, $a_values, $field->field_name, '', $a_maxdepth - 1);
			}
			
		} 	
    }
    

    /**
     * Get array of input fields for the type
     * 
     * @var		mixed	level ("type" or "object")
     * @var		mixed	parent field name or null
     * @var		mixed	parent field option or null
     * @return	array	list of field objects
     */
    function getInputFields($a_level = 'object', $a_parentfield = null, $a_parentvalue = null) 
    {
        $fields = array();
        foreach ($this->fields as $field) 
        {
            if (		$field->field_type != self::FIELDTYPE_TEMPLATE
                    and $field->field_type != self::FIELDTYPE_ILIAS 
                    and $field->field_type != self::FIELDTYPE_CALCULATED
                    and (!isset($a_level) or $field->level == $a_level)
            		and (!isset($a_parentfield) or $field->parentfield == $a_parentfield)
            		and (!isset($a_parentvalue) or $field->parentvalue == $a_parentvalue)
            ) 
            {
            	$fields[] = $field;    
            }                        
        }
        return $fields;
    }
    
    
    /**
     * Get the field definitions of the type
     * 
     * @return array	list of assoc field definitions
     */
    function getFieldsAssoc() 
    {
        $fields = array();
        foreach ($this->fields as $field) 
        {
            $fields[] = (array) $field;
        }
        return $fields;
    }

    /**
     * get the placeholder in a template according to a field name
     * 
     * @param string $a_name
     */
    function getPlaceholder($a_name) 
    {
        return $this->placeholder_start . $a_name . $this->placeholder_end;
    }

    /**
     * get a language text
	 *
     * @param 	string		language variable
     * @return 	string		interface text
     */
    function txt($a_langvar)
    {
    	return $this->plugin_object->txt($a_langvar);
    }
    
    /**
     * Get array of options for selecting the type
     * 
     * @param	mixed		required availability or null
     * @return	array		id => title
     */
    static function _getTypeOptions($a_availability = null) 
    {
        global $ilDB;

        $query = "SELECT * FROM xxco_data_types";
        if (isset($a_availability)) {
            $query .= " WHERE availability=" . $ilDB->quote($a_availability, 'integer');
        }
        $res = $ilDB->query($query);

        $options = array();
        while ($row = $ilDB->fetchObject($res)) 
        {
            $options[$row->type_id] = $row->title;
        }
        return $options;
    }

    /**
     * Get basic data array of all types (without field definitions)
     * 
     * @param	boolean		get extended data ('usages')
     * @param	mixed		required availability or null
     * @return	array		array of assoc data arrays
     */
    static function _getTypesData($a_extended = false, $a_availability = null)
    {
        global $ilDB;

        $query = "SELECT * FROM xxco_data_types";
        if (isset($a_availability)) {
            $query .= " WHERE availability=" . $ilDB->quote($a_availability, 'integer');
        }
        $query .= " ORDER BY type_name";
        $res = $ilDB->query($query);

        $data = array();
        while ($row = $ilDB->fetchAssoc($res)) 
        {
            if ($a_extended) 
            {
                $row['usages'] = self::_countUntrashedUsages($row['type_id']);
            }
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Count the number of untrashed usages of a type
     * 
     * @var		integer		type_id
     * @return	integer		number of references
     */
    static function _countUntrashedUsages($a_type_id) {
        global $ilDB;

        $query = "SELECT COUNT(*) untrashed FROM xxco_data_settings s"
                . " INNER JOIN object_reference r ON s.obj_id = r.obj_id"
                . " WHERE r.deleted IS NULL "
                . " AND s.type_id = " . $ilDB->quote($a_type_id, 'integer');

        $res = $ilDB->query($query);
        $row = $ilDB->fetchObject($res);
        return $row->untrashed;
    }

    /**
     * Get the active object references for a certain type and field value
     * @param $a_type_id
     * @param $a_field_name
     * @param $a_field_value
     * @return int[]   ref_ids
     */
    static function _getRefIdsByTypeAndField($a_type_id, $a_field_name, $a_field_value)
    {
        global $DIC;

        $query = "
            SELECT r.ref_id
            FROM xxco_data_settings s 
            INNER JOIN xxco_data_values v ON s.obj_id = v.obj_id 
            INNER JOIN object_reference r ON s.obj_id = r.obj_id 
            WHERE s.type_id = %s
            AND v.field_name = %s 
            AND v.field_value = %s
            AND r.deleted IS NULL
        ";

        $result = $DIC->database()->queryF($query,
            ['integer','text','text'],
            [$a_type_id, $a_field_name, $a_field_value]
        );

        $ref_ids = [];
        while ($row = $DIC->database()->fetchAssoc($result)) {
            $ref_ids[] = $row['ref_id'];
        }
        return $ref_ids;
    }
}

?>