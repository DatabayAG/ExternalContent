<?php

require_once("Services/Init/classes/class.ilInitialisation.php");

/**
 * Extended ILIAS initialisation for result service
 * This is needed for additional initialisations required by learning progress change event handlers
 */
class ilExternalContentInitialisation extends ilInitialisation
{
    public static function initILIAS()
    {
        parent::initILIAS();

        // needed to get $rbarcreview initialized
		$main_version = substr(ILIAS_VERSION_NUMERIC, 0,3);

		if ($main_version == '5.1' || $main_version == '5.2')
		{
			// needed to get $rbarcreview initialized
			parent::initAccessHandling();
		}
		else
		{
			// fill $DIC['ilUser'] needed by the course event handler
			// this also initializes the access handling
			parent::initUser();
		}
    }
}