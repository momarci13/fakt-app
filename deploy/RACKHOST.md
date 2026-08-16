# Rackhost cPanel telepítés PHP 8.3-ra, SSH nélkül

Ez az útmutató a `nxt02408` cPanel-fiókhoz és az `app.fakt.org.hu` aldomainhez készült. A nyilvános `fakt.org.hu` oldal változatlan marad. A telepíthető csomag PHP 8.3-at és Laravel 13-at igényel.

## 0. Mielőtt elkezded

Ellenőrizd a cPanelben:

1. **MultiPHP Manager** alatt az `app.fakt.org.hu` domainhez választható a PHP 8.3.
2. **Cron Jobs**, **MySQL Databases**, **SSL/TLS Status** és **File Manager** elérhető.
3. A PHP-bővítmények között megtalálható legalább: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pdo_mysql`, `session`, `simplexml`, `tokenizer`, `xml`, `zip`.
4. Van friss adatbázis- és fájlmentésed.

Az alkalmazást ne a GitHub forráskód ZIP-jéből telepítsd. Az nem tartalmazza a `vendor/` mappát és a lefordított frontend fájlokat.

## 1. Telepíthető ZIP letöltése

1. GitHub → **Actions** → **cPanel release package**.
2. Nyisd meg a legújabb zöld, sikeres futást. Ha nincs ilyen, válaszd a **Run workflow** lehetőséget, és várd meg a sikert.
3. Az oldal alján töltsd le a `fakt-cpanel-release` artifactot.
4. A számítógépeden csomagold ki az artifactot. Benne lesz egy második fájl: `fakt-cpanel-release.zip`.
5. Ezt a második ZIP-et töltsd fel a cPanelbe. Kibontva két mappát ad:
   - `fakt-app-core`: Laravel, `vendor/`, konfiguráció és a lefordított assetek;
   - `fakt-app-public`: kizárólag a weben közvetlenül elérhető fájlok.

A Rackhost szerveren nem kell Composer, npm, Node.js vagy Terminal.

## 2. Első telepítés: domain és PHP 8.3

Ha az aldomain már létezik, csak ellenőrizd ezeket az értékeket.

1. cPanel → **Domains → Create A New Domain**.
2. Domain: `app.fakt.org.hu`.
3. Document root: `/public_html/fakt-app`.
4. Rackhost DNS-zóna: az `app` A rekord mutasson a tárhely IP-címére.
5. cPanel → **MultiPHP Manager** → jelöld ki kizárólag az `app.fakt.org.hu` sort → válaszd a **PHP 8.3** verziót → **Apply**.
6. **SSL/TLS Status** → az aldomain legyen AutoSSL-ben → **Run AutoSSL**.
7. Ha a tanúsítvány már zöld, a **Domains** oldalon kapcsold be a **Force HTTPS Redirect** opciót.

## 3. Első telepítés: MySQL

1. cPanel → **MySQL Database Wizard**.
2. Hozz létre külön adatbázist, például `faktapp` néven.
3. Hozz létre külön adatbázis-felhasználót erős, egyedi jelszóval.
4. Rendeld a felhasználót az adatbázishoz **All Privileges** jogosultsággal.
5. Jegyezd fel a teljes, cPanel-előtagos neveket, például:
   - adatbázis: `nxt02408_faktapp`;
   - felhasználó: `nxt02408_faktapp`;
   - jelszó: a létrehozáskor mentett adatbázisjelszó.

## 4. Első telepítés: fájlok

1. A `/cphome/nxt02408` mappában bontsd ki a kiadási ZIP-et.
2. A core végleges helye legyen `/cphome/nxt02408/fakt-app-core`. Ellenőrzés: itt közvetlenül látszódjon az `artisan` fájl.
3. A `fakt-app-public` mappa **tartalmát** másold a `/cphome/nxt02408/public_html/fakt-app` mappába. Az `index.php` és a rejtett `.htaccess` is közvetlenül ebben a mappában legyen.
4. A következő mappák jogosultsága legyen `0755`; ha a Rackhost írási hibát jelez, `0775`:
   - `/cphome/nxt02408/fakt-app-core/storage`;
   - `/cphome/nxt02408/fakt-app-core/bootstrap/cache`.
5. Soha ne használj `0777` jogosultságot.

## 5. Production `.env`

1. A core mappában másold le a `.env.cpanel.example` fájlt `.env` néven.
2. Töltsd ki a valódi adatbázis- és SMTP-adatokat.
3. Ezek maradjanak pontosan így:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.fakt.org.hu
DB_HOST=127.0.0.1
SESSION_SECURE_COOKIE=true
```

4. SMTP 587/STARTTLS esetén használd a `MAIL_SCHEME=smtp` értéket. SMTP 465/implicit TLS esetén `MAIL_SCHEME=smtps` és `MAIL_PORT=465` kell. A cPanel **Connect Devices** oldalán látható érték az irányadó.
5. A `.env` soha ne kerüljön GitHubra vagy `public_html` alá.

## 6. Egyszeri parancsok Cron Jobs segítségével

A PHP 8.3 parancsútvonala:

```text
/usr/local/bin/ea-php83
```

Minden alábbi parancsnál:

1. cPanel → **Cron Jobs**.
2. **Once Per Minute**.
3. Illeszd be az egyetlen aktuális parancsot.
4. Várj 1–2 percet.
5. File Managerben ellenőrizd a `/cphome/nxt02408/fakt-deploy.log` végét.
6. Az ideiglenes cron sort azonnal töröld, és csak ezután add hozzá a következőt.

Futtatókörnyezet ellenőrzése:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/deploy/rackhost-preflight.php >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

Alkalmazáskulcs létrehozása — csak első telepítéskor:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan key:generate --force >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

Adatbázistáblák létrehozása/frissítése:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan migrate --force >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

Első elnöki fiók — csak üres, első telepítésnél:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan fakt:bootstrap-president elnok@fakt.org.hu --name="Elnök neve" >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

Production cache:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan optimize:clear >> /cphome/nxt02408/fakt-deploy.log 2>&1 && /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan optimize >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

Productionben soha ne futtasd a `migrate:fresh` vagy `db:seed` parancsot. A `key:generate` parancsot frissítéskor sem szabad újra futtatni, mert azzal a korábbi titkosított adatok és munkamenetek olvashatatlanná válnának.

## 7. Állandó scheduler cron

Hozz létre egyetlen, percenként futó cron feladatot:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan schedule:run >> /dev/null 2>&1
```

Régi `ea-php74` scheduler sor ne maradjon aktív.

## 8. Első ellenőrzés

1. Nyisd meg inkognitó ablakban: `https://app.fakt.org.hu/login`.
2. Jelentkezz be az elnöki fiókkal, és azonnal módosítsd a kezdeti jelszót.
3. A böngésző konzoljában ne legyen JavaScript hiba; a jelszófrissítés ne adjon 405 hibát.
4. Teszteld a jelszó-reset emailt, egy védett fájlletöltést, az ICS feedet és a TOTP bekapcsolási képernyőjét.
5. Töröld a `fakt-deploy.log` fájlt, mert az első ideiglenes jelszót tartalmazhatja.
6. Készíts új adatbázis- és fájlmentést a működő állapotról.

## 9. Biztonságos frissítés meglévő telepítésről

A PHP 7.4/Laravel 8 → PHP 8.3/Laravel 13 átálláshoz a rövid, sorrendhelyes eljárás a [RACKHOST-QUICK-DEPLOY.md](RACKHOST-QUICK-DEPLOY.md) fájlban található. Ne másold rá vakon az új fájlokat a működő core mappára: staging mappát és visszaállítható mappacserét használj.

## 10. Mentés és visszaállítás

Minden kiadás előtt mentsd:

- a teljes MySQL adatbázist;
- `/cphome/nxt02408/fakt-app-core/.env`;
- `/cphome/nxt02408/fakt-app-core/storage/app/private`;
- az aktuális core és public mappákat.

Sikertelen frissítésnél állítsd vissza az adatbázismentést, a régi core/public mappákat és a hozzájuk tartozó PHP-verziót. A visszaállítást legalább havonta próbáld ki ellenőrzött környezetben.
