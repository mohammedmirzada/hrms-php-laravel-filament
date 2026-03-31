<div>
    <h1 style="font-size: 26px;">{{ $form_name }}</h1>
    <div style="height: 1px;background:#0000001a;margin: 14px 0 22px 0;"></div>
    <div id="form-container" style="display: {{ $hideForm ? 'none' : 'block' }};">
        {{ $this->form }}
        <x-filament::button style="background-color: #11375c;margin-top: 18px;width: 130px;" wire:click="submit">
            Submit
        </x-filament::button>
    </div>
    <style>
        main {
            margin: 0 auto !important;
            max-width: 1200px !important;
            padding: 22px !important;
        }
        .buttonLanguage {
            margin: 0 10px;
            text-decoration: none;
            padding: 12px;
            background: #11375c;
            color: #fafafa;
            font-weight: 500;
        }
    </style>
    <div id="qr-code-container" style="text-align: center;margin-top: 20px; display: {{ $hideForm ? 'block' : 'none' }};">
        <h3 style="margin-bottom: 22px;">Thanks for registering!</h3>
        @if ($showQrCode)
            <div align="center">
                <img src="data:image/png;base64,{{ $showQrCode }}" alt="QR Code">
            </div>
        @endif
        <h3 style="margin-top: 22px;color: red;">Save this QR code for event check-in.</h3>
        <x-filament::link style="margin-top: 12px;" id="save-qr-code" href="data:image/png;base64,{{ $showQrCode }}" download="event-qr-code.png">
            Save QR Code
        </x-filament::link>
    </div>
</div>