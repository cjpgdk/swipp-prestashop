{l s='You have chosen to pay with swipp' mod='swipp'}<br/>
{l s='use the following infomation to compleate your order.' mod='swipp'}<br/>
<br/>
{l s='Account name:' mod='swipp'} {$SWIPP_OWNER}<br/>
{l s='Account phone:' mod='swipp'} {$SWIPP_PHONE}<br/>

{if $SWIPP_CURRENCY_ISO_CODE !== 'DKK'}
{l s='Wee only accept Danish Krone through swipp' mod='swipp'}<br/>
{l s='Amount:' mod='swipp'} {$SWIPP_PRICE}<br/>
{/if}