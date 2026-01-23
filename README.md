# Zebrra MCS

Zebrra MCS (Mail Control Service) is a Symfony-based REST API that provides admin and automation control over a mail platform.

It's designed to sit on top of an existing mail server stack and expose a secure, auditable API for both human admin and automated systems.

---

## Responsibilities

Zebrra MCS is responsible for :
- Admin authentication and authorization (super-admin / admin)
- API token management (permissions, scopes, expiry, rotation)
- Controlled actions on the mail data store (domains, users, aliases)
- Platform-level data management (admins, tokens, permissions)
- Centralized audit logging (MongoDB)

Zebrra MCS is **not** a mail server and does not replace Postfix, Dovecot, or related services.

---

## Data Stores
- MySQL (mail data) : existing mail database (`domains`, `users`, `aliases`)
- MySQL (platform data) : Zebrra MCS internal database (`sf_zebrra_mcs`)
- MongoDB : append-only audit events

All sensitive credentials and secrets are provided via environment variables and are not tracking in this repo.

---

## API Contract

- Base path : `/api/v1`
- Authentication :
    - Admin : session or JWT
    - Automation : Bearer API tokens
- Authorization :
    - Role-bases (admins)
    - Permission + scope-bases (API tokens)

The current API and architecture baseline is defined by :

- **Tag :** `v0.1.0-docs`
- **OpenAPI :** `openapi/v1.yaml`

---

## Related Projects

- Global specifications : **ZebrraMailPlatform**
- Admin panel (frontend) : **ZebrraMailPanel**

---

## Security Notes
- API tokens are never stored in plain text (hash only)
- Hard delete operations are restricted to super-admins
- All API token action are audit-logged in MongoDB
- Admin actions mays also be audited depending on policy

---

## Status

This project is under active development.
The current focus is on implementing the API according to the documented contract.

## License

GPL-3.0-or-later