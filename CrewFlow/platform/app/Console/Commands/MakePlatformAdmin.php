<?php

namespace App\Console\Commands;

use App\Models\PlatformUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class MakePlatformAdmin extends Command
{
    protected $signature = 'platform:make-admin';

    protected $description = 'Create a platform user and assign it a role. Interactive — never accepts credentials as plain command arguments.';

    public function handle(): int
    {
        $name = $this->ask('Name');
        $email = $this->ask('Email');

        if (PlatformUser::where('email', $email)->exists()) {
            $this->error("A platform user with email {$email} already exists.");

            return self::FAILURE;
        }

        $password = $this->secret('Password (min 8 characters)');

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $availableRoles = Role::pluck('name')->all();

        if ($availableRoles === []) {
            $this->error('No roles found. Run the seeder first: php artisan db:seed --class=PlatformDatabaseSeeder');

            return self::FAILURE;
        }

        $roleName = $this->choice('Role', $availableRoles, 0);

        $user = PlatformUser::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole($roleName);

        $this->info("Platform user {$email} created and assigned the '{$roleName}' role.");

        return self::SUCCESS;
    }
}
