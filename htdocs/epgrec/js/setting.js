var PRG = {
	force_cont:function() {
		if( $('#id_force_cont_rec' ).val() == 0 ) {
			$('#id_rec_switch_time').attr('disabled','disabled');
		}
		else {
			$('#id_rec_switch_time').attr('disabled',false);
		}
	},
	drivers:function() {
		if( $('#id_db_type' ).val() == 'sqlite' ) {
			$('#id_db_host').attr('disabled','disabled');
			$('#id_db_port').attr('disabled','disabled');
			$('#id_db_user').attr('disabled','disabled');
			$('#id_db_pass').attr('disabled','disabled');
			$('#id_db_name').attr('disabled','disabled');
		}
		else {
			$('#id_db_host').attr('disabled',false);
			$('#id_db_port').attr('disabled',false);
			$('#id_db_user').attr('disabled',false);
			$('#id_db_pass').attr('disabled',false);
			$('#id_db_name').attr('disabled',false);
		}
	},
	thumbs:function() {
		if( $('#id_use_thumbs' ).val() == 0 ) {
			$('#id_ffmpeg').attr('disabled','disabled');
			$('#id_thumbs').attr('disabled','disabled');
		}
		else {
			$('#id_ffmpeg').attr('disabled',false);
			$('#id_thumbs').attr('disabled',false);
		}
	},
	power_reduce:function() {
		if( $('#id_use_power_reduce').val() == 0 ) {
			$('#id_getepg_timer').attr('disabled','disabled');
			$('#id_wakeup_before').attr('disabled','disabled');
		}
		else {
			$('#id_getepg_timer').attr('disabled',false);
			$('#id_wakeup_before').attr('disabled',false);
		}
	}
};
