<?php

/**
 * Interface ilExternalContent
 * May be a repository object or a page content
 * This interface is used by ilExternalContentRenderer to render the output
 */
interface ilExternalContent {

    /**
     * Get the Settings object
     * @return ilExternalContentSettings
     */
    public function getSettings();

    /**
     * Get the id of the object
     * @return int
     */
    public function getId();

    /**
     * Get the ref id of the object
     * @return int
     */
    public function getRefId();

    /**
     * Get the title of the object
     * @return string
     */
    public function getTitle();

    /**
     * Get the description of the object
     * @return string
     */
    public function getDescription();

    /**
     * Get the data of a context object (e.g. group or course)
     * @return array [id => int, title => string, type => string]
     */
    public function getContext();

    /**
     * Get the suffix provided with a goto link
     * @return string
     */
    public function getGotoSuffix();

    /**
     * Get the URL to which an external content may link back
     * @return string
     */
    public function getReturnUrl();

    /**
     * Get the URL of the LTI outcome service
     * @return string
     */
    public function getResultUrl();

}
