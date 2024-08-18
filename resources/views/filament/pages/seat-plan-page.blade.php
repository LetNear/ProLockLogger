<div>
    <!-- Main Seat Plan Wrapper -->
    <div class="seat-plan">
        <h2>Seat Plan</h2>
        <!-- Grid Wrapper -->
        <div class="seat-grid">
            @forelse ($seats as $seat)
                <!-- Seat Item -->
                <div class="seat-item {{ $seat->userInformation ? 'occupied' : 'available' }}"
                     wire:click="{{ $seat->userInformation ? 'removeStudentFromSeat(' . $seat->id . ')' : 'selectSeat(' . $seat->id . ')' }}">
                    <p>Seat {{ $seat->computer_id }}</p>
                    @if ($seat->userInformation)
                        <p>{{ $seat->userInformation->user->name }}</p>
                        <button class="remove-button" wire:click.stop="removeStudentFromSeat({{ $seat->id }})">Remove</button>
                    @else
                        <p>Available</p>
                    @endif
                </div>
            @empty
                <!-- Fallback message when no seats are available -->
                <p>No seats available.</p>
            @endforelse
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
