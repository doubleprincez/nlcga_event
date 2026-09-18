<?php

use App\Models\BotCommunication;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithPagination;

    public ?string $search = '';
    public ?string $directionFilter = '';
    public ?string $selectedPhone = null;
    public bool $showThreadModal = false;

    public function with(): array
    {
        return [
            'messages' => BotCommunication::query()
                ->when($this->search, fn($q) => $q->where('phone', 'like', "%{$this->search}%")->orWhere('message', 'like', "%{$this->search}%"))
                ->when($this->directionFilter, fn($q) => $q->where('direction', $this->directionFilter))
                ->latest()
                ->paginate(25),
            'threadMessages' => $this->selectedPhone
                ? BotCommunication::where('phone', $this->selectedPhone)->orderBy('created_at', 'asc')->get()
                : collect(),
        ];
    }

    public function viewThread(string $phone): void
    {
        $this->selectedPhone = $phone;
        $this->showThreadModal = true;
    }

    public function closeThread(): void
    {
        $this->showThreadModal = false;
        $this->selectedPhone = null;
    }

    public function delete(int $id): void
    {
        BotCommunication::findOrFail($id)->delete();
        session()->flash('success', 'Message deleted successfully.');
    }

    public function clearThread(string $phone): void
    {
        BotCommunication::where('phone', $phone)->delete();
        $this->closeThread();
        session()->flash('success', "Conversation history with {$phone} cleared.");
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
}; ?>

<div class="p-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-4">
        <div>
            <flux:heading size="xl" level="1">Bot Communications</flux:heading>
            <flux:subheading>Monitor live WhatsApp bot inquiries and complete AI responses sent to attendees.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('ai.chat-tester') }}" variant="subtle" size="sm" icon="chat-bubble-left-right">
                AI Chat Tester
            </flux:button>
            <flux:button href="{{ route('ai.training') }}" variant="subtle" size="sm" icon="cog-6-tooth">
                AI Training Prompt
            </flux:button>
        </div>
    </div>

    @if(session('success'))
        <flux:callout variant="success" class="mb-4" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    <div class="flex flex-wrap gap-3 items-center justify-between">
        <div class="flex flex-wrap gap-3 flex-1 max-w-xl">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search phone or message content..." class="flex-1" icon="magnifying-glass" />
            <flux:select wire:model.live="directionFilter" class="w-36">
                <flux:select.option value="">All Directions</flux:select.option>
                <flux:select.option value="inbound">Inbound (User)</flux:select.option>
                <flux:select.option value="outbound">Outbound (Bot)</flux:select.option>
            </flux:select>
        </div>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-40">Phone Number</flux:table.column>
                <flux:table.column class="w-28">Direction</flux:table.column>
                <flux:table.column>Complete Message Content</flux:table.column>
                <flux:table.column class="w-36">Time</flux:table.column>
                <flux:table.column class="w-28 text-right">Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($messages as $msg)
                    <flux:table.row wire:key="msg-{{ $msg->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                        <flux:table.cell class="font-mono text-sm font-semibold">
                            <button wire:click="viewThread('{{ $msg->phone }}')" class="hover:underline text-blue-600 dark:text-blue-400 text-left">
                                {{ $msg->phone }}
                            </button>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="{{ $msg->direction === 'inbound' ? 'blue' : 'green' }}">
                                {{ ucfirst($msg->direction) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-pre-line break-words text-sm py-3 text-zinc-900 dark:text-zinc-100 max-w-2xl leading-relaxed">
                            {{ $msg->message }}
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-zinc-500">
                            {{ $msg->created_at->diffForHumans() }}
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button size="sm" variant="ghost" wire:click="viewThread('{{ $msg->phone }}')" icon="chat-bubble-left-ellipsis" title="View Full Conversation Thread" />
                                <flux:button size="sm" variant="ghost" wire:click="delete({{ $msg->id }})" wire:confirm="Delete this message record?" icon="trash" class="text-red-500 hover:text-red-700" title="Delete" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-8 text-zinc-400">
                            No bot communications found matching your query.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <div class="mt-4">
        {{ $messages->links() }}
    </div>

    <!-- Conversation Thread Modal -->
    @if($showThreadModal && $selectedPhone)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" wire:click.self="closeThread">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-2xl max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                <!-- Modal Header -->
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between bg-zinc-50 dark:bg-zinc-800/60">
                    <div>
                        <flux:heading size="lg">Conversation History</flux:heading>
                        <flux:subheading>WhatsApp Thread with <span class="font-mono font-bold text-zinc-900 dark:text-zinc-100">{{ $selectedPhone }}</span></flux:subheading>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:button size="sm" variant="danger" wire:click="clearThread('{{ $selectedPhone }}')" wire:confirm="Clear all message history for {{ $selectedPhone }}?" icon="trash">
                            Clear Thread
                        </flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="closeThread" icon="x-mark" />
                    </div>
                </div>

                <!-- Chat Bubbles Body -->
                <div class="p-4 flex-1 overflow-y-auto space-y-3 bg-zinc-100/50 dark:bg-zinc-950/50">
                    @forelse($threadMessages as $chat)
                        <div class="flex flex-col {{ $chat->direction === 'outbound' ? 'items-end' : 'items-start' }}">
                            <div class="max-w-[85%] rounded-2xl p-3.5 shadow-sm text-sm whitespace-pre-line break-words {{ $chat->direction === 'outbound' ? 'bg-green-600 text-white rounded-br-none' : 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 border border-zinc-200 dark:border-zinc-700 rounded-bl-none' }}">
                                <div class="text-xs font-semibold mb-1 opacity-75">
                                    {{ $chat->direction === 'outbound' ? '🤖 NLCGA AI Assistant' : '👤 Attendee (' . $chat->phone . ')' }}
                                </div>
                                {{ $chat->message }}
                            </div>
                            <span class="text-[11px] text-zinc-400 mt-1 px-1">
                                {{ $chat->created_at->format('M d, Y h:i:s A') }} ({{ $chat->created_at->diffForHumans() }})
                            </span>
                        </div>
                    @empty
                        <div class="text-center py-8 text-zinc-400">
                            No conversation history recorded for this number.
                        </div>
                    @endforelse
                </div>

                <!-- Modal Footer -->
                <div class="p-3 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 flex justify-end">
                    <flux:button variant="subtle" wire:click="closeThread">Close</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>



