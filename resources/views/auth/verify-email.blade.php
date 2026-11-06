@extends('layouts.guest')
@section('title', 'Verifikasi email')
@section('content')
    <p>Kami mengirim tautan verifikasi ke email Anda. Buka tautan itu untuk melanjutkan.</p>
    <form method="POST" action="{{ route('verification.send') }}" class="mb-2">
        @csrf
        <button type="submit" class="btn btn-primary w-100">Kirim ulang tautan</button>
    </form>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-link w-100">Keluar</button>
    </form>
@endsection
