# Security Policy

## Supported Versions

| Version | PHP          | Supported          |
|---------|-------------|--------------------|
| 1.x     | ^8.1        | Active development |
| 0.x     | >=7.1       | End of life        |

## Reporting a Vulnerability

**Do not open a public issue.** Email security concerns to
**info@ubuviz.com** with the subject line `SECURITY: ihela-php-client`.

Please include:

- A description of the vulnerability
- Steps to reproduce
- Affected version(s)
- Any proposed mitigation

You will receive an acknowledgment within 48 hours and a detailed
response within 5 business days.

## Security Best Practices for Integrators

- **Never** hardcode credentials in source code or commit them to git.
- Store `IHELA_CLIENT_ID`, `IHELA_CLIENT_SECRET`, and `IHELA_PIN_CODE`
  in environment variables or a secrets vault.
- Use a strong, unique PIN code assigned by iHela.
- Production credentials require the iHela VPN — the gateway is
  IP-whitelisted.
- Enable HMAC request signing by passing `signatureKey` to the client
  constructor and verifying responses with `verifySignature()`.
- Never log `access_token`, `pin_code`, or `client_secret`. The SDK
  masks these automatically when a PSR-3 logger is configured.
- Rotate credentials periodically and immediately if exposed.
