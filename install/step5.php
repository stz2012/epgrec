<?php
include_once( "../config.php");

$settings = Settings::factory();

echo 'EPGの初回受信を行います。20～50分程度後に<a href="'.$settings->install_url.'">epgrecのトップページ</a>を開いてください。';

@system( INSTALL_PATH."/getepg.php &" );

exit();

?>