# Security

## Table of contents

- [Scope](#scope)
- [Attack surface](#attack-surface)
- [Threats and mitigations](#threats-and-mitigations)
- [Dependencies](#dependencies)
- [Reporting a vulnerability](#reporting-a-vulnerability)
- [Supported versions](#supported-versions)
- [Release security checklist (12.4.1)](#release-security-checklist-1241)

## Scope

Console Debug Bundle injects debug data into **HTML responses** as inline JavaScript for users who pass the configured **gate** (roles, custom service, optional query parameter). Treat debug roles as highly privileged.

## Attack surface

- **Injected scripts** — a JSON payload block plus a runner script appended to HTML responses when `cdbg()` collected data.
- **Variable payloads** serialized to JSON and parsed client-side with `JSON.parse()`.
- **Authorization gate** (`ConsoleDebugGateInterface`) and Symfony Security integration.
- **Configuration** (`nowo_console_debug.*`) in the host application.

## Threats and mitigations

| Threat | Mitigation |
|--------|------------|
| Data leak to unauthorized users | Default gate requires authentication + configured roles; use `gate_service` for stricter rules in production. |
| XSS via injected script | Payload is `json_encode`d with `JSON_HEX_*` flags inside `<script type="application/json">`; runner script uses `JSON.parse()`. Do not pass unescaped HTML expecting safe rendering in console. |
| Sensitive data in console output | Avoid `cdbg()` on secrets, tokens, PII; restrict `ROLE_CONSOLE_DEBUG` to trusted staff only. |
| Debug left enabled in production | `enabled: true` is intentional for gated prod debugging; combine with role + query param or custom gate. |

## Dependencies

Run `composer audit`; keep Symfony, `symfony/security-core`, and this bundle updated.

## Reporting a vulnerability

Report via [GitHub Security Advisories](https://github.com/nowo-tech/ConsoleDebugBundle/security/advisories) or the issue tracker. Avoid public disclosure until addressed.

## Supported versions

Security fixes apply to the current major release line. Upgrade to the latest tag.

## Release security checklist (1.0.0)

Before tagging a release, confirm:

| Item | Notes |
|------|--------|
| **SECURITY.md** | Current; linked from README. |
| **`.gitignore` and `.env`** | No committed secrets. |
| **Recipe / Flex** | Default config does not grant debug roles automatically. |
| **Input / output** | JSON encoding for script injection; gate documented. |
| **Dependencies** | `composer audit` triaged. |
| **Permissions / exposure** | Document that debug roles expose internal state in the browser. |

Record confirmation in the release PR or tag notes.
