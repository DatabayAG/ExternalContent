<?php
/**
 * Copyright (c) 2015 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg
 * GPLv2, see LICENSE 
 */

/**
 * External Content plugin: LIS Outcome service script
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @version $Id$
 */ 

// fim: [debug] optionally set error before initialisation
// error_reporting (E_ALL);
// ini_set("display_errors","on");
// fim.

chdir("../../../../../../../");

// this should bring us all session data of the desired client
// @see feed.php
if (isset($_GET["client_id"]))
{
    $cookie_domain = $_SERVER['SERVER_NAME'];
    $cookie_path = dirname( $_SERVER['PHP_SELF'] );

    /* if ilias is called directly within the docroot $cookie_path
    is set to '/' expecting on servers running under windows..
    here it is set to '\'.
    in both cases a further '/' won't be appended due to the following regex
    */
    $cookie_path .= (!preg_match("/[\/|\\\\]$/", $cookie_path)) ? "/" : "";

    if($cookie_path == "\\") $cookie_path = '/';

    $cookie_domain = ''; // Temporary Fix

    setcookie("ilClientId", $_GET["client_id"], 0, $cookie_path, $cookie_domain);

    $_COOKIE["ilClientId"] = $_GET["client_id"];
}

// REST context has http and client but no user, templates, html or redirects
require_once "Services/Context/classes/class.ilContext.php";
ilContext::init(ilContext::CONTEXT_REST);

require_once(__DIR__."/classes/class.ilExternalContentInitialisation.php");
ilExternalContentInitialisation::initILIAS();

require_once (__DIR__ ."/classes/class.ilExternalContentResultService.php");
$service = new ilExternalContentResultService;
$service->handleRequest();

?>
