<?php
/**
 * Copyright (c) 2013 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg 
 * GPLv2, see LICENSE 
 */

/**
 * External Content plugin: Loggin GUI
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 */
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/classes/class.ilObjExternalContent.php');
include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/classes/class.ilExternalContentLog.php');

class ilExternalContentLogGUI {

    /**
     * Constructor
     * @access public
     */
    function __construct($a_parent_gui) {
        
        $this->parent_gui = $a_parent_gui;
        $this->ref_id = $this->parent_gui->object->getRefId();
        $this->obj_id = $this->parent_gui->object->getId();
        $this->initPar();
        
    }

    /**
     * perform command
     *
     * @access public
     */
    public function performCommand($cmd) {
        global $ilAccess, $ilErr, $ilCtrl, $lng;

        // access to all functions are only allowed if edit_permission is granted
        if (!$ilAccess->checkAccess("write", "", $this->ref_id)) {
            $ilErr->raiseError($lng->txt('permission_denied'), $ilErr->MESSAGE);
        }
        
        // control flow
        $next_class = $ilCtrl->getNextClass($this);
        switch ($next_class) {
            case "delete":
                if ($this->getPermission('write')) {
                    $this->$cmd();
                }
                break;
            case "viewLogFiltered":
                if ($this->getPermission('write')) {
                    $cmd = "viewLog";
                    $this->$cmd();
                }
                break;
            case 'deleteEntries':
                if ($this->getPermission('write')) {
                    $this->$cmd();
                }
                break;
            case 'excelExport':
            if ($this->getPermission('write')) {
                $this->$cmd();
            }
            break;
            default:
                if (!$cmd = $ilCtrl->getCmd()) {
                    $cmd = "viewLog";
                }
                return $this->$cmd();
                break;
        }

    }

    /**
     * show log table
     *
     * @access public
     */
    public function viewLog() {

        require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/classes/class.ilExternalContentLogTableGUI.php');

        $table_gui = new ilExternalContentLogTableGUI($this, "viewLog");
        $table_gui->init($this->parent_gui, $this->obj_id, $this->getPar('filter_user'), $this->getPar('filter_xxco_type'), $this->getPar('date_less'), $this->getPar('date_more'));

        return $table_gui->getHTML();
    }
    
    public function excelExport(){
        
        require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/classes/class.ilExternalContentLogTableGUI.php');
        $table_gui = new ilExternalContentLogTableGUI($this, "excelExport");
        $table_gui->init($this->parent_gui, $this->obj_id, $this->getPar('filter_user'), $this->getPar('filter_xxco_type'), $this->getPar('date_less'), $this->getPar('date_more'));
        $data = $table_gui->row_data;
        
        return $table_gui->exportData($data, true);
    }
        

    /**
     * Export as Excel file
     *
     * @access public

      function exportExcel() {
      global $lng;

      // get the log data
      $filter_user = $this->getPar('filter_user');
      $filter_course = $this->getPar('filter_course');
      $data = ilExternalContentLog::_getLogEntries(
      $this->obj_id, $filter_user, $filter_course);
      if (!count($data)) {
      ilUtil::sendInfo($lng->txt('rep_robj_xxco_no_events_found'));
      $this->viewLog();
      return;
      }

      require_once "./Services/Utilities/classes/class.ilUtil.php";
      require_once "./classes/class.ilExcelUtils.php";
      require_once "./classes/class.ilExcelWriterAdapter.php";

      // Init the temporary file
      $tempname = ilUtil::ilTempnam();
      $adapter = new ilExcelWriterAdapter($tempname, FALSE);
      $workbook = $adapter->getWorkbook();
      $worksheet = $workbook->addWorksheet();

      // Init formats
      $a_mode = 'latin1';

      $format_bold = $workbook->addFormat();
      $format_bold->setBold();

      $format_percent = $workbook->addFormat();
      $format_percent->setNumFormat("0.00%");

      $format_datetime = $workbook->addFormat();
      $format_datetime->setNumFormat("DD/MM/YYYY hh:mm:ss");

      $format_title = $workbook->addFormat();
      $format_title->setBold();
      $format_title->setColor('black');
      $format_title->setPattern(1);
      $format_title->setFgColor('silver');

      $row = 0;
      $col = 0;

      // write key data
      $worksheet->write($row, 0, ilExcelUtils::_convert_text($lng->txt('rep_robj_xxco_key') . ":", $a_mode));
      $worksheet->write($row++, 2, ilExcelUtils::_convert_text(ilObject::_lookupTitle($this->obj_id), $a_mode));

      preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", date('Y-m-d H:i:s'), $matches);
      $worksheet->write($row, 0, ilExcelUtils::_convert_text($lng->txt('rep_robj_xxco_export') . ":", $a_mode));
      $worksheet->write($row++, 2, ilUtil::excelTime($matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $matches[6]), $format_datetime);

      $name = ilObjUser::_lookupName($filter_user);
      $worksheet->write($row, 0, ilExcelUtils::_convert_text($lng->txt('user') . ":", $a_mode));
      $worksheet->write($row++, 2, ilExcelUtils::_convert_text($filter_user ? $name['lastname'] . ", " . $name['firstname'] : $lng->txt('rep_robj_xxco_any'), $a_mode));

      $worksheet->write($row, 0, ilExcelUtils::_convert_text($lng->txt('rep_robj_xxco_filter_course') . ":", $a_mode));
      $worksheet->write($row++, 2, ilExcelUtils::_convert_text($filter_course ? ilObject::_lookupTitle($filter_course) : $lng->txt('rep_robj_xxco_any'), $a_mode));


      // write headline
      $col = 0;
      $titles = array(
      $lng->txt("rep_robj_xxco_event_id"),
      $lng->txt("user"),
      $lng->txt("rep_robj_xxco_time"),
      $lng->txt("rep_robj_xxco_type"),
      $lng->txt("rep_robj_xxco_subtype"),
      $lng->txt("rep_robj_xxco_integer"),
      $lng->txt("rep_robj_xxco_text")
      );
      foreach ($titles as $title) {
      $worksheet->write($row, $col++, ilExcelUtils::_convert_text($title, $a_mode), $format_title);
      }

      // write data
      $names = array();
      foreach ($data as $record) {
      $row++;
      $col = 0;

      // event_id
      $worksheet->write($row, $col++, $record["event_id"]);

      // user
      if (!$names[$record["usr_id"]]) {
      $name = ilObjUser::_lookupName($record["user_id"]);
      $names[$record["user_id"]] = $name["lastname"] . ", " . $name["firstname"];
      }
      $worksheet->write($row, $col++, ilExcelUtils::_convert_text($names[$record["user_id"]], $a_mode));


      // time
      preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $record["log_time"], $matches);
      $worksheet->write($row, $col++, ilUtil::excelTime($matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $matches[6]), $format_datetime);


      // type
      $worksheet->write($row, $col++, ilExcelUtils::_convert_text($record["event_type"], $a_mode));

      // subtype
      $worksheet->write($row, $col++, ilExcelUtils::_convert_text($record["event_subtype"], $a_mode));

      // integer
      $worksheet->write($row, $col++, $record["event_integer"]);

      // text
      $worksheet->write($row, $col++, ilExcelUtils::_convert_text($record["event_text"], $a_mode));
      }

      $workbook->close();


      // deliver and cleanup the file

      $filename = "key_" . $this->obj_id . "_log_" . date('Y-m-d_H-i-s') . ".xls";
      $mimetype = "application/vnd.ms-excel";
      $inline = false;
      $remove = true;

      ilUtil::deliverFile($tempname, $filename, $mometype, $inline, $remove);
      }
     * 
     */


    /**
     * delete entries
     *
     * @access public
     *
     */
    public function deleteEntries() {

        global $lng;

        if (!is_array($_POST['event_id']) or !count($_POST['event_id'])) {
            ilUtil::sendInfo($lng->txt('rep_robj_xxco_select_one'));
            $this->viewLog();
        }

        foreach ($_POST["event_id"] as $event_id) {
            ilExternalContentLog::_delete($event_id);
        }

        ilUtil::sendInfo($lng->txt('rep_robj_xxco_events_deleted'));
        $this->viewLog();
    }

    /**
     * Get the Html code of the filter form
     */
    function getFilterHTML() {
        global $ilCtrl, $lng;

        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();
        $form->setFormAction($ilCtrl->getFormAction($this));
        $form->setTitle($lng->txt('rep_robj_xxco_filter'));

        // user select
        $item = new ilSelectInputGUI($lng->txt('rep_robj_xxco_filter_user'), 'filter_user');
        $item->setOptions(ilExternalContentLog::_getUsers($this->obj_id, array(0 => $lng->txt('rep_robj_xxco_any'))));
        $item->setValue($this->getPar('filter_user'));
        $form->addItem($item);

        // course select
        //$item = new ilSelectInputGUI($lng->txt('rep_robj_xxco_course'), 'filter_course');
        //$item->setOptions(ilExternalContentLog::_getCourses(
        //                $this->obj_id, array(0 => $lng->txt('rep_robj_xxco_any'))));
        //$item->setValue($this->getPar('filter_course'));
        //$form->addItem($item);
        
        //date select less than date
        $item = new ilTextInputGUI($lng->txt('rep_robj_xxco_less_date'), 'date_less');
        if($this->getPar('date_less')){
            $item->setValue($this->getPar('date_less'));
        }else{
        $item->setValue("year-month-day hour:minute:second");
        }
        $form->addItem($item);
        
        //date select more than date
        $item = new ilTextInputGUI($lng->txt('rep_robj_xxco_more_date'), 'date_more');
        if($this->getPar('date_more')){
            $item->setValue($this->getPar('date_more'));
        }else{
        $item->setValue("year-month-day hour:minute:second");
        }
        $form->addItem($item);

        $form->addCommandButton("viewLog", $lng->txt('rep_robj_xxco_apply_filter'));

        return $form->getHTML();
    }

    /**
     * Init the storage of GUI session params
     */
    function initPar() {
        if (!isset($this->session_params)) {
            if (!is_array($_SESSION[get_class($this)])) {
                $_SESSION[get_class($this)] = array();
            }

            $this->session_params = & $_SESSION[get_class($this)];
        }
    }

    /**
     * Read a param that is either coming from GET, POST
     * or is taken from the session variables of this GUI.
     * A request value is automatically saved in the session variables.
     * Slashes are stripped from request values.
     *
     * @param    string      name of the GET or POST or variable
     * @param    mixed       default value
     */
    function getPar($a_request_name, $a_default_value = '') {
        // get the parameter value
        if (isset($_GET[$a_request_name])) {
            $param = $_GET[$a_request_name];
            $from_request = true;
        } elseif (isset($_POST[$a_request_name])) {
            $param = $_POST[$a_request_name];
            $from_request = true;
        } elseif (isset($this->session_params[$a_request_name])) {
            $param = $this->session_params[$a_request_name];
            $from_request = false;
        } else {
            $param = $a_default_value;
            $from_request = false;
        }

        // strip slashes from request parameters
        if ($from_request) {
            if (is_array($param)) {
                foreach ($param as $key => $value) {
                    $param[$key] = ilUtil::stripSlashes($value);
                }
            } else {
                $param = ilUtil::stripSlashes($param);
            }
        }

        // make the parameter available to further requests
        $this->session_params[$a_request_name] = $param;

        return $param;
    }

}

?>
