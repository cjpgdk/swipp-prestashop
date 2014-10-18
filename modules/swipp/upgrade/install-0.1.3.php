<?php
function upgrade_module_0_1_3($module) {
    Configuration::updateValue('SWIPP_MAX_AMOUNT', 3000.00);
    return true;
}
