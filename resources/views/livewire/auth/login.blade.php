<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.guest')] class extends Component
{
    public ?string $email = '';
    public ?string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email', 'Invalid credentials.');
            return;
        }

        session()->regenerate();
        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <div class="text-center">
        <flux:heading size="xl" class="font-bold text-blue-600">EventMS</flux:heading>
        <flux:subheading>Sign in to your account</flux:subheading>
    </div>

    <form wire:submit="login" class="space-y-4">
        <flux:field>
            <flux:label>Email</flux:label>
            <flux:input type="email" wire:model="email" placeholder="you@example.com" autofocus />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label>Password</flux:label>
            <flux:input type="password" wire:model="password" placeholder="••••••••" />
            <flux:error name="password" />
        </flux:field>

        <div class="flex items-center justify-between">
            <flux:checkbox wire:model="remember" label="Remember me" />
        </div>

        <flux:button type="submit" variant="primary" class="w-full">Sign in</flux:button>
    </form>
</div>



