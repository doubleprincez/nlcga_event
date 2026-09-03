<?php

use App\Models\Event;
use App\Models\EventType;
use App\Models\Member;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public Event $event;
    public ?string $title = '';
    public ?string $description = '';
    public ?string $event_date = '';
    public ?string $event_time = '';
    public ?string $status = 'draft';
    public ?string $currency = 'NGN';
    public ?string $amount = '0';
    public bool $charge = false;
    public ?string $meeting_link = '';
    public ?string $tags = '';
    public ?int $eventtype_id = null;
    public ?int $member_id = null;

    public function mount(Event $event): void
    {
        if (!auth()->user()->isAdmin()) abort(403, 'Unauthorized.');
        $this->event = $event;
        $this->fill($event->only([
            'title', 'description', 'status', 'currency', 'meeting_link',
            'tags', 'eventtype_id', 'member_id', 'charge',
        ]));
        $this->event_date = $event->event_date ?? '';
        $this->event_time = $event->event_time ?? '';
        $this->amount = (string) $event->amount;
    }

    public function save(): void
    {
        $this->validate([
            'title'  => 'required|string|max:255',
            'status' => 'required|in:draft,published,cancelled',
        ]);

        $this->event->update([
            'title'       => $this->title,
            'description' => $this->description,
            'event_date'  => $this->event_date ?: null,
            'event_time'  => $this->event_time ?: null,
            'status'      => $this->status,
            'currency'    => $this->currency,
            'amount'      => $this->amount,
            'charge'      => $this->charge,
            'meeting_link' => $this->meeting_link,
            'tags'        => $this->tags,
            'eventtype_id' => $this->eventtype_id,
            'member_id'   => $this->member_id,
        ]);

        session()->flash('success', 'Event updated.');
        $this->redirect(route('events.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'eventTypes' => EventType::orderBy('title')->get(),
            'members'    => Member::orderBy('company_name')->get(['id', 'company_name']),
        ];
    }
}; ?>

<div>
    <div class="flex items-center gap-3 mb-6">
        <flux:button variant="ghost" href="{{ route('events.index') }}" wire:navigate icon="arrow-left" />
        <flux:heading size="xl">Edit Event</flux:heading>
    </div>
    <form wire:submit="save" class="max-w-3xl space-y-4">
        <flux:card>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:field class="md:col-span-2">
                    <flux:label>Title *</flux:label>
                    <flux:input wire:model="title" />
                    <flux:error name="title" />
                </flux:field>
                <flux:field>
                    <flux:label>Event Type</flux:label>
                    <flux:select wire:model="eventtype_id">
                        <flux:select.option value="">— None —</flux:select.option>
                        @foreach($eventTypes as $type)
                            <flux:select.option value="{{ $type->id }}">{{ $type->title }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:label>Organiser</flux:label>
                    <flux:select wire:model="member_id">
                        <flux:select.option value="">— None —</flux:select.option>
                        @foreach($members as $m)
                            <flux:select.option value="{{ $m->id }}">{{ $m->company_name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:label>Date</flux:label>
                    <flux:input type="date" wire:model="event_date" />
                </flux:field>
                <flux:field>
                    <flux:label>Time</flux:label>
                    <flux:input type="time" wire:model="event_time" />
                </flux:field>
                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model="status">
                        <flux:select.option value="draft">Draft</flux:select.option>
                        <flux:select.option value="published">Published</flux:select.option>
                        <flux:select.option value="cancelled">Cancelled</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:label>Meeting Link</flux:label>
                    <flux:input wire:model="meeting_link" />
                </flux:field>
                <flux:field>
                    <flux:checkbox wire:model="charge" label="Paid Event" />
                </flux:field>
                <flux:field>
                    <flux:label>Amount</flux:label>
                    <flux:input type="number" wire:model="amount" step="0.01" />
                </flux:field>
                <flux:field class="md:col-span-2">
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="description" rows="4" />
                </flux:field>
            </div>
        </flux:card>
        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Update Event</flux:button>
            <flux:button type="button" variant="ghost" href="{{ route('events.index') }}" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>




