<?php

function upgrade_module_0_2($module) {
    Configuration::deleteByName('SWIPP_SHOW_INVIOCE');
	Configuration::deleteByName('SWIPP_SHOW_CONVERTED');
	Configuration::updateValue('SWIPP_ORDERSTATEID', (Configuration::get('SWIPP_PAYMENT_STATE'));
    Configuration::deleteByName('SWIPP_PAYMENT_STATE');
    return true;
}
