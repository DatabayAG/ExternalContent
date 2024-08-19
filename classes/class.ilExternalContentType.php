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

    const AVAILABILITY_NONE = 0;        // Type is not longer available (error message)
    const AVAILABILITY_EXISTING = 1;    // Existing objects of the can be used, but no new created
    const AVAILABILITY_CREATE = 2;      // New objects of this type can be created

    // processing field types
    const FIELDTYPE_ILIAS = "ilias";            // ilias fields have pre-defined values
    const FIELDTYPE_TEMPLATE = "template";      // templates with placeholders for other fields
    const FIELDTYPE_CALCULATED = "calculated";  // values are calculated based on other values

    // input field types
    const FIELDTYPE_TEXT = "text";
    const FIELDTYPE_RAWTEXT = "rawtext";        // text without manipulation at input check
    const FIELDTYPE_TEXTAREA = "textarea";
    const FIELDTYPE_PASSWORD = "password";
    const FIELDTYPE_CHECKBOX = "checkbox";
    const FIELDTYPE_RADIO = "radio";
    const FIELDTYPE_HEADER = "header";
    const FIELDTYPE_DESCRIPTION = "description";
    const FIELDTYPE_SPECIAL = "special";        // special treatment by the field name

    // names of special fields
    const FIELD_LTI_USER_DATA = 'LTI_USER_DATA';
    
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
    
    /**
     * These data are set separately from the interface XML
     */
    private $availability = self::AVAILABILITY_CREATE;
    private $remarks;
    private $placeholder_start = "{";
    private $placeholder_end = "}";

    /** @var ilDBInterface */
    private $db;
    
    /** @var ilExternalContentPlugin */
    private $plugin;

    /**
     * Array of fields
     *   
     * @var array 	list of field objects with properties
     */
    private $fields = array();

    /**
     * Constructor
     * @param int $a_type_id
     */
    public function __construct($a_type_id = 0)
    {
        global $DIC;
        
        $this->db = $DIC->database();
		$this->plugin = ilExternalContentPlugin::getInstance();

        if ($a_type_id)
        {
            $this->type_id = $a_type_id;
            $this->read();
        }
    }

    /**
     * Get a content type id by name
     * @param string $a_name
     * @return int|null
     */
    public static function getIdByName($a_name)
    {
        global $DIC;
        $db = $DIC->database();

        $query = 'SELECT type_id FROM xxco_data_types WHERE type_name = '
            . $db->quote($a_name, 'text');

        $res = $db->query($query);
        $row = $db->fetchAssoc($res);
        if ($row) {
            return $row['type_id'];
        }
        return null;
    }

    /**
     * Set Type Id
     * @param int $a_type_id
     */
    public function setTypeId($a_type_id)
    {
        $this->type_id = $a_type_id;
    }

    /**
     * Get Type Id
     * @return int
     */
    public function getTypeId()
    {
        return $this->type_id;
    }

    /**
     * Set Name
     * @param string $a_name
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
     * @param string $a_title
     */
    public function setTitle($a_title)
    {
        $this->title = $a_title;
    }

    /**
     * Get Title
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set Description
     * @param string $a_description
     */
    public function setDescription($a_description)
    {
        $this->description = $a_description;
    }

    /**
     * Get Description
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }


    /**
     * Get Template
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }


    /**
     * Get Launch Tape
     * @return string
     */
    public function getLaunchType()
    {
        return $this->launch_type;
    }


    /**
     * Set Availability
     *
     * @param integer $a_availability
     */
    public function setAvailability($a_availability)
    {
        $this->availability = $a_availability;
    }

    /**
     * get Availability
     * @return integer
     */
    public function getAvailability()
    {
        return $this->availability;
    }

    /**
     * Set Remarks
     * @param string $a_remarks
     */
    public function setRemarks($a_remarks)
    {
        $this->remarks = $a_remarks;
    }

    /**
     * Get Remarks
     * @return string
     */
    public function getRemarks()
    {
        return $this->remarks;
    }
    
    /**
     * get the type definition as an XML structure
     * (refreshes the title and description)
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

        $this->xml = $doc->saveXML();
        
        return $this->xml;
    }

    /**
     * set the type definition from an xml structure
     * 
     * @param	string	$a_xml xml definition
     * @param	string	$a_failure_message (byref) variable for failure message
     * @return	boolean setting successful
     */
    public function setXML($a_xml, &$a_failure_message)
    {
        $doc = new DOMDocument('1.0');
        $doc->formatOutput = true;
        
        $title = null;
        $description = null;
        $template = null;

        try {
            $doc->loadXML($a_xml);
        }
        catch (Exception $e) {
            $a_failure_message = $e->getMessage();
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

        $tmp_fields = array();
        $fields = $interface->getElementsByTagName('field');
        foreach ($fields as $field)
        {
            /** @var DOMNode $field */
            $tmp = new ilExternalContentField();
            
            // basic properties
            $tmp->field_name = (string) $field->getAttribute('name');
            $tmp->field_type = (string) $field->getAttribute('type');
            
            // properties for input fields
            $tmp->required = $field->getAttribute('required');
            $tmp->size = (int) $field->getAttribute('size');
           	$tmp->rows = (int) $field->getAttribute('rows');
           	$tmp->cols = (int) $field->getAttribute('cols');
           	$tmp->richtext = (string) $field->getAttribute('richtext');
           	$tmp->default = (string) $field->getAttribute('default');

           	// appearance of input fields
           	$tmp->parentfield = (string) $field->getAttribute('parentfield');
            $tmp->parentvalue = (string) $field->getAttribute('parentvalue');
            $tmp->level = (string) ($field->getAttribute('level') ?: "object");
            
            // processing properties
           	$tmp->encoding = $field->getAttribute('encoding');
            $tmp->function = $field->getAttribute('function');

            // optional sub elements (field type specific)
            if ($title_element = $this->getDomChildByName($field, 'title'))
            {
            	$tmp->title = (string) $title_element->textContent;
            }
            if ($description_element = $this->getDomChildByName($field, 'description'))
            {
            	$tmp->description = (string) $description_element->textContent;
            }
            if ($template_element = $this->getDomChildByName($field, 'template'))
            {
            	$tmp->template = (string) $template_element->textContent;
            }
            
            // set options for radio fields
            $tmp->options = array();
            foreach ($field->getElementsByTagName('option') as $option)
            {
            	$opt = new ilExternalContentOption();
            	$opt->value = (string) $option->getAttribute('value');
            	$opt->title = (string) $this->getDomChildByName($option, 'title')->textContent;
            	$opt->description = (string) $this->getDomChildByName($option, 'description')->textContent;
            	$tmp->options[$opt->value] = $opt;
            }
            
            // set parameters for function fields
            $tmp->params = array();
            foreach ($field->getElementsByTagName('param') as $param)
            {
          		$tmp->params[(string) $param->getAttribute('name')] = (string) $param->textContent;
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
                case self::FIELDTYPE_RAWTEXT:
                case self::FIELDTYPE_TEXTAREA:
                case self::FIELDTYPE_PASSWORD:
                case self::FIELDTYPE_CHECKBOX:
                case self::FIELDTYPE_RADIO:
                case self::FIELDTYPE_HEADER:
                case self::FIELDTYPE_DESCRIPTION:
                case self::FIELDTYPE_SPECIAL:
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
        $this->fields = $tmp_fields;
        
        return true;
    }
    
    /**
     * get a DOM child element with a specific name
     * 
     * @param 	DOMNode		$a_node node
     * @param 	string		$a_name child name
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
        $query = 'SELECT * FROM xxco_data_types WHERE type_id = '
                . $this->db->quote($this->getTypeId(), 'integer');

        $res = $this->db->query($query);
        $row = $this->db->fetchAssoc($res);
        if ($row)
        {
            $this->type_id = $row['type_id'];
            $this->name= $row['type_name'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->availability = $row['availability'];
            $this->remarks = $row['remarks'];

           	if ($this->setXML($row['interface_xml'], $void))
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
    public function create()
    {
        $this->type_id = $this->db->nextId('xxco_data_types');
        $this->update();
    }

    /**
     * Update function
     */
    public function update()
    {
        $this->db->replace('xxco_data_types',
        	 array(
            	'type_id' => array('integer', $this->getTypeId())
             ),
             array(
	            'type_name' => array('text', $this->getName()),
	            'title' => array('text', $this->getTitle()),
	            'description' => array('clob', $this->getDescription()),
	            'availability' => array('integer', $this->getAvailability()),
	            'remarks' => array('clob', $this->getRemarks()),
             	'interface_xml' => array('clob', $this->getXML())
             )
        );
    }

    /**
     * Delete
     *
     * @access public
     */
    public function delete()
    {
        ilExternalContentPlugin::_deleteWebspaceDir("type", $this->getTypeId());

        $query = "DELETE FROM xxco_data_types " .
                "WHERE type_id = " . $this->db->quote($this->getTypeId(), 'integer');
        $this->db->manipulate($query);

        return true;
    }


    /**
     * Save an input value directly
     * @param string $a_field_name
     * @param string $a_field_value
     */
    public function saveInputValue($a_field_name, $a_field_value)
    {
        $this->db->replace('xxco_type_values', array(
            'type_id' => array('integer', $this->getTypeId()),
            'field_name' => array('text', $a_field_name)
                ), array(
            'field_value' => array('text', $a_field_value)
                )
        );
    }

    /**
     * Get array of input values
     */
    function getInputValues()
    {
        $query = 'SELECT * FROM xxco_type_values WHERE type_id = '
                . $this->db->quote($this->getTypeId(), 'integer');
        $res = $this->db->query($query);

        $values = array();
        while ($row = $this->db->fetchAssoc($res))
        {
            $values[$row['field_name']] = $row['field_value'];
        }
        return $values;
    }


    /**
     * add type specific input fields to a form
     *
     * @param object	$a_object form, property or radio option
     * @param array		$a_values (assoc) input values
     * @param string	$a_level configuration level ("type" or "object")
     * @param string	$a_parentfield parent field value
     * @param string	$a_parentvalue parent option value
     * @param int		$a_maxdepth maximum recursion depth
     */
    public function addFormElements($a_object, $a_values = array(),
    	$a_level = "object", $a_parentfield = '', $a_parentvalue = '',
    	$a_maxdepth = 3)
    {
    	// recursion end
    	if ($a_maxdepth == 0)
    	{
    		return;
    	}

		foreach ($this->getInputFields($a_level, $a_parentfield, $a_parentvalue) as $field)
		{
            $value = ($a_values['field_' . $field->field_name] ?? '') ?: $field->default;

			switch($field->field_type)
			{
			    case self::FIELDTYPE_HEADER:
			    	$item = new ilFormSectionHeaderGUI();
			    	$item->setTitle((string) $field->title);
			    	break;

			    case self::FIELDTYPE_DESCRIPTION:
		    		$item = new ilCustomInputGUI($field->title);
		    		$item->setHtml((string) nl2br($field->description));
			    	break;

				case self::FIELDTYPE_TEXT:
                    $item = new ilTextInputGUI($field->title, 'field_' . $field->field_name);
                    $item->setInfo($field->description);
                    $item->setRequired($field->required ? true : false);
                    $item->setSize($field->size);
                    $item->setValue((string) $value);
                    break;

                case self::FIELDTYPE_RAWTEXT:
					$item = new ilExternalContentRawtextInputGUI($field->title, 'field_' . $field->field_name);
					$item->setInfo($field->description);
					$item->setRequired($field->required ? true : false);
					$item->setSize($field->size);
			    	$item->setValue((string) $value);
					break;

				case self::FIELDTYPE_TEXTAREA:
					$item = new ilTextAreaInputGUI($field->title, 'field_' . $field->field_name);
					$item->setInfo($field->description);
					$item->setRequired($field->required ? true : false);
					$item->setUseRte($field->richtext ? true : false);
					$item->setRows($field->rows);
					$item->setCols($field->cols);
			    	$item->setValue((string) $value);
					break;

				case self::FIELDTYPE_PASSWORD:
					$item = new ilPasswordInputGUI($field->title, 'field_' . $field->field_name);
					$item->setInfo($field->description);
					$item->setRequired($field->required ? true : false);
					$item->setSkipSyntaxCheck(true);
			    	$item->setValue((string) $value);
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
			    	$item->setValue((string) $value);

					foreach ($field->options as $option)
					{
						$ropt = new ilRadioOption($option->title, $option->value);
						$ropt->setInfo($option->description);

						// add the sub items to the option
						$item->addOption($ropt);
						$this->addFormElements($ropt, $a_values, $a_level, $field->field_name, $option->value, $a_maxdepth - 1);
					}
					break;

                case self::FIELDTYPE_SPECIAL:
                    switch ($field->field_name) {
                        case self::FIELD_LTI_USER_DATA:
                            $data = ilExternalContentUserData::create($this->plugin);
                            $item = $data->getFormItem($field->title, $field->description, $value);
                            break;

                        default:
                            continue 3;
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
     * Get the values
     * @param ilPropertyFormGUI $a_form
     * @param string $a_level
     * @return array    field_name => field_value
     */
    public function getFormValues($a_form, $a_level = "object") {

        $values = array();
        foreach ($this->getInputFields($a_level) as $field)
        {
            if ($field->field_type == self::FIELDTYPE_SPECIAL) {
                if ($field->field_name == self::FIELD_LTI_USER_DATA) {
                    $value = ilExternalContentUserData::create($this->plugin)->getFormValue($a_form);
                    $values[$field->field_name] = $value ?: $field->default;
                }
            }
            else {
                $value = trim($a_form->getInput("field_" . $field->field_name));
                $values[$field->field_name] = $value ?: $field->default; 
            }
        }
        return $values;
    }



    /**
     * Get array of input fields for the type
     *
     * @var		mixed	$a_level level ("type" or "object")
     * @var		mixed	$a_parentfield parent field name or null
     * @var		mixed	$a_parentvalue parent field option or null
     * @return	array	list of field objects
     */
    public function getInputFields($a_level = 'object', $a_parentfield = null, $a_parentvalue = null)
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
    public function getFieldsAssoc()
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
    public function getPlaceholder($a_name)
    {
        return $this->placeholder_start . $a_name . $this->placeholder_end;
    }

    /**
     * get a language text
	 *
     * @param 	string		$a_langvar language variable
     * @return 	string		interface text
     */
    public function txt($a_langvar)
    {
    	return $this->plugin->txt($a_langvar);
    }

    /**
     * Get array of options for selecting the type
     *
     * @param	mixed		$a_availability required availability or null
     * @return	array		id => title
     */
    static function _getTypeOptions($a_availability = null)
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "SELECT * FROM xxco_data_types";
        if (isset($a_availability)) {
            $query .= " WHERE availability=" . $ilDB->quote($a_availability, 'integer');
        }
        $res = $ilDB->query($query);

        $options = array();
        while ($row = $ilDB->fetchAssoc($res))
        {
            $options[$row['type_id']] = $row['title'];
        }
        return $options;
    }

    /**
     * Get basic data array of all types (without field definitions)
     *
     * @param	boolean		$a_extended get extended data ('usages')
     * @param	mixed		$a_availability required availability or null
     * @return	array		array of assoc data arrays
     */
    static function _getTypesData($a_extended = false, $a_availability = null)
    {
        global $DIC;
        $ilDB = $DIC->database();

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
     * @var		integer		$a_type_id type_id
     * @return	integer		number of references
     */
    static function _countUntrashedUsages($a_type_id)
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "SELECT COUNT(*) untrashed FROM xxco_data_settings s"
                . " INNER JOIN object_reference r ON s.obj_id = r.obj_id"
                . " WHERE r.deleted IS NULL "
                . " AND s.type_id = " . $ilDB->quote($a_type_id, 'integer');

        $res = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($res);
        return $row['untrashed'];
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
        $ilDB = $DIC->database();

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

        $result = $ilDB->queryF($query,
            ['integer','text','text'],
            [$a_type_id, $a_field_name, $a_field_value]
        );

        $ref_ids = [];
        while ($row = $ilDB->fetchAssoc($result)) {
            $ref_ids[] = $row['ref_id'];
        }
        return $ref_ids;
    }
}