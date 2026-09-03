<?php
use function Livewire\Volt\{state, layout};
use App\Models\ConferenceMember;

layout('components.layouts.app');

state([
    'scanResult' => null,
    'message' => '',
    'error' => '',
    'member' => null,
    'hardwareScanCode' => '',
]);

$resetScanner = function () {
    $this->message = '';
    $this->error = '';
    $this->member = null;
    $this->scanResult = null;
    $this->hardwareScanCode = '';
};

$checkIn = function ($code) {
    if (empty($code)) {
        $code = $this->hardwareScanCode;
    }

    if (empty($code)) return;

    $this->scanResult = $code;
    $this->message = '';
    $this->error = '';
    $this->member = null;
    $this->hardwareScanCode = '';

    $member = ConferenceMember::where('unique_code', strtoupper(trim($code)))->first();

    if (!$member) {
        $this->error = "Invalid Ticket Code! No attendee found for: {$code}";
        return;
    }

    if ($member->is_checked_in) {
        $this->error = "Already Checked In!";
        $this->message = $member->fullName . " was already checked in at " . \Carbon\Carbon::parse($member->checked_in_at)->format('Y-m-d h:i A');
        $this->member = clone $member;
        return;
    }

    $member->update([
        'is_checked_in' => true,
        'checked_in_at' => now(),
        'checked_in_by' => auth()->id(),
    ]);

    $this->member = clone $member;
    $this->message = "Check-in Successful!";
};
?>
<div id="scanner-box">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Ticket Scanner') }}
            </h2>
            <a href="{{ auth()->user() && auth()->user()->isStaff() ? route('staff.dashboard') : route('dashboard') }}" wire:navigate class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition">
                &larr; Back to Dashboard
            </a>
        </div>
    </x-slot>

    <script src="https://unpkg.com/html5-qrcode"></script>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 min-h-[500px] flex flex-col items-center justify-center relative">

                <div wire:loading.flex wire:target="checkIn, resetScanner" style="display: none;" class="absolute inset-0 bg-white/80 z-50 items-center justify-center">
                    <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-indigo-600"></div>
                </div>

                @if(!$member && !$error)
                    <!-- IDLE VIEW: Physical Scanner & Open Camera Button -->
                    <div id="scanner-idle-view" class="w-full">
                        <div class="w-full max-w-md mx-auto mb-8 p-6 bg-gray-50 rounded-xl border-2 border-dashed border-gray-300 text-center">
                            <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Using a Physical Handheld Scanner?</label>
                            <p class="text-xs text-gray-500 mb-4">Click inside the box below and scan the ticket with your USB/Bluetooth device.</p>
                            <input type="text"
                                   wire:model="hardwareScanCode"
                                   wire:keydown.enter="checkIn('')"
                                   placeholder="Scan or type code here..."
                                   class="w-full text-center text-xl font-mono p-4 border-2 border-indigo-300 rounded-lg focus:ring-4 focus:ring-indigo-200 focus:border-indigo-500 transition shadow-inner"
                                   autofocus>
                        </div>

                        <div class="flex items-center w-full max-w-md mx-auto my-6">
                            <div class="flex-grow border-t border-gray-300"></div>
                            <span class="flex-shrink-0 mx-4 text-gray-400 font-medium">OR USE WEBCAM</span>
                            <div class="flex-grow border-t border-gray-300"></div>
                        </div>

                        <div class="text-center w-full">
                            <h3 class="text-2xl font-extrabold text-gray-900 mb-2">Webcam Scanner</h3>
                            <p class="text-gray-500 mb-6 max-w-sm mx-auto">Click below to activate your camera and scan tickets.</p>

                            <button type="button"
                                    onclick="startScannerCamera()"
                                    class="px-10 py-4 bg-indigo-600 text-white font-bold text-lg rounded-full shadow-xl hover:bg-indigo-700 hover:shadow-2xl hover:-translate-y-1 transform transition duration-200 inline-flex items-center justify-center mx-auto cursor-pointer">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Open Camera
                            </button>
                        </div>
                    </div>

                    <!-- ACTIVE CAMERA SCANNER VIEW -->
                    <div id="scanner-active-view" class="w-full max-w-md mx-auto text-center" style="display: none;">
                        <h3 class="text-2xl font-bold mb-4 text-gray-800">Align QR Code</h3>

                        <div class="relative mx-auto bg-black rounded-2xl overflow-hidden border-8 border-gray-900 shadow-2xl" style="aspect-ratio: 1/1;">
                            <div class="absolute inset-0 z-20 pointer-events-none flex flex-col items-center justify-center overflow-hidden">
                                <div class="w-full h-1 bg-red-500 shadow-[0_0_15px_rgba(239,68,68,1)] animate-[scan_2s_ease-in-out_infinite]"></div>
                            </div>

                            <div class="absolute inset-0 z-10 pointer-events-none flex items-center justify-center p-8">
                                <div class="w-full h-full border-2 border-white/30 relative">
                                    <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-white"></div>
                                    <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-white"></div>
                                    <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-white"></div>
                                    <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-white"></div>
                                </div>
                            </div>

                            <div id="reader" class="w-full h-full object-cover bg-gray-800 flex items-center justify-center text-gray-400 text-sm">
                                <span id="scanner-status-text">Starting camera...</span>
                            </div>
                        </div>

                        <div id="camera-select-wrapper" class="mt-4 text-left" style="display: none;">
                            <label class="text-xs font-semibold text-gray-600 block mb-1">Switch Camera:</label>
                            <select id="camera-select" onchange="switchScannerCamera(this.value)" class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </select>
                        </div>

                        <button type="button"
                                onclick="cancelScannerCamera()"
                                class="mt-6 px-8 py-3 bg-gray-200 text-gray-700 font-bold rounded-full shadow hover:bg-gray-300 transition cursor-pointer">
                            Cancel Scan
                        </button>
                    </div>
                @endif

                <!-- RESULT: SUCCESS / ALREADY CHECKED IN -->
                @if($member)
                    <div class="text-center w-full max-w-md mx-auto animate-fade-in-up">
                        @if(!$error)
                            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-4 shadow-inner">
                                <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <h3 class="text-3xl font-extrabold text-green-600 mb-6">{{ $message }}</h3>
                        @else
                            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-yellow-100 mb-4 shadow-inner">
                                <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <h3 class="text-2xl font-extrabold text-yellow-600 mb-2">{{ $error }}</h3>
                            <p class="text-gray-500 mb-6">{{ $message }}</p>
                        @endif

                        <div class="bg-gray-50 rounded-2xl p-6 mt-2 text-left border border-gray-200 shadow-sm relative overflow-hidden">
                            <div class="absolute top-0 left-0 w-2 h-full {{ !$error ? 'bg-green-500' : 'bg-yellow-500' }}"></div>

                            <h4 class="text-gray-400 uppercase tracking-widest text-xs font-bold mb-4 border-b border-gray-200 pb-2 ml-2">Attendee Information</h4>
                            <div class="space-y-4 ml-2">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Full Name</p>
                                    <p class="text-xl font-bold text-gray-900">{{ $member->fullName }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase font-semibold">Pass Type</p>
                                        <p class="font-medium text-gray-800">{{ $member->passType ?? 'Standard' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase font-semibold">Ticket Code</p>
                                        <p class="font-mono bg-gray-200 text-gray-700 px-2 py-1 rounded inline-block text-sm font-bold mt-1 shadow-inner">{{ $member->unique_code }}</p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Organisation</p>
                                    <p class="font-medium text-gray-800">{{ $member->organisation ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                        <button wire:click="resetScanner" class="mt-8 px-10 py-4 w-full bg-indigo-600 text-white font-bold text-lg rounded-xl shadow-lg hover:bg-indigo-700 transform transition cursor-pointer">
                            Scan Next Ticket
                        </button>
                    </div>
                @endif

                <!-- RESULT: INVALID TICKET -->
                @if($error && !$member)
                    <div class="text-center py-8 w-full animate-fade-in-up">
                        <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-red-100 mb-6 shadow-inner">
                            <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </div>
                        <h3 class="text-2xl font-extrabold text-red-600 mb-2">Invalid Ticket</h3>
                        <p class="text-gray-600 mb-8 font-medium">{{ $error }}</p>

                        <button wire:click="resetScanner" class="px-10 py-4 bg-gray-800 text-white font-bold text-lg rounded-xl shadow-lg hover:bg-gray-900 transition cursor-pointer">
                            Try Again
                        </button>
                    </div>
                @endif

            </div>
        </div>
    </div>

    <style>
        @keyframes scan {
            0% { transform: translateY(-150px); }
            50% { transform: translateY(150px); }
            100% { transform: translateY(-150px); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.4s ease-out forwards;
        }
    </style>

    <script>
        var html5QrCodeScanner = null;
        var currentCameraId = null;

        function setStatus(msg, isError) {
            var el = document.getElementById('scanner-status-text');
            if (el) {
                el.innerText = msg;
                el.style.color = isError ? '#ef4444' : '#9ca3af';
            }
        }

        function startScannerCamera() {
            var idleView = document.getElementById('scanner-idle-view');
            var activeView = document.getElementById('scanner-active-view');
            if (idleView) idleView.style.display = 'none';
            if (activeView) activeView.style.display = 'block';

            setStatus('Requesting camera access...', false);

            if (typeof Html5Qrcode === 'undefined') {
                var script = document.createElement('script');
                script.src = 'https://unpkg.com/html5-qrcode';
                script.onload = function() { initCameraStream(); };
                script.onerror = function() { setStatus('Failed to load QR scanner library.', true); };
                document.head.appendChild(script);
            } else {
                initCameraStream();
            }
        }

        function initCameraStream() {
            Html5Qrcode.getCameras().then(function(devices) {
                if (devices && devices.length > 0) {
                    var selectWrapper = document.getElementById('camera-select-wrapper');
                    var selectEl = document.getElementById('camera-select');

                    if (selectEl) {
                        selectEl.innerHTML = '';
                        devices.forEach(function(d, index) {
                            var opt = document.createElement('option');
                            opt.value = d.id;
                            opt.text = d.label || ('Camera ' + (index + 1));
                            selectEl.appendChild(opt);
                        });
                        if (devices.length > 1 && selectWrapper) {
                            selectWrapper.style.display = 'block';
                        }
                    }

                    var backCam = null;
                    for (var i = 0; i < devices.length; i++) {
                        if (devices[i].label && devices[i].label.toLowerCase().indexOf('back') !== -1) {
                            backCam = devices[i];
                            break;
                        }
                    }
                    currentCameraId = backCam ? backCam.id : devices[0].id;
                    if (selectEl) selectEl.value = currentCameraId;

                    runScanner(currentCameraId);
                } else {
                    setStatus('No cameras detected on this device.', true);
                }
            }).catch(function(err) {
                console.error('Camera error:', err);
                setStatus('Camera access denied or unavailable.', true);
                alert('Camera access was denied! Please allow camera permissions in your browser.');
            });
        }

        function runScanner(cameraId) {
            setStatus('Starting camera feed...', false);

            if (!html5QrCodeScanner) {
                html5QrCodeScanner = new Html5Qrcode('reader');
            }

            var config = { fps: 10, qrbox: { width: 250, height: 250 } };

            html5QrCodeScanner.start(cameraId, config, function(decodedText) {
                stopScannerCamera().then(function() {
                    if (window.Livewire) {
                        var compEl = document.getElementById('scanner-box');
                        var wireId = compEl ? compEl.closest('[wire\\:id]').getAttribute('wire:id') : null;
                        var component = wireId ? Livewire.find(wireId) : null;
                        if (component) {
                            component.call('checkIn', decodedText);
                        } else {
                            @this.call('checkIn', decodedText);
                        }
                    }
                });
            }).catch(function(err) {
                console.error('Start scanner error:', err);
                setStatus('Failed to start camera. Camera may be busy in another app.', true);
            });
        }

        function switchScannerCamera(cameraId) {
            if (html5QrCodeScanner && html5QrCodeScanner.isScanning) {
                html5QrCodeScanner.stop().then(function() {
                    currentCameraId = cameraId;
                    runScanner(cameraId);
                }).catch(function(e) { console.error(e); });
            } else {
                currentCameraId = cameraId;
                runScanner(cameraId);
            }
        }

        function stopScannerCamera() {
            if (html5QrCodeScanner && html5QrCodeScanner.isScanning) {
                return html5QrCodeScanner.stop().catch(function(err) { console.warn(err); });
            }
            return Promise.resolve();
        }

        function cancelScannerCamera() {
            stopScannerCamera().then(function() {
                var idleView = document.getElementById('scanner-idle-view');
                var activeView = document.getElementById('scanner-active-view');
                if (activeView) activeView.style.display = 'none';
                if (idleView) idleView.style.display = 'block';
            });
        }

        document.addEventListener('livewire:navigating', function() {
            stopScannerCamera();
        });
    </script>
</div>
