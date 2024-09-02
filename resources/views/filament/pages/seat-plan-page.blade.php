<div>
    <!-- Dropdown for selecting courses -->
    @if (!empty($courses))
        <div class="course-dropdown">
            <h3>Select Course:</h3>
            <select wire:model="selectedCourse" wire:change="loadSeatPlanDetails">
                <option value="">Select Course</option>
                @foreach ($courses as $id => $course)
                    <option value="{{ $id }}">{{ $course }}</option>
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
                    $seat = $seats->firstWhere('computer.computer_number', $index);
                @endphp

                <div class="seat-item {{ $seat && $seat->student ? 'occupied' : 'available' }}"
                    style="min-height: 100px; border: 1px solid #ccc; display: flex; flex-direction: column; justify-content: center; align-items: center; background-color: {{ $seat && $seat->student ? '#f8d7da' : '#d4edda' }};"
                    wire:click="{{ $seat && $seat->student ? 'removeStudentFromSeat(' . ($seat->id ?? 'null') . ')' : 'selectSeat(' . ($seat ? $seat->id : 'null') . ')' }}">
                    <p>Seat {{ $seat ? $seat->computer->computer_number : $index }}</p>
                    @if ($seat && $seat->student)
                        <p>{{ $seat->student->user->name }}</p>
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
                    $seat = $seats->firstWhere('computer.computer_number', $index);
                @endphp

                <div class="seat-item {{ $seat && $seat->student ? 'occupied' : 'available' }}"
                    style="min-height: 100px; border: 1px solid #ccc; display: flex; flex-direction: column; justify-content: center; align-items: center; background-color: {{ $seat && $seat->student ? '#f8d7da' : '#d4edda' }};"
                    wire:click="{{ $seat && $seat->student ? 'removeStudentFromSeat(' . ($seat->id ?? 'null') . ')' : 'selectSeat(' . ($seat ? $seat->id : 'null') . ')' }}">
                    <p>Seat {{ $seat ? $seat->computer->computer_number : $index }}</p>
                    @if ($seat && $seat->student)
                        <p>{{ $seat->student->user->name }}</p>
                        <button class="remove-button" wire:click.stop="removeStudentFromSeat({{ $seat->id }})"
                            style="margin-top: 10px;">Remove</button>
                    @else
                        <p>Available</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    @if ($selectedSeat)
        <div class="assign-student" style="margin-top: 20px;">
            <h3>Assign a Student to Seat {{ $selectedSeat->computer->computer_number ?? 'N/A' }}</h3>
            <select wire:model="selectedStudent" style="margin-top: 10px; padding: 5px;">
                <option value="">Select a Student</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}">{{ $student->user->name }}</option>
                @endforeach
            </select>
            <button wire:click="assignStudentToSeat" style="margin-top: 10px; padding: 5px 10px;">Assign</button>
        </div>
    @endif
</div>
