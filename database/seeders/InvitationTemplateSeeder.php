<?php

namespace Database\Seeders;

use App\Models\InvitationTemplate;
use Illuminate\Database\Seeder;

class InvitationTemplateSeeder extends Seeder
{
    /**
     * Seed three starter designs for every built-in event category.
     */
    public function run(): void
    {
        $styles = [
            'golden-classic' => [
                'name' => 'Golden Classic',
                'paper' => '#FFF9F0',
                'ink' => '#362B25',
                'accent' => '#B68A42',
                'font' => 'cormorant',
                'layout' => 'elegant',
                'description' => 'A timeless ivory design with gold accents for %s.',
            ],
            'botanical' => [
                'name' => 'Botanical',
                'paper' => '#F4F7F1',
                'ink' => '#24382E',
                'accent' => '#738B62',
                'font' => 'lora',
                'layout' => 'minimal',
                'description' => 'A calm, nature-inspired design in soft green for %s.',
            ],
            'modern-rose' => [
                'name' => 'Modern Rose',
                'paper' => '#F7F2F5',
                'ink' => '#30243B',
                'accent' => '#A64F70',
                'font' => 'montserrat',
                'layout' => 'modern',
                'description' => 'A contemporary rose-accented design for %s.',
            ],
        ];

        $categories = [
            'wedding' => [
                'name' => 'Wedding',
                'occasion' => 'a wedding celebration',
                'headline' => 'Together with our families',
                'message' => 'Join us as we celebrate our wedding. We look forward to sharing this special day with you.',
            ],
            'muslim_wedding' => [
                'name' => 'Muslim Wedding',
                'occasion' => 'a Muslim wedding celebration',
                'headline' => 'Celebrate our marriage',
                'message' => 'Join us and our families for a joyful wedding celebration. Your presence will make the day special.',
            ],
            'sendoff' => [
                'name' => 'Sendoff',
                'occasion' => 'a sendoff celebration',
                'headline' => 'A new journey begins',
                'message' => 'Join us as we celebrate this new beginning and wish our loved one well on the journey ahead.',
            ],
            'kitchen_party' => [
                'name' => 'Kitchen Party',
                'occasion' => 'a kitchen party',
                'headline' => 'A special celebration',
                'message' => 'Join us for a joyful kitchen party filled with love, laughter and good wishes.',
            ],
            'birthday' => [
                'name' => 'Birthday',
                'occasion' => 'a birthday celebration',
                'headline' => 'Another year to celebrate',
                'message' => 'Join us to celebrate a special birthday. Good company and happy memories await.',
            ],
            'gala_dinner' => [
                'name' => 'Gala Dinner',
                'occasion' => 'a gala dinner',
                'headline' => 'An evening to remember',
                'message' => 'Please join us for an elegant evening of conversation, celebration and great company.',
            ],
            'anniversary' => [
                'name' => 'Anniversary',
                'occasion' => 'an anniversary celebration',
                'headline' => 'Celebrating another year together',
                'message' => 'Join us as we look back on cherished memories and celebrate the years still to come.',
            ],
            'save_the_date' => [
                'name' => 'Save the Date',
                'occasion' => 'a special day',
                'headline' => 'Save the date',
                'message' => 'Please keep this special date free. More celebration details will be shared soon.',
            ],
            'contribution' => [
                'name' => 'Contributions and Meetings',
                'occasion' => 'a community gathering',
                'headline' => 'Your presence matters',
                'message' => 'Join us to connect, share ideas and work together toward a meaningful goal.',
            ],
            'corporate' => [
                'name' => 'Corporate',
                'occasion' => 'a corporate event',
                'headline' => 'You are cordially invited',
                'message' => 'We are pleased to invite you to join us for this special company occasion.',
            ],
            'other' => [
                'name' => 'Special Occasion',
                'occasion' => 'a special occasion',
                'headline' => 'You are invited',
                'message' => 'Join us for a special occasion and an enjoyable time together.',
            ],
        ];

        foreach ($categories as $category => $details) {
            foreach ($styles as $design) {
                InvitationTemplate::query()->firstOrCreate(
                    [
                        'category' => $category,
                        'name' => $details['name'].' - '.$design['name'],
                    ],
                    [
                        'price' => 0,
                        'paper' => $design['paper'],
                        'ink' => $design['ink'],
                        'accent' => $design['accent'],
                        'font' => $design['font'],
                        'layout' => $design['layout'],
                        'default_headline' => $details['headline'],
                        'default_message' => $details['message'],
                        'motif' => 'Venue to be announced',
                        'description' => sprintf($design['description'], $details['occasion']),
                    ],
                );
            }
        }
    }
}
