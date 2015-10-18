{include file='header.tpl'}

<div class="container">
<h2>{$sitetitle}</h2>
<p><a href="{$this_class->getCurrentUri(false)}">番組検索へ</a>/<a href="{$home_url}recprog">予約一覧へ</a></p>
</div>

<div class="container nonborderbox">
{if count($keywords)}
<table id="reservation_table" class="table">
<thead>
 <tr>
  <th>id</th>
  <th>検索語句</th>
  <th>正規表現</th>
  <th>種別</th>
  <th>ch</th>
  <th>カテゴリ</th>
  <th>曜日</th>
  <th>開始時</th>
  <th>録画モード</th>
  <th>削除</th>
 </tr>
</thead>
<tbody>
{foreach from=$keywords item=keyword}
 <tr id="keyid_{$keyword.id}">
  <td><a href="{$home_url}recprog/recorded?key={$keyword.id}">{$keyword.id}</a></td>
  <td><a href="{$home_url}recprog/recorded?key={$keyword.id}">{$keyword.keyword|escape}</a></td>
  <td>{if $keyword.use_regexp}使う{else}使わない{/if}</td>
  <td>{$keyword.type}</td>
  <td>{$keyword.channel}</td>
  <td>{$keyword.category}</td>
  <td>{$keyword.weekofday}</td>
  <td>{$keyword.prgtime}</td>
  <td>{$keyword.autorec_mode}</td>
  <td><input type="button" value="削除" onclick="javascript:PRG.delkey('{$keyword.id}')" /></td>
 </tr>
{/foreach}
</tbody>
</table>
{else}
<p>キーワードはありません</p>
{/if}
</div>

{include file='INISet.tpl'}
<script type="text/javascript" src="{$home_url}js/search.js"></script>
{include file='footer.tpl'}
