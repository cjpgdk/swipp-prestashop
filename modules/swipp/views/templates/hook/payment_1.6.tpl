{*
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
*}
<div class="row">
    <div class="col-xs-12 col-md-12">
        <p class="payment_module">
            <a class="swipp" 
                href="{$link->getModuleLink('swipp', 'payment')|escape:'html':'UTF-8'}" 
                title="{l s='Pay by mobilphone through swipp' mod='swipp'}">
                {l s='Pay by mobilphone through swipp' mod='swipp'} <i>(<b>{$DKK_CurrencyName} {displayPrice price=$DKK_Total currency=$DKK_CurrencyId}</b>)</i>
            </a>
        </p>
    </div>
</div>