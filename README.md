ILIAS External Content plugin
=============================

Copyright (c) 2016 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv2, see LICENSE

**Further maintenance is provided by [Databay AG](https://www.databay.de).**

- Forum: http://www.ilias.de/docu/goto_docu_frm_3474_1946.html
- Bug Reports: http://www.ilias.de/mantis (Choose project "ILIAS plugins" and filter by category "External Content")


Installation
------------

When you download the Plugin as ZIP file from GitHub, please rename the extracted directory to *ExternalContent*
(remove the branch suffix, e.g. -master).

1. Copy the ExternalContent directory to your ILIAS installation at the followin path
(create subdirectories, if neccessary): Customizing/global/plugins/Services/Repository/RepositoryObject
2. Run `composer du` in the main directory of your ILIAS installation
3. Go to Administration > Extending ILIAS > Plugins
4. Choose action  "Update" for the ExternalContent plugin
6. Choose action  "Activate" for the ExternalContent plugin

Server Configuration Notes
--------------------------

If you want to use the LTI outcome service with PHP-FPM behind an Apache web server, please add the following configuration
to your virtual host or directory configuration in Apache:

`SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1`

Usage
-----

See [Manual](docs/Manual.pdf) for details.

You may also try the [PCExternalContent](https://github.com/DatabayAG/PCExternalContent) plugin to embed contents with the ILIAS page editor.

Version History
===============

Plugin versions for different ILIAS releases are provided in separate branches of this repository.

Version 1.7.1 (2022-01-11)
* Support for ILIAS 7 and PHP 7.4
* Refactored for page contents with the PCExternalContent plugin
* Added url_rfc3986 encoding
* Used OAuth lib from Modules/LTIConsumer to avoid autoload conflicts

