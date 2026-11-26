<?php

namespace App\Actions;

use App\GuestGender;
use App\Models\Contact;
use App\Models\Event;
use App\Support\GuestRoster;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ImportGuests
{
    /**
     * Columns a host fills in. They match the guest record, not the card number or reply.
     *
     * @var list<string>
     */
    public const array Columns = ['name', 'phone', 'email', 'gender', 'party_size', 'notes', 'table_name'];

    public function __construct(private IssueGuestPass $issueGuestPass) {}

    /**
     * @return array{imported: int, skipped: int, stopped_at_trial_limit: bool}
     */
    public function handle(Event $event, UploadedFile $file): array
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

        $room = $event->trialGuestsRemaining();

        if ($room === 0) {
            throw ValidationException::withMessages([
                'file' => __('A trial can list :count guests. Become a host to add more.', ['count' => Event::TrialGuestLimit]),
            ]);
        }

        $roster = GuestRoster::forEvent($event);
        $imported = 0;
        $skipped = 0;
        $stoppedAtTrialLimit = false;

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
            $notes = $values['notes'] ?? '';
            $tableName = $values['table_name'] ?? '';
            $phone = $phone !== '' ? $phone : null;
            $email = str_contains($email, '@') ? $email : null;

            if ($roster->conflicts($name, $phone, $email) !== []) {
                $skipped++;

                continue;
            }

            if ($room !== null && $imported >= $room) {
                $stoppedAtTrialLimit = true;
                $skipped++;

                continue;
            }

            $guest = $this->issueGuestPass->handle($event, [
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'gender' => $gender,
                'party_size' => min(30, max(1, $partySize)),
                'notes' => $notes !== '' ? mb_substr($notes, 0, 1000) : null,
                'table_name' => $tableName !== '' ? mb_substr($tableName, 0, 40) : null,
            ]);
            Contact::remember($event->user, $guest->name, $guest->phone, $guest->email, $guest->gender?->value);
            $roster->remember($name, $phone, $email);

            $imported++;
        }

        if ($imported === 0 && $skipped === 0) {
            throw ValidationException::withMessages([
                'file' => 'No guests were found in that file.',
            ]);
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'stopped_at_trial_limit' => $stoppedAtTrialLimit,
        ];
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
