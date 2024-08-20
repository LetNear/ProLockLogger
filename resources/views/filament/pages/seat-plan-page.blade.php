<div>
    <!-- Main Seat Plan Wrapper -->
    <div class="seat-plan">
        <h2>Seat Plan</h2>
        <!-- Grid Wrapper -->
        <div class="seat-grid" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px;">
            @forelse ($seats as $seat)
                <!-- Seat Item -->
                <div class="seat-item {{ $seat->userInformation ? 'occupied' : 'available' }}"
                     wire:click="{{ $seat->userInformation ? 'removeStudentFromSeat(' . $seat->id . ')' : 'selectSeat(' . $seat->id . ')' }}"
                     style="min-height: 100px; border: 1px solid #ccc; display: flex; flex-direction: column; justify-content: center; align-items: center; background-color: {{ $seat->userInformation ? '#f8d7da' : '#d4edda' }};">
                    <p>Seat {{ $seat->computer_id }}</p>
                    @if ($seat->userInformation)
                        <p>{{ $seat->userInformation->user->name }}</p>
                        <button class="remove-button" wire:click.stop="removeStudentFromSeat({{ $seat->id }})" style="margin-top: 10px;">Remove</button>
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
