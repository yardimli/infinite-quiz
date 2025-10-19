@extends('layouts.guest')

@section('content')
    <form method="POST" action="{{ route('register') }}">
        @csrf
        
        <!-- Name -->
        <div class="form-control w-full">
            <label for="name" class="label">
                <span class="label-text">{{ __('Name') }}</span>
            </label>
            <input id="name" class="input input-bordered w-full" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" />
            @error('name')
            <label class="label">
                <span class="label-text-alt text-error">{{ $message }}</span>
            </label>
            @enderror
        </div>
        
        <!-- Email Address -->
        <div class="mt-4 form-control w-full">
            <label for="email" class="label">
                <span class="label-text">{{ __('Email') }}</span>
            </label>
            <input id="email" class="input input-bordered w-full" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" />
            @error('email')
            <label class="label">
                <span class="label-text-alt text-error">{{ $message }}</span>
            </label>
            @enderror
        </div>
        
        <!-- Password -->
        <div class="mt-4 form-control w-full">
            <label for="password" class="label">
                <span class="label-text">{{ __('Password') }}</span>
            </label>
            <input id="password" class="input input-bordered w-full"
                   type="password"
                   name="password"
                   required autocomplete="new-password" />
            @error('password')
            <label class="label">
                <span class="label-text-alt text-error">{{ $message }}</span>
            </label>
            @enderror
        </div>
        
        <!-- Confirm Password -->
        <div class="mt-4 form-control w-full">
            <label for="password_confirmation" class="label">
                <span class="label-text">{{ __('Confirm Password') }}</span>
            </label>
            <input id="password_confirmation" class="input input-bordered w-full"
                   type="password"
                   name="password_confirmation" required autocomplete="new-password" />
            @error('password_confirmation')
            <label class="label">
                <span class="label-text-alt text-error">{{ $message }}</span>
            </label>
            @enderror
        </div>
        
        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm hover:text-base-content/80" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>
            
            <button type="submit" class="btn btn-primary ms-4">
                {{ __('Register') }}
            </button>
        </div>
    </form>
@endsection
