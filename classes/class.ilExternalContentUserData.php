<?php
/**
 * Copyright (c) 2013 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg
 * GPLv2, see LICENSE
 */


/**
 * ExternalContent plugin: configuration GUI
 */
class ilExternalContentUserData
{
    /** @var ilLanguage */
    protected $lng;

    /**
     * @var ilExternalContentPlugin
     */
    protected $plugin;

    /**
     * @var array
     */
    protected $elements;


    /**
     * @var array
     */
    protected $values;


    /**
     * Get a new instance
     * @param $plugin
     * @return ilExternalContentUserData
     */
    public static function factory($plugin) {
        return new self;
    }

    /**
     * ilExternalContentFieldLtiUserData constructor.
     * @param $plugin
     */
    public function __construct($plugin) {
        global $lng;

        $this->plugin = $plugin;
        $this->lng = $lng;
    }

    /**
     * get the item that cam be added to a property form
     * @param string $a_value
     * @return ilSubEnabledFormPropertyGUI
     */
    public function getFormItem($a_value) {
        global $ilUser;

        $selected = explode(',', $a_value);

        $item = new ilCheckboxInputGUI($this->plugin->txt('user_data_label'), 'user_data_checkbox');
        $item->setInfo('user_data_info');

        $labels = array(
            'ILIAS_USER_GENDER'=> $this->lng->txt('birthday'),
            'ILIAS_USER_FIRSTNAME' => $this->lng->txt('firstname'),
            'ILIAS_USER_LASTNAME' => $this->lng->txt('lastname'),
            'ILIAS_USER_TITLE' => $this->lng->txt('person_title'),
            'ILIAS_USER_FULLNAME' => $this->lng->txt('fullname'),
            'ILIAS_USER_BIRTHDAY' => $this->lng->txt('birthday'),
            'ILIAS_USER_INSTITUTION' => $this->lng->txt('institution'),
            'ILIAS_USER_DEPARTMENT' => $this->lng->txt('department'),
            'ILIAS_USER_STREET' => $this->lng->txt('street'),
            'ILIAS_USER_CITY' => $this->lng->txt('city'),
            'ILIAS_USER_ZIPCODE' => $this->lng->txt('zipcode'),
            'ILIAS_USER_COUNTRY' => $this->lng->txt('country'),
            'ILIAS_USER_COUNTRY_SELECT' => $this->lng->txt('sel_country'),
            'ILIAS_USER_PHONE_OFFICE' => $this->lng->txt('phone_office'),
            'ILIAS_USER_PHONE_HOME' => $this->lng->txt('phone_home'),
            'ILIAS_USER_PHONE_MOBILE' => $this->lng->txt('phone_mobile'),
            'ILIAS_USER_FAX' => $this->lng->txt('fax'),
            'ILIAS_USER_EMAIL' => $this->lng->txt('email'),
            //'ILIAS_USER_EMAIL_SECOND' => $this->lng->txt('email'),
            'ILIAS_USER_HOBBY' => $this->lng->txt('hobby'),
            'ILIAS_USER_REFERRAL_COMMENT' => $this->lng->txt('referral_comment'),
            'ILIAS_USER_MATRICULATION' => $this->lng->txt('matriculation'),
            //'ILIAS_USER_LATITUDE' => $this->lng->txt('latitude'),
            //'ILIAS_USER_LONGITUDE' => $this->lng->txt('longitude'),
            'ILIAS_USER_IMAGE' => $this->lng->txt('personal_picture'),
            'ILIAS_USER_LANG' => $this->lng->txt('language'),
        );

        foreach ($ilUser->getUserDefinedData() as $field => $value) {
            $labels['ILIAS_UDF_' . strtoupper(ilUtil::getASCIIFilename($field))] = $field;
        }

        $one_checked = false;
        foreach ($labels as $field_name => $title) {
            $subitem = new ilCheckboxInputGUI($title, $field_name);
            if (in_array($field_name, $selected)) {
                $subitem->setChecked(true);
                $one_checked = true;
            }
            $item->addSubItem($subitem);
            $item->setChecked($one_checked);
        }

        return $item;
    }

    /**
     * Get a submitted form value
     * @param ilPropertyFormGUI $form
     * @return string
     */
    public function getFormValue($form) {

        $selected = array();
        if ($form->getInput('user_data_checkbox')) {
            foreach ($this->getElements() as $field_name => $lti_name) {
                if ($form->getInput($field_name))  {
                    $selected[] = $field_name;
                }
            }
        }
        return implode(',', $selected);
    }


    /**
     * Get the values for template processing
     * @return array()
     */
    public function getElementValues() {
        global $ilUser;

        if (!isset($this->values)) {
            $this->values = array(
                'ILIAS_USER_GENDER'=> $ilUser->getGender(),
                'ILIAS_USER_FIRSTNAME' => $ilUser->getFirstname(),
                'ILIAS_USER_LASTNAME' => $ilUser->getLastname(),
                'ILIAS_USER_TITLE' => $ilUser->getTitle(),
                'ILIAS_USER_FULLNAME' => $ilUser->getFullname(),
                'ILIAS_USER_BIRTHDAY' => $ilUser->getBirthday(),
                'ILIAS_USER_INSTITUTION' => $ilUser->getInstitution(),
                'ILIAS_USER_DEPARTMENT' =>  $ilUser->getDepartment(),
                'ILIAS_USER_STREET' => $ilUser->getStreet(),
                'ILIAS_USER_CITY' => $ilUser->getCity(),
                'ILIAS_USER_ZIPCODE' => $ilUser->getZipcode(),
                'ILIAS_USER_COUNTRY' => $ilUser->getCountry(),
                'ILIAS_USER_COUNTRY_SELECT' => $ilUser->getSelectedCountry(),
                'ILIAS_USER_PHONE_OFFICE' => $ilUser->getPhoneOffice(),
                'ILIAS_USER_PHONE_HOME' => $ilUser->getPhoneHome(),
                'ILIAS_USER_PHONE_MOBILE' => $ilUser->getPhoneMobile(),
                'ILIAS_USER_FAX' => $ilUser->getFax(),
                'ILIAS_USER_EMAIL' => $ilUser->getEmail(),
                //'ILIAS_USER_EMAIL_SECOND' => $ilUser->getEmail(),
                'ILIAS_USER_HOBBY' => $ilUser->getHobby(),
                'ILIAS_USER_REFERRAL_COMMENT' => $ilUser->getComment(),
                'ILIAS_USER_MATRICULATION' => $ilUser->getMatriculation(),
                //'ILIAS_USER_LATITUDE' => '',
                //'ILIAS_USER_LONGITUDE' => '',
                'ILIAS_USER_IMAGE' => ILIAS_HTTP_PATH . "/" . $ilUser->getPersonalPicturePath("small"),
                'ILIAS_USER_LANG' => $ilUser->getLanguage()
            );

            foreach ($ilUser->getUserDefinedData() as $field => $value) {
                $this->values['ILIAS_UDF_' . strtoupper(ilUtil::getASCIIFilename($field))] = $value;
            }

        }

        return $this->values;
    }

    /**
     * Get the ILIAS handled by this field
     * @return array field_name => lti_param
     */
    public function getElements() {
        global $ilUser;

        if (!isset($this->elements)) {
            $this->elements = array(
                'ILIAS_USER_GENDER'=> 'ilias_user_gender',
                'ILIAS_USER_FIRSTNAME' => 'lis_person_name_given',
                'ILIAS_USER_LASTNAME' => 'lis_person_name_family',
                'ILIAS_USER_TITLE' => 'lis_person_name_prefix',
                'ILIAS_USER_FULLNAME' => 'lis_person_name_full',
                'ILIAS_USER_BIRTHDAY' => 'ilias_user_birthday',
                'ILIAS_USER_INSTITUTION' => 'ilias_user_institution',
                'ILIAS_USER_DEPARTMENT' => 'ilias_user_department',
                'ILIAS_USER_STREET' => 'lis_person_address_street1',
                'ILIAS_USER_CITY' => 'lis_person_address_locality',
                'ILIAS_USER_ZIPCODE' => 'lis_person_address_postcode',
                'ILIAS_USER_COUNTRY' => 'lis_person_address_country',
                'ILIAS_USER_COUNTRY_SELECT' => 'lis_person_address_country',
                'ILIAS_USER_PHONE_OFFICE' => 'lis_person_phone_work',
                'ILIAS_USER_PHONE_HOME' => 'lis_person_phone_home',
                'ILIAS_USER_PHONE_MOBILE' => 'lis_person_phone_mobile',
                'ILIAS_USER_FAX' => 'ilias_user_fax',
                'ILIAS_USER_EMAIL' => 'lis_person_contact_email_primary',
                //'ILIAS_USER_EMAIL_SECOND' => 'lis_person_email_personal',
                'ILIAS_USER_HOBBY' => 'ilias_user_hobby',
                'ILIAS_USER_REFERRAL_COMMENT' => 'ilias_user_referral_comment',
                'ILIAS_USER_MATRICULATION' => 'ilias_user_matriculation',
                //'ILIAS_USER_LATITUDE' => 'ilias_user_latitude',
                //'ILIAS_USER_LONGITUDE' => 'ilias_user_longitude',
                'ILIAS_USER_IMAGE' => 'user_image',
                'ILIAS_USER_LANG' => 'launch_presentation_locale',
            );

            foreach ($ilUser->getUserDefinedData() as $field => $value) {
                $this->elements['ILIAS_UDF_' . strtoupper(ilUtil::getASCIIFilename($field))] = strtolower(ilUtil::getASCIIFilename($field));
            }
        }

        return $this->elements;
    }

    /**
     * Gat the template values based on a value
     * @param string $a_value   list of selected fields
     * @return array
     */
    public function getTemplateValues($a_value)
    {
        $selected = explode(',', $a_value);

        $params = array();
        foreach ($this->getElements() as $field_name => $lti_name) {
            if (in_array($field_name, $selected)) {
                $params[$lti_name] = $field_name;
            }
        }
        return $params;
    }

}
