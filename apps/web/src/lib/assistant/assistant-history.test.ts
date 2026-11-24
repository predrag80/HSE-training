import { describe, expect, it } from 'vitest';

import { contactDetails } from '../contact';
import {
	ASSISTANT_HISTORY_MAX_MESSAGES,
	getAssistantHistoryKey,
	parseAssistantHistory,
	type AssistantHistory,
} from './assistant-history';

const history: AssistantHistory = {
	open: true,
	topicId: 'training',
	messages: [
		{ sender: 'visitor', blocks: [{ text: 'Koliko traje Banksman obuka?' }] },
		{
			sender: 'assistant',
			blocks: [
				{ text: 'Banksman / Slinger: 5 dana · srpski · najviše 8 polaznika.' },
				{
					links: [
						{ label: 'Banksman / Slinger', href: '/sr/training/banksman-slinger/', kind: 'page' },
						{ label: contactDetails.phone, href: contactDetails.phoneHref, kind: 'phone' },
					],
				},
			],
		},
	],
};

describe('site assistant history', () => {
	it('keeps a separate conversation per language', () => {
		expect(getAssistantHistoryKey('en')).toBe('hse-site-assistant:en');
		expect(getAssistantHistoryKey('sr')).toBe('hse-site-assistant:sr');
	});

	it('restores a stored conversation unchanged', () => {
		expect(parseAssistantHistory(JSON.parse(JSON.stringify(history)))).toEqual(history);
	});

	it('drops stored links that leave the website', () => {
		const tampered = {
			...history,
			messages: [{
				sender: 'assistant',
				blocks: [
					{ text: 'Links' },
					{
						links: [
							{ label: 'Elsewhere', href: 'https://example.com/', kind: 'page' },
							{ label: 'Relative host', href: '//example.com/', kind: 'page' },
							{ label: 'Other phone', href: 'tel:+381000000', kind: 'phone' },
							{ label: 'Script', href: 'javascript:alert(1)', kind: 'page' },
							{ label: 'Courses', href: '/courses/', kind: 'page' },
						],
					},
					{ links: [{ label: 'Only external', href: 'https://example.com/', kind: 'page' }] },
				],
			}],
		};

		expect(parseAssistantHistory(tampered)?.messages).toEqual([{
			sender: 'assistant',
			blocks: [{ text: 'Links' }, { links: [{ label: 'Courses', href: '/courses/', kind: 'page' }] }],
		}]);
	});

	it('skips malformed messages and blocks', () => {
		const parsed = parseAssistantHistory({
			...history,
			messages: [
				{ sender: 'system', blocks: [{ text: 'Unknown sender' }] },
				{ sender: 'visitor', blocks: [{ text: ' ' }, { html: '<b>x</b>' }] },
				{ sender: 'visitor', blocks: [{ text: 'x'.repeat(2001) }] },
				{ sender: 'visitor' },
				{ sender: 'visitor', blocks: [{ text: 'Valid' }] },
			],
		});

		expect(parsed?.messages).toEqual([{ sender: 'visitor', blocks: [{ text: 'Valid' }] }]);
	});

	it('keeps only the most recent messages', () => {
		const messages = Array.from({ length: ASSISTANT_HISTORY_MAX_MESSAGES + 5 }, (_, index) => ({
			sender: 'visitor',
			blocks: [{ text: `Message ${index}` }],
		}));
		const parsed = parseAssistantHistory({ ...history, messages });

		expect(parsed?.messages).toHaveLength(ASSISTANT_HISTORY_MAX_MESSAGES);
		expect(parsed?.messages[0]).toEqual({ sender: 'visitor', blocks: [{ text: 'Message 5' }] });
	});

	it('rejects values that are not a stored conversation', () => {
		expect(parseAssistantHistory(null)).toBeNull();
		expect(parseAssistantHistory([])).toBeNull();
		expect(parseAssistantHistory({ ...history, open: 'yes' })).toBeNull();
		expect(parseAssistantHistory({ ...history, topicId: 3 })).toBeNull();
		expect(parseAssistantHistory({ ...history, messages: {} })).toBeNull();
	});
});
