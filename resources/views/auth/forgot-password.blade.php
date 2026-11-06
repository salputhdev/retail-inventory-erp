@extends('layouts.guest')
@section('title', 'Lupa password')
@section('content')
    <p class="text-body-secondary">Masukkan email Anda. Jika terdaftar, kami kirim tautan untuk mengatur ulang password.</p>
    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="form-control @error('email') is-invalid @enderror">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <button type="submit" class="btn btn-primary w-100">Kirim tautan</button>
        <div class="text-center mt-3"><a href="{{ route('login') }}">Kembali masuk</a></div>
    </form>
@endsection
