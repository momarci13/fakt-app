# FAKT belső alkalmazás

Magyar nyelvű, mobilra optimalizált Laravel 13 + Vue 3/Inertia PWA a FAKT szervezeti működéséhez. A rendszer meghívásos, szerepkör- és hatóköralapú; a publikus honlaptól külön, az `app.fakt.org.hu` címen telepíthető.

## Elkészült modulok

- Elnök → Alelnök → Teamvezető → Teamtag időbeli kinevezési lánc és auditnapló
- félévenként módosítható portfólió-, Team- és projektstruktúra
- kurzuskínálat, preferencia, elbírálás, férőhely és várólista
- személyes összevont naptár, RSVP, végleges jelenlét és visszavonható privát ICS-feed
- hierarchikus Team-/projektfeladatok, több felelős, részfeladat, státusz és komment
- verziózott kötelezettségi szabályok, életút-eredmények és emberi jóváhagyást igénylő kérelmek
- védett bizonyítékfeltöltés és letöltés, alumni címtár és mentorprogram
- célzott közlemények, alkalmazáson belüli és email-értesítések
- előnézetes CSV/XLSX tagimport, duplikációellenőrzés, hibás sorok elkülönítése és visszavonható importbatch
- PWA telepítés, csak olvasható offline naptár-/feladat-gyorsítótár
- napi adatmegőrzési takarítás és adatbázis-alapú queue

## Klónozás és helyi indítás

### Előfeltételek

A projekt nem tartalmaz saját PHP-, Composer- vagy Node.js-futtatókörnyezetet. A fejlesztő gépén az alábbiaknak parancssorból elérhetőnek kell lenniük:

- Git
- PHP 8.3 vagy újabb, a Laravel által igényelt bővítményekkel (`curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_sqlite` vagy `pdo_mysql`, `xml`, `zip`)
- Composer 2
- Node.js 22 LTS vagy újabb, npm-mel
- lokális használathoz SQLite; MySQL 8 csak akkor szükséges, ha arra állítod át a `.env` fájlt

Telepítési oldalak: [PHP](https://www.php.net/downloads.php), [Composer](https://getcomposer.org/download/), [Node.js](https://nodejs.org/en/download).

Telepítés után nyiss új terminált, majd ellenőrizd:

```text
php --version
composer --version
node --version
npm --version
```

Ha a `php` vagy `composer` parancs nem található, az adott program nincs telepítve vagy nincs benne a rendszer `PATH` változójában. Ezt a projekt nem tudja automatikusan pótolni.

### Linux és macOS

```bash
git clone <REPOSITORY-URL> fakt-app
cd fakt-app
cp .env.example .env
touch database/database.sqlite
composer install
php artisan key:generate
php artisan migrate:fresh --seed
npm ci
npm run build
php artisan serve
```

### Windows PowerShell

```powershell
git clone <REPOSITORY-URL> fakt-app
Set-Location fakt-app
Copy-Item .env.example .env
New-Item database/database.sqlite -ItemType File -Force
composer install
php artisan key:generate
php artisan migrate:fresh --seed
npm.cmd ci
npm.cmd run build
php artisan serve
```

Windows alatt az `npm.cmd` forma akkor is működik, ha a PowerShell execution policy blokkolja az `npm.ps1` fájlt. Nem szükséges emiatt csökkenteni a rendszer biztonsági beállításait.

Az alkalmazás alapértelmezetten a [http://127.0.0.1:8000](http://127.0.0.1:8000) címen indul. A szerver leállítása: `Ctrl+C`.

Fejlesztői fiók: `elnok@fakt.local`, jelszó: `Fakt2027!`. A seedelt fiókok kizárólag lokális bemutatóadatok; production környezetben ne futtasd a seedert vagy a `migrate:fresh` parancsot.

### Meglévő helyi példány frissítése

```bash
git pull
composer install
php artisan migrate
npm ci
npm run build
```

Ellenőrzés:

```bash
php artisan test
composer lint:check
npm run lint:check
npm run types:check
npm run build
```

## Rackhost telepítés

1. Futtasd a `deploy/rackhost-preflight.php` ellenőrzést a célcsomagban. Szükséges az SSH, Composer, cron, SSL, SMTP, MySQL 8-kompatibilis adatbázis, napi mentési lehetőség és a felsorolt PHP-bővítmények.
2. Az aldomain dokumentumgyökere a projekt `public/` mappája legyen. A `storage/` és `bootstrap/cache/` legyen írható, de ne legyen publikus.
3. Másold `.env.example`-t `.env`-re. Állítsd: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://app.fakt.org.hu`, MySQL- és SMTP-adatok. Használj külön, minimális jogosultságú adatbázis-felhasználót.
4. Futtasd:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

5. A production buildet CI-ban vagy helyben készítsd `npm ci && npm run build` paranccsal, majd töltsd fel a `public/build/` mappát. A szerveren Node.js nem szükséges.
6. Állítsd be percenként: `* * * * * cd /ABSZOLUT/UT/fakt-app && php artisan schedule:run >> /dev/null 2>&1`.
7. Ellenőrizd a meghívó-, jelszó-reset- és feladatértesítő emaileket, az ICS-feedet, majd futtass teljes visszaállítási próbát.

Részletes ellenőrzőlista: [deploy/RACKHOST.md](deploy/RACKHOST.md). A szerver hozzáférési adatai nélkül a csomag telepítésre kész, de nincs éles Rackhost példányra publikálva.

## Biztonság és adatmegőrzés

Az alkalmazás publikus regisztrációt nem enged. Email-ellenőrzést, rate limitet, erős Laravel jelszóhash-t, jelszó-resetet és opcionális TOTP MFA-t használ. A mellékletek privát lemezen, jogosultság-ellenőrzött végponton tölthetők le; a limit 10 MB és csak engedélyezett dokumentum-/képtípus fogadható.

Az ütemezett `fakt:retention` parancs a specifikáció szerinti 12/24 hónapos részletes adatmegőrzést hajtja végre. A kurzus-, tisztség- és diplomaeredmények hosszú távon megmaradnak. Az adatkezelési tájékoztatót és a végleges megőrzési időket éles indulás előtt a FAKT-nak jóvá kell hagynia.

## Fontos fájlok

- `database/migrations/2026_08_09_000000_create_fakt_domain_tables.php` – teljes adatmodell
- `database/seeders/DatabaseSeeder.php` – a négy portfólió és hat Team bemutatóstruktúrája
- `routes/web.php` – alkalmazásfolyamatok és jogosultságvédett útvonalak
- `tests/Feature/FaktWorkflowTest.php` – kritikus jogosultsági és üzleti folyamatok
- `docs/PERMISSIONS.md` – jogosultsági mátrix
- `docs/IMPORT.md` – importformátum
