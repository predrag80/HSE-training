(() => {
	'use strict';

	const refundBuyerSelector = '[name="bokapos_refund_buyer_value"]';

	const isVisible = (field) => {
		if (field.hidden || field.disabled || field.closest('[hidden], .hidden, [aria-hidden="true"]')) {
			return false;
		}

		const styles = window.getComputedStyle(field);
		return styles.display !== 'none' && styles.visibility !== 'hidden' && field.getClientRects().length > 0;
	};

	const syncField = (field) => {
		if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) {
			return;
		}

		if (!field.hasAttribute('data-hse-bokapos-original-required')) {
			field.setAttribute('data-hse-bokapos-original-required', field.required ? '1' : '0');
		}

		const shouldBeRequired = field.getAttribute('data-hse-bokapos-original-required') === '1' && isVisible(field);
		if (field.required !== shouldBeRequired) {
			field.required = shouldBeRequired;
		}
	};

	const syncRefundFields = () => {
		document.querySelectorAll(refundBuyerSelector).forEach(syncField);
	};

	const hasRefundQuantity = () => Array.from(document.querySelectorAll('.refund input.refund_order_item_qty'))
		.some((field) => Number.parseFloat(field.value || '0') > 0);

	const guardFiscalRefund = (event) => {
		const button = event.target instanceof Element
			? event.target.closest('button.do-api-refund, button.do-manual-refund')
			: null;
		if (!button) return;

		const fiscalize = document.querySelector('input[name="bokapos_refund_fiscalize"]:checked');
		if (!fiscalize || hasRefundQuantity()) return;

		event.preventDefault();
		event.stopImmediatePropagation();
		const message = window.hseCommerceAdmin?.refundLinesRequired
			|| 'Select the refunded item quantity before continuing. BokaPOS cannot fiscalize an amount-only refund.';
		window.alert(message);
		document.querySelector('.refund input.refund_order_item_qty')?.focus();
	};

	const initialize = () => {
		syncRefundFields();
		document.addEventListener('click', guardFiscalRefund, true);

		const observer = new MutationObserver(syncRefundFields);
		observer.observe(document.body, {
			attributes: true,
			attributeFilter: ['aria-hidden', 'class', 'disabled', 'hidden', 'required', 'style'],
			childList: true,
			subtree: true,
		});

		document.addEventListener('click', (event) => {
			const target = event.target instanceof Element ? event.target.closest('#publish, .save_order, button[name="save"]') : null;
			if (target) syncRefundFields();
		}, true);
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initialize, { once: true });
	} else {
		initialize();
	}
})();
