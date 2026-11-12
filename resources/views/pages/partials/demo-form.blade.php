<section class="contact-details" id="demo" data-emca-region="demo-form">
    <div class="auto-container pt-110 pb-110">
        <div class="row gy-5">
            <div class="col-xl-7 col-lg-6">
                <div class="sec-title mb-40">
                    <div class="h6 sub-title">{{ __('Walkthrough') }}</div>
                    <h2 class="h2 title">{{ __('Book a call') }}</h2>
                </div>
                <form method="POST" action="{{ route('demos.store') }}" class="emca-form">
                    @csrf
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <input class="form-control" name="name" value="{{ old('name') }}" placeholder="{{ __('Your name') }}" required>
                            @error('name')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="col-sm-6">
                            <input class="form-control" type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('Email') }}" required>
                            @error('email')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="col-sm-6">
                            <input class="form-control" name="phone" value="{{ old('phone') }}" placeholder="{{ __('Phone') }}" required>
                            @error('phone')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="col-sm-6">
                            <input class="form-control" name="organization" value="{{ old('organization') }}" placeholder="{{ __('Family or organisation') }}">
                            @error('organization')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="col-sm-6">
                            <select class="form-select" name="event_type">
                                <option value="">{{ __('Event type') }}</option>
                                @foreach (\App\Models\EventCategory::options() as $type)
                                    <option value="{{ $type->slug }}" @selected(old('event_type') === $type->slug)>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                            @error('event_type')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="col-sm-6">
                            <input class="form-control" type="number" min="1" name="guest_count" value="{{ old('guest_count') }}" placeholder="{{ __('Expected guests') }}">
                            @error('guest_count')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="col-12">
                            <textarea class="form-control" name="message" rows="5" placeholder="{{ __('What should we know?') }}">{{ old('message') }}</textarea>
                            @error('message')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="col-12 pt-2">
                            <x-site-button>{{ __('Request a demo') }}</x-site-button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-xl-5 col-lg-6">
                <div class="contact-details__right">
                    <div class="sec-title">
                        <h2 class="h2 title">{{ __('Talk to us') }}</h2>
                        <div class="text mt-3">{{ __('Tell us the occasion and roughly how many guests. We will call the number you leave here. You can also reach us at :phone.', ['phone' => config('emca.support_phone')]) }}</div>
                    </div>
                    <ul class="list-unstyled contact-details__info">
                        <li>
                            <div class="icon"><span class="fa-light fa-phone"></span></div>
                            <div class="text">
                                <div class="h6 mb-1">{{ __('Call us') }}</div>
                                <a href="tel:{{ preg_replace('/\s+/', '', config('emca.support_phone')) }}">{{ config('emca.support_phone') }}</a>
                            </div>
                        </li>
                        <li>
                            <div class="icon"><span class="fa-light fa-envelope"></span></div>
                            <div class="text">
                                <div class="h6 mb-1">{{ __('Send an email') }}</div>
                                <a href="mailto:{{ config('emca.support_email') }}">{{ config('emca.support_email') }}</a>
                            </div>
                        </li>
                        <li>
                            <div class="icon"><span class="fa-light fa-location-dot"></span></div>
                            <div class="text">
                                <div class="h6 mb-1">{{ __('Visit us') }}</div>
                                <span>{{ config('emca.support_address') }}</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
