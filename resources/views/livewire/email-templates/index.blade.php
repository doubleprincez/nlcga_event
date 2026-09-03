<?php

use App\Models\EmailTemplate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public function with(): array { return ['templates' => EmailTemplate::latest()->get()]; }
    public function delete(int $id): void { EmailTemplate::findOrFail($id)->delete(); session()->flash('success', 'Deleted.'); }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Email Templates</flux:heading>
        <flux:button variant="primary" href="{{ route('email-templates.create') }}" wire:navigate icon="plus">Add Template</flux:button>
    </div>
    @if(session('success'))<flux:callout variant="success" class="mb-4" icon="check-circle">{{ session('success') }}</flux:callout>@endif
    <flux:table>
        <flux:table.columns>
<flux:table.column>Name</flux:table.column>
                <flux:table.column>Subject</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Active</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($templates as $tpl)
                <flux:table.row>
                    <flux:table.cell>{{ $tpl->name }}</flux:table.cell>
                    <flux:table.cell>{{ $tpl->subject ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $tpl->type ?? '—' }}</flux:table.cell>
                    <flux:table.cell><flux:badge color="{{ $tpl->is_active ? 'green' : 'zinc' }}">{{ $tpl->is_active ? 'Yes' : 'No' }}</flux:badge></flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="ghost" href="{{ route('email-templates.edit', $tpl) }}" wire:navigate icon="pencil" />
                            <flux:button size="sm" variant="ghost" wire:click="delete({{ $tpl->id }})" wire:confirm="Delete?" icon="trash" class="text-red-500" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="5" class="text-center text-zinc-400">No templates yet.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>


