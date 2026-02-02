<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\Review;
use App\Models\User;
use Illuminate\Console\Command;

final class CreateMidway1976Review extends Command
{
    protected $signature = 'review:create-midway-1976-jones
                            {--dry-run : Show what would be created without writing}';

    protected $description = 'Create the first Midway (1976) review as jonesrussell42@gmail.com (one-off, prod).';

    private const USER_EMAIL = 'jonesrussell42@gmail.com';

    private const REVIEW_TITLE = 'Midway (1976)';

    private const RATING = 2.0;

    private const MARKDOWN_CONTENT = <<<'MD'
"Midway" wants to be two things at once and never fully commits to either. It wants to be a sweeping war epic in the tradition of Hollywood's star-studded 1970s spectacles, and a meticulous recreation of one of the most decisive naval engagements of the Second World War. The result lands somewhere in no man's land — too dry to stir the emotions, too dramatized to work as history, and yet interesting enough to send you to the library afterward.

The cast is enormous and impressive on paper — **Heston, Fonda, Mifune, Mitchum** — but the film cycles through so many officers on both sides that keeping track of who commands what becomes its own tactical challenge.

The best scenes involve the intelligence war: Rochefort's bathrobe-clad introduction to Nimitz, and the water shortage ruse that confirms "AF" means Midway. However, military leadership treats the intelligence as an uncertain *best guess* rather than definitive proof. When Nimitz tells his intelligence officer he's earned his salary, you believe it. These procedural moments, where clever people argue over fragments of information with everything at stake, are when "Midway" is at its best.

It's less successful when it reaches for emotion. Heston's Matt Garth has a son dating a Japanese-American woman whose family the government interned — a subplot that raises one of the war's great domestic injustices but uses it mainly as a romantic obstacle. When Garth himself dies in the final act, the officers' reaction plays almost comically flat, less like grief and more like mild inconvenience.

The film's use of real combat footage spliced into dramatized sequences is jarring at first, but I eventually stopped fighting it. Some cuts blend better than others, and the archival footage carries a weight no special effect of the era could match. Still, the overall experience feels less like a war movie and more like a dramatized documentary that forgot to include the narrator—long stretches of men standing over maps, debating strategy. If the film had committed to that approach — something closer to the modern Netflix docudrama format with narration bridging the gaps — it might have found its footing.

Roland Emmerich's 2019 "Midway" covers the same battle with sharper character work and modern effects. The older film treats its people as chess pieces in a historical recreation; the newer one makes you care about who's in the cockpits. But the 1976 version did something the remake didn't — it made me want to dig deeper.

After the credits, I found myself researching the real Torpedo Squadron 8, the delayed Scout 4 launch, and what happened aboard the *Akagi*. A film that sends you into the history books has done something right, even if it couldn't quite bring that history to life on screen.
MD;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $user = User::query()->where('email', self::USER_EMAIL)->first();
        if (! $user) {
            $this->error('User not found: '.self::USER_EMAIL);

            return self::FAILURE;
        }

        $movie = Movie::query()
            ->where('release_year', 1976)
            ->where(function ($q) {
                $q->where('title', 'Midway')
                    ->orWhere('slug', 'midway')
                    ->orWhere('slug', 'like', 'midway-%');
            })
            ->first();

        if (! $movie) {
            $this->error('Movie not found: Midway (1976). Create the movie first or check slug/release_year.');

            return self::FAILURE;
        }

        $existing = Review::query()
            ->where('user_id', $user->id)
            ->where('movie_id', $movie->id)
            ->first();

        if ($existing) {
            $this->warn('Review already exists for this user and movie (id: '.$existing->id.').');
            if (! $dryRun && $this->confirm('Update it with this content?', false)) {
                $existing->update([
                    'title' => self::REVIEW_TITLE,
                    'rating' => self::RATING,
                    'content' => self::MARKDOWN_CONTENT,
                    'has_spoilers' => false,
                    'is_published' => true,
                ]);
                $this->info('Review updated.');
            }

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info('[DRY RUN] Would create review:');
            $this->line('  User: '.$user->email.' (id: '.$user->id.')');
            $this->line('  Movie: '.$movie->title.' ('.$movie->release_year.') (id: '.$movie->id.')');
            $this->line('  Rating: '.self::RATING);
            $this->line('  Title: '.self::REVIEW_TITLE);

            return self::SUCCESS;
        }

        Review::query()->create([
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'rating' => self::RATING,
            'title' => self::REVIEW_TITLE,
            'content' => self::MARKDOWN_CONTENT,
            'has_spoilers' => false,
            'is_published' => true,
            'helpful_count' => 0,
            'comments_count' => 0,
        ]);

        $this->info('Review created for Midway (1976) as '.self::USER_EMAIL);

        return self::SUCCESS;
    }
}
