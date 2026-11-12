@extends('layouts.marketing')

@section('title', __('Privacy policy').' | '.config('app.name'))

@section('content')
    @include('layouts.partials.breadcrumb', ['title' => __('Privacy policy'), 'lead' => __('What we collect, why we keep it, and who can see it.'), 'photo' => 'page-titles/privacy.jpg'])

    <section class="section-padding fix">
        <div class="auto-container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="emca-panel emca-prose wow fadeInUp" data-wow-delay="200ms">
                        <p class="emca-prose-meta">{{ __('Last updated: :date', ['date' => $updatedAt->translatedFormat('j F Y')]) }}</p>
                        <p>{{ __('This policy explains how Emca collects, uses, and protects personal information when you visit our website, run an event as a host, or open an invitation card as a guest. We handle personal information in line with the Personal Data Protection Act, 2022 of the United Republic of Tanzania.') }}</p>

                        <h2>{{ __('Who we are') }}</h2>
                        <p>{{ __('Emca runs this platform from :address, Tanzania. For host accounts and demo requests, Emca decides how the information is used. For guest lists, the host decides who is invited and what is recorded, and Emca processes that information on the host\'s behalf.', ['address' => config('emca.support_address')]) }}</p>

                        <h2>{{ __('Information we collect') }}</h2>
                        <p>{{ __('We collect only what is needed to run an event:') }}</p>
                        <ul>
                            <li>{{ __('Host accounts: first name, last name, phone number, email address, and password. Passwords are stored in scrambled (hashed) form, so nobody at Emca can read them.') }}</li>
                            <li>{{ __('Events: the title, type, hosts\' names, date, venue, card design, and any details the host adds.') }}</li>
                            <li>{{ __('Guest lists: guest names, phone numbers, email addresses, replies, party size, notes, card numbers, and the time a card was used at the door.') }}</li>
                            <li>{{ __('Planning records: pledges and contributions (contributor name, phone number, amount, and status), budget items, tasks, and vendor details such as name, category, phone number, and price.') }}</li>
                            <li>{{ __('Message records: the text, channel, purpose, and time of each invitation, reminder, or thank-you message prepared in the platform.') }}</li>
                            <li>{{ __('Demo requests: the name, email, phone number, organisation, event type, expected number of guests, and message you submit.') }}</li>
                            <li>{{ __('Technical information: your IP address, browser type, session details, and the language you choose for the site.') }}</li>
                        </ul>

                        <h2>{{ __('How we use information') }}</h2>
                        <ul>
                            <li>{{ __('To create invitation cards, record replies, and check guests in with a code that works once.') }}</li>
                            <li>{{ __('To let hosts manage guest lists, pledges, budgets, tasks, and vendors for their events.') }}</li>
                            <li>{{ __('To prepare the messages a host chooses to send to guests.') }}</li>
                            <li>{{ __('To reply to demo requests and support questions.') }}</li>
                            <li>{{ __('To keep accounts secure, prevent misuse, and fix problems.') }}</li>
                            <li>{{ __('To meet our legal and accounting obligations.') }}</li>
                        </ul>
                        <p>{{ __('We do not sell personal information, and we do not use guest lists to market our own services.') }}</p>

                        <h2>{{ __('Who can see it') }}</h2>
                        <p>{{ __('That information is visible to the host who created the event and to Emca administrators. A guest can open only the card sent to them. A public listing shows the title, hosts, date, and venue when the host chooses to publish it.') }}</p>
                        <p>{{ __('Messages prepared in the platform are logged so the host can see what was ready to send. Delivery still happens through WhatsApp or the phone\'s SMS app.') }} {{ __('Once a message leaves the platform, WhatsApp and your mobile network handle it under their own privacy policies.') }}</p>
                        <p>{{ __('We also rely on a small number of service providers to run the platform, such as our hosting provider and the services that deliver web fonts and the QR code tool to your browser. They receive only what they need to provide that service, such as your IP address. We share information with authorities only when the law requires it.') }}</p>

                        <h2>{{ __('Hosts and guest information') }}</h2>
                        <p>{{ __('Hosts are responsible for the guest information they add. Before adding someone, a host should have a good reason to contact them, such as inviting them to the event, and should add only the details the event needs. Guests who want their details corrected or removed can ask the host directly or contact us.') }}</p>

                        <h2>{{ __('Cookies') }}</h2>
                        <p>{{ __('We use a small number of cookies that the site needs to work:') }}</p>
                        <ul>
                            <li>{{ __('A session cookie that keeps you signed in and remembers your place on the site.') }}</li>
                            <li>{{ __('A security cookie that protects forms from being submitted by other websites.') }}</li>
                            <li>{{ __('A language cookie that remembers whether you chose Kiswahili or English.') }}</li>
                        </ul>
                        <p>{{ __('We do not use advertising or tracking cookies.') }}</p>

                        <h2>{{ __('How long we keep information') }}</h2>
                        <ul>
                            <li>{{ __('Host accounts are kept while the account is open.') }}</li>
                            <li>{{ __('Events, guest lists, and planning records are kept until the host deletes them or closes the account.') }}</li>
                            <li>{{ __('Demo requests are kept for up to 12 months after we last contacted you.') }}</li>
                            <li>{{ __('Deleted information may stay in backups for a short time before it is overwritten.') }}</li>
                        </ul>
                        <p>{{ __('We may keep some records longer when the law requires it, for example for accounting.') }}</p>

                        <h2>{{ __('How we protect information') }}</h2>
                        <p>{{ __('Passwords are hashed, each invitation link uses a long random code that is hard to guess, and only the host and Emca administrators can open an event\'s records. Check-in codes work once, so a forwarded card cannot be used twice at the door. No system is completely secure, so please tell us straight away if you think your account has been misused.') }}</p>

                        <h2>{{ __('Your rights') }}</h2>
                        <p>{{ __('Under Tanzanian data protection law you can ask us to:') }}</p>
                        <ul>
                            <li>{{ __('Tell you what personal information we hold about you and give you a copy.') }}</li>
                            <li>{{ __('Correct information that is wrong or incomplete.') }}</li>
                            <li>{{ __('Delete information we no longer need or have no right to keep.') }}</li>
                            <li>{{ __('Stop or limit how we use your information, or withdraw consent you gave earlier.') }}</li>
                        </ul>
                        <p>{{ __('Send requests to :email or :phone. We may ask you to confirm who you are before we act, and we will reply within 30 days. If you are not satisfied with our answer, you can complain to the Personal Data Protection Commission of Tanzania.', ['email' => config('emca.support_email'), 'phone' => config('emca.support_phone')]) }}</p>

                        <h2>{{ __('Children') }}</h2>
                        <p>{{ __('Host accounts are for people aged 18 and over. Hosts who add children to a guest list should do so with the agreement of a parent or guardian.') }}</p>

                        <h2>{{ __('Information stored outside Tanzania') }}</h2>
                        <p>{{ __('Some of our service providers may store or process information outside Tanzania. When that happens, we take the steps the law requires to keep it protected.') }}</p>

                        <h2>{{ __('Changes to this policy') }}</h2>
                        <p>{{ __('We may update this policy as the platform changes. The date at the top shows the latest version. If a change affects how we use your information in an important way, we will tell hosts by email or in the dashboard before it takes effect.') }}</p>

                        <h2>{{ __('Contact us') }}</h2>
                        <p>{{ __('Send your questions to :email or :phone, or visit us at :address.', ['email' => config('emca.support_email'), 'phone' => config('emca.support_phone'), 'address' => config('emca.support_address')]) }}</p>
                        <p class="emca-prose-meta mb-0">{{ __('See also:') }} <a href="{{ route('terms') }}">{{ __('Terms and conditions') }}</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
