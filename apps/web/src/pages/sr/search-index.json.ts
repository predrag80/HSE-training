import type { APIRoute } from 'astro';

import { loadSearchEntries } from '../../lib/search/search-index';

export const GET: APIRoute = async () =>
	new Response(JSON.stringify(await loadSearchEntries('sr')), {
		headers: { 'Content-Type': 'application/json; charset=utf-8' },
	});
