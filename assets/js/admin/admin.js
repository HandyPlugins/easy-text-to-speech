/* global jQuery */
import '../../css/admin/admin-style.css';
import '@wpmudev/shared-ui/dist/js/_src/modal-dialog';
import {noticeTemplate} from './utils';

(function ($) {

	$('input[name="tts_provider"]').on('change', function () {
		$('.tts-provider-settings').hide();
		$('#' + $(this).val() + '-details').show();
	});

	$('#aws_polly_region').on('change',function(e){
		e.preventDefault();
		populate_voice_selection();
	});

	$('.aws_polly_engine').on('change',function(e){
		e.preventDefault();
		populate_voice_selection();
	});

	function populate_voice_selection(){
		const $errContainer = $('#aws_polly_default_voice_desc');

		$.post(
			ajaxurl,
			{
				beforeSend() {
					jQuery('#aws_polly_default_voice').attr('disabled', 'disabled');
					$errContainer.html('');
				},
				action: 'easytts_voice_list',
				nonce : EasyTTSAdmin.nonce,
				data: $('#easytts_settings_form').serialize()
			},
			function (response) {
				if(response.success){
					$('#aws_polly_default_voice').html(response.data.html);
				}else{
					const err = noticeTemplate(response.data.message, 'error');
					$errContainer.html(err);
				}
			},
		).done(function () {
			jQuery('#aws_polly_default_voice').attr('disabled', false);
		});
	}



})(jQuery);
