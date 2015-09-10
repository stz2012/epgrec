var PRG = {
	rec:function(id){
		$.get(INISet.prgCancelURL, { reserve_id: id } ,function(data){
			$('#eraseDialog').html('キャンセル中......');
			$('#eraseDialog').dialog('open','center');
			if(data.match(/^error/i)){
				$('#eraseDialog').dialog('close');
				alert(data);
			}
			else {
				$('#resid_' + id ).hide();
				$('#eraseDialog').dialog('close');
			}
		});
	},
	editdialog:function(id) {
		var str;
		str  = '<div class="prg_title">予約ID:' + id + '</div>';
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
	DG.dialog({title:'予約編集', width:500});
	DG.dialog('close');

	var EG = $('#eraseDialog');
	EG.dialog({title:'キャンセル', width:400});
	EG.dialog('close');
});
