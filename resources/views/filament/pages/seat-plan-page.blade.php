<div>
    <!-- Dropdown for selecting instructor's subject -->
    @if (!empty($instructorSubjects))
        <div class="subject-dropdown">
            <h3>Select Subject:</h3>
            <select wire:model="selectedSchedule">
                <option value="">Select Subject</option>
                @foreach ($instructorSubjects as $subject)
                    <option value="{{ json_encode(['block' => $subject->block_id, 'year' => $subject->year]) }}">
                        {{ $subject->subject_code }} - {{ $subject->subject_name }} ({{ $subject->block_id }} - {{ $subject->year }})
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <!-- Main Seat Plan Wrapper -->
    <div class="seat-plan">
        <h2>Seat Plan</h2>

        <!-- Grid Wrapper for the First 2x7 Table -->
        <div class="seat-grid"
            style="display: grid; grid-template-columns: repeat(7, 1fr); grid-template-rows: repeat(2, 1fr); gap: 10px; margin-bottom: 20px;">
            @foreach (range(1, 14) as $index)
                @php
                    // Find the seat by its computer number
                    $seat = $seats->firstWhere(
                        fn($seat) => $seat->computer && $seat->computer->computer_number == $index,
                    );
                @endphp

                <div class="seat-item {{ $seat && $seat->student ? 'occupied' : 'available' }}"
                    style="min-height: 100px; border: 1px solid #ccc; display: flex; flex-direction: column; justify-content: center; align-items: center; background-color: {{ $seat && $seat->student ? '#f8d7da' : '#d4edda' }};"
                    wire:click="{{ $seat && $seat->student ? 'removeStudentFromSeat(' . ($seat->id ?? 'null') . ')' : 'selectSeat(' . ($seat ? $seat->id : 'null') . ')' }}">
                    <p>Seat {{ $seat ? $seat->computer->computer_number : 'N/A' }}</p>
                    @if ($seat && $seat->student)
                        <p>{{ $seat->student->name }}</p>
                        <button class="remove-button" wire:click.stop="removeStudentFromSeat({{ $seat->id }})"
                            style="margin-top: 10px;">Remove</button>
                    @else
                        <p>Available</p>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Grid Wrapper for the Second 2x7 Table -->
        <div class="seat-grid"
            style="display: grid; grid-template-columns: repeat(7, 1fr); grid-template-rows: repeat(2, 1fr); gap: 10px;">
            @foreach (range(15, 28) as $index)
                @php
                    // Find the seat by its computer number
                    $seat = $seats->firstWhere(
                        fn($seat) => $seat->computer && $seat->computer->computer_number == $index,
                    );
                @endphp

                <div class="seat-item {{ $seat && $seat->student ? 'occupied' : 'available' }}"
                    style="min-height: 100px; border: 1px solid #ccc; display: flex; flex-direction: column; justify-content: center; align-items: center; background-color: {{ $seat && $seat->student ? '#f8d7da' : '#d4edda' }};"
                    wire:click="{{ $seat && $seat->student ? 'removeStudentFromSeat(' . ($seat->id ?? 'null') . ')' : 'selectSeat(' . ($seat ? $seat->id : 'null') . ')' }}">
                    <p>Seat {{ $seat ? $seat->computer->computer_number : 'N/A' }}</p>
                    @if ($seat && $seat->student)
                        <p>{{ $seat->student->name }}</p>
                        <button class="remove-button" wire:click.stop="removeStudentFromSeat({{ $seat->id }})"
                            style="margin-top: 10px;">Remove</button>
                    @else
                        <p>Available</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Conditional: Only render if selectedSeat is set -->
    @if ($selectedSeat)
        <div class="assign-student" style="margin-top: 20px;">
            <h3>Assign a Student to Seat {{ $selectedSeat->computer->computer_number ?? 'N/A' }}</h3>
            <select wire:model="selectedStudent" style="margin-top: 10px; padding: 5px;">
                <option value="">Select a Student</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}">{{ $student->name }}</option>
                @endforeach
            </select>
            <button wire:click="assignStudentToSeat" style="margin-top: 10px; padding: 5px 10px;">Assign</button>
        </div>
    @endif
</div>
