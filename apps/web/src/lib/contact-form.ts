export interface ContactEnquiry {
	readonly name: string;
	readonly email: string;
	readonly phone: string;
	readonly message: string;
	readonly locale: 'en' | 'sr';
	readonly company_website: string;
	readonly turnstile_token: string;
}

export type ContactSubmissionResult =
	| 'success'
	| 'rate-limited'
	| 'verification-failed'
	| 'verification-unavailable'
	| 'error';

type FetchLike = (input: RequestInfo | URL, init?: RequestInit) => Promise<Response>;

export async function sendContactEnquiry(
	endpoint: string,
	enquiry: ContactEnquiry,
	fetcher: FetchLike = fetch,
): Promise<ContactSubmissionResult> {
	try {
		const response = await fetcher(endpoint, {
			method: 'POST',
			headers: {
				Accept: 'application/json',
				'Content-Type': 'application/json',
			},
			body: JSON.stringify(enquiry),
		});

		if (response.ok) return 'success';
		if (response.status === 429) return 'rate-limited';

		const payload: unknown = await response.json().catch(() => null);
		const code = payload && typeof payload === 'object' && 'code' in payload
			? String(payload.code)
			: '';
		if (code === 'hse_contact_verification_failed') return 'verification-failed';
		if (code === 'hse_contact_verification_unavailable') return 'verification-unavailable';

		return 'error';
	} catch {
		return 'error';
	}
}
