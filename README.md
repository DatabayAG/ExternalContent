ILIAS External Content plugin
=============================

Copyright (c) 2016 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv2, see LICENSE

- Author:   Fred Neumann <fred.neumann@ili.fau.de>, Jesus Copado <jesus.copado@ili.fau.de>
- Forum: http://www.ilias.de/docu/goto_docu_frm_3474_1946.html
- Bug Reports: http://www.ilias.de/mantis (Choose project "ILIAS plugins" and filter by category "External Content")


Installation
------------

When you download the Plugin as ZIP file from GitHub, please rename the extracted directory to *ExternalContent*
(remove the branch suffix, e.g. -master).

1. Copy the ExternalContent directory to your ILIAS installation at the followin path
(create subdirectories, if neccessary): Customizing/global/plugins/Services/Repository/RepositoryObject
2. Go to Administration > Plugins
3. Choose action  "Update" for the ExternalContent plugin
4. Choose action  "Activate" for the ExternalContent plugin

Server Configuration Notes
--------------------------

If you want to use the LTI outcome service with PHP-FPM behind an Apache web server, please add the following configuration
to your virtual host or directory configuration in Apache:

`SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1`

Usage
-----

See [Manual](docs/Manual.pdf) for details.

Version History
===============

* All versions for ILIAS 5.1 and higher are maintained in GitHub: https://github.com/ilifau/ExternalContent
* Versions up to 1.5.x are in the 'master' branch
* Version 1.6 for ILIAS 5.3 and ILIAS 5.4 is in the 'master-ilias53' branch

Version 1.6.3 (2021-03-30)
* Support for ILIAS 6

Version 1.6.2 (2020-11-22)
* Added user profile data and user defined profile data
* Added selector for provision of profile data via LIT
* Added autostart options to LTI
* Added recognition of goto link suffixes
* Updated the manual

Version 1.6.1 (2019-05-10)
* Support for ILIAS 5.4

Version 1.6.0 (2018-09-19)
* Support for ILIAS 5.3
* Drop support for ILIAS 5.1 and 5.2

Version 1.5.8 (2018-02-19)
* support for ILIAS 5.3
* fixed default launch target and launch_presentation_return_url in type models
* removed outdated event logging
Note:
Changes to the type models do not affect existing content types. You may create new types based on the changed models.
To change an existing type, please copy the XML definiton of a new type to the XML definition of your existing type.

Version 1.5.7 (2018-01-31)
* fixed wrong launch_presentation_document_target in lti type models (1/2018)
* support contexts outside courses or groups in lti type models (8/2017)
* changed links to https in youtube type model (8/2017)
* changed http to https in demo urls of lti type models (7/2017)
* changed name of settings tab (7/2017)
* skip syntax check for password (5/2017)
* fixed icon upload (11/2016)
Note:
Changes to the type models do not affect existing content types. You may create new types based on the changed models.
To change an existing type, please copy the XML definiton of a new type to the XML definition of your existing type.


Version 1.5.6 (2016-11-21)
* Support for ILIAS 5.2 and PHP7 (beta). Thanks to 'xus' for the pull request.
* Changed 'goto' access check of permanent links to default for plugins. Now the 'read' permission is needed instead of 'visible'.
  This prevents the necessity of a different branch for ILIAS 5.2 due to the changed declaration of _checkGoto()

Version 1.5.5 (2016-09-01)
* changed variable ILIAS_CONTACT_EMAIL to the Field "E-Mail" in Administration > General Settings > Contact Information
  (The former "Feedback Recipient" ist not longer changeable in ILIAS 5.1)

Version 1.5.4 (2016-07-01)
* added two new functions splitToArray and mergeArrays
* added a textarea for custom parameters to the LTI type models

Version 1.5.3 (2016-06-14)
* fixed signature check in outcome service
* fixed object creation
* fixed ilLink call
* fixed missing access handler for lp change event in result service
* fixed mantis #18529 (wrong permission check for offline)

Version 1.5.2 (2016-03-07)
* support of timings

Version 1.5.1 (2016-02-11)
* stable version for ILIAS 5.1
* added SVG for custom icon (other icons are still supported with adjusted sizes)
* fixed mantis #16234 (ignored offline setting)
* fixed error with unavailable types

