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
<form id="system_setting" method="post" action="{$post_to}" class="formSetting">

<h2>データベース設定</h2>

<h3>データベースドライバー名</h3>
<div class="setting">
<div class="caption">データベースのドライバー名を選択してください。</div>
{html_options name="db_type" options=$pdo_driver selected=$settings->db_type}
</div>

<h3>データベースホスト名</h3>
<div class="setting">
<div class="caption">データベースのホスト名を入力してください。</div>
<input type="text" name="db_host" value="{$settings->db_host}" size="15" class="required" />
</div>

<h3>データベースポート</h3>
<div class="setting">
<div class="caption">データベースのポートを入力してください。</div>
<input type="text" name="db_port" value="{$settings->db_port}" size="15" class="required" />
</div>

<h3>データベース接続ユーザー名</h3>
<div class="setting">
<div class="caption">データベースの接続に使用するユーザー名を入力してください。</div>
<input type="text" name="db_user" value="{$settings->db_user}" size="15" class="required" />
</div>

<h3>データベース接続パスワード</h3>
<div class="setting">
<div class="caption">データベースの接続に使用するパスワードを入力してください。</div>
<input type="text" name="db_pass" value="{$settings->db_pass}" size="15" class="required" />
</div>

<h3>使用データベース名</h3>
<div class="setting">
<div class="caption">使用するデータベース名を設定します。設定するデータベースは接続ユーザーがテーブルの作成等を行う権限を持っている必要があります。</div>
<input type="text" name="db_name" value="{$settings->db_name}" size="15" class="required" />
</div>

<h3>テーブル接頭辞</h3>
<div class="setting">
<div class="caption">テーブル名の冒頭に追加する接頭辞です。epgrecの再インストールを旧テーブルを使用せずに行うようなケースを除き、デフォルトのままで構いません。</div>
<input type="text" name="tbl_prefix" value="{$settings->tbl_prefix}" size="15" class="required" />
</div>

<h2>インストール関連設定</h2>

<h3>インストールURL</h3>
<div class="setting">
<div class="caption">epgrecをLAN内のクライアントから参照することができるURLを設定します。http://localhost…のままで利用することも可能ですが、その場合はビデオの視聴等がサーバー上でしかできないなどの制限が生じます。</div>
<input type="text" name="install_url" value="{$settings->install_url}" size="40" class="required" />
</div>

<h3>録画保存ディレクトリ</h3>
<div class="setting">
<div class="caption">録画ファイルを保存するディレクトリを{$install_path}からの相対ディレクトリで設定します。先頭に/が必ず必要です。設定するディレクトリには十分な空き容量があり、書き込み権が必要です。また、URLで参照可能なディレクトリなディレクトリを設定しないとASFによる録画の視聴ができません。デフォルトは/video（つまり{$install_path}/video）で、とくに問題がなければデフォルトを推奨します。</div>
<input type="text" name="spool" value="{$settings->spool}" size="15" class="required" />
</div>

<h3>サムネールの使用</h3>
<div class="setting">
<div class="caption">録画済み一覧にサムネールを入れるかどうかを設定します。サムネールを利用するにはffmpegが必要です。ffmpegをお持ちでない方は「使用しない」を設定してください。</div>
<select name="use_thumbs" id="id_use_thumbs" onchange="javascript:PRG.thumbs()" >
  <option value="0" {if $settings->use_thumbs == 0} selected="selected"{/if}>使用しない</option>
  <option value="1" {if $settings->use_thumbs == 1} selected="selected"{/if}>使用する</option>
</select>
</div>

<h3>ffmpegのパス</h3>
<div class="setting">
<div class="caption">サムネール作成に使うffmpegのパスを設定します。フルパスを設定してください。</div>
<input type="text" id="id_ffmpeg" name="ffmpeg" value="{$settings->ffmpeg}" size="40" class="required" />
</div>

<h3>サムネール保存ディレクトリ</h3>
<div class="setting">
<div class="caption">サムネールを保存するディレクトリを{$install_path}からの相対パスで設定します。設定の方法、条件は録画保存ディレクトリと同様です。</div>
<input type="text" id="id_thumbs" name="thumbs" value="{$settings->thumbs}" size="15" class="required" />
</div>

<h3>EPG取得用テンポラリファイルの設定</h3>
<div class="setting">
<div class="caption">EPG取得に用いる録画データとXMLデータのパスを設定します。通常、この設定を変える必要はありませんが、/tmpに十分な空き容量（500MB程度）がない環境では異なるパスを設定してください。パスはWebサーバーから書き込み可能になっている必要があります</div>

<div><b>録画データ：</b><input type="text" name="temp_data" value="{$settings->temp_data}" size="30" class="required" /></div>
<div><b>XMLファイル：</b><input type="text" name="temp_xml" value="{$settings->temp_xml}" size="30" class="required" /></div>
</div>

<h3>使用コマンドのパス設定</h3>
<div class="setting">
<div class="caption">epgrecが内部的に使用するコマンドのパスを設定します。ほとんどの場合、設定を変える必要はないはずです。</div>
<div><b>epgdump：</b><input type="text" name="epgdump" value="{$settings->epgdump}" size="30" class="required" /></div>
<div><b>at：</b><input type="text" name="at" value="{$settings->at}" size="30" class="required" /></div>
<div><b>atrm：</b><input type="text" name="atrm" value="{$settings->atrm}" size="30" class="required" /></div>
<div><b>sleep：</b><input type="text" name="sleep" value="{$settings->sleep}" size="30" class="required" /></div>
</div>

<h3>省電力の設定</h3>
<div class="setting">
<div class="caption">シャットダウンからの復帰および起動をACPIタイマーを使って行い録画機の消費電力を低減させます。この機能を使うためには、お使いのLinux機がACPIタイマーでシャットダウン状態から確実に復帰できる必要があるので注意してください。ACPIタイマーが使えないPCで設定を行った場合、録画は正常に行えません。また、ACPIタイマーの動作が不確実なPCでは録画の失敗の確率が高くなります。詳しくはドキュメントを参照してください。</div>
<select name="use_power_reduce" id="id_use_power_reduce" onchange="javascript:PRG.power_reduce()" >
  <option value="0" {if $settings->use_power_reduce == 0} selected="selected"{/if}>使用しない</option>
  <option value="1" {if $settings->use_power_reduce == 1} selected="selected"{/if}>使用する</option>
</select>
</div>

<h3>録画スタート前に起動させる時間（分）</h3>
<div class="setting">
<div class="caption">録画開始より前に起動させる時間を分単位で設定します。5分以上の値を設定したほうが無難でしょう。省電力が有効なときに使う値です。</div>
<input type="text" name="wakeup_before" id="id_wakeup_before" value="{$settings->wakeup_before}" size="2" class="required digits" />
</div>

<h3>EPGを取得する間隔（時間）</h3>
<div class="setting">
<div class="caption">EPGを取得する間隔を時間単位で設定します。省電力が有効なときに使う値です。</div>
<input type="text" name="getepg_timer" id="id_getepg_timer" value="{$settings->getepg_timer}" size="2" class="required digits" />
</div>

<h3>Webサーバーのユーザーおよびグループ名</h3>
<div class="setting">
<div class="caption">Webサーバーのユーザー名およびグループ名を設定してください。省電力が有効なときに使う値です。</div>
<div><b>グループ名：</b><input type="text" name="www_group" id="id_www_group" value="{$settings->www_group}" size="30" class="required" /></div>
<div><b>ユーザー名：</b><input type="text" name="www_user" id="id_www_user"  value="{$settings->www_user}" size="30" class="required" /></div>
</div>

<h3>シャットダウンコマンド</h3>
<div class="setting">
<div class="caption">シャットダウンさせるコマンドを設定してください。省電力が有効なときに使う値です。</div>
<div><b>グループ名：</b><input type="text" name="shutdown" id="id_shutdown" value="{$settings->shutdown}" size="40" class="required" /></div>
</div>

<input type="hidden" name="token" value="{$token}" />
<input type="submit" value="設定を保存する" id="system_setting-submit" />
</form>
</div>
{include file='INISet.tpl'}
<script type="text/javascript" src="{$home_url}js/setting.js"></script>
<script type="text/javascript">
<!--
{literal}
$(document).ready(function(){
	$("#system_setting").validate({
		rules : {
			wakeup_before: { min: 5, max: 60 },
			getepg_timer: { min: 2, max: 24 }
		}
	});
	PRG.thumbs();
	PRG.power_reduce();
});
{/literal}
-->
</script>
{include file='footer.tpl'}
