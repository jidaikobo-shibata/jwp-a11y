(function (wp) {
	if (!wp || !wp.data || !wp.apiFetch) {
		return;
	}

	var wasSavingPost = false;
	var notices = wp.data.dispatch('core/notices');
	var editorStore = 'core/editor';
	var noticeIds = [
		'jwp-a11y-editor-error',
		'jwp-a11y-editor-warning',
		'jwp-a11y-editor-success'
	];

	function clearNotices() {
		noticeIds.forEach(function (noticeId) {
			notices.removeNotice(noticeId);
		});
	}

	function showResult(result) {
		clearNotices();

		if (!result || !result.html) {
			return;
		}

		if (result.has_errors) {
			notices.createNotice('error', result.html, {
				id: noticeIds[0],
				isDismissible: true,
				__unstableHTML: true
			});
			return;
		}

		if (result.has_notices) {
			notices.createNotice('warning', result.html, {
				id: noticeIds[1],
				isDismissible: true,
				__unstableHTML: true
			});
			return;
		}

		if (result.has_success) {
			notices.createNotice('success', result.html, {
				id: noticeIds[2],
				isDismissible: true,
				__unstableHTML: true
			});
		}
	}

	function fetchResult(postId) {
		if (!postId) {
			return;
		}

		wp.apiFetch({
			path: '/jwp-a11y/v1/post-check-result/' + postId
		}).then(showResult).catch(function () {
			// Keep the editor usable even if the notice fetch fails.
		});
	}

	wp.data.subscribe(function () {
		var editor = wp.data.select(editorStore);

		if (!editor || !editor.getCurrentPostId) {
			return;
		}

		var isAutosaving = editor.isAutosavingPost && editor.isAutosavingPost();
		var isSaving = editor.isSavingPost && editor.isSavingPost();

		if (isSaving && !isAutosaving) {
			wasSavingPost = true;
			return;
		}

		if (!wasSavingPost || isSaving) {
			return;
		}

		wasSavingPost = false;
		fetchResult(editor.getCurrentPostId());
	});
})(window.wp);
