{literal}
<script type="text/javascript">
var INISet = {
{/literal}
	prgHomeURL : '{$home_url}',				// ホーム
	prgProgramURL : '{$home_url}index',			// 番組表
	prgSearchURL : '{$home_url}search',			// 番組検索
	prgKeywordURL : '{$home_url}search/keyword',		// キーワード
	prgRecprogURL : '{$home_url}recprog',			// 録画予約一覧
	prgRecordedURL : '{$home_url}recprog/recorded',		// 録画済一覧
	prgSettingURL : '{$home_url}setting',			// 環境設定
	prgRecordURL : '{$home_url}index/simple',		// 簡易予約
	prgRecordPlusURL : '{$home_url}index/custom',		// 詳細予約
	prgChangeURL : '{$home_url}index/change',		// 予約変更
	prgCancelURL : '{$home_url}index/cancel',		// 予約キャンセル
	prgReservFormURL : '{$home_url}index/reserveForm',	// 予約フォーム
	prgSetChannelURL : '{$home_url}index/setChannelInfo',	// チャンネル設定
	prgDelKeyURL : '{$home_url}search/delete'		// キーワード削除
{if $this_class->getControllerName() == 'index'}
	,
	dotHour : {$height_per_hour},
	dotMin : {$height_per_min},
	tableStartTime : '{$top_time}',
	tableEndTime : '{$last_time}',
	ch_width : {$ch_set_width},
	num_ch : {$num_ch},
	num_all_ch : {$num_all_ch}
{/if}
{literal}
};
</script>
{/literal}