import { describe, expect, it } from 'vitest';
import { clampHeroSlideIndex, getHeroSlidePosition } from './home-hero-slider';

describe('clampHeroSlideIndex', () => {
	it('keeps a requested slide inside the available range', () => {
		expect(clampHeroSlideIndex(1, 3)).toBe(1);
		expect(clampHeroSlideIndex(-1, 3)).toBe(0);
		expect(clampHeroSlideIndex(3, 3)).toBe(2);
	});

	it('safely resolves an empty slider to index zero', () => {
		expect(clampHeroSlideIndex(2, 0)).toBe(0);
	});
});

describe('getHeroSlidePosition', () => {
	it('identifies the active, previous and next parallax positions', () => {
		expect(getHeroSlidePosition(1, 1)).toBe('active');
		expect(getHeroSlidePosition(0, 1)).toBe('before');
		expect(getHeroSlidePosition(2, 1)).toBe('after');
	});
});
