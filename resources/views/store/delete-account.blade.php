@extends('store.layouts.app')

@section('title', __('store.delete_account.title').' | '.__('store.brand'))

@section('content')
<section class="mq-page">
    <div class="mq-container mq-legal">
        <div class="mq-breadcrumb">
            <a href="{{ route('store.home') }}">{{ __('store.common.home') }}</a>
            <span class="sep">/</span>
            <span>{{ __('store.delete_account.title') }}</span>
        </div>
        <h1 class="mq-page-title">{{ __('store.delete_account.title') }}</h1>
        <p class="mq-page-lead">{{ __('store.delete_account.lead') }}</p>

        <div class="mq-panel mq-prose">
            <h3>{{ __('store.delete_account.app_title') }}</h3>
            <ol>
                @foreach (__('store.delete_account.app_steps') as $step)
                    <li>{{ $step }}</li>
                @endforeach
            </ol>

            <h3>{{ __('store.delete_account.form_title') }}</h3>
            @if (session('success'))
                <p><strong>{{ session('success') }}</strong></p>
            @endif
            <form method="POST" action="{{ route('store.delete-account.submit') }}">
                @csrf
                <p><input type="text" name="phone" value="{{ old('phone') }}" placeholder="{{ __('store.delete_account.phone') }}" required inputmode="numeric" style="width:100%;padding:10px"></p>
                @error('phone') <p style="color:#c00">{{ $message }}</p> @enderror
                <p><input type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('store.delete_account.name') }}" style="width:100%;padding:10px"></p>
                <button type="submit" class="mq-btn mq-btn-primary">{{ __('store.delete_account.submit') }}</button>
            </form>

            <h3>{{ __('store.delete_account.data_title') }}</h3>
            <ul>
                <li>{{ __('store.delete_account.deleted') }}</li>
                <li>{{ __('store.delete_account.kept') }}</li>
            </ul>
            <p>{{ __('store.delete_account.email_note') }} <a href="mailto:{{ $email }}">{{ $email }}</a></p>
        </div>
    </div>
</section>
@endsection
