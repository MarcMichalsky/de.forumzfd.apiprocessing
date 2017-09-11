<h2>{ts}Data coming from API Request:{/ts}</h2>
  {foreach from=$data item=value key=key}
		{$key} = {$value}
  {/foreach}