<div>
    <!-- Dropdown for selecting courses -->
    @if (!empty($courses))
        <div class="course-dropdown" style="margin-bottom: 20px;">
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
    @if ($selectedCourse)
        <div class="seat-plan"
            style="display: flex; justify-content: center; width: 100%; padding-bottom: 50px; position: relative;">

            <!-- Centered Rows of Seats with Aisles -->
            <div style="display: flex; flex-direction: column; gap: 30px; max-width: 80%; margin-right: auto;">

                <!-- First Row of Seats -->
                <div style="display: grid; grid-template-columns: repeat(10, 1fr); gap: 10px;">
                    @foreach ($computers->slice(0, 10) as $computer)
                        @php
                            $seat = $seats->get($computer->id);
                        @endphp
                        <div wire:click="selectSeat({{ $computer->id }})"
                            class="seat-item {{ $seat && $seat->student ? 'occupied' : 'available' }}"
                            style="min-height: 80px; border: 1px solid #ccc; display: flex; flex-direction: column; justify-content: center; align-items: center; background-color: {{ $seat && $seat->student ? '#f8d7da' : '#d4edda' }};">
                            <p>Seat {{ $computer->computer_number }}</p>
                            @if ($seat && $seat->student)
                                <p>{{ $seat->student->user->name }}</p>
                                <button class="remove-button"
                                    wire:click.stop="removeStudentFromSeat({{ $seat->id }})"
                                    style="margin-top: 5px;">Remove</button>
                            @else
                                <p>Available</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Second Row of Seats -->
                <div style="display: grid; grid-template-columns: repeat(10, 1fr); gap: 10px;">
                    @foreach ($computers->slice(10, 10) as $computer)
                        @php
                            $seat = $seats->get($computer->id);
                        @endphp
                        <div wire:click="selectSeat({{ $computer->id }})"
                            class="seat-item {{ $seat && $seat->student ? 'occupied' : 'available' }}"
                            style="min-height: 80px; border: 1px solid #ccc; display: flex; flex-direction: column; justify-content: center; align-items: center; background-color: {{ $seat && $seat->student ? '#f8d7da' : '#d4edda' }};">
                            <p>Seat {{ $computer->computer_number }}</p>
                            @if ($seat && $seat->student)
                                <p>{{ $seat->student->user->name }}</p>
                                <button class="remove-button"
                                    wire:click.stop="removeStudentFromSeat({{ $seat->id }})"
                                    style="margin-top: 5px;">Remove</button>
                            @else
                                <p>Available</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Third Row of Seats -->
                <div style="display: grid; grid-template-columns: repeat(10, 1fr); gap: 10px;">
                    @foreach ($computers->slice(20, 10) as $computer)
                        @php
                            $seat = $seats->get($computer->id);
                        @endphp
                        <div wire:click="selectSeat({{ $computer->id }})"
                            class="seat-item {{ $seat && $seat->student ? 'occupied' : 'available' }}"
                            style="min-height: 80px; border: 1px solid #ccc; display: flex; flex-direction: column; justify-content: center; align-items: center; background-color: {{ $seat && $seat->student ? '#f8d7da' : '#d4edda' }};">
                            <p>Seat {{ $computer->computer_number }}</p>
                            @if ($seat && $seat->student)
                                <p>{{ $seat->student->user->name }}</p>
                                <button class="remove-button"
                                    wire:click.stop="removeStudentFromSeat({{ $seat->id }})"
                                    style="margin-top: 5px;">Remove</button>
                            @else
                                <p>Available</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Fourth Row of Seats -->
                <div style="display: grid; grid-template-columns: repeat(10, 1fr); gap: 10px;">
                    @foreach ($computers->slice(30, 10) as $computer)
                        @php
                            $seat = $seats->get($computer->id);
                        @endphp
                        <div wire:click="selectSeat({{ $computer->id }})"
                            class="seat-item {{ $seat && $seat->student ? 'occupied' : 'available' }}"
                            style="min-height: 80px; border: 1px solid #ccc; display: flex; flex-direction: column; justify-content: center; align-items: center; background-color: {{ $seat && $seat->student ? '#f8d7da' : '#d4edda' }};">
                            <p>Seat {{ $computer->computer_number }}</p>
                            @if ($seat && $seat->student)
                                <p>{{ $seat->student->user->name }}</p>
                                <button class="remove-button"
                                    wire:click.stop="removeStudentFromSeat({{ $seat->id }})"
                                    style="margin-top: 5px;">Remove</button>
                            @else
                                <p>Available</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Fixed TV and Door on the Center-Right -->
            <div
                style="position: absolute; top: 50%; transform: translateY(-50%); right: 20px; display: flex; flex-direction: column; align-items: center; gap: 20px;">
                <div
                    style="width: 50px; height: 80px; background-color: #ccc; display: flex; align-items: center; justify-content: center;">
                    TV
                </div>
                <div
                    style="width: 50px; height: 80px; background-color: #ccc; display: flex; align-items: center; justify-content: center; writing-mode: vertical-rl; text-orientation: upright;">
                    Door
                </div>
            </div>
        </div>
    @endif

    <!-- Assign Student Dropdown -->
    @if ($selectedSeat)
        <div class="assign-student" style="margin-top: 20px;"
            wire:key="assign-student-{{ $selectedSeat->id ?? 'none' }}">
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
