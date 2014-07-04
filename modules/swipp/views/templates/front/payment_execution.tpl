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
	<img src="{$this_path_bw}swipp.jpg" alt="{l s='Swipp payment' mod='swipp'}" width="86" height="49" style="float:left; margin: 0px 10px 5px 0px;" />
	{l s='You have chosen to pay by Swipp payment.' mod='swipp'}
	<br/><br />
	{l s='Here is a short summary of your order:' mod='swipp'}
</p>
<p style="margin-top:20px;">
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
		<br /><br />
		{l s='Choose one of the following:' mod='swipp'}
		<select id="currency_payement" name="currency_payement" onchange="setCurrency($('#currency_payement').val());">
			{foreach from=$currencies item=currency}
				<option value="{$currency.id_currency}" {if $currency.id_currency == $cust_currency}selected="selected"{/if}>{$currency.name}</option>
			{/foreach}
		</select>
	{else}
		{l s='We allow the following currency to be sent via swipp:' mod='swipp'}&nbsp;<b>{$currencies.0.name}</b>
		<input type="hidden" name="currency_payement" value="{$currencies.0.id_currency}" />
	{/if}
</p>
<p>
	{l s='swipp account information will be displayed on the next page.' mod='swipp'}
	<br /><br />
	<b>{l s='Please confirm your order by clicking "I confirm my order."' mod='swipp'}.</b>
</p>
<p class="cart_navigation" id="cart_navigation">
	<input type="submit" value="{l s='I confirm my order' mod='swipp'}" class="exclusive_large" />
	<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button_large">{l s='Other payment methods' mod='swipp'}</a>
</p>
</form>
{/if}
