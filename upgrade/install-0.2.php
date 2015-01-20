<?php

function upgrade_module_0_2($module) {
    Configuration::deleteByName('SWIPP_MAX_AMOUNT');
    Configuration::deleteByName('SWIPP_SHOW_CONVERTED');
    Configuration::deleteByName('SWIPP_SHOW_INVIOCE');
    $module->unregisterHook('header');
    return true;
}
