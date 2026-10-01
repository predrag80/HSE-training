export interface EditorialContentSection {
	headingHtml: string;
	bodyHtml: string;
}

export interface EditorialContent {
	introHtml: string;
	sections: EditorialContentSection[];
}

/**
 * Groups editor-authored course copy by its top-level H2 headings so the
 * presentation layer can turn long CMS content into scannable sections.
 */
export const splitEditorialContent = (html: string): EditorialContent => {
	const headingPattern = /<h2(?:\s[^>]*)?>([\s\S]*?)<\/h2>/gi;
	const matches = Array.from(html.matchAll(headingPattern));

	if (matches.length === 0) {
		return { introHtml: html.trim(), sections: [] };
	}

	const firstMatchIndex = matches[0].index ?? 0;
	const introHtml = html.slice(0, firstMatchIndex).trim();
	const sections = matches.map((match, index) => {
		const contentStart = (match.index ?? 0) + match[0].length;
		const nextMatchIndex = matches[index + 1]?.index ?? html.length;

		return {
			headingHtml: match[1].trim(),
			bodyHtml: html.slice(contentStart, nextMatchIndex).trim(),
		};
	});

	return { introHtml, sections };
};
