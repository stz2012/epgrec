{include file='header.tpl'}

<div class="container nonborderbox">
<h2>{$sitetitle}</h2>

<div id="float_titles" style="width: {math equation="x + 80" x=$chs_width}px;height:120px;">
<div id="float_follows">

<div class="set">
  <ul>
    <li><a href="{$home_url}setting">環境設定</a></li>
  </ul>
</div>

<div class="set ctg_sel" id="category_select">
 <span class="title"><a href="javascript:CTG.toggle()">強調表示</a></span>

 <ul>
   {foreach from=$cats item=cat}
   <li><a href="javascript:CTG.select('{$cat.name_en}');" class="ctg_{$cat.name_en}">{$cat.name_jp}</a></li>
   {/foreach}
  </ul>
</div>

<div id="time_selects">
 <div class="set" id="jump-broadcast" >
 <span class="title">放送波選択</span>
  <ul>
   {foreach from=$types item=type}
     <li {$type.selected}><a  class="jump" href="{$type.link}">{$type.name}</a></li>
   {/foreach}
  </ul><br style="clear:left;" />
 </div>

 <div class="set"  id="jump-time">
 <span class="title">時間</span>
 <ul>
    {foreach from=$toptimes item=top}
     <li><a class="jump" href="{$top.link}">{$top.hour}～</a></li>
    {/foreach}
  </ul><br style="clear:left;" />
 </div>
 
 <div class="set">
   <ul><li><a class="jump" href="javascript:PRG.toggle()">チャンネル表示</a></li></ul>
 </div>

 <br style="clear:left;" />

 <div class="set">
  <ul>
    <li><a href="{$home_url}search">番組検索</a></li>
    <li><a href="{$home_url}recprog">録画予約一覧</a></li>
    <li><a href="{$home_url}recprog/recorded">録画済一覧</a></li>
  </ul>
 </div>

 <div class="set" id="jump-day" >
 <span class="title">日付</span>
  <ul>
    {foreach from=$days item=day}
     <li {$day.selected}><a {if $day.d eq "現在"} class="jump-today" {else} class="jump" {/if} href="{$day.link}">{$day.d}{$day.ofweek}</a></li>
    {/foreach}
  </ul><br style="clear:left;" />
 </div>


</div>
<br style="clear:left;" />
<div id="prg_info"><div class="dummy">test</div></div>
</div>


<!-- チャンネル局名 -->
<div id="ch_title_bar" style="position:absolute;bottom:0;">
  <div class="tvtimeDM" style="float:left;">&nbsp;</div>
  {foreach from=$programs item=program}{if isset($program.ch_hash)}
  <div id="ch_title_{$program.ch_hash}"  class="ch_title{if $program.skip == 1} ch_title_skip{/if}" >
    <div class="ch_hash">{$program.ch_hash}</div>
    <div class="ch_disc">{$program.channel_disc}</div>
    <div class="ch_skip">{$program.skip}</div>
    <div class="ch_sid">{$program.sid}</div>
    <div class="ch_name">{$program.station_name}</div>
    <div id="ch_title_str_{$program.ch_hash}" {if $program.skip == 1}class="ch_skip_color" {/if} style="cursor: pointer;" onClick="javascript:PRG.chdialog('{$program.ch_hash}')" >{$program.station_name}</div>
  </div>
  {else}
  <div class="ch_title"><div style="font-style:italic">no epg</div></div>
  {/if}{/foreach}
</div>

<br style="clear:left;" />
<div id="prg_info"><div class="dummy">&nbsp;</div></div>
</div>

<div id="float_titles_dummy" style="width:1410px;height:120px;">&nbsp;</div>


<div id="tvtable">

<div id="tvtimes">
  {foreach from=$tvtimes item=time}
  <div class="tvtime">{$time}</div>
  {/foreach}
</div>

<div id="tv_chs" style="width: {$chs_width}px" >
   {foreach from=$programs item=program}{if isset($program.ch_hash)}
   <div id="tv_chs_{$program.ch_hash}" class="ch_set{if $program.skip == 1} ch_set_skip{/if}" >
    <div class="ch_programs" >
	{else}
	<div class="ch_set"><div class="ch_programs">
    {/if}{foreach from=$program.list item=item}{if isset($item.id)}
      <div {if $item.id}id="prgID_{$item.id}"{/if} class="prg {if ! $item.id}prg_none {/if} ctg_{$item.category_name}{if $item.rec gt 0} prg_rec{/if}" style="height:{$item.height}px;">
        <div class="prg_dummy">
          <div class="prg_title">{$item.title|escape}</div>
          <div class="prg_subtitle">{$item.starttime}</div>
          <div class="prg_desc">{$item.description|escape}</div>
          <div class="prg_channel">{$item.channel}</div>
          <div class="prg_start">{$item.prg_start}</div>
          <div class="prg_duration">{$item.duration}</div>
          <div class="prg_id">{$item.id}</div>
        </div>
      </div>
	{else}
	<div class="prg prg_none ctg_none"{if isset($item.height)} style="height:{$item.height}px;"{/if}><div class="prg_dummy"><div class="prg_title">&nbsp;</div></div></div>
    {/if}{/foreach}
    </div>
   </div>
   {/foreach}
 </div>

 <div id="tvtimes2" style="top : 0px; left: {math equation="x + 40" x=$chs_width}px" >
  {foreach from=$tvtimes item=time}
    <div class="tvtime">{$time}</div>
  {/foreach}
 </div>
</div>
</div>

{include file='INISet.tpl'}
<script type="text/javascript" src="{$home_url}js/index.js"></script>
{include file='footer.tpl'}
