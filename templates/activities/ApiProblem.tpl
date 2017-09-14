<h2>{ts}Data coming from API Request:{/ts}</h2>
<table>
  <tr>
    <th>Parameter name:</th>
    <th>Parameter value:</th>
  </tr>
  {foreach from=$data item=value key=key}
    <tr>
      <td>{$key}</td>
      {if $value|is_array}
        {foreach from=$value key=dataKey item=dataValue}
          <td>{$dataKey}&nbsp;:{$dataValue}</td>
        {/foreach}
      {else}
        <td>{$value}</td>
      {/if}
    </tr>
  {/foreach}
</table>
