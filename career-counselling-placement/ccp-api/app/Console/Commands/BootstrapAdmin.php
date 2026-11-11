<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class BootstrapAdmin extends Command
{
    protected $signature = 'ccp:bootstrap-admin';

    protected $description = 'Create the first active administrator without a demo account';

    public function handle(): int
    {
        if (User::where('role', 'admin')->where('status', 'active')->exists()) {
            $this->error('An active administrator already exists. Ask an administrator to create another account.');

            return self::FAILURE;
        }

        $data = Validator::make([
            'name' => $this->ask('Admin name'),
            'email' => strtolower(trim($this->ask('Admin email'))),
            'password' => $this->secret('Password'),
            'password_confirmation' => $this->secret('Confirm password'),
        ], [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ])->validate();

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);

        $this->info('Initial administrator created. Sign in with the account you just configured.');

        return self::SUCCESS;
    }
}
