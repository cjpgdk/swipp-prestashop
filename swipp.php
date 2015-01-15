<?php

if (!defined('_CAN_LOAD_FILES_'))
    exit;
/*
 * Copyright (C) 2014,2015  Christian M. Jensen
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

class swipp extends PaymentModule {

    private $_html = '';
    private $_postErrors = array();
    private $_config;
    public $extra_mail_vars;

    public function __construct() {

        $this->name = 'swipp';
        $this->tab = 'payments_gateways';
        $this->version = '0.2';

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';


        $this->_config = Configuration::getMultiple(array('SWIPP_PHONE', 'SWIPP_OWNER', 'SWIPP_CUR_ENABLED'));

        parent::__construct();

        $this->displayName = $this->l('Swipp Transfer');
        $this->description = $this->l('Accept payments for your products via swipp transfer.');
        $this->confirmUninstall = $this->l('Are you sure about removing these details?');

        if (!isset($this->_config['SWIPP_PHONE']) || empty($this->_config['SWIPP_PHONE']))
            $this->warning = $this->l('Swipp phone number must be configured before using this module.');
        else if (!isset($this->_config['SWIPP_OWNER']) || empty($this->_config['SWIPP_OWNER']))
            $this->warning .= $this->l('Swipp owner/user must be configured before using this module.');
        else if (!Currency::getIdByIsoCode('DKK') && (!isset($_GET['uninstall']) || $_GET['uninstall'] != 'swipp') && (!isset($_GET['install']) || $_GET['install'] != 'swipp'))
            $this->warning = $this->l('Currency Danish Krone is not installed');
        else if (!sizeof(Currency::checkPaymentCurrencies($this->id)))
            $this->warning = $this->l('No currency set for this module');

        $this->extra_mail_vars = array(
            '{swipp_owner}' => @$this->_config['SWIPP_OWNER'],
            '{swipp_phone}' => @$this->_config['SWIPP_PHONE'],
        );
    }

    private function _displayForm() {

        $this->_html .= '<img src="../modules/swipp/swipp.jpg" style="float:left; margin-right:15px;"><b>'
                . $this->l('This module allows you to accept secure payments by swipp.') . '</b><br /><br />'
                . $this->l('If the client chooses to pay by swipp, the order\'s status will change to \'Waiting for Payment.\'') . '<br />'
                . $this->l('That said, you must manually confirm the order upon receiving the swipp.')
                . '<br /><br /><br />'
                . '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                       <fieldset>
                           <legend><img src="../img/admin/contact.gif" />' . $this->l('Swipp details') . '</legend>
                           <table border="0" width="500" cellpadding="0" cellspacing="0" id="form">
                               <tr><td colspan="2">' . $this->l('Please specify the Swipp account name phone number for customers to use') . '.<br /><br /></td></tr>
                               <tr><td width="130" style="height: 35px;">' . $this->l('Swipp owner/user') . '</td><td><input type="text" name="SWIPP_OWNER" value="' . htmlentities(Tools::getValue('SWIPP_OWNER', @$this->_config['SWIPP_OWNER']), ENT_COMPAT, 'UTF-8') . '" style="width: 300px;" /></td></tr>
                               <tr><td width="130" style="height: 35px;">' . $this->l('Swipp phone') . '</td><td><input type="text" name="SWIPP_PHONE" value="' . htmlentities(Tools::getValue('SWIPP_PHONE', @$this->_config['SWIPP_PHONE']), ENT_COMPAT, 'UTF-8') . '" style="width: 300px;" /></td></tr>
                               ' . $this->_displayFormCurrencies() . '
                               <tr><td colspan="2" align="center"><input class="button" name="btnSubmit" value="' . $this->l('Update settings') . '" type="submit" /></td></tr>
                           </table>
                       </fieldset>
                   </form>';
    }

    private function _displayFormCurrencies() {
        $html = "";
        $currencies_enabled = array();
        if (isset($this->_config["SWIPP_CUR_ENABLED"])) {
            $currencies_enabled = explode(',', $this->_config["SWIPP_CUR_ENABLED"]);
        }
        foreach (parent::getCurrency() as $value) {
            if ($value['active'] != 1)
                continue;
            $enabled = "";
            if (in_array($value['iso_code'], $currencies_enabled))
                $enabled = 'checked="checked"';
            $val = Configuration::get("SWIPP_CUR_{$value['iso_code']}");
            $html .= "<tr><td colspan='2'><hr/></td></tr><tr>"
                    . "<td width=\"130\" style=\"height: 35px;\">"
                    . sprintf($this->l('Currency \'%s\' max payment amount allowed'), $value['name'])
                    . "</td>"
                    . "<td>"
                    . "<input type=\"checkbox\" name=\"SWIPP_CUR_ENABLED[]\" value=\"{$value['iso_code']}\" {$enabled}> {$value['name']}<br>"
                    . "<input type=\"text\" name=\"SWIPP_CUR_{$value['iso_code']}\" value=\""
                    . htmlentities(Tools::getValue("SWIPP_CUR_{$value['iso_code']}", ((float) $val > 0 ? (float) $val : 0)), ENT_COMPAT, 'UTF-8')
                    . "\" style=\"width: 300px;\" />"
                    . "</td>"
                    . "</tr>";
        }
        return $html;
    }

    private function _postValidation() {
        if (isset($_POST['btnSubmit'])) {
            if (empty($_POST['SWIPP_OWNER']))
                $this->_postErrors[] = $this->l('Swipp owner/user is required.');
            if (empty($_POST['SWIPP_PHONE']))
                $this->_postErrors[] = $this->l('Swipp phone is required.');
            if (empty($_POST['SWIPP_CUR_ENABLED']))
                $this->_postErrors[] = $this->l('You havent selected a currency to allow through Swipp');
        }
    }

    private function _postProcess() {
        if (isset($_POST['btnSubmit'])) {
            Configuration::updateValue('SWIPP_OWNER', $_POST['SWIPP_OWNER']);
            Configuration::updateValue('SWIPP_PHONE', $_POST['SWIPP_PHONE']);
            Configuration::updateValue('SWIPP_CUR_ENABLED', implode(',', $_POST['SWIPP_CUR_ENABLED']));
            foreach ($_POST['SWIPP_CUR_ENABLED'] as $value) {
                Configuration::updateValue("SWIPP_CUR_{$value}", (float) Tools::getValue("SWIPP_CUR_{$value}", 0));
            }
        }
        $this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="' . $this->l('ok') . '" /> ' . $this->l('Settings updated') . '</div>';
    }

    public function getContent() {
        $this->_html = '<h2>' . $this->displayName . '</h2>';

        if (!Currency::getIdByIsoCode('DKK'))
            $this->_html .= '<div class="alert error">' . $this->l('Currency Danish Krone is not installed') . '</div>';
        if (!empty($_POST)) {
            $this->_postValidation();
            if (!sizeof($this->_postErrors))
                $this->_postProcess();
            else
                foreach ($this->_postErrors AS $err)
                    $this->_html .= '<div class="alert error">' . $err . '</div>';
        } else
            $this->_html .= '<br />';
        $this->_displayForm();
        return $this->_html;
    }

    public function hookPayment($params) {

        if (!$this->active)
            return;
        if (!Currency::getIdByIsoCode('DKK'))
            return;
        /* prestashop way */
//        if (!$this->_checkCurrency($params['cart']))
//            return;
        /* my way */
        $currencies_enabled = array();
        if (isset($this->_config["SWIPP_CUR_ENABLED"])) {
            $currencies_enabled = explode(',', $this->_config["SWIPP_CUR_ENABLED"]);
        }
        $cart_currency = Currency::getCurrencyInstance($params['cart']->id_currency);
        $cart_currency_iso_code = $cart_currency->iso_code;
        if (!in_array($cart_currency_iso_code, $currencies_enabled))
            return;
        unset($cart_currency);

        if ($params['cart']->getOrderTotal() > (float) Configuration::get("SWIPP_CUR_{$cart_currency_iso_code}"))
            return;

        global $smarty;

        $smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_ssl' => Tools::getHttpHost(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
        ));
        return $this->display(__FILE__, 'payment.tpl');
    }

    public function getCurrency($object = false, $active = 1) {
        if ($object)
            return parent::getCurrency($object, $active);

        $currencies_enabled = array();
        if (isset($this->_config["SWIPP_CUR_ENABLED"])) {
            $currencies_enabled = explode(',', $this->_config["SWIPP_CUR_ENABLED"]);
        }
        $ret = array();
        foreach (parent::getCurrency($object, $active) as $value) {
            if (in_array($value['iso_code'], $currencies_enabled))
                $ret[] = $value;
        }
        return $ret;
    }

    public function execPayment($cart) {
        if (!$this->active)
            return;
        if (!$this->_checkCurrency($cart))
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php');

        global $cookie, $smarty;

        $smarty->assign(array(
            'nbProducts' => $cart->nbProducts(),
            'cust_currency' => $cookie->id_currency,
            'id_currency_accepted' => $cookie->id_currency,
            'currencies' => $this->getCurrency(),
            'total' => $cart->getOrderTotal(true, 3),
            'isoCode' => Language::getIsoById(intval($cookie->id_lang)),
            'this_path' => $this->_path,
            'this_path_ssl' => Tools::getHttpHost(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
        ));

        return $this->display(__FILE__, 'payment_execution.tpl');
    }

    public function hookpaymentReturn($params) {
        if (!$this->active)
            return;

        global $smarty;
        $state = $params['objOrder']->getCurrentState();
        if ($state == (int) Configuration::get("SWIPP_ORDERSTATEID") || $state == (int) Configuration::get("SWIPP_PAYMENT_STATE") || $state == _PS_OS_BANKWIRE_ || $state == _PS_OS_OUTOFSTOCK_)
            $smarty->assign(array(
                'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false, false),
                'swippOwner' => @$this->_config['SWIPP_OWNER'],
                'swippPhone' => @$this->_config['SWIPP_PHONE'],
                'status' => 'ok',
                'id_order' => $params['objOrder']->id
            ));
        else
            $smarty->assign('status', 'failed');
        return $this->display(__FILE__, 'payment_return.tpl');
    }

    private function _checkCurrency($cart) {
        $currency_order = new Currency(intval($cart->id_currency));
        $currencies_module = $this->getCurrency();
        $currency_default = Configuration::get('PS_CURRENCY_DEFAULT');

        if (is_array($currencies_module))
            foreach ($currencies_module AS $currency_module)
                if ($currency_order->id == $currency_module['id_currency'])
                    return true;
    }

    public function install() {
        if (!parent::install() || !$this->_addOrderState() || !$this->registerHook('payment') || !$this->registerHook('paymentReturn'))
            return false;
        return true;
    }

    public function uninstall() {
        if (!parent::uninstall()) {
            return false;
        }
        Configuration::deleteByName('SWIPP_PHONE');
        Configuration::deleteByName('SWIPP_OWNER');
        $SwippOrder = new OrderState(Configuration::get('SWIPP_ORDERSTATEID'));
        $SwippOrder->delete();
        Configuration::deleteByName('SWIPP_ORDERSTATEID');
        unset($SwippOrder);
        foreach (explode(',', Configuration::get('SWIPP_CUR_ENABLED')) as $value) {
            Configuration::deleteByName("SWIPP_CUR_{$value}");
        }
        Configuration::deleteByName('SWIPP_CUR_ENABLED');
        foreach (Language::getLanguages(false) as $lang) {
            if (isset($lang['iso_code']) && is_dir(_PS_MAIL_DIR_ . $lang['iso_code'])) {
                unlink(_PS_MAIL_DIR_ . $lang['iso_code'] . '/swipp_payment.html');
                unlink(_PS_MAIL_DIR_ . $lang['iso_code'] . '/swipp_payment.txt');
            } else if (isset($lang['language_code']) && is_dir(_PS_MAIL_DIR_ . $lang['language_code'])) {
                unlink(_PS_MAIL_DIR_ . $lang['language_code'] . '/swipp_payment.html');
                unlink(_PS_MAIL_DIR_ . $lang['language_code'] . '/swipp_payment.txt');
            }
        }
        return true;
    }

    private function _addOrderState() {
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
        if (!$SwippOrder->add()) {
            return false;
        }
        Configuration::updateValue('SWIPP_ORDERSTATEID', $SwippOrder->id);
        copy(dirname(__FILE__) . '/logo.gif', _PS_IMG_DIR_ . 'os/' . $SwippOrder->id . '.gif');
        // mails tmpls
        $copy_files_dir = dirname(__FILE__) . '/_copy_files/';
        foreach (Language::getLanguages(false) as $lang) {
            if (isset($lang['iso_code']) && is_dir(_PS_MAIL_DIR_ . $lang['iso_code'])) {
                copy($copy_files_dir . 'mails/swipp_payment.html', _PS_MAIL_DIR_ . $lang['iso_code'] . '/swipp_payment.html');
                copy($copy_files_dir . 'mails/swipp_payment.txt', _PS_MAIL_DIR_ . $lang['iso_code'] . '/swipp_payment.txt');
            } else if (isset($lang['language_code']) && is_dir(_PS_MAIL_DIR_ . $lang['language_code'])) {
                copy($copy_files_dir . 'mails/swipp_payment.html', _PS_MAIL_DIR_ . $lang['language_code'] . '/swipp_payment.html');
                copy($copy_files_dir . 'mails/swipp_payment.txt', _PS_MAIL_DIR_ . $lang['language_code'] . '/swipp_payment.txt');
            }
        }

        return true;
    }

}
