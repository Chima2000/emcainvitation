<?php

namespace App\Actions;

use App\GuestGender;
use App\Models\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ImportGuests
{
    public function __construct(private IssueGuestPass $issueGuestPass) {}

    public function handle(Event $event, UploadedFile $file): int
    {
        $contents = file($file->getRealPath(), FILE_IGNORE_NEW_LINES);

        if ($contents === false) {
            throw ValidationException::withMessages([
                'file' => 'That file could not be read.',
            ]);
        }

        $rows = array_map(str_getcsv(...), $contents);
        $rows = array_values(array_filter($rows, fn (array $row): bool => $this->rowHasContent($row)));

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'No guests were found in that file.',
            ]);
        }

        $first = array_map(fn (mixed $value): string => strtolower(trim((string) $value)), $rows[0]);
        $hasHeader = in_array('name', $first, true);
        $headers = $hasHeader
            ? $first
            : (count($rows[0]) >= 5
                ? ['name', 'phone', 'email', 'gender', 'party_size']
                : ['name', 'phone', 'email', 'party_size']);
        $dataRows = $hasHeader ? array_slice($rows, 1) : $rows;

        if (count($dataRows) > 2000) {
            throw ValidationException::withMessages([
                'file' => 'Import 2,000 guests or fewer at a time.',
            ]);
        }

        $genderIndex = array_search('gender', $headers, true);

        if ($genderIndex !== false) {
            foreach ($dataRows as $rowIndex => $row) {
                $genderValue = trim((string) ($row[$genderIndex] ?? ''));

                if ($genderValue !== '' && GuestGender::tryFrom($genderValue) === null) {
                    throw ValidationException::withMessages([
                        'file' => __('The gender on row :row must be female, male, other, or prefer_not_to_say.', ['row' => $rowIndex + ($hasHeader ? 2 : 1)]),
                    ]);
                }
            }
        }

        $imported = 0;

        foreach ($dataRows as $row) {
            $values = [];

            foreach ($headers as $index => $header) {
                $values[$header] = trim((string) ($row[$index] ?? ''));
            }

            $name = $values['name'] ?? '';

            if ($name === '') {
                continue;
            }

            $email = $values['email'] ?? '';
            $phone = $values['phone'] ?? '';
            $partySize = (int) ($values['party_size'] ?? 1);
            $genderValue = $values['gender'] ?? '';
            $gender = $genderValue === '' ? null : GuestGender::tryFrom($genderValue);

            $this->issueGuestPass->handle($event, [
                'name' => $name,
                'phone' => $phone !== '' ? $phone : null,
                'email' => str_contains($email, '@') ? $email : null,
                'gender' => $gender,
                'party_size' => min(30, max(1, $partySize)),
            ]);

            $imported++;
        }

        if ($imported === 0) {
            throw ValidationException::withMessages([
                'file' => 'No guests were found in that file.',
            ]);
        }

        return $imported;
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function rowHasContent(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }
}
