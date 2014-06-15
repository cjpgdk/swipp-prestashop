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
{if $status == 'ok'}
    <p>{l s='Your order on %s is complete.' sprintf=$shop_name mod='swipp'}
        <br /><br />
        {l s='Please send us a sweep with' mod='swipp'}
        <br /><br />- {l s='Amount' mod='swipp'} <span class="price"> <strong>{$total_to_pay}</strong></span>
        <br /><br />- {l s='Name of account owner' mod='swipp'}  <strong>{if $swippOwner}{$swippOwner}{else}___________{/if}</strong>
        <br /><br />- {l s='Swipp phone number' mod='swipp'}  <strong>{if $swippPhone}{$swippPhone}{else}___________{/if}</strong>
        {* if !isset($reference)}
            <br /><br />- { ** l s='Do not forget to insert your order number #%d in the subject of your bank wire' sprintf=$id_order mod='swipp'}
        {else}
            <br /><br />- { ** l s='Do not forget to insert your order reference %s in the subject of your bank wire.' sprintf=$reference mod='swipp'}
        {/if *}
        <br /><br />{l s='An email has been sent with this information.' mod='swipp'}
        <br /><br /> <strong>{l s='Your order will be sent as soon as we receive payment.' mod='swipp'}</strong>
        <br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='swipp'} <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team. ' mod='swipp'}</a>.
    </p>
{else}
    <p class="warning">
        {l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='swipp'} 
        <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team. ' mod='swipp'}</a>.
    </p>
{/if}
