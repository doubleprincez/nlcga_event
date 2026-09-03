<?php
use function Livewire\Volt\{state, mount, layout};
use App\Models\ConferenceMember;

layout('components.layouts.app');

state(['staffName' => '', 'staffEmail' => '', 'totalScanned' => 0, 'eventYear' => date('Y')]);

mount(function () {
    $user = auth()->user();
    $this->staffName = $user->name;
    $this->staffEmail = $user->email;
    $this->totalScanned = ConferenceMember::where('checked_in_by', $user->id)->count();
});
?>
<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Staff Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex flex-col md:flex-row items-center justify-between">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900">Welcome back, {{ $staffName }}!</h3>
                    <p class="text-gray-500 mt-1">{{ $staffEmail }} | Role: Event Staff</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="{{ route('staff.scanner') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-lg font-semibold text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition shadow-md" wire:navigate>
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Launch Scanner
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Stats Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-indigo-500">
                    <h4 class="text-lg font-semibold text-gray-700 mb-2">Your Scanning Stats</h4>
                    <div class="flex items-center mt-4">
                        <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-3xl font-bold text-gray-900">{{ $totalScanned }}</p>
                            <p class="text-sm text-gray-500">Attendees Checked-in by you</p>
                        </div>
                    </div>
                </div>

                <!-- Event Info Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-t-4 border-green-500">
                    <h4 class="text-lg font-semibold text-gray-700 mb-2">Current Event</h4>
                    <div class="mt-4">
                        <p class="text-xl font-bold text-gray-900">NLCGA Conference {{ $eventYear }}</p>
                        <p class="text-sm text-gray-500 mt-1">Status: Active</p>
                        <p class="text-sm text-gray-500 mt-1">Please ensure you verify attendee identity if requested.</p>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>


