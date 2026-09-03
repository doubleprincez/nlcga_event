<?php

use App\Models\Event;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout("components.layouts.app")] class extends Component
{
    use WithPagination;
    public ?string $search = "";

    public function with(): array
    {
        return [
            "events" => Event::with(["member", "eventType"])
                ->when($this->search, fn($q) => $q->where("title", "like", "%{$this->search}%"))
                ->latest()->paginate(15),
        ];
    }

    public function delete(int $id): void
    {
        if (!auth()->user()->isAdmin()) abort(403, "Unauthorized.");
        Event::findOrFail($id)->delete();
        session()->flash("success", "Event deleted.");
    }

    public function updatedSearch(): void { $this->resetPage(); }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Events</flux:heading>
        @if(auth()->user()->isAdmin())
        <flux:button variant="primary" href="{{ route('events.create') }}" wire:navigate icon="plus">Add Event</flux:button>
        @endif
    </div>
    @if(session("success"))
        <flux:callout variant="success" class="mb-4" icon="check-circle">{{ session("success") }}</flux:callout>
    @endif
    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search events..." class="max-w-sm mb-4" icon="magnifying-glass" />
    <flux:table>
        <flux:table.columns>
                <flux:table.column>Title</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Date</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                @if(auth()->user()->isAdmin())
                <flux:table.column>Actions</flux:table.column>
                @endif
        </flux:table.columns>
        <flux:table.rows>
            @forelse($events as $event)
                <flux:table.row>
                    <flux:table.cell>{{ $event->title }}</flux:table.cell>
                    <flux:table.cell>{{ $event->eventType?->title ?? "N/A" }}</flux:table.cell>
                    <flux:table.cell>{{ $event->event_date ? \Carbon\Carbon::parse($event->event_date)->format("M d, Y") : "N/A" }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $event->status === 'published' ? 'green' : ($event->status === 'cancelled' ? 'red' : 'yellow') }}">
                            {{ ucfirst($event->status) }}
                        </flux:badge>
                    </flux:table.cell>
                    @if(auth()->user()->isAdmin())
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="ghost" href="{{ route('events.edit', $event) }}" wire:navigate icon="pencil" />
                            <flux:button size="sm" variant="ghost" wire:click="delete({{ $event->id }})" wire:confirm="Delete this event?" icon="trash" class="text-red-500" />
                        </div>
                    </flux:table.cell>
                    @endif
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center text-zinc-400">No events found.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    <div class="mt-4">{{ $events->links() }}</div>
</div>

