# Rackhost átadási ellenőrzőlista

## Csomag és szerver

- PHP 8.3 vagy újabb; `php deploy/rackhost-preflight.php`
- SSH és Composer 2
- cron percenkénti futtatási lehetőséggel
- MySQL 8-kompatibilis adatbázis, legalább 1 GB induló kvótával és `utf8mb4` karakterkészlettel
- SSL az `app.fakt.org.hu` aldomainhez és kényszerített HTTPS
- hitelesített SMTP, SPF/DKIM beállítással
- napi fájl- és adatbázismentés, külső vagy Rackhost által biztosított második példánnyal

## Élesítés

- A dokumentumgyökér kizárólag `public/`.
- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://app.fakt.org.hu`.
- `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`, `QUEUE_CONNECTION=database`.
- A `.env`, `storage/`, adatbázismentések és feltöltések nem érhetők el HTTP-n.
- `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `php artisan optimize`.
- Cron: `* * * * * cd /ABSZOLUT/UT/fakt-app && php artisan schedule:run >> /dev/null 2>&1`.
- A queue backlog, sikertelen jobok és lemezhasználat hetente ellenőrizendő.

## Mentés és visszaállítás

- Napi titkosított adatbázis- és `storage/app/private` mentés, 30 gördülő napi példánnyal.
- A titkosítókulcs ne ugyanott legyen, mint a mentés.
- Havonta külön tesztadatbázisba és ideiglenes fájlútvonalra történő teljes visszaállítás.
- A visszaállítási jegyzőkönyv rögzítse a dátumot, mentésazonosítót, időtartamot, sor-/fájlszámot és az ellenőrző személyt.

## Go-live kapuk

- két próbaimport egyező egyeztetési jelentéssel;
- vezetői jogosultsági teszt minden hatókörre;
- email, TOTP, reset, védett fájl és ICS-token visszavonási próba;
- PWA telepítés Androidon és iOS-en, magyar dátum és Europe/Budapest időzóna;
- adatkezelési tájékoztató és megőrzési idők jóváhagyva.
