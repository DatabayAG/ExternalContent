<?php
/**
 * Copyright (c) 2013 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg 
 * GPLv2, see LICENSE 
 */

/**
 * External Content plugin: event log script
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 */ 

// fim: [debug] optionally set error before initialisation
error_reporting (E_ALL);
ini_set("display_errors","on");
// fim.

chdir("../../../../../../../");

// Avoid redirection to start screen
// (see ilInitialisation::InitILIAS for details)
$_GET["baseClass"] = "ilStartUpGUI";

require_once "./include/inc.header.php";
require_once "./Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/classes/class.ilExternalContentLogGUI.php";

$log_obj = new ilExternalContentLog();
//$log_obj->performCommand();


?>