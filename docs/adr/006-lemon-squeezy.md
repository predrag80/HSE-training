# ADR-006: Lemon Squeezy for Payments

## Status

Proposed

## Date

2026-08-31

## Context

The public application needs a payment provider while keeping sensitive payment handling outside the browser and making payment confirmation reliable.

## Proposed Decision

Use Lemon Squeezy as the expected payment provider. Confirm payments only from verified webhooks, never from checkout success redirects, and process every webhook idempotently.

## Alternatives Considered

- Treat the checkout success redirect as confirmation.
- Build direct card processing.
- Use another merchant-of-record or payment provider.

## Consequences

- Webhook signature verification is mandatory.
- Provider event identifiers and processing outcomes must be persisted.
- Success redirects may communicate pending status but cannot grant course access.

