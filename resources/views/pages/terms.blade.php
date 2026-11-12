@extends('layouts.marketing')

@section('title', __('Terms and conditions').' | '.config('app.name'))

@section('content')
    @include('layouts.partials.breadcrumb', ['title' => __('Terms and conditions'), 'lead' => __('The rules for using Emca as a host, a guest, or a visitor.'), 'photo' => 'page-titles/privacy.jpg'])

    <section class="section-padding fix">
        <div class="auto-container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="emca-panel emca-prose wow fadeInUp" data-wow-delay="200ms">
                        <p class="emca-prose-meta">{{ __('Last updated: :date', ['date' => $updatedAt->translatedFormat('j F Y')]) }}</p>
                        <p>{{ __('These terms apply when you visit the Emca website, create a host account, or open an invitation card. By using the platform you agree to these terms. If you do not agree, please do not use the platform.') }}</p>
                        <p>{{ __('How we handle personal information is explained in the') }} <a href="{{ route('privacy') }}">{{ __('Privacy policy') }}</a>.</p>

                        <h2>{{ __('The service') }}</h2>
                        <p>{{ __('Emca helps hosts design digital invitation cards, share them through WhatsApp or SMS, track replies, and check guests in at the door with a code that works once. Depending on the plan, hosts can also keep records of pledges, budgets, tasks, and vendors.') }}</p>

                        <h2>{{ __('Host accounts') }}</h2>
                        <ul>
                            <li>{{ __('You must be at least 18 years old to create a host account.') }}</li>
                            <li>{{ __('The details you give us, including your name, phone number, and email address, must be accurate and kept up to date.') }}</li>
                            <li>{{ __('Keep your password private. You are responsible for everything done through your account.') }}</li>
                            <li>{{ __('Tell us straight away if you think someone else has used your account.') }}</li>
                        </ul>

                        <h2>{{ __('Plans and payment') }}</h2>
                        <ul>
                            <li>{{ __('Prices are shown on the pricing page in Tanzanian shillings and depend on the size of your guest list.') }}</li>
                            <li>{{ __('We confirm your plan and how to pay before your cards are sent.') }}</li>
                            <li>{{ __('We may change our prices, but a change will not affect a plan you have already paid for.') }}</li>
                            <li>{{ __('Fees are not refundable once invitations have been sent, unless the law requires a refund or we were unable to provide the service.') }}</li>
                        </ul>

                        <h2>{{ __('Your responsibilities as a host') }}</h2>
                        <ul>
                            <li>{{ __('Make sure event details such as the date, venue, and hosts\' names are correct before you send cards.') }}</li>
                            <li>{{ __('Only add guests you have a good reason to contact, and only the details the event needs.') }}</li>
                            <li>{{ __('Send messages only to people who expect to hear from you about the event. Do not use the platform for spam or unrelated promotions.') }}</li>
                            <li>{{ __('Messages are sent from your own WhatsApp account or phone. The terms of those services apply, and any network charges are yours.') }}</li>
                            <li>{{ __('You are responsible for the content you add and for any claims that arise from it or from the guest information you upload.') }}</li>
                        </ul>

                        <h2>{{ __('Acceptable use') }}</h2>
                        <p>{{ __('You must not use Emca to:') }}</p>
                        <ul>
                            <li>{{ __('Break the law or help anyone else break it.') }}</li>
                            <li>{{ __('Publish content that is false, hateful, threatening, obscene, or that infringes someone else\'s rights.') }}</li>
                            <li>{{ __('Pretend to be another person or create fake events, including to collect money or pledges dishonestly.') }}</li>
                            <li>{{ __('Try to access accounts or records that are not yours, test or break our security, or copy data from the platform in bulk.') }}</li>
                            <li>{{ __('Overload, disrupt, or interfere with the platform or other users.') }}</li>
                        </ul>

                        <h2>{{ __('Pledges and contributions') }}</h2>
                        <p>{{ __('Emca records pledges and contributions so hosts can keep track of them. We do not collect, hold, or transfer money. Any payment, and any disagreement about a pledge, is between the host and the contributor.') }}</p>

                        <h2>{{ __('Guests and invitation cards') }}</h2>
                        <ul>
                            <li>{{ __('Each card link is personal to the guest it was sent to. Please do not share it.') }}</li>
                            <li>{{ __('A check-in code works once. After it has been used at the door, it cannot be used again.') }}</li>
                            <li>{{ __('Your reply goes to the host. The host decides who is admitted and is responsible for the event itself.') }}</li>
                        </ul>

                        <h2>{{ __('Public listings') }}</h2>
                        <p>{{ __('A host can choose to publish an event on the upcoming events page. A published listing shows the title, hosts, date, and venue to anyone. By publishing, the host confirms that everyone named is happy for those details to be public.') }}</p>

                        <h2>{{ __('Content and ownership') }}</h2>
                        <p>{{ __('You keep ownership of the content you add, such as event details, messages, and guest lists. You allow Emca to store, display, and process that content only to provide the service to you and your guests.') }}</p>
                        <p>{{ __('The Emca name, logo, card templates, and software belong to Emca or the people who license them to us. You may use the templates for events run on the platform, but you may not copy, resell, or reuse them elsewhere without our written permission.') }}</p>

                        <h2>{{ __('Availability') }}</h2>
                        <p>{{ __('We work to keep the platform available, but we cannot promise it will always be free of interruptions or errors. We may pause the service for maintenance or updates. Keep your own copy of guest lists and other records that are important to you.') }}</p>

                        <h2>{{ __('Suspension and closing an account') }}</h2>
                        <p>{{ __('We may suspend or close an account that breaks these terms, puts other users at risk, or is used for fraud. Where it is reasonable, we will tell you first and give you a chance to put things right. You can close your account at any time by contacting us. What happens to your information afterwards is explained in the privacy policy.') }}</p>

                        <h2>{{ __('Limitation of liability') }}</h2>
                        <p>{{ __('Emca provides the platform as it is. We are not responsible for the event itself, for the work of vendors, for pledges that are not paid, or for messages delayed or blocked by WhatsApp or a mobile network. We are not liable for indirect losses such as lost profits or opportunities. Where the law allows, our total liability for any claim is limited to the fees you paid for the event concerned. Nothing in these terms limits rights you have under Tanzanian consumer law.') }}</p>

                        <h2>{{ __('Changes to these terms') }}</h2>
                        <p>{{ __('We may update these terms as the platform changes. The date at the top shows the latest version. If a change is important, we will tell hosts by email or in the dashboard before it takes effect. Continuing to use the platform after that means you accept the updated terms.') }}</p>

                        <h2>{{ __('Governing law') }}</h2>
                        <p>{{ __('These terms are governed by the laws of the United Republic of Tanzania. If a dispute arises, we will first try to resolve it with you in good faith. If that does not work, it will be settled by the courts of Tanzania.') }}</p>

                        <h2>{{ __('Contact us') }}</h2>
                        <p>{{ __('Send your questions to :email or :phone, or visit us at :address.', ['email' => config('emca.support_email'), 'phone' => config('emca.support_phone'), 'address' => config('emca.support_address')]) }}</p>
                        <p class="emca-prose-meta mb-0">{{ __('See also:') }} <a href="{{ route('privacy') }}">{{ __('Privacy policy') }}</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
