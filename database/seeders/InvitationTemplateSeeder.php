<?php

namespace Database\Seeders;

use App\Models\InvitationTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class InvitationTemplateSeeder extends Seeder
{
    /**
     * Give every occasion its own set of original stationery, instead of one trio repeated everywhere.
     */
    public function run(): void
    {
        $kept = [];

        foreach ($this->catalog() as $category => $designs) {
            foreach ($designs as $index => $design) {
                $art = $this->arts()[$design['art']];
                $name = $design['label'].' - '.$art['name'];
                $previousName = $design['label'].' - '.($design['replaces'] ?? '');

                $template = InvitationTemplate::query()
                    ->where('category', $category)
                    ->where('name', $name)
                    ->first();

                if (! $template && $design['replaces'] !== null) {
                    $template = InvitationTemplate::query()
                        ->where('category', $category)
                        ->where('name', $previousName)
                        ->first();
                }

                $template ??= new InvitationTemplate;
                $kept[$category][$name] = true;

                $attributes = [
                    'name' => $name,
                    'category' => $category,
                    'price' => 0,
                    'host_only' => empty($design['public']) && $index !== 0 && ! isset($design['portrait']),
                    'paper' => $art['paper'],
                    'ink' => $art['ink'],
                    'accent' => $art['accent'],
                    'font' => $art['font'],
                    'layout' => 'classic',
                    'background_path' => $this->publishArtwork($art['file']),
                    'portrait' => $this->portraitPayload($design),
                    'default_headline' => $design['headline'],
                    'default_message' => $design['message'],
                    'motif' => 'Venue to be announced',
                    'description' => $art['description'],
                ];

                if ($template->exists) {
                    unset($attributes['price'], $attributes['host_only']);

                    if (filled($template->default_headline) && $template->default_headline !== $attributes['default_headline']) {
                        unset($attributes['default_headline']);
                    }

                    if (filled($template->default_message) && $template->default_message !== $attributes['default_message']) {
                        unset($attributes['default_message']);
                    }

                    if (filled($template->motif) && $template->motif !== $attributes['motif']) {
                        unset($attributes['motif']);
                    }

                    $stockBackground = blank($template->background_path)
                        || str_starts_with((string) $template->background_path, 'card-templates/');

                    if (! $stockBackground) {
                        unset(
                            $attributes['background_path'],
                            $attributes['paper'],
                            $attributes['ink'],
                            $attributes['accent'],
                            $attributes['font'],
                            $attributes['layout'],
                            $attributes['portrait'],
                            $attributes['description'],
                        );
                    }
                }

                $template->forceFill($attributes)->save();
            }
        }

        $this->removePaddedStationery($kept);
    }

    /**
     * Drop stock cards that were copied in only to make every category the same length.
     * An event's card, and a picture an admin uploaded, stay.
     *
     * @param  array<string, array<string, true>>  $kept
     */
    private function removePaddedStationery(array $kept): void
    {
        InvitationTemplate::query()
            ->where('background_path', 'like', 'card-templates/%')
            ->whereDoesntHave('events')
            ->orderBy('id')
            ->get()
            ->each(function (InvitationTemplate $template) use ($kept): void {
                $category = (string) $template->getRawOriginal('category');

                if (! isset($kept[$category][$template->name])) {
                    $template->delete();
                }
            });
    }

    private function publishArtwork(string $file): string
    {
        $path = 'card-templates/'.$file;
        $source = public_path('images/card-templates/'.$file);

        if (is_file($source)) {
            Storage::disk('public')->put($path, file_get_contents($source));
        }

        return $path;
    }

    /**
     * @param  array{portrait?: string, slot?: string}  $design
     * @return array{path: string, shape: string}|null
     */
    private function portraitPayload(array $design): ?array
    {
        if (! isset($design['portrait'], $design['slot'])) {
            return null;
        }

        return [
            'path' => $this->publishArtwork('portraits/'.$design['portrait']),
            'shape' => $design['slot'],
        ];
    }

    /**
     * @return array<string, array{name: string, file: string, paper: string, ink: string, accent: string, font: string, description: string}>
     */
    private function arts(): array
    {
        return [
            'bougainvillea' => [
                'name' => 'Bougainvillea',
                'file' => 'bougainvillea.jpg',
                'paper' => '#F7F1E6',
                'ink' => '#3A2A24',
                'accent' => '#B4532A',
                'font' => 'cormorant',
                'description' => 'Ivory paper with magenta and terracotta bougainvillea in two corners.',
            ],
            'acacia' => [
                'name' => 'Acacia',
                'file' => 'acacia.jpg',
                'paper' => '#1C3A2E',
                'ink' => '#F6F1E4',
                'accent' => '#D4B56A',
                'font' => 'cormorant',
                'description' => 'Deep green paper with brass acacia trees along the top and bottom.',
            ],
            'kanga' => [
                'name' => 'Kanga Border',
                'file' => 'kanga.jpg',
                'paper' => '#F3E6D4',
                'ink' => '#2C241C',
                'accent' => '#C4652D',
                'font' => 'playfair',
                'description' => 'A cream panel inside an original cloth border of teal, saffron, and clay.',
            ],
            'jasmine' => [
                'name' => 'Night Jasmine',
                'file' => 'jasmine.jpg',
                'paper' => '#1B1740',
                'ink' => '#F7F3EA',
                'accent' => '#E7D7A1',
                'font' => 'cormorant',
                'description' => 'Indigo paper with white jasmine and a few gold dots in opposite corners.',
            ],
            'hibiscus' => [
                'name' => 'Hibiscus',
                'file' => 'hibiscus.jpg',
                'paper' => '#F3E4C8',
                'ink' => '#3E2A22',
                'accent' => '#C4523A',
                'font' => 'lora',
                'description' => 'Sand paper with coral hibiscus along one side.',
            ],
            'sunburst' => [
                'name' => 'Evening Sun',
                'file' => 'sunburst.jpg',
                'paper' => '#161616',
                'ink' => '#F4EFE4',
                'accent' => '#D7B56A',
                'font' => 'cormorant',
                'description' => 'Charcoal paper with a small gold sun and a single line.',
            ],
            'olive' => [
                'name' => 'Olive Branch',
                'file' => 'olive.jpg',
                'paper' => '#E4E6DF',
                'ink' => '#2E342C',
                'accent' => '#5E6B58',
                'font' => 'lora',
                'description' => 'Quiet stone paper with olive branches and no gold.',
            ],
            'frangipani' => [
                'name' => 'Frangipani',
                'file' => 'frangipani.jpg',
                'paper' => '#FBF7F0',
                'ink' => '#2F3330',
                'accent' => '#C4A15A',
                'font' => 'playfair',
                'description' => 'Warm white paper with frangipani across the top and a brass dot rule.',
            ],
            'palm' => $this->art('Palm Court', 'palm-court.jpg', '#F6F0E4', '#3A3428', '#8A7340', 'playfair', 'Ivory paper with date palms in the corners.'),
            'clay_arch' => $this->art('Clay Arch', 'clay-arch.jpg', '#F3E4D4', '#5C3A2E', '#C47A52', 'cormorant', 'Terracotta paper with a clay arch around an open center.'),
            'baobab' => $this->art('Baobab', 'baobab.jpg', '#F4E6D4', '#3E2A22', '#C47B4A', 'cormorant', 'Dusk paper with baobab silhouettes at the sides.'),
            'papyrus' => $this->art('Papyrus', 'papyrus.jpg', '#F4F7F2', '#2C3A32', '#7A8F78', 'lora', 'Pale paper with papyrus reeds along the edges.'),
            'lanterns' => $this->art('Lanterns', 'lanterns.jpg', '#14343C', '#F6F1E4', '#D4B56A', 'cormorant', 'Teal paper with brass lanterns at the top.'),
            'crescent' => $this->art('Crescent', 'crescent.jpg', '#141A38', '#F7F3EA', '#E7D7A1', 'cormorant', 'Midnight paper with a small crescent above the words.'),
            'henna' => $this->art('Henna Edge', 'henna-edge.jpg', '#FBF6EE', '#3E2A22', '#B4532A', 'playfair', 'Ivory paper with a henna-style border.'),
            'sand_arch' => $this->art('Sand Arch', 'sand-arch.jpg', '#F6EFE4', '#3A2A24', '#C4A15A', 'cormorant', 'Sand paper with a pointed brass arch.'),
            'marigold' => $this->art('Marigold', 'marigold.jpg', '#FBF6EE', '#3E2A22', '#D06A2B', 'lora', 'Cream paper with marigolds along one side.'),
            'dawn' => $this->art('Dawn Path', 'dawn-road.jpg', '#F8EFE6', '#3A2A24', '#C48A62', 'cormorant', 'Dawn paper with a quiet path at the bottom.'),
            'reeds' => $this->art('River Reeds', 'reeds.jpg', '#EEF2F3', '#2C3438', '#7E8C90', 'lora', 'Blue-grey paper with reeds along the bottom.'),
            'cocoa' => $this->art('Cocoa Leaf', 'cocoa.jpg', '#E7D3C0', '#3A2A22', '#8A5A3A', 'playfair', 'Cocoa paper with leaves in the top corners.'),
            'citrus' => $this->art('Citrus', 'citrus.jpg', '#FBF7F0', '#3A2A22', '#D08A2A', 'lora', 'Linen paper with citrus in the corners.'),
            'enamel' => $this->art('Enamel Cups', 'enamel.jpg', '#F7F1E8', '#3A2A24', '#C4652D', 'cormorant', 'Cream paper with enamel cups along the bottom.'),
            'mango' => $this->art('Mango Leaf', 'mango.jpg', '#F6EBD8', '#3E2A22', '#C47A2A', 'lora', 'Sand paper with mango leaves at the top.'),
            'picnic' => $this->art('Picnic Border', 'picnic.jpg', '#F8F3EA', '#2C241C', '#2F6F6A', 'playfair', 'Cream paper inside a clay and teal check border.'),
            'confetti' => $this->art('Confetti', 'confetti.jpg', '#FFFAF6', '#3A2A24', '#C4523A', 'lora', 'White paper with confetti near the edges.'),
            'bunting' => $this->art('Bunting', 'bunting.jpg', '#FBF7F2', '#3A2A24', '#C4523A', 'playfair', 'Cream paper with a short bunting string at the top.'),
            'meadow' => $this->art('Meadow', 'meadow.jpg', '#F8EFE8', '#3E2A22', '#C46A7A', 'lora', 'Blush paper with wildflowers in the corners.'),
            'candles' => $this->art('Candles', 'candles.jpg', '#FBF6EE', '#3A2A24', '#C4A15A', 'cormorant', 'Ivory paper with small candles in the corners.'),
            'marble' => $this->art('Marble', 'marble.jpg', '#F4F2EF', '#2A2A2A', '#C4A15A', 'playfair', 'Pale marble with a double gold rule.'),
            'candlelight' => $this->art('Candlelight', 'candlelight.jpg', '#1A1A1A', '#F4EFE4', '#D7B56A', 'cormorant', 'Charcoal paper with candlesticks in the corners.'),
            'burgundy' => $this->art('Burgundy', 'burgundy.jpg', '#4A1C24', '#F6F1E4', '#D4B56A', 'playfair', 'Burgundy paper with a single gold frame.'),
            'skyline' => $this->art('Night Skyline', 'skyline.jpg', '#12182A', '#F4EFE4', '#D7B56A', 'montserrat', 'Navy paper with a low gold skyline.'),
            'bands' => $this->art('Two Bands', 'bands.jpg', '#FBF7F0', '#3A2A24', '#C4A15A', 'cormorant', 'Ivory paper with two small gold bands at the top.'),
            'corners' => $this->art('Photo Corners', 'corners.jpg', '#F7F1E6', '#3A2A24', '#B68A42', 'playfair', 'Cream paper with brass photo corners.'),
            'two_olive' => $this->art('Paired Olive', 'two-olive.jpg', '#E4E6DF', '#2E342C', '#5E6B58', 'lora', 'Sage paper with two olive sprigs.'),
            'gold_dots' => $this->art('Gold Dots', 'gold-dots.jpg', '#FBF7F0', '#3A2A24', '#C4A15A', 'cormorant', 'Ivory paper with a border of gold dots.'),
            'calendar' => $this->art('Calendar', 'calendar.jpg', '#FBF8F3', '#2C241C', '#8A7340', 'montserrat', 'Cream paper with a blank calendar corner.'),
            'liner' => $this->art('Envelope Liner', 'liner.jpg', '#F7F4EE', '#2F3330', '#5E6B58', 'lora', 'White paper with a leaf-pattern liner border.'),
            'stamp' => $this->art('Blank Stamp', 'stamp.jpg', '#F8F4EC', '#3A2A24', '#8A7340', 'playfair', 'Cream paper with a blank stamp in the corner.'),
            'linen' => $this->art('Linen Note', 'linen.jpg', '#F3EEE4', '#2C241C', '#C4A15A', 'cormorant', 'Linen paper with one brass rule.'),
            'canopy' => $this->art('Shade Canopy', 'canopy.jpg', '#E7EFE4', '#243028', '#5E6B58', 'lora', 'Green paper with a tree canopy at the top.'),
            'circle' => $this->art('Circle of Dots', 'circle.jpg', '#EFE4D6', '#3A2A24', '#C47A52', 'cormorant', 'Clay paper with a small circle of dots.'),
            'notebook' => $this->art('Notebook', 'notebook.jpg', '#F7F5F0', '#2C241C', '#8A8178', 'montserrat', 'Notebook paper with a single margin line.'),
            'clay_pots' => $this->art('Clay Pots', 'clay-pots.jpg', '#E8D7C4', '#3A2A22', '#A86B45', 'lora', 'Earth paper with clay pots at the bottom.'),
            'navy' => $this->art('Navy Frame', 'navy-frame.jpg', '#152033', '#F4EFE4', '#D4B56A', 'montserrat', 'Navy paper with a brass rectangle.'),
            'grid' => $this->art('Quiet Grid', 'grid.jpg', '#F2F3F5', '#1E2430', '#5C6B7A', 'montserrat', 'Grey paper with a faint grid.'),
            'seal' => $this->art('Blank Seal', 'seal.jpg', '#FBF7F0', '#2A2A2A', '#C4A15A', 'playfair', 'Ivory paper with a blank seal at the top.'),
            'harbor' => $this->art('Harbor Line', 'harbor.jpg', '#D5DEE6', '#1E2A36', '#6E8494', 'montserrat', 'Slate paper with a low harbor line.'),
            'wildflower' => $this->art('Wildflower', 'wildflower.jpg', '#F7F3EA', '#2E342C', '#6E8A62', 'lora', 'Meadow paper with wildflowers along the bottom.'),
            'moon' => $this->art('Moon', 'moon.jpg', '#14182C', '#F7F3EA', '#E7D7A1', 'cormorant', 'Night paper with a small moon.'),
            'stones' => $this->art('River Stones', 'stones.jpg', '#E6E4E1', '#2C3438', '#8A8680', 'lora', 'Grey paper with river stones along the bottom.'),
            'saffron' => $this->art('Saffron Border', 'saffron.jpg', '#FBF6EE', '#2C241C', '#C47A2A', 'playfair', 'Cream paper with a saffron and indigo border.'),
            'photo_arch_palm' => $this->art('Couple Arch', 'photo-arch-palm.jpg', '#F7F1E6', '#3A2A24', '#C4A15A', 'cormorant', 'A gold arch for the couple photo, with palms below the words.'),
            'photo_arch_henna' => $this->art('Henna Portrait', 'photo-arch-henna.jpg', '#14343C', '#F6F1E4', '#D4B56A', 'cormorant', 'A teal arch that holds the couple photo above the wording.'),
            'photo_circle_confetti' => $this->art('Birthday Portrait', 'photo-circle-confetti.jpg', '#FFFAF6', '#3A2A24', '#C4523A', 'lora', 'A round frame for one photo, with confetti around it.'),
            'photo_frame_candle' => $this->art('Evening Portrait', 'photo-frame-candle.jpg', '#1A1A1A', '#F4EFE4', '#D7B56A', 'playfair', 'A wide frame for a photo, with candles at the bottom.'),
            'photo_frame_marigold' => $this->art('Farewell Portrait', 'photo-frame-marigold.jpg', '#FBF6EE', '#3E2A22', '#D06A2B', 'lora', 'A wide frame for a photo, with marigolds below.'),
            'photo_frame_citrus' => $this->art('Kitchen Portrait', 'photo-frame-citrus.jpg', '#FBF7F0', '#3A2A22', '#D08A2A', 'lora', 'A wide frame for a photo, with citrus in the corners.'),
            'photo_arch_olive' => $this->art('Together Portrait', 'photo-arch-olive.jpg', '#E4E6DF', '#2E342C', '#5E6B58', 'lora', 'A quiet arch for a photo, with olive at the bottom.'),
            'photo_frame_stamp' => $this->art('Date Portrait', 'photo-frame-stamp.jpg', '#F8F4EC', '#3A2A24', '#8A7340', 'playfair', 'A wide frame for a photo on save-the-date paper.'),
            'photo_frame_navy' => $this->art('Speaker Portrait', 'photo-frame-navy.jpg', '#152033', '#F4EFE4', '#D4B56A', 'montserrat', 'A wide frame for a photo on navy paper.'),
            'photo_circle_canopy' => $this->art('Gathering Portrait', 'photo-circle-canopy.jpg', '#E7EFE4', '#243028', '#5E6B58', 'lora', 'A round frame for a photo under a green canopy.'),
            'photo_frame_wild' => $this->art('Occasion Portrait', 'photo-frame-wild.jpg', '#F7F3EA', '#2E342C', '#C47A2A', 'playfair', 'A wide frame for a photo, with wildflowers below.'),
            'scene_silk' => $this->art('Silk Night', 'scene-silk.jpg', '#0D0D0D', '#F3D27A', '#E2B340', 'playfair', 'Black silk with gold bunting and balloons in the corners.'),
            'scene_medallion' => $this->art('Gold Medallion', 'scene-medallion.jpg', '#0C0C0C', '#F6E7B4', '#E2B340', 'playfair', 'A gold ring for one photo, with balloons and a gift.'),
            'scene_filigree' => $this->art('Gold Filigree', 'scene-filigree.jpg', '#070707', '#F3E6C0', '#D4B56A', 'cormorant', 'Black paper with gold filigree in the corners.'),
            'scene_polka' => $this->art('Polka Dots', 'scene-polka.jpg', '#F6EFE4', '#1D1A17', '#E4572E', 'montserrat', 'Cream paper with bright dots around one photo.'),
            'scene_navy' => $this->art('Navy Floral', 'scene-navy.jpg', '#1C3148', '#F7F1EA', '#E7B4C2', 'cormorant', 'A navy panel with blush flowers in the top corner.'),
            'scene_peony' => $this->art('Peony Arch', 'scene-peony.jpg', '#FBF6F1', '#3A2A32', '#A85A6A', 'cormorant', 'Ivory paper inside a peony arch.'),
            'scene_wreath' => $this->art('Violet Wreath', 'scene-wreath.jpg', '#F7F4FB', '#2C2438', '#6D4D86', 'cormorant', 'A violet wreath around the names.'),
            'scene_burgundy' => $this->art('Burgundy Frame', 'scene-burgundy.jpg', '#4A1420', '#F6E7C1', '#D4B56A', 'playfair', 'Burgundy paper inside a gold frame.'),
            'scene_bow' => $this->art('Satin Bow', 'scene-bow.jpg', '#F7F5F2', '#2C3140', '#8AA0B4', 'cormorant', 'A satin bow beside watercolor flowers.'),
            'scene_wash' => $this->art('Blue Wash', 'scene-wash.jpg', '#F4F8FC', '#1E3A5F', '#3D7EA6', 'lora', 'A blue watercolor ring for one photo.'),
            'scene_chalk' => $this->art('Chalkboard', 'scene-chalk.jpg', '#1A1C16', '#F4F1E8', '#F4F1E8', 'lora', 'A polaroid on a chalkboard.'),
            'scene_balloons' => $this->art('Balloon Gold', 'scene-balloons.jpg', '#090909', '#F3D27A', '#E2B340', 'playfair', 'Black paper with gold balloons along the bottom.'),
            'scene_tulip' => $this->art('Tulip Oval', 'scene-tulip.jpg', '#5C1020', '#F6E7C1', '#E7C98A', 'cormorant', 'A burgundy oval with tulips at the foot.'),
            'scene_torn' => $this->art('Torn Paper', 'scene-torn.jpg', '#F6F1E8', '#2C2824', '#3E6B4F', 'cormorant', 'A photo above a torn paper edge.'),
            'scene_eucalyptus' => $this->art('Eucalyptus', 'scene-eucalyptus.jpg', '#F7F8F6', '#243028', '#5E6B58', 'lora', 'White paper with eucalyptus at the top and bottom.'),
            'scene_envelope' => $this->art('Envelope Seal', 'scene-envelope.jpg', '#F4EFE6', '#6D1D2C', '#8D1F2F', 'cormorant', 'A cream card rising from a red envelope.'),
            'scene_gala' => $this->art('Gala Fans', 'scene-gala.jpg', '#0A0A0A', '#F6E7B4', '#E2B340', 'playfair', 'Black paper with gold fans in the corners.'),
            'scene_nikah' => $this->art('Nikah Gold', 'scene-nikah.jpg', '#070707', '#F3E6C0', '#D4B56A', 'cormorant', 'A black and gold frame with flowers along the foot.'),
            'scene_palace' => $this->art('Palace Court', 'scene-palace.jpg', '#F3E6D4', '#3A2A24', '#A33B4A', 'playfair', 'A palace court with room for the names in the center.'),
            'scene_stripes' => $this->art('Pink Stripes', 'scene-stripes.jpg', '#F8E7EE', '#7A3048', '#C45B78', 'cormorant', 'Pink stripes with a small dress drawing.'),
            'scene_cake' => $this->art('Cake Oval', 'scene-cake.jpg', '#FBEAF1', '#7A3048', '#C45B78', 'cormorant', 'A pink oval with a small cake at the foot.'),
            'scene_baby' => $this->art('Rose Circle', 'scene-baby.jpg', '#FBEFF3', '#5C3040', '#C46A86', 'lora', 'A ring of roses around one photo.'),
            'scene_wavy' => $this->art('Wavy Border', 'scene-wavy.jpg', '#F6F1E6', '#1A1A1A', '#1A1A1A', 'playfair', 'A hand-drawn wavy border on cream paper.'),
            'scene_dust' => $this->art('Gold Dust', 'scene-dust.jpg', '#0A0A0A', '#F6E7B4', '#E2B340', 'cormorant', 'Black paper with gold dust across the top.'),
            'scene_rosette' => $this->art('Rosette Gala', 'scene-rosette.jpg', '#F4EFE6', '#1A1A1A', '#C4A15A', 'playfair', 'Ivory paper with gold fans in the corners.'),
            'scene_arch' => $this->art('Gold Arch', 'scene-arch.jpg', '#070707', '#F3E6C0', '#D4B56A', 'cormorant', 'A gold arch on black paper.'),
            'scene_wine' => $this->art('Wine Glass', 'scene-wine.jpg', '#1C2A44', '#F7F4EE', '#E2B340', 'montserrat', 'Navy paper with a gold wine glass.'),
            'scene_lace' => $this->art('Lace Oval', 'scene-lace.jpg', '#F7F3EA', '#2C3428', '#6E7D62', 'cormorant', 'A white lace oval on sage paper.'),
            'scene_ribbon' => $this->art('Ribbon Dinner', 'scene-ribbon.jpg', '#F7F6F1', '#243028', '#3D5C45', 'cormorant', 'Striped paper with ribbon bows and a small glass.'),
            'scene_blush' => $this->art('Blush Roses', 'scene-blush.jpg', '#F8E8EA', '#5C3040', '#C46A86', 'cormorant', 'Blush paper with roses in two corners.'),
            'scene_autumn' => $this->art('Autumn Border', 'scene-autumn.jpg', '#FBF6EE', '#3A2A22', '#A85A3A', 'playfair', 'Cream paper inside an autumn flower border.'),
            'scene_citrusring' => $this->art('Orange Wreath', 'scene-citrusring.jpg', '#FFFAF4', '#2C3A28', '#D07A2A', 'lora', 'White paper with oranges around the edge.'),
            'scene_meadowline' => $this->art('Wildflower Foot', 'scene-meadowline.jpg', '#FBFAF7', '#2E342C', '#6E8A62', 'lora', 'White paper with wildflowers along the foot.'),
            'scene_column' => $this->art('Flower Column', 'scene-column.jpg', '#FBFAF7', '#243028', '#5E6B58', 'lora', 'A column of white flowers down one side.'),
            'scene_split' => $this->art('Split Panel', 'scene-split.jpg', '#5C5344', '#F6F1E4', '#E7D7B0', 'playfair', 'An olive panel beside one photograph.'),
            'scene_manor' => $this->art('Manor Line', 'scene-manor.jpg', '#F7F4EE', '#1A1A1A', '#1A1A1A', 'playfair', 'A line drawing of a house above the names.'),
            'scene_gifts' => $this->art('Gift Oval', 'scene-gifts.jpg', '#F6EFE4', '#2C241C', '#C4A15A', 'playfair', 'An oval photo with gifts in the corners.'),
            'scene_pitch' => $this->art('Match Day', 'scene-pitch.jpg', '#F7F7F5', '#1A1A1A', '#1F7A3A', 'montserrat', 'A ball and a strip of grass for a birthday.'),
            'scene_mandap' => $this->art('Marigold Arch', 'scene-mandap.jpg', '#F8E4DF', '#5C2430', '#C45B4A', 'playfair', 'A marigold arch around one photograph.'),
            'scene_toile' => $this->art('Blue Toile', 'scene-toile.jpg', '#F7F5F0', '#1E3A5F', '#3D6E99', 'playfair', 'An illustrated blue border on ivory paper.'),
            'scene_candy' => $this->art('Candy Lights', 'scene-candy.jpg', '#FBF6F0', '#8D1F2F', '#C44536', 'cormorant', 'Cream paper with lights and candy canes.'),
            'scene_bauble' => $this->art('Bauble Night', 'scene-bauble.jpg', '#6D1020', '#F6E7C1', '#F3D27A', 'cormorant', 'Crimson paper with ornaments and gifts.'),
            'scene_trees' => $this->art('Three Trees', 'scene-trees.jpg', '#FBF7F2', '#8D1F2F', '#C44536', 'cormorant', 'Cream paper with three fir trees and ribbon bows.'),
            'scene_fir' => $this->art('Fir Wreath', 'scene-fir.jpg', '#143028', '#F6F1E4', '#D4B56A', 'playfair', 'Green paper with a wreath and small trees.'),
            'scene_shore' => $this->art('Shore Photo', 'scene-shore.jpg', '#1A1A1A', '#FFFFFF', '#F4EFE4', 'cormorant', 'A full photograph with the words along the foot.'),
            'scene_mosaic' => $this->art('Mosaic Gold', 'scene-mosaic.jpg', '#6B1D2A', '#F6E7C1', '#D4B56A', 'playfair', 'Burgundy paper inside a gold mosaic border.'),
            'scene_monogram' => $this->art('Open Frame', 'scene-monogram.jpg', '#F7F3EA', '#2C241C', '#C4A15A', 'playfair', 'Ivory paper with a thin double gold frame.'),
            'scene_lantern' => $this->art('Brass Lanterns', 'scene-lantern.jpg', '#0E3A42', '#F6F1E4', '#D4B56A', 'cormorant', 'Teal paper with brass lanterns at the top.'),
            'scene_henna' => $this->art('Henna Corners', 'scene-henna.jpg', '#FBF6EE', '#3E2A22', '#B4532A', 'cormorant', 'Ivory paper with henna ornaments in the corners.'),
            'scene_safari' => $this->art('Acacia Dusk', 'scene-safari.jpg', '#F3C9A2', '#2A241C', '#8A4B2A', 'cormorant', 'A dusk sky with acacia trees along the foot.'),
            'scene_menu' => $this->art('Dinner Plate', 'scene-menu.jpg', '#F7F3EA', '#2C241C', '#3A2A24', 'cormorant', 'Cream paper with a plate and cutlery drawing.'),
            'scene_spark' => $this->art('Gold Rain', 'scene-spark.jpg', '#0C0C0C', '#F6E7B4', '#E2B340', 'playfair', 'Black paper with a fall of gold confetti.'),
            'scene_stars' => $this->art('Night Stars', 'scene-stars.jpg', '#10182E', '#F6E7B4', '#E2B340', 'playfair', 'Midnight paper with a few gold stars.'),
            'scene_rings' => $this->art('Two Rings', 'scene-rings.jpg', '#F7F3EA', '#3A2A24', '#C4A15A', 'cormorant', 'Ivory paper with two gold bands at the top.'),
            'scene_oliveoval' => $this->art('Olive Oval', 'scene-oliveoval.jpg', '#F4F6F2', '#2E342C', '#5E6B58', 'lora', 'A loose olive wreath on stone paper.'),
            'scene_stamp' => $this->art('Date Stamp', 'scene-stamp.jpg', '#F8F4EC', '#2C241C', '#8A7340', 'playfair', 'Cream paper with a blank stamp in the corner.'),
            'scene_horizon' => $this->art('Sea Horizon', 'scene-horizon.jpg', '#F6F1E8', '#2C2824', '#C47A52', 'cormorant', 'A sunset horizon above a paper panel.'),
            'scene_clouds' => $this->art('Soft Clouds', 'scene-clouds.jpg', '#F4F8FC', '#1E3A5F', '#7EACD0', 'lora', 'A pale sky with soft clouds.'),
            'scene_coupe' => $this->art('Champagne Coupe', 'scene-coupe.jpg', '#0C0C0C', '#F6E7B4', '#E2B340', 'playfair', 'Black paper with a gold coupe at the foot.'),
            'scene_seal' => $this->art('Gold Seal', 'scene-seal.jpg', '#FBF7F0', '#2A2A2A', '#C4A15A', 'playfair', 'Ivory paper with a blank gold seal.'),
            'scene_drums' => $this->art('Drum Line', 'scene-drums.jpg', '#F3E6D4', '#3A2A22', '#A86B45', 'cormorant', 'Sand paper with drums along the foot.'),
            ...$this->prioritizedLeadArts(),
        ];
    }

    /**
     * @return array{name: string, file: string, paper: string, ink: string, accent: string, font: string, description: string}
     */
    private function art(string $name, string $file, string $paper, string $ink, string $accent, string $font, string $description): array
    {
        return [
            'name' => $name,
            'file' => $file,
            'paper' => $paper,
            'ink' => $ink,
            'accent' => $accent,
            'font' => $font,
            'description' => $description,
        ];
    }

    /**
     * @return array<string, list<array{art: string, label: string, replaces: ?string, headline: string, message: string}>>
     */
    private function catalog(): array
    {
        $words = [
            'wedding' => ['Wedding', 'Together with our families', 'Join us as we celebrate our wedding. We look forward to sharing this special day with you.'],
            'muslim_wedding' => ['Muslim Wedding', 'Celebrate our marriage', 'Join us and our families for a joyful wedding celebration. Your presence will make the day special.'],
            'sendoff' => ['Sendoff', 'A new journey begins', 'Join us as we celebrate this new beginning and wish our loved one well on the journey ahead.'],
            'kitchen_party' => ['Kitchen Party', 'A special celebration', 'Join us for a joyful kitchen party filled with love, laughter and good wishes.'],
            'birthday' => ['Birthday', 'Another year to celebrate', 'Join us to celebrate a special birthday. Good company and happy memories await.'],
            'gala_dinner' => ['Gala Dinner', 'An evening to remember', 'Please join us for an elegant evening of conversation, celebration and great company.'],
            'anniversary' => ['Anniversary', 'Celebrating another year together', 'Join us as we look back on cherished memories and celebrate the years still to come.'],
            'save_the_date' => ['Save the Date', 'Save the date', 'Please keep this special date free. More celebration details will be shared soon.'],
            'contribution' => ['Contributions and Meetings', 'Your presence matters', 'Join us to connect, share ideas and work together toward a meaningful goal.'],
            'corporate' => ['Corporate', 'You are cordially invited', 'We are pleased to invite you to join us for this special company occasion.'],
            'other' => ['Special Occasion', 'You are invited', 'Join us for a special occasion and an enjoyable time together.'],
        ];

        $sets = [
            'wedding' => ['bougainvillea', 'acacia', 'kanga', 'frangipani', 'palm', 'clay_arch', 'baobab', 'papyrus'],
            'muslim_wedding' => ['jasmine', 'frangipani', 'acacia', 'olive', 'lanterns', 'crescent', 'henna', 'sand_arch'],
            'sendoff' => ['hibiscus', 'kanga', 'jasmine', 'bougainvillea', 'marigold', 'dawn', 'reeds', 'cocoa'],
            'kitchen_party' => ['hibiscus', 'kanga', 'frangipani', 'bougainvillea', 'citrus', 'enamel', 'mango', 'picnic'],
            'birthday' => ['hibiscus', 'frangipani', 'kanga', 'sunburst', 'confetti', 'bunting', 'meadow', 'candles'],
            'gala_dinner' => ['sunburst', 'jasmine', 'acacia', 'frangipani', 'marble', 'candlelight', 'burgundy', 'skyline'],
            'anniversary' => ['bougainvillea', 'acacia', 'frangipani', 'sunburst', 'bands', 'corners', 'two_olive', 'gold_dots'],
            'save_the_date' => ['frangipani', 'jasmine', 'olive', 'bougainvillea', 'calendar', 'liner', 'stamp', 'linen'],
            'contribution' => ['olive', 'jasmine', 'hibiscus', 'sunburst', 'canopy', 'circle', 'notebook', 'clay_pots'],
            'corporate' => ['sunburst', 'olive', 'jasmine', 'acacia', 'navy', 'grid', 'seal', 'harbor'],
            'other' => ['olive', 'hibiscus', 'frangipani', 'kanga', 'wildflower', 'moon', 'stones', 'saffron'],
        ];

        $replaced = ['Golden Classic', 'Botanical', 'Modern Rose', null];
        $catalog = [];

        foreach ($sets as $category => $arts) {
            [$label, $headline, $message] = $words[$category];

            foreach ($arts as $index => $art) {
                $catalog[$category][] = [
                    'art' => $art,
                    'label' => $label,
                    'replaces' => $replaced[$index] ?? null,
                    'headline' => $headline,
                    'message' => $message,
                ];
            }
        }

        foreach ([
            'wedding' => ['photo_arch_palm', 'couple-formal.jpg', 'arch'],
            'muslim_wedding' => ['photo_arch_henna', 'couple-modest.jpg', 'arch'],
            'sendoff' => ['photo_frame_marigold', 'friends.jpg', 'frame'],
            'kitchen_party' => ['photo_frame_citrus', 'couple-evening.jpg', 'frame'],
            'birthday' => ['photo_circle_confetti', 'person-smile.jpg', 'circle'],
            'gala_dinner' => ['photo_frame_candle', 'couple-evening.jpg', 'frame'],
            'anniversary' => ['photo_arch_olive', 'couple-formal.jpg', 'arch'],
            'save_the_date' => ['photo_frame_stamp', 'couple-formal.jpg', 'frame'],
            'contribution' => ['photo_circle_canopy', 'friends.jpg', 'circle'],
            'corporate' => ['photo_frame_navy', 'person-smile.jpg', 'frame'],
            'other' => ['photo_frame_wild', 'person-smile.jpg', 'frame'],
        ] as $category => [$art, $portrait, $slot]) {
            [$label, $headline, $message] = $words[$category];
            $catalog[$category][] = [
                'art' => $art,
                'label' => $label,
                'replaces' => null,
                'headline' => $headline,
                'message' => $message,
                'portrait' => $portrait,
                'slot' => $slot,
            ];
        }

        foreach ($this->posters() as $category => $designs) {
            [$label, $headline, $message] = $words[$category];

            foreach ($designs as $design) {
                $entry = [
                    'art' => $design['art'],
                    'label' => $label,
                    'replaces' => null,
                    'headline' => $headline,
                    'message' => $message,
                    'public' => true,
                ];

                if (isset($design['portrait'], $design['slot'])) {
                    $entry['portrait'] = $design['portrait'];
                    $entry['slot'] = $design['slot'];
                }

                $catalog[$category][] = $entry;
            }
        }

        foreach ($this->occasions() as $category => $occasion) {
            [$label, $headline, $message, $arts, $portrait] = $occasion;

            foreach ($arts as $art) {
                $catalog[$category][] = [
                    'art' => $art,
                    'label' => $label,
                    'replaces' => null,
                    'headline' => $headline,
                    'message' => $message,
                    'public' => true,
                ];
            }

            $catalog[$category][] = [
                'art' => $portrait[0],
                'label' => $label,
                'replaces' => null,
                'headline' => $headline,
                'message' => $message,
                'public' => true,
                'portrait' => $portrait[1],
                'slot' => $portrait[2],
            ];
        }

        foreach ($catalog as $category => $designs) {
            $source = $designs[0];
            array_unshift($catalog[$category], [
                'art' => 'lead_'.$category,
                'label' => $source['label'],
                'replaces' => null,
                'headline' => $source['headline'],
                'message' => $source['message'],
                'public' => true,
            ]);
        }

        return $catalog;
    }

    /**
     * One original lead artwork for every occasion, so first-choice cards do not
     * repeat the shared stationery used by older category variants.
     *
     * @return array<string, array{name: string, file: string, paper: string, ink: string, accent: string, font: string, description: string}>
     */
    private function prioritizedLeadArts(): array
    {
        $categories = [
            'wedding', 'muslim_wedding', 'sendoff', 'kitchen_party', 'birthday',
            'gala_dinner', 'anniversary', 'save_the_date', 'contribution', 'corporate',
            'other', 'graduation', 'holiday', 'christmas', 'thanksgiving', 'easter',
            'ramadan', 'eid', 'valentines', 'halloween', 'baby_shower', 'gender_reveal',
            'baptism', 'first_communion', 'quinceanera', 'sweet_sixteen', 'bridal_shower',
            'bridesmaid', 'groomsman', 'rehearsal_dinner', 'housewarming', 'open_house',
            'picnic', 'cookout', 'brunch', 'breakfast', 'luncheon', 'banquet', 'church',
            'funeral', 'celebration_of_life', 'awards', 'black_tie', 'wine_tasting',
            'masquerade', 'sports', 'football', 'movie_night', 'get_together', 'sleepover',
            'exhibition', 'safari', 'travel',
        ];

        $palettes = [
            ['#F7F1E6', '#3A2A24', '#B4532A', 'cormorant'],
            ['#14343C', '#F6F1E4', '#D4B56A', 'cormorant'],
            ['#F3E6D4', '#2C241C', '#C4652D', 'playfair'],
            ['#FBF7F0', '#2F3330', '#C4A15A', 'lora'],
            ['#1A1A1A', '#F4EFE4', '#D7B56A', 'montserrat'],
        ];

        $arts = [];

        foreach ($categories as $index => $category) {
            [$paper, $ink, $accent, $font] = $palettes[$index % count($palettes)];
            $label = ucwords(str_replace('_', ' ', $category));
            $arts['lead_'.$category] = $this->art(
                'Signature Edition',
                'lead-'.$category.'.jpg',
                $paper,
                $ink,
                $accent,
                $font,
                'Original '.$label.' artwork created as a dedicated lead design.'
            );
        }

        return $arts;
    }

    /**
     * Further occasions. Each reuses stationery already in the module, with wording of its own.
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: list<string>, 4: array{0: string, 1: string, 2: string}}>
     */
    private function occasions(): array
    {
        return [
            'graduation' => ['Graduation', 'A new chapter begins', 'Join us as we celebrate this graduation and the road opening ahead.', ['navy', 'seal', 'gold_dots', 'marble', 'scene_seal', 'scene_monogram', 'scene_column', 'scene_dust'], ['photo_frame_navy', 'person-smile.jpg', 'frame']],
            'holiday' => ['Holiday', 'A season to gather', 'Join us for a holiday celebration with the people who matter most.', ['scene_candy', 'scene_bauble', 'scene_trees', 'scene_stars', 'gold_dots', 'scene_envelope', 'calendar', 'scene_seal'], ['photo_frame_stamp', 'person-smile.jpg', 'frame']],
            'christmas' => ['Christmas', 'Merry Christmas', 'Join us to celebrate Christmas with warmth, good wishes, and good company.', ['scene_candy', 'scene_bauble', 'scene_trees', 'scene_fir', 'candles', 'scene_stars', 'scene_spark', 'gold_dots'], ['photo_circle_confetti', 'person-smile.jpg', 'circle']],
            'thanksgiving' => ['Thanksgiving', 'With grateful hearts', 'Join us for a thanksgiving gathering and a meal shared in gratitude.', ['scene_autumn', 'scene_citrusring', 'scene_menu', 'picnic', 'mango', 'enamel', 'scene_oliveoval', 'clay_pots'], ['photo_frame_wild', 'friends.jpg', 'frame']],
            'easter' => ['Easter', 'An Easter celebration', 'Join us as we celebrate Easter with family, friends, and a joyful table.', ['scene_meadowline', 'meadow', 'scene_column', 'scene_clouds', 'wildflower', 'frangipani', 'scene_oliveoval', 'scene_bow'], ['photo_circle_canopy', 'friends.jpg', 'circle']],
            'ramadan' => ['Ramadan', 'Ramadan Mubarak', 'Join us for an evening of iftar, prayer, and time together during Ramadan.', ['lanterns', 'crescent', 'jasmine', 'scene_lantern', 'moon', 'scene_stars', 'henna', 'scene_filigree'], ['photo_arch_henna', 'couple-modest.jpg', 'arch']],
            'eid' => ['Eid', 'Eid Mubarak', 'Join us to celebrate Eid al-Fitr with family, gratitude, and a joyful gathering.', ['crescent', 'lanterns', 'scene_lantern', 'henna', 'jasmine', 'scene_filigree', 'gold_dots', 'moon'], ['photo_arch_henna', 'couple-modest.jpg', 'arch']],
            'valentines' => ['Valentine', 'With love', 'Join us for a Valentine celebration filled with warmth and good company.', ['scene_blush', 'scene_peony', 'bands', 'scene_bow', 'meadow', 'scene_lace', 'gold_dots', 'scene_envelope'], ['photo_arch_olive', 'couple-formal.jpg', 'arch']],
            'halloween' => ['Halloween', 'An evening of costumes', 'Join us for a Halloween gathering of costumes, lights, and good company.', ['moon', 'scene_stars', 'scene_spark', 'scene_bauble', 'sunburst', 'scene_silk', 'scene_arch', 'candles'], ['photo_frame_wild', 'friends.jpg', 'frame']],
            'baby_shower' => ['Baby Shower', 'A little one is on the way', 'Join us for a baby shower as we celebrate the arrival of a new baby.', ['scene_baby', 'scene_wash', 'scene_stripes', 'scene_bow', 'scene_blush', 'meadow', 'scene_clouds', 'scene_cake'], ['photo_circle_confetti', 'person-smile.jpg', 'circle']],
            'gender_reveal' => ['Gender Reveal', 'A surprise to share', 'Join us as we reveal a happy surprise and celebrate the baby on the way.', ['scene_wash', 'scene_blush', 'scene_polka', 'scene_balloons', 'confetti', 'scene_clouds', 'bunting', 'scene_baby'], ['photo_circle_confetti', 'person-smile.jpg', 'circle']],
            'baptism' => ['Baptism', 'A blessed day', 'Join us for a baptism and the celebration that follows with family and friends.', ['scene_wash', 'scene_clouds', 'olive', 'scene_column', 'linen', 'scene_monogram', 'frangipani', 'scene_horizon'], ['photo_circle_canopy', 'person-smile.jpg', 'circle']],
            'first_communion' => ['First Communion', 'A day of faith', 'Join us as we celebrate a first communion with family and friends.', ['scene_wash', 'scene_column', 'scene_lace', 'candles', 'linen', 'olive', 'scene_clouds', 'scene_monogram'], ['photo_circle_canopy', 'person-smile.jpg', 'circle']],
            'quinceanera' => ['Quinceañera', 'Fifteen years to celebrate', 'Join us for a quinceañera as we celebrate this milestone with family and friends.', ['scene_lace', 'scene_peony', 'scene_blush', 'scene_palace', 'gold_dots', 'scene_bow', 'frangipani', 'scene_tulip'], ['photo_arch_palm', 'person-smile.jpg', 'arch']],
            'sweet_sixteen' => ['Sweet 16', 'Sixteen years to celebrate', 'Join us for a sweet sixteen celebration with music, friends, and happy memories.', ['scene_cake', 'scene_polka', 'scene_medallion', 'confetti', 'bunting', 'scene_spark', 'scene_blush', 'candles'], ['photo_circle_confetti', 'person-smile.jpg', 'circle']],
            'bridal_shower' => ['Bridal Shower', 'Celebrating the bride', 'Join us for a bridal shower filled with laughter, good wishes, and time together.', ['scene_stripes', 'scene_peony', 'scene_bow', 'hibiscus', 'citrus', 'enamel', 'scene_blush', 'scene_ribbon'], ['photo_frame_citrus', 'couple-evening.jpg', 'frame']],
            'bridesmaid' => ['Bridesmaid', 'Will you stand with me', 'A warm invitation to stand with the bride and share in the celebration.', ['scene_blush', 'scene_bow', 'scene_peony', 'meadow', 'frangipani', 'scene_lace', 'bunting', 'gold_dots'], ['photo_circle_confetti', 'person-smile.jpg', 'circle']],
            'groomsman' => ['Groomsman', 'Will you stand with me', 'A warm invitation to stand with the groom and share in the celebration.', ['navy', 'scene_navy', 'bands', 'scene_seal', 'marble', 'linen', 'scene_monogram', 'gold_dots'], ['photo_frame_navy', 'person-smile.jpg', 'frame']],
            'rehearsal_dinner' => ['Rehearsal Dinner', 'The evening before', 'Join us for a rehearsal dinner as we gather before the wedding day.', ['scene_menu', 'scene_wine', 'scene_ribbon', 'candlelight', 'marble', 'scene_coupe', 'scene_gala', 'linen'], ['photo_frame_candle', 'couple-evening.jpg', 'frame']],
            'housewarming' => ['Housewarming', 'Our new home', 'Join us for a housewarming as we open our new home to family and friends.', ['scene_manor', 'canopy', 'clay_pots', 'scene_oliveoval', 'linen', 'picnic', 'scene_horizon', 'wildflower'], ['photo_frame_wild', 'friends.jpg', 'frame']],
            'open_house' => ['Open House', 'Come and see', 'Join us for an open house. Come through, look around, and spend a little time with us.', ['scene_manor', 'canopy', 'linen', 'grid', 'scene_seal', 'wildflower', 'picnic', 'clay_pots'], ['photo_frame_wild', 'friends.jpg', 'frame']],
            'picnic' => ['Picnic', 'A day outdoors', 'Join us for a picnic outdoors. Bring an easy heart and stay for the afternoon.', ['picnic', 'citrus', 'mango', 'meadow', 'scene_safari', 'wildflower', 'scene_horizon', 'bunting'], ['photo_frame_wild', 'friends.jpg', 'frame']],
            'cookout' => ['Cookout', 'Food and fresh air', 'Join us for a cookout with good food, open air, and easy company.', ['picnic', 'citrus', 'mango', 'scene_safari', 'bunting', 'meadow', 'enamel', 'wildflower'], ['photo_frame_marigold', 'friends.jpg', 'frame']],
            'brunch' => ['Brunch', 'A late morning table', 'Join us for brunch, a relaxed table, and unhurried conversation.', ['scene_menu', 'citrus', 'enamel', 'mango', 'scene_clouds', 'picnic', 'frangipani', 'linen'], ['photo_frame_citrus', 'friends.jpg', 'frame']],
            'breakfast' => ['Breakfast', 'The morning starts here', 'Join us for breakfast and an easy start to the day together.', ['citrus', 'enamel', 'mango', 'scene_menu', 'scene_clouds', 'frangipani', 'linen', 'picnic'], ['photo_frame_citrus', 'friends.jpg', 'frame']],
            'luncheon' => ['Luncheon', 'A midday gathering', 'Join us for a luncheon and an afternoon of good food and conversation.', ['scene_menu', 'scene_ribbon', 'marble', 'linen', 'scene_oliveoval', 'citrus', 'enamel', 'scene_wine'], ['photo_frame_candle', 'friends.jpg', 'frame']],
            'banquet' => ['Banquet', 'An evening at table', 'Join us for a banquet evening of fine company and a shared celebration.', ['scene_gala', 'scene_menu', 'marble', 'candlelight', 'scene_wine', 'scene_ribbon', 'burgundy', 'scene_rosette'], ['photo_frame_candle', 'couple-evening.jpg', 'frame']],
            'church' => ['Church', 'You are welcome', 'Join us for a church gathering. Your presence will be a blessing.', ['scene_column', 'scene_monogram', 'olive', 'linen', 'scene_lace', 'candles', 'scene_fir', 'scene_seal'], ['photo_circle_canopy', 'person-smile.jpg', 'circle']],
            'funeral' => ['Funeral', 'In loving memory', 'You are invited to join the family as we remember a life that mattered.', ['olive', 'stones', 'scene_eucalyptus', 'linen', 'candles', 'scene_column', 'scene_clouds', 'scene_monogram'], ['photo_frame_wild', 'person-smile.jpg', 'frame']],
            'celebration_of_life' => ['Celebration of Life', 'A life remembered', 'Join us to celebrate a life well lived and the memories we still share.', ['olive', 'scene_eucalyptus', 'scene_column', 'linen', 'stones', 'scene_clouds', 'scene_oliveoval', 'candles'], ['photo_frame_wild', 'person-smile.jpg', 'frame']],
            'awards' => ['Awards', 'An evening of honour', 'Join us for an awards night as we recognise effort, talent, and achievement.', ['scene_seal', 'scene_gala', 'navy', 'scene_dust', 'scene_rosette', 'marble', 'scene_monogram', 'skyline'], ['photo_frame_navy', 'person-smile.jpg', 'frame']],
            'black_tie' => ['Black Tie', 'An evening in formal dress', 'Join us for a black-tie evening. Formal dress, fine company, and a night to remember.', ['scene_gala', 'scene_burgundy', 'scene_coupe', 'marble', 'candlelight', 'scene_filigree', 'scene_dust', 'navy'], ['photo_frame_candle', 'couple-evening.jpg', 'frame']],
            'wine_tasting' => ['Wine Tasting', 'An evening of wine', 'Join us for a wine tasting and an unhurried evening of conversation.', ['scene_wine', 'scene_coupe', 'burgundy', 'marble', 'scene_menu', 'candlelight', 'scene_gala', 'linen'], ['photo_frame_candle', 'friends.jpg', 'frame']],
            'masquerade' => ['Masquerade', 'Masks and candlelight', 'Join us for a masquerade. Come in a mask and stay for the evening.', ['scene_filigree', 'scene_burgundy', 'scene_palace', 'scene_gala', 'moon', 'scene_spark', 'gold_dots', 'scene_arch'], ['photo_frame_candle', 'couple-evening.jpg', 'frame']],
            'sports' => ['Sports', 'Game day', 'Join us for a sports celebration. Come ready to cheer and enjoy the day.', ['scene_pitch', 'scene_navy', 'navy', 'grid', 'scene_dust', 'bunting', 'confetti', 'scene_spark'], ['photo_circle_confetti', 'friends.jpg', 'circle']],
            'football' => ['Football', 'Match day', 'Join us for a football gathering. Bring your colours and enjoy the match with us.', ['scene_pitch', 'bunting', 'navy', 'confetti', 'scene_dust', 'scene_spark', 'grid', 'scene_navy'], ['photo_circle_confetti', 'friends.jpg', 'circle']],
            'movie_night' => ['Movie Night', 'Lights down', 'Join us for a movie night. Settle in, and enjoy the film together.', ['scene_stars', 'scene_silk', 'scene_chalk', 'moon', 'scene_spark', 'navy', 'scene_balloons', 'confetti'], ['photo_frame_navy', 'friends.jpg', 'frame']],
            'get_together' => ['Get Together', 'Come as you are', 'Join us for an easy get-together. Good company is the whole plan.', ['picnic', 'bunting', 'confetti', 'wildflower', 'scene_wavy', 'meadow', 'citrus', 'canopy'], ['photo_circle_canopy', 'friends.jpg', 'circle']],
            'sleepover' => ['Sleepover', 'Stay the night', 'Join us for a sleepover with stories, snacks, and a long night of fun.', ['scene_stars', 'moon', 'scene_clouds', 'scene_polka', 'confetti', 'bunting', 'scene_silk', 'meadow'], ['photo_circle_confetti', 'person-smile.jpg', 'circle']],
            'exhibition' => ['Exhibition', 'You are invited to view', 'Join us for an exhibition. Come and see the work, and stay for a conversation.', ['scene_manor', 'navy', 'grid', 'scene_monogram', 'marble', 'scene_seal', 'skyline', 'linen'], ['photo_frame_navy', 'person-smile.jpg', 'frame']],
            'safari' => ['Safari', 'Out on the land', 'Join us for a safari gathering under open sky and acacia trees.', ['scene_safari', 'acacia', 'baobab', 'scene_horizon', 'dawn', 'reeds', 'palm', 'scene_drums'], ['photo_frame_marigold', 'friends.jpg', 'frame']],
            'travel' => ['Travel', 'The journey begins', 'Join us as we mark a journey. Come and send us off with good wishes.', ['scene_horizon', 'scene_stamp', 'calendar', 'liner', 'harbor', 'scene_shore', 'dawn', 'stamp'], ['photo_frame_stamp', 'couple-formal.jpg', 'frame']],
        ];
    }

    /**
     * Poster art matched to the occasion. The wording still comes from the card fields.
     *
     * @return array<string, list<array{art: string, portrait?: string, slot?: string}>>
     */
    private function posters(): array
    {
        return [
            'wedding' => [
                ['art' => 'scene_burgundy'],
                ['art' => 'scene_wreath'],
                ['art' => 'scene_torn', 'portrait' => 'couple-formal.jpg', 'slot' => 'frame'],
                ['art' => 'scene_palace'],
                ['art' => 'scene_lace'],
                ['art' => 'scene_blush'],
                ['art' => 'scene_autumn'],
                ['art' => 'scene_citrusring'],
                ['art' => 'scene_meadowline'],
                ['art' => 'scene_column'],
                ['art' => 'scene_manor'],
                ['art' => 'scene_toile'],
                ['art' => 'scene_mosaic'],
                ['art' => 'scene_monogram'],
            ],
            'muslim_wedding' => [
                ['art' => 'scene_filigree'],
                ['art' => 'scene_peony'],
                ['art' => 'scene_tulip'],
                ['art' => 'scene_nikah'],
                ['art' => 'scene_arch'],
                ['art' => 'scene_mandap', 'portrait' => 'couple-formal.jpg', 'slot' => 'frame'],
                ['art' => 'scene_lantern'],
                ['art' => 'scene_henna'],
            ],
            'sendoff' => [
                ['art' => 'scene_eucalyptus'],
                ['art' => 'scene_navy'],
                ['art' => 'scene_bow'],
                ['art' => 'scene_dust'],
                ['art' => 'scene_safari'],
                ['art' => 'scene_drums'],
            ],
            'kitchen_party' => [
                ['art' => 'scene_stripes'],
                ['art' => 'scene_wavy'],
                ['art' => 'scene_peony'],
                ['art' => 'scene_bow'],
                ['art' => 'scene_ribbon'],
                ['art' => 'scene_menu'],
                ['art' => 'scene_oliveoval'],
            ],
            'birthday' => [
                ['art' => 'scene_silk'],
                ['art' => 'scene_medallion', 'portrait' => 'person-smile.jpg', 'slot' => 'circle'],
                ['art' => 'scene_polka', 'portrait' => 'person-smile.jpg', 'slot' => 'circle'],
                ['art' => 'scene_cake'],
                ['art' => 'scene_gifts', 'portrait' => 'person-smile.jpg', 'slot' => 'circle'],
                ['art' => 'scene_pitch'],
                ['art' => 'scene_spark'],
                ['art' => 'scene_clouds'],
            ],
            'gala_dinner' => [
                ['art' => 'scene_gala'],
                ['art' => 'scene_balloons'],
                ['art' => 'scene_wavy'],
                ['art' => 'scene_dust'],
                ['art' => 'scene_rosette'],
                ['art' => 'scene_wine'],
                ['art' => 'scene_stars'],
                ['art' => 'scene_coupe'],
            ],
            'anniversary' => [
                ['art' => 'scene_eucalyptus'],
                ['art' => 'scene_wreath'],
                ['art' => 'scene_burgundy'],
                ['art' => 'scene_envelope'],
                ['art' => 'scene_meadowline'],
                ['art' => 'scene_column'],
                ['art' => 'scene_rings'],
                ['art' => 'scene_oliveoval'],
            ],
            'save_the_date' => [
                ['art' => 'scene_navy'],
                ['art' => 'scene_bow'],
                ['art' => 'scene_envelope'],
                ['art' => 'scene_dust'],
                ['art' => 'scene_split', 'portrait' => 'couple-formal.jpg', 'slot' => 'frame'],
                ['art' => 'scene_shore', 'portrait' => 'couple-shore.jpg', 'slot' => 'frame'],
                ['art' => 'scene_stamp'],
                ['art' => 'scene_horizon'],
            ],
            'contribution' => [
                ['art' => 'scene_eucalyptus'],
                ['art' => 'scene_navy'],
                ['art' => 'scene_dust'],
                ['art' => 'scene_wavy'],
                ['art' => 'scene_seal'],
                ['art' => 'scene_monogram'],
            ],
            'corporate' => [
                ['art' => 'scene_gala'],
                ['art' => 'scene_navy'],
                ['art' => 'scene_dust'],
                ['art' => 'scene_filigree'],
                ['art' => 'scene_wine'],
                ['art' => 'scene_seal'],
                ['art' => 'scene_stars'],
            ],
            'other' => [
                ['art' => 'scene_baby', 'portrait' => 'person-smile.jpg', 'slot' => 'circle'],
                ['art' => 'scene_wash', 'portrait' => 'person-smile.jpg', 'slot' => 'circle'],
                ['art' => 'scene_chalk', 'portrait' => 'person-smile.jpg', 'slot' => 'frame'],
                ['art' => 'scene_cake'],
                ['art' => 'scene_candy'],
                ['art' => 'scene_bauble'],
                ['art' => 'scene_trees'],
                ['art' => 'scene_fir'],
                ['art' => 'scene_clouds'],
                ['art' => 'scene_lantern'],
            ],
        ];
    }
}
