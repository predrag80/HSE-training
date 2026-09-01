export type CmsErrorCode =
	| 'configuration'
	| 'unavailable'
	| 'http'
	| 'invalid-response'
	| 'invalid-query';

export class CmsError extends Error {
	readonly code: CmsErrorCode;
	readonly status: number | null;

	constructor(
		code: CmsErrorCode,
		message: string,
		options: { cause?: unknown; status?: number } = {},
	) {
		super(message, { cause: options.cause });
		this.name = 'CmsError';
		this.code = code;
		this.status = options.status ?? null;
	}
}

export function isCmsError(error: unknown): error is CmsError {
	return error instanceof CmsError;
}
