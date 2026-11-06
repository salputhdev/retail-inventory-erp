@extends('layouts.guest')
@section('title', 'Atur ulang password')
@section('content')
    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autocomplete="username" class="form-control @error('email') is-invalid @enderror">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password baru</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">
            <div class="form-text">Minimal 12 karakter dan tidak boleh ada di daftar password yang pernah bocor.</div>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Ulangi password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary w-100">Simpan password</button>
    </form>
@endsection
