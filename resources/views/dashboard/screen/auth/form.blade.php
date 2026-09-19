<form  action="{{ $action }}" method="POST">
    @csrf

    {{-- email or phone --}}
    @isset($field)
        @if($field === 'phone')
            <input type="tel" name="phone" class="form-control" placeholder="{{__('inputs.phone')}}" required>
        @else
            <input type="email" name="email" class="form-control" placeholder="{{__('inputs.email')}}" required>
        @endif
    @endisset

    {{-- password (optional) --}}
    @if($showPassword ?? false)
        <input type="password" name="password" class="form-control mt-2" placeholder="{{__('inputs.password')}}" required>
    @endif
    @isset($extra_button)
        <div class="text-end mt-2">
            <a href="{{$extra_button['route']}}">
                {{ $extra_button['text'] }}
            </a>
        </div>
    @endisset

    {{-- new password (reset page) --}}
    @if($showNewPassword ?? false)
        <input type="hidden" name="email" value="{{ request('email') }}" required>
        <input type="hidden" name="token" value="{{ $token }}" required>
        <input type="password" name="password" class="form-control mt-2" placeholder="{{__('inputs.password')}}" required minlength="6">
        <input type="password" name="password_confirmation" class="form-control mt-2" placeholder="{{__('inputs.confirm_password')}}" required minlength="6">
    @endif

    <button type="submit" class="form-control btn btn-primary mt-3">
        {{ __('buttons.send') }}
    </button>
</form>
