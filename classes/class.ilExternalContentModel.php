<?php
/**
 * Copyright (c) 2013 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg 
 * GPLv2, see LICENSE 
 */

require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/classes/class.ilExternalContentType.php");

/**
 * External Content plugin: model for type definition
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 */ 
class ilExternalContentModel
{
	static $builtin_path = "./Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/models";
	
	
	/**
	 * get a list of all available models for a new type definition
	 * 
	 * @return	array	array( array (	'name'	=> string
	 * 									'title' => string
	 * 								  	'description' => string))
	 */
	static function _getModelsList()
	{	
		$models = array();
		$path = self::$builtin_path;
		if ($dp = @opendir($path))
		{
			while (($file = readdir($dp)) != false)
			{
				if (is_dir($path."/".$file) && $file != "." && $file != ".." && $file != "CVS"
					&& $file != ".svn")
				{
					if (is_file($path."/".$file."/interface.xml"))
					{
						$message = "";
						$xml = file_get_contents($path."/".$file."/interface.xml");
						$tmp_type = new ilExternalContentType();
						if ($tmp_type->setXML($xml, $message))
						{
							$model = array (
								'name' => $file,
								'title' => $tmp_type->getTitle(),
								'description' => $tmp_type->getDescription()
							);

							array_push($models, $model);
						}
					}
				}
			} 
		}
	
		return $models;
	}
	
	
	/**
	 * Create a new type from a model
	 * 
	 * @param string	model name (= sub directory of models)
	 * @param string	title of the new type
	 * 
	 * @return int		type id
	 */
	static function _createTypeFromModel($a_model_name, $a_type_name)
	{
		if ($xml = file_get_contents(self::$builtin_path."/".$a_model_name."/interface.xml"))
		{
			$type = new ilExternalContentType();
			if ($type->setXML($xml, $message))
			{
				$type->setName($a_type_name);
				$type->create();
				return $type->getTypeId();	
			}
		}
	}
	
	
}