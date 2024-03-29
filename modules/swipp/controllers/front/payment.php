<?php

/*
 * Copyright (C) 2014  Christian M. Jensen
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
class SwippPaymentModuleFrontController extends ModuleFrontController {

    public $ssl = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent() {
        parent::initContent();

        $cart = $this->context->cart;
        if (!$this->module->checkCurrency($cart))
            Tools::redirect('index.php?controller=order');

        $dkkC = new Currency(Currency::getIdByIsoCode('DKK'));
        $this->context->smarty->assign(array(
            'nbProducts' => $cart->nbProducts(),
            'cust_currency' => $cart->id_currency,
            'name_currency' => $dkkC->name,
            'id_currency_accepted' => $dkkC->id,
            'id_currency' => $cart->id_currency,
            'currencies' => $this->module->getCurrency((int) $cart->id_currency),
            'total' => $this->module->__getPriceDkk($cart),
            'this_path' => $this->module->getPathUri(),
            'this_path_bw' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/'
        ));

        $this->setTemplate('payment_execution.tpl');
    }

}
