var PRG = {
	rec: function(id){
		$.get(INISet.prgRecordURL, { program_id: id } ,function(data){
			if(data.match(/^error/i)){
				alert(data);
			}else{
				$('#resid_' + id).addClass('prg_rec');
			}
		});
	},

	customform: function(id) {
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

	customrec: function() {
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
		
		$.post(INISet.prgRecordPlusURL, {
			syear: id_syear,
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
			program_id: id_program_id
		}, function(data) {
			if(data.match(/^error/i)){
				$('#floatBox4Dialog').dialog('close');
				alert(data);
			}
			else {
				var id = parseInt(data);
				if( id ) {
					$('#resid_' + id).addClass('prg_rec');
				}
				$('#floatBox4Dialog').dialog('close');
			}
		});
	},

	delkey:function(id){
		$.get(INISet.prgDelKeyURL, { keyword_id: id } ,function(data){
			if(data.match(/^error/i)){
				alert(data);
			}else{
				$('#keyid_' + id).hide();
			}
		});
	}
};
$(document).ready(function () {
	var DG = $('#floatBox4Dialog');
	DG.dialog({title:'録画予約', width:600});
	DG.dialog('close');
});
