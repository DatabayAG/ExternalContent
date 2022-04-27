<?php

/**
 * Text inpput without stripping html and without replacing "<" by "< "
 * This can be used for visible password fields
 */
class ilExternalContentRawtextInputGUI extends ilTextInputGUI
{
    public function stripSlashesAddSpaceFallback($a_str)
    {
        return ilUtil::stripSlashes($a_str, false);
    }

}