<?php
/**
 * Copyright (c) 2013 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg 
 * GPLv2, see LICENSE 
 */

/**
 * External Contentplugin: logging table GUI
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 */
require_once("./Services/Table/classes/class.ilTable2GUI.php");

class ilExternalContentLogTableGUI extends ilTable2GUI {
    
    public $row_data = array();
    
    function __construct($a_parent_obj, $a_parent_cmd) {
        parent::__construct($a_parent_obj, $a_parent_cmd);
    }

    /* Init */

    public function init($a_parent_obj, $a_obj_id, $a_usr_id, $a_xxco_type, $a_date_less, $a_date_more) {

        global $ilCtrl, $lng;

        $this->addCommandButton('excelExport', $lng->txt('rep_robj_xxco_export_excel'));

        $this->addColumn('', 'f', 1);
        $this->addColumn($lng->txt("rep_robj_xxco_type_obj"), "type_name", "5%");
        $this->addColumn($lng->txt("rep_robj_xxco_event_id"), "event_id", "5%");
        $this->addColumn($lng->txt("rep_robj_xxco_ref_id"), "ref_id", "20%");
        $this->addColumn($lng->txt("rep_robj_xxco_session_id"), "session_id", "10%");
        $this->addColumn($lng->txt("rep_robj_xxco_user"), "user_id", "20%");
        $this->addColumn($lng->txt("rep_robj_xxco_time"), "log_time", "10%");
        $this->addColumn($lng->txt("rep_robj_xxco_call_time"), "call_time", "10%");
        $this->addColumn($lng->txt("rep_robj_xxco_type"), "event_type", "10%");
        $this->addColumn($lng->txt("rep_robj_xxco_subtype"), "event_subtype", "10%");
        $this->addColumn($lng->txt("rep_robj_xxco_integer"), "event_integer", "5%");
        $this->addColumn($lng->txt("rep_robj_xxco_text"), "vent_text", "40%");

        $this->setTitle($lng->txt("rep_robj_xxco_view_log"));
        $this->setNoEntriesText($lng->txt("rep_robj_xxco_no_events_found"));
        $this->setEnableHeader(true);
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
        $this->setSelectAllCheckbox("event_id");
        $this->addMultiCommand("deleteEntries", $lng->txt("delete"));

        $this->setRowTemplate("tpl.xxco_log_row.html", "Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent");

        $this->getResults($a_obj_id, $a_usr_id, $a_xxco_type, $a_date_less, $a_date_more);

        return $this;
    }

    /**
     * Get data and put it into an array
     */
    function getResults($a_obj_id, $a_usr_id, $a_xxco_type, $a_date_less, $a_date_more) {

        require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/classes/class.ilExternalContentLog.php');
        $results = ilExternalContentLog::_getLogEntries($a_obj_id, $a_usr_id, $a_xxco_type, $a_date_less, $a_date_more);
        $data = array();
        $names = array();
        foreach ($results as $row) {
            
            if (!$names[$row["usr_id"]]) {
                $name = ilObjUser::_lookupName($row["user_id"]);
                $row["user_id"] = $name["lastname"] . ", " . $name["firstname"];
            }
            

            $data[] = $row;
        }
        
        $this->row_data = $data;
        
        
        $this->setDefaultOrderField("col_date");
        $this->setDefaultOrderDirection("asc");
        $this->setData($data);
    }

    /**
     * Fill a single data row.
     */
    protected function fillRow($a_set) {

        $this->tpl->setVariable("VAL_ID", $a_set["obj_id"]);
        $this->tpl->setVariable("TXT_TYPE_NAME", $a_set["type_name"]);
        $this->tpl->setVariable("TXT_EVENT_ID", $a_set["obj_id"]);
        $this->tpl->setVariable("TXT_REF_ID", $a_set["ref_id"]);
        $this->tpl->setVariable("TXT_SESSION_ID", $a_set["session_id"]);
        $this->tpl->setVariable("TXT_USER", $a_set["user_id"]);
        $this->tpl->setVariable("TXT_LOG_TIME", $a_set["log_time"]);
        $this->tpl->setVariable("TXT_CALL_TIME", $a_set["call_time"]);
        $this->tpl->setVariable("TXT_EVENT_TYPE", $a_set["event_type"]);
        $this->tpl->setVariable("TXT_EVENT_SUBTYPE", $a_set["event_subtype"]);
        $this->tpl->setVariable("TXT_EVENT_INTEGER", $a_set["event_integer"]);
        $this->tpl->setVariable("TXT_EVENT_TEXT", $a_set["event_text"]);
        
    }

    /**
     * Export and optionally send current table data
     *
     * @param	int	$format
     */
    public function exportData($data, $send = false) {
        
        
        $filename = "export";
        include_once "./Services/Excel/classes/class.ilExcelUtils.php";
        include_once "./Services/Excel/classes/class.ilExcelWriterAdapter.php";
        $adapter = new ilExcelWriterAdapter($filename . ".xls", $send);
        $workbook = $adapter->getWorkbook();
        $worksheet = $workbook->addWorksheet();
        $row = 0;
        
        ob_start();
        $this->fillMetaExcel($worksheet, $row);
        $this->fillHeaderExcel($worksheet, $row);
        
        foreach ($data as $set) {
            $row++;
            $this->fillRowExcel($worksheet, $row, $set);
        }
        ob_end_clean();

        $workbook->close();

        if ($send) {
            exit();
        }
    }
}
?>
