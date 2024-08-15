<!-- resources/views/filament/resources/user-resource.blade.php -->
@extends('layouts.filament')

@section('content')
    <div class="p-4">
        @if (session('duplicateEmails'))
            <div class="alert alert-danger">
                <strong>Duplicate Emails:</strong>
                <ul>
                    @foreach (session('duplicateEmails') as $email)
                        <li>{{ $email }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('invalidEmails'))
            <div class="alert alert-danger">
                <strong>Invalid Email Format:</strong>
                <ul>
                    @foreach (session('invalidEmails') as $email)
                        <li>{{ $email }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Your resource content goes here -->
    </div>
@endsection
