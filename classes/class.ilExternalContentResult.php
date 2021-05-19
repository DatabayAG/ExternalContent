<?php
/**
 * Copyright (c) 2013 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

/**
 * Data Model for LTI results
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @version $Id$
 */
class ilExternalContentResult
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var integer
     */
    public $obj_id;

    /**
     * @var integer
     */
    public $usr_id;

    /**
     * @var float
     */
    public $result;


    /**
     * Get a result by id
     * @param integer id
     * @return ilExternalContentResult|null
     */
    public static function getById($a_id)
    {
        global $ilDB;

        $query = 'SELECT * FROM xxco_results'
            .' WHERE id = '. $ilDB->quote($a_id,'integer');

        $res = $ilDB->query($query);
        if ($row = $ilDB->fetchAssoc($res))
        {
            $resObj = new ilExternalContentResult;
            $resObj->fillData($row);
            return $resObj;
        }
        else
        {
            return null;
        }
    }

    /**
     * Get a result by object and user key
     *
     * @param integer   object id
     * @param integer   user id
     * @param boolean   save a new result object result if not exists
     * @return ilExternalContentResult|null
     */
    public static function getByKeys($a_obj_id, $a_usr_id, $a_create = false)
    {
        global $ilDB;

        $query = 'SELECT * FROM xxco_results'
            .' WHERE obj_id = '. $ilDB->quote($a_obj_id,'integer')
            .' AND usr_id = '. $ilDB->quote($a_usr_id,'integer');

        $res = $ilDB->query($query);
        if ($row = $ilDB->fetchAssoc($res))
        {
            $resObj = new ilExternalContentResult;
            $resObj->fillData($row);
            return $resObj;
        }
        elseif ($a_create)
        {
            $resObj = new ilExternalContentResult;
            $resObj->obj_id = $a_obj_id;
            $resObj->usr_id = $a_usr_id;
            $resObj->result = null;
            $resObj->save();
            return $resObj;
        }
        else
        {
            return null;
        }
    }

    /**
     * Fill the properties with data from an array
     * @param array assoc data
     */
    protected function fillData($data)
    {
        $this->id = $data['id'];
        $this->obj_id = $data['obj_id'];
        $this->usr_id = $data['usr_id'];
        $this->result = $data['result'];
    }

    /**
     * Save a result object
     */
    public function save()
    {
        global $ilDB;

        if (!isset($this->usr_id) or !isset($this->obj_id))
        {
            return false;
        }
        if (!isset($this->id))
        {
            $this->id = $ilDB->nextId('xxco_results');
        }
        $ilDB->replace('xxco_results',
            array(
                'id' => array('integer', $this->id)
            ),
            array(
                'obj_id' => array('integer', $this->obj_id),
                'usr_id' => array('integer', $this->usr_id),
                'result' => array('float', $this->result)
            )
        );
        return true;
    }
} 