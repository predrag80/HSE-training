/** Client-safe model and validation of the site assistant conversation kept in sessionStorage. */

import { isAllowedAssistantLink, type AssistantLink } from './assistant-match';

export type AssistantMessageBlock =
	| { readonly text: string }
	| { readonly links: readonly AssistantLink[] };

export interface AssistantMessage {
	readonly sender: 'assistant' | 'visitor';
	readonly blocks: readonly AssistantMessageBlock[];
}

export interface AssistantHistory {
	/** Whether the panel was open when the visitor left the previous page. */
	readonly open: boolean;
	/** Topic whose follow-up choices were shown last. */
	readonly topicId: string;
	readonly messages: readonly AssistantMessage[];
}

/** Older messages are dropped so a long session stays small. */
export const ASSISTANT_HISTORY_MAX_MESSAGES = 40;
const MAX_TEXT_LENGTH = 2000;

/** Set once the assistant opened by itself in this tab, so it does so only once per visit. */
export const ASSISTANT_AUTO_OPENED_KEY = 'hse-site-assistant:auto-opened';

/** One key per locale, so switching language starts a conversation in that language. */
export function getAssistantHistoryKey(locale: 'en' | 'sr'): string {
	return `hse-site-assistant:${locale}`;
}

function parseLink(value: unknown): AssistantLink | null {
	if (typeof value !== 'object' || value === null) return null;

	const link = value as Record<string, unknown>;
	if (typeof link.label !== 'string' || !link.label.trim() || typeof link.href !== 'string') return null;
	if (link.kind !== 'page' && link.kind !== 'phone' && link.kind !== 'email') return null;

	const parsed: AssistantLink = { label: link.label, href: link.href, kind: link.kind };
	return isAllowedAssistantLink(parsed) ? parsed : null;
}

function parseBlock(value: unknown): AssistantMessageBlock | null {
	if (typeof value !== 'object' || value === null) return null;

	const block = value as Record<string, unknown>;
	if (typeof block.text === 'string') {
		return block.text.trim() && block.text.length <= MAX_TEXT_LENGTH ? { text: block.text } : null;
	}
	if (Array.isArray(block.links)) {
		const links = block.links.map(parseLink).filter((link): link is AssistantLink => link !== null);
		return links.length > 0 ? { links } : null;
	}

	return null;
}

function parseMessage(value: unknown): AssistantMessage | null {
	if (typeof value !== 'object' || value === null) return null;

	const message = value as Record<string, unknown>;
	if (message.sender !== 'assistant' && message.sender !== 'visitor') return null;
	if (!Array.isArray(message.blocks)) return null;

	const blocks = message.blocks.map(parseBlock).filter((block): block is AssistantMessageBlock => block !== null);
	return blocks.length > 0 ? { sender: message.sender, blocks } : null;
}

/**
 * Validate a stored conversation before it is shown again. Malformed messages
 * are skipped, and links that leave the website are dropped exactly as in the
 * downloaded content, so storage cannot introduce an off-site link.
 */
export function parseAssistantHistory(value: unknown): AssistantHistory | null {
	if (typeof value !== 'object' || value === null) return null;

	const history = value as Record<string, unknown>;
	if (typeof history.open !== 'boolean' || typeof history.topicId !== 'string' || !Array.isArray(history.messages)) {
		return null;
	}

	const messages = history.messages
		.map(parseMessage)
		.filter((message): message is AssistantMessage => message !== null)
		.slice(-ASSISTANT_HISTORY_MAX_MESSAGES);

	return { open: history.open, topicId: history.topicId, messages };
}
