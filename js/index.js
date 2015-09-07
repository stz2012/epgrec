function tvtimes_scroll(){
	var t2max = $('#tvtimes2').position().left;
	var ftmin = $('#float_titles').position().top;
	tvtimes2scrl();
	$(window).scroll(function () {
		$('#tvtimes').css('left', parseInt($(document ).scrollLeft())); 
		var newTop = parseInt($(document ).scrollTop());
		if(newTop < ftmin) {newTop = ftmin;}
		$('#float_titles').css('top', newTop);
		tvtimes2scrl();
		$('#float_follows').css('left', parseInt($(document ).scrollLeft()));
	});
	$(window).resize(function () {  tvtimes2scrl();});
	function tvtimes2scrl(){
		var inwidth = parseInt($('body').innerWidth());
		// IE6
		if ($.browser.msie && $.browser.version == 6){ inwidth = document.documentElement.clientWidth;}
		var newLeft = inwidth - parseInt($('#tvtimes2').width()) + parseInt($( document ).scrollLeft());
		if(newLeft > t2max ) {newLeft = t2max}
		$('#tvtimes2').css('left', newLeft);
		$('#float_follows').width(inwidth);
	}
}

function prg_hover(){
	function aClick(){
		var TG = $(this).children('.prg_dummy');
		var startTime = new Date(TG.children('.prg_start').html());
		var duration = parseInt(TG.children('.prg_duration').html());
		var endTime = new Date(startTime.getTime() + duration * 1000);
		var prgID = parseInt(TG.children('.prg_id').html());

		var str = '<div class="prg_title">' + TG.children('.prg_title').html() +'</div>' + 
			'<div class="prg_rec_cfg ui-corner-all"><div class="prg_channel"><span class=" labelLeft">チャンネル：</span><span class="bold">' + TG.children('.prg_channel').html() + '</span></div>' +
			'<div class="prg_startTime" style="clear: left"><span class=" labelLeft">日時：</span>' + MDA.Days.time4Disp(startTime) + ' ～ ' + MDA.Days.time4DispH(endTime) + '</div>' +
			'<div class="prg_duration" style="clear: left"><span class=" labelLeft">録画時間：</span><span class="bold">' + parseInt(duration / 60) +'</span>分' + ((duration % 60)>0?'<span class="bold">' + parseInt(duration % 60) + '</span>秒':'') + '</div>' +
			'</div>';
		if ($(this).hasClass('prg_rec')) {
			str += '<div style="margin:2em 0 1em 0;text-align:center;"><a href="javascript:PRG.cancel(' + prgID + ')" class="ui-state-default ui-corner-all ui-dialog-buttonpane button">予約キャンセル</a></div>';
		} else {
			str += '<div style="margin:2em 0 1em 0;text-align:center;"><a href="javascript:PRG.rec(' + prgID + ')" class="ui-state-default ui-corner-all ui-dialog-buttonpane button">簡易予約</a>　<a href="javascript:PRG.customform(' + prgID + ')" class="ui-state-default ui-corner-all ui-dialog-buttonpane button">予約カスタマイズ</a></div>';
		}
		$('#floatBox4Dialog').html(str);
		$('#floatBox4Dialog').dialog('open', 'center');
	};
	$('.prg').hover(
		function(){
			$('#tv_chs .prg_hover').removeClass('prg_hover');
			if($(this).hasClass('prg_none')) return ;
			$(this).addClass('prg_hover');
			var TG = $(this).children('.prg_dummy');
			var startTime = new Date(TG.children('.prg_start').html());
			var duration = parseInt(TG.children('.prg_duration').html());
			var endTime = new Date(startTime.getTime() + duration * 1000);
			var str = '<div class="prg_title">' + TG.children('.prg_title').html() + '</div>' +
				'<div class="prg_desc"><span class="prg_sub">' + TG.children('.prg_channel').html() + '：' + MDA.Days.time4Disp(startTime) + '～' + MDA.Days.time4DispH(endTime)  + ' </span>' + TG.children('.prg_desc').html() + '</div>';
			$('#prg_info').html('<div class="prg_dummy">' + str + '</div>').show();
			$(this).click(aClick);
		},
		function(){
			$(this).removeClass('prg_hover');$('#prg_info').hide();
			$(this).unbind('click',aClick);
		}
	);
}

var PRG = {
	chdialog:function(chash){
		$('#channelDialog').dialog('close');
		var skip = $('#ch_title_'+chash+' .ch_skip').html();
		var st_name = $('#ch_title_'+chash+' .ch_name').html();
		var sid = $('#ch_title_'+chash+' .ch_sid').html();
		var disc = $('#ch_title_'+chash+' .ch_disc').html();

		var str = '<div class="prg_title">';
		str += st_name;
		str += '</div>';
		str += '<form method="post" action="'+INISet.prgSetChannelURL+'">';
		// スキップ
		str += '<div class="prg_channel"><span class="labelLeft">視聴しない：</span>';
		str += '<span>';
		if( skip == 1 ) {
			str += '<input type="checkbox" name="n_skip_name" id="id_ch_skip" value="'+skip+'" checked />';
		}
		else {
			str += '<input type="checkbox" name="n_skip_name" id="id_ch_skip" value="'+skip+'" />';
		}
		str += '</span></div>';
		// サービスID
		str += '<div class="prg_channel"><span class="labelLeft">サービスID：</span>';
		str += '<span><input type="text" name="n_sid" size="20" id="id_sid" value="';
		str += sid;
		str += '" /></span></div>';

		str += '<input type="hidden" name="n_channel_disc" id="id_disc" value="';
		str += disc;
		str += '" />';
		str += '<input type="hidden" name="n_channel_hash" id="id_hash" value="';
		str += chash;
		str += '" />';


		str += '</form>';
		
		str += '<div style="margin:2em 0 1em 0;text-align:center;"><a href="javascript:PRG.chupdate()" class="ui-state-default ui-corner-all ui-dialog-buttonpane button">更新</a></div>';
		
		$('#channelDialog').html(str);
		$('#channelDialog').dialog('open', 'center');
	},

	chupdate:function() {
		var v_sid = $('#id_sid').val();
		var v_channel_disc = $('#id_disc').val();
		var v_hash = $('#id_hash').val();
		var v_skip = $('#id_ch_skip').attr('checked');
		var n_skip = v_skip ? 1 : 0;

		$.post(INISet.prgSetChannelURL, { channel_disc: v_channel_disc,
					      sid: v_sid,
					      skip: n_skip
					    }, function(data) {
			if(data.match(/^error/i)){
				alert(data);
			}
			else {
				var old_skip = $('#ch_title_'+v_hash+' .ch_skip').html();
				if( old_skip != n_skip ) {
					if( v_skip ) {
						(INISet.num_ch)--;
						$('#ch_title_'+v_hash ).addClass('ch_title_skip');
						$('#tv_chs_'+v_hash ).addClass('ch_set_skip');
						$('#ch_title_str_'+v_hash ).addClass('ch_skip_color');
						$('#ch_title_'+v_hash+' .ch_skip').html('1');
					}
					else {
						(INISet.num_ch)++;
						$('#ch_title_'+v_hash ).removeClass('ch_title_skip');
						$('#tv_chs_'+v_hash ).removeClass('ch_set_skip');
						$('#ch_title_str_'+v_hash ).removeClass('ch_skip_color');
						$('#ch_title_'+v_hash+' .ch_skip').html('0');
					}
					if( PRG.F_Skip == 1 ) {
						PRG.chSkipHide();
					}
					else {
						$('#ch_title_bar div.ch_title_skip').show();
						$('#tv_chs div.ch_set_skip').show();
					}
				}
			}
			$('#channelDialog').dialog('close');
		});
	},

	rec:function(id){
		$.get(INISet.prgRecordURL, { program_id: id } ,function(data){
			if(data.match(/^error/i)){
				alert(data);
				$('#floatBox4Dialog').dialog('close');
			}else{
				$('#prgID_' + id).addClass('prg_rec');
				$('#floatBox4Dialog').dialog('close');
			}
		});
	},

	cancel:function(id){
		$('#floatBox4Dialog').html('予約取り消し中....');
		$.get(INISet.prgCancelURL, { program_id: id } ,function(data){
			if(data.match(/^error/i)){
				alert(data);
				$('#floatBox4Dialog').dialog('close');
			}else{
				$('#prgID_' + id).removeClass('prg_rec');
				$('#floatBox4Dialog').dialog('close');
			}
		});
	},

	customform:function(id) {
		$('#floatBox4Dialog').dialog('close');
		$.get(INISet.prgReservFormURL, { program_id: id }, function(data) {
			if(data.match(/^error/i)){
				alert(data);
			}
			else {
				var str = data;
				str += '<div style="margin:2em 0 1em 0;text-align:center;"><a href="javascript:PRG.customrec()" class="ui-state-default ui-corner-all ui-dialog-buttonpane button">予約する</a></div>';
				$('#floatBox4Dialog').html(str);
				$('#floatBox4Dialog').dialog('open', 'center');
			}
		});
	},

	customrec:function() {
		var id_syear = $('#id_syear').val();
		var id_smonth = $('#id_smonth').val();
		var id_sday = $('#id_sday').val();
		var id_shour = $('#id_shour').val();
		var id_smin = $('#id_smin').val();
		var id_eyear = $('#id_eyear').val();
		var id_emonth = $('#id_emonth').val();
		var id_eday = $('#id_eday').val();
		var id_ehour = $('#id_ehour').val();
		var id_emin = $('#id_emin').val();
		var id_channel_id = $('#id_channel_id').val();
		var id_record_mode = $('#id_record_mode').val();
		var id_title = $('#id_title').val();
		var id_description = $('#id_description').val();
		var id_category_id = $('#id_category_id ').val();
		var id_program_id = $('#id_program_id').val();
		var with_program_id = $('#id_program_id').attr('checked');
		
		if( ! with_program_id ) id_program_id = 0;
		
		$.post(INISet.prgRecordPlusURL, { syear: id_syear,
						  smonth: id_smonth,
						  sday: id_sday,
						  shour: id_shour,
						  smin: id_smin,
						  eyear: id_eyear,
						  emonth: id_emonth,
						  eday: id_eday,
						  ehour: id_ehour,
						  emin: id_emin,
						  channel_id: id_channel_id,
						  record_mode: id_record_mode,
						  title: id_title,
						  description: id_description,
						  category_id: id_category_id,
						  program_id: id_program_id }, function(data) {
			if(data.match(/^error/i)){
				$('#floatBox4Dialog').dialog('close');
				alert(data);
			}
			else {
				var id = parseInt(data);
				if( id ) {
					$('#prgID_' + id).addClass('prg_rec');
				}
				$('#floatBox4Dialog').dialog('close');
			}
		});
	},

	F_Skip: 1,

	chSkipShow:function() {
		$('#float_titles').width(INISet.num_all_ch * INISet.ch_width + 80);
		$('#tv_chs').width(INISet.num_all_ch * INISet.ch_width );
		$('#tvtimes2').css( { left: INISet.num_all_ch * INISet.ch_width  + 40 } ).show();
		tvtimes_scroll();
		$('#ch_title_bar div.ch_title_skip').show();
		$('#tv_chs div.ch_set_skip').show();
		nowBar.INI();
	},

	chSkipHide:function() {
		$('#ch_title_bar div.ch_title_skip').hide();
		$('#tv_chs div.ch_set_skip').hide();
		$('#float_titles').width( INISet.num_ch * INISet.ch_width + 80 );
		$('#tv_chs').width( INISet.num_ch * INISet.ch_width );
		$('#tvtimes2').css( { left: INISet.num_ch * INISet.ch_width  + 40 }).show();
		tvtimes_scroll();
		nowBar.INI();
	},

	toggle:function() {
		if( this.F_Skip ) {
			this.chSkipShow();
			this.F_Skip = 0;
		}
		else {
			this.chSkipHide();
			this.F_Skip = 1;
		}
	}
};

var CTG = {
	CN:'ctg',
	CV:'0.1',
	defaultCk:[],
	INI:function(){
		var Ck = this.CkGet()[1];
		if(Ck){ $.each(Ck.split(','), function(){CTG.select(this);})}
	},
	select:function(ctg){
		if($('#category_select .ctg-hide.ctg_'+ctg).length){
			$('#tv_chs .ctg_'+ctg).removeClass('ctg-hide');
			$('#category_select a.ctg_'+ctg).removeClass('ctg-hide');
		} else {
			$('#tv_chs .ctg_'+ctg).addClass('ctg-hide');
			$('#category_select a.ctg_'+ctg).addClass('ctg-hide');
		}
		this.oCk();
	},
	toggle:function (){$('#category_select ul').toggle();},
	oCk:function(){
		var T=$('#category_select ul li a.ctg-hide');
		var X=[];
		$.each(T.get(), function(){
			$(this).attr('class').match(/ctg_([^ ]+)/);
			var TMC=RegExp.$1;
			X.push(TMC);
		});
		this.CkSet([X.join(',')]);
	},
	CkGet:function (){
		var Ck = MDA.Cookie.get(this.CN);
		if(!Ck){return this.defaultCk};
		 Ck=Ck.replace(/^([^;]+;)/,'');
		return Ck.split('+');
	},
	CkSet:function(V){
		MDA.Cookie.set(this.CN,'ver='+this.CV+'+'+V.join('+'));
	}
};

var nowBar = {
	defaultID:'tableNowBas',
	startTime:null,
	endTime:null,
	INI:function(){
		if (INISet.tableStartTime && INISet.tableStartTime && INISet.dotMin) {
			$('#tvtable').append('<div id="' + this.defaultID + '" style="display:none">now</div>');
			this.startTime = new Date(INISet.tableStartTime);
			this.endTime = new Date(INISet.tableEndTime);
			$('#' + this.defaultID).width($('#float_titles').width());
			this.ch();
		}
	},
	ch:function(){
		var now = new Date();
		if(this.startTime){
			if((now >= this.startTime) && (this.endTime >= now)){
//					console.log((now - this.startTime) / 60000);
				$('#' + this.defaultID).css({top:(now - this.startTime) / 60000 * INISet.dotMin}).show()
			} else {
				$('#' + this.defaultID).hide()
			}
		}
	}
};

MDA.SCR = {
	CN:'scr',
	CV:'0.1',
	defaultCk:{md:'',x:0,y:0},
	jqSel:[{sel:'#jump-time a.jump',md:'x'},{sel:'#jump-day a.jump',md:'xy'},{sel:'#jump-day a.jump-today',md:'x'},{sel:'#jump-broadcast a.jump',md:'y'}],
	INI:function(){
//			this.defaultCk.y = $('#float_titles').position().top;
		$.each(this.jqSel, function(){
			var md = this.md;
			$(this.sel).click(function(){MDA.SCR.oCk(md)})
		});
		var Ck = this.CkGet();
//			console.log(Ck);
		var x = (Ck.md.indexOf('x')>-1)?Ck.x:this.defaultCk.x;
		var y = (Ck.md.indexOf('y')>-1)?Ck.y:this.defaultCk.y;
		if (Ck.md) {
			window.scrollBy(x, y);
		}
		this.CkClear();
	},
	channel:{
		save:function(){},
		load:function(){}
	},
	time: {
	},
	oCk:function(xy){
		this.CkSet(['md=' + ((!xy)?'xy':xy),
			'x=' + $(document ).scrollLeft(),
			'y=' + $(document ).scrollTop()]);
	},
	CkGet:function (){
		var Ck = MDA.Cookie.get(this.CN);
		if(!Ck){return this.defaultCk};
		Ck=Ck.replace(/^([^;]+;)/,'').split('+');
		var ret = {};
		$.each(Ck, function(){
			var str = this.split('=', 2);
			ret[str[0]] = str[1];
		})
		return ret;
	},
	CkSet:function(V){
		MDA.Cookie.set(this.CN,'ver='+this.CV+'+'+V.join('+'));
	},
	CkClear:function(){
		MDA.Cookie.del(this.CN);
	}
};

$(document).ready(function () {
	$('div.tvtime').css('height', INISet.dotHour+'px');
	$('#tvtable div.ch_set').css('width', INISet.ch_width+'px');
	$('#float_titles div.ch_title').css('width', INISet.ch_width+'px');
	
	MDA.Cookie.CookieName = 'tvProgmas_';
	CTG.toggle();
	tvtimes_scroll();
	prg_hover();
	var DG = $('#floatBox4Dialog');
	DG.dialog({title:'録画予約',width:600});
	DG.dialog('close');

	var DG2 = $('#channelDialog');
	DG2.dialog({title:'チャンネル情報',width:480});
	DG2.dialog('close');

//		PRG.toggle();

	nowBar.INI();
	CTG.INI();
	MDA.SCR.INI();	// 番組表の位置保存
});
