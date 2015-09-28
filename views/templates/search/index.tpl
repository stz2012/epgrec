{include file='header.tpl'}

<div class="container">
<h2>{$sitetitle}</h2>
<a href="{$home_url}index">番組表に戻る</a>/<a href="{$this_class->getCurrentUri(false)}/keyword">自動録画キーワード管理へ</a>
</div>

<div class="container">
絞り込み：
<form method="post" action="{$this_class->getCurrentUri()}">
<input type="hidden" name="do_search" value="1" />
検索語句<input type="text" size="20" name="search" value="{$search}" /><br />
正規表現使用<input type="checkbox" name="use_regexp" value="1" {if $use_regexp}checked{/if} />
種別{html_options name="type" options=$types selected=$sel_type}
局{html_options name="station" options=$stations selected=$sel_station}
カテゴリ{html_options name="category_id" options=$categorys selected=$sel_category}
開始時{html_options name="prgtime" options=$prgtimes selected=$sel_prgtime}
曜日{html_options name="weekofday" options=$weekofdays selected=$sel_weekofday}
<input type="submit" value="絞り込む" />
</form>
</div>

<div class="container">
{if count($programs)}
<table id="reservation_table" class="table">
<thead>
 <tr>
  <th>種別</th>
  <th>局名</th>
  <th>番組開始</th>
  <th>番組終了</th>
  <th>タイトル</th>
  <th>内容</th>
  <th>簡易録画</th>
  <th>詳細録画</th>
 </tr>
</thead>
<tbody>
{foreach from=$programs item=program}
 <tr id="resid_{$program.id}" class="ctg_{$program.cat}{if $program.rec > 0} prg_rec{/if}">
  <td>{$program.type}</td>
  <td>{$program.station_name}</td>
  <td>{$program.starttime}</td>
  <td>{$program.endtime}</td>
  <td>{$program.title|escape}</td>
  <td>{$program.description|escape}</td>
  <td><input type="button" value="録画" onclick="javascript:PRG.rec('{$program.id}')" /></td>
  <td><input type="button" value="詳細" onclick="javascript:PRG.customform('{$program.id}')" /></td>
 </tr>
{/foreach}
</tbody>
</table>
{else}
<p>該当する番組はありません</p>
{/if}
</div>
<div class="container">{$programs|@count}件ヒット</div>
{if count($programs) >= 300}
<div class="container">表示最大300件まで</div>
{/if}
{if $do_keyword}
{if (count($programs) < 300)}
<div class="container">
<form method="post" action="{$this_class->getCurrentUri(false)}/keyword">
  <b>語句:</b>{$search|escape}
  <b>正規表現:</b>{if $use_regexp}使う{else}使わない{/if}
  <b>種別:</b>{$types[$sel_type]|escape}
  <b>局:</b>{$stations[$sel_station]|escape}
  <b>カテゴリ:</b>{$categorys[$sel_category]|escape}
  <b>曜日:</b>{if $sel_weekofday == 7}なし{else}{$weekofdays[$sel_weekofday]|escape}曜{/if}
  <b>時間:</b>{$prgtimes[$sel_prgtime]|escape}
  <b>件数:</b>{$programs|@count}
  <input type="hidden" name="add_keyword" value="{$do_keyword}" />
  <input type="hidden" name="k_use_regexp" value="{$use_regexp}" />
  <input type="hidden" name="k_search" value="{$search}" />
  <input type="hidden" name="k_type" value="{$sel_type}" />
  <input type="hidden" name="k_category" value="{$sel_category}" />
  <input type="hidden" name="k_station" value="{$sel_station}" />
  <input type="hidden" name="k_weekofday" value="{$sel_weekofday}" />
  <input type="hidden" name="k_prgtime" value="{$sel_prgtime}" />
  <b>録画モード:</b>{html_options name="autorec_mode" options=$record_mode selected=$sel_recmode}
  <br /><input type="submit" value="この絞り込みを自動録画キーワードに登録" />
  </form>
</div>
{/if}
{/if}

{include file='INISet.tpl'}
<script type="text/javascript" src="{$home_url}js/search.js"></script>
{include file='footer.tpl'}
