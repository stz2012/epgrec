{include file='header.tpl'}

<div class="container">
<h2>{$sitetitle}</h2>
<p><a href="{$home_url}setting">環境設定へ</a> / <a href="{$home_url}setting/system">システム設定へ</a> / <a href="{$home_url}setting/viewLog">動作ログを見る</a></p>
</div>

<div class="container nonborderbox">
{if count($users)}
<table id="user_table" class="table">
<thead>
 <tr>
  <th>ユーザ名</th>
  <th>ユーザレベル</th>
  <th>&nbsp;</th>
 </tr>
</thead>
<tbody>
{foreach from=$users item=user}
 <tr>
  <td>{$user.name|escape}</td>
  <td>{if $user.level == 100}特権ユーザ{else}一般ユーザ{/if}</td>
  <td><a href="{$home_url}setting/userEdit?{$user.link}">編集</a></td>
 </tr>
{/foreach}
</tbody>
</table>
{else}
<p>該当するログはありません</p>
{/if}
</div>
{include file='INISet.tpl'}
{include file='footer.tpl'}
