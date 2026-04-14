<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeUserCommand extends Command
{
    protected $signature = 'scraper:make-user
                            {email : The user\'s email address}
                            {password : The user\'s password}
                            {--name= : Optional display name}';

    protected $description = 'Create an admin user for the StreetEasy Scraper dashboard';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        $name = $this->option('name') ?: explode('@', $email)[0];

        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists.");
            return self::FAILURE;
        }

        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("Created user: {$user->email} (id: {$user->id})");
        return self::SUCCESS;
    }
}
