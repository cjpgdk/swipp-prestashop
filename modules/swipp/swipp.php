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

if (!defined('_PS_VERSION_'))
    exit;

class Swipp extends PaymentModule {

    public function __construct() {
        $this->name = 'swipp';
        $this->tab = 'payments_gateways';
        $this->version = '0.1.2';
        $this->author = 'CMJ Scripter';
        $this->controllers = array('payment', 'validation');

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->SWIPP_PHONE = Configuration::get('SWIPP_PHONE');
        $this->SWIPP_OWNER = Configuration::get('SWIPP_OWNER');

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = 'Swipp';
        $this->description = $this->l('Accept payments for your products via swipp transfer.');
        $this->confirmUninstall = $this->l('Are you sure about removing these details?');
        if (!isset($this->SWIPP_PHONE) || empty($this->SWIPP_PHONE))
            $this->warning = $this->l('Swipp phone number must be configured before using this module.');
        if (!isset($this->SWIPP_OWNER) || empty($this->SWIPP_OWNER))
            $this->warning .= $this->l('Swipp owner/user must be configured before using this module.');

        $this->extra_mail_vars = array(
            '{swipp_owner}' => $this->SWIPP_OWNER,
            '{swipp_phone}' => $this->SWIPP_PHONE,
        );
    }

    public function hookPaymentReturn($params) {
        if (!$this->active)
            return;

        $state = $params['objOrder']->getCurrentState();
        if ($state == Configuration::get('SWIPP_PAYMENT_STATE') || $state == Configuration::get('PS_OS_OUTOFSTOCK')) {
            $this->smarty->assign(array(
                'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
                'swippOwner' => $this->SWIPP_OWNER,
                'swippPhone' => $this->SWIPP_PHONE,
                'status' => 'ok',
                'id_order' => $params['objOrder']->id
            ));
            if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference)) {
                $this->smarty->assign('reference', $params['objOrder']->reference);
            }
        } else {
            $this->smarty->assign('status', 'failed');
        }
        if (version_compare(_PS_VERSION_, '1.6.0.0', '>=')) {
            return $this->display(__FILE__, 'payment_return_1.6.tpl');
        } elseif (version_compare(_PS_VERSION_, '1.5.0.0', '>=')) {
            return $this->display(__FILE__, 'payment_return.tpl');
        }
    }

    public function hookPayment($params) {
        if (!$this->active)
            return;
        if (!$this->checkCurrency($params['cart']))
            return;

        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_bw' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
        ));
        if (version_compare(_PS_VERSION_, '1.6.0.0', '>=')) {
            return $this->display(__FILE__, 'payment_1.6.tpl');
        } elseif (version_compare(_PS_VERSION_, '1.5.0.0', '>=')) {
            return $this->display(__FILE__, 'payment.tpl');
        }
    }

    public function hookPDFInvoice($params) {
        $object = $params['object'];
        $order = new Order(intval($object->id_order));
        if (strtolower($order->payment) == strtolower($this->name)) {
            $html = str_replace('[NL]', '<br />', sprintf($this->l('You have chosen to pay with swipp[NL]use the following infomation to compleate your order.[NL]Account name: %s[NL]Account phone: %s'), $this->SWIPP_OWNER, $this->SWIPP_PHONE));
            return $html;
        }
        return "";
    }

    public function install() {

        $SwippOrder = new OrderState();
        $SwippOrder->color = "lightblue";
        $SwippOrder->logable = 0;
        $SwippOrder->invoice = 0;
        $SwippOrder->hidden = 0;
        $SwippOrder->send_email = 1;
        $SwippOrder->shipped = 0;
        $SwippOrder->paid = 0;
        $SwippOrder->delivery = 0;
        $SwippOrder->deleted = 0;
        foreach (Language::getLanguages(false) as $lang) {
            $SwippOrder->name[(int) $lang['id_lang']] = 'Awaiting Swipp payment';
        }
        foreach (Language::getLanguages(false) as $lang) {
            $SwippOrder->template[(int) $lang['id_lang']] = 'swipp_payment';
        }

        if (!$SwippOrder->add() ||
                !parent::install() ||
                !$this->registerHook('payment') ||
                !$this->registerHook('paymentReturn') ||
                !$this->registerHook('PDFInvoice') ||
                !$this->registerHook('header'))
            return false;

        Configuration::updateValue('SWIPP_PAYMENT_STATE', $SwippOrder->id);

        copy(dirname(__FILE__) . '/logo.gif', _PS_IMG_DIR_ . 'os/' . $SwippOrder->id . '.gif');
        return true;
    }

    public function uninstall() {
        if (!Configuration::deleteByName('SWIPP_OWNER') ||
                !Configuration::deleteByName('SWIPP_PHONE') ||
                !parent::uninstall())
            return false;

        // we do not remove the from the system if ordeers are placed, we only mark it as deleted
        $SwippOrer = new OrderState(Configuration::updateValue('SWIPP_PAYMENT_STATE'));
        $SwippOrer->deleted = 1;
        $SwippOrer->update();
        return true;
    }

    public function hookHeader($param) {
        $this->context->controller->addCSS(($this->_path) . 'css/swipp.css', 'all');
    }

    private function _postValidation() {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('SWIPP_PHONE'))
                $this->_postErrors[] = $this->l('Swipp phone number are required.');
            elseif (!Tools::getValue('SWIPP_OWNER'))
                $this->_postErrors[] = $this->l('Swipp owner/user is required.');
        }
    }

    private function _postProcess() {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('SWIPP_PHONE', Tools::getValue('SWIPP_PHONE'));
            Configuration::updateValue('SWIPP_OWNER', Tools::getValue('SWIPP_OWNER'));
        }
        $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    public function getContent() {
        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!isset($this->_postErrors) || !count($this->_postErrors))
                $this->_postProcess();
            else
                foreach ($this->_postErrors as $err)
                    $this->_html .= $this->displayError($err);
        } else
            $this->_html .= '<br />';

        $this->_html .= $this->display(__FILE__, 'infos.tpl');
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    public function checkCurrency($cart) {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module))
            foreach ($currencies_module as $currency_module)
                if ($currency_order->id == $currency_module['id_currency'])
                    return true;
        return false;
    }

    public function renderForm() {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Swipp details'),
                    'icon' => 'icon-envelope'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Swipp owner/user'),
                        'name' => 'SWIPP_OWNER',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Swipp Phone'),
                        'name' => 'SWIPP_PHONE',
                        'desc' => $this->l('The phone registred with swipp')
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues() {
        return array(
            'SWIPP_OWNER' => Tools::getValue('SWIPP_OWNER', Configuration::get('SWIPP_OWNER')),
            'SWIPP_PHONE' => Tools::getValue('SWIPP_PHONE', Configuration::get('SWIPP_PHONE')),
        );
    }

}
