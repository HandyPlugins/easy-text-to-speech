/**
 * Utils
 */
import jQuery from 'jquery'; // eslint-disable-line import/no-unresolved
/*
 * Return delay speed of Typewriter
 *
 * @param int strLen String length
 * @returns {number}
 */
export const getTypewriterSpeed = (strLen) => {
	if (strLen > 500) {
		return 0;
	}

	if (strLen > 200) {
		return 5;
	}

	if (strLen > 100) {
		return 20;
	}

	return 30;
};

export const isTinyMCEActive = (editor) => {
	if (jQuery('#wp-' + editor + '-wrap').hasClass('tmce-active')) {
		return true;
	}

	return false;
};

/**
 * unslashit
 *
 * @param {string} str String to unslash
 *
 * @returns {string} unslashed string
 */
export const unslash = (str) => {
	return str.replace(/\\/g, '');
};


export const getTrimmedText = (str) => {
	return str.replace(/<[^>]*>?/gm, '').replace(/[ ]+/g, ' ');
}

export const noticeTemplate = (message, type = 'error') => {
	return `<div class="sui-notice sui-notice-${type}">
							<div class="sui-notice-content">
								<div class="sui-notice-message">
									<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
									<p>${message}</p>
								</div>
							</div>
						</div>`;
}


export const getTinymceContent = (editor_id, textarea_id) => {
	if (typeof editor_id == 'undefined'){
		editor_id = wpActiveEditor;
	}

	if (typeof textarea_id == 'undefined') {
		textarea_id = editor_id;
	}

	if (jQuery('#wp-' + editor_id + '-wrap').hasClass('tmce-active') && tinyMCE.get(editor_id)) {
		const currentSelection = tinyMCE.get(editor_id).selection.getContent({format: 'text'}); // selected content in the editor
		if (currentSelection) {
			return currentSelection.trim();
		}
		const postTitle = jQuery('#title').val();
		const postContent = tinyMCE.get(editor_id).getContent({format: 'text'});
		const content = postTitle + '\n\n' + postContent;
		return content;
	} else {
		const selectedText = getSelectedText(jQuery('#' + textarea_id));
		if (selectedText) {
			return getTrimmedText(selectedText)
		}

		return getTrimmedText(jQuery('#' + textarea_id).val());
	}
}

export const getSelectedText = (textarea) =>  {
	const start = textarea.prop('selectionStart');
	const finish = textarea.prop('selectionEnd');
	const value = textarea.val();
	if (value && start !== undefined && finish !== undefined) {
		return value.substring(start, finish);
	}
	return '';
}

export const isBlockEditor = () =>  {
	if( EasyTTSEditor.isBlockEditor){
		return EasyTTSEditor.isBlockEditor;
	}
	return false;
}

