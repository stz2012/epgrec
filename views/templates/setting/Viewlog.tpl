<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>{$sitetitle}</title>
<meta http-equiv="Content-Style-Type" content="text/css">

{literal}
<style type="text/css">
<!--
body {padding:4px;margin:0;font-size:10pt;}
a {text-decoration:none;}

table#log_table {
    width: 800px;
    border: 1px #BBB solid;
    border-collapse: collapse;
    border-spacing: 0;
}

table#log_table th {
    padding: 5px;
    border: #E3E3E3 solid;
    border-width: 0 0 1px 1px;
    background: #BBB;
    font-weight: bold;
    line-height: 120%;
    text-align: center;
}
table#log_table td {
    padding: 5px;
    border: 1px #BBB solid;
    border-width: 0 0 1px 1px;
    text-align: center;
}

table#log_table td.errorlevel0 {background-color: #FFFFFF;}
table#log_table td.errorlevel1 {background-color: yellow;}
table#log_table td.errorlevel2 {background-color: red;}

-->
</style>
{/literal}

</head>

<body>

<h2>{$sitetitle}</h2>

<div><a href="index.php">番組表に戻る</a></div>

<table id="log_table">
 <tr>
  <th>レベル</th>
  <th>日時</th>
  <th>内容</th>
 </tr>

{foreach from=$logs item=log}
 <tr>
  <td class="errorlevel{$log->level}">
    {if $log->level == 0}情報
    {elseif $log->level == 1}警告
    {elseif $log->level == 2}エラー
    {/if}
  </td>
  <td>{$log->logtime}</td>
  <td>{$log->message|escape}</td>
 </tr>
{/foreach}
</body>
</html>
