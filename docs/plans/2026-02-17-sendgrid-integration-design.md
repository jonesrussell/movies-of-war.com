# SendGrid Integration via northcloud-laravel

**Date:** 2026-02-17
**Status:** Approved
**Scope:** northcloud-laravel package + Movies of War + Streetcode (later)

## Goal

Add SendGrid Web API email transport to northcloud-laravel so every consuming app gets email sending by adding `SENDGRID_API_KEY` to their `.env`. No code changes needed in consuming apps.

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| API vs SMTP | Web API | Better deliverability tracking, faster, matches SendGrid's recommended approach |
| Mail scope | Auto-replace default | When `SENDGRID_API_KEY` is set, SendGrid becomes the default mailer automatically |
| From address | Use Laravel's existing config | Apps keep their own `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` |
| Extra features | Basic sending only | Webhooks/tracking can be added later |
| Implementation | Custom Mail Transport | Follows Laravel's own driver pattern (Postmark, Resend, SES) |

## Architecture

### New files in northcloud-laravel

```
src/Mail/Transport/SendGridTransport.php      # Symfony TransportInterface
src/Mail/SendGridTransportFactory.php          # Factory for Mail::extend()
```

### Modified files

- `composer.json` — add `sendgrid/sendgrid` dependency
- `config/northcloud.php` — add `mail.sendgrid` section
- `src/NorthCloudServiceProvider.php` — register transport, auto-configure default

### Config addition

```php
// config/northcloud.php
'mail' => [
    'sendgrid' => [
        'enabled' => env('SENDGRID_API_KEY') !== null,
        'api_key' => env('SENDGRID_API_KEY'),
        'set_as_default' => true,
    ],
],
```

### Registration flow

1. Service provider checks if `SENDGRID_API_KEY` is set
2. Registers `sendgrid` transport via `Mail::extend('sendgrid', ...)`
3. Dynamically adds `sendgrid` mailer to `config/mail.mailers`
4. Sets `config/mail.default` to `sendgrid` (when `set_as_default` is true)
5. All existing notifications and mailables route through SendGrid automatically

## SendGridTransport

Implements `Symfony\Component\Mailer\Transport\TransportInterface`:

- Receives `Symfony\Component\Mime\Email` from Laravel's mail system
- Extracts from, to, cc, bcc, subject, html/text body, attachments
- Builds SendGrid `\SendGrid\Mail\Mail` object
- Sends via `sendgrid/sendgrid` client
- Throws `TransportException` on API errors (non-2xx)
- Returns `SentMessage` on success

## Movies of War Integration

**Production `.env`:**
```
SENDGRID_API_KEY=<your-sendgrid-api-key>
MAIL_FROM_ADDRESS=noreply@movies-of-war.com
MAIL_FROM_NAME="Movies of War"
```

**`.env.example`:** Add `SENDGRID_API_KEY=` placeholder

**`composer.json`:** Update northcloud-laravel version constraint

**No code changes needed.** Fortify password reset, email verification, and all future notifications work automatically.

## Testing

### northcloud-laravel package tests

1. **SendGridTransport unit test** — Mock SendGrid client, verify correct API calls for from/to/subject/body/attachments/cc/bcc, error handling throws `TransportException`
2. **Service provider test** — Verify transport registered when key is set, not registered when null, default mailer behavior

### Movies of War

3. Existing auth tests already use `Notification::fake()` — no changes needed
4. Manual integration test: send test email after deployment

## Streetcode (Future)

Same pattern — create a SendGrid API key, add to `.env`, update northcloud-laravel version. No code changes.
