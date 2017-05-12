<?php
/**
 * Copyright (c) 2015 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg
 * GPLv2, see LICENSE
 */
require_once 'Services/Tracking/classes/status/class.ilLPStatusPlugin.php';

/**
 * Class ilExternalContentLPStatus
 *
 * This class is an adapter to access protected methods of ilLPStatusPlugin
 * It is used by the ilLPStatusPluginInterface methods of ilObjExternalContent
 *
 * The plugin stores the lp status using ilLearningProgress::_tracProgress() and ilLPStatusWrapper::_updateStatus()
 * This data is stored in the common learning progress tables and can be taken from there.
 * It must, however, be deliverd by methods of ilObjExternalContent
 *
 * Example:
 * - ilLPStatusWrapper::getInProgress($a_obj_id) calls ilLPStatusPlugin::_getInProgress($a_obj_id)
 * - ilLPStatusPlugin::_getInProgress($a_obj_id) creates $obj and calls $obj->getLPInProgress()
 * The status can't be read there via public methods of ilLPStatusWrapper or ilLPStatusPlugin (would be a loop)
 */
class ilExternalContentLPStatus extends ilLPStatusPlugin
{
    /**
     * Get the LP status data directly from the database table
     * This can be called from ilObjExternalContent::getLP* methods avoiding loops
     *
     * @param $a_obj_id
     * @param $a_status
     * @return mixed
     */
    public static function getLPStatusDataFromDb($a_obj_id, $a_status)
    {
        return self::getLPStatusData($a_obj_id, $a_status);
    }

    /**
     * Get the LP data directly from the database table
     * This can be called from ilObjExternalContent::getLP* methods avoiding loops
     *
     * @param $a_obj_id
     * @param $a_user_id
     * @return int
     */
    public static function getLPDataForUserFromDb($a_obj_id, $a_user_id)
    {
        return self::getLPDataForUser($a_obj_id, $a_user_id);
    }


    /**
     * Track read access to the object
     * Prevents a call of determineStatus() that would return "not attempted"
     * @see ilLearningProgress::_tracProgress()
     *
     * @param $a_user_id
     * @param $a_obj_id
     * @param $a_ref_id
     * @param string $a_obj_type
     */
    public static function trackAccess($a_user_id, $a_obj_id, $a_ref_id)
    {
        require_once('Services/Tracking/classes/class.ilChangeEvent.php');
        ilChangeEvent::_recordReadEvent('xxco', $a_ref_id, $a_obj_id, $a_user_id);

        $status = self::getLPDataForUser($a_obj_id, $a_user_id);
        if ($status == self::LP_STATUS_NOT_ATTEMPTED_NUM)
        {
            self::writeStatus($a_obj_id, $a_user_id, self::LP_STATUS_IN_PROGRESS_NUM);
            self::raiseEventStatic($a_obj_id, $a_user_id, self::LP_STATUS_IN_PROGRESS_NUM,
                self::getPercentageForUser($a_obj_id, $a_user_id));
        }
    }

    /**
     * Track result from the external content
     *
     * @param $a_user_id
     * @param $a_obj_id
     * @param $a_status
     * @param $a_percentage
     */
    public static function trackResult($a_user_id, $a_obj_id, $a_status = self::LP_STATUS_IN_PROGRESS_NUM, $a_percentage)
    {
        self::writeStatus($a_obj_id, $a_user_id, $a_status, $a_percentage, true);
        self::raiseEventStatic($a_obj_id, $a_user_id, $a_status, $a_percentage);
    }

    /**
     * Static version if ilLPStatus::raiseEvent
     * This function is just a workaround for PHP7 until ilLPStatus::raiseEvent is declared as static
     *
     * @param $a_obj_id
     * @param $a_usr_id
     * @param $a_status
     * @param $a_percentage
     */
    protected static function raiseEventStatic($a_obj_id, $a_usr_id, $a_status, $a_percentage)
    {
        global $ilAppEventHandler;

        $ilAppEventHandler->raise("Services/Tracking", "updateStatus", array(
            "obj_id" => $a_obj_id,
            "usr_id" => $a_usr_id,
            "status" => $a_status,
            "percentage" => $a_percentage
        ));
    }
}