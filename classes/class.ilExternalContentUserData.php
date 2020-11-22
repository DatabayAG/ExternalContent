<?php
/**
 * Copyright (c) 2020 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv2, see LICENSE
 */

require_once("./Services/User/classes/class.ilUserDefinedFields.php");

/**
 * ExternalContent plugin: user data
 * Provides the user profile fields for template processing
 * Provides a type or object settings form element for choosing available fields
 */
class ilExternalContentUserData
{
    const INDEX_LTI = 0;
    const INDEX_LABEL = 1;
    const INDEX_VALUE = 2;

    /** @var ilLanguage */
    protected $lng;
    
    /** @var ilObjUser */
    protected $user;

    /** @var ilExternalContentPlugin  */
    protected $plugin;

    /** @var array   FIELD_NAME => [ 0 => lti_name, 1 => label, 2 => value]  */
    protected $fields;


    /**
     * Get a new instance
     *
     * @param $plugin
     * @return ilExternalContentUserData
     */
    public static function create($plugin)
    {
        return new self($plugin);
    }


    /**
     * ilExternalContentFieldLtiUserData constructor.
     *
     * @param $plugin
     */
    public function __construct($plugin)
    {
        // todo: replace by DIC
        global $lng, $ilUser;

        $this->plugin = $plugin;
        $this->lng = $lng;
        $this->user = $ilUser;

        $this->initFields();
    }


    /**
     * Init the list of fields
     * todo: add second email, latitude, longitude
     */
    protected function initFields()
    {
        if (!isset($this->fields)) {
            $this->fields = array(
                'ILIAS_USER_GENDER' => array(
                    'ilias_user_gender',
                    $this->lng->txt('gender'),
                    $this->user->getGender()
                ),
                'ILIAS_USER_FIRSTNAME' => array(
                    'lis_person_name_given',
                    $this->lng->txt('firstname'),
                    $this->user->getFirstname()
                ),
                'ILIAS_USER_LASTNAME' => array(
                    'lis_person_name_family',
                    $this->lng->txt('lastname'),
                    $this->user->getLastname()
                ),
                'ILIAS_USER_TITLE' => array(
                    'lis_person_name_prefix',
                    $this->lng->txt('person_title'),
                    $this->user->getTitle()
                ),
                'ILIAS_USER_FULLNAME' => array(
                    'lis_person_name_full',
                    $this->lng->txt('fullname'),
                    $this->user->getFullname()
                ),
                'ILIAS_USER_BIRTHDAY' => array(
                    'ilias_user_birthday',
                    $this->lng->txt('birthday'),
                    $this->user->getBirthday()
                ),
                'ILIAS_USER_INSTITUTION' => array(
                    'ilias_user_institution',
                    $this->lng->txt('institution'),
                    $this->user->getInstitution()
                ),
                'ILIAS_USER_DEPARTMENT' => array(
                    'ilias_user_department',
                    $this->lng->txt('department'),
                    $this->user->getDepartment()
                ),
                'ILIAS_USER_STREET' => array(
                    'lis_person_address_street1',
                    $this->lng->txt('street'),
                    $this->user->getStreet()
                ),
                'ILIAS_USER_CITY' => array(
                    'lis_person_address_locality',
                    $this->lng->txt('city'),
                    $this->user->getCity()
                ),
                'ILIAS_USER_ZIPCODE' => array(
                    'lis_person_address_postcode',
                    $this->lng->txt('zipcode'),
                    $this->user->getZipcode()
                ),
                'ILIAS_USER_COUNTRY' => array(
                    'lis_person_address_country',
                    $this->lng->txt('country'),
                    $this->user->getCountry()
                ),
                'ILIAS_USER_COUNTRY_SELECT' => array(
                    'lis_person_address_country',
                    $this->lng->txt('sel_country'),
                    $this->user->getSelectedCountry()
                ),
                'ILIAS_USER_PHONE_OFFICE' => array(
                    'lis_person_phone_work',
                    $this->lng->txt('phone_office'),
                    $this->user->getPhoneOffice()
                ),
                'ILIAS_USER_PHONE_HOME' => array(
                    'lis_person_phone_home',
                    $this->lng->txt('phone_home'),
                    $this->user->getPhoneHome()
                ),
                'ILIAS_USER_PHONE_MOBILE' => array(
                    'lis_person_phone_mobile',
                    $this->lng->txt('phone_mobile'),
                    $this->user->getPhoneMobile()
                ),
                'ILIAS_USER_FAX' => array(
                    'ilias_user_fax',
                    $this->lng->txt('fax'),
                    $this->user->getFax()
                ),
                'ILIAS_USER_EMAIL' => array(
                    'lis_person_contact_email_primary',
                    $this->lng->txt('email'),
                    $this->user->getEmail()
                ),
//                'ILIAS_USER_EMAIL_SECOND' => array(
//                    'lis_person_email_personal',
//                    $this->lng->txt('email'),
//                    ''
//                ),
                'ILIAS_USER_HOBBY' => array(
                    'ilias_user_hobby',
                    $this->lng->txt('hobby'),
                    $this->user->getHobby()
                ),
                'ILIAS_USER_REFERRAL_COMMENT' => array(
                    'ilias_user_referral_comment',
                    $this->lng->txt('referral_comment'),
                    $this->user->getComment()
                ),
                'ILIAS_USER_MATRICULATION' => array(
                    'ilias_user_matriculation',
                    $this->lng->txt('matriculation'),
                    $this->user->getMatriculation()
                ),
//                'ILIAS_USER_LATITUDE' => array(
//                    'ilias_user_latitude',
//                    $this->lng->txt('latitude'),
//                    ''
//                ),
//                'ILIAS_USER_LONGITUDE' => array(
//                    'ilias_user_longitude',
//                    $this->lng->txt('longitude'),
//                    ''
//                ),
                'ILIAS_USER_IMAGE' => array(
                    'user_image',
                    $this->lng->txt('personal_picture'),
                    $this->getUserImageUrl()
                ),
                'ILIAS_USER_LANG' => array(
                    'launch_presentation_locale',
                    $this->lng->txt('language'),
                    $this->user->getLanguage()
                ),
            );

            $udfDef = ilUserDefinedFields::_getInstance();
            $data = $this->user->getUserDefinedData();
            foreach ($udfDef->getDefinitions() as $def) {
                $name = $this->getUdfFieldname($def['field_name']);
                $this->fields[$name] = array(
                    strtolower($name),
                    $def['field_name'],
                    $data['f_' . $def['field_id']]
                );
            }
        }
    }

    /**
     * Get a valid field name for a user defined field
     * @param $name
     * @return string|string[]
     */
    protected function getUdfFieldname($name) {
        $name = ilUtil::getASCIIFilename($name);
        $name = str_replace(' ', '', $name);
        return 'ILIAS_UDF_' . strtoupper($name);
    }
    
    /**
     * Get the item that cam be added to a property form
     *
     * @param string $title         field title
     * @param string $description   field description
     * @param string $value         comma separated list of selected elements
     * @return ilSubEnabledFormPropertyGUI
     */
    public function getFormItem($title = '', $description = '', $value = '')
    {
        $selected = explode(',', $value);
        $one_checked = false;

        $item = new ilCheckboxInputGUI($title, 'user_data_checkbox');
        $item->setInfo($description);

        foreach ($this->fields as $name => $field) {
            $subitem = new ilCheckboxInputGUI($field[self::INDEX_LABEL], $name);
            $subitem->setOptionTitle($field[self::INDEX_LTI]);
            if (in_array($name, $selected)) {
                $subitem->setChecked(true);
                $one_checked = true;
            }
            $item->addSubItem($subitem);
        }
        $item->setChecked($one_checked);
        return $item;
    }


    /**
     * Get the submitted value for the property form element
     *
     * @param ilPropertyFormGUI $form
     * @return string   comma separated list of selected fields
     */
    public function getFormValue($form)
    {
        $selected = array();
        if ($form->getInput('user_data_checkbox')) {
            foreach ($this->getFieldNames() as $name) {
                if ($form->getInput($name))  {
                    $selected[] = $name;
                }
            }
        }
        return implode(',', $selected);
    }


    /**
     * Get the names of the fields
     * These are provided as FIELDTYPE_ILIAS
     * @return string[]
     */
    public function getFieldNames()
    {
        return array_keys($this->fields);
    }


    /**
     * Get the user data values for template processing
     *
     * @return array
     */
    public function getFieldValues()
    {
        $values = array();
        foreach ($this->fields as $name => $field) {
            $values[$name] = $field[self::INDEX_VALUE];
        }
        return $values;
    }


    /**
     * Get the lti parameters for selected user data fields
     *
     * @param string $value   comma separated list of selected fields
     * @return array    name => value
     */
    public function getLtiParams($value)
    {
        $selected = explode(',', $value);

        $params = array();
        foreach ($this->fields as $name => $field) {
            if (in_array($name, $selected)) {
                $lti_name = $field[self::INDEX_LTI];
                $lti_value = $field[self::INDEX_VALUE];

                // lis_person_address_country:
                // keep existing freetext country if selected country is not set
                // otherwise the selected country (later in field list) wins
                if (empty($lti_value) && !empty($params[$lti_name])) {
                    continue;
                }

                $params[$lti_name] = $field[self::INDEX_VALUE];
            }
        }
        return $params;
    }

    /**
     * Get the url of a personal picture
     * @return mixed|string
     */
    protected function getUserImageUrl() {

        $path =  $this->user->getPersonalPicturePath("small");

        if (substr($path, 0,5) == 'data:') {
            return $path;
        }

        return ILIAS_HTTP_PATH . '/'. $path;
    }
}
