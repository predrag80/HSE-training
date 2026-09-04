const PARALLAX_DISTANCE = 50;

export function getAboutParallaxOffset(
	elementTop: number,
	elementHeight: number,
	viewportHeight: number,
): number {
	const travelDistance = viewportHeight + elementHeight;
	if (travelDistance <= 0) return 0;

	const progress = Math.min(Math.max((viewportHeight - elementTop) / travelDistance, 0), 1);
	return PARALLAX_DISTANCE - progress * PARALLAX_DISTANCE * 2;
}
