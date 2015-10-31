<?php

if (!defined('_PS_VERSION_'))
    exit;
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

class Swipp extends PaymentModule {

    public function __construct($name = null, $context = null) {
        $this->name = 'swipp';
        $this->tab = 'payments_gateways';
        $this->version = '0.2';
        $this->author = 'Christian Jensen';

        $this->controllers = array('payment', 'validation');

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->is_eu_compatible = 1;

        $this->bootstrap = true;

        parent::__construct($name, ($context instanceof Context ? $context : NULL));

        $this->displayName = 'Swipp';
        $this->description = $this->l('Accept payments for your products via swipp transfer.');
        $this->confirmUninstall = $this->l('Are you sure about removing these details?');

        $this->SwippPhone = Configuration::get('SWIPP_PHONE');
        $this->SwippOwner = Configuration::get('SWIPP_OWNER');

        $this->_html = "";

        if (!isset($this->SwippPhone) || empty($this->SwippPhone))
            $this->warning .= (!empty($this->warning) ? '<br/>' : '') . $this->l('Swipp phone number must be configured before using this module.');
        if (!isset($this->SwippOwner) || empty($this->SwippOwner))
            $this->warning .= (!empty($this->warning) ? '<br/>' : '') . $this->l('Swipp owner/user must be configured before using this module.');

        $this->extra_mail_vars = array(
            '{swipp_phone}' => $this->SwippPhone,
            '{swipp_owner}' => $this->SwippOwner,
        );

        $this->_active = $this->active;
        $dkkC_id = Currency::getIdByIsoCode('DKK');
        if (Validate::isInt($dkkC_id)) {
            $dkkC = new Currency($dkkC_id);
            if ($dkkC->id != $dkkC_id || $dkkC->iso_code_num != 208) {
                $this->_active = false;
            } else {
                $this->_active = $dkkC->active;
            }
        } else {
            $this->_active = false;
        }
    }

    /* ## HOOKS ## */

    public function hookDisplayPaymentEU($params) {
        if (!$this->active)
            return;

        if (!$this->checkCurrency($params['cart']))
            return;

        $payment_options = array(
            'cta_text' => $this->l('Pay by Swipp mobile payment'),
            'logo' => Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/swipp.jpg'),
            'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true)
        );

        return $payment_options;
    }

    public function hookPaymentReturn($params) {
        if (!$this->active)
            return;
        if (!$this->_active)
            return;
        $state = $params['objOrder']->getCurrentState();
        $allowedOrderStates = array();
        if (Configuration::hasKey('SWIPP_ORDER_STATES')) {
            $allowedOrderStates = explode(',', Configuration::get('SWIPP_ORDER_STATES'));
        }
        if ($state == Configuration::get('SWIPP_PAYMENT_STATE') ||
                $state == Configuration::get('PS_OS_OUTOFSTOCK') ||
                in_array($state, $allowedOrderStates)) {
            $dkkC_id = Currency::getIdByIsoCode('DKK');
            $dkkC = new Currency($dkkC_id);
            $cart_total = $this->__getPriceDkk($params['objOrder'], 99);
            $this->smarty->assign(array(
                'total_to_pay' => Tools::displayPrice($cart_total, $dkkC),
                'swippOwner' => $this->SwippOwner,
                'swippPhone' => $this->SwippPhone,
                'status' => 'ok',
                'id_order' => $params['objOrder']->id
            ));
            if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference)) {
                $this->smarty->assign('reference', $params['objOrder']->reference);
            }
        } else {
            $this->smarty->assign('status', 'failed');
        }
        return $this->display(__FILE__, 'payment_return.tpl');
    }

    public function hookPayment($params) {
        if (!$this->active)
            return;
        if (!$this->_active)
            return;
        if (!$this->checkCurrency($params['cart']))
            return;
        $cart_total = $this->__getPriceDkk($params['cart']);
        if ((float) $cart_total > (float) Configuration::get('SWIPP_MAX_AMOUNT')) {
            return;
        }
        $dkkC_id = Currency::getIdByIsoCode('DKK');
        $dkkC = new Currency($dkkC_id);
        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_bw' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
            'DKK_Total' => $cart_total,
            'DKK_CurrencyId' => $dkkC->id,
            'DKK_CurrencyName' => $dkkC->name,
            'SWIPP_SHOW_CONVERTED' => (bool) Configuration::get('SWIPP_SHOW_CONVERTED'),
        ));
        return $this->display(__FILE__, 'payment.tpl');
    }

    /**
     * hook to ad Swipp payment info to the bottom of the pdf file.!
     * @param array $params
     * @return string
     */
    public function hookPDFInvoice($params) {
        if (!(bool) Configuration::get('SWIPP_SHOW_INVIOCE')) {
            return "";
        }
        if (isset($params['object'])) {
            $object = $params['object'];
            $order = new Order(intval($object->id_order));
            if (strtolower($order->payment) != strtolower($this->displayName) && strtolower($order->payment) != strtolower($this->name))
                return ""; /* not payment by this module. */
            $_order_currency = new Currency($order->id_currency);
            $this->smarty->assign(array(
                'SWIPP_CURRENCY_ISO_CODE' => strtoupper($_order_currency->iso_code),
                'SWIPP_OWNER' => $this->SwippOwner,
                'SWIPP_PHONE' => $this->SwippPhone,
            ));
            if (strtoupper($_order_currency->iso_code) != "DKK") {
                $dkkC = new Currency(Currency::getIdByIsoCode('DKK'));
                $price = $this->__getPriceDkk($order, 99);
                $this->smarty->assign(array('SWIPP_PRICE' => $this->SwippPhone));
                unset($_order_currency, $dkkC, $price);
            }
            //return $this->context->smarty->createTemplate($this->getTemplatePath('pdf.tpl'), null, null, $this->smarty);
            return $this->display(__FILE__, 'pdf.tpl');
        }
        return "";
    }

    /**
     * add swipp css to the header
     * @param array $param
     * @todo restrict to only checkout pages!
     */
    public function hookHeader($param) {
        $this->context->controller->addCSS(($this->_path) . 'css/swipp.css', 'all');
    }

    /* ## INSTALL / UNINJSTALL ## */

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
                !$this->registerHook('displayPaymentEU') || 
                !$this->registerHook('header'))
            return false;
        Configuration::updateValue('SWIPP_PAYMENT_STATE', $SwippOrder->id);
        copy(dirname(__FILE__) . '/logo.gif', _PS_IMG_DIR_ . 'os/' . $SwippOrder->id . '.gif');

        Configuration::updateValue('SWIPP_MAX_AMOUNT', 10000.00);
        Configuration::updateValue('SWIPP_SHOW_CONVERTED', 0);
        Configuration::updateValue('SWIPP_SHOW_INVIOCE', 0);

        $copy_files_dir = dirname(__FILE__) . '/_copy_files/';
        // danish mails tmpls
        if (is_dir(_PS_MAIL_DIR_ . 'da')) {
            if (!file_exists(_PS_MAIL_DIR_ . 'da/swipp_payment.html'))
                copy($copy_files_dir . 'mails/da/swipp_payment.html', _PS_MAIL_DIR_ . 'da/swipp_payment.html');
            if (!file_exists(_PS_MAIL_DIR_ . 'da/swipp_payment.txt'))
                copy($copy_files_dir . 'mails/da/swipp_payment.txt', _PS_MAIL_DIR_ . 'da/swipp_payment.txt');
        }
        // english mails tmpls
        foreach (Language::getLanguages(false) as $lang) {
            if ((isset($lang['iso_code']) && (strtolower($lang['iso_code']) == 'da')) ||
                    (isset($lang['language_code']) && (strtolower($lang['language_code']) == 'da')))
                continue;

            if (isset($lang['iso_code']) && is_dir(_PS_MAIL_DIR_ . $lang['iso_code'])) {
                if (!file_exists(_PS_MAIL_DIR_ . $lang['iso_code'] . '/swipp_payment.html'))
                    copy($copy_files_dir . 'mails/en/swipp_payment.html', _PS_MAIL_DIR_ . $lang['iso_code'] . '/swipp_payment.html');
                if (!file_exists(_PS_MAIL_DIR_ . $lang['iso_code'] . '/swipp_payment.txt'))
                    copy($copy_files_dir . 'mails/en/swipp_payment.txt', _PS_MAIL_DIR_ . $lang['iso_code'] . '/swipp_payment.txt');
            } else if (isset($lang['language_code']) && is_dir(_PS_MAIL_DIR_ . $lang['language_code'])) {
                if (!file_exists(_PS_MAIL_DIR_ . $lang['language_code'] . '/swipp_payment.html'))
                    copy($copy_files_dir . 'mails/en/swipp_payment.html', _PS_MAIL_DIR_ . $lang['language_code'] . '/swipp_payment.html');
                if (!file_exists(_PS_MAIL_DIR_ . $lang['language_code'] . '/swipp_payment.txt'))
                    copy($copy_files_dir . 'mails/en/swipp_payment.txt', _PS_MAIL_DIR_ . $lang['language_code'] . '/swipp_payment.txt');
            }
        }


        return true;
    }

    public function uninstall() {
        if (!Configuration::deleteByName('SWIPP_OWNER') ||
                !Configuration::deleteByName('SWIPP_SHOW_CONVERTED') ||
                !Configuration::deleteByName('SWIPP_SHOW_INVIOCE') ||
                !Configuration::deleteByName('SWIPP_MAX_AMOUNT') ||
                !Configuration::deleteByName('SWIPP_PHONE') ||
                !Configuration::deleteByName('SWIPP_ORDER_STATES') ||
                !Configuration::deleteByName('SWIPP_PAYMENT_STATE') ||
                !parent::uninstall())
            return false;

        return true;
    }

    /* ## MODULE ADMIN CONFIG ## */

    /**
     * Validate required posted values from admin form
     */
    private function _postValidation() {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('SWIPP_PHONE'))
                $this->_postErrors[] = $this->l('Swipp phone number are required.');
            elseif (!Tools::getValue('SWIPP_OWNER'))
                $this->_postErrors[] = $this->l('Swipp owner/user is required.');
        }
    }

    /**
     * save the posted values from admin form
     */
    private function _postProcess() {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('SWIPP_PHONE', Tools::getValue('SWIPP_PHONE'));
            Configuration::updateValue('SWIPP_OWNER', Tools::getValue('SWIPP_OWNER'));
            Configuration::updateValue('SWIPP_MAX_AMOUNT', (float) Tools::getValue('SWIPP_MAX_AMOUNT'));
            $_currencies = self::__getCurrencies();
            $currencies = array();
            foreach ($_currencies as $currenciesK => $currenciesV) {
                if (Tools::getIsset('SWIPP_CURRENCIES_' . $currenciesV->id)) {
                    $_val = Tools::getValue('SWIPP_CURRENCIES_' . $currenciesV->id);
                    if (!empty($_val) && Validate::isUnsignedInt($_val) && !in_array($_val, $currencies))
                        $currencies[] = $_val;
                }
            }
            unset($_currencies);
            Configuration::updateValue('SWIPP_CURRENCIES', implode(',', $currencies));
            Configuration::updateValue('SWIPP_SHOW_CONVERTED', Tools::getValue('SWIPP_SHOW_CONVERTED', 0));
            Configuration::updateValue('SWIPP_SHOW_INVIOCE', Tools::getValue('SWIPP_SHOW_INVIOCE', 0));
            Configuration::updateValue('SWIPP_ORDER_STATES', implode(',', Tools::getValue('SWIPP_ORDER_STATES', '')));
            Configuration::updateValue('SWIPP_PAYMENT_STATE', Tools::getValue('SWIPP_PAYMENT_STATE', Configuration::get('PS_OS_PAYMENT')));
        }
        $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    /**
     * get the admin from for display in the modules admin
     * @return string
     */
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

    /**
     * build the admin configuration form
     * @return string
     */
    public function renderForm() {
        // get currencies in this shop
        $_currencies = self::__getCurrencies();
        $currencies = array();
        $danishKroneName = 'Danish Krone (Kr.)';
        foreach ($_currencies as $currenciesK => $currenciesV) {
            if ((int) $currenciesV->iso_code_num == 208 || strtoupper($currenciesV->iso_code) == 'DKK') {
                $danishKroneName = $currenciesV->name . ' (' . $currenciesV->iso_code . ')';
                continue;
            }
            $currencies[] = array(
                'id' => $currenciesV->id,
                'name' => $currenciesV->name . '(' . $currenciesV->iso_code . ' : ' . $currenciesV->sign . ')',
                'val' => $currenciesV->id,
            );
        }
        unset($_currencies);

        // all order states
        $orderStates = OrderState::getOrderStates($this->context->language->id);
        $order_state = $selected_order_states = array();
        if (Configuration::hasKey('SWIPP_ORDER_STATES')) {
            $selected_order_states = explode(',', Configuration::get('SWIPP_ORDER_STATES'));
        }

        foreach ($orderStates as $orderState) {
            if (in_array($orderState['id_order_state'], $selected_order_states)) {
                $order_state[] = array(
                    'selected' => true,
                    'disabled' => false,
                    'id_category' => $orderState['id_order_state'],
                    'name' => $orderState['name'],
                );
            } else {
                $order_state[] = array(
                    'disabled' => false,
                    'id_category' => $orderState['id_order_state'],
                    'name' => $orderState['name'],
                );
            }
        }

        $tree = new Tree('swipp-order-state', $order_state);
        //$tree->setTemplateDirectory(dirname(__FILE__) . '/views/templates/admin/_configure/helpers/tree/');
        $tree->setNodeItemTemplate('../../../../../../modules/swipp/views/templates/admin/tree_node_item_order_state.tpl');
        $tree->setContext($this->context);
        $tree->getContext()->smarty->assign(array('input_name' => 'SWIPP_ORDER_STATES'));


        $fields_form[0]['form']['legend'] = array(
            'title' => $this->l('Swipp details'),
            'icon' => 'icon-credit-card'
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
        $fields_form[0]['form']['input'][] = array(
            'type' => 'text',
            'label' => $this->l('Swipp Max Payment'),
            'name' => 'SWIPP_MAX_AMOUNT',
            'desc' => $this->l('The maximum amount allowed through swipp per order'),
            'prefix' => $danishKroneName
        );
        if (count($currencies) > 0) {
            $fields_form[0]['form']['input'][] = array(
                'type' => 'checkbox',
                'label' => $this->l('Currencies witch we allow'),
                'name' => 'SWIPP_CURRENCIES',
                'desc' => $this->l('The currencies you select here will have the swipp payment option available but with the paymant amount converted to Danish Krone'),
                'values' => array(
                    'query' => $currencies,
                    'id' => 'id',
                    'name' => 'name',
                ),
                'class' => 't'
            );
        }
        $fields_form[0]['form']['input'][] = array(
            'name' => 'SWIPP_ORDER_STATES',
            'type' => 'categories_select',
            'label' => $this->l('Accepted Order state'),
            'category_tree' => $tree->render(),
            'required' => true,
            'desc' => $this->l('Order states where swipp payment is accepted')
        );
        $fields_form[0]['form']['input'][] = array(
            'name' => 'SWIPP_PAYMENT_STATE',
            'type' => 'select',
            'label' => $this->l('Status of payment when order is placed'),
            'options' => array(
                'default' => array('value' => 0, 'label' => $this->l('Choose status')),
                'query' => $order_state,
                'id' => 'id_category',
                'name' => 'name'
            ),
            'required' => true,
            'desc' => $this->l('The status the order is set to when the customer clicks the accept button.')
        );

        $fields_form[0]['form']['input'][] = array(
            'type' => 'switch',
            'is_bool' => true,
            'label' => $this->l('Show converted order amount on payment option'),
            'name' => 'SWIPP_SHOW_CONVERTED',
            'desc' => $this->l('Only usefull if you use multiple currencies'),
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                )
            ),
        );
        $fields_form[0]['form']['input'][] = array(
            'type' => 'switch',
            'is_bool' => true,
            'label' => $this->l('Show message on invoice'),
            'name' => 'SWIPP_SHOW_INVIOCE',
            'desc' => $this->l('Only usefull if you use multiple currencies'),
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->l('Yes')
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->l('No')
                )
            ),
        );

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
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
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
            'SWIPP_ORDER_STATES' => '', /* avoid not isset */
            'SWIPP_PAYMENT_STATE' => Tools::getValue('SWIPP_PAYMENT_STATE', Configuration::get('SWIPP_PAYMENT_STATE')),
            'SWIPP_OWNER' => Tools::getValue('SWIPP_OWNER', Configuration::get('SWIPP_OWNER')),
            'SWIPP_PHONE' => Tools::getValue('SWIPP_PHONE', Configuration::get('SWIPP_PHONE')),
            'SWIPP_MAX_AMOUNT' => Tools::getValue('SWIPP_MAX_AMOUNT', Configuration::get('SWIPP_MAX_AMOUNT')),
            'SWIPP_SHOW_CONVERTED' => ((bool) Configuration::get('SWIPP_SHOW_CONVERTED') ? 1 : 0),
            'SWIPP_SHOW_INVIOCE' => ((bool) Configuration::get('SWIPP_SHOW_INVIOCE') ? 1 : 0),
        );
        $cconf = explode(',', Configuration::get('SWIPP_CURRENCIES'));
        foreach ($cconf as $key => $value) {
            $retval['SWIPP_CURRENCIES_' . $value] = $value;
        }
        return $retval;
    }

    /* ## HELPER FUNCTION ## */

    /**
     * Method to exec payment hook from prestashop version 1.4
     * @global Cookie $cookie
     * @global Smarty $smarty
     * @param Cart $cart
     * @return string
     */
    public function execPayment($cart) {
        if (!$this->active)
            return;
        if (!$this->checkCurrency($cart))
            Tools::redirectLink(__PS_BASE_URI__ . 'order.php');
        global $cookie, $smarty;
        $dkkC = new Currency(Currency::getIdByIsoCode('DKK'));
        $smarty->assign(array(
            'nbProducts' => $cart->nbProducts(),
            'cust_currency' => $cart->id_currency,
            'name_currency' => $dkkC->name,
            'id_currency_accepted' => $dkkC->id,
            'id_currency' => $cart->id_currency,
            'total' => $this->__getPriceDkk($cart),
            'this_path' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . ((int) Configuration::get('PS_REWRITING_SETTINGS') && count(Language::getLanguages()) > 1 && isset($smarty->ps_language) && !empty($smarty->ps_language) ? $smarty->ps_language->iso_code . '/' : '') . 'modules/' . $this->name . '/'
        ));
        return $this->display(__FILE__, 'payment_execution.tpl');
    }

    /**
     * get the total price of the shopping cart as Danish Kroner
     * @param Cart|Order $cart
     * @return floate
     */
    public function __getPriceDkk($cart, $t = 1) {
        /* t==1 use prestashop to convert in Cart Class */

        $_shop_currency = Currency::getDefaultCurrency();
        $_cart_currency = new Currency($cart->id_currency);
        if ($t == 1) {
            $oldc = $cart->id_currency;
            $cart->id_currency = (int) (Currency::getIdByIsoCode('DKK'));
            $price = $cart->getOrderTotal(true, Cart::BOTH);
            $cart->id_currency = $oldc;
            return $price;
//            $price = $cart->getOrderTotal(true, Cart::BOTH);
//            if ($cart->id_currency != $_shop_currency->id) {
//                $_shop_price = ($price / $_cart_currency->conversion_rate);
//            } else
//                $_shop_price = $price;
//            return Tools::convertPrice($_shop_price, Currency::getCurrencyInstance((int) (Currency::getIdByIsoCode('DKK'))));
        } else {
            /* t!=1 convert manualy */
            $_cart_price = $cart->total_paid;
            $_dkk_currency = new Currency(Currency::getIdByIsoCode('DKK'));
            if ($_cart_currency->id != $_shop_currency->id) {
                /* convert price to shop default */
                $_shop_price = ($_cart_price / $_cart_currency->conversion_rate);
            } else
                $_shop_price = $_cart_price;
            if (strtoupper($_shop_currency->iso_code) != "DKK") {
                /* convert price to DKK */
                return ($_shop_price * $_dkk_currency->conversion_rate);
            } else
                return $_shop_price;
        }
    }

    /**
     * check currency is accepted
     * @param Cart $cart
     * @return boolean
     */
    public function checkCurrency($cart) {
        $currency_order = new Currency($cart->id_currency);
        if (strtoupper($currency_order->iso_code) == 'DKK' || $currency_order->iso_code_num == 208) {
            return true;
        }
        $cconf = explode(',', Configuration::get('SWIPP_CURRENCIES'));
        foreach ($cconf as $key => $value) {
            if ($cart->id_currency == (int) $value)
                return true;
        }
        return false;
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

}
