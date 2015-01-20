<?php

if (!defined('_PS_VERSION_'))
    exit;
/*
 * Copyright (C) 2014-2105  Christian M. Jensen
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

class Swipp extends PaymentModule {

    private $_html = '';
    private $_postErrors = array();
    private $_config;
    public $extra_mail_vars;

    public function __construct($name = null, $context = null) {
        $this->name = 'swipp';
        $this->tab = 'payments_gateways';
        $this->version = '0.1.6';
        $this->author = 'Christian Jensen';
        $this->controllers = array('payment', 'validation');
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->bootstrap = true;

        $this->_config = Configuration::getMultiple(array('SWIPP_PHONE', 'SWIPP_OWNER', 'SWIPP_CUR_ENABLED'));

        parent::__construct($name, ($context instanceof Context ? $context : NULL));

        $this->displayName = 'Swipp';
        $this->description = $this->l('Accept payments for your products via swipp transfer.');
        $this->confirmUninstall = $this->l('Are you sure about removing these details?');

        if (!isset($this->_config['SWIPP_PHONE']) || empty($this->_config['SWIPP_PHONE']))
            $this->warning = $this->l('Swipp phone number must be configured before using this module.');
        else if (!isset($this->_config['SWIPP_OWNER']) || empty($this->_config['SWIPP_OWNER']))
            $this->warning .= $this->l('Swipp owner/user must be configured before using this module.');
        else if (!Currency::getIdByIsoCode('DKK') && (!Tools::getIsset('uninstall') || Tools::getValue('uninstall') != 'swipp') && (!Tools::getIsset('install') || Tools::getValue('install') != 'swipp'))
            $this->warning = $this->l('Currency Danish Krone is not installed');
        else if (!sizeof(Currency::checkPaymentCurrencies($this->id)))
            $this->warning = $this->l('No currency set for this module');

        $this->extra_mail_vars = array(
            '{swipp_owner}' => @$this->_config['SWIPP_OWNER'],
            '{swipp_phone}' => @$this->_config['SWIPP_PHONE'],
        );
    }

    /* ## HOOKS ## */

    public function hookPaymentReturn($params) {
        if (!$this->active)
            return "";

        $state = $params['objOrder']->getCurrentState();
        if ($state == (int) Configuration::get("SWIPP_PAYMENT_STATE") || $state == _PS_OS_BANKWIRE_ || $state == _PS_OS_OUTOFSTOCK_)
            $this->smarty->assign(array(
                'total_to_pay' => ToolsCore::displayPrice($params['total_to_pay'], $params['currencyObj']),
                'swippOwner' => @$this->_config['SWIPP_OWNER'],
                'swippPhone' => @$this->_config['SWIPP_PHONE'],
                'status' => 'ok',
                'id_order' => $params['objOrder']->id
            ));
        else
            $this->smarty->assign('status', 'failed');

        if (version_compare(_PS_VERSION_, '1.5.0.5', '>='))
            return $this->display(__FILE__, 'payment_return.tpl');
        else
            return $this->display(__FILE__, 'views/templates/hook/payment_return.tpl');
    }

    public function hookPayment($params) {
        if (!$this->active)
            return;
        if (!Currency::getIdByIsoCode('DKK'))
            return;
        /* prestashop way */
        // if (!$this->_checkCurrency($params['cart']))
        // return;
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
        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_ssl' => Tools::getHttpHost(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
        ));
        if (version_compare(_PS_VERSION_, '1.5.0.5', '>='))
            return $this->display(__FILE__, 'payment.tpl');
        else
            return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /* ## INSTALL / UNINJSTALL ## */

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
        Configuration::deleteByName('SWIPP_PAYMENT_STATE');
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
        Configuration::updateValue('SWIPP_PAYMENT_STATE', $SwippOrder->id);
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

    /* ## MODULE ADMIN CONFIG ## */

    /**
     * Validate required posted values from admin form
     */
    private function _postValidation() {
        if (Tools::isSubmit('btnSubmit')) {
            if (empty($_POST['SWIPP_OWNER']))
                $this->_postErrors[] = $this->l('Swipp owner/user is required.');
            if (empty($_POST['SWIPP_PHONE']))
                $this->_postErrors[] = $this->l('Swipp phone is required.');


            $_POST['SWIPP_CUR_ENABLED'] = array();
            $_currencies = self::__getCurrencies();
            foreach ($_currencies as $currenciesK => $currenciesV) {
                if (Tools::getIsset('SWIPP_CUR_ENABLED_' . $currenciesV->iso_code)) {
                    $_POST['SWIPP_CUR_ENABLED'][$currenciesV->iso_code] = $currenciesV->iso_code;
                }
            }
            if (empty($_POST['SWIPP_CUR_ENABLED']))
                $this->_postErrors[] = $this->l('You havent selected a currency to allow through Swipp');
        }
    }

    /**
     * save the posted values from admin form
     */
    private function _postProcess() {
        if (isset($_POST['btnSubmit'])) {
            Configuration::updateValue('SWIPP_OWNER', $_POST['SWIPP_OWNER']);
            Configuration::updateValue('SWIPP_PHONE', $_POST['SWIPP_PHONE']);
            Configuration::updateValue('SWIPP_CUR_ENABLED', implode(',', $_POST['SWIPP_CUR_ENABLED']));
            $_currencies = self::__getCurrencies();
            foreach ($_currencies as $currenciesK => $currenciesV) {
                if (in_array($currenciesV->iso_code, $_POST['SWIPP_CUR_ENABLED'])) {
                    Configuration::updateValue("SWIPP_CUR_{$currenciesV->iso_code}", (float) Tools::getValue("SWIPP_CUR_{$currenciesV->iso_code}", 0));
                } else {
                    Configuration::deleteByName("SWIPP_CUR_{$currenciesV->iso_code}");
                }
            }
        }
        $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    /**
     * get the admin from for display in the modules admin
     * @return string
     */
    public function getContent() {
        // just a reload
        $this->_config = Configuration::getMultiple(array('SWIPP_PHONE', 'SWIPP_OWNER', 'SWIPP_CUR_ENABLED'));
        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!isset($this->_postErrors) || !count($this->_postErrors))
                $this->_postProcess();
            else
                foreach ($this->_postErrors as $err)
                    $this->_html .= $this->displayError($err);
        } else
            $this->_html .= '<br />';

        if (version_compare(_PS_VERSION_, '1.5.0.5', '>='))
            $this->_html .= $this->display(__FILE__, 'infos.tpl');
        else
            $this->_html .= $this->display(__FILE__, 'views/templates/hook/infos.tpl');
        $this->_html .= $this->renderForm();
        return $this->_html;
    }

    /**
     * build the admin form it self.
     * @global string $currentIndex
     * @return string
     */
    public function renderForm() {
        $_currencies = self::__getCurrencies();
        $currencies = array();
        foreach ($_currencies as $currenciesK => $currenciesV) {
            $currencies[] = array(
                'id' => $currenciesV->id,
                'name' => $currenciesV->name,
                'iso_code' => $currenciesV->iso_code,
            );
        }
        unset($_currencies);
        $fields_form[0]['form']['legend'] = array(
            'title' => $this->l('Swipp details'),
            'icon' => 'icon-envelope'
        );

        $fields_form[0]['form']['input'][] = array(
            'type' => 'text',
            'label' => $this->l('Swipp owner/user'),
            'name' => 'SWIPP_OWNER',
        );
        $fields_form[0]['form']['input'][] = array(
            'type' => 'text',
            'label' => $this->l('Swipp Phone'),
            'name' => 'SWIPP_PHONE',
            'desc' => $this->l('The phone registred with swipp')
        );
        foreach ($currencies as $currencies_value) {
            $fields_form[0]['form']['input'][] = array('type' => 'free', 'name' => 'HR_LINE');
            $fields_form[0]['form']['input'][] = array(
                'type' => 'checkbox',
                'label' => '',
                'name' => 'SWIPP_CUR_ENABLED',
                /* 'desc' => $this->l('Only if you allow payment through swipp with this currency'), */
                'values' => array(
                    'query' => array(
                        array(
                            'id' => $currencies_value['iso_code'],
                            'name' => sprintf($this->l('Enable \'%s\''), $currencies_value['name']),
                            'val' => $currencies_value['iso_code'],
                        ),
                    ),
                    'id' => 'id',
                    'name' => 'name',
                ),
                'class' => 't'
            );

            $fields_form[0]['form']['input'][] = array(
                'type' => 'text',
                'label' => sprintf($this->l('Currency \'%s\' max payment amount allowed'), $currencies_value['name']),
                'name' => 'SWIPP_CUR_' . $currencies_value['iso_code'],
                'suffix' => $currencies_value['name'] . ' (' . $currencies_value['iso_code'] . ')'
            );
        }


        $fields_form[0]['form']['submit'] = array(
            'title' => $this->l('Save'),
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
        if (_PS_VERSION_ < '1.5')
            $helper->currentIndex = $currentIndex . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        else
            $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            /* 'languages' => $this->context->controller->getLanguages(), */
            'id_language' => $this->context->language->id
        );
        return $helper->generateForm($fields_form);
    }

    /**
     * get the admin form input values 
     * @return array
     */
    public function getConfigFieldsValues() {
        $retval = array(
            'SWIPP_OWNER' => Tools::getValue('SWIPP_OWNER', Configuration::get('SWIPP_OWNER')),
            'SWIPP_PHONE' => Tools::getValue('SWIPP_PHONE', Configuration::get('SWIPP_PHONE')),
            'HR_LINE' => '<hr/>',
        );

        $SWIPP_CUR_ENABLED = Tools::getValue('SWIPP_CUR_ENABLED', Configuration::get('SWIPP_CUR_ENABLED'));
        if (!is_array($SWIPP_CUR_ENABLED))
            $SWIPP_CUR_ENABLED = explode(',', $SWIPP_CUR_ENABLED);

        $_currencies = self::__getCurrencies();
        foreach ($_currencies as $currenciesK => $currenciesV) {
            $val = Tools::getValue('SWIPP_CUR_' . $currenciesV->iso_code, Configuration::get('SWIPP_CUR_' . $currenciesV->iso_code));
            if (!empty($val) && in_array($currenciesV->iso_code, $SWIPP_CUR_ENABLED)) {
                $retval['SWIPP_CUR_ENABLED_' . $currenciesV->iso_code] = $currenciesV->iso_code;
                $retval['SWIPP_CUR_' . $currenciesV->iso_code] = Tools::getValue('SWIPP_CUR_' . $currenciesV->iso_code, Configuration::get('SWIPP_CUR_' . $currenciesV->iso_code));
            } else
                $retval['SWIPP_CUR_' . $currenciesV->iso_code] = 0;
        }

        return $retval;
    }

    /* ## HELPER FUNCTION ## */

    /**
     * check currency is accepted
     * @param Cart $cart
     * @return boolean
     */
    public function checkCurrency($cart) {
        $currencies_enabled = array();
        if (isset($this->_config["SWIPP_CUR_ENABLED"])) {
            $currencies_enabled = explode(',', $this->_config["SWIPP_CUR_ENABLED"]);
        }
        $cart_currency = Currency::getCurrencyInstance($cart->id_currency);
        $cart_currency_iso_code = $cart_currency->iso_code;
        if (!in_array($cart_currency_iso_code, $currencies_enabled))
            return false;
        unset($cart_currency);
        if ($cart->getOrderTotal(true, Cart::BOTH) > (float) Configuration::get("SWIPP_CUR_{$cart_currency_iso_code}"))
            return false;


        return true;
    }

    /**
     * holdes the shop currencies after a call to Swipp::__getCurrencies();
     * @var Currency[]
     */
    private static $_urrencies = NULL;

    /**
     * get the shop active currencies
     * @return Currency[]
     */
    private static function __getCurrencies() {
        if (empty(self::$_urrencies) || self::$_urrencies == NULL) {
            self::$_urrencies = Currency::getCurrencies(true);
        }
        return self::$_urrencies;
    }

    /**
     * @param int $id_currency : this parameter is optionnal but on 1.5 version of Prestashop, it will be REQUIRED
     * @return Currency
     */
    public function getCurrency($current_id_currency = null) {
        $currencies_parent = parent::getCurrency($current_id_currency);
        if ($this->currencies_mode == 'checkbox') {

            $currencies_enabled = array();
            if (isset($this->_config["SWIPP_CUR_ENABLED"])) {
                $currencies_enabled = explode(',', $this->_config["SWIPP_CUR_ENABLED"]);
            }
            $ret = array();
            foreach ($currencies_parent as $value) {
                if (in_array($value['iso_code'], $currencies_enabled))
                    $ret[] = $value;
            }
            return $ret;
        }
        return $currencies_parent;
    }

}
