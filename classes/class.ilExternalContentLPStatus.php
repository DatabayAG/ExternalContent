<?php
/**
 * Copyright (c) 2015 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg
 * GPLv2, see LICENSE
 */

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
     * @param int $a_obj_id
     * @param int $a_status
     */
    public static function getLPStatusDataFromDb($a_obj_id, $a_status): array
    {
        return self::getLPStatusData((int) $a_obj_id, (int) $a_status);
    }

    /**
     * Get the LP data directly from the database table
     * This can be called from ilObjExternalContent::getLP* methods avoiding loops
     *
     * @param int $a_obj_id
     * @param int $a_user_id
     * @return int
     */
    public static function getLPDataForUserFromDb($a_obj_id, $a_user_id): int
    {
        try {
            return self::getLPDataForUser((int) $a_obj_id, (int) $a_user_id);
        }
        catch (Exception $e) {
            return self::LP_STATUS_NOT_ATTEMPTED_NUM;
        }
    }


    /**
     * Track read access to the object
     * Prevents a call of determineStatus() that would return "not attempted"
     * @see ilLearningProgress::_tracProgress()
     *
     * @param int $a_user_id
     * @param int $a_obj_id
     * @param int $a_ref_id
     */
    public static function trackAccess($a_user_id, $a_obj_id, $a_ref_id)
    {
        ilChangeEvent::_recordReadEvent('xxco', (int) $a_ref_id, (int) $a_obj_id, (int) $a_user_id);

        try {
            $status = self::getLPDataForUser((int) $a_obj_id, (int) $a_user_id);
        }
        catch (Exception $e) {
            $status = self::LP_STATUS_NOT_ATTEMPTED_NUM;
        }
        if ($status == self::LP_STATUS_NOT_ATTEMPTED_NUM)
        {
            self::writeStatus((int) $a_obj_id, (int) $a_user_id, self::LP_STATUS_IN_PROGRESS_NUM);
        }
    }

    /**
     * Track result from the external content
     *
     * @param int $a_user_id
     * @param int $a_obj_id
     * @param int $a_status
     * @param int $a_percentage
     */
    public static function trackResult($a_user_id, $a_obj_id, $a_status, $a_percentage)
    {
        self::writeStatus((int) $a_obj_id, (int) $a_user_id, (int) $a_status, (int) $a_percentage, true);
    }

}