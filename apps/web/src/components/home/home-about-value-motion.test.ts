import { describe, expect, it } from 'vitest';
import { getAboutParallaxOffset } from './home-about-value-motion';

describe('getAboutParallaxOffset', () => {
	it('moves from 50px to -50px while the media crosses the viewport', () => {
		expect(getAboutParallaxOffset(1000, 500, 1000)).toBe(50);
		expect(getAboutParallaxOffset(250, 500, 1000)).toBe(0);
		expect(getAboutParallaxOffset(-500, 500, 1000)).toBe(-50);
	});

	it('clamps the movement outside the visible scroll range', () => {
		expect(getAboutParallaxOffset(1400, 500, 1000)).toBe(50);
		expect(getAboutParallaxOffset(-900, 500, 1000)).toBe(-50);
	});
});
