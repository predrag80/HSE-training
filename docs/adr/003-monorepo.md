# ADR-003: Monorepo Structure

## Status

Proposed

## Date

2026-08-31

## Context

The project contains an Astro application, a custom WordPress integration plugin, and shared architectural documentation. These artifacts have related contracts but different deployment targets.

## Proposed Decision

Maintain the Astro application, WordPress plugin, documentation, and project tasks in one repository, with clear top-level boundaries for each deployable artifact.

## Alternatives Considered

- Use separate repositories for Astro and WordPress.
- Place all project files in a single undifferentiated root structure.

## Consequences

- Cross-system changes can be reviewed together.
- Each deployable artifact must retain independent build and deployment concerns.
- Shared business identifiers and integration contracts remain visible in one place.

