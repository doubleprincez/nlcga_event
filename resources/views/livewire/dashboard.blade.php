<?php

use App\Models\Member;
use App\Models\ConferenceMember;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\Event;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public int $totalMembers = 0;
    public int $totalUsers = 0;
    public int $totalEvents = 0;
    public int $totalConferenceMembers = 0;
    public int $totalRegistrations = 0;
    public int $checkedIn = 0;
    public float $totalRevenue = 0;

    public function mount(): void
    {
        $this->totalMembers          = Member::count();
        $this->totalUsers            = User::count();
        $this->totalEvents           = Event::count();
        $this->totalConferenceMembers = ConferenceMember::count();
        $this->totalRegistrations    = Registration::count();
        $this->checkedIn             = Registration::where('is_checked_in', true)->count();
        $this->totalRevenue          = Payment::where('status', 'success')->sum('amount_paid_kobo') / 100;
    }
}; ?>

<div>
    <flux:heading size="xl" class="mb-6">Dashboard</flux:heading>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <flux:card>
            <div class="border-b border-zinc-200 dark:border-zinc-700 mb-4 pb-4">
                <div class="flex items-center justify-between">
                    <flux:subheading>Total Members</flux:subheading>
                    <flux:icon.users class="size-5 text-blue-500" />
                </div>
            </div>
            <div>
                <flux:heading size="xl">{{ number_format($totalMembers) }}</flux:heading>
            </div>
        </flux:card>

        <flux:card>
            <div class="border-b border-zinc-200 dark:border-zinc-700 mb-4 pb-4">
                <div class="flex items-center justify-between">
                    <flux:subheading>Conference Members</flux:subheading>
                    <flux:icon.user-group class="size-5 text-purple-500" />
                </div>
            </div>
            <div>
                <flux:heading size="xl">{{ number_format($totalConferenceMembers) }}</flux:heading>
            </div>
        </flux:card>

        <flux:card>
            <div class="border-b border-zinc-200 dark:border-zinc-700 mb-4 pb-4">
                <div class="flex items-center justify-between">
                    <flux:subheading>Registrations</flux:subheading>
                    <flux:icon.clipboard-document-check class="size-5 text-green-500" />
                </div>
            </div>
            <div>
                <flux:heading size="xl">{{ number_format($totalRegistrations) }}</flux:heading>
                <flux:subheading class="text-xs text-green-600">{{ $checkedIn }} checked in</flux:subheading>
            </div>
        </flux:card>

        <flux:card>
            <div class="border-b border-zinc-200 dark:border-zinc-700 mb-4 pb-4">
                <div class="flex items-center justify-between">
                    <flux:subheading>Revenue</flux:subheading>
                    <flux:icon.banknotes class="size-5 text-yellow-500" />
                </div>
            </div>
            <div>
                <flux:heading size="xl">₦{{ number_format($totalRevenue, 2) }}</flux:heading>
            </div>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <flux:card>
            <div class="border-b border-zinc-200 dark:border-zinc-700 mb-4 pb-4">
                <div class="flex items-center justify-between">
                    <flux:subheading>Total Events</flux:subheading>
                    <flux:icon.calendar class="size-5 text-indigo-500" />
                </div>
            </div>
            <div>
                <flux:heading size="xl">{{ number_format($totalEvents) }}</flux:heading>
                <div class="mt-2">
                    <flux:button variant="ghost" size="sm" href="{{ route('events.index') }}" wire:navigate>
                        View all →
                    </flux:button>
                </div>
            </div>
        </flux:card>

        @if(auth()->user()->isAdmin())
        <flux:card>
            <div class="border-b border-zinc-200 dark:border-zinc-700 mb-4 pb-4">
                <div class="flex items-center justify-between">
                    <flux:subheading>Total Users</flux:subheading>
                    <flux:icon.user-circle class="size-5 text-rose-500" />
                </div>
            </div>
            <div>
                <flux:heading size="xl">{{ number_format($totalUsers) }}</flux:heading>
                <div class="mt-2">
                    <flux:button variant="ghost" size="sm" href="{{ route('users.index') }}" wire:navigate>
                        View all →
                    </flux:button>
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="border-b border-zinc-200 dark:border-zinc-700 mb-4 pb-4">
                <flux:subheading>Quick Actions</flux:subheading>
            </div>
            <div class="flex flex-col gap-2">
                <flux:button variant="primary" size="sm" href="{{ route('members.create') }}" wire:navigate>
                    + Add Member
                </flux:button>
                <flux:button variant="filled" size="sm" href="{{ route('events.create') }}" wire:navigate>
                    + Create Event
                </flux:button>
                <flux:button variant="outline" size="sm" href="{{ route('registrations.create') }}" wire:navigate>
                    + Register Attendee
                </flux:button>
            </div>
        </flux:card>
        @endif
    </div>
</div>
