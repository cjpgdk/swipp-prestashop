<?php

function upgrade_module_0_2($module) {

    /*
     * new setting, select the oordre state were ordre is ok.!
     * Validate the current order state
     */
    $payment_states_accepted = (int) Configuration::get('SWIPP_PAYMENT_STATE');
    if ($payment_states_accepted !== NULL && $payment_states_accepted !== FALSE) {
        $_test_order_state = new OrderState((int) $payment_states_accepted);
        if ($_test_order_state->id != $payment_states_accepted || $_test_order_state->deleted) {
            /* bad status..., deleted or id has changed */
            $payment_states_accepted = Configuration::get('PS_OS_PAYMENT');
        } else {
            $payment_states_accepted .= "," . Configuration::get('PS_OS_PAYMENT');
        }
    } else {
        $payment_states_accepted = "," . Configuration::get('PS_OS_PAYMENT');
    }
    if (Configuration::hasKey('PS_OS_OUTOFSTOCK_UNPAID'))
        $payment_states_accepted .= "," . Configuration::get('PS_OS_OUTOFSTOCK_UNPAID');
    if (Configuration::hasKey('PS_OS_OUTOFSTOCK'))
        $payment_states_accepted .= "," . Configuration::get('PS_OS_OUTOFSTOCK');
    
    Configuration::updateValue('SWIPP_ORDER_STATES', $payment_states_accepted);

    return $module->registerHook('displayPaymentEU');
}
