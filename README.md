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

* All versions for ILIAS 5.1 and higher are maintained in GitHub: https://github.com/ilifau/assAccountingQuestion
* Former versions for ILIAS 5.0 and lower are maintained in ILIAS SVN: http://svn.ilias.de/svn/ilias/branches/fau/plugins

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
