# Security Policy

This private repository contains only Server Turizm-owned custom source and
approved operational documentation.

Never commit credentials, API keys, tokens, passwords, production database
dumps, customer/passenger/passport/lead data, WordPress uploads, WordPress core,
the Porto parent theme, or licensed third-party plugin source.

Store production credentials in the relevant hosting or WordPress secret store.
If a secret is committed, revoke it immediately, remove it from repository
history, and record the incident without recording the secret value.

All production-affecting changes require a branch, reviewable pull request,
runtime verification, and an explicit rollback path.
