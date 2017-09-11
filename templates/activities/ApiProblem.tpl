<h2>{ts}Data coming from API Request:{/ts}</h2>
<table>
  <tr>
    <th>Parameter name:</th>
    <th>Parameter value:</th>
  </tr>
  {foreach from=$data item=value key=key}
    <tr>
      <td>{$key}</td>
      <td>{$value}</td>
    </tr>
  {/foreach}
</table>
