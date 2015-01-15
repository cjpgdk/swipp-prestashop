<?php

/*
 * Copyright (C) 2014-2015  Christian M. Jensen
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @author Christian M. Jensen <christian@cmjscripter.net>
 *  @copyright 2014 Christian M. Jensen
 *  @license http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3
 */


include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../header.php');
if (!class_exists('swipp', false))
    include(dirname(__FILE__) . '/swipp.php');

$swipp = new swipp();

if ($cart->id_customer == 0 OR $cart->id_address_delivery == 0 OR $cart->id_address_invoice == 0 OR ! $swipp->active)
    Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');

// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
if (method_exists('Module', 'getPaymentModules')) {
    $authorized = false;
    foreach (Module::getPaymentModules() as $module)
        if ($module['name'] == 'bankwire') {
            $authorized = true;
            break;
        }
    if (!$authorized)
        die(Tools::displayError('This payment method is not available.'));
}

$customer = new Customer((int) $cart->id_customer);

if (!Validate::isLoadedObject($customer))
    Tools::redirectLink(__PS_BASE_URI__ . 'order.php?step=1');

$currency = new Currency(Tools::getValue('currency_payement', false) ? Tools::getValue('currency_payement') : $cookie->id_currency);
$total = (float) ($cart->getOrderTotal(true, 3));

$mailVars = $swipp->extra_mail_vars;

$SWIPP_ORDERSTATEID = Configuration::get("SWIPP_ORDERSTATEID");
if(!$SWIPP_ORDERSTATEID || $SWIPP_ORDERSTATEID<0)
    $SWIPP_ORDERSTATEID = Configuration::get('SWIPP_PAYMENT_STATE');

$swipp->validateOrder($cart->id, ($SWIPP_ORDERSTATEID > 0 ? $SWIPP_ORDERSTATEID : Configuration::get('PS_OS_BANKWIRE')), $total, $swipp->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
$order = new Order($swipp->currentOrder);
Tools::redirectLink(__PS_BASE_URI__ . 'order-confirmation.php?id_cart=' . $cart->id . '&id_module=' . $swipp->id . '&id_order=' . $swipp->currentOrder . '&key=' . $customer->secure_key);
