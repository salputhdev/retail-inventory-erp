@extends('layouts.guest')
@section('title', 'Dashboard')
@section('content')
    {{-- Placeholder T1.03 untuk menguji alur login; diganti shell UI di T1.06 dan dashboard di M5. --}}
    <p class="mb-3">Halo, <strong>{{ auth()->user()->name }}</strong>.</p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-outline-secondary w-100">Keluar</button>
    </form>
@endsection
