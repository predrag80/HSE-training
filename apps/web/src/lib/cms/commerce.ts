import type { Locale } from '../../i18n/config';
import type { CommerceProduct } from '../../types/commerce';
import { fetchCmsJson } from './client';
import { isCanonicalCourseKey } from './course-key';
import { CmsError, isCmsError } from './errors';

interface CommerceProductDto {
	readonly schema_version: 1;
	readonly course_key: string;
	readonly name: string;
	readonly price_minor: number;
	readonly currency: string;
	readonly currency_decimals: number;
	readonly purchasable: boolean;
	readonly checkout_url: string;
}

function isCommerceProductDto(value: unknown): value is CommerceProductDto {
	if (typeof value !== 'object' || value === null) return false;
	const dto = value as Partial<CommerceProductDto>;
	return dto.schema_version === 1
		&& typeof dto.course_key === 'string'
		&& isCanonicalCourseKey(dto.course_key)
		&& typeof dto.name === 'string'
		&& dto.name.trim().length > 0
		&& typeof dto.price_minor === 'number'
		&& Number.isSafeInteger(dto.price_minor)
		&& dto.price_minor >= 0
		&& typeof dto.currency === 'string'
		&& /^[A-Z]{3}$/.test(dto.currency)
		&& typeof dto.currency_decimals === 'number'
		&& Number.isInteger(dto.currency_decimals)
		&& dto.currency_decimals >= 0
		&& dto.currency_decimals <= 4
		&& typeof dto.purchasable === 'boolean'
		&& typeof dto.checkout_url === 'string';
}

function mapCommerceProduct(value: unknown, expectedCourseKey: string, locale: Locale): CommerceProduct {
	if (!isCommerceProductDto(value) || value.course_key !== expectedCourseKey) {
		throw new CmsError('invalid-response', 'CMS returned an invalid Commerce product.');
	}

	let checkoutUrl: URL;
	try {
		checkoutUrl = new URL(value.checkout_url);
	} catch (cause) {
		throw new CmsError('invalid-response', 'CMS returned an invalid Commerce checkout URL.', { cause });
	}
	if (!['http:', 'https:'].includes(checkoutUrl.protocol)) {
		throw new CmsError('invalid-response', 'CMS returned an unsupported Commerce checkout URL.');
	}
	checkoutUrl.searchParams.set('lang', locale);

	return {
		schemaVersion: value.schema_version,
		courseKey: value.course_key,
		name: value.name,
		priceMinor: value.price_minor,
		currency: value.currency,
		currencyDecimals: value.currency_decimals,
		purchasable: value.purchasable,
		checkoutUrl: checkoutUrl.toString(),
	};
}

/**
 * Read a staging commerce projection. A missing route is an intentional signal
 * that the environment has not enabled the temporary WooCommerce bridge.
 */
export async function getOptionalCommerceProduct(courseKey: string, locale: Locale = 'en'): Promise<CommerceProduct | null> {
	if (!isCanonicalCourseKey(courseKey)) {
		throw new CmsError('invalid-query', 'Commerce course_key is invalid.');
	}

	try {
		return mapCommerceProduct(
			await fetchCmsJson(`/wp-json/hse/v1/commerce/products/${courseKey}`),
			courseKey,
			locale,
		);
	} catch (error) {
		if (isCmsError(error) && error.code === 'http' && error.status === 404) return null;
		throw error;
	}
}

/** Read commerce projections for a set of Course keys without duplicating requests. */
export async function getOptionalCommerceProducts(
	courseKeys: readonly string[],
	locale: Locale = 'en',
): Promise<readonly CommerceProduct[]> {
	const uniqueKeys = [...new Set(courseKeys)];
	const products = await Promise.all(uniqueKeys.map((courseKey) => getOptionalCommerceProduct(courseKey, locale)));

	return products.filter((product): product is CommerceProduct => product !== null);
}

/** Format integer minor units without trusting preformatted CMS markup. */
export function formatCommercePrice(product: CommerceProduct, locale: Locale): string {
	return new Intl.NumberFormat(locale === 'sr' ? 'sr-Latn-RS' : 'en-GB', {
		style: 'currency',
		currency: product.currency,
		minimumFractionDigits: product.currencyDecimals,
		maximumFractionDigits: product.currencyDecimals,
	}).format(product.priceMinor / (10 ** product.currencyDecimals));
}
