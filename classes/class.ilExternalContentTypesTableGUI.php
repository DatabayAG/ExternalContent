<?php
/**
 * Copyright (c) 2013 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg 
 * GPLv2, see LICENSE 
 */

require_once(__DIR__ . '/trait.ilExternalContentGUIBase.php');
require_once(__DIR__ . '/class.ilExternalContentPlugin.php');
require_once(__DIR__ . '/class.ilExternalContentType.php');

/**
 * External Content plugin: content types table GUI
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 */ 
class ilExternalContentTypesTableGUI extends ilTable2GUI {

    use ilExternalContentGUIBase;

    /** @var ilExternalContentPlugin  */
    protected $plugin;

    /**
     * Constructor
     * 
     * @param 	object		parent object
     * @param 	string		parent command
     */
    function __construct($a_parent_obj, $a_parent_cmd = '', $a_template_context = '') 
    {
    	// this uses the cached plugin object
		$this->plugin = ilExternalContentPlugin::getInstance();

		parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
    }

    /**
     * Init the table with some configuration
     * 
     * 
     * @access public
     */
    public function init($a_parent_obj) 
    {
        $this->addColumn($this->lng->txt('id'), 'type_id', '10%');
        $this->addColumn($this->lng->txt('name'), 'type_name', '10%');
        $this->addColumn($this->lng->txt('title'), 'title', '30%');
        $this->addColumn($this->lng->txt('rep_robj_xxco_type_availability'), 'availability', '20%');
        $this->addColumn($this->lng->txt('rep_robj_xxco_untrashed_usages'), 'usages', '10%');
        $this->addColumn($this->lng->txt('actions'), '', '20%');
        $this->setEnableHeader(true);
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
        $this->addCommandButton('createType', $this->lng->txt('rep_robj_xxco_create_type'));

        $this->setRowTemplate('tpl.types_row.html', 'Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent');
        $this->getMyDataFromDb();
    }

    /**
     * Get data and put it into an array
     */
    function getMyDataFromDb() 
    {
        // get types data with usage info
        $data = ilExternalContentType::_getTypesData(true);
        $this->setDefaultOrderField('type_id');
        $this->setDefaultOrderDirection('asc');
        $this->setData($data);
    }

    /**
     * Fill a single data row.
     */
    protected function fillRow($a_set) 
    {
        $this->ctrl->setParameter($this->parent_obj, 'type_id', $a_set['type_id']);

        $this->tpl->setVariable('TXT_ID', $a_set['type_id']);
        $this->tpl->setVariable('TXT_NAME', $a_set['type_name']);
        $this->tpl->setVariable('TXT_TITLE', $a_set['title']);
        $this->tpl->setVariable('TXT_AVAILABILITY', $this->lng->txt('rep_robj_xxco_availability_' . $a_set['availability']));
        $this->tpl->setVariable('TXT_USAGES', (int) $a_set['usages']);

        $this->tpl->setVariable('TXT_EDIT', $this->lng->txt('edit'));
        $this->tpl->setVariable('LINK_EDIT', $this->ctrl->getLinkTarget($this->parent_obj, 'editType'));

        if ($a_set['usages'] == 0) {
            $this->tpl->setVariable('TXT_DELETE', $this->lng->txt('delete'));
            $this->tpl->setVariable('LINK_DELETE', $this->ctrl->getLinkTarget($this->parent_obj, 'deleteType'));
        }
    }

}