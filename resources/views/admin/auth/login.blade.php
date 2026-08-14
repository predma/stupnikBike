@extends('admin.layout')

@section('content')
    <div class="login-wrap">
        <div class="login-card">
            <div class="pill">Sibinj Bike Admin</div>
            <h1 style="margin: 14px 0 8px; font-size: 34px;">Prijava</h1>
            <p class="muted" style="margin: 0 0 24px;">Uđi u administraciju općinskog sustava za najam bicikala.</p>

            <form method="POST" action="{{ route('admin.login.store') }}">
                @csrf
                <div class="field">
                    <label for="email" class="muted">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                    @error('email')<div class="error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="password" class="muted">Lozinka</label>
                    <input id="password" name="password" type="password" required>
                    @error('password')<div class="error">{{ $message }}</div>@enderror
                </div>

                <label style="display:flex; align-items:center; gap:10px; margin: 6px 0 20px;">
                    <input type="checkbox" name="remember" value="1">
                    <span class="muted">Zapamti me</span>
                </label>

                <button class="btn" type="submit" style="width: 100%;">Prijavi se</button>
            </form>
        </div>
    </div>
@endsection
