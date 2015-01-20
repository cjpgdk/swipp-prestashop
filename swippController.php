<?php

/*
 * Copyright (C) 2105  Christian M. Jensen
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

/**
 * @since 1.5.0
 */
class ModuleSwippController extends ModuleController {

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess() {
        $this->display_column_left = false;
        if ($this->process == 'validation')
            $this->processValidation();
    }

    /**
     * Validate bankwire payment
     */
    public function processValidation() {
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
            Tools::redirect('index.php?controller=order&step=1');

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module)
            if ($module['name'] == 'swipp') {
                $authorized = true;
                break;
            }
        if (!$authorized)
            die(Tools::displayError('This payment method is not available.'));

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer))
            Tools::redirect('index.php?controller=order&step=1');

        $currency = Tools::getValue('currency_payement', false) ? new Currency(Tools::getValue('currency_payement')) : Context::getContext()->currency;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
        
        $mailVars = $this->module->extra_mail_vars;
        
        $state = (int) Configuration::get("SWIPP_PAYMENT_STATE");
        if (!$state || (int) $state <= 0)
            $state = Configuration::get('PS_OS_BANKWIRE');
        
        $this->module->validateOrder($cart->id, $state, $total, $this->module->displayName, NULL, $mailVars, (int) $currency->id, false, $customer->secure_key);
        Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module=' . $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
    }

    /**
     * @see FrontController::initContent()
     */
    public function initContent() {
        parent::initContent();

        if ($this->process == 'payment')
            $this->assignPaymentExecution();
    }

    /**
     * Assign bankwire payment template
     */
    public function assignPaymentExecution() {
        $cart = $this->context->cart;
        if (!$this->module->checkCurrency($cart))
            Tools::redirect('index.php?controller=order');

        $this->context->smarty->assign(array(
            'nbProducts' => $cart->nbProducts(),
            'cust_currency' => $cart->id_currency,
            'currencies' => $this->module->getCurrency((int) $cart->id_currency),
            'total' => $cart->getOrderTotal(true, Cart::BOTH),
            'this_path' => $this->module->getPathUri(),
            'this_path_bw' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/'
        ));

        $this->setTemplate('views/templates/front/payment_execution.tpl');

    }

}
