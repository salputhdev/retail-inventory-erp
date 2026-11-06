@extends('layouts.guest')
@section('title', 'Masuk')
@section('content')
    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="form-control @error('email') is-invalid @enderror">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary w-100">Masuk</button>
        <div class="text-center mt-3"><a href="{{ route('password.request') }}">Lupa password?</a></div>
    </form>
@endsection
