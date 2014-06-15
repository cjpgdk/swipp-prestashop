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

{capture name=path}
    {l s='Swipp payment.' mod='swipp'}
{/capture}

<h1 class="page-heading">
    {l s='Order summary' mod='swipp'}
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
    <p class="alert alert-warning">
        {l s='Your shopping cart is empty.' mod='swipp'}
    </p>
{else}
    <form action="{$link->getModuleLink('swipp', 'validation', [], true)|escape:'html':'UTF-8'}" method="post">
        <div class="box cheque-box">
            <h3 class="page-subheading">
                {l s='Swipp mobile payment.' mod='swipp'}
            </h3>
            <p class="cheque-indent">
                <strong class="dark">
                    {l s='You have chosen to pay by swipp.' mod='swipp'} {l s='Here is a short summary of your order:' mod='swipp'}
                </strong>
            </p>
            <p>
                - {l s='The total amount of your order is' mod='swipp'}
                <span id="amount" class="price">{displayPrice price=$total}</span>
                {if $use_taxes == 1}
                    {l s='(tax incl.)' mod='swipp'}
                {/if}
            </p>
            <p>
                -
                {if $currencies|@count > 1}
                    {l s='We allow several currencies to be sent via swipp.' mod='swipp'}
                <div class="form-group">
                    <label>{l s='Choose one of the following:' mod='swipp'}</label>
                    <select id="currency_payement" class="form-control" name="currency_payement">
                        {foreach from=$currencies item=currency}
                            <option value="{$currency.id_currency}" {if $currency.id_currency == $cust_currency}selected="selected"{/if}>
                                {$currency.name}
                            </option>
                        {/foreach}
                    </select>
                </div>
            {else}
                {l s='We allow the following currency to be sent via swipp:' mod='swipp'}&nbsp;<b>{$currencies.0.name}</b>
                <input type="hidden" name="currency_payement" value="{$currencies.0.id_currency}" />
            {/if}
            </p>
            <p>
                - {l s='Swipp account information will be displayed on the next page.' mod='swipp'}
                <br />
                - {l s='Please confirm your order by clicking "I confirm my order."' mod='swipp'}.
            </p>
        </div>
        <p class="cart_navigation clearfix" id="cart_navigation">
            <a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
                <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='swipp'}
            </a>
            <button class="button btn-default button-medium" type="submit">
                <span>{l s='I confirm my order' mod='swipp'}<i class="icon-chevron-right right"></i></span>
            </button>
        </p>
    </form>
{/if}
