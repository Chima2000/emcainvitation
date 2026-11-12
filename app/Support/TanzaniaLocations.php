<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use RuntimeException;

class TanzaniaLocations
{
    /**
     * @var array<string, array{districts: array<string, array{wards: list<string>}>}>|null
     */
    private static ?array $locations = null;

    /**
     * @return array<string, array{districts: array<string, array{wards: list<string>}>}>
     */
    public static function data(): array
    {
        if (self::$locations !== null) {
            return self::$locations;
        }

        $path = public_path('data/tanzania-locations.json');

        if (! is_file($path)) {
            throw new RuntimeException('The Tanzania location dataset is missing.');
        }

        $locations = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($locations)) {
            throw new RuntimeException('The Tanzania location dataset is invalid.');
        }

        /** @var array<string, array{districts: array<string, array{wards: list<string>}>}> $locations */
        self::$locations = $locations;

        return self::$locations;
    }

    /**
     * @return list<string>
     */
    public static function regions(): array
    {
        return array_keys(self::data());
    }

    /**
     * @return list<string>
     */
    public static function districts(?string $region): array
    {
        return array_keys(self::data()[$region]['districts'] ?? []);
    }

    /**
     * @return list<string>
     */
    public static function wards(?string $region, ?string $district): array
    {
        return self::data()[$region]['districts'][$district]['wards'] ?? [];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array<mixed>|string>
     */
    public static function rules(array $input, bool $required): array
    {
        $hasLocationData = self::hasLocationData($input);
        $requireLocation = $required || $hasLocationData;
        $fieldRule = $requireLocation ? 'required' : 'nullable';
        $country = is_string($input['country'] ?? null) ? $input['country'] : null;
        $region = is_string($input['region'] ?? null) ? $input['region'] : null;
        $district = is_string($input['district'] ?? null) ? $input['district'] : null;
        $regions = $country === 'Tanzania' ? [...self::regions(), '__manual__'] : ['__manual__'];
        $districts = $country === 'Tanzania' && $region !== '__manual__'
            ? [...self::districts($region), '__manual__']
            : ['__manual__'];
        $wards = $country === 'Tanzania' && $region !== '__manual__' && $district !== '__manual__'
            ? [...self::wards($region, $district), '__manual__']
            : ['__manual__'];

        return [
            'country' => [$fieldRule, 'string', Rule::in(['Tanzania', '__manual__'])],
            'country_manual' => ['required_if:country,__manual__', 'nullable', 'string', 'max:100'],
            'region' => [$fieldRule, 'string', Rule::in($regions)],
            'region_manual' => ['required_if:region,__manual__', 'nullable', 'string', 'max:120'],
            'district' => [$fieldRule, 'string', Rule::in($districts)],
            'district_manual' => ['required_if:district,__manual__', 'nullable', 'string', 'max:120'],
            'ward' => [$fieldRule, 'string', Rule::in($wards)],
            'ward_manual' => ['required_if:ward,__manual__', 'nullable', 'string', 'max:120'],
            'street' => [$fieldRule, 'string', 'max:180'],
            'address_line' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function hasLocationData(array $input): bool
    {
        foreach ([
            'country',
            'country_manual',
            'region',
            'region_manual',
            'district',
            'district_manual',
            'ward',
            'ward_manual',
            'street',
            'address_line',
        ] as $field) {
            if (filled($input[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string|null>
     */
    public static function normalize(array $input): array
    {
        if (! self::hasLocationData($input)) {
            return [];
        }

        return [
            'country' => self::selectedValue($input, 'country'),
            'region' => self::selectedValue($input, 'region'),
            'district' => self::selectedValue($input, 'district'),
            'ward' => self::selectedValue($input, 'ward'),
            'street' => trim((string) ($input['street'] ?? '')),
            'address_line' => filled($input['address_line'] ?? null) ? trim((string) $input['address_line']) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private static function selectedValue(array $input, string $field): ?string
    {
        $value = $input[$field] ?? null;

        if ($value === '__manual__') {
            $value = $input[$field.'_manual'] ?? null;
        }

        return is_string($value) && $value !== '' ? trim($value) : null;
    }
}
