{include file='header.tpl'}

<div class="container nonborderbox">
	<h2>EpgRec ログイン画面</h2>
	<form method="post" action="{$this_class->getCurrentUri()}">
	<fieldset>
		<div class="form-group">
			<label for="login_name" class="control-label">ログイン名 :</label>
			<input type="text" name="login_name" id="login_name" value="" maxlength="32" class="form-control" />
		</div>
		<div class="form-group">
			<label for="passwd" class="control-label">パスワード :</label>
			<input type="password" name="passwd" id="passwd" value="" maxlength="16" class="form-control" />
		</div>
		<input type="submit" name="btn_action" value="ログイン" class="btn btn-primary" />
	</fieldset>
	<input type="hidden" name="token" value="{$token}" />
	</form>
{if $error_msg|@count > 0}
	<div class="attentionMessage">
		<ul>
{foreach from=$error_msg item=value}
			<li>{$value}</li>
{/foreach}
		</ul>
	</div>
{/if}
</div>

{include file='INISet.tpl'}
{include file='footer.tpl'}
