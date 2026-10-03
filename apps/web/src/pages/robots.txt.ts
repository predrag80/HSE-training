import type { APIRoute } from 'astro';

const productionHosts = new Set(['hsetraining.rs', 'www.hsetraining.rs']);

export const GET: APIRoute = ({ site }) => {
	if (!site || !productionHosts.has(site.hostname)) {
		return new Response('User-agent: *\nDisallow: /\n', {
			headers: { 'Content-Type': 'text/plain; charset=utf-8' },
		});
	}

	const sitemapUrl = new URL('sitemap-index.xml', site).href;
	return new Response(`User-agent: *\nAllow: /\n\nSitemap: ${sitemapUrl}\n`, {
		headers: { 'Content-Type': 'text/plain; charset=utf-8' },
	});
};
