<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ChangeUserPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:change-password {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change a user\'s password';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Enter user email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error('User with email \''.$email.'\' not found.');

            return self::FAILURE;
        }

        $this->info("Found user: {$user->name} ({$user->email})");
        $this->info('Is admin: '.($user->is_admin ? 'Yes' : 'No'));

        $password = $this->secret('Enter new password');
        $passwordConfirmation = $this->secret('Confirm new password');

        if ($password !== $passwordConfirmation) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters long.');

            return self::FAILURE;
        }

        $user->password = Hash::make($password);
        $user->save();

        $this->info("Password successfully changed for {$user->email}");

        return self::SUCCESS;
    }
}
