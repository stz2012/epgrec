{include file='header.tpl'}

<div class="container">
<h2>{$sitetitle}</h2>
<p><a href="{$home_url}setting">環境設定へ</a> / <a href="{$home_url}setting/system">システム設定へ</a> / <a href="{$home_url}setting/userList">ユーザ一覧へ</a></p>
</div>

<div class="container nonborderbox">
<form method="post" action="{$this_class->getCurrentUri()}" class="formSetting">
<div class="setting">
ログ種別：<select name="log_type" id="id_log_type" onchange="this.form.submit();">
<option value=""> </option>
{html_options options=$log_types selected=$post_data.log_type}
</select>
</div>
{if count($logs)}
<table id="log_table" class="table">
<thead>
 <tr>
  <th>レベル</th>
  <th>日時</th>
  <th>内容</th>
 </tr>
</thead>
<tbody>
{foreach from=$logs item=log}
 <tr>
  <td class="errorlevel{$log.level}">
    {if $log.level == 0}情報
    {elseif $log.level == 1}警告
    {elseif $log.level == 2}エラー
    {/if}
  </td>
  <td>{$log.logtime}</td>
  <td>{$log.message|escape}</td>
 </tr>
{/foreach}
</tbody>
</table>
{elseif count($events)}
<table id="log_table" class="table">
<thead>
 <tr>
  <th>日時</th>
  <th>内容</th>
 </tr>
</thead>
<tbody>
{foreach from=$events item=log}
 <tr>
  <td>{$log.event_date}</td>
  <td>{$log.event_comment|escape}</td>
 </tr>
{/foreach}
</tbody>
</table>
{else}
<p>該当するログはありません</p>
{/if}
<input type="hidden" name="token" value="{$token}" />
</form>
</div>
{include file='INISet.tpl'}
{include file='footer.tpl'}
