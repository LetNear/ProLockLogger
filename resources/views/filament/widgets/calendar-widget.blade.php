<x-filament-widgets::widget>
    <style>
        .fc .fc-timegrid-slot {
            height: 60px; /* Adjust the slot height to make events larger */
        }
        
        .fc-event-title, .fc-timegrid-slot-label {
            font-size: 1.2em; /* Increase font size for better readability */
        }
        
        .fc-view-harness {
            transform: scale(1.1); /* Scale up the calendar slightly for better visibility */
            transform-origin: top left;
        }
        </style>
    <x-filament::section>
        {{-- Widget content --}}
    </x-filament::section>
</x-filament-widgets::widget>
