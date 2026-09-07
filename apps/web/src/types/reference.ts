export interface Reference {
	readonly referenceKey: string;
	readonly quote: string;
	readonly authorName: string;
	readonly role: string;
	readonly featuredOnHomepage: boolean;
	readonly accentOnHomepage: boolean;
}

export interface ReferenceCollection {
	readonly schemaVersion: 1;
	readonly collectionKey: 'references';
	readonly references: readonly Reference[];
}
