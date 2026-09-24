# Kashier Payment Integration Rules for MAQAM

When implementing or modifying payment integrations involving Kashier (`developers.kashier.io`) in this repository, you MUST follow these guidelines:

## 1. Documentation Source of Truth
* Always reference [docs/kashier_integration_knowledge.md](file:///d:/Nour/personal/maqam/docs/kashier_integration_knowledge.md) for full endpoint lists, test card credentials, and formulas.

## 2. Authentication & Keys
* Use **Secret Key** in `Authorization: <KEY>` header (no `Bearer ` prefix) for Dashboard/Session/Refund calls.
* Use **Payment API Key** for HMAC-SHA256 calculations:
  * Request order hash: `/?payment={mid}.{orderId}.{amount}.{currency}`
  * Webhook verification: `x-kashier-signature` header
  * Redirect verification: ordered parameters query string
* Keys are mode-bound: Never use test keys on live hosts or vice versa (results in `403 INVALID_HASH_CHECK`).

## 3. Webhook Handling
* Webhook endpoint `api/webhooks/kashier` must be excluded from CSRF protection in `bootstrap/app.php`.
* Never trust `event == "pay"` alone for success; always check `data.status == "SUCCESS"`.
* Always respond with HTTP `200` (or `409` if duplicate) within 30 seconds with an empty body.
* Ensure idempotent database updates on `payments` and `orders`.

## 4. API Quirks & Gotchas
* Notice that the provider reconciliation field in transaction responses is spelled `reconcilation` (single `i`).
* In sandbox, hosted checkout restricts payment methods to Card and Wallet only.
* If IP allow-listing is enabled in Kashier dashboard, ensure the server's public egress IP is allow-listed.
