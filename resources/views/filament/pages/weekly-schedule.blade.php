<div class="flex gap-4 p-4 pt-8 overflow-x-auto">
    @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
        <div class="flex-1 min-w-[250px] border p-2 rounded-lg shadow-md bg-white dark:bg-gray-900 dark:border-gray-700">
            <div class="font-bold text-center text-lg mb-2 text-indigo-600 dark:text-purple-400">{{ $day }}</div>
            @if (!empty($weekSchedule[$day]))
                @foreach ($weekSchedule[$day] as $time => $slots)
                    @foreach ($slots as $slot)
                        <div class="border-t mt-2 pt-2 hover:bg-gray-100 dark:hover:bg-gray-800 transition duration-200 rounded-lg p-2 bg-gray-50 dark:bg-gray-800 shadow-sm dark:shadow-purple-900">
                            <!-- Display 'course_code', 'course_name', and 'instructor_name' -->
                            <div class="font-semibold text-blue-600 dark:text-teal-400">{{ $slot['course_code'] }}</div>
                            <div class="text-gray-700 dark:text-gray-200">{{ $slot['course_name'] }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ $slot['instructor_name'] }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $slot['class_start'] }} - {{ $slot['class_end'] }}</div>
                        </div>
                    @endforeach
                @endforeach
            @else
                <div class="text-center text-gray-400 dark:text-gray-500 italic mt-4">
                    No classes scheduled.
                </div>
            @endif
        </div>
    @endforeach
</div>
