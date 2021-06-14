<?php
/**
 * Copyright (c) 2015 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg
 * GPLv2, see LICENSE 
 */
require_once('./Services/Repository/classes/class.ilObjectPlugin.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/classes/class.ilExternalContentType.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/classes/class.ilExternalContentEncodings.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/classes/class.ilExternalContentUserData.php');

require_once 'Services/Tracking/interfaces/interface.ilLPStatusPlugin.php';

/**
 * External Content plugin: base class for repository object
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 */
class ilObjExternalContent extends ilObjectPlugin implements ilLPStatusPluginInterface
{

    const ACTIVATION_OFFLINE = 0;
    const ACTIVATION_UNLIMITED = 1;

    const LP_INACTIVE = 0;
    const LP_ACTIVE = 1;

    /**
     * External Content Type definition (object)
     */
    var $typedef;

    /**
     * Fields for filling template (list of field arrays)
     */
    protected $fields;
    protected $availability_type;
    protected $type_id;
    protected $instructions;
    protected $meta_data_xml;
    protected $context = null;
    protected $lp_mode = self::LP_INACTIVE;
    protected $lp_threshold = 0.5;

    /**
     * Return URL: This is a run-time variable set by the GUI and not stored
     * @var string
     */
    protected $return_url;

    /**
     * Goto Suffix:  This is a run-time variable set by the GUI and not stored
     * @var string
     */
    protected $goto_suffix;



    /** @var ilExternalContentUserData */
    protected $userData;

    /**
     * Constructor
     *
     * @access public
     * 
     */
    public function __construct($a_id = 0, $a_call_by_reference = true) {
        global $ilDB;

        parent::__construct($a_id, $a_call_by_reference);

        $this->db = $ilDB;
        $this->typedef = new ilExternalContentType();
    }

    /**
     * Get type.
     * The initType() method must set the same ID as the plugin ID.
     *
     * @access	public
     */
    final public function initType() {
        $this->setType('xxco');
    }


    /**
     * Get the cached user data
     * don't initialize in outcome service (ui for avatar not available)
     * @return ilExternalContentUserData
     */
    protected function getUserData() {
        if (!isset($this->userData)) {
            $this->userData = ilExternalContentUserData::create($this->plugin);
        }
        return $this->userData;
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
     * Set Type Id
     *
     * @param int type id
     */
    public function setTypeId($a_type_id) {
        if ($this->type_id != $a_type_id) {
            $this->typedef = new ilExternalContentType($a_type_id);
            $this->type_id = $a_type_id;
        }
    }

    /**
     * Get Type Id
     */
    public function getTypeId() {
        return $this->type_id;
    }

    /**
     * Set vailability type
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
        global $lng;

        switch ($this->availability_type) {
            case self::ACTIVATION_OFFLINE:
                return $lng->txt('offline');

            case self::ACTIVATION_UNLIMITED:
                return $lng->txt('online');
        }
        return '';
    }

    /**
     * Set meta data as xml structure
     *
     * @param int availability type
     */
    public function setMetaDataXML($a_xml) {
        $this->meta_data_xml = $a_xml;
    }

    /**
     * get meta data as xml structure
     */
    public function getMetaDataXML() {
        return $this->meta_data_xml;
    }


    /**
     * Get online status
     */
    public function getOnline() {
        switch ($this->availability_type) {
            case self::ACTIVATION_UNLIMITED:
                return true;

            case self::ACTIVATION_OFFLINE:
                return false;

            default:
                return false;
        }
    }

    /**
     * set a return url for coming back from the content
     * 
     * @param string	return url
     */
    public function setReturnUrl($a_return_url) {
        $this->return_url = $a_return_url;
    }

    /**
     * get a return url for coming back from the content
     * 
     * @return string	return url
     */
    public function getReturnUrl() {
        return $this->return_url;
    }


    /**
     * set a suffix provided by the goto link
     *
     * @param string	goto suffix
     */
    public function setGotoSuffix($a_goto_suffix) {
        $this->goto_suffix = (string) $a_goto_suffix;
    }

    /**
     * get the suffix provided by the goto link
     *
     * @return string	goto suffix
     */
    public function getGotoSufix() {
        return (string) $this->goto_suffix;
    }


    /**
     * get the URL to lauch the assessment
     *
     * @access public
     */
    public function getLaunchLink() {
        return $this->fillTemplateRec($this->typedef->getTemplate());
    }

    /**
     * get the code to embed the object on a page
     *
     * @access public
     */
    public function getEmbedCode() {
        return $this->fillTemplateRec($this->typedef->getTemplate());
    }

    /**
     * get the code of a page to show
     *
     * @access public
     */
    public function getPageCode() {
        return $this->fillTemplateRec($this->typedef->getTemplate());
    }


    /**
     * Fill a template recursively with field values
     * Placeholders are like {FIELD_NAME}
     * Replacement is case insensitive
     *
     * @param    string  template
     * @param    int     maximum recursion depth (default 100, stops at 0)
     */
    private function fillTemplateRec($a_template, $a_maxdepth = 100) {
        $this->initFields();

        foreach ($this->fields as $name => $field) {
            $pattern = $this->typedef->getPlaceholder($name);
            if (strpos($a_template, $pattern) !== false) {
                $value = $this->fillField($field, $a_maxdepth);

                // replace the placeholder in the template
                $a_template = str_replace($pattern, $value, $a_template);
            }
        }
        return $a_template;
    }

    /**
     * Fill a field and return its value
     * 
     * @param	array	field
     * @param	int		maximum recoursion depth
     * @return 	mixed	field value (depending on type)
     */
    private function fillField($a_field, $a_maxdepth = 100) {
        // check recursion or existing values   	
        if (0 > $a_maxdepth--) {
            return 'max depth reached!';
        } elseif (isset($a_field['field_value'])) {
            //echo "<br />FOUND: ".  $a_field['field_name'] . " = ";
            //var_dump($a_field['field_value']);

            return $a_field['field_value'];
        }

        // get field values that are not yet known
        switch ($a_field['field_type']) {
            case ilExternalContentType::FIELDTYPE_ILIAS:
                $value = $this->fillIliasField($a_field);
                break;

            case ilExternalContentType::FIELDTYPE_CALCULATED:
                $value = $this->fillCalculatedField($a_field, $a_maxdepth);
                break;

            case ilExternalContentType::FIELDTYPE_TEMPLATE:
                $value = $this->fillTemplateRec($a_field['template'], $a_maxdepth);
                break;
        }

        // apply an encoding to the value
        $value = ilExternalContentEncodings::_applyEncoding($a_field['encoding'], $value);


        // save the value so that it is not re-calculated
        $this->fields[$a_field['field_name']]['field_value'] = $value;

        //echo "<br />FILLED: ".  $a_field['field_name'] . " = ";
        //var_dump($value);

        return $value;
    }

    /**
     * Apply a function with parameters to fill a field
     * 
     * @param $a_field
     * @param $a_maxdepth
     * @return mixed
     */
    private function fillCalculatedField($a_field, $a_maxdepth) {
        // process the function parameters
        $parsed_params = array();
        foreach ($a_field['params'] as $param_name => $param_value) {
            foreach ($this->fields as $field_name => $field) {
                if ($param_value == $this->typedef->getPlaceholder($field_name)) {
                    $param_value = $this->fillField($field, $a_maxdepth);
                }

                $parsed_params[$param_name] = $param_value;
            }
        }

        // apply the function
        require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/classes/class.ilExternalContentFunctions.php");
        $value = ilExternalContentFunctions::applyFunction($a_field['function'], $parsed_params);

        // save the value so that it is not re-calculated
        $this->fields[$a_field['field_name']]['field_value'] = $value;

        return $value;
    }

    /**
     * create an access token
     * 
     * @param $a_field
     * @return unknown_type
     */
    private function fillToken($a_field) {
        $seconds = $this->getTimeToDelete();
        $result = $this->selectCurrentTimestamp();
        $time = new ilDateTime($result['current_timestamp'], IL_CAL_DATETIME);

        $timestamp = $time->get(IL_CAL_UNIX);
        $new_timestamp = $timestamp + $seconds;

        $value = $this->createToken($timestamp);

        $time_to_db = new ilDateTime($new_timestamp, IL_CAL_UNIX);

        //Insert new token in DB
        $this->insertToken($value, $time_to_db->get(IL_CAL_DATETIME));

        //delete old tokens
        $this->deleteToken($timestamp);

        return $value;
    }

    /**
     * fill an ILIAS field
     * @param $a_field
     * @return unknown_type
     */
    private function fillIliasField($a_field) {
        global $ilias, $ilUser, $ilSetting, $ilAccess, $ilClientIniFile;


        $value = "";
        switch ($a_field['field_name']) {
            // object information

            case "ILIAS_REF_ID":
                $value = $this->getRefId();
                break;

            case "ILIAS_TITLE":
                $value = $this->getTitle();
                break;

            case "ILIAS_DESCRIPTION":
                $value = $this->getDescription();
                break;

            case "ILIAS_INSTRUCTIONS":
                $value = $this->getInstructions();
                break;

            // object context	

            case "ILIAS_CONTEXT_ID":
                $context = $this->getContext();
                $value = $context['id'];
                break;

            case "ILIAS_CONTEXT_TYPE":
                $context = $this->getContext();
                $value = $context['type'];
                break;

            case "ILIAS_CONTEXT_TITLE":
                $context = $this->getContext();
                $value = $context['title'];
                break;

            // call-time imformation

            case "ID":
                $value = $this->selectID();
                break;

            case "ILIAS_REMOTE_ADDR":
                $value = $_SERVER["REMOTE_ADDR"];
                break;

            case "ILIAS_TIME":
                $value = date('Y-m-d H:i:s', time());
                break;

            case "ILIAS_TIMESTAMP":
                $value = time();
                break;

            case "ILIAS_SESSION_ID":
                $value = session_id();
                break;

            case "ILIAS_TOKEN":
                $value = $this->fillToken($a_field);
                break;

            case "ILIAS_RESULT_ID":
                if ($this->getLPMode() == self::LP_ACTIVE)
                {
                    $this->plugin->includeClass('class.ilExternalContentResult.php');
                    $result = ilExternalContentResult::getByKeys($this->getId(), $ilUser->getId(), true);
                    $value = $result->id;
                }
                else
                {
                    $value= "";
                }
                break;

            case "ILIAS_GOTO_SUFFIX":
                $value = $this->getGotoSufix();
                break;

            case "ILIAS_GOTO_AUTOSTART":
                $value = ($this->getGotoSufix() == 'autostart' ?  1 : 0);
                break;

            // service urls
            case "ILIAS_CALLBACK_URL":
                $value = ILIAS_HTTP_PATH . "/Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/callback.php";
                break;

            case "ILIAS_EVENT_LOG_URL":
                $value = ILIAS_HTTP_PATH . "/Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/event_log.php";
                break;

            case "ILIAS_RETURN_URL":
                $value = $this->getReturnUrl();
                break;

            case "ILIAS_RESULT_URL":
                if ($this->getLPMode() == self::LP_ACTIVE)
                {
                    $value = ILIAS_HTTP_PATH . "/Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/result.php"
                        . '?client_id='.CLIENT_ID;
                }
                else
                {
                    $value = '';
                }
                break;

            // user information			

            case "ILIAS_USER_ID":
                $value = $ilUser->getId();
                break;

            case "ILIAS_USER_CODE":
                $value = sha1($ilUser->getId() . $ilUser->getCreateDate());
                break;

            case "ILIAS_USER_LOGIN":
                $value = $ilUser->getLogin();
                break;

            case "ILIAS_USER_WRITE_ACCESS":
                $value = $ilAccess->checkAccess('write', '', $this->getRefId()) ? "1" : "0";
                break;

            // platform information

            case "ILIAS_VERSION":
                $value = $ilias->getSetting("ilias_version");
                break;

            case "ILIAS_CONTACT_EMAIL":
                $value = $ilSetting->get("admin_email");
                break;

            case "ILIAS_CLIENT_ID":
                $value = CLIENT_ID;
                break;

            case "ILIAS_HTTP_PATH":
                $value = ILIAS_HTTP_PATH;
                break;

            case "ILIAS_LMS_URL":
                require_once ('./Services/Link/classes/class.ilLink.php');
                $value = ilLink::_getLink(ROOT_FOLDER_ID, "root");
                break;

            case "ILIAS_LMS_GUID":
                $parsed = parse_url(ILIAS_HTTP_PATH);
                $value = CLIENT_ID . "." . implode(".", array_reverse(explode("/", $parsed["path"]))) . $parsed["host"];
                break;

            case "ILIAS_LMS_NAME":
                if (!$value = $ilSetting->get("short_inst_name")) {
                    $value = $ilClientIniFile->readVariable('client', 'name');
                }
                break;

            case "ILIAS_LMS_DESCRIPTION":
                require_once("Modules/SystemFolder/classes/class.ilObjSystemFolder.php");
                if (!$value = ilObjSystemFolder::_getHeaderTitle()) {
                    $value = $ilClientIniFile->readVariable('client', 'description');
                }
                break;

            default:

                // fill additional user fields
                foreach ($this->getUserData()->getFieldValues() as $field_name => $field_value) {
                    if ($field_name == $a_field['field_name']) {
                        $value = $field_value;
                    }
                }
        }

        return $value;
    }

    /**
     * initialize the fields for template processing
     */
    private function initFields() {

        if (is_array($this->fields)) {
            return;
        }
        $this->fields = array();


        //
        // ILIAS fields (type and encoding are commmon to all)
        //
        $ilias_names = array(
            // object information
            'ILIAS_REF_ID',
            'ILIAS_TITLE',
            'ILIAS_DESCRIPTION',
            'ILIAS_INSTRUCTIONS',
            // object context
            'ILIAS_CONTEXT_ID',
            'ILIAS_CONTEXT_TYPE',
            'ILIAS_CONTEXT_TITLE',
            // call-time imformation
            'ID',
            'ILIAS_REMOTE_ADDR',
            'ILIAS_TIME',
            'ILIAS_TIMESTAMP',
            'ILIAS_SESSION_ID',
            'ILIAS_TOKEN',
            'ILIAS_RESULT_ID',
            'ILIAS_GOTO_SUFFIX',
            'ILIAS_GOTO_AUTOSTART',
            // service urls
            'ILIAS_CALLBACK_URL',
            'ILIAS_EVENT_LOG_URL',
            'ILIAS_RETURN_URL',
            'ILIAS_RESULT_URL',
            // user information
            'ILIAS_USER_ID',
            'ILIAS_USER_CODE',
            'ILIAS_USER_LOGIN',
            'ILIAS_USER_WRITE_ACCESS',
            // platform information
            'ILIAS_VERSION',
            'ILIAS_CONTACT_EMAIL',
            'ILIAS_CLIENT_ID',
            'ILIAS_HTTP_PATH',
            'ILIAS_LMS_URL',
            'ILIAS_LMS_GUID',
            'ILIAS_LMS_NAME',
            'ILIAS_LMS_DESCRIPTION',
        );

        // add user data fields
        $ilias_names = array_merge($ilias_names, $this->getUserData()->getFieldNames());

        foreach ($ilias_names as $name) {
            $field = array();
            $field['field_name'] = $name;
            $field['field_type'] = ilExternalContentType::FIELDTYPE_ILIAS;
            $field['encoding'] = '';

            $this->fields[$field['field_name']] = $field;
        }

        //
        // type specific fields
        //
        $type_fields = $this->typedef->getFieldsAssoc();
        $type_values = $this->typedef->getInputValues();
        $object_values = $this->getInputValues();

        foreach ($type_fields as $field) {

            // set value to user input
            if ($field['field_type'] != ilExternalContentType::FIELDTYPE_TEMPLATE and $field['field_type'] != ilExternalContentType::FIELDTYPE_CALCULATED) {
                switch ($field['level']) {
                    case "type":
                        $field['field_value'] = $type_values[$field['field_name']];
                        break;

                    case "object":
                    default:
                        $field['field_value'] = $object_values[$field['field_name']];
                        break;
                }
            }

            // special input fields: process user input to a new value
            if ($field['field_type'] == ilExternalContentType::FIELDTYPE_SPECIAL) {
                switch ($field['field_name']) {
                    case ilExternalContentType::FIELD_LTI_USER_DATA:
                        $field['field_value'] = $this->getUserData()->getLtiParams($field['field_value']);
                        break;
                }
            }

            $this->fields[$field['field_name']] = $field;
        }
    }


    /**
     * get info about the context in which the link is used
     * 
     * The most outer matching course or group is used
     * If not found the most inner category or root node is used
     * 
     * @param	array	list of valid types
     * @return 	array	context array ("ref_id", "title", "type")
     */
    public function getContext($a_valid_types = array('crs', 'grp', 'cat', 'root')) {
        global $tree;

        if (!isset($this->context)) {

            $this->context = array();

            // check fromm inner to outer
            $path = array_reverse($tree->getPathFull($this->getRefId()));
            foreach ($path as $key => $row)
            {
                if (in_array($row['type'], $a_valid_types))
                {
					// take an existing inner context outside a course
					if (in_array($row['type'], array('cat', 'root')) && !empty($this->context))
					{
						break;
					}

					$this->context['id'] = $row['child'];
                    $this->context['title'] = $row['title'];
                    $this->context['type'] = $row['type'];

                    // don't break to get the most outer course or group
                }
            }
        }

        return $this->context;
    }

    /**
     * fetch external meta data
     * and save them locally for caching
     *
     * @return   object 	simpleXMLElement of metadata
     */
    public function fetchMetaData($a_timeout = 0) {
        $meta_raw = "";

        $url = $this->typedef->getMetaDataUrl();
        if ($url) {
            $url = $this->fillTemplateRec($url);
            $default_timeout = ini_get('default_socket_timeout');
            if ($a_timeout) {
                ini_set('default_socket_timeout', $a_timeout);
            }
            $meta_raw = @file_get_contents($url);
            ini_set('default_socket_timeout', $default_timeout);

            $meta_raw_enc = utf8_encode($meta_raw);
        }

        // Verification
        $meta_obj = simplexml_load_string($meta_raw_enc);


        if ($meta_obj === false) {
            // use cached calue
            return simplexml_load_string($this->getMetaDataXML());
        }

        if ($meta_raw_enc != $this->getMetaDataXML()) {
            $this->setMetaDataXML($meta_raw_enc);
            $this->doUpdate();
        }

        return $meta_obj;
    }

    /**
     * Update function
     *
     * @access public
     */
    public function doUpdate() {
        global $ilDB;


        $ilDB->replace('xxco_data_settings', array(
            'obj_id' => array('integer', $this->getId()),
                ), array(
            'type_id' => array('integer', $this->getTypeId()),
            'availability_type' => array('integer', $this->getAvailabilityType()),
            'instructions' => array('text', $this->getInstructions()),
            'meta_data_xml' => array('text', $this->getMetaDataXML()),
            'lp_mode' => array('integer', $this->getLPMode()),
            'lp_threshold' => array('float', $this->getLPThreshold())

            )
        );

        return true;
    }

    public function insertToken($a_token, $a_time) {
        global $ilDB;

        $ilDB->insert('xxco_data_token', array(
            'token' => array('text', $a_token),
            'time' => array('timestamp', $a_time))
        );

        return true;
    }

    public function deleteToken($times) {
        global $ilDB;

        $value = date('Y-m-d H:i:s', $times);
        $query = "DELETE FROM xxco_data_token WHERE time < " . $ilDB->quote($value, 'timestamp');
        $ilDB->manipulate($query);
        return true;
    }

    /**
     * Save field values
     *
     * @access public
     */
    public function saveFieldValue($a_field_name, $a_field_value) {
        global $ilDB;

        $ilDB->replace('xxco_data_values', array(
            'obj_id' => array('integer', $this->getId()),
            'field_name' => array('text', $a_field_name)
                ), array(
            'field_value' => array('text', $a_field_value)
                )
        );

        return true;
    }

    /**
     * Get array of input values
     */
    function getInputValues() {
        global $ilDB;

        $query = 'SELECT * FROM xxco_data_values WHERE obj_id = '
                . $ilDB->quote($this->getId(), 'integer');
        $res = $ilDB->query($query);

        $values = array();
        while ($row = $ilDB->fetchObject($res)) {
            $values[$row->field_name] = $row->field_value;
        }
        return $values;
    }

    /**
     * Delete
     *
     * @access public
     */
    public function doDelete() {
        global $ilDB;
        
        $query = "DELETE FROM xxco_data_settings " .
                "WHERE obj_id = " . $ilDB->quote($this->getId(), 'integer') . " ";
        $ilDB->manipulate($query);

        $query = "DELETE FROM xxco_data_values " .
                "WHERE obj_id = " . $ilDB->quote($this->getId(), 'integer') . " ";
        $ilDB->manipulate($query);
        return true;
    }
    
    /**
     * read settings
     *
     * @access public
     */
    public function doRead() {
        global $ilDB;
        
        $query = 'SELECT * FROM xxco_data_settings WHERE obj_id = '
                . $ilDB->quote($this->getId(), 'integer');

        $res = $ilDB->query($query);
        $row = $ilDB->fetchObject($res);
        
        if ($row) {
            $this->setAvailabilityType($row->availability_type);
            $this->setTypeId($row->type_id);
            $this->setInstructions($row->instructions);
            $this->setMetaDataXML($row->meta_data_xml);
            $this->setLPMode($row->lp_mode);
            $this->setLPThreshold($row->lp_threshold);
        }
    }

    /**
     * Do Cloning
     */
    function doCloneObject($new_obj, $a_target_id, $a_copy_id = null) {
        global $ilDB;
        
        //Settings filling
        $ilDB->insert('xxco_data_settings', array(
            'obj_id' => array('integer', $new_obj->getId()),
            'type_id' => array('integer', $this->getTypeId()),
            'availability_type' => array('integer', $this->getAvailabilityType()),
            'instructions' => array('text', $this->getInstructions()),
            'meta_data_xml' => array('text', $this->getMetaDataXML()),
            'lp_mode' => array('integer', $this->getLPMode()),
            'lp_threshold' => array('float', $this->getLPThreshold())
         ));
        //Value filling
        $values = $this->getInputValues();
        
        foreach($values as $it => $value){
            $ilDB->insert('xxco_data_values', array(
            'obj_id' => array('integer', $new_obj->getId()),
            'field_name' => array('text', $it),
            'field_value' => array('text', $value)
                )
        );
        }
    }

    function createToken($time) {
        $pre_token = rand(-100000, 100000);
        $token = $pre_token . $time;
        $token = md5($token);
        return $token;
    }

    function selectCurrentTimestamp() {
        global $ilDB;
        $query = "SELECT CURRENT_TIMESTAMP";
        $result = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($result);
        return $row;
    }

    function selectID() {
        global $ilDB;
        $query = "SELECT field_value FROM xxco_data_values WHERE obj_id = " . $ilDB->quote($this->getId(), 'integer');
        $result = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($result);
        return $row['field_value'];
    }

    function checkToken() {
        global $ilDB;

        $token = $_GET['token'];
        $query = "SELECT token FROM xxco_data_token WHERE token = " . $ilDB->quote($token, 'text');
        $result = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($result);

        if ($row) {
            return "1";
        } else {
            return "0";
        }
    }

    function getTimeToDelete() {
        global $ilDB;
        $query = "SELECT time_to_delete FROM xxco_data_types WHERE type_id = " . $ilDB->quote($this->getTypeId(), 'integer');
        $result = $ilDB->query($query);
        $row = $ilDB->fetchAssoc($result);
        return $row['time_to_delete'];
    }


    /**
     * get the learning progress mode
     */
    public function getLPMode() {
        return $this->lp_mode;
    }

    /**
     * set the learning progress mode
     */
    public function setLPMode($a_mode) {
        $this->lp_mode = $a_mode;
    }

    /**
     * get the learning progress mode
     */
    public function getLPThreshold() {
        return $this->lp_threshold;
    }

    /**
     * set the learning progress mode
     */
    public function setLPThreshold($a_threshold) {
        $this->lp_threshold = $a_threshold;
    }


    /**
     * Get all user ids with LP status completed
     *
     * @return array
     */
    public function getLPCompleted()
    {
        $this->plugin->includeClass('class.ilExternalContentLPStatus.php');
        return ilExternalContentLPStatus::getLPStatusDataFromDb($this->getId(), ilLPStatus::LP_STATUS_COMPLETED_NUM);
    }

    /**
     * Get all user ids with LP status not attempted
     *
     * @return array
     */
    public function getLPNotAttempted()
    {
        $this->plugin->includeClass('class.ilExternalContentLPStatus.php');
        return ilExternalContentLPStatus::getLPStatusDataFromDb($this->getId(), ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM);
    }

    /**
     * Get all user ids with LP status failed
     *
     * @return array
     */
    public function getLPFailed()
    {
        $this->plugin->includeClass('class.ilExternalContentLPStatus.php');
        return ilExternalContentLPStatus::getLPStatusDataFromDb($this->getId(), ilLPStatus::LP_STATUS_FAILED_NUM);
    }

    /**
     * Get all user ids with LP status in progress
     *
     * @return array
     */
    public function getLPInProgress()
    {
        $this->plugin->includeClass('class.ilExternalContentLPStatus.php');
        return ilExternalContentLPStatus::getLPStatusDataFromDb($this->getId(), ilLPStatus::LP_STATUS_IN_PROGRESS_NUM);
    }

    /**
     * Get current status for given user
     *
     * @param int $a_user_id
     * @return int
     */
    public function getLPStatusForUser($a_user_id)
    {
        $this->plugin->includeClass('class.ilExternalContentLPStatus.php');
        return ilExternalContentLPStatus::getLPDataForUserFromDb($this->getId(), $a_user_id);
    }

    /**
     * Track access for learning progress
     */
    public function trackAccess()
    {
        global $ilUser;

        // track access for learning progress
        if ($ilUser->getId() != ANONYMOUS_USER_ID and $this->getLPMode() == self::LP_ACTIVE)
        {
            $this->plugin->includeClass('class.ilExternalContentLPStatus.php');
            ilExternalContentLPStatus::trackAccess($ilUser->getId(),$this->getId(), $this->getRefId());
        }
    }
}

?>
