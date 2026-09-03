<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
    }

    public function updateProfile(): void
    {
        $user = auth()->user();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        session()->flash('profile_success', 'Profile information updated successfully.');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        session()->flash('password_success', 'Password updated successfully.');
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-6">Account Settings</flux:heading>

    <div class="max-w-2xl space-y-6">
        <!-- Profile Info Card -->
        <flux:card>
            <flux:heading size="lg" class="mb-1">Profile Information</flux:heading>
            <flux:subheading class="mb-4">Update your account's profile name and email address.</flux:subheading>

            @if (session('profile_success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 text-sm font-semibold rounded-lg border border-green-300">
                    {{ session('profile_success') }}
                </div>
            @endif

            <form wire:submit="updateProfile" class="space-y-4">
                <flux:field>
                    <flux:label>Name</flux:label>
                    <flux:input wire:model="name" required />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Email Address</flux:label>
                    <flux:input type="email" wire:model="email" required />
                    <flux:error name="email" />
                </flux:field>

                <div class="flex items-center gap-3 pt-2">
                    <flux:button type="submit" variant="primary">Save Changes</flux:button>
                </div>
            </form>
        </flux:card>

        <!-- Update Password Card -->
        <flux:card>
            <flux:heading size="lg" class="mb-1">Update Password</flux:heading>
            <flux:subheading class="mb-4">Ensure your account is using a long, random password to stay secure.</flux:subheading>

            @if (session('password_success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 text-sm font-semibold rounded-lg border border-green-300">
                    {{ session('password_success') }}
                </div>
            @endif

            <form wire:submit="updatePassword" class="space-y-4">
                <flux:field>
                    <flux:label>Current Password</flux:label>
                    <flux:input type="password" wire:model="current_password" required />
                    <flux:error name="current_password" />
                </flux:field>

                <flux:field>
                    <flux:label>New Password</flux:label>
                    <flux:input type="password" wire:model="password" required />
                    <flux:error name="password" />
                </flux:field>

                <flux:field>
                    <flux:label>Confirm New Password</flux:label>
                    <flux:input type="password" wire:model="password_confirmation" required />
                    <flux:error name="password_confirmation" />
                </flux:field>

                <div class="flex items-center gap-3 pt-2">
                    <flux:button type="submit" variant="primary">Update Password</flux:button>
                </div>
            </form>
        </flux:card>
    </div>
</div>
