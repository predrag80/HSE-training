(() => {
	if (!window.wp?.media || !window.hseCompanyAdmin) return;

	document.querySelectorAll('[data-hse-media-field]').forEach((field) => {
		const imageId = field.querySelector('[data-hse-image-id]');
		const preview = field.querySelector('[data-hse-image-preview]');
		const selectButton = field.querySelector('[data-hse-select-image]');
		const removeButton = field.querySelector('[data-hse-remove-image]');
		if (!imageId || !preview || !selectButton || !removeButton) return;

		let frame;
		selectButton.addEventListener('click', () => {
			if (!frame) {
				frame = window.wp.media({
					title: window.hseCompanyAdmin.dialogTitle,
					button: { text: window.hseCompanyAdmin.buttonLabel },
					library: { type: 'image' },
					multiple: false,
				});
				frame.on('select', () => {
					const attachment = frame.state().get('selection').first().toJSON();
					imageId.value = String(attachment.id);
					preview.replaceChildren(Object.assign(document.createElement('img'), {
						alt: '',
						src: attachment.sizes?.medium?.url ?? attachment.url,
					}));
					removeButton.hidden = false;
				});
			}
			frame.open();
		});

		removeButton.addEventListener('click', () => {
			imageId.value = '';
			preview.replaceChildren();
			removeButton.hidden = true;
		});
	});
})();
