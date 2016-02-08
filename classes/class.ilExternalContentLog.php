<?php
/**
 * Copyright (c) 2013 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg 
 * GPLv2, see LICENSE 
 */

/**
 * External Content plugin: base logging class
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 */
class ilExternalContentLog {

    private $user_id;
    private $ref_id;
    private $obj_id;
    private $params = array();

    public function __construct() {
        $this->getRequestParams();
        $this->verifyUser($this->params['session_id']);
        $this->verifyObject($this->params['ref_id']);
        $this->writeLog();
    }

    protected function getRequestParams() {
        $this->params = array();
        foreach ($_REQUEST as $name => $value) {
            $this->params[$name] = ilUtil::stripSlashes(urldecode($value));
        }
    }

    protected function verifyUser($a_session_id) {
        global $ilDB;

        $sql = 'select user_id from usr_session where session_id=' . $ilDB->quote($a_session_id);
        $res = $ilDB->query($sql);
        $rec = $res->fetchRow(DB_FETCHMODE_ASSOC);
        if ($rec) {
            $this->user_id = $rec['user_id'];
        } else {
            $this->sendError("Session not valid!");
        }
    }

    protected function verifyObject($a_ref_id) {
        global $ilAccess;

        $this->ref_id = $a_ref_id;
        $this->obj_id = ilObject::_lookupObjId($this->ref_id);

        if (ilObject::_lookupType($this->obj_id) != "xxco") {
            $this->sendError("Wrong object given!");
        }
        if (!$ilAccess->checkAccessOfUser($this->user_id, 'read', '', $this->ref_id)) {
            $this->sendError("No access!");
        }
    }

    protected function writeLog() {
        global $ilDB;

        $ilDB->insert('xxco_data_log', array(
            'obj_id' => array('integer', $ilDB->nextId('xxco_data_log')),
            'ref_id' => array('integer', $ilDB->quote($this->obj_id)),
            'session_id' => array('text', $ilDB->quote($this->params["session_id"])),
            'user_id' => array('integer', $this->user_id),
            'log_time' => array('timestamp', date('Y-m-d H:i:s', time())),
            'call_time' => array('timestamp', $this->params["call_time"]),
            'event_type' => array('text', $ilDB->quote($this->params["event_type"])),
            'event_subtype' => array('text', $ilDB->quote(strval($this->params["totalScorePossible"]))),
            'event_integer' => array('integer', $this->params["finalScore"]),
            'event_text' => array('clob', $ilDB->quote($this->params["event_text"]))
                )
        );
        
        $this->setLearningProgress($this->ref_id, $this->user_id);
        
        $this->sendResponse();
    }
    

    /**
     * Send the response parameters to perception
     */
    protected function sendResponse() {
        echo "Log written.";
        exit;
    }

    /**
     * Send an error message to perception
     */
    protected function sendError($a_message) {
        echo $a_message;
        exit;
    }

    static function _getUsers($a_obj_id, $outputs = array()) {
        global $ilDB;

        $query = "SELECT DISTINCT user_id, firstname, lastname FROM xxco_data_log "
                . " INNER JOIN usr_data ON xxco_data_log.user_id = usr_data.usr_id "
                . " WHERE ref_id = " . $ilDB->quote($a_obj_id)
                . " ORDER BY lastname, firstname";

        $result = $ilDB->query($query);
        while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
            $outputs[$row['user_id']] = $row['lastname'] . ', ' . $row['firstname'];
        }
        return $outputs;
    }

    static function _getCourses($a_obj_id, $outputs = array()) {
        global $ilDB;

        $query = "SELECT DISTINCT obd.obj_id, obd.title " .
                "FROM rbac_ua ua " .
                "INNER JOIN rbac_fa fa ON ua.rol_id = fa.rol_id " .
                "INNER JOIN tree t1 ON t1.child = fa.parent " .
                "INNER JOIN object_reference obr ON t1.parent = obr.ref_id " .
                "INNER JOIN object_data obd ON obr.obj_id = obd.obj_id " .
                "INNER JOIN xxco_data_log el ON ua.usr_id = el.user_id " .
                "WHERE obd.type = 'crs' " .
                "AND fa.assign = 'y' " .
                "AND el.obj_id= " . $ilDB->quote($a_obj_id) . " " .
                "ORDER BY obd.title";

        $result = $ilDB->query($query);
        while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
            $outputs[$row['obj_id']] = $row['title'];
        }
        return $outputs;
    }

    static function _getLogEntries($a_obj_id, $a_user_id = '', $a_xxco_type = '', $a_date_less = '', $a_date_more = '') {
        global $ilDB;
        $outputs = array();
        
        if($a_date_less){
            $date_less = strtotime($a_date_less);
        }
        if($a_date_more){
            $date_more = strtotime($a_date_more);
        }

        $query = "SELECT c.type_name, a.obj_id, a.ref_id, a.session_id, a.user_id, a.log_time, a.call_time, a.event_type, a.event_subtype, a.event_integer, a.event_text ".
                "from xxco_data_log a, xxco_data_settings b, xxco_data_types c ";
        
        if($a_obj_id){
            $query .= "WHERE ref_id = " . $ilDB->quote($a_obj_id);
        }else{
            $query .= "WHERE true ";
        }

        if ($a_user_id) {
            $query .= " AND a.user_id = " . $ilDB->quote($a_user_id);
        }
        
        if($a_xxco_type)
        {
            $query .= " AND c.type_name = " . $ilDB->quote($a_xxco_type);
        }

        if ($date_less and $date_more) {
            $query .= " AND log_time < " . $ilDB->quote($a_date_less, 'timestamp') . " AND log_time > " . $ilDB->quote($a_date_more, 'timestamp');
        } elseif ($date_less) {
            $query .= " AND log_time < " . $ilDB->quote($a_date_less, 'timestamp');
        } elseif ($date_more) {
            $query .= " AND log_time > " . $ilDB->quote($a_date_more, 'timestamp');
        }
        
        $query .= " AND a.ref_id=b.obj_id AND b.type_id = c.type_id";


        $query .= " order by obj_id";
        
        $result = $ilDB->query($query);

        while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
            $outputs[] = $row;
        }
        return $outputs;
    }

    static function _countLogEntries($a_obj_id, $a_user_id = 0) {
        global $ilDB;

        $query = "select count(obj_id) as events from xxco_data_log "
                . " where ref_id = " . $ilDB->quote($a_obj_id);

        if ($a_user_id) {
            $query .= " and user_id = " . $ilDB->quote($a_user_id);
        }

        $result = $ilDB->query($query);
        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);

        return $row["events"];
    }

    static function _delete($a_obj_id) {
        global $ilDB;

        $query = "delete from xxco_data_log "
                . " where obj_id = " . $ilDB->quote($a_obj_id);

        $ilDB->query($query);
    }
    
    public function setLearningProgress($a_ref_id, $a_user)
    {
        
        include_once './Services/Tracking/classes/class.ilLPMarks.php';
        $marks = new ilLPMarks($this->ref_id, $this->user_id);
        $marks->setMark($this->params["event_integer"]);
        $marks->setComment($this->params["event_text"]);
        
        if($this->isNewEntry($a_ref_id, $a_user)){
            $marks->__add();
        }else{
            $marks->update();
        }
    }
    
    public function isNewEntry($a_ref_id, $a_usr_id){
        
        global $ilDB;

		$query = "SELECT * FROM ut_lp_marks ".
			"WHERE usr_id = ".$ilDB->quote($a_usr_id ,'integer')." ".
			"AND obj_id = ".$ilDB->quote($a_ref_id ,'integer');
                
		$result = $ilDB->query($query);
		$row = $result->fetchRow(DB_FETCHMODE_ASSOC);
                if($row){
                    return false;
                }else{
                    return true;
                }
    }

}

?>