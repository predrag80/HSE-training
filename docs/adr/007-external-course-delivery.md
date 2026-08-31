# ADR-007: External Course Delivery

## Status

Proposed

## Date

2026-08-31

## Context

Course content and learner access are provided through a third-party LMS. The public application sells courses but does not need local customer accounts or learning-management capabilities.

## Proposed Decision

Keep course delivery external. Do not build LMS functionality or customer authentication unless a future explicit requirement changes this boundary.

## Alternatives Considered

- Host course content in Astro.
- Build an LMS in this project.
- Create local customer accounts solely to mirror external access.

## Consequences

- Purchase and fulfillment state may be tracked locally, but learning activity remains external.
- The owner can provision LMS access after verified purchase.
- Any future LMS automation must be designed as an integration, not as a replacement LMS.

