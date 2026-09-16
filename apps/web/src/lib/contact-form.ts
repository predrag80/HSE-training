export interface ContactEnquiry {
	readonly name: string;
	readonly email: string;
	readonly phone: string;
	readonly message: string;
	readonly locale: 'en' | 'sr';
	readonly company_website: string;
}

export type ContactSubmissionResult = 'success' | 'rate-limited' | 'error';

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
		return response.status === 429 ? 'rate-limited' : 'error';
	} catch {
		return 'error';
	}
}
