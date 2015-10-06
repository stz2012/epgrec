/*
 * 	epgrec assist
 *
 *		version 0.1.3.0 customized by stz2012
 *		2:43 2012/08/15
 *
 *		Auther : osamu / atelier TRUMPHOUSE
 *		Twitter : trumphouse
 *		Download : http://sourceforge.jp/users/trumphouse/pf/epgrec_assist/
 *
 */

var ASSIST_INI = ASSIST_INI || {};

$(function(){
// ユーザー設定　================= ここから

// epgrec_assistを有効にする
// 	epgrecのバージョンアップ等による
//		不具合が出た場合は 0 にしてください
ASSIST_INI.use_this = 1;

// 時間表示を12時間表示
ASSIST_INI.time_ap = 1;

// 番組表で有効にする
ASSIST_INI.index = 1;
// 番組表、現在時刻を示す赤線を自動更新
//	単位は秒、0で更新しない
ASSIST_INI.index_nowbar_live = 10;

// 番組検索で有効にする
ASSIST_INI.search = 1;

// キーワードで有効にする
ASSIST_INI.keyword = 1;

// 録画予約一覧で有効にする
ASSIST_INI.reserved = 1;

// 録画済一覧で有効にする
ASSIST_INI.recorded = 1;
// 録画済一覧、データ削除時ファイルも削除
ASSIST_INI.recorded_delete_with_file = 1;

// 予約、録画済み一覧で番組選択後、選択された番組のみ表示
ASSIST_INI.selv = 0;

// 環境設定で有効にする
ASSIST_INI.envsetting = 1;

// ディスク残量を表示（ajax ver）
ASSIST_INI.ext_diskinfo = 1;
// 残量表示警告色、切り替え割合（％）
// HDDの容量に応じて変更してください
ASSIST_INI.ext_diskinfo_levels = [50,65,80,90];
// 残量表示に予約領域も表示
ASSIST_INI.ext_diskinfo_rsvBar = 1;
// 予約領域計算用ビットレート
ASSIST_INI.ext_diskinfo_rsvBar_bitrates = {GR : 16.85 , BS : 26.1 ,CS : 26.1};

// ユーザー設定　================= ここまで

});

(function($){
/**
 * jQuery Plugin
 * 数字(n)を(i)桁の頭0付きの文字列に変換する
 *
 * @param	{Number}	n 数字
 * @param	{Number}	i 返信文字列の桁数
 * @return	{String}	頭0付きの文字列に変換されたn
 */
	$.numToStr = function(n, i) {
		var S = new Array(i+1).join('0')+n;
		return S.substr(S.length-i,i);
	};
	$.N2S = $.numToStr;
/**
 * jQuery Plugin
 */
	$.fn.paddingW = function() {
		return parseInt($(this).css('padding-left'),10)+parseInt($(this).css('padding-right'),10);
	};
	$.fn.paddingH = function() {
		return parseInt($(this).css('padding-top'),10)+parseInt($(this).css('padding-bottom'),10);
	};
})(jQuery);

var ER_sub = ER_sub || {};
ER_sub.__PAGE = 'index';
ER_sub.INI = function() {
	$('body').append('<style type="text/css"><!-- ' + ER_sub.STYLES.basic + ER_sub.STYLES.md_screen + ' --></style>');
}

// 共通メニュー
ER_sub.topMenu = function(tg) {
	var  t = '',
	style = '<style type="text/css"><!-- #topMenu{background:#000;position:fixed;top:0;right:0;letter-spacing:1px;padding:0.5em 1em;line-height:100%;border-radius: 0 0 0 1.2em;}#topMenu a{display:inline-block;padding:0.4em 0.5em;margin:0 0.3em;color:#DDD;border:2px solid transparent;}#topMenu a.selected{color:#46A;border-bottom-color:#046;}#topMenu a:hover{color:#FFF;border-bottom-color:#48F;}@media screen and (max-width:1100px) {#topMenu a{padding:0.3em;}}@media screen and (max-width:1000px) {#topMenu a{padding:0.2em;}}@media screen and (max-width:750px) {#topMenu a{letter-spacing:0;margin:0 0.1em;}} --></style>';
	t +='<a href="'+INISet.prgProgramURL+'" data-page="index">番組表</a><a href="'+INISet.prgSearchURL+'" data-page="programtable"><span class="WD90">番組</span>検索</a><a href="'+INISet.prgKeywordURL+'" data-page="keywordtable"><span class="WD90N">KW</span><span class="WD90">キーワード</span></a><a href="'+INISet.prgRecprogURL+'" data-page="reservationtable"><span class="WD90">録画</span>予約<span class="WD90">一覧</span></a><a href="'+INISet.prgRecordedURL+'" data-page="recordedtable">録画済<span class="WD90">一覧</span></a><a href="'+INISet.prgSettingURL+'" data-page="envsetting"><span class="WD90">環境</span>設定</a>';
	$('body').append(style);
	tg = $(tg || 'body');
	tg.append('<div id="topMenu">'+t+'</div>');
	$('#topMenu > a[data-page='+ER_sub.__PAGE+']').addClass('selected');
};

// CSSカスタマイズ
ER_sub.STYLES = {
	basic : 'body {background:#222;color:#EEE;padding:0;}.vsblN {visibility:hidden}.unSel{ -moz-user-select: none;-khtml-user-select: none;-webkit-user-select: none;-ms-user-select:none;user-select: none;cursor:default;}.btnB{color:#EEE;background:#333;display:inline-block;padding:0.6em 1.2em;margin:0.4em;letter-spacing:2px;line-height:1em;border:1px solid #666;border-radius:0.6em;}.btnB:hover{color:#FFF;background:#222;}'+
	'.ezBtn{display:inline-block;padding:0.4em 0.8em !important;border-radius:0.6em;}.ezBtn.uTri:after{content:\'\';display:inline-block;overflow:hidden;position:relative;margin:0 0.2em;top:3px;width:0px;height:0px;border:5px solid transparent;border-top-color:#EEE;padding:0;}.ezBtn.selected{background:#27D;color:#FFF !important;}.ezChk{color:#CCC;}.ezChk:hover{color:#FFF;}.ezChk:before{content:\'\';display:inline-block;width:20px;height:20px;vertical-align:middle;margin-right:0.5em;background:url('+INISet.prgHomeURL+'assist/imgs/ic20.png) ;}.checked.ezChk:before{background-position:0px -20px;}.spblock {display:inline-block;border-radius:0.4em;font-size:85%;background:#555;padding:0 0.4em;margin:0 0.5em;}',
	md_screen : '.WD100N,.WD90N,.WD70N{display:none;}@media screen and (max-width:1100px) {.WD110{display:none !important;}}@media screen and (max-width:1000px) { .WD100{display:none !important;}.WD100N{display:inherit;}#jmpTimes .dates a{width:3.4em;}#jmpTimes .dates a.now span{font-size:100%;}#jmpTimes .dates a .num{font-size:160%;}#jmpTimes a.hour{padding:0.1em 0;}#jmpTimes a{width:1.42em;}#float_titles div.ch_title div {font-size:90%;padding:0.5em 0;}table .ch_Box{max-width:5em;color:#CCC;text-align:left;padding:0.15em 0.3em;}table .ctg_Box{color:#CCC;text-transform:capitalize;padding:0.15em;}#twrap0 table td,#twrap0 table td.title span.title{font-size:100% !important;}}@media screen and (max-width:900px) {.WD90{display:none !important;}.WD90N{display:inherit;}}@media screen and (max-width:900px) {table#reservation_table td{padding:3px;}}@media screen and (max-width:700px) {#reservation_table td:first-child{padding-left:1em;}#jmpTimes .dates a{width:2.6em;}#jmpTimes .dates a .num{font-size:140%;}#jmpTimes a.hour{width:1.1em;} #float_titles div.ch_title div {padding:0.3em 0;}.WD70{display:none !important;}.WD70N{display:block;}span.WD70N{display:inline;} table .ch_Box {max-width:3em;}}',
	colorD : '.dotBox.ctg_news, .ctg_Box.ctg_news { background:#992; }.dotBox.ctg_drama, .ctg_Box.ctg_drama { background:#282; }.dotBox.ctg_variety, .ctg_Box.ctg_variety { background:#836; }.dotBox.ctg_etc, .ctg_Box.ctg_etc { background:#444; }.dotBox.ctg_information, .ctg_Box.ctg_information { background:#528; }.dotBox.ctg_anime, .ctg_Box.ctg_anime { background:#962; }.dotBox.ctg_sports, .ctg_Box.ctg_sports { background:#277; }.dotBox.ctg_music, .ctg_Box.ctg_music { background:#258; }.dotBox.ctg_cinema, .ctg_Box.ctg_cinema { background:#933; }',
	table : function(t){return ' h2 {font-size:140%;margin:0;padding:0.6em 2em;}#fmWrap{text-align:right;} div.fmBox{display:inline-block;color:#444;background:#BBB;margin:0 2em;padding:0.4em 1em;border-radius:0.8em;}div.fmBox form{display:inline;}#tblSel{padding:1em 2em;}#mutiPrgCnt{margin-right:2em;}#selCount{font-size:140%;font-weight:bold;}.btnB{margin:0 2em;}@media screen and (max-width:800px) {h2{font-size:110%;}div.fmBox{display:block;padding:0.2em 0.6em;margin:0;border-radius:0;}#twrap0 table th{padding:0.4em 0;}.btnB{padding:0.4em 1em;margin:0 0.4em;}}@media screen and (max-width:700px) {div.fmBox input{}div.fmBox select{width:5em;}#mutiPrgCnt{margin-right:0.5em;}#selCount{font-size:110%;}#tblSel {padding:0.7em 0.8em;}} table#'+t+'{width:100%;margin:0;font-size:110%;}table#'+t+',table#'+t+' tr ,table#'+t+',table#'+t+' td ,table#'+t+' th {border:none;color:#AAA;background:#181818;}table#'+t+' th {color:#888;background-color:#222;font-size:85%;font-weight:normal;letter-spacing:2px;padding:0.5em 0 0.8em;overflow:hidden;}#'+t+' td{padding:0.3em;}#'+t+' tbody tr:first-child td{border-top:10px solid #181818;}#'+t+' tbody tr.last td{border-bottom:10px solid #181818;}#'+t+' td:first-child{padding-left:2em;}table#'+t+' td.date span.year{display:block;}table#'+t+' tr:hover td, table#'+t+' tr.selected td {background-color:#12304A;}table#'+t+' tr.rsv:hover td, table#'+t+' tr.rsv.selected td {background-color:#341814;}table#'+t+' tr.error td{background-image:url('+INISet.prgHomeURL+'assist/imgs/stripe_w3.png);background-color:#400;background-attachment: fixed;}tr:hover .vsblN.hv{visibility:visible;}table#'+t+' tr:hover td,table#'+t+' tr:hover td.title .title {color:#FFF;}#'+t+' td.date,#'+t+' td.time{font-size:120%;font-weight:bold;color:#CCC;text-align:right;white-space:nowrap;} table#'+t+' td.title{position:relative;text-align:left;}table#'+t+' td.title>div{max-height:1.4em;-webkit-transition: max-height 0.2s linear;overflow:hidden;} table#'+t+' td.title .desc{color:#478}table#'+t+' tr:hover td.title .desc{color:#ADD}table#'+t+' tr.rsv td.title .desc{color:#952;line-height:1.5em;}table#'+t+' tr:hover.rsv td.title .desc{color:#FA8}table#'+t+' tr.dSplit  td,table#'+t+' tr.mSplit td,table#'+t+' tr.ySplit td{padding:1px;overflow:hidden;border:solid #181818;border-width:4px 0;background:#282828;}table#'+t+' tr.mSplit td{padding:2px;background:#444;}table#'+t+' tr.ySplit  td{background:#444;text-align:center;letter-spacing:2px;} table#'+t+' td.title .title{font-size:110%;font-weight:bold;color:#CCC} table#'+t+' td.title a.title:hover{text-decoration:underline;}td.date,td.time,td.year{font-family:arial,helvetica;}td.date .dString{letter-spacing:2px;}td.date .year .sub,td.date .month .sub,td.date .date .sub{font-size:70%;color:#666;font-weight:normal;}td.date .dNum,td.time .dNum{font-size:110%;} td.date .dw{color:#888;font-size:90%;padding-left:0.2em;}td.date .dw_xx{display:inline-block;padding:0.1em 0.8em;margin:0 0.4em;vertical-align:middle;font-size:70%;background:#666;color:#FFF;border-radius:0.6em;}td.date .dw.dw_0{color:#C44;}td.date .dw.dw_6{color:#46C;}td.time .ap{font-size:80%;color:#888;padding-right:0.4em;}td.time .ap_time .zero{visibility:hidden;}table td.time .durTxt{color:#666;}table .ctg_Box,table .ch_Box{color:#DDD;font-size:90%;display:block;overflow:hidden;padding:0.2em 0.4em;white-space:nowrap;border-radius:0.6em;}table .ch_Box{width:8em;background:#2D2D2D;}table .img.play,table .img.rsvicon {display:inline-block;position:relative;width:24px;height:18px;vertical-align:-0.2em;margin-right:0.6em;}table a.play{line-height:999px;display:inline-block;width:24px;height:18px;border-radius:0.4em;overflow:hidden;}table tr:hover a.play{background:#066;}table a.play:after{content:\'\';display:block;width:0px;height:0px;position:absolute;top:50%;left:50%;margin-top:-6px;margin-left:-3px;border:6px solid transparent;border-left-color:#2AA}table tr:hover a.play:after{border-left-color:#FFF;}table tr a.play:hover{background:#08B;}table td.title .img.rsvicon{text-align:center;vertical-align:2px;background:transparent;color:#A00;border-radius:0.4em;}table td.title .rsvicon.auto{background:#622;color:#000;}table td.title .rsvicon.auto span{display:inline-block;font-size:80%;font-weight:bold;}.spblock.mode{font-size:80%;font-weight:bold;background:#457;color:#000;vertical-align:2px;margin:0 1em 0 0;}table tr:hover td.time .durTxt,table tr:hover .ctg_Box, table tr:hover .ch_Box, table tr:hover td.title .spblock, table tr:hover td.title .rsvicon.auto{color:#FFF;}'+
	'table .moreBtn{display:none;position:absolute;right:0;top:0;font-size:90%;padding:0.4em;background:#12304A;color:#FFF;}table .moreBtn:hover{text-decoration:underline;}ttable tr.rsv .moreBtn{background:#341814;}table tr.rsv .moreBtn{background:#341814;}table tr:hover .moreBtn{display:block;} table#'+t+' tr.moreOpen td.title>div{max-height:50em;}'+
	'#'+t+' tr.reserved:hover td,#'+t+' tr.reserved.selected td{background:#511;}#'+t+' tr.reserved td.title .desc{color:#945;}#'+t+' tr.reserved:hover td.title .desc{color:#D88;}'+
	'#'+t+' td.thumb {padding:0.1em 0.2em;}#'+t+' td.thumb img {height:36px;}'+
	'#'+t+' tr.moreOpen td.thumb img {height:auto;}'+
	'#'+t+' tbody tr.selVnone {display:none;}#selvAll {font-size:140%;text-align:center;background:#011;margin-top:0.5em;padding:2em;}#'+t+'.selOnly tbody .vsblN{visibility:visible;}'+
	'#tblMenu{position:relative;padding:0;} #tblMes{position:absolute;top:0;width:99%;text-align:center;display:none;padding:1em 0;z-index:5000;}#tblMes .Box{display:inline-block;position:relative;padding:0.3em 2em;text-align:left;border-radius:0.6em;background-color:#DDD;}#tblMes .Box div.title{color:#400;font-size:120%;border-bottom:2px solid #EEE;border-color:rgba(15,15,15,0.4);margin-bottom:0.6em;}#tblMes .Box .title{font-weight:bold;}#tblMes .Box .mesSplit {opacity:0.4;}#tblMes .Box.error{background:#F55;border:2px solid #F77;box-shadow:0 0 16px #000;padding:0.6em 1em 0.4em 34px;}#tblMes .Box.error:after{content:\'\';display:block;width:20px;height:20px;top:50%;left:6px;margin-top:-10px;position:absolute;background:url('+INISet.prgHomeURL+'assist/imgs/icon20_warning.png) no-repeat;opacity:0.6;}#tblMes .error .title{color:#800;font-size:110%;}#tblMes .Box.green{background:#086;}#tblMes .Box.orange{background:#E90;}.iconW20{display:inline-block;width:20px;height:20px;background:url('+INISet.prgHomeURL+'assist/imgs/ic20.png);margin:0 0.3em;}.iconW20.ic_chkCnt{background-position:0 20px;}.iconW20.ic_chkCnt.plus{background-positin:0 20;}.iconW20.ic_check{background-position:20px 0px }table tr:hover .iconW20.ic_check {background-position-x:0;}table tr.selected .iconW20.ic_check{background-position:0 60px;}'}
}

// ================== 番組表
// 番組再設定
// ER_sub.rePRG.add(function($tg){})しておくと
// 表示中の番組に対して初期処理する
ER_sub.rePRG = {
	Fns : [],
	ini : function() {
		var that = this, $tg, i, iMax, Fn;
		$('#tv_chs>:visible>.ch_programs>div').each(function(){
			$tg = $(this);
			for (i = 0, iMax = that.Fns.length; i < iMax; i++) {
				Fn = that.Fns[i];
				Fn($tg);
			}
		});
	},
	add: function(fn){
		this.Fns.push(fn);
	}
};

ER_sub.INDEX = {
	style : 'body {background:#666;} #float_follows{position:relative;} #float_titles{position:fixed;top:0;} #ch_title_bar {letter-spacing:1px;} #tvtable div.ch_set {background:#888;position:relative;} #tvtable div.prg{position:relative;background-image: url('+INISet.prgHomeURL+'assist/imgs/prg_bg3.png);cursor:pointer;box-shadow:inset 0 1px 3px hsla(0,0%,100%,0.6);} #tvtable div.ctg_etc, #category_select a.ctg_etc, .ctg_BOX.ctg_etc {color:#666;background-color: #FFFFFF;} #tvtable div.ctg_news, #category_select a.ctg_news, .ctg_BOX.ctg_news {color:#6F6F48;background-color: #FFFFD8;} #tvtable div.ctg_information, #category_select a.ctg_information, .ctg_BOX.ctg_information {color:#644876;background-color: #F2D8FF;} #tvtable div.ctg_sports, #category_select a.ctg_sports, .ctg_BOX.ctg_sports {color:#486F6F;background-color: #D8FFFF;} #tvtable div.ctg_cinema, #category_select a.ctg_cinema, .ctg_BOX.ctg_cinema {color:#6F4848;background-color: #FFD6D0;} #tvtable div.ctg_music, #category_select a.ctg_music, .ctg_BOX.ctg_music  {color:#4F537B;background-color: #D4DFFF;} #tvtable div.ctg_drama, #category_select a.ctg_drama, .ctg_BOX.ctg_drama  {color:#4F6F46;background-color: #E2FFD4;} #tvtable div.ctg_anime, #category_select a.ctg_anime, .ctg_BOX.ctg_anime  {color:#6F5238;background-color: #FFEFCF;} #tvtable div.ctg_variety, #category_select a.ctg_variety, .ctg_BOX.ctg_variety {color:#764264;background-color: #FFD2EB;} #tvtable div.ctg_hide, #category_select a.ctg_hide {background-color: #F4F4F4;color:#AAA;} #tvtable div.prg_none {background-color:#AAA;cursor:default;} #tvtable div.prg_rec  {background-color: #F55;color:#FEE} #tvtable div.prg_rec.prg_pass  {background-color: #977;color:#FCC} #tvtable div.ctg_hide .prg_title, #category_select a.ctg_hide .prg_title{color:#777;} #tvtable div.prg_hover .prg_title {color:white;} #tvtable div.prg.prg_hover {background-color: #28D;color:#EFF;} #tvtable div.prg_pass {color:#666;background-color:#BBB;}#tvtable div.prg_pass,#tvtable div.prg_none{box-shadow:inset 0 1px 3px hsla(0,0%,100%,0.4);}#tvtable div.prg_pass.prg_hover {background-color: #678;color:#EEE}#float_titles .set2 {display:inline-block;padding:0.8em 0.2em;margin:0.4em 0.2em; background-color:#333;font-family: arial,helvetica;} #ch_title_bar .no_epg {color:#888;font-style:italic;cursor:default;}'+
	'#tvtable .rectoggle{display:none;position:absolute; top:0;right:0;background:#06B;border-bottom-left-radius:8px;}#tvtable .prg_hover .rectoggle {display:block;}#tvtable .prg_hover.prg_pass .rectoggle {display:none;}#tvtable .rectoggle:hover{background:#F40}#tvtable .rectoggle a{padding:0.2em 0.6em;color:#EEE;}#tvtable .rectoggle a:hover{text-decoration:underline;}'+
	'.IErnd10l {width:10px;height:10px;position:absolute;top:0;left:0;filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src="'+INISet.prgHomeURL+'assist/imgs/ie_r10_l.png");}.IErnd10r {width:10px;height:10px;position:absolute;top:0;right:0;filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src="'+INISet.prgHomeURL+'assist/imgs/ie_r10_r.png");}',
	fix_bug_style : '#floatBox4Dialog > form >div {clear:left;}',
	ini : function() {
		this.fix_bug();

		// 基本レイアウト変更
		$('body > div > h2').hide();
		$('#float_titles_dummy').remove();
		$('#ch_title_bar').css({position:'relative'}).find('>div>div:visible').each(function(){$(this).html(ER_sub.STR.toHan($(this).html()))});
		$('#float_titles').css({width:'auto',height:'auto'});
		$('#tvtimes').css({position:'fixed',left:0});

		nowBar.INI = function() {};
		ER_sub.NB.ini();

		// エレメントスタイルリセット
		$('#tvtimes2').attr('style','position:fixed;right:0;');

		// 放送休止の属性変更、番組長再設定
		var change_None = function($tg) {
			if ( $tg.find('>div>.prg_title').html() == '放送休止' ) {
				$tg.addClass('prg_none');
			}
			var d = $tg.find('>div>.prg_duration').text();
			if (d){
				$tg.attr('data-dur',d);
			}
		}
		ER_sub.rePRG.add(change_None);
		// 放送終了判定
		if (ASSIST_INI.index_passed_prg ) {
			var ST,ED;
			ST = new Date(INISet.tableStartTime),
			ED = new Date(INISet.tableEndTime);
			if ( ER_sub.D < ST ) {
			} else if ( ER_sub.D > ED ) {
				ER_sub.rePRG.add(function($tg){$tg.addClass('prg_pass')});
			} else {
				ER_sub.rePRG.add(ER_sub.TBLpass);
			}
		}
		ER_sub.rePRG.add(function($tg){
			if ($tg.hasClass('prg_pass')) { return; }
			var txt, Id = $tg.find('.prg_id').text();
			if ($tg.hasClass('prg_rec')) {
				txt = 'キャンセル';
			} else {
				txt = '予約';
			}
			$tg.append('<div class="rectoggle"><a href="javascript:ER_sub.TBLprg.toggleRec(\''+Id+'\')" data-Id="'+Id+'">'+txt+'</a></div>');
		});

		// 番組再設定
		ER_sub.rePRG.ini();

		$('body').append('<style type="text/css"><!-- '+this.style+this.fix_bug_style+' --></style>');

		ER_sub.SB.ini();
		if (ASSIST_INI.index_prg_info ) {
			//番組情報表示ファンクション削除
			prg_hover  = function () {};
			ER_sub.TBLprg.ini();
		}
		ER_sub.TBL_IE();

		// 上部メニュー
		if (ASSIST_INI.index_new_top ) {
			ER_sub.TBLnew();
		}

		// scroll イベントファンクション削除
		tvtimes_scroll  = function () {};
		$(window).unbind().scroll(ER_sub.INDEX._scroll).resize(ER_sub.INDEX._scroll);
		// ページ読み込み時イベントが発生しない場合用
		ER_sub.INDEX._scroll();
	},
	_scroll : function() {
		ER_sub.RS.reWidth();
		var h = $('#float_titles').height(), t = h-$(document).scrollTop()
		$('#tvtable').css('margin-top',h);
		$('#tvtimes').css('top',t);
		$('#tvtimes2').css('top',t);
		$('#ch_title_bar').css('margin-left',-$(document).scrollLeft());
	},
	fix_bug : function() {
		// epgデータの無いチャンネル処理
		var wb = false;
		$('#ch_title_bar > .ch_title').each(function() {
			if ($(this).attr('id') == 'ch_title_') {
				$(this).find(':visible').html('<span class="no_epg">no-epg</span>').attr('onclick','');
				wb = true;
			}
		});

		// 「現在」又は時間無視定で番組表を表示している時は
		// 放送波のリンクに時間を入れない
		var ST = new Date(INISet.tableStartTime);
		var NW = new Date();
		var i =( NW - ST ) / 60000;
		if( i < 60 && i > 0 ) {
			$('#jump-broadcast li a').each(function() {
				$(this).attr('href', $(this).attr('href').replace(/[&?]time=[0-9]+/,''));
			});
		};
	}
}

// 現在の時刻を表す赤線
ER_sub.NB = {
	startTime:null,
	endTime:null,
	_timer : null,
	ini : function() {
		if (INISet.tableStartTime && INISet.tableStartTime && INISet.dotMin) {
			$('#tvtable').append('<div id="tableNowBas" style="display:none;width:100%">now</div>');
			this.startTime = new Date(INISet.tableStartTime);
			this.endTime = new Date(INISet.tableEndTime);
			this.update();
			var t = parseInt(ASSIST_INI.index_nowbar_live, 10);
			if (t > 0){
				this._timer = setInterval(function(){
					ER_sub.NB.update();
					if (ASSIST_INI.index_passed_prg){
						ER_sub.TBLpassLv();
					}
				}, t*1000);
			}
		}
	},
	update : function() {
		var now = new Date();
		if(this.startTime){
			if((now >= this.startTime) && (this.endTime >= now)){
				$('#tableNowBas').css({top:(now - this.startTime) / 60000 * INISet.dotMin}).show();
			} else {
				$('#tableNowBas').hide()
			}
		}
	}
};

// 表示中のチャンネル数にあわせて横幅調整
ER_sub.RS = {
	reWidth : function(){
		var w = $('#tv_chs > div:eq(0)').width() * $('#ch_title_bar > .ch_title:visible').length;
		$('#tvtable').width(w + $('#tvtimes').width() * 2);
		$('#ch_title_bar').width(w + $('#tvtimes').width() * 2);
		$('#tv_chs').width(w);
	}
}

// 番組表横時間表示
ER_sub.SB = {
	style : '#tvtimes, #tvtimes2 {z-index:10;text-shadow:0 0 2px #000, 0 0 8px #000;line-height:120%;} #tvtimes .hour,#tvtimes2 .hour {font-family: arial,helvetica;display:inline-block; padding-top:0.5em;} #tvtimes .ap, #tvtimes2 .ap {display:block;font-size:70%;letter-spacing:1px;} #tvtimes .date, #tvtimes2 .date {font-size:110%;background:#666;display:inline-block;padding:0.5em 0.2em;} #tvtimes .dw, #tvtimes2 .dw {display:block;font-size:70%;} #tvtimes .dw_0, #tvtimes2 .dw_0 {background:#D20;} #tvtimes .dw_6, #tvtimes2 .dw_6 {background:#06E;}@media screen and (min-width:1100px) {#tvtimes, #tvtimes2, .tvtimeDM{width:50px;font-size:110%;}#tv_chs{padding:0 50px;}}@media screen and (max-width:800px) {  #tvtimes, #tvtimes2, .tvtimeDM{width:26px;font-size:94%;}#tv_chs{padding:0 26px 0 26px;}}',
	colorStyle : '#tvtimes, #tvtimes2 {background:none;} #tvtimes {border-right:1px solid #111;} #tvtimes2 {border-left:1px solid #111;} .colorTM.tm11, .colorTM.tm12{background-color:rgba(20,60,200,0.7);-ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr=#B2143CC8,endColorstr=#B2143CC8)";filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=#B2143CC8,endColorstr=#B2143CC8);} .colorTM.tm0, .colorTM.tm23 {background-color:rgba(0,0,0,0.75);-ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr=#BF000000,endColorstr=#BF000000)";filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=#BF000000,endColorstr=#BF000000);} .colorTM.tm1, .colorTM.tm2, .colorTM.tm3,.colorTM.tm4, .colorTM.tm19, .colorTM.tm20, .colorTM.tm21, .colorTM.tm22 {background-color:rgba(20,30,30,0.7);-ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr=#B2141E1E,endColorstr=#B2141E1E)";filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=#B2141E1E,endColorstr=#B2141E1E);}.colorTM.tm5, .colorTM.tm6, .colorTM.tm17, .colorTM.tm18{background-color:rgba(50,0,50,0.7);-ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr=#B2320032,endColorstr=#B2320032)";filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=#B2320032,endColorstr=#B2320032);}.colorTM.tm7, .colorTM.tm8, .colorTM.tm9, .colorTM.tm10, .colorTM.tm13, .colorTM.tm14, .colorTM.tm15, .colorTM.tm16{background-color:rgba(0, 40, 120,0.7);-ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr=#B2002878,endColorstr=#B2002878)";filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=#B2002878,endColorstr=#B2002878)}',
	ieDot : '.colorTM{border-top:1px dotted #BBB;margin-top:-1px;} #tvtimes>div>span, #tvtimes2>div>span {-ms-filter:"filter: progid:DXImageTransform.Microsoft.Chroma(Color=#000000) progid:DXImageTransform.Microsoft.Glow(Color=#000000, Strength=2)"; margin:-2px  0 0 -2px;}',
	ini : function() {
		var Fn = function(){
			var $tg = $(this), tm = parseInt($tg.text(),10);
			if ( tm == 0 && $tg.prev().length) {
				$tg.addClass('colorTM tm'+tm).html(ER_sub.SB.date());
			} else {
				$tg.addClass('colorTM tm'+tm).html(ASSIST_INI.time_ap?ER_sub.SB.ap(tm):'<span class="hour">'+tm+'</span>');
			}
		};
		$('#tvtimes div').each(Fn);
		$('#tvtimes2 div').each(Fn);
		$('body').append('<style type="text/css"><!-- '+this.style+(ASSIST_INI.index_timebar_color?this.colorStyle:'')+($.browser.msie?this.ieDot:'')+' --></style>');
	},
	ap : function(t) {
		var ap = 'AM';
		if ( t > 11 ) { ap = 'PM'; }
		return '<span class="hour">'+(t%12)+'</span><span class="ap">'+ap+'</span>';
	},
	date : function () {
		var D = new Date(INISet.tableEndTime);
		return '<span class="date dw_'+D.getDay()+'">'+D.getDate()+'<span class="dw">('+MDA.Days.dayStr[D.getDay()]+')</span></span>';
	},
	clickScroll : function() {
		$('#tvtime').click(function(){});
	}

};

// 番組表上部
ER_sub.TBLnew = function() {
	var  $tg = $('#float_follows');
	// epgrecオリジナルのメニューを隠す
	$tg.find('>div').hide();
	$tg.find('>br').hide();
	// 新しいメニュー作成
	ER_sub.topMenu('#float_follows');
//	ER_sub.TBLDate();
	ER_sub.TM.ini();
	ER_sub.BRDC();
	ER_sub.CTGbd.ini();
	ER_sub.CHtoggle.ini();
};

// 放送波選択
ER_sub.BRDC = function(){
	var t = '', $tg, $types, i, iMax, tpc, a, style = '<style type="text/css"><!-- #brdc_type.set2 a{display:inline-block;position:relative;letter-spacing:2px;min-width:3em;text-align:center;background:#222;padding:0.4em 0.6em;border:2px solid #555;border-right-width:0;}#brdc_type a:first-child {border-top-left-radius:0.6em; border-bottom-left-radius:0.6em;} #brdc_type a.btm {border-top-right-radius:0.6em;border-bottom-right-radius:0.6em;border-right-width:2px;} #brdc_type a.selected{color:#FFF;background-color:#26F;} #brdc_type:hover .selected{background-color:#049;} #brdc_type a.selected:hover:before{border-left-color:#48F;}#brdc_type a.selected:hover:before{border-left-color:#48F;} --></style>',
	$types = $('#jump-broadcast li');
	$('body').append(style);
	for ( i = 0, iMax = $types.length; i < iMax; i++) {
		$tg = $($types[i]);
		a = $tg.find('>a');
		//tpc = a.attr('href').match(/[&?]type=([A-Z]+)/)[1];
		tpc = (a.text() == 'BS' || a.text() == 'CS') ? a.text() : 'GR';
		t += '<a href="'+a.attr('href') + '" class="'+tpc+($tg.hasClass('selected')?' selected':'')+((i==iMax-1)?' btm':'')+'" data-md="y">'+(tpc=='GR'?'地デジ':a.html())+'</a>';
	}
	$('#float_follows').append('<div id="brdc_type" class="set2">'+t+'</div>');
	$('#brdc_type a').each(function(){
		var $tg=$(this);
		$tg.click(function(){MDA.SCR.oCk($tg.attr('data-md'));});
	});
};

// 強調表示
ER_sub.CTGbd = {
	style : '<style type="text/css"><!-- #tvtable div.ctg-hide, #category_select a.ctg-hide {opacity:0.6;}#ctg_bld_list{position:absolute;z-index:200;background:#444;font-size:110%;border:4px solid #222;border-radius:10px;}#ctg_bld_list>a{display:block;padding:0.5em 1em !important;}#ctg_bld_list>a:first-child{border-radius:0.6em 0.6em 0 0;}#ctg_bld_list>a.last{border-radius:0 0 0.6em 0.6em} --></style>',
	ini : function(){
		$('body').append(this.style);
		var $ctgs, $tg, i, iMax, t = '';
		$ctgs = $('#category_select li');
		for (i = 0, iMax = $ctgs.length; i < iMax; i++) {
			$tg = $($ctgs[i]).find('>a');
			t += '<a href="'+$tg.attr('href')+'" class="'+$tg.attr('class')+(i==iMax-1?' last':'')+'">'+$tg.text()+'</a>';
		}
		$('#category_select').attr('id','category_select_org');
		$('#float_follows').append('<div id="category_select" class="set2"><a href="javascript:ER_sub.CTGbd.toggle()" class="ezBtn uTri"><span class="WD90N">Ctg.</span><span class="WD90">強調表示</span></a><div id="ctg_bld_list" style="display:none;">'+t+'</div></div>');
	},
	toggle : function() {
		$('#category_select .ezBtn').toggleClass('selected');
		$('#ctg_bld_list').toggle();
	}
};

ER_sub.CHtoggle = {
	ini : function() {
		$('#float_follows').append('<div id="ch_toggle" class="set2"><a href="javascript:ER_sub.CHtoggle.toggle()" class="ezBtn"><span class="WD90N">Ch.</span><span class="WD90">チャンネル表示</span></a></div>');
	},
	toggle : function() {
		$('#ch_title_bar div.ch_title_skip').toggle();
		$('#tv_chs div.ch_set_skip').toggle();
		// 横幅調整
		ER_sub.RS.reWidth();
	}
};

// 表示時間帯選択再設定
ER_sub.TM = {
	OVERLAP : 1, LEN : 6, NW : null, ST : null, ED : null,
	LINK : '',
	ini : function() {
		this.NW = new Date();
		this.ST = new Date(INISet.tableStartTime);
		this.ED = new Date(INISet.tableEndTime);
		this.LEN = (this.ED - this.ST)/3600000;
		this.LINK = $('#jump-broadcast .selected a').attr('href');
		this.T_make();
	},
	// @param t ( hour || Date || 'now')
	makeLink : function(t){
		var D;
		if ( t == 'now' ) {
			return this.LINK;
		} else if ( typeof(t) == 'object' ) {
			D = t;
		} else {
			D= new Date(this.ST);
			D.setHours(D.getHours() +parseInt(t,10));
		}
		var timeStr = D.getFullYear() + $.N2S(D.getMonth()+1, 2) + $.N2S(D.getDate(), 2) + $.N2S(D.getHours(), 2);
		return INISet.prgProgramURL + '?' + INISet.prgTimeLink[timeStr];
	},
	T_make : function(){
		var i, dt = ht = '', date, day, S, D = new Date(this.ST), that = this,
		style = '<style type="text/css"><!-- #float_titles #jmpTimes {font-size:100%;margin-left:1em;}#float_titles .set2 a {line-height:1em;padding:0.15em 0;color:#AAA} #jmpTimes a{display:inline-block;width:1.7em; text-align:center;overflow:hidden;padding:0;} #jmpTimes .pages{margin:0.5em;}#jmpTimes .pages a{padding:0.5em 0;} #jmpTimes .datepage a {vertical-align:bottom;padding:1.1em 0;} #jmpTimes .dates a{border-radius:0.6em;} #jmpTimes .dates .dw {display:block;line-height:1em;padding:0 0 2px;margin:0 2px 2px 2px;font-size:85%;border-bottom:2px solid #333;} #jmpTimes .dates .dw_0 {border-color:#D20;} #jmpTimes .dates .dw_6 {border-color:#08F;} #jmpTimes .dates .month {line-height:1em;font-size:50%; }#jmpTimes .dates a{width:4em;}#jmpTimes .dates a.now{font-weight:bold;vertical-align:bottom;padding:1.2em 0;margin-right:0.2em;}#jmpTimes .dates a.now span{font-size:120%;}#jmpTimes .dates a .num{font-size:180%;font-weight:bold;color:#FFF;line-height:1.2em;} #jmpTimes .times a{font-size:90%;background:#262626;} #jmpTimes .times a.now {border-left:2px solid #D40;}#jmpTimes:hover a.selected{background:#049;} #float_titles .set2 .hover a,#float_titles .set2 a.hover, #float_titles .set2 a:hover{ color:#FFF;background:#27D;}   #jmpTimes a.hour {padding:0.2em 0 0.3em;border-top:2px solid #222;border-bottom:2px solid #444;} #jmpTimes .hour.selected, #jmpTimes a.selected {color:#FFF;background:#16C;border-bottom-color:#27D;border-top-color:#05B;}.pages a.prev span,.pages a.next span{display:inline-block;width:0;height:0;overflow:hidden;}.pages a.prev:after,.pages a.next:after{content:\'\';display:inline-block;position:relative;width:0px;height:0px;border:5px solid transparent;}.pages a.prev:after{margin-left:-4px;border-right-color:#BBB;}.pages a.next:after{margin-left:4px;border-left-color:#BBB;}.pages a:hover.prev:after{border-right-color:#FFF}.pages a:hover.next:after{border-left-color:#FFF} --></style>';
		S = new Date(this.NW);
		S.setHours(this.ST.getHours());
		for (i = 0; i < 8; i++) {
			date = S.getDate(); day = S.getDay();
			dt += '<a href="'+this.makeLink(new Date(S))+'" data-h="'+this.ST.getHours()+'" data-d="' + date + '" style="margin:0;" data-md="xy" class="date"><span class="dw dw_'+day+'"> '+MDA.Days.dayStr[day]+' </span>'+(date==1&&i!=0?'<span class="month">'+(S.getMonth()+1)+'月</span>':'')+'<span class="num">'+date+'</span></a>';
			S.setDate(date + 1);
		}
		dt = '<span class="dates"><a href="'+this.makeLink('now')+'" data-h="'+this.NW.getHours()+'" data-d="'+this.NW.getDate()+'" data-md="x" class="now"><span>現在</span></a>'+dt+'</span>';
		ht = '', D = new Date(this.ST)
		for (i = 0; i < 24; i++) {
			ht += '<a href="'+this.makeLink(new Date(D.setHours(i)))+'" class="hour" data-h="'+i+'" data-md="x" style="margin:0;">'+((i%3)?'&middot;':i)+'</a>';
		}
		$('#jump-day').hide();
		$('#jump-time').hide();

		$('#float_follows').append('<div class="set2" id="jmpTimes">' + dt + this.makeA() + '<br/>' + '<span class="times">'+ht+'</span>'+ this.makeA('h') + style + '</div>');
		this.H_hover(this.ST.getDate(), 'selected');
		this.T_hover(this.ST.getHours(), 'selected');
		$('#jmpTimes .times a[data-h='+this.NW.getHours()+']').addClass('now');
		$('#jmpTimes a').hover(
			function() {
				that.H_hover($(this).attr('data-d') || that.ST.getDate());
				that.T_hover($(this).attr('data-h'));
			},
			function() {
				$('#jmpTimes a.hover').removeClass('hover');
			}
		).each(function(){
			var $tg = $(this);
			$tg.click(function(){MDA.SCR.oCk($tg.attr('data-md')||'x')});
		});
	},
	makeA : function(op) {
		var md, h0, h1, d0, d1, D, s, c = '';
		if ( op == 'h' ) {
			s = this.LEN - this.OVERLAP;
			D = new Date((new Date(this.ST)).setHours(this.ST.getHours()-s));
			d0 = D.getDate();
			h0 = D.getHours();
			D = new Date((new Date(this.ST)).setHours(this.ST.getHours()+s));
			d1 = D.getDate();
			h1 = D.getHours();
			md = 'x';
		} else {
			s = 24; c = ' datepage';
			D = new Date(this.ST);
			d0 = (new Date(D.setDate(D.getDate() - 1))).getDate();
			d1 = (new Date(D.setDate(D.getDate() + 2))).getDate();
			this.ST.getDate();
			h0 = h1 =this.ST.getHours();
			md = 'xy';
		}
		return '<span class="pages'+c+'" style="margin-right:0.5em;"><a class="prev" title="prev" href="'+this.makeLink(-s)+'" data-h="'+h0+'" data-d="'+d0+'" data-md="'+md+'"><span>&lt;</span></a><a class="next" title="next" href="'+this.makeLink(s)+'" data-h="'+h1+'" data-d="'+d1+'" data-md="'+md+'"><span>&gt;</span></a></span>'
	},
	H_hover : function(st, op) {
		op = op || 'hover';
		$('#jmpTimes .dates a.date').removeClass(op);
		$('#jmpTimes .dates a.date[data-d='+st+']').addClass(op);
	},
	T_hover : function(st, op) {
		op = op || 'hover';
		var i = 0, iMax = this.LEN>23?1:this.LEN, $tg = $('#jmpTimes .times a:eq('+st+')');
		$('#jmpTimes .times a').removeClass(op);
		for( i; i < iMax; i++) {
			$tg.addClass(op);
			if ( $tg.nextAll().length ) {
				$tg = $tg.next();
			} else {
				$tg = $('#jmpTimes .times a:first');
			}
		}
	}
}

// 現在時刻表示
ER_sub.TBLDate = function() {
	var D = new Date(),$tg = $('#float_follows'),
	day = D.getDay();

	$tg.append('<div class="ndate set2" style="font-size:140%;font-family: arial,helvetica; font-weight:bold;color:#FFF;text-align:center;"><span class="month">'+(D.getMonth()+1)+'</span> / <span class="date" style="font-size:140%;">'+D.getDate()+'</span><div class="dw_'+day+'" style="font-size:60%;">'+MDA.Days.dayStr[day]+'曜日</div><div>'+D.getHours()+':'+$.N2S(D.getMinutes(),2)+'</div></div>');
}

// 放送終了をライブ判定
ER_sub.TBLpassLv = function() {
	var i, iMax, $tgs, $tg;
	ER_sub.D = new Date();
	$('#tv_chs>div').each(function(){
		$tgs = $(this).find('>div>div');
		for (i = 0, iMax = $tgs.length; i < iMax; i++) {
			$tg = $($tgs.eq(i));
			if (!$tg.hasClass('prg_pass')){
				if (ER_sub.prgWill($tg) == 0) {
					$tg.addClass('prg_pass');
					$tg.find('.rectoggle').remove();
				} else {
					i = iMax;
				}
			}
		}
	});
}

// 放送終了番組
ER_sub.TBLpass = function($tg) {
	if (ER_sub.prgWill($tg)==0){$tg.addClass('prg_pass')};
};

// 判定用現在のDate
ER_sub.D = new Date();
// 放送終了番組判定 & attr 再設定
// @return 0:放送終了,1:放送中,2:放送予定
ER_sub.prgWill = function($tg) {
	var T, P = 0;
	if ($tg.attr('data-start')){
		T = $tg.attr('data-start');
	} else {
		T = $tg.find('>div>.prg_start').text();
		$tg.attr('data-start', T);
	}
	T = new Date(T);
	if (T > this.D){
		P = 2;	// 開始時刻が今より前
	}
	if ($tg.attr('data-end')) {
		T = new Date($tg.attr('data-end'));
	} else {
		T = new Date(T.setMinutes(T.getMinutes() + parseInt($tg.find('>div>.prg_duration').text(),10)/60));
		$tg.attr('data-end', ER_sub.Dt2S(T));
	}
	if (T > this.D && P == 0) {
		P = 1;	// 放送中
	}
	return P;
}

// 番組情報の表示方法を無理やり変更
ER_sub.TBLprg = {
	infHtml : '<div id="prg_info_B" style="display:none;"><div class="dummy"><div id ="prg_info_B_title" class="title">&nbsp;</div><div id="prg_info_B_cont" class="content">&nbsp;</div><div id="prg_info_B_btns" class="btns"><a href="javascript:ER_sub.TBLprg.unselect()" class="btn">cancel</a></div></div></div>',
	infStyle : '#prg_info_B {position:fixed;z-index:500;font-size:110%;color:#FFF;} #prg_info_B>div {background:#222;background-color:rgba(0,0,0,0.85);border-radius:1em;box-shadow:1px 1px 10px #000;} #prg_info_B>div>div {display:block;clear:left;}#prg_info_B .rightBox,#prg_info_B .leftBox{display:inline-block;vertical-align:middle;}#prg_info_B .rightBox{width:400px;padding:0.8em 1em;} #prg_info_B>div.prg_pass {background-color:rgba(80,80,80,0.85);} #prg_info_B .title {font-size:130%;font-weight:bold;} #prg_info_B .desc {color:#BBB;} #prg_info_B .leftBox {color:#000;background:#EEE;font-weight:bold;width:280px;text-align:center;padding:0.8em 0.2em;} #prg_info_B_title {padding:0.4em 1em 0.2em;font-size:140%;} #prg_info_B_cont{border:4px solid #EEE;} #prg_info_B_cont, #prg_info_B_btns {}#prg_info_B_btns {background:#DDD;text-align:center;padding:0.6em 1em 0.8em;}#prg_info_B_btns a.btn{display:inline-block;border-radius:0.6em;padding:0.4em 1em;margin:0.3em;color:#222;background:#DDD;border:2px solid #888;box-shadow:inset 1px 1px 8px #FFF,inset 1px 1px 3px #FFF;}#prg_info_B_btns a.btn.btn_red{color:#EEE;background:#D20;box-shadow:inset 1px 1px 8px #E63,inset 1px 1px 3px #E63;}#prg_info_B_btns a.btn.btn_red:hover{background:#E30;color:#FFF;}#prg_info_B_btns a.btn.btn_yellow{color:#EEE;background:#B70;box-shadow:inset 1px 1px 8px #D91,inset 1px 1px 3px #D91;}#prg_info_B_btns a.btn.btn_yellow:hover{background:#C80;color:#FFF;}#prg_info_B_btns a.btn.default{border-color:#222;}#prg_info_B_btns a.btn.close,#prg_info_B_btns a.btn.crsv{float:right;} #prg_info_B_btns a.btn:hover {background:#E4E4E4;color:#000;border-color:#28F;color:000;box-shadow:inset 1px 1px 8px #FFF,0 0 6px 1px #FFF;}#prg_info_B .rsv {background:#F42;color:#FFF;} #prg_info_B .rec {background:#28F;color:#FFF;} #prg_info_B_cont .rsv, #prg_info_B_cont .rec ,#prg_info_B_cont .ctg_BOX {display:inline-block;padding:0.3em 1em;font-size:90%;border:2px solid #FFF;border-radius:0.7em;} #prg_info_B .date_box {display:inline-block;font-size:140%;line-height:120%;font-family:arial,helvetica;} #prg_info_B .month{font-size:125%;} #prg_info_B .date {font-size:160%;}#prg_info_B .time {font-size:110%;}#prg_info_B .ap{font-size:75%;color:#888;padding-right:0.2em;} #prg_info_B .day {display:inline-block;background:#999;color:#FFF;padding:0.3em 0.3em;font-size:75%;line-height:100%;border-radius:0.4em;margin:0 0.3em;vertical-align:25%;}#prg_info_B .day.dw_0 {background:#F64;}#prg_info_B .day.dw_6 {background:#4BF;}#prg_info_B .labelLeft{display:inline-block;text-align:right;vertical-align:middle;width:6em;}#prg_info_B textarea{width:30em;vertical-align:middle;}',
	infStyle_t : '#prg_info_B.top {right:10px;top:0px;} #prg_info_B_cont{border-radius:0 0 1em 1em;overflow:hidden;} #prg_info_B.top #prg_info_B_cont{border-top-width:0;} #prg_info_B.lock #prg_info_B_cont {border-top-width:4px;border-radius:1em 1em 0 0;} #prg_info_B.top .rightBox{}#prg_info_B.top #prg_info_B_btns {display:none;}#prg_info_B.lock #prg_info_B_btns {display:block;border-radius:0 0 1em 1em;}#prg_info_B.lock >div{border-radius:1em;box-shadow:2px 2px 20px 4px hsla(0,0%,0%,0.8);}',
	ieStyle : '#prg_info_B {border:1px solid #000;}',
	btns : {close:'<a href="javascript:ER_sub.TBLprg.unselect()" class="btn close default">閉じる</a>', rsv:'<a href="javascript:ER_sub.TBLprg.doRec({id})" class="btn rsv btn_red">簡易予約</a>', crsv:'<a href="javascript:ER_sub.TBLprg.customRec({id})" class="btn crsv btn_yellow">予約カスタマイズ</a>', cancel:'<a href="javascript:ER_sub.TBLprg.doCancel({id})" class="btn cancel btn_yellow">録画予約キャンセル</a>', play:'<a href="javascript:ER_sub.TBLprg.doPlay({id})" class="btn play">再生</a>', del:'<a href="javascript:ER_sub.TBLprg.doDel({id})" class="btn del btn_yellow">録画情報削除</a>', docstm:'<a href="javascript:ER_sub.TBLprg.doCustomRec({id})" class="btn del btn_red">予約する</a>'},
	$INF : null, $ID : null, $IT : null, $IC : null, $IB : null,
	_selected : false, _P : null, _posType : 0, _posClass : new Array('top','slide'),
	ini : function() {
		setTimeout( this.__bindEV, 100);
		$('body').append('<style type="text/css"><!-- ' + this.infStyle + this.infStyle_t + ($.browser.msie?this.ieStyle:'')+' --></style>' + this.infHtml);
		this.$INF = $('#prg_info_B');
		this.$ID = $('#prg_info_B > div');
		this.$IT = $('#prg_info_B_title').hide();
		this.$IC = $('#prg_info_B_cont');
		this.$IB = $('#prg_info_B_btns');
		this.$INF.addClass(this._posClass[this._posType]);
	},
	// this is EV target
	__bindEV : function() {
		var that = ER_sub.TBLprg;
		$('#prg_info').hide();
		$('#tv_chs .prg').unbind().hover(function(ev){
			if ( that._selected ) {return;}
			$('#tv_chs .prg.prg_hover').removeClass('prg_hover');
			$tg = $(this);
			if($tg.hasClass('prg_none')) {return;}
			$tg.addClass('prg_hover');
			that.showInfo($tg,ev);
		},function(){
			if ( that._selected ) {return;}
			$tg = $(this);
			$tg.removeClass('prg_hover');
			that.hideInfo();

		}).click(function(ev){
			$tg = $(this);
			if($tg.hasClass('prg_none')) {return;}
			if (!$('#tv_chs .prg_hover').length) {return;}
			if ( that._selected ) {
				that._selected.removeClass('prg_hover');
				$tg.addClass('prg_hover');
				that._makeInfo($tg);
			}
			that.select($tg);
		}).find('.rectoggle a').click(function(ev){
			that.toggleRec($(this).attr('data-Id'));
			return false;
		});
	},
	select : function($tg, effect) {
		ER_sub.D = new Date();
		var B = [], RSV = $tg.hasClass('prg_rec'), t, that = this;
		this.$INF.addClass('lock');
		switch(ER_sub.prgWill($tg)) {
		case 0 :
			if (RSV) {B=[];};
			break;
		case 1 : case 2:
			B = RSV?['cancel']:['rsv','crsv'];
		}
		if (!effect){ ER_sub.SCRL.toHTML($tg);}
		this.$IB.html(this.makeBtns(B,$tg.find('.prg_id').text()));
		this.setConHeight();
		t = $tg.offset().top - $(document).scrollTop() - this.$INF.height() - 10;
		if ( t < 0 ) {t = t + $tg.height()+this.$INF.height()+20;}
		this.$INF.animate({top:t},50);
		this._selected = $tg;
		$(document).bind('keyup.prg_select',function(ev){if(ev.keyCode == 27 ){that.unselect();}});
		if (effect){ ER_sub.SCRL.toHTML($tg,true);}
	},
	unselect : function() {
		this._selected.removeClass('prg_hover');
		this._selected = false;
		this.$INF.removeClass('lock');
		this.hideInfo();
		$(document).unbind('keyup.prg_select');
	},
	showInfo : function($tg,ev) {
		this._makeInfo($tg);

		switch (this._posType) {
		case 1 :
			this.$INF.stop().show().fadeTo('fast',1);
			if (this.$ID.find('.rightBox').height() < this.$INF.find('.leftBox').height()){
				this.$ID.height(this.$INF.find('.leftBox').height()-10);
			} else {
				this.$ID.height('inherit');
			}
			ftH = $('#float_titles').height();
			if (ev.clientY - ftH > ( $(window).height() - ftH )  * (this._P?2:1) / 3 ) {
				this.$INF.css('top',ftH+15);
				this._P = 0;
			} else {
				this.$INF.css('top',$(window).height() - this.$ID.outerHeight() - 15);
				this._P = 1;
			}
			break;
		default : case 0:
			// for ie
			if ($.browser.msie) { this.$INF.css('left',$(window).width()-this.$INF.width()-10);}
			this.$INF.stop().css('top',0).show().fadeTo(100,1);
			this.setConHeight(1);
		}
		// for ie
		if ($.browser.msie) {
			this.$IC.width('auto').width(parseInt(this.$IC.width(),10)+1);
			this.$INF.width('auto').width(parseInt(this.$INF.width(),10)+2);
		}
	},
	hideInfo : function() {
		var t;
		switch (this._posType) {
		case 0 :
			t = parseInt(this.$INF.css('top'),10);
			if (t > 0 ){
				t = t - 40;
			} else {
				t = -40;
			}
			this.$INF.stop().animate({opacity:0,top:t},150,function(){$(this).hide();});
			break;
		case 1 : default :
			this.$INF.stop().fadeTo('slow',0,function(){$(this).hide();});
			break;
		}
	},
	_makeInfo : function($tg) {
		var ftH, ap, h, $ch = $('#ch_title_bar .ch_title:eq('+$tg.parents('.ch_set').prevAll().length+')'),
		RSV = '', Will = ER_sub.prgWill($tg),
		ST = new Date($tg.find('.prg_start').text()), day = ST.getDay(),
		h = ST.getHours();
		if (ASSIST_INI.time_ap){
			if (h>11) { ap = 'PM'; } else { ap = 'AM'; }
			h = '<span class="ap ap_'+ap.toLowerCase()+'">'+ap+'</span>'+(h % 12);
		};

		D = '<span class="date_box" style="display:block;"><span class="month">' + (ST.getMonth()+1) + '<span style="padding:0 0.2em;">/</span></span><span class="date">' + ST.getDate() + '</span>' + '<span class="day dw_' + day + '">' + MDA.Days.dayStr[day] + '</span><span class="time">' + h + ':' + $.N2S(ST.getMinutes(),2) + '<span style="color:#888;font-size:85%;padding-left:0.4em;">+'+ER_sub.DATE.durToHm($tg.attr('data-dur'))+'</span></span></span>',
		ctg = $tg.attr('class').match(/ctg_([a-z]+)/)[1],
		CT = '<span class="ctg_BOX ctg_' + ctg +'">' + ER_sub.CTGS[ctg] + '</span>';
		if ($tg.hasClass('prg_rec')) {
			switch (Will) {
			case 0 :
				RSV = '<div class="rec">録画済み</div>';break;
			case 1 :
				RSV = '<div class="rsv">録画中</div>';break;
			case 2 : default :
				RSV = '<div class="rsv">録画予約</div>';break;
			}
		}
		this.$IC.html('<div class="leftBox">'+'' + D +'<span class="ch">' +RSV+ ER_sub.STR.toHan($ch.find('.ch_name').text())+'</span>'+CT+ '</div>'+ '<div class="rightBox"><span style="display:inline-block;vertical-align:middle;"><span class="title">'+$tg.find('.prg_title').text() + '</span> <span class="desc">'+$tg.find('.prg_desc').text() +'</span></span></div>');
	},
	setConHeight : function(op) {
		var Rh, Lh, $R = this.$IC.find('.rightBox'),$L =this.$IC.find('.leftBox'), m = parseInt($R.css('padding-top'),10)+parseInt($R.css('padding-bottom'),10);
		Rh = $R.height('auto').height();
		Lh = $L.height('auto').height();
		if (Rh < Lh) {
			Rh = 'auto';
			h = Lh;
		} else {
			h = Lh = Rh;
		}
		if (op) {
			h = $('#float_titles').height() - $('#ch_title_bar >div.ch_title:eq(0)').height() - 8;
			if (Rh != 'auto') {
				Rh = Lh = h - m;
			} else if ( Lh + m < h){
				Lh = h - m;
			}
		} else {
			h = 'auto';
		}
		// ieの場合ボーダー分調整
		this.$IC.height($.browser.msie && op?h-4:h);
		$R.height(Rh);
		$L.height(Lh);
		this.$ID.height(h);
	},
	_intoWindow : function() {
		var t = this.$INF.offset().top - $(document).scrollTop() + this.$INF.height() + 10;
		if (t > $(window).height()) {
			this.$INF.css('top', $(window).height() - this.$INF.height() - 10);
		}
		return;
	},
	makeBtns : function(B,Id) {
		var  btns = this.btns, t = btns['close'];
		if (!$.isArray(B)) {B = [B];}
		$.each(B, function(){t += btns[this].replace(/{id}/,Id);});
		return t+'<hr style="clear:right;margin:0;border:none;"/>';
	},
	toggleRec : function(Id) {
		var $tg = $('#prgID_'+Id);
		this._selected = $tg;
		if ($tg.hasClass('prg_rec')){
			this.doCancel(Id);
		} else {
			this.doRec(Id);
		}
		return false;
	},
	doRec : function(Id) {
		var that = this;
		$.post(INISet.prgRecordURL, { program_id: Id } ,function(data){that._rsv(Id, data, true);});
		},
	doCancel : function(Id) {
		var that = this;
		$.post(INISet.prgCancelURL, { program_id: Id } ,function(data){that._rsv(Id, data, false);});
	},
	customRec : function(Id) {
		var that = this;
		$.post(INISet.prgReservFormURL, { program_id: Id }, function(data) {
			if(data.match(/^error/i)){
				alert(data);
			}
			else {
				var str = '<div  style="background:#EEE;color:#111;font-size:110%;padding:1em 2em;margin:auto;">' + data + '</div>';
				that.$IB.html(that.makeBtns(['docstm'],Id));
				that.$IC.html(str);
			}
			that._intoWindow();
		});
	},
	doCustomRec : function(Id) {
		var that = this;
		$.post(INISet.prgRecordPlusURL, {
			syear: $('#id_syear').val(),
			smonth: $('#id_smonth').val(),
			sday: $('#id_sday').val(),
			shour: $('#id_shour').val(),
			smin: $('#id_smin').val(),
			eyear: $('#id_eyear').val(),
			emonth: $('#id_emonth').val(),
			eday:$('#id_eday').val(),
			ehour: $('#id_ehour').val(),
			emin: $('#id_emin').val(),
			channel_id: $('#id_channel_id').val(),
			record_mode:$('#id_record_mode').val(),
			title: $('#id_title').val(),
			description: $('#id_description').val(),
			category_id: $('#id_category_id ').val(),
			program_id: $('#id_program_id').attr('checked')?$('#id_program_id').val():0 },
			function(data){that._rsv(Id, data, true);}
		);
	},
	_rsv : function(Id,data,add){
		if(data.match(/error/i)){
			if(data.match(/^error/i)){
				data = 'epgrecエラー：'+data;
			} else {
				data = 'phpエラー：'+data;
			}
			alert(data);
		}else{
			var $tg = $('#prgID_' + Id);
			if (add) {
				$tg.addClass('prg_rec').find('.rectoggle>a').html('キャンセル');
			} else {
				$tg.removeClass('prg_rec').find('.rectoggle>a').html('予約');
			}
		}
		this.unselect();
	},
	doDel : function($tg) {
		alert('できるといいね');
	},
	doPlay : function($tg) {
		alert('できるといいね');
	}
};

// IE対策、角丸
ER_sub.TBL_IE = function() {
	if($.browser.msie) {
		$('#tv_chs div.prg').prepend('<div class="IErnd10l">&nbsp;</div><div class="IErnd10r">&nbsp;</div>');
	}
};

// スクロール可能テーブル作成
ER_sub.TBL_SCROLLABLE = {
	_id : null,
	ini : function(id) {
		this._id = id;
		$('#'+this._id).addClass('PRGS_LIST_TABLE').wrap('<div id="twrap0" style="position:relative;"><div id="twrap1" style="overflow:auto;background:#181818;"></div></div>');
		$('#'+this._id+'>thead').css({position:'absolute',top:0,left:0});
		$('#'+this._id+' tr:last-child').addClass('last');
		this.reSize();
	},
	reSize : function() {
		var h = i = w = 0, $th = $('#'+this._id+'>thead'),
		$td = $('#'+this._id+'>tbody>tr:eq(0)>td:eq(0)');
		if (!$td.length) return false;
		$('#twrap0').css({paddingTop:$th.height()});
		h = $(window).height()-$td.offset().top+parseInt($td.css('padding-top'),10)-12;
		if (parseInt($('#twrap1').height(),10) >  h ) {
			$('#twrap1').css({height:h});
		}
		$('#'+this._id+' th').each(function(){
			if ($(this).attr('colspan')){
				var j = $(this).attr('colspan');
				w = 0;
				while(j){
					w += parseInt($td.innerWidth(),10);
					k=parseInt($td.attr('colspan'),10);
					if (k>1){
						j=j-k;
					} else {
						j--;
					}
					$td = $td.next();
				}
			} else {
				w = $td.innerWidth();
				$td = $td.next();
			}
			$(this).width(w-$(this).paddingW());
		});
	}
};

// ================== 番組検索
ER_sub.PROGRAMTBL = {
	style : '',
	ini : function() {
		var tmp, keyword = null, that = this, $tgs = $('#reservation_table tr:not(:eq(0))');
		$('body').append('<style type="text/css"><!-- ' + ER_sub.STYLES.colorD + ER_sub.STYLES.table('reservation_table') + this.style+'</style>');

		// タイトル、リンク等を再構成
		$('body>div:eq(0)>p').hide();
		tmp = $('body>div:eq(1)>form');
		tmp.parent().removeClass('container');
		tmp.parent().addClass('fmBox').wrap('<div class="container" />').wrap('<div id="fmWrap"/>');
		ER_sub.topMenu();

		this.reMake($tgs);
		ER_sub.TBL_SCROLLABLE.ini('reservation_table');
		$(window).resize(ER_sub.TBL_SCROLLABLE.reSize);

		ER_sub.FRM.selectAssist();
		ER_sub.FRM.chTypeAssist();
	},
	reMake : function($tgs) {
		var $tg, $tds, tg0, d, h, tm, dur, img, Lap, href = DSP = Dstr = LD = chn = ctg = '', odd = true, DFn = ER_sub.DATE, Id, mode, keyword, dur;
		ToD = ER_sub.DATE.DateER(ER_sub.D),
		YsD = ER_sub.DATE.DateER(new Date((new Date(ER_sub.D)).setDate(ER_sub.D.getDate()-1))),
		Tmr = ER_sub.DATE.DateER(new Date((new Date(ER_sub.D)).setDate(ER_sub.D.getDate()+1)));

		$('#reservation_table tr:eq(0)').remove();
		$('#reservation_table').prepend(ER_sub.tableMultiHead);
		$('#reservation_table thead tr th:eq(0)').remove();
		$('#reservation_table thead tr').append('<th colspan="2">&nbsp;</th>');
		$tgs.each(function(){
			$tg = $(this);
			$tg.addClass('rsv unSel');
			$tds = $tg.children();
			// Id
			Id = $tg.attr('id').match(/resid_([0-9]*)/)[1];
			$tg.attr('data-resid',Id);
			// 番組長
			dur = parseInt((new Date($tds.eq(3).html().replace(/-/g,'/')) - new Date($tds.eq(2).html().replace(/-/g,'/')))/1000,10);
			$tg.attr('data-dur',dur);
			// 日付
			$tg0 = $tds.eq(0).addClass('date');
			d = $tds.eq(2).html().replace(/-/g,'/');
			$tg.attr('data-date', d);
			tm = ER_sub.DATE.AP(d.substr(11,5));
			if ((d.slice(0,10)!=DSP) && DSP) {
				Lap = false;
				if (d.slice(0,7)!=DSP.slice(0,7)) {
					if (d.slice(0,4)!=DSP.slice(0,4)){
						$tg.before('<tr class="ySplit split" data-split="6"><td colspan="2" class="year">'+DFn.Year(d)+'</td><td colspan="5">&nbsp;</td></tr>');
					} else {
						$tg.before('<tr class="mSplit split" data-split="4"><td colspan="7"></td></tr>');
					}
				} else {
					$tg.before('<tr class="dSplit split" data-split="2"><td colspan="7"></td></tr>');
				}
			}
			$tg0.after('<td class="time">'+(ASSIST_INI.time_ap?'<span class="WD70"><span class="ap ap_'+tm.ap+((Lap&&tm.ap==Lap)?' vsblN hv':'')+'">'+tm.ap+'</span>'+tm.html+'</span><span class="WD70N">'+tm._24+'</span>':tm._24)+(dur?'<span class="WD70"><span style="color:#555;padding:0 0.4em;">+</span><span class="durTxt">'+ER_sub.DATE.durToHm(dur)+'</span></span>':'')+'</td>');
			Lap = tm.ap;
			DSP = d.slice(0,10);
			Dstr = '';
			switch(DSP) {
			case ToD :
				Dstr = '<span class="dString">今日</span>';break;
			case YsD :
				Dstr = '<span class="dString">昨日</span>';break;
			case Tmr :
				Dstr = '<span class="dString">明日</span>';break;
			default :
				if (DSP.slice(0,7) == LD.slice(0,7)) {
					Dstr = '<span class="month vsblN hv">'+DFn.Month(DSP)+'</span>'+DFn.Date(DSP);
				} else {
					Dstr = '<span class="month">'+DFn.Month(DSP)  + '</span>' + DFn.Date(DSP);
				}
			}
			$tg0.html((LD==DSP?'<span class="vsblN hv" >':'')+Dstr + DFn.Day(DSP,'<span class="WD70">（</span>', '<span class="WD70">）</span>')+(LD==DSP?'</span>':''));
			LD = DSP;
			// チャンネル
			$tds.eq(1).html('<div class="ch_Box">'+ (ER_sub.STR.toHan($tds.eq(1).html()))+'</div>');
			// カテゴリー
			ctg = $tg.attr('class').match(/ctg_([a-z]*)/)[1];
			$tds.eq(2).html('<span class="ctg_Box ctg_'+ctg+'"><span class="WD100">'+ER_sub.CTGS[ctg]+'</span><span class="WD100N">'+ctg.slice(0,3)+'</span></span>');
			// タイトル
			$tds.eq(3).addClass('title').attr('colspan',2).html('<div><span class="title">' + $tds.eq(4).text() + '</span><span class="desc">' + $tds.eq(5).text() + '</span><span class="WD70N" style="color:#EEE;">'+(dur?parseInt((dur/60),10)+'分 ':'')+'</span></div><a href="javascript:ER_sub.PROGRAMTBL._more('+Id+');" class="moreBtn" data-a="'+Id+'">more</a>');
			$tds.eq(4).remove();
			$tds.eq(5).remove();
		});
	},
	_more : function(Id) {
		$tg = $('#reservation_table tr[data-resid='+Id+']');
		$tg.toggleClass('moreOpen').toggleClass('unSel');
	}
}

// ================== キーワード
ER_sub.KEYWORD = {
	style : '',
	ini : function() {
		$('body').append('<style type="text/css"><!-- ' + ER_sub.STYLES.colorD + ER_sub.STYLES.table('reservation_table') + this.style+'</style>');

		// タイトル、リンク等を再構成
		$('body>div:eq(0)>p').hide();
		ER_sub.topMenu();

		ER_sub.TBL_SCROLLABLE.ini('reservation_table');
		$(window).resize(ER_sub.TBL_SCROLLABLE.reSize);
	}
}

// ================== 録画予約一覧
ER_sub.RESERVED = {
	_durTotal : 0,
	ini : function() {
		var tmp, keyword = null, that = this, $tgs = $('#reservation_table tr:not(:eq(0))');
		$('body').append('<style type="text/css"><!-- ' + ER_sub.STYLES.colorD + ER_sub.STYLES.table('reservation_table') + this.style+'</style>');

		// タイトル、リンク等を再構成
		$('body>div:eq(0)>p').hide();
		tmp = $('body>div:eq(1)>form');
		tmp.parent().removeClass('container');
		tmp.parent().addClass('fmBox').wrap('<div class="container" />').wrap('<div id="fmWrap"/>');
		ER_sub.topMenu();

		this.reMake($tgs);
		ER_sub.TBL_SCROLLABLE.ini('reservation_table');
		$(window).resize(ER_sub.TBL_SCROLLABLE.reSize);

		$tgs.click(function(ev){
			if (that.__work) { return; }
			if (!ev.shiftKey) {
				$('#reservation_table .selected').removeClass('selected');
			}
			$(this).addClass('selected');
			that.selC();
//			return false;
		});
		$('#reservation_table td.chkbox .ic_check').click(function(){
			if (that.__work) { return; }
			$(this).parents('tr').toggleClass('selected');
			that.selC();
			return false;
		});
		$('#reservation_table td.title a.moreBtn').click(function(){that._more($(this).attr('data-a'));return false;});
		if (ASSIST_INI.ext_diskinfo) {
			$('#tblMenu').before(ER_sub.DISKINF.html());
			ER_sub.DISKINF.reDiskInfo();
		} else if (INISet.disk_total && INISet.disk_free){
			$('#tblMenu').before(ER_sub.DISKINF.html(INISet.disk_total, INISet.disk_free ));
		}
		ER_sub.DISKINF.plusBar(this._durTotal, 'RSV');
		ER_sub.SELV.ini();
	},
	selC : function() {
		var $sel = $('#reservation_table .selected');
		if ($sel.length){
			$('#tblSel').removeClass('vsblN')
			$('#selCount').html($sel.length);
		} else {
			$('#tblSel').addClass('vsblN');
		}
	},
	unSel : function() {
		if (this.__work) { return; }
		$('#reservation_table tr.selected').removeClass('selected');
		$('#tblSel').addClass('vsblN')
	},
	reMake : function($tgs) {
		var $tg, $tds, tg0, d, h, tm, dur, img, Lap, href = DSP = Dstr = LD = chn = ctg = '', odd = true, DFn = ER_sub.DATE, Id, mode, keyword, dur, durTotal = 0;
		ToD = ER_sub.DATE.DateER(ER_sub.D),
		YsD = ER_sub.DATE.DateER(new Date((new Date(ER_sub.D)).setDate(ER_sub.D.getDate()-1))),
		Tmr = ER_sub.DATE.DateER(new Date((new Date(ER_sub.D)).setDate(ER_sub.D.getDate()+1)));

		$('#reservation_table tr:eq(0)').remove();
		$('#reservation_table').prepend(ER_sub.tableMultiHead);
		$tgs.each(function(){
			$tg = $(this);
			$tg.addClass('rsv unSel');
			$tds = $tg.children();
			// Id
			Id = $tds.eq(0).html();
			$tg.attr('data-rsvid',Id);
			// モード
			mode = $tds.eq(5).html();
			$tg.attr('data-mode',mode);
			// キーワード
			keyword = $tds.eq(8).html();
			$tg.attr('data-keyword', keyword);
			// チャンネル
			$tg0 = $tds.eq(1);
			$tg.attr('data-chtype', $tg0.html());
			$tg0.html('<div class="ch_Box">'+ (ER_sub.STR.toHan($tds.eq(2).html()))+'</div>');
			// カテゴリー
			ctg = $tg.attr('class').match(/ctg_([a-z]*)/)[1];
			$tds.eq(2).html('<span class="ctg_Box ctg_'+ctg+'"><span class="WD100">'+ER_sub.CTGS[ctg]+'</span><span class="WD100N">'+ctg.slice(0,3)+'</span></span>');
			// 番組長
			dur = parseInt((new Date($tds.eq(4).html().replace(/-/g,'/')) - new Date($tds.eq(3).html().replace(/-/g,'/')))/1000,10);
			$tg.attr('data-dur',dur);
			durTotal += dur;
			// タイトル
			href  = $tds.eq(3).find('>a').attr('href');
			$tds.eq(5).addClass('title').attr('colspan',2).html('<div><span class="WD70N" style="color:#A00;">●</span><span class="img WD70 rsvicon'+(keyword?' auto':'')+'"><span>'+(keyword||'●')+'</span></span><span class="WD70"><span class="WD90N spblock mode">'+mode.slice(0,3)+'</span><span class="WD90 spblock mode">'+mode+'</span></span><span class="title">' + $tds.eq(6).text() + '</span><span class="desc">' + $tds.eq(7).text() + '</span><span class="WD70N" style="color:#EEE;">'+(dur?parseInt((dur/60),10)+'分 ':'')+mode+'</span></div><a href="javascript:ER_sub.RESERVED._more('+Id+');" class="moreBtn" data-a="'+Id+'">more</a>');
			$tds.eq(6).remove();
			$tds.eq(7).remove();
			$tds.eq(8).remove();

			// 日付
			$tg0 = $tds.eq(0).addClass('date');
			d = $tds.eq(3).html().replace(/-/g,'/');
			$tg.attr('data-date', d);
			tm = ER_sub.DATE.AP(d.substr(11,5));
			if ((d.slice(0,10)!=DSP) && DSP) {
				Lap = false;
				if (d.slice(0,7)!=DSP.slice(0,7)) {
					if (d.slice(0,4)!=DSP.slice(0,4)){
						$tg.before('<tr class="ySplit split" data-split="6"><td colspan="2" class="year">'+DFn.Year(d)+'</td><td colspan="5">&nbsp;</td></tr>');
					} else {
						$tg.before('<tr class="mSplit split" data-split="4"><td colspan="7"></td></tr>');
					}
				} else {
					$tg.before('<tr class="dSplit split" data-split="2"><td colspan="7"></td></tr>');
				}
			}
			$tg0.after('<td class="time">'+(ASSIST_INI.time_ap?'<span class="WD70"><span class="ap ap_'+tm.ap+((Lap&&tm.ap==Lap)?' vsblN hv':'')+'">'+tm.ap+'</span>'+tm.html+'</span><span class="WD70N">'+tm._24+'</span>':tm._24)+(dur?'<span class="WD70"><span style="color:#555;padding:0 0.4em;">+</span><span class="durTxt">'+ER_sub.DATE.durToHm(dur)+'</span></span>':'')+'</td>');
			Lap = tm.ap;
			DSP = d.slice(0,10);
			Dstr = '';
			switch(DSP) {
			case ToD :
				Dstr = '<span class="dString">今日</span>';break;
			case YsD :
				Dstr = '<span class="dString">昨日</span>';break;
			case Tmr :
				Dstr = '<span class="dString">明日</span>';break;
			default :
				if (DSP.slice(0,7) == LD.slice(0,7)) {
					Dstr = '<span class="month vsblN hv">'+DFn.Month(DSP)+'</span>'+DFn.Date(DSP);
				} else {
					Dstr = '<span class="month">'+DFn.Month(DSP)  + '</span>' + DFn.Date(DSP);
				}
			}
			$tg0.html((LD==DSP?'<span class="vsblN hv" >':'')+Dstr + DFn.Day(DSP,'<span class="WD70">（</span>', '<span class="WD70">）</span>')+(LD==DSP?'</span>':''));
			LD = DSP;
			$tds.eq(3).remove();
			$tds.eq(4).remove();
			$tds.eq(9).remove();
			$tg.prepend('<td class="chkbox"><div class="iconW20 ic_check">&nbsp;</div></td>');
		});
		this._durTotal = durTotal*1024*1024/8*ASSIST_INI.ext_diskinfo_rsvBar_bitrates['GR'];
	},
	_more : function(Id) {
		$tg = $('#reservation_table tr[data-rsvid='+Id+']');
		$tg.toggleClass('moreOpen').toggleClass('unSel');
	},
	del : function(Id) {
		var that = this;
		$.post(INISet.prgCancelURL, { program_id: Id } ,function(data){that._rsv(Id, data, false);});
	},
	__work : null,
	__wmode : '',
	__mes : null,
	_prginf : function(Id) {
		$tg = $('#reservation_table tr[data-rsvid='+Id+']');
		return $tg.attr('data-date')+' '+$tg.find('>td.title .title').text();
	},
	del : function() {
		if (this.__work) { return; }
		var that = this, $tgs;
		$tgs = $('#reservation_table tr.selected');
		if ($tgs.length > 0 ){
			this.__work = $tgs.eq(0).attr('data-rsvid');
			this.__wmode = '予約キャンセル';
		}
		this.__mes = [];
		this._del();
	},
	_del : function() {
		var $tgs, that = this, er;
//		console.log(this.__work);
		if(this.__work && this.__work != 'alert'){
			ER_sub.PLST.showM({style:'orange',html:'<span><span style="font-weight:bold;font-size:120%;">'+$('#reservation_table tr.selected').length+'</span> 番組処理中</span>'});
			$.post(INISet.prgCancelURL, { reserve_id: this.__work} ,function(data){
				if(data.match(/error/i)){
					if(data.match(/^error/i)){
						er = 'epgrecエラー：';
					} else {
						er = 'phpエラー：';
						data = data.replace(/^<br \/>/,'');
					}
					that.__mes.push('<div>'+that._prginf(that.__work)+'</div><span class="title">'+er+'</span><span class="">'+data+'</span>');
					$('#reservation_table tr[data-rsvid='+that.__work+']').addClass('error');
				} else {
					ER_sub.tableDelReSplit($('#reservation_table tr[data-rsvid='+that.__work+']'));
				}
				$tgs = $('#reservation_table tr.selected:not(.error)');
				if ( $tgs.length > 0 ) {
					that.__work = $tgs.eq(0).attr('data-rsvid');
					that._del();
				} else {
					that.__work = null;
					$('#tblSel').addClass('vsblN');
					ER_sub.SELV.all();
					if ($('#reservation_table tr.error').length) {
						that.__work = 'alert';
						$('#reservation_table tr.selected').removeClass('selected');
						ER_sub.PLST.showM({style:'error',title:that.__mes.length+'番組の'+that.__wmode+'中にエラーが発生しました。'+that.__wmode+'処理が終了したかどうか確認できません',html:that.__mes.join('<hr class="mesSplit" />')});
					} else {
						// 正常終了
						ER_sub.PLST.showM({html:that.__wmode+'が正常に終了しました'});
						ER_sub.DISKINF.rePlusBar();
						setTimeout( function(){ER_sub.PLST.hideM();},2000)
					}
				}
			});
		}
	}
}

// ================== 録画済一覧
ER_sub.RECORDED = {
	style : '',
	ini : function() {
		var tmp, keyword = null, that = this, $tgs = $('#reservation_table tr:not(:eq(0))');
		$('body').append('<style type="text/css"><!-- ' + ER_sub.STYLES.basic + ER_sub.STYLES.colorD + ER_sub.STYLES.table('reservation_table') + ER_sub.STYLES.md_screen + this.style+'</style>');

		// タイトル、リンク等を再構成
		$('body>div:eq(0)>p').hide();
		tmp = $('body>div:eq(1)>form');
		tmp.parent().removeClass('container');
		tmp.parent().addClass('fmBox').wrap('<div class="container" />').wrap('<div id="fmWrap"/>');
		ER_sub.topMenu();
		// 「タイトルや内容をクリックす…」を隠す
		if ($('#reservation_table').length){
			$('#reservation_table').prev().hide();
		}

		this.reMake($tgs);
		ER_sub.TBL_SCROLLABLE.ini('reservation_table');
		$(window).resize(ER_sub.TBL_SCROLLABLE.reSize);

		//
		$tgs.click(function(ev){
			if (that.__work) { return; }
			if (!ev.shiftKey) {
				$('#reservation_table .selected').removeClass('selected');
			}
			$(this).addClass('selected');
			that.selC();
//			return false;
		});
		$('#reservation_table td.chkbox .ic_check').click(function(){
			if (that.__work) { return; }
			$(this).parents('tr').toggleClass('selected');
			that.selC();
			return false;
		});
		$('#reservation_table td.title a.moreBtn').click(function(){that._more($(this).attr('data-a'));return false;});
		if (ASSIST_INI.ext_diskinfo) {
			$('#tblMenu').before(ER_sub.DISKINF.html());
			ER_sub.DISKINF.reDiskInfo();
		} else if (INISet.disk_total && INISet.disk_free){
			$('#tblMenu').before(ER_sub.DISKINF.html(INISet.disk_total, INISet.disk_free ));
		}
		ER_sub.SELV.ini();
	},
	withF : function() {
		$('#delWithFile').toggleClass('checked');
		$('#tblMenuBtn').html(this._rcdDelText());
	},
	_rcdDelText :function(op) {
		if ($('#delWithFile').hasClass('checked') || op){
			return 'ファイルと<span class="WD70">共に</span>削除';
		} else {
			return '<span class="WD70">記録</span>データのみ削除';
		}
	},	
	selC : function() {
		var $sel = $('#reservation_table .selected');
		if ($sel.length){
			$('#tblSel').removeClass('vsblN')
			$('#selCount').html($sel.length);
		} else {
			$('#tblSel').addClass('vsblN');
		}
	},
	unSel : function() {
		if (this.__work) { return; }
		$('#reservation_table tr.selected').removeClass('selected');
		$('#tblSel').addClass('vsblN')
	},
	reMake : function($tgs) {
		var $tg, $tds, tg0, d, h, tm, dur, img, Lap, href = DSP = Dstr = LD = cls = '', odd = true, DFn = ER_sub.DATE, Id, mode;
		ToD = ER_sub.DATE.DateER(ER_sub.D),
		YsD = ER_sub.DATE.DateER(new Date((new Date(ER_sub.D)).setDate(ER_sub.D.getDate()-1))),
		Tmr = ER_sub.DATE.DateER(new Date((new Date(ER_sub.D)).setDate(ER_sub.D.getDate()+1)));

		$('#reservation_table tr:eq(0)').remove();
		$('#reservation_table').prepend(ER_sub.tableMultiHead);
		$('#reservation_table thead tr').append('<th class="filesize">サイズ</th>');
	// var T0 = new Date();
		$tgs.each(function(){
			$tg = $(this);
			$tg.addClass('rcd unSel');
			$tds = $tg.children();
			// サムネイル
			img = $tds.eq(3);
			if (img.find('img').length) {
				img = $tds.eq(3).html();
				$tds.eq(3).remove();
				$tds = $tg.children();
			} else {
				img = false;
			}
			//番組長
			dur = $tg.attr('data-dur') || '';
			// 編集、削除ボタンを無効化、隠す
//			$tg.find('input').attr('disabled','disabled');
			$tds.eq(6).hide().next().hide();
			Id = $tg.attr('id').match(/[0-9]*$/)[0];
			$tg.attr('data-rsvid',Id).addClass(odd?'odd':'even');odd = !odd;
			// チャンネル
			$tds.eq(1).html('<div class="ch_Box">'+ (ER_sub.STR.toHan($tds.eq(1).html()))+'</div>');
			// モード
			mode = $tds.eq(2).html();
			$tg.attr('data-mode', mode);
			// カテゴリー
			cls = $tg.attr('class').match(/ctg_([a-z]*)/)[1];
			$tds.eq(2).html('<span class="ctg_Box ctg_'+cls+'"><span class="WD100">'+ER_sub.CTGS[cls]+'</span><span class="WD100N">'+cls.slice(0,3)+'</span></span>');
			// タイトル
			href  = $tds.eq(3).find('>a').attr('href');
			$tds.eq(4).attr('colspan',img?1:2).addClass('title').html('<div><span class="WD70N" style="color:#0BD;">&gt;</span><span class="img play WD70"><a href="'+href+'" class="play">Play</a></span><span class="WD70"><span class="WD90N spblock mode">'+mode.slice(0,3)+'</span><span class="WD90 spblock mode">'+mode+'</span></span><a href="'+href+'" class="title">' + $tds.eq(3).text() + '</a><span class="desc">' + $tds.eq(4).text() + '</span><span class="WD70N" style="color:#EEE;">'+(dur?parseInt((dur/60),10)+'分 ':'')+mode+'</span></div><a href="javascript:ER_sub.RECORDED._more('+Id+');" class="moreBtn" data-a="'+Id+'">more</a>');
			if (img) {
				$tds.eq(3).addClass('thumb').html(img);
			} else {
				$tds.eq(3).remove();
			}
			// 日付
			$tg0 = $tds.eq(0).addClass('date');
			d = $tg0.html().replace(/-/g,'/');
			$tg.attr('data-date', d);
			tm = ER_sub.DATE.AP(d.substr(11,5));
			if ((d.slice(0,10)!=DSP) && DSP) {
				Lap = false;
				if (d.slice(0,7)!=DSP.slice(0,7)) {
					if (d.slice(0,4)!=DSP.slice(0,4)){
						$tg.before('<tr class="ySplit split" data-split="6"><td colspan="2" class="year">'+DFn.Year(d)+'</td><td colspan="5">&nbsp;</td></tr>');
					} else {
						$tg.before('<tr class="mSplit split" data-split="4"><td colspan="7"></td></tr>');
					}
				} else {
					$tg.before('<tr class="dSplit split" data-split="2"><td colspan="7"></td></tr>');
				}
			}
			$tg0.after('<td class="time">'+(ASSIST_INI.time_ap?'<span class="WD70"><span class="ap ap_'+tm.ap+((Lap&&tm.ap==Lap)?' vsblN hv':'')+'">'+tm.ap+'</span>'+tm.html+'</span><span class="WD70N">'+tm._24+'</span>':tm._24)+(dur?'<span class="WD70"><span style="color:#555;padding:0 0.4em;">+</span><span class="durTxt">'+ER_sub.DATE.durToHm(dur)+'</span></span>':'')+'</td>');
			Lap = tm.ap;
			DSP = d.slice(0,10);
			Dstr = '';
			switch(DSP) {
			case ToD :
				Dstr = '<span class="dString">今日</span>';break;
			case YsD :
				Dstr = '<span class="dString">昨日</span>';break;
			case Tmr :
				Dstr = '<span class="dString">明日</span>';break;
			default :
				if (DSP.slice(0,7) == LD.slice(0,7)) {
					Dstr = '<span class="month vsblN hv">'+DFn.Month(DSP)+'</span>'+DFn.Date(DSP);
				} else {
					Dstr = '<span class="month">'+DFn.Month(DSP)  + '</span>' + DFn.Date(DSP);
				}
			}
			$tg0.html((LD==DSP?'<span class="vsblN hv" >':'')+Dstr + DFn.Day(DSP,'<span class="WD70">（</span>', '<span class="WD70">）</span>')+(LD==DSP?'</span>':''));
			LD = DSP;
			$tg.prepend('<td class="chkbox"><div class="iconW20 ic_check">&nbsp;</div></td>');
		});
	},
	_more : function(Id) {
		$tg = $('#reservation_table tr[data-rsvid='+Id+']');
		$tg.toggleClass('moreOpen').toggleClass('unSel');
	},
	__work : null,
	__wmode : '',
	__mes : null,
	_prginf : function(Id) {
		$tg = $('#reservation_table tr[data-rsvid='+Id+']');
		return $tg.attr('data-date')+' '+$tg.find('>td.title .title').text();
	},
	del : function() {
		if (this.__work) { return; }
		var that = this, $tgs;
		$tgs = $('#reservation_table tr.selected');
		if ($tgs.length > 0 ){
			this.__work = $tgs.eq(0).attr('data-rsvid');
			this.__wmode = '削除';
		}
		this.__mes = [];
		this._del();
	},
	_del : function() {
		var $tgs, that = this, delFile = $('#delWithFile').hasClass('checked')?1:0, er;
		if(this.__work && this.__work != 'alert'){
			ER_sub.PLST.showM({style:'orange',html:'<span><span style="font-weight:bold;font-size:120%;">'+$('#reservation_table tr.selected').length+'</span> 番組処理中</span>'});
			$.post(INISet.prgCancelURL, { reserve_id: this.__work, delete_file: delFile } ,function(data){
				if(data.match(/error/i)){
					if(data.match(/^error/i)){
						er = 'epgrecエラー：';
					} else {
						er = 'phpエラー：';
						data = data.replace(/^<br \/>/,'');
					}
					that.__mes.push('<div>'+that._prginf(that.__work)+'</div><span class="title">'+er+'</span><span class="">'+data+'</span>');
//					console.log(data);
					$('#reservation_table tr[data-rsvid='+that.__work+']').addClass('error');
				} else {
					ER_sub.tableDelReSplit($('#reservation_table tr[data-rsvid='+that.__work+']'));
				}
				$tgs = $('#reservation_table tr.selected:not(.error)');
				if ( $tgs.length > 0 ) {
					that.__work = $tgs.eq(0).attr('data-rsvid');
					that._del();
				} else {
					that.__work = null;
					$('#tblSel').addClass('vsblN');
					ER_sub.SELV.all();
					if ($('#reservation_table tr.error').length) {
						that.__work = 'alert';
						$('#reservation_table tr.selected').removeClass('selected');
						ER_sub.PLST.showM({style:'error',title:that.__mes.length+'番組の'+that.__wmode+'中にエラーが発生しました。'+that.__wmode+'処理が終了したかどうか確認できません',html:that.__mes.join('<hr class="mesSplit" />')});
					} else {
						// 正常終了
						ER_sub.PLST.showM({html:that.__wmode+'が正常に終了しました'});
						// ディス残量更新
						ER_sub.DISKINF.reDiskInfo();
						setTimeout( function(){ER_sub.PLST.hideM();},2000)
					}
				}
			});
		}
	}
};

// ================== 環境設定
ER_sub.SETTING = {
	style : 'table#log_table td.errorlevel0 {background-color:transparent;} table#log_table td.errorlevel1 {background-color:transparent;color:yellow;} table#log_table td.errorlevel2 {background-color:transparent;color:red;}',
	ini : function() {
		$('body').append('<style type="text/css"><!-- ' + ER_sub.STYLES.colorD + ER_sub.STYLES.table('log_table') + this.style+'</style>');

		// タイトル、リンク等を再構成
		var tmp = $('body>div:eq(0)>p');
		tmp.insertAfter($('body>div:eq(0)'));
		tmp.wrap('<div id="fmWrap" class="container" />').wrap('<div class="fmBox" style="float:right;" />');
		ER_sub.topMenu();

		if ($('#log_table').length) {
			ER_sub.TBL_SCROLLABLE.ini('log_table');
			$(window).resize(ER_sub.TBL_SCROLLABLE.reSize);
		}
	}
};

// ================== diskinfo
ER_sub.DISKINF = {
	_levels : null,
	html : function(total, free) {
		var per, level, loading = null;
		if (!total && !free){
			total = free = 1;
			loading = 'Loading...';
		}
		this._levels = ASSIST_INI.ext_diskinfo_levels;
		while (this._levels.length < 4) {
			this._levels.push(0);
		}
		this._levels.sort();
		per = ((total - free)*100 / total);
		level = this._diskLevel(per);
		return '<style type="text/css"><!-- -->#diskInfoBox {width:200px;height:26px;border-radius:0.6em;overflow:hidden;box-shadow:inset 0 1px 4px #420;background:#555;line-height:26px;}#diskInfoBar {height:26px;overflow:hidden;border-radius:0 0.6em 0.6em 0;box-shadow:inset 0 0 6px #28B,0 0 4px #000;background:#049;background:-webkit-gradient(linear, left top, left bottom, from(#05A),to(#238));background:-moz-linear-gradient(top, #05A, #238)}#diskInfoBox.lv1{background:#662;}#diskInfoBox.lv2{background:#970;}#diskInfoBox.lv3{background:#C60;}#diskInfoBox.lv4{background:#F20;}#diskInfoFreeText{font-size:120%;}@media screen and (max-width:900px) {#diskInfoBox,#diskInfoBar{height:20px;}#diskInfoBox{width:150px;line-height:20px;}#diskInfoFreeText{font-size:110%;}}</style><div style="position:absolute;margin-top:14px;right:2em;"><div id="diskInfoBox" class="lv'+level+'"  data-total="'+total+'" data-free="'+free+'"><div id="diskInfoBar" style="width:'+per+'%;" >&nbsp;</div><div style="position:absolute;top:0;width:100%;color:#DDD;font-weight:bold;font-family: arial,helvetica;text-align:right;">Free : <span id="diskInfoFreeText" style="color:#FFF;text-shadow:0 0 3px #000;padding-right:0.5em;">' + (loading?loading:ER_sub.filesize(free))+'</span></div></div></div>';
	},
	reDiskInfo : function(total, free) {
		if (!ASSIST_INI.ext_diskinfo) { return; }
		if (!total && !free)
			this._getDiskInfo();
		else 
			this._reDiskInfo(total, free);
	},
	_reDiskInfo : function(total, free) {
		var per = ((total - free)*100 / total);
		$('#diskInfoBox').attr('data-total', total).attr('data-free', free).attr('class','').addClass('lv'+this._diskLevel(per));;
		$('#diskInfoFreeText').html(ER_sub.filesize(free));
		$('#diskInfoBar').animate({width: per +'%'},150);
		if ($('#diskInfo_RSV').length){
			this.rePlusBar(total);
		}
	},
	_diskLevel : function(per) {
		var level = 0, i, iMax;
		for ( i=0,iMax=this._levels.length; i<iMax; i++) {
			if (this._levels[i] <= per) { level = i+1}
		}
		return level;
	},
	_getDiskInfo : function() {
		var that = this;
		$.getJSON(INISet.prgHomeURL+'assist/tools/diskinfo.php',{},function(data){
			that._reDiskInfo(data.disk_total, data.disk_free);
		});
	},
	plusBar : function(size, Id) {
		$('#diskInfoBox').attr('data-durtotal', size);
		size = ER_sub.filesize(size);
		if ($('#diskInfo_'+Id).length) {
			$('#diskInfo_'+Id+' .text').html(size);
		} else {
			$('#diskInfoBox').after('<div id="diskInfo_'+Id+'" style="text-align:right;margin-top:4px;position:relative;z-index:300;line-height:14px;color:#888;"><span class="WD90">Reserve：</span><span class="text" style="color:#EEE;">'+size+'</span><span id="plusBar_'+Id+'" style="display:inline-block;width:1px;height:14px;background:#A20;border-radius:0.4em;margin-left:4px;">&nbsp;</span></div>');
		}
	},
	rePlusBar : function(total, durTotal) {
		if (!total) {
			total = parseInt($('#diskInfoBox').attr('data-total'),10);
		}
		if  (durTotal==null) {
			// durTotal = parseInt($('#diskInfoBox').attr('data-durtotal'),10);
			durTotal = this.getDurTotal();
		}
		$('#diskInfo_RSV .text').html(ER_sub.filesize(durTotal));
		per = ((durTotal) * 100 / total);
		$('#plusBar_RSV').width(per+'%');
	},
	getDurTotal : function(){
		// 予約容量更新
		var durTotal = 0;
		$('#reservation_table tr.rsv:visible').each(function(){
			durTotal += parseInt($(this).attr('data-dur'), 10)*131072*ASSIST_INI.ext_diskinfo_rsvBar_bitrates[$(this).attr('data-chType')];
		});
		this.rePlusBar(parseInt($('#diskInfoBox').attr('data-total'),10), durTotal);
		return durTotal;
	}
}

ER_sub.tableDelReSplit = function($tg) {
	var $n = $tg.next(), $p = $tg.prev(), sp = '0';
	if (!$p.length){
		if ($n.hasClass('split')){
			$n.remove();
		} else {
			// all
			$n.find('.vsblN.hv').removeClass('vsblN');
		}
	} else if ($n.length){
		if ($p.hasClass('split')){
			$n.find('td.time .vsblN.hv').removeClass('vsblN');
			sp = $p.attr('data-split');
			if ($n.hasClass('split')){
				if ($p.attr('data-split') > $n.attr('data-split')) {
					$n.remove();
				} else {
					$p.remove();
				}
			}
		} else {
			if (!$n.hasClass('split')){
				if (!$tg.find('td.time .ap').hasClass('vsblN')){
					$n.find('td.time .ap').removeClass('vsblN');
				}
			}
		}
	}
	switch(sp){
	case '0':
		break;
	case '2':
		$tg.next().find('td.date>span').removeClass('vsblN');break;
	case '4':
	case '6':
		$tg.next().find('td.date .vsblN').removeClass('vsblN');
	}
	$tg.remove();
}

ER_sub.tableMultiFunc = function(php) {
	php = php.toUpperCase() || 'RECORDED';
	var btn;
	switch(php) {
	case 'RECORDED':
		btn = ER_sub.RECORDED._rcdDelText(ASSIST_INI.recorded_delete_with_file);
		break;
	case 'RESERVED':
		btn = '予約キャンセル';
	}
	return '<div id="tblMenu"><div id="tblSel" class="vsblN"><a href="javascript:ER_sub.'+php+'.unSel()" title="選択解除" id="mutiPrgCnt" class="iconW20 ic_chkCnt">&nbsp;</a><span style="color:#888;"><span id="selCount">0</span>番組選択 </span><a href="javascript:ER_sub.'+php+'.del()" class="btnB del" id="tblMenuBtn">'+btn+'</a>'+(php=='RECORDED'?'<a href="javascript:ER_sub.RECORDED.withF()" class="ezChk'+(ASSIST_INI.recorded_delete_with_file?' checked':'')+'" id="delWithFile" style="margin-left:1em;"><span><span class="WD90">録画</span>ファイルも削除<span class="WD90">する</span></span></a>':'')+'</div><div id="tblMes">&nbsp;</div></div>';
}
ER_sub.tableMultiHead = '<thead><tr class="th"><th class="" style="padding:0 5px;text-align:right;">&nbsp;</th><th class="date" colspan="2">日付</th><th class="ch" ></span><span class="WD100">チャンネル</span><span class="WD100N">Ch</span></th><th class="ctg" ><span class="WD100">カテゴリ</span><span class="WD100N">Ctg</span></th><th class="title" colspan="2">番組名／内容</th></tr></thead>';

ER_sub.PLST = {
	showM : function(op){
		$('#tblMes').html('<div class="Box '+(op.style||'green')+'">'+(op.title?'<div class="title">'+op.title+'</div>':'')+op.html+'</div>').show();
	},
	hideM : function(){
		$('#tblMes').fadeOut();
	}
};

ER_sub.SELV = {
	enabled : false,
	ini : function() {
		if (!ASSIST_INI.selv) {
			this.enabled = true;
			return false;
		}
		var that = this;
		$('table.PRGS_LIST_TABLE').after('<div id="selvAll" style="display:none;">View All</div>');
		$('#tblSel').hover(that.only);
		$('#selvAll').hover(that.all);
	},
	only : function(){
		if(this.enabled){return;}
		$('table.PRGS_LIST_TABLE').addClass('selOnly');
		$('table.PRGS_LIST_TABLE tbody tr').each(function(){
			if(!$(this).hasClass('selected')){
				$(this).addClass('selVnone');
			}
		});
		$('#selvAll').show();
	},
	all : function(){
		if(this.enabled){return;}
		$('table.PRGS_LIST_TABLE').removeClass('selOnly');
		$('table.PRGS_LIST_TABLE tr.selVnone').removeClass('selVnone');
		$('#selvAll').hide();
	}
};

// =============== チャンネルフォームアシスト
ER_sub.FRM ={
	// ひらがなカタカタ
	Astr : {
		a : 'あいうえおアイウエオ',
		i : 'いイ', u : 'うウ', e : 'えエ', o : 'おオ',
		k : 'かきくけこカキクケコがぎぐげごガギギゲゴ',
		g : 'がぎぐげごガギギゲゴ',
		s : 'さしすせそサシスセソざじずぜぞザジズゼゾ',
		z : 'ざじずぜぞザジズゼゾ',
		t : 'たちつてとタチツテトだぢづでどダヂヅデド',
		c : 'ちチ',
		d : 'だぢづでどダヂヅデド',
		n : 'なにぬねのナニヌネノ',
		h : 'はひふへほハヒフヘホぱぴぷぺぽパピプペポばびぶべぼバビブベボ',
		p : 'ぱぴぷぺぽパピプペポ',
		b : 'ばびぶべぼバビブベボ',
		m : 'まみむめもマミムメモ',
		y : 'やゆよヤユヨ',
		r : 'らりるれろラリルレロ',
		w : 'わワ'
	},
	// 漢字等
	Kstr : {a : '朝衛青岩秋愛大岡', i : '岩石', e : '衛愛', o : '大岡', k : '関高鹿北九熊火金', g : '群月', s : '札仙静四瀬山信水', j : '時', h : '放北東福広', n : '日新長奈南西', t : '東宝旅富中土動', c : '中', d : '動土', m : '三宮南木', y : '洋山読', r : '琉'},
	// エイリアス
	Xstr : {f : 'h', j : 'z', l : 'r'},
	key_is : function(key, str) {
		if (!key || !str) {return false;}
		key = String.fromCharCode(key).toLowerCase();
		str = ER_sub.STR.toHan(str[0]).toLowerCase();
		if ( key == str ) { return true;}
		if ( this.Xstr[key] != undefined ) { key = this.Xstr[key];}
		if ( this.Astr[key] != undefined && this.Astr[key].indexOf(str) > -1 ){ return true;}
		if ( this.Kstr[key] != undefined && this.Kstr[key].indexOf(str) > -1 ){ return true;}
	},
	selectAssist : function(tg, attr){
		if (!tg) { tg = 'select';}
		attr = attr?attr.toLowerCase():'html';
		switch (attr) {
		case 'value':
			keyFn = function(t){return $(t).val();};break;
		case 'accesskey':
			keyFn = function(t){return $(t).attr('accesskey');};break;
		case 'html':
			keyFn = function(t){return $(t).html();};break;
		case 'text':
			keyFn = function(t){return $(t).text();};break;
		default:
			keyFn = function(t){return $(t).attr(attr);};break;
		}
		$(tg).each(function(){
			var $tg = $(this), W = false,
			Ret = function(r){W=false;return r||false};
			$tg.focus(function(){
				$tg .bind('keydown', (function(ev){
					T0 = new Date();
					if ( W ) {return ;}
					W = true;
					if (ev.keyCode < 30 || ER_sub.STR.Hstr.indexOf(String.fromCharCode(ev.keyCode)) < 0) {
						return Ret(true) ;
					}
					var S = L = $tg.find('option:selected'),
					select, i=0,iMax=$tg.find('option:enabled').length;
					if (iMax == 0) { return Ret(false); }
					while (!select) {
						if ( ev.shiftKey ){
							if (S.prevAll('option:enabled').length == 0) {
								S = $tg.find('option:enabled:last');
							} else {
								S = S.prevAll('option:enabled:first');
							}
						} else {
							if (S.nextAll('option:enabled').length == 0) {
								S = $tg.find('option:enabled:first');
							} else {
								S = S.nextAll('option:enabled:first');
							}
						}
						if ( ER_sub.FRM.key_is(ev.keyCode, keyFn(S)) && !S.attr('disabled')){
							select = S;
							// for Firefox
							if ($.browser.mozilla && !ev.shiftKey && (attr == 'html' || attr == 'text') &&  keyFn(S).match(/[A-Za-z0-9]/)) {
//								console.log('FireFox:' + $.browser.version, navigator.userAgent);
								return Ret(true);
							}
						} else {
							i++;
							if ( i > iMax ) {
								return Ret(false);
//								select = L;
							}
						}
					}
					$tg.val(select.val()).change();
					return Ret(false);
				}));
			}).blur(function(){
				$tg .unbind('keydown');
			});
		});
	},
	typeStyle : '<style type="text/css"><!-- option.disabled {display:none;} option.BS {background:#FFE4D8;} option.CS {background:#D4FFF8;}--></style>',
	chTypeAssist : function() {
		$('body').append(this.typeStyle);
		var that = this, $tg = $('select[name=type]');
		this._chTypeAssist($tg.val());
		$tg.change(function(){
			that._chTypeAssist($(this).val());
		});
	},
	_chTypeAssist :function(v) {
		var $st_tg = $('select[name=station]');
//			for IE
//				require jQuery 1.4
//				$st_tg.find('option:disabled').unwrap();
				$st_tg.find('>div').each(
					function(){$(this).after($(this).html());}
				).remove();
		if (v == '*') {
			$st_tg.find('option').removeAttr('disabled').removeClass('disabled');
		} else {
			$st_tg.find('option[value!=0]').attr('disabled', 'disabled').addClass('disabled');
			$st_tg.find('option.'+v).removeAttr('disabled').removeClass('disabled');
//				for IE
				$st_tg.find('option:disabled').wrap('<div style="display:none"/>');
			if (!$st_tg.find(':selected').hasClass(v)) {
				$st_tg.val('0');
			}
		}
	}
}

ER_sub.SCRL = {
	inied:false,
	ini:function(){
	},
	/**
	 *	jQuery ObjectまたはhtmlIDを表示できるようにスクロール
	 * @param {jQuery Object | String('#htmlid')}
	 */
	toHTML:function(p,effect){
		if(!this.inied){this.ini();this.inied=true;}
		var tg = $(p),
			o = tg.offset(),
			w = tg.width(),
			h = tg.height(),
			wTop = $(document).scrollTop(),
			wLeft = $(document).scrollLeft(),
			inWidth = parseInt($(window).width()),
			inHeight = parseInt($(window).height());
/*
		// IE6
		if ($.browser.msie && $.browser.version == 6){
			inWidth = document.documentElement.clientWidth;
			inHeight = document.documentElement.clientHeight;
		}
*/
		if(o.left+w > inWidth + wLeft - $('#tvtimes2').width() + 2){
			wLeft = o.left - inWidth + w + $('#tvtimes2').width() + 2;
		} else if(o.left < wLeft + $('#tvtimes').width() - 2){
			wLeft = o.left - $('#tvtimes').width() - 2;
		}
		if (o.top < wTop + $('#float_titles').height()){
			wTop = o.top - $('#float_titles').height();
		} else if(o.top+h > inHeight + wTop) {
			wTop = o.top - inHeight + h;
		}

//		ER.PRG.hDisabled = true;
		this._scrollTo({scrollLeft : wLeft, scrollTop : wTop},effect);
	},
	/**
	 * 画面をスクロールする
	 * @param {Objext : {
	 * 	x : {Number},
	 *		y : {Number}
	 *	}}
	 */
	_scrollTo : function (scr, effect) {
		if (effect){
			$('html, body').stop(false,true).animate(scr, 120,null, function(){});
		} else {
			$('html, body').stop(false,true).scrollLeft(scr.scrollLeft).scrollTop(scr.scrollTop);
		}
	}
};

ER_sub.CTGS = {news:'ニュース・報道',drama:'ドラマ',variety:'バラエティ',etc:'その他',information:'情報',anime:'アニメ・特撮',sports:'スポーツ',music:'音楽',cinema:'映画'};

ER_sub.STR ={
	Zstr : '０１２３４５６７８９ａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚＡＢＣＤＥＦＧＨＩＪＫＬＭＮＯＰＱＲＳＴＵＶＷＸＹＺ　～！＠＃＄％＾＆＊（）[]＿＋｜：；',
	Hstr : '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ ~!@#$%^&*()[]_+|:;',
	toHan : function (str) {
		var i,iMax,T,N='';
		for (i = 0, iMax = str.length; i < iMax; i++) {
			T = this.Zstr.indexOf(str[i]);
			if (T > -1) { N += this.Hstr[T];
			} else { N += str[i];}
		}
		return N;
	}
}

ER_sub.DATE = {
	dayStr : ['日', '月', '火', '水', '木', '金', '土'],
	AP : function (d ) {
		var ap = 'AM',z = 0;
		d = d.replace(/^0/,'').split(':');
		d[2] = d[0]+':'+d[1];
		if (d[0]>11) {ap = 'PM'}
		d[0] = d[0]%12;
		if ( d[0] <10){z = 1;}
		return {ap:ap, aphtml:'<span class="ap ap_'+ap+'">'+ap+'</span>', html:'<span class="ap_time">'+(z?'<span class="zero">0</span>':'')+d[0]+':'+d[1]+'</span>',_24:d[2]};
	},
	DateER : function(D,s) {return D.getFullYear()+(s?s:'/')+$.N2S(D.getMonth()+1, 2)+(s?s:'/')+$.N2S(D.getDate(),2);},
	Year : function(D,op) {return '<span class="year">'+'<span class="dNum">'+D.slice(0,4)+'</span>'+(op?op:'<span class="sub">年</span>')+'</span>';},
	Month : function(D,op) {return '<span class="month"><span class="dNum">'+parseInt(D.slice(5,7),10)+'</span>'+(op?op:'<span class="sub">月</span>')+'</span>';},
	Date : function(D,op) {return '<span class="date">'+'<span class="dNum">'+parseInt(D.slice(8,10),10)+'</span>'+(op?op:'<span class="sub">日</span>')+'</span>';},
	Day : function(D,L,R) {
		var dw = (new Date(D)).getDay();
		return '<span class="dw dw_'+dw+'">'+(L?L:'')+this.dayStr[dw]+(R?R:'')+'</span>';
	},
	durToHm : function(t, h) {
		t = parseInt(t/60,10);
		if (h) return t;
		h = t % 60;
		t = parseInt(t / 60,10);
		if ( t == 0 ) {
			t = '<span class="vsblN">0:';
			if  ( h < 10 ){
				return t+'0</span>'+h;
			} else {
				return t+'</span>'+h;
			}
		} else {
			return t+':'+$.N2S('0'+h,2);
		}
	}
}
ER_sub.DateToString = function(D) {
	return D.getFullYear()+'/'+$.N2S(D.getMonth()+1,2)+'/'+$.N2S(D.getDate(),2)+' '+$.N2S(D.getHours(),2)+':'+$.N2S(D.getMinutes(),2)+':'+$.N2S(D.getSeconds(),2);
};
ER_sub.Dt2S = ER_sub.DateToString;

ER_sub.MSGBX = {
	__uid : 0,
	redAlert : function(op) {
		op = op || {};
		this.__uid++;
		return '<div id="msgbx_al_'+this.__uid+'" class="MSGBX redAlert"><span class="icon warning"></span><span class="title">'+(op.title?op.title:'Error')+' : </span>'+(op.mes?op.mes:'')+'<a href="javascript:ER_sub.MSGBX.alClose(\'msgbx_al_'+this.__uid+'\')" class="close">X</a></div>';
	}
};

// file,disksize
ER_sub.filesize = function(s,u) {
	var sizeunit = 0;
	var units = u||['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];
	var reSize = function(s) {
		if (s > 1024) {
			s = reSize(s / 1024);
			sizeunit++;
		}
		return s;
	}
	s = (reSize(s)+'').split('.');
	return s[0]+(sizeunit?('.'+(s[1]?s[1].slice(0,2):'00')):'') + '<span class="unit" style="font-size:60%;color:#DDD">'+units[sizeunit]+'</span>';
};

//==========================
ASSIST_INI.ver = '0.1.1';

// epgrec_assistを有効にする
// 	epgrecのバージョンアップ等による
//		不具合が出た場合は 0 にしてください
ASSIST_INI.use_this = 1;

// 時間表示を12時間表示
ASSIST_INI.time_ap = 1;

// 番組表で有効にする
ASSIST_INI.index = 1;
// 番組表横の時間表示に色付け
ASSIST_INI.index_timebar_color = 1;
// 番組表内の放送済みの番組を色分け
ASSIST_INI.index_passed_prg = 1;
// 番組表、番組情報表示形式変更
ASSIST_INI.index_prg_info = 1;
// 番組表、上部メニュー一覧表示形式変更
ASSIST_INI.index_new_top = 1;
// 番組表、現在時刻を示す赤線を自動更新
//	単位は秒、0で更新しない
ASSIST_INI.index_nowbar_live = 10;

// 番組検索で有効にする
ASSIST_INI.search = 1;

// キーワードで有効にする
ASSIST_INI.keyword = 1;

// 録画予約一覧で有効にする
ASSIST_INI.reserved = 1;

// 録画済み一覧で有効にする
ASSIST_INI.recorded = 1;
// 録画済み一覧、データ削除時ファイルも削除
ASSIST_INI.recorded_delete_with_file = 1;

// 予約、録画済み一覧で番組選択後、選択された番組のみ表示
ASSIST_INI.selv = 0;

// 環境設定で有効にする
ASSIST_INI.envsetting = 1;

// ディスク残量を表示（ajax ver）
ASSIST_INI.ext_diskinfo = 1;
// 残量表示警告色、切り替え割合（％）
// HDDの容量に応じて変更してください
ASSIST_INI.ext_diskinfo_levels = [50,70,80,90];
// 残量表示に予約領域も表示
ASSIST_INI.ext_diskinfo_rsvBar = 1;
// 予約領域計算用ビットレート
ASSIST_INI.ext_diskinfo_rsvBar_bitrates = {GR:16.85 , BS:26.1, CS:26.1};

$(function(){
	if (!ASSIST_INI.use_this ) {return;}
	var reg = /epgrec\/([a-zA-Z/]+)[?]*/, php = document.URL.match(reg);
	if ( !php ) {
		php = 'index';
	} else {
		php = php[1].toLowerCase().replace('/', '_');
	}
	ER_sub.INI();
	switch(php) {
	case 'index' :
	case 'index_index' :
		if ( ASSIST_INI.index ) {ER_sub.INDEX.ini();}
		break;
	case 'search' :
	case 'search_index' :
		ER_sub.__PAGE = 'programtable';
		if ( ASSIST_INI.search ) {ER_sub.PROGRAMTBL.ini();}
		break;
	case 'search_keyword' :
		ER_sub.__PAGE = 'keywordtable';
		if ( ASSIST_INI.keyword ) {ER_sub.KEYWORD.ini();}
		break;
	case 'recprog' :
	case 'recprog_index' :
		ER_sub.__PAGE = 'reservationtable';
		if ( ASSIST_INI.reserved ) {ER_sub.RESERVED.ini();}
		break;
	case 'recprog_recorded' :
		ER_sub.__PAGE = 'recordedtable';
		if ( ASSIST_INI.recorded ) {ER_sub.RECORDED.ini();}
		break;
	case 'setting' :
	case 'setting_index' :
	case 'setting_system' :
	case 'setting_viewlog' :
		ER_sub.__PAGE = 'envsetting';
		if ( ASSIST_INI.envsetting ) {ER_sub.SETTING.ini();}
		break;
	}
});
