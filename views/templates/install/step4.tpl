{include file='header.tpl'}

<div class="container">
<h2>{$sitetitle}</h2>

<p>初期設定が完了しました。下のボタンをクリックするとEPGの初回受信が始まります。<br />
EPGの受信には20～50分程度はかかります。初回受信が終了するまで番組表は表示できません。</p>

<p>EPG受信後、/etc/cron.d/以下にcronによるEPG受信の自動実行を設定する必要があります。<br />
Debian/Ubuntu用の設定ファイルは{$install_path}/docs/cron.d/getepgです。Debian/Ubuntuをお使いの方は<br />
<pre>
$ sudo cp {$install_path}/docs/cron.d/getepg /etc/cron.d/ [Enter]
</pre>
という具合にコピーするだけで動作するでしょう。<br />
それ以外のディストリビューションをお使いの方はDebian/Ubuntu用の設定ファイルを参考に、適切にcronの設定を行ってください。</p>

<p>なお、設定ミスや受信データの異常によってEPGの初回受信に失敗すると番組表の表示はできません。<br />
設定ミスが疑われる場合、<a href="{$this_class->getCurrentUri(false)}/index">インストール設定</a>を実行し直してください。<br />
また、手動でEPGの受信を試みるのもひとつの方法です。コンソール上で、<br />
<pre>
$ {$install_path}/scripts/getEpg.php [Enter]
</pre>
と実行してください。
</p>

<form method="post" action="{$this_class->getCurrentUri(false)}/step5">
<input type="hidden" name="token" value="{$token}" />
<input type="submit" value="クリックするとEPGの初回受信が始まります" />
</form>
</div>

{include file='INISet.tpl'}
{include file='footer.tpl'}
