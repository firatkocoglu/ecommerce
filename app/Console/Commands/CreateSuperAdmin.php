<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:super-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a super admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating super admin user...');
        $name = $this->ask('Enter the name of the super admin');
        $email = $this->ask('Enter the email of the super admin');
        $password = $this->secret('Enter the password of the super admin');

        if (empty($name) || empty($email) || empty($password)) {
            $this->error('Name, email, and password are required.');
            return self::FAILURE;
        }

        // Check if user already exists
        if (Admin::where('email', $email)->exists()) {
            $this->error('An admin with this email already exists.');
            return self::FAILURE;
        }

        $admin = Admin::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
            'is_super_admin' => true,
        ]);

        $this->info("Super admin created successfully!");
        $this->info("ID: {$admin->id}");
        $this->info("Email: {$admin->email}");

        return self::SUCCESS;
    }
}
