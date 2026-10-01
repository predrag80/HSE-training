import { describe, expect, it } from 'vitest';
import { splitEditorialContent } from './editorial-content';

describe('splitEditorialContent', () => {
	it('groups CMS copy into sections by top-level headings', () => {
		const content = splitEditorialContent(`
			<p>Intro copy.</p>
			<h2>Who is it for?</h2>
			<p>Managers and supervisors.</p>
			<h2 class="wp-block-heading">What will you learn?</h2>
			<ul><li>Risk management</li></ul>
		`);

		expect(content.introHtml).toBe('<p>Intro copy.</p>');
		expect(content.sections).toEqual([
			{
				headingHtml: 'Who is it for?',
				bodyHtml: '<p>Managers and supervisors.</p>',
			},
			{
				headingHtml: 'What will you learn?',
				bodyHtml: '<ul><li>Risk management</li></ul>',
			},
		]);
	});

	it('keeps content without section headings as introductory copy', () => {
		const content = splitEditorialContent('<p>Plain editor content.</p>');

		expect(content).toEqual({
			introHtml: '<p>Plain editor content.</p>',
			sections: [],
		});
	});
});
