<?php

require_once("Services/Init/classes/class.ilInitialisation.php");

/**
 * Extended ILIAS initialisation for result service
 * This is needed to initialize the access handler which is required by learning progress change event handlers
 */
class ilExternalContentInitialisation extends ilInitialisation
{
    public static function initILIAS()
    {
        parent::initILIAS();

        // needed to get $rbarcreview initialized
        parent::initAccessHandling();
    }
}