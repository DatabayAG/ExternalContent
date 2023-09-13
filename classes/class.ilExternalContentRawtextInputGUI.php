<?php

/**
 * Text inpput without stripping html and without replacing "<" by "< "
 * This can be used for visible password fields
 */
class ilExternalContentRawtextInputGUI extends ilTextInputGUI
{
    public function stripSlashesAddSpaceFallback(string $a_str): string
    {
        return ilUtil::stripSlashes($a_str, false);
    }

}