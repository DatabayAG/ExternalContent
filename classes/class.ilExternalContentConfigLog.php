<?php
/**
 * Copyright (c) 2013 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg 
 * GPLv2, see LICENSE 
 */

/**
 * External Content plugin: base class for logging configuration
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 */ 
class ilExternalContentConfigLog {
    
    private $log;
    
    public function __construct() {
        
        $this->log = $this->getLogEntries();
        
    }
    
    function getLogEntries($a_obj_id = '', $a_user_id = '', $a_xxco_type = '', $a_date_less = '', $a_date_more = '') {
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
        
        if($a_xxco_type){
            
        }

        if ($a_user_id) {
            $query .= " AND a.user_id = " . $ilDB->quote($a_user_id);
        }

        if ($date_less and $date_more) {
            $query .= " AND log_time < " . $ilDB->quote($a_date_less, 'timestamp') . " AND log_time > " . $ilDB->quote($a_date_more, 'timestamp');
        } elseif ($date_less) {
            $query .= " AND log_time < " . $ilDB->quote($a_date_less, 'timestamp');
        } elseif ($date_more) {
            $query .= " AND log_time > " . $ilDB->quote($a_date_more, 'timestamp');
        }


        $query .= " order by obj_id";

        $result = $ilDB->query($query);

        while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
            $outputs[] = $row;
        }
        return $outputs;
    }
    
    function delete($a_obj_id) {
        global $ilDB;

        $query = "delete from xxco_data_log "
                . " where obj_id = " . $ilDB->quote($a_obj_id);

        $ilDB->query($query);
    }
    
    static function _getUsers($outputs = array()) {
        global $ilDB;

        $query = "SELECT DISTINCT user_id, firstname, lastname FROM xxco_data_log "
                . " INNER JOIN usr_data ON xxco_data_log.user_id = usr_data.usr_id "
                . " ORDER BY lastname, firstname";
        

        $result = $ilDB->query($query);
        while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
            $outputs[$row['user_id']] = $row['lastname'] . ', ' . $row['firstname'];
        }
        return $outputs;
    }
    
    
    static function _getXXCOType($a_obj_id ="", $outputs = array()) {
        global $ilDB;

        $query = "SELECT DISTINCT c.type_name name FROM xxco_data_log a, xxco_data_settings b, xxco_data_types c WHERE a.ref_id=b.obj_id AND b.type_id = c.type_id ";
        if($a_obj_id){
            $query .= "AND a.ref_if = ". $ilDB->quote($a_obj_id);
        }

        $result = $ilDB->query($query);
        while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
            $outputs[$row['name']] = $row['name'];
        }
        return $outputs;
    }

}
?>
