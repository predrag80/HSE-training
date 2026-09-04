export type HeroSlidePosition = 'before' | 'active' | 'after';

export function clampHeroSlideIndex(requestedIndex: number, slideCount: number): number {
	return Math.min(Math.max(requestedIndex, 0), Math.max(slideCount - 1, 0));
}

export function getHeroSlidePosition(index: number, activeIndex: number): HeroSlidePosition {
	if (index === activeIndex) return 'active';
	return index < activeIndex ? 'before' : 'after';
}
