import type { APIRoute } from 'astro';

import { loadAssistantContent } from '../lib/assistant/assistant-content';

export const GET: APIRoute = () =>
	new Response(JSON.stringify(loadAssistantContent('en')), {
		headers: { 'Content-Type': 'application/json; charset=utf-8' },
	});
