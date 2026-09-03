<?php

use App\Models\WhatsAppTemplate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public function with(): array { return ['templates' => WhatsAppTemplate::latest()->get()]; }
    public function delete(int $id): void { WhatsAppTemplate::findOrFail($id)->delete(); session()->flash('success', 'Deleted.'); }
    public function toggleStatus(int $id): void
    {
        $t = WhatsAppTemplate::findOrFail($id);
        $t->update(['status' => $t->status === 'active' ? 'inactive' : 'active']);
    }
}; ?>

<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">WhatsApp Templates</flux:heading>
        <flux:button variant="primary" href="{{ route('whatsapp-templates.create') }}" wire:navigate icon="plus">Add Template</flux:button>
    </div>
    @if(session('success'))<flux:callout variant="success" class="mb-4" icon="check-circle">{{ session('success') }}</flux:callout>@endif
    <flux:table>
        <flux:table.columns>
<flux:table.column>Name</flux:table.column>
                <flux:table.column>Display Name</flux:table.column>
                <flux:table.column>Category</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse($templates as $tpl)
                <flux:table.row>
                    <flux:table.cell class="font-mono text-xs">{{ $tpl->name }}</flux:table.cell>
                    <flux:table.cell>{{ $tpl->display_name }}</flux:table.cell>
                    <flux:table.cell>{{ $tpl->category }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" wire:click="toggleStatus({{ $tpl->id }})">
                            <flux:badge color="{{ $tpl->status === 'active' ? 'green' : 'zinc' }}">{{ ucfirst($tpl->status) }}</flux:badge>
                        </flux:button>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="ghost" href="{{ route('whatsapp-templates.edit', $tpl) }}" wire:navigate icon="pencil" />
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


