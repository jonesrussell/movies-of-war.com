<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RefreshTmdbPersonJob;
use App\Models\Person;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshTmdbPeople extends Command
{
    protected $signature = 'tmdb:refresh-people
                            {--limit=50 : Maximum number of people to queue}
                            {--force-backfill : Only people with tmdb_last_synced_at null}
                            {--force : Ignore cadence; refresh all people}
                            {--dry-run : List people that would be refreshed without dispatching jobs}';

    protected $description = 'Queue TMDB refresh jobs for stale people (cadence + max-age)';

    public function handle(): int
    {
        $start = now();
        $limit = (int) $this->option('limit');
        $forceBackfill = $this->option('force-backfill');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $people = $this->resolveCandidates($limit, $force, $forceBackfill);

        if ($people->isEmpty()) {
            $this->info('No people to refresh.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->table(
                ['ID', 'Name', 'Last synced'],
                $people->map(fn (Person $p) => [
                    $p->id,
                    $p->name,
                    $p->tmdb_last_synced_at?->toDateTimeString() ?? 'never',
                ])->all()
            );
            $this->info("Dry run: {$people->count()} person(s) would be refreshed.");

            return self::SUCCESS;
        }

        foreach ($people as $person) {
            RefreshTmdbPersonJob::dispatch($person);
        }

        $duration = $start->diffInSeconds(now());
        $count = $people->count();

        Log::info('tmdb:refresh-people completed', [
            'jobs_dispatched' => $count,
            'duration_seconds' => $duration,
        ]);

        $this->info("Dispatched {$count} refresh job(s) in {$duration}s.");

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Person>
     */
    private function resolveCandidates(int $limit, bool $force, bool $forceBackfill): \Illuminate\Support\Collection
    {
        if ($force) {
            return Person::query()
                ->orderBy('id')
                ->limit($limit)
                ->get();
        }

        if ($forceBackfill) {
            return Person::query()
                ->whereNull('tmdb_last_synced_at')
                ->orderBy('id')
                ->limit($limit)
                ->get();
        }

        return Person::query()
            ->staleForTmdbRefresh($limit)
            ->get();
    }
}
