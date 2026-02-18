# SendGrid Integration Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a SendGrid Web API mail transport to northcloud-laravel so consuming apps (Movies of War, Streetcode) get email delivery by setting `SENDGRID_API_KEY` in `.env`.

**Architecture:** Custom Symfony mail transport extending `AbstractTransport` (same pattern as Laravel's ResendTransport). Registered via `Mail::extend()` in the service provider. Auto-configures as default mailer when the API key is present.

**Tech Stack:** `sendgrid/sendgrid` PHP library, Symfony Mailer `AbstractTransport`, Laravel `Mail` facade, Pest tests.

**Design doc:** `docs/plans/2026-02-17-sendgrid-integration-design.md`

---

## Working Directories

- **northcloud-laravel:** `/home/jones/dev/northcloud-laravel`
- **movies-of-war.com:** `/home/jones/dev/movies-of-war.com`

All `composer` / test commands in northcloud-laravel run directly (not ddev).
All commands in movies-of-war.com use `ddev` prefix.

---

### Task 1: Add sendgrid/sendgrid dependency to northcloud-laravel

**Files:**
- Modify: `/home/jones/dev/northcloud-laravel/composer.json`

**Step 1: Add the dependency**

```bash
cd /home/jones/dev/northcloud-laravel && composer require sendgrid/sendgrid "^8.0"
```

This adds `sendgrid/sendgrid` to the `require` section (not require-dev — consuming apps need it at runtime).

**Step 2: Verify installation**

```bash
cd /home/jones/dev/northcloud-laravel && composer show sendgrid/sendgrid
```

Expected: Shows package info with version ^8.x.

**Step 3: Commit**

```bash
cd /home/jones/dev/northcloud-laravel
git add composer.json composer.lock
git commit -m "feat: add sendgrid/sendgrid dependency for mail transport"
```

---

### Task 2: Add mail config section to northcloud config

**Files:**
- Modify: `/home/jones/dev/northcloud-laravel/config/northcloud.php`
- Modify: `/home/jones/dev/northcloud-laravel/tests/Unit/ConfigTest.php`

**Step 1: Write the failing test**

Add to `tests/Unit/ConfigTest.php`:

```php
it('provides default mail config values', function () {
    expect(config('northcloud.mail.sendgrid.api_key'))->toBeNull();
    expect(config('northcloud.mail.sendgrid.set_as_default'))->toBeTrue();
});
```

**Step 2: Run test to verify it fails**

```bash
cd /home/jones/dev/northcloud-laravel && vendor/bin/pest tests/Unit/ConfigTest.php --filter="provides default mail config"
```

Expected: FAIL — `northcloud.mail.sendgrid.api_key` config key doesn't exist yet.

**Step 3: Add mail section to config**

In `/home/jones/dev/northcloud-laravel/config/northcloud.php`, add before the closing `];`:

```php
    'mail' => [
        'sendgrid' => [
            'api_key' => env('SENDGRID_API_KEY'),
            'set_as_default' => true,
        ],
    ],
```

**Step 4: Run test to verify it passes**

```bash
cd /home/jones/dev/northcloud-laravel && vendor/bin/pest tests/Unit/ConfigTest.php --filter="provides default mail config"
```

Expected: PASS

**Step 5: Commit**

```bash
cd /home/jones/dev/northcloud-laravel
git add config/northcloud.php tests/Unit/ConfigTest.php
git commit -m "feat: add sendgrid mail config section"
```

---

### Task 3: Create SendGridTransport class

**Files:**
- Create: `/home/jones/dev/northcloud-laravel/src/Mail/Transport/SendGridTransport.php`
- Create: `/home/jones/dev/northcloud-laravel/tests/Unit/Mail/Transport/SendGridTransportTest.php`

**Step 1: Write the failing tests**

Create `tests/Unit/Mail/Transport/SendGridTransportTest.php`:

```php
<?php

use JonesRussell\NorthCloud\Mail\Transport\SendGridTransport;
use SendGrid\Response;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

beforeEach(function () {
    $this->sendGridClient = Mockery::mock(\SendGrid::class);
    $this->transport = new SendGridTransport($this->sendGridClient);
});

it('sends a basic email via SendGrid API', function () {
    $email = (new Email())
        ->from(new Address('sender@example.com', 'Sender'))
        ->to(new Address('recipient@example.com', 'Recipient'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>')
        ->text('Hello');

    $response = Mockery::mock(Response::class);
    $response->shouldReceive('statusCode')->andReturn(202);
    $response->shouldReceive('body')->andReturn('');

    $this->sendGridClient
        ->shouldReceive('send')
        ->once()
        ->withArgs(function (\SendGrid\Mail\Mail $mail) {
            // Verify the SendGrid Mail object was built correctly
            return true;
        })
        ->andReturn($response);

    $sentMessage = $this->transport->send($email);

    expect($sentMessage)->not->toBeNull();
});

it('sends email with cc and bcc', function () {
    $email = (new Email())
        ->from(new Address('sender@example.com', 'Sender'))
        ->to(new Address('recipient@example.com', 'Recipient'))
        ->cc(new Address('cc@example.com', 'CC'))
        ->bcc(new Address('bcc@example.com', 'BCC'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>');

    $response = Mockery::mock(Response::class);
    $response->shouldReceive('statusCode')->andReturn(202);
    $response->shouldReceive('body')->andReturn('');

    $this->sendGridClient
        ->shouldReceive('send')
        ->once()
        ->andReturn($response);

    $sentMessage = $this->transport->send($email);

    expect($sentMessage)->not->toBeNull();
});

it('sends email with attachments', function () {
    $email = (new Email())
        ->from(new Address('sender@example.com', 'Sender'))
        ->to(new Address('recipient@example.com', 'Recipient'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>')
        ->attach('file content', 'document.pdf', 'application/pdf');

    $response = Mockery::mock(Response::class);
    $response->shouldReceive('statusCode')->andReturn(202);
    $response->shouldReceive('body')->andReturn('');

    $this->sendGridClient
        ->shouldReceive('send')
        ->once()
        ->andReturn($response);

    $sentMessage = $this->transport->send($email);

    expect($sentMessage)->not->toBeNull();
});

it('throws TransportException on API error', function () {
    $email = (new Email())
        ->from(new Address('sender@example.com', 'Sender'))
        ->to(new Address('recipient@example.com', 'Recipient'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>');

    $response = Mockery::mock(Response::class);
    $response->shouldReceive('statusCode')->andReturn(401);
    $response->shouldReceive('body')->andReturn('{"errors":[{"message":"Invalid API key"}]}');

    $this->sendGridClient
        ->shouldReceive('send')
        ->once()
        ->andReturn($response);

    $this->transport->send($email);
})->throws(TransportException::class);

it('throws TransportException when SendGrid client throws', function () {
    $email = (new Email())
        ->from(new Address('sender@example.com', 'Sender'))
        ->to(new Address('recipient@example.com', 'Recipient'))
        ->subject('Test Subject')
        ->html('<p>Hello</p>');

    $this->sendGridClient
        ->shouldReceive('send')
        ->once()
        ->andThrow(new \Exception('Connection failed'));

    $this->transport->send($email);
})->throws(TransportException::class);

it('returns sendgrid as string representation', function () {
    expect((string) $this->transport)->toBe('sendgrid');
});
```

**Step 2: Run tests to verify they fail**

```bash
cd /home/jones/dev/northcloud-laravel && vendor/bin/pest tests/Unit/Mail/Transport/SendGridTransportTest.php
```

Expected: FAIL — class `SendGridTransport` not found.

**Step 3: Write the SendGridTransport implementation**

Create `/home/jones/dev/northcloud-laravel/src/Mail/Transport/SendGridTransport.php`:

```php
<?php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Mail\Transport;

use SendGrid;
use SendGrid\Mail\Mail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class SendGridTransport extends AbstractTransport
{
    public function __construct(private SendGrid $client)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $sendGridMail = new Mail();

        $sender = $envelope->getSender();
        $sendGridMail->setFrom($sender->getAddress(), $sender->getName());
        $sendGridMail->setSubject($email->getSubject() ?? '');

        foreach ($envelope->getRecipients() as $recipient) {
            $sendGridMail->addTo($recipient->getAddress(), $recipient->getName());
        }

        foreach ($email->getCc() as $cc) {
            $sendGridMail->addCc($cc->getAddress(), $cc->getName());
        }

        foreach ($email->getBcc() as $bcc) {
            $sendGridMail->addBcc($bcc->getAddress(), $bcc->getName());
        }

        foreach ($email->getReplyTo() as $replyTo) {
            $sendGridMail->setReplyTo($replyTo->getAddress(), $replyTo->getName());
            break; // SendGrid only supports one reply-to
        }

        if ($email->getHtmlBody()) {
            $sendGridMail->addContent('text/html', $email->getHtmlBody());
        }

        if ($email->getTextBody()) {
            $sendGridMail->addContent('text/plain', $email->getTextBody());
        }

        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $filename = $headers->getHeaderParameter('Content-Disposition', 'filename') ?? 'attachment';
            $contentType = $headers->get('Content-Type')?->getBodyAsString() ?? 'application/octet-stream';
            $disposition = $headers->getHeaderBody('Content-Disposition');

            $sendGridMail->addAttachment(
                base64_encode($attachment->getBody()),
                $contentType,
                $filename,
                $disposition === 'inline' ? 'inline' : 'attachment',
            );
        }

        try {
            $response = $this->client->send($sendGridMail);
        } catch (\Exception $exception) {
            throw new TransportException(
                sprintf('Request to SendGrid API failed: %s', $exception->getMessage()),
                0,
                $exception
            );
        }

        $statusCode = $response->statusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new TransportException(
                sprintf(
                    'SendGrid API returned status %d: %s',
                    $statusCode,
                    $response->body()
                )
            );
        }
    }

    public function __toString(): string
    {
        return 'sendgrid';
    }
}
```

**Step 4: Run tests to verify they pass**

```bash
cd /home/jones/dev/northcloud-laravel && vendor/bin/pest tests/Unit/Mail/Transport/SendGridTransportTest.php
```

Expected: All 6 tests PASS.

**Step 5: Commit**

```bash
cd /home/jones/dev/northcloud-laravel
git add src/Mail/Transport/SendGridTransport.php tests/Unit/Mail/Transport/SendGridTransportTest.php
git commit -m "feat: add SendGridTransport implementing Symfony AbstractTransport"
```

---

### Task 4: Register SendGrid transport in service provider

**Files:**
- Modify: `/home/jones/dev/northcloud-laravel/src/NorthCloudServiceProvider.php`
- Modify: `/home/jones/dev/northcloud-laravel/tests/Feature/ServiceProviderTest.php`

**Step 1: Write the failing tests**

Add to `tests/Feature/ServiceProviderTest.php`:

```php
it('registers sendgrid mail transport when API key is configured', function () {
    config(['northcloud.mail.sendgrid.api_key' => 'SG.test-key']);

    // Re-boot the service provider to pick up the config
    $provider = new \JonesRussell\NorthCloud\NorthCloudServiceProvider($this->app);
    $provider->boot();

    // Verify the mailer config was added
    expect(config('mail.mailers.sendgrid'))->toBe(['transport' => 'sendgrid']);
    expect(config('mail.default'))->toBe('sendgrid');
});

it('does not register sendgrid transport when API key is null', function () {
    config(['northcloud.mail.sendgrid.api_key' => null]);

    $provider = new \JonesRussell\NorthCloud\NorthCloudServiceProvider($this->app);
    $provider->boot();

    expect(config('mail.mailers.sendgrid'))->toBeNull();
});

it('does not override default mailer when set_as_default is false', function () {
    config([
        'northcloud.mail.sendgrid.api_key' => 'SG.test-key',
        'northcloud.mail.sendgrid.set_as_default' => false,
        'mail.default' => 'smtp',
    ]);

    $provider = new \JonesRussell\NorthCloud\NorthCloudServiceProvider($this->app);
    $provider->boot();

    expect(config('mail.mailers.sendgrid'))->toBe(['transport' => 'sendgrid']);
    expect(config('mail.default'))->toBe('smtp');
});
```

**Step 2: Run tests to verify they fail**

```bash
cd /home/jones/dev/northcloud-laravel && vendor/bin/pest tests/Feature/ServiceProviderTest.php --filter="sendgrid"
```

Expected: FAIL — sendgrid mailer not being registered.

**Step 3: Add SendGrid registration to service provider**

In `src/NorthCloudServiceProvider.php`, add this use statement at the top:

```php
use Illuminate\Support\Facades\Mail;
use JonesRussell\NorthCloud\Mail\Transport\SendGridTransport;
```

Add a new method:

```php
protected function registerSendGridTransport(): void
{
    $apiKey = config('northcloud.mail.sendgrid.api_key');

    if (! $apiKey) {
        return;
    }

    Mail::extend('sendgrid', function () use ($apiKey) {
        return new SendGridTransport(new \SendGrid($apiKey));
    });

    config(['mail.mailers.sendgrid' => ['transport' => 'sendgrid']]);

    if (config('northcloud.mail.sendgrid.set_as_default', true)) {
        config(['mail.default' => 'sendgrid']);
    }
}
```

Call it at the top of the `boot()` method:

```php
public function boot(): void
{
    $this->registerSendGridTransport();

    // ... existing boot code
}
```

**Step 4: Run tests to verify they pass**

```bash
cd /home/jones/dev/northcloud-laravel && vendor/bin/pest tests/Feature/ServiceProviderTest.php --filter="sendgrid"
```

Expected: All 3 tests PASS.

**Step 5: Run all existing tests to check for regressions**

```bash
cd /home/jones/dev/northcloud-laravel && vendor/bin/pest
```

Expected: All tests PASS.

**Step 6: Commit**

```bash
cd /home/jones/dev/northcloud-laravel
git add src/NorthCloudServiceProvider.php tests/Feature/ServiceProviderTest.php
git commit -m "feat: register SendGrid mail transport in service provider"
```

---

### Task 5: Run Pint and tag release

**Files:**
- Possibly: any files with formatting issues

**Step 1: Run Pint**

```bash
cd /home/jones/dev/northcloud-laravel && vendor/bin/pint --dirty
```

Expected: Any formatting fixes applied.

**Step 2: Run full test suite**

```bash
cd /home/jones/dev/northcloud-laravel && vendor/bin/pest
```

Expected: All tests PASS.

**Step 3: Commit formatting fixes (if any)**

```bash
cd /home/jones/dev/northcloud-laravel
git add -A
git commit -m "style: apply pint formatting"
```

**Step 4: Bump version in composer.json**

Change `"version": "0.3.1"` to `"version": "0.4.0"` in composer.json (new feature = minor bump).

**Step 5: Commit and tag**

```bash
cd /home/jones/dev/northcloud-laravel
git add composer.json
git commit -m "chore: bump version to 0.4.0"
git tag v0.4.0
```

---

### Task 6: Update Movies of War to use new northcloud-laravel

**Files:**
- Modify: `/home/jones/dev/movies-of-war.com/composer.json`
- Modify: `/home/jones/dev/movies-of-war.com/.env.example`

**Step 1: Update composer dependency**

```bash
cd /home/jones/dev/movies-of-war.com && ddev composer update jonesrussell/northcloud-laravel
```

**Step 2: Add SENDGRID_API_KEY to .env.example**

In `.env.example`, after the `MAIL_FROM_NAME` line, add:

```
SENDGRID_API_KEY=
```

**Step 3: Add production env vars**

Add to the production `.env` file:

```
SENDGRID_API_KEY=<your-sendgrid-api-key>
MAIL_FROM_ADDRESS=noreply@movies-of-war.com
MAIL_FROM_NAME="Movies of War"
```

**Step 4: Run existing MoW tests to verify no regressions**

```bash
cd /home/jones/dev/movies-of-war.com && ddev artisan test --compact
```

Expected: All tests PASS.

**Step 5: Commit**

```bash
cd /home/jones/dev/movies-of-war.com
git add composer.json composer.lock .env.example
git commit -m "feat: integrate northcloud-laravel v0.4 SendGrid mail transport"
```

---

### Task 7: Verify SendGrid integration works end-to-end

**Step 1: Verify mail config in MoW**

```bash
cd /home/jones/dev/movies-of-war.com && ddev artisan tinker --execute="echo config('mail.default');"
```

Expected: `sendgrid` (if SENDGRID_API_KEY is set in local .env) or `log` (if not set locally).

**Step 2: Test send in production**

After deployment, use tinker to send a test email:

```php
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

Mail::raw('Test email from Movies of War via SendGrid', function (Message $message) {
    $message->to('your-email@example.com')
            ->subject('SendGrid Integration Test');
});
```

**Step 3: Verify in SendGrid dashboard**

Check SendGrid Activity Feed to confirm the email was accepted and delivered.
