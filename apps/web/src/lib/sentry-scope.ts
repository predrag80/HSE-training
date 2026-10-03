const CONTACT_PATH = /^\/(?:sr\/)?contact\/?$/;

/** Enable browser telemetry only for the production contact experience. */
export function shouldEnableContactSentry(
	dsn: string | undefined,
	environment: string,
	pathname: string,
): boolean {
	return Boolean(dsn?.trim()) && environment === 'production' && CONTACT_PATH.test(pathname);
}
