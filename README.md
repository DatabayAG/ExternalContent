# ILIAS External Content plugin

Copyright (c) 2016 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv2, see LICENSE

**Further maintenance can be offered by [Databay AG](https://www.databay.de).**

- Forum: http://www.ilias.de/docu/goto_docu_frm_3474_1946.html
- Bug Reports: http://www.ilias.de/mantis (Choose project "ILIAS plugins" and filter by category "External Content")

## Installation

When you download the Plugin as ZIP file from GitHub, please rename the extracted directory to *ExternalContent*
(remove the branch suffix, e.g. -master).

1. Copy the ExternalContent directory to your ILIAS installation at the followin path
(create subdirectories, if neccessary): Customizing/global/plugins/Services/Repository/RepositoryObject
2. Run `composer du` in the main directory of your ILIAS installation
3. Go to Administration > Extending ILIAS > Plugins
4. Choose action  "Update" for the ExternalContent plugin
5. Choose action  "Activate" for the ExternalContent plugin

## Server Configuration Notes

If you want to use the LTI outcome service with PHP-FPM behind an Apache web server, please add the following configuration
to your virtual host or directory configuration in Apache:

`SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1`

## Usage

See [Manual](docs/Manual.pdf) for details.

You may also try the [PCExternalContent](https://github.com/DatabayAG/PCExternalContent) plugin to embed contents with the ILIAS page editor.


## Update

Plugin versions for different ILIAS releases are provided in separate branches of this repository. See [Changelog](CHANGELOG.md) for Details.

When you update your plugin to ILIAS 8, you should change the XML of your LTI type definitions to get rid of the jQuery dependencies.

Search for:
````
$(document).ready(function()
````
and replace it with: 
````
document.addEventListener("DOMContentLoaded", function(event)
````




