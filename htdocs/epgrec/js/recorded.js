var PRG = {
	dialog:function(id, title){
		$('#floatBox4Dialog').dialog({title:'削除',width:500});
		var str = '<div class="prg_title">' + title + 'を削除します</div>';
		str += '<form><div style="text-align:center;">録画ファイルも削除する<input type="checkbox" id="delete_file" name="delete_file" value="1" /></div></form>';
		str +='<div style="margin:2em 0 1em 0;text-align:center;"><a href="javascript:PRG.rec(' + id + ')" class="ui-state-default ui-corner-all ui-dialog-buttonpane button">この録画を本当に削除する</a></div>';
		$('#floatBox4Dialog').html(str);
		$('#floatBox4Dialog').dialog('open', 'center');
	},
	rec:function(id){
		var df = 0;
		if( $('#delete_file').attr('checked') ) {
			df = 1;
		}
		$('#floatBox4Dialog').dialog('close');
		$('#floatBox4Dialog').html('削除中.......');
		$('#floatBox4Dialog').dialog('open', 'center');
		$.get(INISet.prgCancelURL, { reserve_id: id, delete_file: df } ,function(data){
			if(data.match(/^error/i)){
				$('#floatBox4Dialog').dialog('close');
				alert(data);
			}
			else {
//					alert(data);
				$('#resid_' + id ).hide();
				$('#floatBox4Dialog').dialog('close');
			}
		});
	},
	editdialog:function(id) {
		$('#floatBox4Dialog').dialog({title:'変更',width:500});
		var str;
		str  = '<div class="prg_title">録画ID:' + id + '</div>';
		str += '<input type="hidden" name="reserve_id" id="id_reserve_id" value="' + id +  '" />';
		str += '<div><span class="labelLeft">タイトル</span><input name="title" id="id_title" size="30"  value="'+ $('#tid_' + id ).html() + '" /></div>';
		str += '<div><span class="labelLeft">概要</span><textarea name="description" id="id_description" cols="30" rows="5" >' + $('#did_' + id ).html() + '</textarea></div>';
		str += '<div style="margin:2em 0 1em 0;text-align:center;"><a href="javascript:PRG.edit()" class="ui-state-default ui-corner-all ui-dialog-buttonpane button">変更する</a></div>';
		
		$('#floatBox4Dialog').html(str);
		$('#floatBox4Dialog').dialog('open','center');
	},
	edit:function() {
		var id_reserve_id = $('#id_reserve_id').val();
		var id_title = $('#id_title').val();
		var id_description = $('#id_description').val();

		$.post(INISet.prgChangeURL, { reserve_id: id_reserve_id,
						  title: id_title,
						  description: id_description }, function( data ) {
			if(data.match(/^error/i)){
				alert(data);
				$('#floatBox4Dialog').dialog('close');

			}
			else {
				$('#tid_' + id_reserve_id ).html( id_title );
				$('#did_' + id_reserve_id ).html( id_description );
				$('#floatBox4Dialog').dialog('close');
			}
		});
	}
};
$(document).ready(function () {
	var DG = $('#floatBox4Dialog');
	DG.dialog({title:'変更',width:500});
	DG.dialog('close');
});
