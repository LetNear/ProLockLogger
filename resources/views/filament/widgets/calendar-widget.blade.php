<x-filament-widgets::widget>
    <x-filament::section>
        <style>
            .fc .fc-timegrid-slot {
                height: 60px !important; /* Enforce slot height */
            }

            .fc-event-title, .fc-timegrid-slot-label {
                font-size: 1.2em !important; /* Enforce font size */
            }

            .fc-view-harness, .fc-view-harness-active {
                transform: scale(1.1) !important; /* Scale up the calendar */
                transform-origin: top left !important;
            }
        </style>

        {{-- Place the calendar component here --}}
        <div id="calendar"></div> <!-- Placeholder example -->
        
    </x-filament::section>
</x-filament-widgets::widget>
