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

chdir(__DIR__ . "/../../../../../../../../");

/** @noRector */
require_once("vendor/composer/vendor/autoload.php");

// most appropriate context, user and ILIAS_HTTP_PATH is set
ilContext::init(ilContext::CONTEXT_RSS_AUTH);
ilInitialisation::initILIAS();

$service = new ilExternalContentResultService();
$service->handleRequest();
