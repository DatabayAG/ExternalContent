<?php
// Copyright (c) 2020 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg, GPLv3, see LICENSE

trait ilExternalContentGUIBase
{
    /** @var ilObjUser */
    protected $user;

    /** @var  ilAccessHandler $access */
    protected $access;

    /** @var ilCtrl $ctrl */
    protected $ctrl;

    /** @var  ilLanguage $lng */
    protected $lng;

    /** @var ilTabsGUI */
    protected $tabs;

    /** @var  ilToolbarGUI $toolbar */
    protected $toolbar;

    /** @var ilTemplate $tpl */
    protected $tpl;


    protected function initGlobals()
    {
        global $DIC;
        $this->user = $DIC->user();
        $this->access = $DIC->access();
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->tabs = $DIC->tabs();
        $this->toolbar = $DIC->toolbar();
        $this->tpl = $DIC['tpl'];
    }
}