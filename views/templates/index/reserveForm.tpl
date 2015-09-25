<form method="post" action="{$this_class->getCurrentUri(false)}/custom">
<div>
  <span class="labelLeft">開始日時</span>
  <input type="text" size="4" name="syear" id="id_syear" value="{$syear}" />年
  <input type="text" size="2" name="smonth" id="id_smonth" value="{$smonth}" />月
  <input type="text" size="2" name="sday" id="id_sday" value="{$sday}" />日
  <input type="text" size="2" name="shour" id="id_shour" value="{$shour}" />時
  <input type="text" size="2" name="smin" id="id_smin" value="{$smin}" />分～
</div>
<div>
  <span class="labelLeft">終了日時</span>
  <input type="text" size="4" name="eyear" id="id_eyear" value="{$eyear}" />年
  <input type="text" size="2" name="emonth" id="id_emonth" value="{$emonth}" />月
  <input type="text" size="2" name="eday" id="id_eday" value="{$eday}" />日
  <input type="text" size="2" name="ehour" id="id_ehour" value="{$ehour}" />時
  <input type="text" size="2" name="emin" id="id_emin" value="{$emin}" />分
</div>
<div>
  <span class="labelLeft">種別/ch</span>
  {$type}:{$channel}ch
  <input type="hidden" name="channel_id" id="id_channel_id" value="{$channel_id}" />
</div>
<div>
  <span class="labelLeft">録画モード</span>
  <select name="record_mode" id="id_record_mode">
{foreach from=$record_mode item=mode name=recmode}
    <option value="{$smarty.foreach.recmode.index}" {$mode.selected}>{$mode.name}</option>
{/foreach}
  </select>
</div>
<div>
  <span class="labelLeft">タイトル</span>
  <input type="text" size="40" name="title" id="id_title" value="{$title}" />
</div>
<div>
  <span class="labelLeft">概要</span>
  <textarea name="description" id="id_description" rows="4" cols="40" >{$description}</textarea>
</div>
<div>
  <span class="labelLeft">カテゴリ</span>
  {html_options name="category_id" id="id_category_id" options=$categorys selected=$sel_category}
</div>
<div>
  <span class="labelLeft">番組ID保持</span>
  <input type="checkbox" name="program_id" id="id_program_id" value="{$program_id}" checked />
</div>
</form>
