import { describe, expect, it, vi } from 'vitest';
import { sendContactEnquiry, type ContactEnquiry } from './contact-form';

const enquiry: ContactEnquiry = {
	name: 'Predrag Vuckovic',
	email: 'predrag@example.com',
	phone: '+381 61 123 456',
	message: 'Please send more information.',
	locale: 'en',
	company_website: '',
	turnstile_token: 'turnstile-test-token',
};

describe('sendContactEnquiry', () => {
	it('posts the form payload as JSON', async () => {
		const fetcher = vi.fn(async () => new Response(null, { status: 202 }));

		await expect(sendContactEnquiry('https://cms.example.test/wp-json/hse/v1/contact', enquiry, fetcher)).resolves.toBe('success');
		expect(fetcher).toHaveBeenCalledWith(
			'https://cms.example.test/wp-json/hse/v1/contact',
			expect.objectContaining({ method: 'POST', body: JSON.stringify(enquiry) }),
		);
	});

	it('distinguishes rate limiting from a generic delivery error', async () => {
		const limited = vi.fn(async () => new Response(null, { status: 429 }));
		const failed = vi.fn(async () => new Response(null, { status: 503 }));

		await expect(sendContactEnquiry('/contact', enquiry, limited)).resolves.toBe('rate-limited');
		await expect(sendContactEnquiry('/contact', enquiry, failed)).resolves.toBe('error');
	});

	it('maps server-side Turnstile failures to safe frontend states', async () => {
		const rejected = vi.fn(async () => new Response(
			JSON.stringify({ code: 'hse_contact_verification_failed' }),
			{ status: 403, headers: { 'Content-Type': 'application/json' } },
		));
		const unavailable = vi.fn(async () => new Response(
			JSON.stringify({ code: 'hse_contact_verification_unavailable' }),
			{ status: 503, headers: { 'Content-Type': 'application/json' } },
		));

		await expect(sendContactEnquiry('/contact', enquiry, rejected)).resolves.toBe('verification-failed');
		await expect(sendContactEnquiry('/contact', enquiry, unavailable)).resolves.toBe('verification-unavailable');
	});

	it('returns a generic error for network failures', async () => {
		const fetcher = vi.fn(async () => { throw new Error('offline'); });
		await expect(sendContactEnquiry('/contact', enquiry, fetcher)).resolves.toBe('error');
	});
});
