# Rackhost cPanel telepítés PHP 7.4-re, SSH nélkül

Ez a leírás a jelenlegi `nxt02408` cPanel-fiókhoz készült. A megoldás nem módosítja a `fakt.org.hu` nyilvános oldalát: az alkalmazás külön, az `app.fakt.org.hu` címen fut.

> Biztonsági figyelmeztetés: a PHP 7.4 és a Laravel 8 már nem támogatott. Ez a csomag technikailag fut a jelenlegi tárhelyen, de éles, személyes adatokat kezelő rendszerhez támogatott PHP 8.3+ tárhelyre váltás javasolt. A migrációig rendszeresen futtasd a GitHub Actions teszteket és figyeld a biztonsági értesítéseket.

## 1. Telepíthető ZIP elkészítése

1. GitHubon nyisd meg a repót, majd **Actions → cPanel release package → Run workflow**.
2. A sikeres futás alján töltsd le a `fakt-cpanel-release` artifactot.
3. Csomagold ki a letöltött artifactot, majd a benne lévő `fakt-cpanel-release.zip` fájlt is. Két mappát kapsz:
   - `fakt-app-core`: Laravel, `vendor/` és a lefordított frontend;
   - `fakt-app-public`: kizárólag a weben elérhető fájlok.

Ehhez a lépéshez a Rackhost szerveren nem kell Composer, Node.js vagy Terminal.

## 2. Aldomain és dokumentumgyökér

1. cPanel → **Domains → Create A New Domain**.
2. Domain: `app.fakt.org.hu`.
3. Kapcsold ki a közös dokumentumgyökeret, és add meg: `/public_html/fakt-app`.
4. Ha a DNS-zóna nem frissül automatikusan, a Rackhost **DNS zónák → Rekordok szerkesztése** alatt adj `app` A rekordot a tárhely megosztott IP-címére (`91.227.138.57`).
5. cPanel → **MultiPHP Manager**: csak az `app.fakt.org.hu` sort jelöld ki, válaszd a PHP 7.4-et, majd **Apply**.
6. cPanel → **SSL/TLS Status**: az aldomain legyen AutoSSL-be bevonva, majd **Run AutoSSL**. Ezután Domains alatt kapcsold be a **Force HTTPS Redirect** opciót.

## 3. MySQL adatbázis

1. cPanel → **MySQL Database Wizard**.
2. Hozz létre külön adatbázist és külön felhasználót; adj neki erős, egyedi jelszót.
3. Rendeld a felhasználót az adatbázishoz **All Privileges** jogosultsággal.
4. Jegyezd fel a cPanel által előtagolt teljes neveket, például `nxt02408_faktapp`.

## 4. Fájlok feltöltése

1. cPanel → **File Manager**.
2. A `/cphome/nxt02408` mappába töltsd fel és csomagold ki a `fakt-app-core` mappát. A végeredmény: `/cphome/nxt02408/fakt-app-core/artisan`.
3. A `/cphome/nxt02408/public_html/fakt-app` mappába a `fakt-app-public` mappa **tartalmát** töltsd fel. A végeredmény: `/cphome/nxt02408/public_html/fakt-app/index.php`.
4. A `fakt-app-core/storage` és `fakt-app-core/bootstrap/cache` mappák jogosultsága legyen írható a tárhely PHP-folyamata számára; cPanelen jellemzően `0755`, szükség esetén `0775`. Ne használj `0777`-et.

A Laravel-mag, a `.env`, a feltöltések és a naplók így a `public_html` mappán kívül maradnak.

## 5. Production `.env`

1. A core mappában másold át a `.env.cpanel.example` fájlt `.env` névre.
2. Töltsd ki a valódi adatbázis- és SMTP-adatokat.
3. Maradjon `APP_DEBUG=false`, `APP_ENV=production` és `APP_URL=https://app.fakt.org.hu`.
4. Ne töltsd fel a `.env` fájlt GitHubra, és ne tedd a publikus mappába.

Rackhostos postafiók használatakor az SMTP host/port/titkosítás pontos értékeit a cPanel **Connect Devices** oldala mutatja. A sablonban szereplő `mail.fakt.org.hu:587` csak kiinduló érték.

## 6. Egyszeri Artisan parancsok Terminal nélkül

A cPanel **Cron Jobs** oldalán ideiglenesen adj hozzá egy-egy, percenként futó feladatot. Minden parancsnál várd meg a napló létrejöttét, ellenőrizd, majd azonnal töröld az adott cron sort.

PHP 7.4 parancsútvonal:

```text
/usr/local/bin/ea-php74
```

1. Alkalmazáskulcs:

```text
/usr/local/bin/ea-php74 /cphome/nxt02408/fakt-app-core/artisan key:generate --force >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

2. Adatbázis-migráció:

```text
/usr/local/bin/ea-php74 /cphome/nxt02408/fakt-app-core/artisan migrate --force >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

3. Első elnöki fiók; az emailt és nevet cseréld ki:

```text
/usr/local/bin/ea-php74 /cphome/nxt02408/fakt-app-core/artisan fakt:bootstrap-president elnok@fakt.org.hu --name="Elnök neve" >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

4. Production cache:

```text
/usr/local/bin/ea-php74 /cphome/nxt02408/fakt-app-core/artisan optimize >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

A `fakt-deploy.log` tartalmazza az egyszer használatos elnöki jelszót. Belépés és jelszócsere után töröld ezt a naplófájlt a File Managerben. Productionben soha ne futtasd a `migrate:fresh` vagy `db:seed` parancsot.

## 7. Állandó cron

Hozz létre egyetlen, percenként futó cron feladatot:

```text
/usr/local/bin/ea-php74 /cphome/nxt02408/fakt-app-core/artisan schedule:run >> /dev/null 2>&1
```

Ez futtatja a rövid, adatbázis-alapú queue feldolgozást, emlékeztetőket, ismétlődő feladatokat és adatmegőrzési takarítást. Nem kell folyamatos queue worker.

## 8. Ellenőrzés és frissítés

- Nyisd meg a `https://app.fakt.org.hu` címet, jelentkezz be, és azonnal cseréld le a kezdeti jelszót.
- Teszteld a jelszó-reset és meghívó emailt, TOTP-t, védett fájlletöltést és az ICS-token visszavonását.
- Készíts napi mentést az adatbázisról és a `fakt-app-core/storage/app/private` mappáról; a `.env` és az `APP_KEY` külön, titkosított mentésben is legyen meg.
- Frissítéskor készíts új Actions artifactot, ments adatbázist/fájlokat, cseréld a két mappa alkalmazásfájljait, majd futtasd ideiglenes cronból a `migrate --force` és `optimize` parancsokat. A `.env` és a `storage/app/private` tartalma maradjon meg.

Go-live előtt kötelező a teljes visszaállítási próba és a jogosultsági teszt. A `deploy/rackhost-preflight.php` futtatható ideiglenes cronból is, ha a kimenetet naplóba irányítod.
