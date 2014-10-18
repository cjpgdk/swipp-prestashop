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
 * @deprecated 1.5.0 This file is deprecated, use moduleFrontController instead
 */
include(dirname(__FILE__) . '/../../config/config.inc.php');
/* SSL Management */
$useSSL = true;
if (_PS_VERSION_ >= '1.5') {
    Tools::displayFileAsDeprecated();
    // init front controller in order to use Tools::redirect
    $controller = new FrontController();
    $controller->init();
    Tools::redirect(Context::getContext()->link->getModuleLink('swipp', 'payment'));
} else {
    include(dirname(__FILE__) . '/../../header.php');
    if (!class_exists('swipp', false)) include(dirname(__FILE__) . '/swipp.php');
    if (!$cookie->isLogged(true)) Tools::redirect('authentication.php?back=order.php');
    elseif (!Customer::getAddressesTotalById((int) ($cookie->id_customer))) Tools::redirect('address.php?back=order.php?step=1');
    $Swipp = new Swipp();
    echo $Swipp->execPayment($cart);
    include_once(dirname(__FILE__) . '/../../footer.php');
}