# Rackhost scheduler hotfix - PCNTL kompatibilitás

Ez a kiadás kizárólag a cPanel/Rackhost scheduler hibáját javítja. A hiba oka: a PHP 8.3 környezetben a `pcntl` bővítmény látszik, de a `pcntl_signal()` függvény tiltott. A FAKT ütemezett feladatai ezután megtartják a párhuzamos futást tiltó mutexet, de nem regisztrálnak PCNTL jelkezelőt.

## Mielőtt feltöltöd

1. cPanel -> **Cron Jobs**: töröld vagy tiltsd le az összes jelenlegi `schedule:run` sort.
2. Ne futtasd újra a `key:generate`, `migrate`, `migrate:fresh`, `db:seed` vagy `fakt:bootstrap-president` parancsot.
3. Ne módosítsd az `.env`, `APP_KEY`, SMTP vagy adatbázisadatokat.

## A hotfix telepítése

1. GitHub Actions -> **cPanel release package**: kizárólag a jelen hotfix utáni zöld futás artifactját töltsd le.
2. Csomagold ki az artifactot, majd a benne lévő `fakt-cpanel-release.zip` fájlt. Ellenőrizd a mellékelt SHA-256 értéket.
3. A cPanel File Managerben készíts visszaállítható másolatot:
   - `/cphome/nxt02408/fakt-app-core/app/Console/Kernel.php`
   - például `Kernel.php.before-scheduler-hotfix` néven ugyanabban a mappában.
4. Az új kiadás `fakt-app-core/app/Console/Kernel.php` fájlját töltsd fel, és ezzel írd felül a telepített `Kernel.php` fájlt.
5. Hozz létre egy ideiglenes, percenként futó cront az alábbi paranccsal; várj egy futást, ellenőrizd a naplót, majd töröld ezt a cron sort:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan optimize:clear >> /cphome/nxt02408/fakt-deploy.log 2>&1 && /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan optimize >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

## Ellenőrzés

Előbb nyisd meg inkognitó ablakban a `https://app.fakt.org.hu/login` oldalt. Ha betölt, futtasd külön-külön az alábbi két ideiglenes cron sort. Mindegyiket egy futás után töröld.

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan schedule:list >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan schedule:run >> /cphome/nxt02408/fakt-scheduler-test.log 2>&1
```

Ellenőrizd, hogy a `fakt-scheduler-test.log` és a legújabb `storage/logs/laravel-YYYY-MM-DD.log` nem tartalmaz `pcntl_signal`, `ERROR` vagy `SQLSTATE` sort.

Ha mindkettő tiszta, hozz létre pontosan egy állandó, percenként futó cron sort:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan schedule:run >> /cphome/nxt02408/fakt-app-core/storage/logs/scheduler.log 2>&1
```

Az első 24 órában naponta ellenőrizd a `scheduler.log` és Laravel log végét. Ha a hotfix után is HTTP 500 jelenik meg, ne futtass migrációt: a legújabb, a böngésző frissítése UTÁN keletkezett Laravel hibaüzenetet vizsgáld.
