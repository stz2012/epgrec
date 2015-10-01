{include file='header.tpl'}

<div class="container">
<h2>{$sitetitle}</h2>
<p><a href="{$home_url}index">番組表に戻る</a></p>
</div>

<div class="container nonborderbox">
{if count($reservations)}
<table id="reservation_table" class="table">
<thead>
 <tr>
  <th>id</th>
  <th>種別</th>
  <th>ch</th>
  <th>開始</th>
  <th>終了</th>
  <th>モード</th>
  <th>タイトル</th>
  <th>内容</th>
  <th><a href="{$home_url}search/keyword">自動ID</a></th>
  <th>削除</th>
 </tr>
</thead>
<tbody>
{foreach from=$reservations item=reserve}
 <tr id="resid_{$reserve.id}" class="ctg_{$reserve.cat}">
  <td>{$reserve.id}</td>
  <td>{$reserve.type}</td>
  <td id="chid_{$reserve.id}">{$reserve.station_name}</td>
  <td id="stid_{$reserve.id}">{$reserve.starttime}</td>
  <td>{$reserve.endtime}</td>
  <td>{$reserve.mode}</td>
  <td style="cursor: pointer" id="tid_{$reserve.id}" onClick="javascript:PRG.editdialog('{$reserve.id}')">{$reserve.title|escape}</td>
  <td style="cursor: pointer" id="did_{$reserve.id}" onClick="javascript:PRG.editdialog('{$reserve.id}')">{$reserve.description|escape}</td>
  <td>{if $reserve.autorec}{$reserve.autorec}{/if}</td>
  <td><input type="button" value="削除" onClick="javascript:PRG.rec('{$reserve.id}')" /></td>
 </tr>
{/foreach}
</tbody>
</table>
{else}
<p>現在、予約はありません</p>
{/if}
</div>

{include file='INISet.tpl'}
<script type="text/javascript" src="{$home_url}js/recprog.js"></script>
{include file='footer.tpl'}
