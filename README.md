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

Usage
-----

See [Manual](docs/Manual.pdf) for details.

Version History
===============

* All versions for ILIAS 5.1 and higher are maintained in GitHub: https://github.com/ilifau/ExternalContent
* Former versions for ILIAS 5.0 and lower are maintained in ILIAS SVN: http://svn.ilias.de/svn/ilias/branches/fau/plugins

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

Version 1.4.2 (2016-02-11)
* http://svn.ilias.de/svn/ilias/branches/fau/plugins/ExternalContent-1.4.x
* stable version for ILIAS 5.0 with learning progress and LTI 1.1
* added Dutch language (thanks to Rick de Koster from L&M Software bv)
* prettyfied content page for LTI type models
* fixed mantis #16234 (ignored offline setting)
* fixed error with unavailable types

Version 1.3.8 (2016-02-11)
* http://svn.ilias.de/svn/ilias/branches/fau/plugins/ExternalContent-1.2.x
* stable version for ILIAS 4.4 with learning progress and LTI 1.1
* supported cloning of objects
* bugfix for encoding path
* bugfix for call of info screen
* updated manual
* fixed mantis #16234 (ignored offline setting)
* fixed error with unavailable types

Version 1.1.1 (2013-08-26)
* http://svn.ilias.de/svn/ilias/branches/fau/plugins/ExternalContent-1.1.x
* stable version for ILIAS 4.3
* fixed ignored online setting
* LTI 1.0 support
* Provision of type models
* Separating Type settings and XML definition
* Suport defined fields in type settings
* More ILIAS fields
* More input field types
* Calculated fields and functions
* Icon handling on type and object level
