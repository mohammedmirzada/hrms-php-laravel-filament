<div>
    <h1 style="font-size: 26px" class="mb-4 mt-6"><b>Submit Leave Request</b></h1>
    <div id="form-container" style="display: {{ $hideForm ? 'none' : 'block' }};">
        {{ $this->form }}
        <x-filament::button style="background-color: #11375c;margin-top: 18px;width: 130px;" wire:click="submit">
            Submit
        </x-filament::button>
    </div>
</div>