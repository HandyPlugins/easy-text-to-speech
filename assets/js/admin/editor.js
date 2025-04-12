/**
 * Editor stuff
 */
/* eslint-disable react/destructuring-assignment, @wordpress/no-global-get-selection */
import jQuery from 'jquery'; // eslint-disable-line import/no-unresolved
import '@wpmudev/shared-ui/dist/js/_src/modal-dialog';
import {
	getTrimmedText,
	isTinyMCEActive,
	noticeTemplate,
	getTinymceContent,
	getSelectedText,
	isBlockEditor
} from './utils';

const {__} = wp.i18n;
const supportedBlocks = ['core/paragraph', 'core/heading']; // toolbar items only available for these blocks
const {createHigherOrderComponent} = wp.compose;
const {Fragment, useState} = wp.element;
const {BlockControls} = wp.blockEditor;
const {ToolbarGroup, ToolbarButton, Icon} = wp.components;


/**
 * Add Custom Button to Paragraph Toolbar
 */
const withToolbarButton = createHigherOrderComponent((BlockEdit) => {
	return (props) => {
		// If current block is not allowed
		if (!supportedBlocks.includes(props.name)) {
			return <BlockEdit {...props} />;
		}

		const {setAttributes} = props;
		// toolbar icon - dashicon
		const toolbarIcon = () => <Icon icon="controls-volumeon" />;

		// set the selected content in the editor if any or use entire content
		const selectedBlockContent = () => {
			let selectedText = window.getSelection().toString();
			if (!selectedText) {
				selectedText = jQuery('.wp-block-post-content').text();
				let blocks = wp.data.select('core/block-editor').getBlocks();
				let postContent = '';
				if (blocks.length > 0) {
					blocks.forEach(function (block) {
						if (supportedBlocks.includes(block.name)) {
							postContent += block.attributes.content + '\n\n';
						}
					});

					selectedText = postContent;
				}

				// Get the post title
				const postTitle = wp.data.select('core/editor').getEditedPostAttribute('title');

				// Append the post title to the selected text
				selectedText = postTitle + '\n\n' + selectedText;
			}

			return selectedText;
		};


		// open shared-ui modal and populate the text content
		const openTTSModal = () => {
			let voiceContent = selectedBlockContent();
			let VoiceContentTextarea = jQuery('#easytts-content');
			jQuery('#generate_voice_result_msg').html(''); // reset feedback message
			VoiceContentTextarea.val(''); // reset text area
			voiceContent = VoiceContentTextarea.html(voiceContent).text(); // for entity encoding eg: &nbsp
			voiceContent = voiceContent.replace( /(<([^>]+)>)/ig, ''); // strip html tags
			VoiceContentTextarea.val(voiceContent);

			window.SUI.openModal(
				'easytts-modal',
				'wpbody-content',
				undefined,
				true
			);
		}


		return (
			<Fragment>
				<BlockControls group="block">
					<ToolbarGroup>
						<ToolbarButton
							icon={toolbarIcon()}
							label={__('Text-to-Speech', 'easy-text-to-speech')}
							showTooltip="true"
							onClick={openTTSModal}
						/>
					</ToolbarGroup>
				</BlockControls>
				<BlockEdit {...props} />
			</Fragment>
		);
	};
}, 'withToolbarButton');

wp.hooks.addFilter('editor.BlockEdit', 'easytts/toolbar-button', withToolbarButton, 99);

(function ($) {
	const getPostTitle = () => {
		if (isBlockEditor()) {
			return wp.data.select('core/editor').getEditedPostAttribute('title');
		}

		return jQuery('#titlewrap').find('input').val();
	}

	$('.aws_polly_engine_for_content').on('change', function (e) {
		e.preventDefault();
		const $errContainer = $('#generate_voice_result_msg');

		$.post(
			ajaxurl,
			{
				beforeSend() {
					jQuery('#aws_polly_voice').attr('disabled', 'disabled');
					$errContainer.html('');
				},
				action: 'easytts_voice_list',
				nonce : $('#content_nonce').val(),
				engine: $(this).val()
			},
			function (response) {
				if (response.success) {
					$('#aws_polly_voice').html(response.data.html);
				}else{
					$('#aws_polly_voice').html('');
					const err = noticeTemplate(response.data.message, 'error');
					$errContainer.html(err);
				}
			},
		).done(function () {
			jQuery('#aws_polly_voice').attr('disabled', false);
		});
	});


	$(document).on('click', '.easytts-classic-editor-btn', function (e) {
		const editorID = $(this).data('editor-id') || 'content';
		let tinyMceActive = isTinyMCEActive(editorID);
		let voiceContent = '';
		if (tinyMceActive) {
			voiceContent = getTinymceContent(editorID);
		} else {
			const selectedText = getSelectedText($('#' + editorID));
			voiceContent = selectedText ? selectedText.trim() : $('#' + editorID).val();
			voiceContent = getTrimmedText(voiceContent);

			if (!selectedText) {
				const postTitle = jQuery('#title').val();
				voiceContent = postTitle + '\n\n' + voiceContent;
			}
		}

		$('#easytts-editor-id').val(editorID);

		$('#easytts-content').text(voiceContent);

		SUI.openModal(
			'easytts-modal',
			this,
			undefined,
			true,
			true,
			false
		);
	});


	$(document).on('click', '#easytts-modal-close', function (e) {
		e.preventDefault();
		jQuery('.wp-toolbar').removeClass('sui-has-modal');
		jQuery('.sui-modal').removeClass('sui-active');
		window.SUI.closeModal();
	});

	$(document).on('easytts-audio-generated', function (e) {
		jQuery('.wp-toolbar').removeClass('sui-has-modal');
		jQuery('.sui-modal').removeClass('sui-active');
		window.SUI.closeModal();
	})

	$(document).on('submit', '#easytts-voice-generator-form', function (e) {
		e.preventDefault();
		const $errContainer = $('#generate_voice_result_msg');
		const $submitBtn = $('#easytts-generate-voice');
		const editorID = $('#easytts-editor-id').val();

		$.post(
			ajaxurl,
			{
				beforeSend() {
					$errContainer.html('');
					$submitBtn.addClass('sui-button-onload-text');
				},
				action: 'easytts_generate_voice',
				nonce : $('#content_nonce').val(),
				data  : $('#easytts-voice-generator-form').serialize(),
				title : getPostTitle()
			},
			function (response) {
				if (response.success) {
					$(document).trigger('easytts-audio-generated');

					if (isBlockEditor()) {
						let name = 'core/audio';
						let audioBlock = wp.blocks.createBlock(name, {
							id : response.data.attachment_id,
							src: response.data.attachment_url,
							caption: $('#easytts_tts_disclosure').val(),
						});
						wp.data.dispatch('core/block-editor').insertBlocks(audioBlock);
						return;
					}

					// fallback to classic editor
					if (wp && wp.media && wp.media.editor) {

						// remove selection to prevent selected content loss while adding audio into the TinyMCE editor
						if(wpActiveEditor && tinyMCE){
							tinyMCE.get(wpActiveEditor).selection.collapse();
						}

						wp.media.editor.activeEditor = editorID;
						let currentEditor = wp.media.editor.get(editorID);
						if (!currentEditor || (currentEditor.options && currentEditor.state !== currentEditor.options.state)) {
							currentEditor = wp.media.editor.add(editorID, {});
						}


						wp.media.frame = currentEditor;
						wp.media.frame.content.mode('browse'); // set browse mode all the time

						wp.media.frame.on('open', function () {
							// refresh and reset selection
							if (wp.media.frame.content.get() !== null) {
								wp.media.frame.content.get().collection._requery(true);
								wp.media.frame.content.get().options.selection.reset();
							}

							let selection = wp.media.frame.state().get('selection');
							let attachment = wp.media.attachment(response.data.attachment_id);
							attachment.set('type', 'audio');
							attachment.set('filename', 'easytts.mp3');
							attachment.set('meta', {
								bitrate     : 48000,
								bitrate_mode: 'cbr',
							});
							selection.multiple = false;
							selection.add(attachment);
						}, this);
						wp.media.frame.open();
					}

				} else {
					const err = noticeTemplate(response.data.message, 'error');
					$errContainer.html(err)
				}
			},
		).done(function () {
			$submitBtn.removeClass('sui-button-onload-text');
		});
	});

})(jQuery);
