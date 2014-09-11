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

{capture name=path}{l s='Swipp payment.' mod='swipp'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summary' mod='swipp'}</h2>

{assign var='current_step' value='swipp'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
    <p class="warning">{l s='Your shopping cart is empty.' mod='swipp'}</p>
{else}

    <h3>{l s='Swipp mobile payment.' mod='swipp'}</h3>
    <form action="{$link->getModuleLink('swipp', 'validation', [], true)|escape:'html'}" method="post">
        <p>
            <img src="{$this_path_bw}swipp.jpg" alt="{l s='Swipp' mod='swipp'}" width="86" height="49" style="float:left; margin: 0px 10px 5px 0px;" />
            {l s='You have chosen to pay by swipp mobile transfer.' mod='swipp'}
            <br/><br />
            {l s='Here is a short summary of your order:' mod='swipp'}
        </p>
        <p style="margin-top:20px;">
            - {l s='The total amount of your order is' mod='swipp'}
            <span id="amount" class="price">{displayPrice price=$total currency=$id_currency_accepted}</span>
            {if $use_taxes == 1}
                {l s='(tax incl.)' mod='swipp'}
            {/if}
        </p>
        <p>
            -
            {l s='We accept the following currency to be sent by swipp mobile transfer:' mod='swipp'}&nbsp;<b>{$name_currency}</b>
            <input type="hidden" name="currency_payement" value="{$id_currency}" />
        </p>
        <p>
            {l s='Swipp account information will be displayed on the next page.' mod='swipp'}
            <br /><br />
            <b>{l s='Please confirm your order by clicking \'I confirm my order\'' mod='swipp'}.</b>
        </p>
        <p class="cart_navigation">
            <a href="{$link->getPageLink('order.php', true)}?step=3" class="button_large hideOnSubmit">{l s='Other payment methods' mod='swipp'}</a>
            <input type="submit" name="submit" value="{l s='I confirm my order' mod='swipp'}" class="exclusive_large hideOnSubmit" />
        </p>
    </form>
{/if}
