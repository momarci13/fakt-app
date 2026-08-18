<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Models\Semester;
use App\Notifications\FaktNotification;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, array_merge($this->profileRules(), [
            'password' => $this->passwordRules(),
            'cohort_year' => ['nullable', 'integer', 'between:2008,2100'],
            'registration_note' => ['required', 'string', 'min:20', 'max:2000'],
            'privacy_accepted' => ['accepted'],
        ]), [
            'registration_note.required' => 'Írd le röviden, milyen kapcsolatban állsz a FAKT-tal.',
            'registration_note.min' => 'A bemutatkozás legalább 20 karakter legyen.',
            'privacy_accepted.accepted' => 'A regisztrációhoz el kell fogadnod az adatkezelési feltételeket.',
        ])->validate();

        $user = DB::transaction(function () use ($input): User {
            $user = User::query()->create([
                'name' => trim($input['name']),
                'email' => Str::lower(trim($input['email'])),
                'password' => $input['password'],
                'approval_status' => 'pending',
                'registration_note' => trim($input['registration_note']),
                'calendar_token' => Str::random(48),
            ]);

            $user->profile()->create([
                'member_status' => 'pending',
                'cohort_year' => $input['cohort_year'] ?? null,
                'alumni_visible' => false,
            ]);

            Audit::record($user, 'registration_submitted');

            return $user;
        });

        $semester = Semester::active();
        if ($semester) {
            User::query()
                ->whereHas('roles', fn ($query) => $query
                    ->where('semester_id', $semester->id)
                    ->where('role', 'president')
                    ->whereNull('revoked_at')
                    ->whereDate('starts_at', '<=', today())
                    ->where(fn ($dates) => $dates
                        ->whereNull('ends_at')
                        ->orWhereDate('ends_at', '>=', today())))
                ->get()
                ->each->notify(new FaktNotification(
                    'Új regisztrációs kérelem',
                    $user->name.' jóváhagyásra vár.',
                    '/admin#regisztraciok'
                ));
        }

        return $user;
    }
}
