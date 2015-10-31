
<li class="tree-item{if isset($node['disabled']) && $node['disabled'] == true} tree-item-disable{/if} {if isset($node['selected'])} tree-selected{/if}">
	<span class="tree-item-name{if isset($node['disabled']) && $node['disabled'] == true} tree-item-name-disable{/if}">
		<input type="checkbox" name="{$input_name}[]" value="{$node['id_category']}"{if isset($node['disabled']) && $node['disabled'] == true} disabled="disabled"{/if} {if isset($node['selected'])} checked="checked"{/if}/>
		<i class="tree-dot"></i>
		<label class="tree-toggler">{$node['name']}</label>
	</span>
</li>