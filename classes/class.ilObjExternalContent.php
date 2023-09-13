<?php
/**
 * Copyright (c) 2015 Institut für Lern-Innovation, Friedrich-Alexander-Universität Erlangen-Nürnberg
 * GPLv2, see LICENSE 
 */

/**
 * External Content plugin: base class for repository object
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @author Jesus Copado <jesus.copado@fim.uni-erlangen.de>
 * @version $Id$
 */
class ilObjExternalContent extends ilObjectPlugin implements ilExternalContent, ilLPStatusPluginInterface
{
    /**
     * @var ilExternalContentSettings
     */
    protected $settings;

    /**
     * Array with context information, will be initialized in getContext()
     * @var array
     */
    protected $context;

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



    /**
     * Get type.
     * The initType() method must set the same ID as the plugin ID.
     *
     * @access	public
     */
    protected function initType(): void
    {
        $this->setType('xxco');
    }


    /**
     * Get the content settings object
     * @return ilExternalContentSettings
     */
    public function getSettings()
    {
        if (!isset($this->settings)) {
            $this->settings = new ilExternalContentSettings();
            $this->settings->readByObjId($this->getId());
        }
        return $this->settings;
    }


    /**
     * Get the content type definition
     * @return ilExternalContentType
     */
    public function getTypeDef()
    {
        return $this->getSettings()->getTypeDef();
    }

    /**
     * Get the fields object
     * @return ilExternalContentRenderer
     */
    public function getRenderer() {
        return new ilExternalContentRenderer($this);
    }

    /**
     * Get online status
     */
    public function getOnline() {
        switch ($this->getSettings()->getAvailabilityType()) {
            case ilExternalContentSettings::ACTIVATION_UNLIMITED:
                return true;

            case ilExternalContentSettings::ACTIVATION_OFFLINE:
            default:
                return false;
        }
    }

    /**
     * set a return url for coming back from the content
     * 
     * @param string	$a_return_url return url
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
     * get the url for receiving lti outcomes
     * @return string
     */
    public function getResultUrl(){
        return ILIAS_HTTP_PATH . "/Customizing/global/plugins/Services/Repository/RepositoryObject/ExternalContent/result.php"
            . '?client_id='.CLIENT_ID;
    }

    /**
     * set a suffix provided by the goto link
     *
     * @param string	$a_goto_suffix goto suffix
     */
    public function setGotoSuffix($a_goto_suffix) {
        $this->goto_suffix = (string) $a_goto_suffix;
    }

    /**
     * get the suffix provided by the goto link
     *
     * @return string	goto suffix
     */
    public function getGotoSuffix() {
        return (string) $this->goto_suffix;
    }



    /**
     * get info about the context in which the link is used
     * 
     * The most outer matching course or group is used
     * If not found the most inner category or root node is used
     * 
     * @param	array	$a_valid_types list of valid types
     * @return 	array	context array ("ref_id", "title", "type")
     */
    public function getContext($a_valid_types = array('crs', 'grp', 'cat', 'root'))
    {
        global $DIC;
        $tree  = $DIC->repositoryTree();

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
     * Create
     */
    protected function doCreate(bool $clone_mode = false): void
    {
        $this->getSettings()->setObjId($this->getId());
        $this->getSettings()->save();
    }

    /**
     * Read
     */
    protected function doRead(): void
    {
        // nothing special
    }

    /**
     * Update function
     */
    protected function doUpdate(): void
    {
        $this->getSettings()->save();
    }

    /**
     * Delete
     */
    protected function doDelete(): void
    {
        $this->getSettings()->delete();
    }
    

    /**
     * Do Cloning
     * @param self $new_obj
     * @param int $a_target_id
     * @param int $a_copy_id
     */
    protected function doCloneObject(ilObject2 $new_obj, int $a_target_id, ?int $a_copy_id = null): void
    {
        $this->getSettings()->clone($new_obj->getSettings());
    }


    /**
     * Get all user ids with LP status completed
     *
     * @return array
     */
    public function getLPCompleted(): array
    {
        return ilExternalContentLPStatus::getLPStatusDataFromDb($this->getId(), ilLPStatus::LP_STATUS_COMPLETED_NUM);
    }

    /**
     * Get all user ids with LP status not attempted
     *
     * @return array
     */
    public function getLPNotAttempted(): array
    {
        return ilExternalContentLPStatus::getLPStatusDataFromDb($this->getId(), ilLPStatus::LP_STATUS_NOT_ATTEMPTED_NUM);
    }

    /**
     * Get all user ids with LP status failed
     *
     * @return array
     */
    public function getLPFailed(): array
    {
        return ilExternalContentLPStatus::getLPStatusDataFromDb($this->getId(), ilLPStatus::LP_STATUS_FAILED_NUM);
    }

    /**
     * Get all user ids with LP status in progress
     *
     * @return array
     */
    public function getLPInProgress(): array
    {
        return ilExternalContentLPStatus::getLPStatusDataFromDb($this->getId(), ilLPStatus::LP_STATUS_IN_PROGRESS_NUM);
    }

    /**
     * Get current status for given user
     *
     * @param int $a_user_id
     * @return int
     */
    public function getLPStatusForUser(int $a_user_id): int
    {
        return ilExternalContentLPStatus::getLPDataForUserFromDb($this->getId(), $a_user_id);
    }

    /**
     * Track access for learning progress
     */
    public function trackAccess()
    {
        global $DIC;

        // track access for learning progress
        if ($DIC->user()->getId() != ANONYMOUS_USER_ID and $this->settings->getLPMode() == ilExternalContentSettings::LP_ACTIVE)
        {
            ilExternalContentLPStatus::trackAccess($DIC->user()->getId(),$this->getId(), $this->getRefId());
        }
    }
}
