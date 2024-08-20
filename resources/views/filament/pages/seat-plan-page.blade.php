<div>
    <!-- Main Seat Plan Wrapper -->
    <div class="seat-plan">
        <h2>Seat Plan</h2>

        <!-- First Grid Wrapper for 2x7 Table -->
        <div class="seat-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); grid-template-rows: repeat(2, 1fr); gap: 10px; margin-bottom: 20px;">
            @for ($i = 0; $i < 14; $i++)
                @php
                    $seat = $seats->get($i); // Get the seat if it exists
                @endphp

                <div class="seat-item {{ $seat && $seat->userInformation ? 'occupied' : 'available' }}"
                     wire:click="{{ $seat && $seat->userInformation ? 'removeStudentFromSeat(' . $seat->id . ')' : ($seat ? 'selectSeat(' . $seat->id . ')' : '') }}"
                     style="min-height: 100px; border: 1px solid #ccc; display: flex; flex-direction: column; justify-content: center; align-items: center; background-color: {{ $seat && $seat->userInformation ? '#f8d7da' : '#d4edda' }};">
                    <p>Seat {{ $seat ? $seat->computer_id : 'N/A' }}</p>
                    @if ($seat && $seat->userInformation)
                        <p>{{ $seat->userInformation->user->name }}</p>
                        <button class="remove-button" wire:click.stop="removeStudentFromSeat({{ $seat->id }})" style="margin-top: 10px;">Remove</button>
                    @else
                        <p>Available</p>
                    @endif
                </div>
            @endfor
        </div>

        <!-- Second Grid Wrapper for 2x7 Table -->
        <div class="seat-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); grid-template-rows: repeat(2, 1fr); gap: 10px;">
            @for ($i = 14; $i < 28; $i++)
                @php
                    $seat = $seats->get($i); // Get the seat if it exists
                @endphp

                <div class="seat-item {{ $seat && $seat->userInformation ? 'occupied' : 'available' }}"
                     wire:click="{{ $seat && $seat->userInformation ? 'removeStudentFromSeat(' . $seat->id . ')' : ($seat ? 'selectSeat(' . $seat->id . ')' : '') }}"
                     style="min-height: 100px; border: 1px solid #ccc; display: flex; flex-direction: column; justify-content: center; align-items: center; background-color: {{ $seat && $seat->userInformation ? '#f8d7da' : '#d4edda' }};">
                    <p>Seat {{ $seat ? $seat->computer_id : 'N/A' }}</p>
                    @if ($seat && $seat->userInformation)
                        <p>{{ $seat->userInformation->user->name }}</p>
                        <button class="remove-button" wire:click.stop="removeStudentFromSeat({{ $seat->id }})" style="margin-top: 10px;">Remove</button>
                    @else
                        <p>Available</p>
                    @endif
                </div>
            @endfor
        </div>
    </div>

    <!-- Conditional: Only render if selectedSeat is set -->
    @if ($selectedSeat)
        <!-- Assign Student Wrapper -->
        <div class="assign-student">
            <h3>Assign a Student to Seat {{ $selectedSeat->computer_id }}</h3>
            <select wire:model="selectedStudent">
                <option value="">Select a Student</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}">{{ $student->user->name }}</option>
                @endforeach
            </select>
            <button wire:click="assignStudentToSeat">Assign</button>
        </div>
    @endif
</div>
