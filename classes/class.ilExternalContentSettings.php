<?php
/**
 * Copyright (c) 2021 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

require_once(__DIR__ . '/class.ilExternalContentType.php');

class ilExternalContentSettings
{
    const ACTIVATION_OFFLINE = 0;
    const ACTIVATION_UNLIMITED = 1;

    const LP_INACTIVE = 0;
    const LP_ACTIVE = 1;

    protected $settings_id;
    protected $obj_id;
    protected $type_id;
    protected $instructions;
    protected $availability_type;
    protected $lp_mode = self::LP_INACTIVE;
    protected $lp_threshold = 0.5;

    protected $input_values = [];

    /**
     * @var ilDBInterface
     */
    protected $db;

    /**
     * @var ilExternalContentType
     */
    protected $typedef;


    /**
     * Constructor
     * @param $settings_id
     */
    public function __construct($settings_id = null)
    {
        global $DIC;
        $this->db = $DIC->database();

        if ($settings_id) {
            $this->settings_id = $settings_id;
            $this->read();
        }
    }

    /**
     * Set the settings id
     * @param int $id
     */
    public function setSettingsId($id) {
        $this->settings_id = $id;
    }

    /**
     * Get the settings id
     * @return int
     */
    public function getSettingsId() {
        return $this->settings_id;
    }

    /**
     * Get the object id
     * @param int $id
     */
    public function setObjId($id) {
        $this->obj_id = $id;
    }

    /**
     * Get the object to which the settings belong
     * @return int
     */
    public function getObjId() {
        return $this->obj_id;
    }


    /**
     * Set Type Id
     *
     * @param int type id
     */
    public function setTypeId($a_type_id)
    {
        $this->type_id = $a_type_id;
        $this->typedef = new ilExternalContentType($a_type_id);
    }

    /**
     * Get Type Id
     */
    public function getTypeId() {
        return $this->type_id;
    }

    /**
     * Get the type definition
     * @return ilExternalContentType
     */
    public function getTypeDef()
    {
        if (!isset($this->typedef)) {
            $this->typedef = new ilExternalContentType($this->type_id);
        }
        return $this->typedef;
    }

    /**
     * Set instructions
     *
     * @param string instructions
     */
    public function setInstructions($a_instructions) {
        $this->instructions = $a_instructions;
    }

    /**
     * Get instructions
     */
    public function getInstructions() {
        return $this->instructions;
    }

    /**
     * Set availability type
     *
     * @param int availability type
     */
    public function setAvailabilityType($a_type) {
        $this->availability_type = $a_type;
    }

    /**
     * get availability type
     */
    public function getAvailabilityType() {
        return $this->availability_type;
    }

    /**
     * get a text telling the availability
     */
    public function getAvailabilityText() {
        global $DIC;

        switch ($this->availability_type) {
            case self::ACTIVATION_OFFLINE:
                return $DIC->language()->txt('offline');

            case self::ACTIVATION_UNLIMITED:
                return $DIC->language()->txt('online');
        }
        return '';
    }

    /**
     * get the learning progress mode
     * @return int
     */
    public function getLPMode() {
        return $this->lp_mode;
    }

    /**
     * set the learning progress mode
     * @param int $a_mode
     */
    public function setLPMode($a_mode) {
        $this->lp_mode = $a_mode;
    }

    /**
     * get the learning progress mode
     * @return float
     */
    public function getLPThreshold() {
        return $this->lp_threshold;
    }

    /**
     * set the learning progress mode
     * @param float $a_threshold
     */
    public function setLPThreshold($a_threshold) {
        $this->lp_threshold = $a_threshold;
    }

    /**
     * Get array of input values
     */
    public function getInputValues()
    {
        return $this->input_values;
    }

    /**
     * Set an array of input values
     * @param array $a_values   field_name => field_value
     */
    public function setInputValues($a_values)
    {
        $this->input_values = [];
        foreach ($a_values as $a_field_name => $a_field_value) {
            $this->input_values[(string) $a_field_name] = $a_field_value;
        }
    }


    /**
     * Set a single input
     * @param $a_field_name
     * @param $a_field_value
     */
    public function setInputValue($a_field_name, $a_field_value)
    {
        $this->input_values[(string) $a_field_name] = $a_field_value;
    }




    /**
     * Read from db with a given obj_id
     * Should only be used for the repository plugin where only one setting exists per object
     * The page editor plugin may have more settings for the same object
     * @param int $obj_id
     */
    public function readByObjId($obj_id)
    {
        $this->setObjId($obj_id);

        $query = 'SELECT settings_id FROM xxco_data_settings WHERE obj_id = '
            . $this->db->quote($obj_id, 'integer');

        $res = $this->db->query($query);
        $row = $this->db->fetchAssoc($res);

        if (isset($row['settings_id'])) {
            $this->setSettingsId((int) $row['settings_id']);
            $this->read();
        }
    }


    /**
     * Read from db
     */
    public function read() {

        $query = 'SELECT * FROM xxco_data_settings WHERE settings_id = '
            . $this->db->quote($this->getSettingsId(), 'integer');

        $res = $this->db->query($query);
        $row = $this->db->fetchAssoc($res);
        if ($row) {
            $this->setSettingsId($row['settings_id']);
            $this->setObjId($row['obj_id']);
            $this->setTypeId($row['type_id']);
            $this->setInstructions($row['instructions']);
            $this->setAvailabilityType($row['availability_type']);
            $this->setLPMode($row['lp_mode']);
            $this->setLPThreshold($row['lp_threshold']);
        }

        $query = 'SELECT * FROM xxco_data_values WHERE settings_id = '
            . $this->db->quote($this->getSettingsId(), 'integer');
        $res = $this->db->query($query);

        $this->setInputValues([]);
        while ($row = $this->db->fetchAssoc($res)) {
            $this->setInputValue($row['field_name'], $row['field_value']);
        }
    }



    /**
     * save in db
     */
    public function save() {

        if (empty($this->settings_id)) {
            $this->settings_id = $this->db->nextId('xxco_data_settings');
        }

        $this->db->replace('xxco_data_settings', array(
            'settings_id' => array('integer', $this->getSettingsId()),
        ), array(
                'obj_id' => array('integer', $this->getObjId()),
                'type_id' => array('integer', (int) $this->getTypeId()),
                'availability_type' => array('integer', (int) $this->getAvailabilityType()),
                'instructions' => array('text', $this->getInstructions()),
                'lp_mode' => array('integer', $this->getLPMode()),
                'lp_threshold' => array('float', $this->getLPThreshold())
            )
        );

        foreach ($this->getInputValues() as $field_name => $field_value) {
            $this->db->replace('xxco_data_values', array(
                'settings_id' => array('integer', $this->getSettingsId()),
                'field_name' => array('text', $field_name)
            ), array(
                    'field_value' => array('text', $field_value)
                )
            );
        }
    }

    /**
     * Delete from db
     */
    public function delete()
    {
        $query = "DELETE FROM xxco_data_settings " .
            "WHERE settings_id = " . $this->db->quote($this->getSettingsId(), 'integer') . " ";
        $this->db->manipulate($query);

        $query = "DELETE FROM xxco_data_values " .
            "WHERE settings_id = " . $this->db->quote($this->getSettingsId(), 'integer') . " ";
        $this->db->manipulate($query);
        return true;
    }

    /**
     * Clone the settings
     * @param self $newSettings
     */
    public function clone($newSettings)
    {
        $newSettings->setObjId($this->getObjId());
        $newSettings->setTypeId($this->getTypeId());
        $newSettings->setAvailabilityType($this->getAvailabilityType());
        $newSettings->setInstructions($this->getInstructions());
        $newSettings->setLPMode($this->getLPMode());
        $newSettings->setLPThreshold($this->getLPThreshold());
        $newSettings->setInputValues($this->getInputValues());
        $newSettings->save();
    }
}