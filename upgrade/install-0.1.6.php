<?php

function upgrade_module_0_1_6($module) {
    Configuration::updateValue('SWIPP_SHOW_CONVERTED', 0);
    Configuration::updateValue('SWIPP_SHOW_INVIOCE', 0);
    return true;
}
