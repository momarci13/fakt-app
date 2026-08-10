# FAKT belső alkalmazás

Magyar nyelvű, mobilra optimalizált Laravel + Vue/Inertia PWA a FAKT szervezeti működéséhez. Az alkalmazás meghívásos, szerepkör- és hatóköralapú, és a publikus honlaptól külön, az `app.fakt.org.hu` címen telepíthető.

## Technikai állapot

Ez az ág a jelenlegi Rackhost cPanel környezethez készült:

- PHP 7.4-kompatibilis Laravel 8.83;
- Vue 3, Inertia és Vite frontend;
- MySQL production adatbázis, SQLite helyi fejlesztéshez;
- adatbázis-alapú queue és cPanel cron;
- SSH, szerveroldali Composer és szerveroldali Node.js nélküli telepítés GitHub Actions artifactból.

> Fontos: a PHP 7.4 és a Laravel 8 már nem kap biztonsági javításokat. A kompatibilitási ág a jelenlegi tárhely technikai korlátját kezeli, de személyes adatokat kezelő éles rendszerhez támogatott PHP 8.3+ tárhelyre váltás javasolt.

A jelenlegi Composer advisoryk és az alkalmazásszintű kockázatcsökkentések részletei: [docs/PHP74_SECURITY.md](docs/PHP74_SECURITY.md).

## Fő modulok

- Elnök → Alelnök → Teamvezető → Teamtag időbeli kinevezési lánc és auditnapló;
- félévenként módosítható portfólió-, Team- és projektstruktúra;
- kurzuskínálat, preferencia, elbírálás, férőhely és várólista;
- személyes összevont naptár, RSVP, jelenlét és visszavonható privát ICS-feed;
- hierarchikus Team-/projektfeladatok, több felelős, részfeladat, státusz és komment;
- verziózott kötelezettségi szabályok, életút-eredmények és kérelmek;
- védett bizonyítékfeltöltés, alumni címtár és mentorprogram;
- célzott közlemények, alkalmazáson belüli és email-értesítések;
- előnézetes CSV/XLSX tagimport és visszavonható importbatch;
- PWA telepítés és csak olvasható offline naptár-/feladat-gyorsítótár.

## Klónozás és helyi indítás

### Előfeltételek

- Git;
- PHP 7.4 vagy újabb a szükséges bővítményekkel (`curl`, `fileinfo`, `mbstring`, `openssl`, `pdo_sqlite` vagy `pdo_mysql`, `xml`, `zip`);
- Composer 2;
- Node.js 22 LTS és npm.

Telepítési oldalak: [PHP](https://www.php.net/downloads.php), [Composer](https://getcomposer.org/download/), [Node.js](https://nodejs.org/en/download/).

Telepítés után nyiss új terminált, majd ellenőrizd:

```text
php --version
composer --version
node --version
npm --version
```

Ha a `php` vagy `composer` parancs nem található, az nincs telepítve vagy nincs benne a rendszer `PATH` változójában. Ezt maga a repository nem tudja automatikusan pótolni.

### Windows PowerShell

```powershell
git clone <REPOSITORY-URL> fakt-app
Set-Location fakt-app
Copy-Item .env.example .env
New-Item database/database.sqlite -ItemType File -Force
composer install --no-blocking
php artisan key:generate
php artisan migrate:fresh --seed
npm.cmd ci
npm.cmd run build
php artisan serve
```

Az `npm.cmd` forma akkor is működik, ha a PowerShell execution policy blokkolja az `npm.ps1` fájlt. Nem kell emiatt gyengíteni a rendszer execution policy beállítását.

### Linux és macOS

```bash
git clone <REPOSITORY-URL> fakt-app
cd fakt-app
cp .env.example .env
touch database/database.sqlite
composer install --no-blocking
php artisan key:generate
php artisan migrate:fresh --seed
npm ci
npm run build
php artisan serve
```

Az alkalmazás a [http://127.0.0.1:8000](http://127.0.0.1:8000) címen indul. A helyi bemutatófiók: `elnok@fakt.local`, jelszó: `Fakt2027!`. A seedert és a `migrate:fresh` parancsot production környezetben tilos futtatni.

### Meglévő helyi példány frissítése

```bash
git pull
composer install --no-blocking
php artisan migrate
npm ci
npm run build
```

Ellenőrzés:

```bash
php artisan test
composer lint:check
npm run lint:check
npm run format:check
npm run types:check
npm run build
```

## Rackhost cPanel telepítés

A jelenlegi tárhelyen nincs Terminal/SSH, ezért ne töltsd fel egyszerűen a GitHub repositoryt: abból hiányozna a `vendor/` és a lefordított frontend.

1. GitHubon futtasd az **Actions → cPanel release package** workflow-t.
2. Töltsd le a létrejött `fakt-cpanel-release` artifactot.
3. A `fakt-app-core` mappát tedd a `/cphome/nxt02408/fakt-app-core` útvonalra, a `fakt-app-public` tartalmát pedig a `/cphome/nxt02408/public_html/fakt-app` mappába.
4. Hozd létre az `app.fakt.org.hu` aldomaint, MySQL adatbázist, AutoSSL-t és az állandó percenkénti cront.
5. Az egyszeri kulcsgenerálást, migrációt és első elnöki fiókot ideiglenes cron feladatok futtatják.

A teljes, kattintásról kattintásra leírás: [deploy/RACKHOST.md](deploy/RACKHOST.md).

## Biztonság és adatmegőrzés

Az alkalmazás publikus regisztrációt nem enged. Email-ellenőrzést, rate limitinget, erős jelszóhash-t, jelszó-resetet és opcionális TOTP MFA-t használ. A mellékletek privát tárhelyen vannak; letöltésük jogosultság-ellenőrzött, a méretlimit 10 MB, futtatható fájl nem tölthető fel.

Az ütemezett `fakt:retention` parancs a konfigurált 12/24 hónapos részletes adatmegőrzést hajtja végre. A kurzus-, tisztség- és diplomaeredmények hosszú távon megmaradnak. A végleges adatkezelési szabályzatot és megőrzési időket éles indulás előtt a FAKT-nak jóvá kell hagynia.

## Fontos fájlok

- `database/migrations/2026_08_09_000000_create_fakt_domain_tables.php` – teljes adatmodell;
- `database/seeders/DatabaseSeeder.php` – helyi bemutatóstruktúra;
- `routes/web.php` – alkalmazásfolyamatok és védett útvonalak;
- `tests/Feature/FaktWorkflowTest.php` – kritikus jogosultsági és üzleti folyamatok;
- `docs/PERMISSIONS.md` – jogosultsági mátrix;
- `docs/IMPORT.md` – importformátum;
- `.github/workflows/cpanel-release.yml` – SSH nélküli telepítési csomag.
