<?php

use App\Models\EventType;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public ?string $title = '';
    public ?string $description = '';
    public ?int $editingId = null;
    public bool $showForm = false;

    public function with(): array
    {
        return ['eventTypes' => EventType::withCount('events')->latest()->get()];
    }

    public function create(): void { $this->reset(['title', 'description', 'editingId']); $this->showForm = true; }

    public function edit(int $id): void
    {
        $et = EventType::findOrFail($id);
        $this->editingId = $id;
        $this->title = $et->title;
        $this->description = $et->description ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate(['title' => 'required|string|max:255']);
        $data = ['title' => $this->title, 'description' => $this->description];
        $this->editingId ? EventType::findOrFail($this->editingId)->update($data) : EventType::create($data);
        $this->reset(['title', 'description', 'editingId', 'showForm']);
        session()->flash('success', 'Event type saved.');
    }

    public function delete(int $id): void { EventType::findOrFail($id)->delete(); session()->flash('success', 'Deleted.'); }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Event Types</flux:heading>
        <flux:button variant="primary" wire:click="create" icon="plus">Add Type</flux:button>
    </div>
    @if(session('success'))
        <flux:callout variant="success" class="mb-4" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif
    @if($showForm)
        <flux:card class="max-w-lg mb-6">
            <div class="border-b border-zinc-200 dark:border-zinc-700 mb-4 pb-4"><flux:heading>{{ $editingId ? 'Edit' : 'New' }} Event Type</flux:heading></div>
            <div>
                <form wire:submit="save" class="space-y-3">
                    <flux:field><flux:label>Title *</flux:label><flux:input wire:model="title" /><flux:error name="title" /></flux:field>
                    <flux:field><flux:label>Description</flux:label><flux:textarea wire:model="description" rows="2" /></flux:field>
                    <div class="flex gap-2">
                        <flux:button type="submit" variant="primary">Save</flux:button>
                        <flux:button type="button" variant="ghost" wire:click="$set('showForm', false)">Cancel</flux:button>
                    </div>
                </form>
            </div>
        </flux:card>
    @endif
    <flux:table>
        <flux:table.columns>
<flux:table.column>Title</flux:table.column>
                <flux:table.column>Events</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($eventTypes as $et)
                <flux:table.row>
                    <flux:table.cell>{{ $et->title }}</flux:table.cell>
                    <flux:table.cell>{{ $et->events_count }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="ghost" wire:click="edit({{ $et->id }})" icon="pencil" />
                            <flux:button size="sm" variant="ghost" wire:click="delete({{ $et->id }})" wire:confirm="Delete?" icon="trash" class="text-red-500" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="3" class="text-center text-zinc-400">No event types yet.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>



