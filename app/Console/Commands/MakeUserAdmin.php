<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeUserAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-admin {email?} {--revoke : Revoke admin access}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant or revoke admin access for a user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Enter user email');
        $revoke = $this->option('revoke');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error('User with email \''.$email.'\' not found.');

            return self::FAILURE;
        }

        $this->info("Found user: {$user->name} ({$user->email})");
        $this->info('Current admin status: '.($user->is_admin ? 'Yes' : 'No'));

        if ($revoke) {
            if (! $user->is_admin) {
                $this->warn('User is not an admin.');

                return self::SUCCESS;
            }

            $user->is_admin = false;
            $user->save();

            $this->info("Admin access revoked for {$user->email}");
        } else {
            if ($user->is_admin) {
                $this->warn('User is already an admin.');

                return self::SUCCESS;
            }

            $user->is_admin = true;
            $user->save();

            $this->info("Admin access granted to {$user->email}");
        }

        return self::SUCCESS;
    }
}
