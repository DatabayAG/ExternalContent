<?php

require_once(__DIR__ . '/interface.ilExternalContent.php');
require_once(__DIR__ . '/class.ilExternalContentPlugin.php');
require_once(__DIR__ . '/class.ilExternalContentType.php');
require_once(__DIR__ . '/class.ilExternalContentEncodings.php');
require_once(__DIR__ . '/class.ilExternalContentUserData.php');

/**
 * Output processor
 * Generates output by processing templates and filling fields recursively
 */
class ilExternalContentRenderer
{

    /**
     * @var ilExternalContentPlugin
     */
    protected $plugin;

    /**
     * @var ilExternalContent
     */
    protected $content;

    /**
     * @var ilExternalContentType
     */
    protected $typedef;

    /**
     * @var ilExternalContentSettings;
     */
    protected $settings;


    /** @var ilExternalContentUserData */
    protected $userData;


    /** @var array */
    protected $fields;


    /**
     * Constructor
     * @param ilExternalContent $content
     */
    public function __construct(ilExternalContent $content)
    {
        $this->plugin = ilExternalContentPlugin::getInstance();
        $this->settings = $content->getSettings();
        $this->typedef = $this->settings->getTypeDef();
        $this->userData = ilExternalContentUserData::create($this->plugin);
    }

    /**
     * Render the output by filling the template
     * @return string
     */
    public function render()
    {
        return $this->fillTemplateRec($this->typedef->getTemplate());
    }


    /**
     * Fill a template recursively with field values
     * Placeholders are like {FIELD_NAME}
     * Replacement is case insensitive
     *
     * @param    string  $a_template template
     * @param    int     $a_maxdepth maximum recursion depth (default 100, stops at 0)
     * @return string
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
            'ILIAS_REMOTE_ADDR',
            'ILIAS_TIME',
            'ILIAS_TIMESTAMP',
            'ILIAS_SESSION_ID',
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
        $ilias_names = array_merge($ilias_names, $this->userData->getFieldNames());

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
        $object_values = $this->settings->getInputValues();

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
                        $field['field_value'] = $this->userData->getLtiParams($field['field_value']);
                        break;
                }
            }

            $this->fields[$field['field_name']] = $field;
        }
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

            default:
                $value = '';
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
     * fill an ILIAS field
     * @param $a_field
     * @return mixed
     */
    private function fillIliasField($a_field) {
        global $ilias, $ilUser, $ilSetting, $ilAccess, $ilClientIniFile;


        $value = "";
        switch ($a_field['field_name']) {
            // object information

            case "ILIAS_REF_ID":
                $value = $this->content->getRefId();
                break;

            case "ILIAS_TITLE":
                $value = $this->content->getTitle();
                break;

            case "ILIAS_DESCRIPTION":
                $value = $this->content->getDescription();
                break;

            case "ILIAS_INSTRUCTIONS":
                $value = $this->settings->getInstructions();
                break;

            // object context

            case "ILIAS_CONTEXT_ID":
                $context = $this->content->getContext();
                $value = $context['id'];
                break;

            case "ILIAS_CONTEXT_TYPE":
                $context = $this->content->getContext();
                $value = $context['type'];
                break;

            case "ILIAS_CONTEXT_TITLE":
                $context = $this->content->getContext();
                $value = $context['title'];
                break;

            // call-time imformation

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

            case "ILIAS_RESULT_ID":
                if ($this->settings->getLPMode() == ilExternalContentSettings::LP_ACTIVE)
                {
                    $this->plugin->includeClass('class.ilExternalContentResult.php');
                    $result = ilExternalContentResult::getByKeys($this->content->getId(), $ilUser->getId(), true);
                    $value = $result->id;
                }
                else
                {
                    $value= "";
                }
                break;

            case "ILIAS_GOTO_SUFFIX":
                $value = $this->content->getGotoSuffix();
                break;

            case "ILIAS_GOTO_AUTOSTART":
                $value = ($this->content->getGotoSuffix() == 'autostart' ?  1 : 0);
                break;

            // service urls
            case "ILIAS_RETURN_URL":
                $value = $this->content->getReturnUrl();
                break;

            case "ILIAS_RESULT_URL":
                if ($this->settings->getLPMode() == ilExternalContentSettings::LP_ACTIVE)
                {
                    $value = $this->content->getResultUrl();
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
                $value = $ilAccess->checkAccess('write', '', $this->content->getRefId()) ? "1" : "0";
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
                foreach ($this->userData->getFieldValues() as $field_name => $field_value) {
                    if ($field_name == $a_field['field_name']) {
                        $value = $field_value;
                    }
                }
        }

        return $value;
    }
}