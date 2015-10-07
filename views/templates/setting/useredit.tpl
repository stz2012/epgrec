{include file='header.tpl'}

<div class="container">
<h2>{$sitetitle}</h2>
{if $message|default:'' != ''}
{$message}
{else}
<p><a href="{$home_url}index">設定せずに番組表に戻る</a> / <a href="{$home_url}setting">環境設定へ</a> / <a href="{$home_url}setting/viewLog">動作ログを見る</a></p>
{/if}
</div>

<div class="container">
<form id="user_setting" method="post" action="{$post_to}" class="formSetting">

<h2>ユーザ設定</h2>

<h3>ユーザー名</h3>
<div class="setting">
<div class="caption">ユーザー名を入力してください。</div>
<input type="text" name="user_name" value="{$user_data.name}" size="15" class="required" />
</div>

<h3>ログイン名</h3>
<div class="setting">
<div class="caption">ログイン名を入力してください。</div>
<input type="text" name="login_user" value="{$user_data.login_user}" size="15" class="required" />
</div>

<h3>ログインパスワード</h3>
<div class="setting">
<div class="caption">パスワードを入力してください。</div>
<input type="password" name="login_pass" value="{$user_data.login_pass}" size="15" class="required" />
</div>

<input type="hidden" name="token" value="{$token}" />
<input type="submit" value="設定を保存する" id="user_setting-submit" />
</form>
</div>
{include file='INISet.tpl'}
<script type="text/javascript">
<!--
{literal}
$(document).ready(function(){
	$("#user_setting").validate({
		rules : {
			login_user: { min: 8, max: 16 },
			login_pass: { min: 8, max: 16 }
		}
	});
});
{/literal}
-->
</script>
{include file='footer.tpl'}
