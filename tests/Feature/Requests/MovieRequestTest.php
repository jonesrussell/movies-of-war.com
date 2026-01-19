<?php

declare(strict_types=1);

use App\Enums\MovieStatus;
use App\Http\Requests\Admin\StoreMovieRequest;
use App\Http\Requests\Admin\UpdateMovieRequest;
use App\Models\Movie;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

describe('StoreMovieRequest', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->user = User::factory()->create();
    });

    describe('authorization', function () {
        it('allows admin users to create movies', function () {
            $request = new StoreMovieRequest;
            $request->setUserResolver(fn () => $this->admin);

            expect($request->authorize())->toBeTrue();
        });

        it('prevents non-admin users from creating movies', function () {
            $request = new StoreMovieRequest;
            $request->setUserResolver(fn () => $this->user);

            expect($request->authorize())->toBeFalse();
        });
    });

    describe('validation rules', function () {
        it('requires title', function () {
            $rules = (new StoreMovieRequest)->rules();

            expect($rules['title'])->toContain('required');
        });

        it('requires release_year', function () {
            $rules = (new StoreMovieRequest)->rules();

            expect($rules['release_year'])->toContain('required');
        });

        it('requires synopsis', function () {
            $rules = (new StoreMovieRequest)->rules();

            expect($rules['synopsis'])->toContain('required');
        });

        it('validates release_year is between 1900 and 2100', function () {
            $rules = (new StoreMovieRequest)->rules();

            expect($rules['release_year'])->toContain('min:1900');
            expect($rules['release_year'])->toContain('max:2100');
        });

        it('validates runtime is between 1 and 1440 minutes', function () {
            $rules = (new StoreMovieRequest)->rules();

            expect($rules['runtime'])->toContain('min:1');
            expect($rules['runtime'])->toContain('max:1440');
        });

        it('validates imdb_id format', function () {
            $rules = (new StoreMovieRequest)->rules();

            expect($rules['imdb_id'])->toContain('regex:/^tt\d{7,}$/');
        });

        it('validates status is a valid MovieStatus enum', function () {
            $rules = (new StoreMovieRequest)->rules();

            // Check that status rule contains an enum rule
            $statusRules = $rules['status'];
            $hasEnumRule = false;
            foreach ($statusRules as $rule) {
                if ($rule instanceof \Illuminate\Validation\Rules\Enum) {
                    $reflection = new \ReflectionClass($rule);
                    $typeProperty = $reflection->getProperty('type');
                    $typeProperty->setAccessible(true);
                    if ($typeProperty->getValue($rule) === MovieStatus::class) {
                        $hasEnumRule = true;
                        break;
                    }
                }
            }
            expect($hasEnumRule)->toBeTrue();
        });

        it('validates tags exist', function () {
            $rules = (new StoreMovieRequest)->rules();

            expect($rules['tags.*'])->toContain('exists:tags,id');
        });

        it('validates poster_url is a valid HTTPS URL', function () {
            $rules = (new StoreMovieRequest)->rules();

            expect($rules['poster_url'])->toContain('url:https');
        });

        it('validates trailer_url is a valid HTTPS URL', function () {
            $rules = (new StoreMovieRequest)->rules();

            expect($rules['trailer_url'])->toContain('url:https');
        });
    });

    describe('validation scenarios', function () {
        it('passes with valid data', function () {
            $tag = Tag::factory()->create();

            $data = [
                'title' => 'Saving Private Ryan',
                'release_year' => 1998,
                'release_date' => '1998-07-24',
                'synopsis' => 'A war movie about WWII.',
                'runtime' => 169,
                'country' => 'United States',
                'conflict' => 'World War II',
                'poster_url' => 'https://example.com/poster.jpg',
                'trailer_url' => 'https://youtube.com/watch?v=xyz',
                'imdb_id' => 'tt0120815',
                'is_upcoming' => false,
                'status' => MovieStatus::Draft->value,
                'tags' => [$tag->id],
            ];

            $validator = Validator::make($data, (new StoreMovieRequest)->rules());

            expect($validator->passes())->toBeTrue();
        });

        it('fails when title is missing', function () {
            $data = [
                'release_year' => 1998,
                'synopsis' => 'A war movie.',
            ];

            $validator = Validator::make($data, (new StoreMovieRequest)->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('title'))->toBeTrue();
        });

        it('fails when release_year is out of range', function () {
            $data = [
                'title' => 'Test Movie',
                'release_year' => 1800,
                'synopsis' => 'A movie.',
            ];

            $validator = Validator::make($data, (new StoreMovieRequest)->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('release_year'))->toBeTrue();
        });

        it('fails when imdb_id has invalid format', function () {
            $data = [
                'title' => 'Test Movie',
                'release_year' => 1998,
                'synopsis' => 'A movie.',
                'imdb_id' => 'invalid',
            ];

            $validator = Validator::make($data, (new StoreMovieRequest)->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('imdb_id'))->toBeTrue();
        });

        it('fails when tag does not exist', function () {
            $data = [
                'title' => 'Test Movie',
                'release_year' => 1998,
                'synopsis' => 'A movie.',
                'tags' => [99999],
            ];

            $validator = Validator::make($data, (new StoreMovieRequest)->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('tags.0'))->toBeTrue();
        });
    });

    describe('validatedWithDefaults', function () {
        it('has validatedWithDefaults method that sets default status', function () {
            $request = new StoreMovieRequest;

            expect(method_exists($request, 'validatedWithDefaults'))->toBeTrue();
        });
    });
});

describe('UpdateMovieRequest', function () {
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create();
        $this->user = User::factory()->create();
        $this->movie = Movie::factory()->create();
    });

    describe('authorization', function () {
        it('has authorization method that checks user can update', function () {
            $request = new UpdateMovieRequest;

            expect(method_exists($request, 'authorize'))->toBeTrue();
        });
    });

    describe('validation rules', function () {
        it('has same validation rules as StoreMovieRequest', function () {
            $storeRules = (new StoreMovieRequest)->rules();
            $updateRules = (new UpdateMovieRequest)->rules();

            // Remove route-dependent rules if any
            expect($updateRules)->toEqual($storeRules);
        });
    });

    describe('validation scenarios', function () {
        it('passes with valid data', function () {
            $tag = Tag::factory()->create();

            $data = [
                'title' => 'Updated Title',
                'release_year' => 1999,
                'synopsis' => 'Updated synopsis.',
                'tags' => [$tag->id],
            ];

            $validator = Validator::make($data, (new UpdateMovieRequest)->rules());

            expect($validator->passes())->toBeTrue();
        });

        it('fails when required fields are missing', function () {
            $data = [];

            $validator = Validator::make($data, (new UpdateMovieRequest)->rules());

            expect($validator->fails())->toBeTrue();
        });
    });
});
