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


<p class="payment_module">
    <a href="{$this_path_ssl}payment.php" title="{l s='Pay by swipp' mod='swipp'}">
        <img src="{$this_path_bw}swipp.jpg" alt="{l s='Pay by swipp' mod='swipp'}" width="86" height="49"/>
        {l s='Pay by mobilphone through swipp' mod='swipp'} {if $SWIPP_SHOW_CONVERTED}<i>(<b>{$DKK_CurrencyName} {displayPrice price=$DKK_Total currency=$DKK_CurrencyId}</b>)</i>{/if}
    </a>
</p>