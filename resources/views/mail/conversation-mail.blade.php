{{-- <div>
    <!-- Simplicity is the ultimate sophistication. - Leonardo da Vinci -->
</div> --}}

@extends('layouts.mail')

@section('content')
    <!-- Salutation -->
    {{-- <h2>Hi, Dear!</h2> --}}
    <h2>Dear {{ $user?->first_name ?? 'SDSSN Member' }},</h2>
    <p>
    <div style="font-family: monospace;">
        <pre>{{ $content['message'] ?? '' }}</pre>
    </div>
    </p>
@endsection
