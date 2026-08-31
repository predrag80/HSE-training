# ADR-005: PostgreSQL for Business State

## Status

Proposed

## Date

2026-08-31

## Context

Payment events, purchase records, fulfillment state, and idempotency records require durable transactional storage outside the CMS.

## Proposed Decision

Use PostgreSQL as the system of record for payment and business state. Reference courses by `course_key` and retain the data needed to process payment webhooks idempotently.

## Alternatives Considered

- Store payment state in WordPress metadata.
- Treat the payment provider as the only business-state store.
- Add an in-memory cache or queue as primary state infrastructure.

## Consequences

- Business-state schema changes require explicit migrations.
- WordPress remains independent of transactional state.
- Duplicate webhook deliveries can be detected and handled safely.

