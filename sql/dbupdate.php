<#1>
<?php
/**
 * Copyright (c) 2015 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg 
 * GPLv2, see LICENSE 
 */

/**
 * External Content plugin: database update script
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 */ 

/**
 * Type definitions
 */
if(!$ilDB->tableExists('xxco_data_types'))
{
  $types = array(
      'type_id' => array(
          'type' => 'integer',
          'length' => 4,
          'notnull' => true,
          'default' => 0
      ),
      'type_name' => array(
          'type' => 'text',
          'length' => 32
      ),
      'title' => array(
          'type' => 'text',
          'length' => 255
      ),
      'description' => array(
          'type' => 'text',
          'length' => 4000
      ),
      'availability' => array(
          'type' => 'integer',
          'length' => 4,
          'notnull' => true,
          'default' => 1
      ),
      'remarks' => array(
          'type' => 'text',
          'length' => 4000
      ),
      'interface_xml' => array(
           'type' => 'clob'
      ),
      'time_to_delete' => array(
          'type' => 'integer',
          'length' => 4
      ),
      'use_logs' => array(
          'type' => 'text',
          'length' => 32
      ),
      'use_learning_progress' => array(
          'type' => 'text',
          'length' => 32
      )
      
  );
  $ilDB->createTable("xxco_data_types", $types);
  $ilDB->addPrimaryKey("xxco_data_types", array("type_id"));
  $ilDB->createSequence("xxco_data_types");
}
?>
<#2>
<?php 
/**
 * originally created the fields table
 * (not needed anymore - field definitions are stored in the interface_xml field of the types table) 
 */ 
?>
<#3>
<?php
if(!$ilDB->tableExists('xxco_data_settings'))
{
    $settings = array(
        'obj_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0
        ),
        'type_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0
        ),
        'instructions' => array(
          'type' => 'text',
          'length' => 4000
        ),
        'availability_type' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0
        ),
        'meta_data_xml' => array(
            'type' => 'clob'
        )
    );

    $ilDB->createTable("xxco_data_settings", $settings);
    $ilDB->addPrimaryKey("xxco_data_settings", array("obj_id"));
}
?>
<#4>
<?php
if(!$ilDB->tableExists('xxco_data_values'))
{
    $values = array(
        'obj_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0
        ),
        'field_name' => array(
            'type' => 'text',
            'length' => 255,
            'notnull' => true,
            'default' => ''
        ),
        'field_value' => array(
            'type' => 'text',
            'length' => 255,
            'notnull' => false,
        )
    );
    $ilDB->createTable("xxco_data_values", $values);
    $ilDB->addPrimaryKey("xxco_data_values", array("obj_id", "field_name"));
}
?>
<#5>
<?php
if(!$ilDB->tableExists('xxco_data_token'))
{
    $token = array(
      'token' => array(
          'type' => 'text',
          'length' => 255,
          'notnull' => true,
          'default' => 0
      ),
      'time' => array(
          'type' => 'timestamp',
          'notnull' => true,
          'default' => ''
      )
    );
    $ilDB->createTable("xxco_data_token", $token);
    $ilDB->addPrimaryKey("xxco_data_token", array("token", "time"));
}
?>
<#6>
<?php
/**
 * originally created the icons table
 * (not needed anymore) 
 */ 
?>
<#7>
<?php
    ilUtil::makeDirParents(ilUtil::getWebspaceDir().'/xxco/cache');
?>
<#8>
<?php
if(!$ilDB->tableExists('xxco_data_log'))
{
    $fields = array(
        'obj_id' => array(
            'type' => 'integer',
            'length' => 8,
            'notnull' => true,
            'default' => 0
        ),
        'ref_id' => array(
            'type' => 'integer',
            'length' => 8
        ),
        'session_id' => array(
            'type' => 'text',
            'length' => 255,
            'notnull' => true,
            'default' => ' '
        ),
        'user_id' => array(
            'type' => 'integer',
            'length' => 8
        ),
        'log_time' => array(
            'type' => 'timestamp',
            'notnull' => false
        ),
        'call_time' => array(
            'type' => 'timestamp',
            'notnull' => false
        ),
        'event_type' => array(
            'type' => 'text',
            'length' => 255,
            'notnull' => false
        ),
        'event_subtype' => array(
            'type' => 'text',
            'length' => 255,
            'notnull' => false
        ),
        'event_integer' => array(
            'type' => 'integer',
            'length' => 4,
        ),
        'event_text' => array(
            'type' => 'clob'
        )
    );

    $ilDB->createTable("xxco_data_log", $fields);
    $ilDB->addPrimaryKey("xxco_data_log", array("obj_id"));
    $ilDB->createSequence("xxco_data_log");
}
?>
<#9>
<?php
/**
 * add the interface XML to the types table
 */
if (!$ilDB->tableColumnExists('xxco_data_types', 'interface_xml'))
{
     $ilDB->addTableColumn('xxco_data_types', 'interface_xml', array('type' => 'clob'));	 
}
?>
<#10>
<?php 
/**
 * create the interface xml for existing types 
 */
if($ilDB->tableExists('xxco_data_fields'))
{
	$type_query = "SELECT * FROM xxco_data_types";
	$type_result = $ilDB->query($type_query);
	
	while ($type = $ilDB->fetchObject($type_result))
	{
	        $doc = new DOMDocument('1.0');
	        $doc->formatOutput = true;
	
	        $interface = $doc->appendChild(new DOMElement('interface'));
	        $interface->setAttribute('type', $type->launch_type);
	        $interface->appendChild(new DOMElement('title', $type->title));
	        $interface->appendChild(new DOMElement('description', $type->description));
	
	        $template = $interface->appendChild(new DOMElement('template'));
	        $template->appendChild($doc->createCDATASection($type->template));
	
	        if ($type->meta_data_url)
	        {
	        $metasource = $interface->appendChild(new DOMElement('metasource'));
	        $metasource->appendChild($doc->createCDATASection($type->meta_data_url));
	        }
	
	        $fields = $interface->appendChild(new DOMElement('fields'));
	        
	        $fields_query = "SELECT * FROM xxco_data_fields"
	        			. " WHERE type_id = ". $ilDB->quote($type->type_id)
	        			. " ORDER BY position";
	        $fields_result = $ilDB->query($fields_query);    
	
	        while ($fdata = $ilDB->fetchObject($fields_result))
	        {
	            $field = $fields->appendChild(new DOMElement('field'));
	            $field->setAttribute('name', $fdata->field_name);
	            $field->setAttribute('type', $fdata->field_type);
	            
			if ($fdata->title)
			{
	            $field->appendChild(new DOMElement('title', $fdata->title));
			}
			if ($fdata->description)
			{
	            $field->appendChild(new DOMElement('description', $fdata->description));
			}
			
			if ($fdata->field_type == "calculated")
			{
				$field->setAttribute('function', $fdata->encoding);
				
				if (is_array($params = unserialize($fdata->template)))
				{
					foreach ($params as $name => $value)
					{
						$param = $field->appendChild(new DOMElement('param', $value));
						$param->setAttribute('name', $name);
					}
				}
			}
			else
			{
	            if ($fdata->encoding)
	            {
	            	$field->setAttribute('encoding', $fdata->encoding);
	            }
				
				if ($fdata->template)
				{
	            	$template = $field->appendChild(new DOMElement('template'));
	            	$template->appendChild($doc->createCDATASection($fdata->template));
				}
			}
	        }
	
	        $ilDB->update("xxco_data_types", 
	        array(
	        "interface_xml" =>        array("clob", $doc->saveXML())),
	        array(
	        "type_id" =>       array("integer", $type->type_id))
	   );
	}
}
?>
<#11>
<?php
/**
 * drop fields table
 * (these data are now saved in the interface_xml field)
 */
if ($ilDB->tableExists('xxco_data_fields'))
{
	$ilDB->dropTable('xxco_data_fields');
}
?>
<#12>
<?php
/**
 * cleanup types table
 * These data are now saved in the interface_xml field
 */
if($ilDB->tableColumnExists('xxco_data_types', 'template'))
{
	$ilDB->dropTableColumn('xxco_data_types', 'template');
}
if($ilDB->tableColumnExists('xxco_data_types', 'launch_type'))
{
	$ilDB->dropTableColumn('xxco_data_types', 'launch_type');
}
if($ilDB->tableColumnExists('xxco_data_types', 'meta_data_url'))
{
	$ilDB->dropTableColumn('xxco_data_types', 'meta_data_url');
}
?>
<#13>
<?php
/**
 * drop icons table
 * (existance of icons is checked directly in the file system)
 */
if ($ilDB->tableExists('xxco_data_icon'))
{
	$ilDB->dropTable('xxco_data_icon');
}
?>
<#14>
<?php
/**
 * add the table for type input values
 */
if(!$ilDB->tableExists('xxco_type_values'))
{
    $values = array(
        'type_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0
        ),
        'field_name' => array(
            'type' => 'text',
            'length' => 255,
            'notnull' => true,
            'default' => ''
        ),
        'field_value' => array(
            'type' => 'text',
            'length' => 255,
            'notnull' => false,
        )
    );
    $ilDB->createTable("xxco_type_values", $values);
    $ilDB->addPrimaryKey("xxco_type_values", array("type_id", "field_name"));
}
?>
<#15>
<?php
/**
 * change clob fields to text
 * (for faster queries)
 */
$ilDB->modifyTableColumn('xxco_data_types', 'description', array('type' => 'text', 'length' => 4000));
$ilDB->modifyTableColumn('xxco_data_types', 'remarks', array('type' => 'text', 'length' => 4000));
$ilDB->modifyTableColumn('xxco_data_settings', 'instructions', array('type' => 'text', 'length' => 4000));
?>
<#16>
<?php
/**
 * change fields for input values
 * (allow longer inputs)
 */
$ilDB->modifyTableColumn('xxco_data_values', 'field_value', array('type' => 'text', 'length' => 4000));
$ilDB->modifyTableColumn('xxco_type_values', 'field_value', array('type' => 'text', 'length' => 4000));
?>
<#17>
<?php
/**
 * This step is a placeholder for 1.1.x hotfixes
 */
?>
<#18>
<?php
/**
 * This step is a placeholder for 1.1.x hotfixes
 */
?>
<#19>
<?php
/**
 * This step is a placeholder for 1.1.x hotfixes
 */
 ?>
<#20>
<?php
/**
 * add the table for type input values
 */
if(!$ilDB->tableExists('xxco_results'))
{
    $values = array(
        'id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
        ),
        'obj_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
        ),
        'usr_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
        ),
        'result' => array(
            'type' => 'float',
            'notnull' => false,
        ),
    );
    $ilDB->createTable("xxco_results", $values);
    $ilDB->addPrimaryKey("xxco_results", array("id"));
    $ilDB->createSequence("xxco_results");
    $ilDB->addIndex("xxco_results",array("obj_id","usr_id"),'i1');
}
?>
<#21>
<?php
    /**
    * add the learning progress mode
    */
    if (!$ilDB->tableColumnExists('xxco_data_settings', 'lp_mode'))
    {
        $ilDB->addTableColumn('xxco_data_settings', 'lp_mode', array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0
        ));
    }

    /**
     * add the learning progress mode
     */
    if (!$ilDB->tableColumnExists('xxco_data_settings', 'lp_threshold'))
    {
        $ilDB->addTableColumn('xxco_data_settings', 'lp_threshold', array(
            'type' => 'float',
            'notnull' => true,
            'default' => 0.5
        ));
    }

?>
<#22>
<?php

    /**
     * Check whether type exists in object data, if not, create the type
     * The type is normally created at plugin activation, see ilRepositoryObjectPlugin::beforeActivation()
     */
    $set = $ilDB->query("SELECT obj_id FROM object_data WHERE type='typ' AND title = 'xxco'");
    if ($rec = $ilDB->fetchAssoc($set))
    {
        $typ_id = $rec["obj_id"];
    }
    else
    {
        $typ_id = $ilDB->nextId("object_data");
        $ilDB->manipulate("INSERT INTO object_data ".
            "(obj_id, type, title, description, owner, create_date, last_update) VALUES (".
            $ilDB->quote($typ_id, "integer").",".
            $ilDB->quote("typ", "text").",".
            $ilDB->quote("xxco", "text").",".
            $ilDB->quote("Plugin ExternalContent", "text").",".
            $ilDB->quote(-1, "integer").",".
            $ilDB->quote(ilUtil::now(), "timestamp").",".
            $ilDB->quote(ilUtil::now(), "timestamp").
            ")");
    }

    /**
     * Add new RBAC operations
     */
    $operations = array('edit_learning_progress');
    foreach ($operations as $operation)
    {
        $query = "SELECT ops_id FROM rbac_operations WHERE operation = ".$ilDB->quote($operation, 'text');
        $res = $ilDB->query($query);
        $row = $ilDB->fetchObject($res);
        $ops_id = $row->ops_id;

        $query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ("
            .$ilDB->quote($typ_id, 'integer').","
            .$ilDB->quote($ops_id, 'integer').")";
        $ilDB->manipulate($query);
    }

?>
<#23>
<?php
/**
 * This step is a placeholder for 1.3.x hotfixes
 */
?>
<#24>
<?php
/**
 * This step is a placeholder for 1.3.x hotfixes
 */
?>
<#25>
<?php
/**
 * This step is a placeholder for 1.3.x hotfixes
 */
 ?>
<#26>
<?php
    $set = $ilDB->query("SELECT obj_id FROM object_data WHERE type='typ' AND title = 'xxco'");
    $rec = $ilDB->fetchAssoc($set);
    $typ_id = $rec["obj_id"];

    /**
    * Add new RBAC operations
    */
    $operations = array('read_learning_progress');
    foreach ($operations as $operation)
    {
        $query = "SELECT ops_id FROM rbac_operations WHERE operation = ".$ilDB->quote($operation, 'text');
        $res = $ilDB->query($query);
        $row = $ilDB->fetchObject($res);
        $ops_id = $row->ops_id;

        $query = "INSERT INTO rbac_ta (typ_id, ops_id) VALUES ("
        .$ilDB->quote($typ_id, 'integer').","
        .$ilDB->quote($ops_id, 'integer').")";
        $ilDB->manipulate($query);
    }

?>
<#27>
<?php
    if($ilDB->tableExists('xxco_data_log'))
    {
        $ilDB->dropSequence('xxco_data_log');
        $ilDB->dropTable('xxco_data_log');
    }
?>