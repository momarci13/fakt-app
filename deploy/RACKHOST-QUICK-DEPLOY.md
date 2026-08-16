# Rackhost PHP 8.3 frissítés — rövid, biztonságos eljárás

Ezt használd a már működő `app.fakt.org.hu` telepítés PHP 7.4/Laravel 8 verziójáról az új PHP 8.3/Laravel 13 kiadásra történő frissítéséhez. A sorrendet tartsd be.

## 1. Töltsd le a helyes csomagot

1. GitHub → **Actions → cPanel release package**.
2. Csak zöld, sikeres futás artifactját töltsd le.
3. Csomagold ki az artifactot, majd a benne lévő `fakt-cpanel-release.zip` fájlt is.

## 2. Készíts teljes mentést

Mentsd le:

- a MySQL adatbázist a cPanel Backup/phpMyAdmin segítségével;
- `/cphome/nxt02408/fakt-app-core/.env`;
- `/cphome/nxt02408/fakt-app-core/storage/app/private`;
- `/cphome/nxt02408/fakt-app-core` teljes mappát;
- `/cphome/nxt02408/public_html/fakt-app` teljes mappát.

Mentés nélkül ne folytasd.

## 3. Készíts staging mappákat

1. Töltsd fel az új `fakt-app-core` mappát ide: `/cphome/nxt02408/fakt-app-core-next`.
2. Töltsd fel az új `fakt-app-public` mappát ide: `/cphome/nxt02408/fakt-app-public-next`.
3. Másold a régi core `.env` fájlját a `fakt-app-core-next/.env` helyre.
4. Az új `.env` fájlban:
   - `DB_HOST=127.0.0.1`;
   - `MAIL_SCHEME=smtp` 587-es porthoz, vagy `smtps` 465-ös porthoz;
   - `APP_ENV=production`, `APP_DEBUG=false`;
   - az eredeti `APP_KEY` változatlan marad.
5. Másold át a régi `storage/app/private` **tartalmát** az új core azonos mappájába.
6. Az új `storage` és `bootstrap/cache` jogosultsága legyen `0755`, szükség esetén `0775`, de soha ne `0777`.

## 4. Ellenőrizd az új core-t még az átváltás előtt

Ideiglenes **Once Per Minute** cron:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core-next/deploy/rackhost-preflight.php >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

Várj egy percet, ellenőrizd a naplót, majd töröld a cron sort. Csak `[OK]` eredménnyel folytasd.

## 5. Kapcsold maintenance módba az új kiadást

Ideiglenes cron:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core-next/artisan down --secret=FAKT-frissites-2026 >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

Várj egy percet, ellenőrizd a naplót, majd töröld a cron sort. A secretet saját, nehezen kitalálható értékre cserélheted.

## 6. Állítsd le a régi schedulert és válts PHP 8.3-ra

1. Töröld vagy ideiglenesen tiltsd le az állandó `ea-php74 ... schedule:run` cron sort.
2. **MultiPHP Manager** → csak `app.fakt.org.hu` → **PHP 8.3** → **Apply**.

## 7. Cseréld fel a mappákat

File Managerben:

1. Nevezd át `/cphome/nxt02408/fakt-app-core` mappát `fakt-app-core-backup` névre.
2. Nevezd át `fakt-app-core-next` mappát `fakt-app-core` névre.
3. Nevezd át `/cphome/nxt02408/public_html/fakt-app` mappát `fakt-app-backup` névre.
4. Mozgasd a `fakt-app-public-next` mappát `/cphome/nxt02408/public_html/fakt-app` helyre.
5. Ellenőrizd, hogy az új public mappában közvetlenül ott van az `index.php`, `.htaccess`, `build` és `manifest.webmanifest`.

## 8. Futtasd a frissítést

Ideiglenes **Once Per Minute** cron:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan migrate --force >> /cphome/nxt02408/fakt-deploy.log 2>&1 && /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan optimize:clear >> /cphome/nxt02408/fakt-deploy.log 2>&1 && /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan optimize >> /cphome/nxt02408/fakt-deploy.log 2>&1 && /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan up >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

Várj 1–2 percet. A naplóban nem lehet `ERROR`, `SQLSTATE`, `Class not found` vagy permission hiba. Ezután azonnal töröld az ideiglenes cron sort.

## 9. Hozd létre az új állandó cront

**Once Per Minute**:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan schedule:run >> /dev/null 2>&1
```

Pontosan egy scheduler cron maradjon.

## 10. Ellenőrizd a működést

1. Inkognitó ablak: `https://app.fakt.org.hu/login`.
2. Belépés, dashboard, jelszófrissítési oldal.
3. Próbálj hibás jelenlegi jelszóval menteni: validációs hibát kell kapnod, nem 405-ös oldalt.
4. Tesztelj egy emailt, fájlletöltést és ICS feedet.
5. Ellenőrizd a Laravel naplót: `fakt-app-core/storage/logs`.

Ha minden rendben, 24–48 óra után törölheted a `fakt-app-core-backup` és `public_html/fakt-app-backup` mappát. A biztonsági mentést tartsd meg a megőrzési szabály szerint.

## Visszaállítás hiba esetén

1. Kapcsold vissza maintenance módba az aktuális core-t.
2. Állítsd vissza a mentett MySQL adatbázist.
3. Nevezd vissza a régi core és public backup mappákat.
4. MultiPHP Managerben állítsd vissza a régi PHP-verziót.
5. Állítsd vissza a régi scheduler cront.

Productionben tilos: `migrate:fresh`, `db:seed`, frissítés közbeni `key:generate`, illetve az `.env` vagy `storage/app/private` felülírása.
