import { describe, expect, it } from 'vitest';
import { shouldEnableContactSentry } from './sentry-scope';

describe('production contact Sentry scope', () => {
	it('enables only the English and Serbian production contact routes', () => {
		expect(shouldEnableContactSentry('https://public@example.test/1', 'production', '/contact/')).toBe(true);
		expect(shouldEnableContactSentry('https://public@example.test/1', 'production', '/sr/contact/')).toBe(true);
	});

	it('rejects non-production environments, unrelated routes, and missing DSNs', () => {
		expect(shouldEnableContactSentry('https://public@example.test/1', 'dev', '/contact/')).toBe(false);
		expect(shouldEnableContactSentry('https://public@example.test/1', 'staging', '/contact/')).toBe(false);
		expect(shouldEnableContactSentry('https://public@example.test/1', 'production', '/courses/')).toBe(false);
		expect(shouldEnableContactSentry(undefined, 'production', '/contact/')).toBe(false);
	});
});
