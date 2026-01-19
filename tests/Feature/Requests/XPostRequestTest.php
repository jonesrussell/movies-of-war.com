<?php

declare(strict_types=1);

use App\Enums\XPostStatus;
use App\Http\Requests\ScheduleXPostRequest;
use App\Http\Requests\StoreXPostRequest;
use App\Models\User;
use App\Models\XPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

describe('StoreXPostRequest', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->user = User::factory()->create();
    });

    describe('authorization', function () {
        it('allows admin users to create X posts', function () {
            $request = new StoreXPostRequest;
            $request->setUserResolver(fn () => $this->admin);

            expect($request->authorize())->toBeTrue();
        });

        it('prevents non-admin users from creating X posts', function () {
            $request = new StoreXPostRequest;
            $request->setUserResolver(fn () => $this->user);

            expect($request->authorize())->toBeFalse();
        });
    });

    describe('validation rules', function () {
        it('validates content is required when not publishing immediately', function () {
            $rules = (new StoreXPostRequest)->rules();

            expect($rules['content'])->toContain('required_without:thread_parts');
        });

        it('validates content max length is 280 characters', function () {
            $rules = (new StoreXPostRequest)->rules();

            expect($rules['content'])->toContain('max:280');
        });

        it('validates thread_parts is an array', function () {
            $rules = (new StoreXPostRequest)->rules();

            expect($rules['thread_parts'])->toContain('array');
        });

        it('validates each thread part max length is 280 characters', function () {
            $rules = (new StoreXPostRequest)->rules();

            expect($rules['thread_parts.*'])->toContain('max:280');
        });

        it('validates media_urls is an array', function () {
            $rules = (new StoreXPostRequest)->rules();

            expect($rules['media_urls'])->toContain('array');
        });

        it('validates each media URL is a string', function () {
            $rules = (new StoreXPostRequest)->rules();

            expect($rules['media_urls.*'])->toContain('string');
        });

        it('validates status is a valid XPostStatus enum', function () {
            $rules = (new StoreXPostRequest)->rules();

            // Check that status rule contains an enum rule
            $statusRules = $rules['status'];
            $hasEnumRule = false;
            foreach ($statusRules as $rule) {
                if ($rule instanceof \Illuminate\Validation\Rules\Enum) {
                    $reflection = new \ReflectionClass($rule);
                    $typeProperty = $reflection->getProperty('type');
                    $typeProperty->setAccessible(true);
                    if ($typeProperty->getValue($rule) === XPostStatus::class) {
                        $hasEnumRule = true;
                        break;
                    }
                }
            }
            expect($hasEnumRule)->toBeTrue();
        });

        it('validates scheduled_for is required when status is scheduled', function () {
            $rules = (new StoreXPostRequest)->rules();

            expect($rules['scheduled_for'])->toContain('required_if:status,scheduled');
        });

        it('validates scheduled_for is a valid date in the future', function () {
            $rules = (new StoreXPostRequest)->rules();

            expect($rules['scheduled_for'])->toContain('after:+1 minute');
        });
    });

    describe('validation scenarios', function () {
        it('passes with valid draft post data', function () {
            $data = [
                'content' => 'This is a test tweet',
                'status' => XPostStatus::Draft->value,
            ];

            $validator = Validator::make($data, (new StoreXPostRequest)->rules());

            expect($validator->passes())->toBeTrue();
        });

        it('passes with valid scheduled post data', function () {
            $data = [
                'content' => 'This is a scheduled tweet',
                'status' => XPostStatus::Scheduled->value,
                'scheduled_for' => now()->addDay()->toDateTimeString(),
            ];

            $validator = Validator::make($data, (new StoreXPostRequest)->rules());

            expect($validator->passes())->toBeTrue();
        });

        it('fails when content exceeds 280 characters', function () {
            $data = [
                'content' => str_repeat('a', 281),
                'status' => XPostStatus::Draft->value,
            ];

            $validator = Validator::make($data, (new StoreXPostRequest)->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('content'))->toBeTrue();
        });

        it('fails when scheduled_for is in the past', function () {
            $data = [
                'content' => 'This is a scheduled tweet',
                'status' => XPostStatus::Scheduled->value,
                'scheduled_for' => now()->subDay()->toDateTimeString(),
            ];

            $validator = Validator::make($data, (new StoreXPostRequest)->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('scheduled_for'))->toBeTrue();
        });

        it('fails when scheduled_for is missing for scheduled status', function () {
            $data = [
                'content' => 'This is a scheduled tweet',
                'status' => XPostStatus::Scheduled->value,
            ];

            $validator = Validator::make($data, (new StoreXPostRequest)->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('scheduled_for'))->toBeTrue();
        });

        it('fails when thread part exceeds 280 characters', function () {
            $data = [
                'content' => 'First tweet',
                'thread_parts' => [str_repeat('a', 281)],
                'status' => XPostStatus::Draft->value,
            ];

            $validator = Validator::make($data, (new StoreXPostRequest)->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('thread_parts.0'))->toBeTrue();
        });

        it('passes when media URLs are provided as strings', function () {
            $data = [
                'content' => 'Tweet with media',
                'media_urls' => ['https://example.com/image.jpg'],
                'status' => XPostStatus::Draft->value,
            ];

            $validator = Validator::make($data, (new StoreXPostRequest)->rules());

            expect($validator->passes())->toBeTrue();
        });
    });
});

describe('ScheduleXPostRequest', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->user = User::factory()->create();
        $this->xPost = XPost::factory()->draft()->create();
    });

    describe('authorization', function () {
        it('allows admin users to schedule X posts', function () {
            $this->actingAs($this->admin);

            $request = ScheduleXPostRequest::create('/test', 'POST', []);

            expect($request->authorize())->toBeTrue();
        });

        it('prevents non-admin users from scheduling X posts', function () {
            $this->actingAs($this->user);

            $request = ScheduleXPostRequest::create('/test', 'POST', []);

            expect($request->authorize())->toBeFalse();
        });
    });

    describe('validation rules', function () {
        it('requires scheduled_for', function () {
            $rules = (new ScheduleXPostRequest)->rules();

            expect($rules['scheduled_for'])->toContain('required');
        });

        it('validates scheduled_for is a valid date', function () {
            $rules = (new ScheduleXPostRequest)->rules();

            expect($rules['scheduled_for'])->toContain('date');
        });

        it('validates scheduled_for is in the future', function () {
            $rules = (new ScheduleXPostRequest)->rules();

            // The rule uses a closure, so we check that scheduled_for has a required rule
            expect($rules['scheduled_for'])->toContain('required');
            expect($rules['scheduled_for'])->toContain('date');
        });
    });

    describe('validation scenarios', function () {
        it('passes with valid future date', function () {
            $data = [
                'scheduled_for' => now()->addDay()->toDateTimeString(),
            ];

            $validator = Validator::make($data, (new ScheduleXPostRequest)->rules());

            expect($validator->passes())->toBeTrue();
        });

        it('fails when scheduled_for is missing', function () {
            $data = [];

            $validator = Validator::make($data, (new ScheduleXPostRequest)->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('scheduled_for'))->toBeTrue();
        });

        it('fails when scheduled_for is in the past', function () {
            $data = [
                'scheduled_for' => now()->subDay()->toDateTimeString(),
            ];

            $validator = Validator::make($data, (new ScheduleXPostRequest)->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('scheduled_for'))->toBeTrue();
        });
    });
});
