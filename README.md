ILIAS External Content plugin
=============================

Copyright (c) 2016 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv2, see LICENSE

**Further maintenance can be offered by [Databay AG](https://www.databay.de).**

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
5. Choose action  "Activate" for the ExternalContent plugin

Server Configuration Notes
--------------------------

If you want to use the LTI outcome service with PHP-FPM behind an Apache web server, please add the following configuration
to your virtual host or directory configuration in Apache:

`SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1`

Usage
-----

See [Manual](docs/Manual.pdf) for details.

You may also try the [PCExternalContent](https://github.com/DatabayAG/PCExternalContent) plugin to embed contents with the ILIAS page editor.


Update
------

If you update your plugin to ILIAS 8, you should change the XML of your LTI type definitions to get rid if the jQuery dependencies.

See here, how this is done in the included type models:
https://github.com/DatabayAG/ExternalContent/commit/9d21b2bb64be2c5020262cfaf1df9d53364fc89f#diff-cec2d9d3b80db0ab89abc4421b6ff87838bb346e2888d8a60a431eb3280b47a0


Version History
===============

Plugin versions for different ILIAS releases are provided in separate branches of this repository.

Version 1.8.2 (2024-09-05)
* Fixed update of learning progress

Version 1.8.1 (2023-09-13)
* Support for ILIAS 8
* Used FileSystem service for icons
* Dropped support for PNG icons (only SVG allowed like in other ILIAS objects)
* Removed jQuery dependency from type models
* Changed example test service url in type models

