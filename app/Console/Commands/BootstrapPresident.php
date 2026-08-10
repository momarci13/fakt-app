<?php

namespace App\Console\Commands;

use App\Models\MemberProfile;
use App\Models\RoleAssignment;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BootstrapPresident extends Command
{
    protected $signature = 'fakt:bootstrap-president {email} {--name=FAKT Elnök}';

    protected $description = 'Létrehozza az első elnöki fiókot egy friss production adatbázisban.';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $name = trim((string) $this->option('name'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Érvénytelen email-cím.');

            return self::FAILURE;
        }

        if (RoleAssignment::query()->where('role', 'president')->whereNull('revoked_at')->exists()) {
            $this->error('Már létezik aktív elnöki kinevezés. A parancs nem módosított adatot.');

            return self::FAILURE;
        }

        $generatedPassword = null;

        DB::transaction(function () use ($email, $name, &$generatedPassword): void {
            $semester = Semester::active();

            if (! $semester) {
                $semester = Semester::query()->create([
                    'name' => 'Induló félév',
                    'starts_at' => now()->startOfDay(),
                    'ends_at' => now()->addMonths(6)->endOfDay(),
                    'is_active' => true,
                    'course_selection_open' => false,
                ]);
            }

            $user = User::query()->where('email', $email)->first();

            if (! $user) {
                $generatedPassword = Str::random(24);
                $user = new User();
                $user->name = $name !== '' ? $name : 'FAKT Elnök';
                $user->email = $email;
                $user->email_verified_at = now();
                $user->password = Hash::make($generatedPassword);
                $user->calendar_token = Str::random(48);
                $user->save();
            }

            MemberProfile::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['member_status' => 'active']
            );

            RoleAssignment::query()->create([
                'semester_id' => $semester->id,
                'user_id' => $user->id,
                'role' => 'president',
                'starts_at' => now()->toDateString(),
            ]);
        });

        $this->info('Az elnöki jogosultság elkészült: '.$email);

        if ($generatedPassword !== null) {
            $this->warn('Egyszer használatos kezdeti jelszó: '.$generatedPassword);
            $this->warn('Belépés után azonnal módosítsd, majd töröld a cron kimeneti naplóját.');
        } else {
            $this->info('A meglévő felhasználó jelszava nem változott.');
        }

        return self::SUCCESS;
    }
}
