# Rackhost cPanel telepítés PHP 8.3-ra, SSH nélkül

Ez az útmutató a `nxt02408` cPanel-fiókhoz és az `app.fakt.org.hu` aldomainhez készült. A nyilvános `fakt.org.hu` oldal változatlan marad. A telepíthető csomag PHP 8.3-at és Laravel 13-at igényel.

Az aktuális kiadás önregisztrációt, elnöki fiókjóváhagyást, szigorú delegálást és rétegezett biztonsági megerősítést tartalmaz. A legújabb változások: [CHANGELOG-2026-08-18-SECURITY.md](../docs/CHANGELOG-2026-08-18-SECURITY.md), a kötelező biztonsági alapvonal: [SECURITY-HARDENING.md](../docs/SECURITY-HARDENING.md). Minden élesítéshez készíts külön másolatot a [DEPLOYMENT-LOG-TEMPLATE.md](DEPLOYMENT-LOG-TEMPLATE.md) fájlból.

## Melyik telepítési útvonalat válaszd?

- **Első telepítés:** nincs használatban lévő FAKT-adatbázis, nincs megőrzendő `.env`, és az `app.fakt.org.hu` még nem szolgál ki működő alkalmazást. Kövesd sorrendben ennek a dokumentumnak az 1–8. fejezetét.
- **Meglévő telepítés frissítése:** az aldomain már működik, van Elnök vagy tagi adat, illetve korábban már futott migráció. Ne generálj új kulcsot és ne seedelj. Kövesd a [RACKHOST-QUICK-DEPLOY.md](RACKHOST-QUICK-DEPLOY.md) staging és mappacsere eljárását.

Ha nem vagy biztos benne, hogy üres-e a rendszer, kezeld meglévő telepítésként. Ez a biztonságosabb út.

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
6. A csomag mellett található `fakt-cpanel-release.zip.sha256`. A számítógépen futtasd:

```powershell
$actual = (Get-FileHash .\fakt-cpanel-release.zip -Algorithm SHA256).Hash.ToLower()
$expected = (Get-Content .\fakt-cpanel-release.zip.sha256).Split(' ')[0].ToLower()
if ($actual -ne $expected) { throw 'HIBÁS VAGY SÉRÜLT KIADÁSI CSOMAG' }
```

7. Csak egyező hash esetén folytasd. A csomag tartalmazza a biztonsági kézikönyvet, útmutatókat, változásnaplót és telepítési naplót. Jegyezd fel az Actions URL-t és commit SHA-t.

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
3. Hozz létre két külön, erős és egymástól eltérő jelszavú felhasználót:
   - `faktdeploy`: csak migrációhoz, ideiglenesen **All Privileges**;
   - `faktruntime`: az alkalmazáshoz csak `SELECT`, `INSERT`, `UPDATE`, `DELETE`.
4. Jegyezd fel a teljes, cPanel-előtagos neveket, például:
   - adatbázis: `nxt02408_faktapp`;
   - deploy user: `nxt02408_faktdeploy`;
   - runtime user: `nxt02408_faktruntime`;
   - jelszavak: kizárólag a jelszókezelőben.

A runtime user soha ne kapjon `DROP`, `ALTER`, `CREATE`, `INDEX`, `FILE`, `GRANT` vagy `ALL PRIVILEGES` jogot. Ez az adatvesztés elleni legfontosabb szerveroldali korlát.

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
SESSION_ENCRYPT=true
SESSION_SAME_SITE=lax
APP_TRUSTED_HOST=app.fakt.org.hu
SECURITY_REQUIRE_PRIVILEGED_MFA=true
```

Az első migrációig a `DB_USERNAME`/`DB_PASSWORD` a deploy user legyen. A migráció után kötelező runtime userre cserélni és újracache-elni a konfigurációt.

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

Alkalmazáskulcs létrehozása — csak első telepítéskor:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan key:generate --force >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

Futtatókörnyezet és titokmentes production-konfiguráció ellenőrzése:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/deploy/rackhost-preflight.php >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

Adatbázistáblák létrehozása/frissítése:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan migrate --force >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

Ezután a `.env` fájlban cseréld a DB usert `nxt02408_faktruntime` értékre, a deploy usert pedig vedd le az adatbázisról. Csak ezután futtasd a bootstrapot és a production cache-t.

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
3. Azonnal állítsd be és erősítsd meg a TOTP MFA-t, a recovery kódokat offline, biztonságos helyen tárold.
4. A böngésző konzoljában ne legyen JavaScript hiba; a jelszófrissítés ne adjon 405 hibát.
5. Teszteld a jelszó-reset emailt, egy védett fájlletöltést, az ICS feedet és a TOTP kihívást.
6. DevTools/Network alatt ellenőrizd a CSP, `nosniff`, `DENY`, `no-referrer` és HSTS fejléceket.
7. Ellenőrizd, hogy `/.env`, `/artisan`, `/composer.json` és `/.git/config` nem érhető el.
8. Töröld a `fakt-deploy.log` fájlt, mert az első ideiglenes jelszót tartalmazhatja.
9. Készíts új adatbázis- és fájlmentést a működő állapotról.

A teljes támadási ellenőrzőlista a csomag `SECURITY-HARDENING.md` fájljában van. Ne futtass romboló SQL-t valódi production táblán.

### Jóváhagyásos regisztráció ellenőrzése

1. Kijelentkezve nyisd meg a `https://app.fakt.org.hu/register` címet.
2. Hozz létre egy külön tesztfiókot valódi, elérhető email címmel.
3. A regisztráció után a rendszernek a belépési oldalra kell visszairányítania, és elnöki jóváhagyásra váró állapotot kell jeleznie.
4. Próbálj belépni a tesztfiókkal. A belépést a rendszernek meg kell tagadnia.
5. Lépj be az Elnökkel → **Adminisztráció → Regisztrációs kérelmek**.
6. Ellenőrizd a nevet, emailt, évfolyamot és bemutatkozást, majd hagyd jóvá.
7. Ellenőrizd, hogy a jelentkező emailt kapott, majd végezze el az email-ellenőrzést.
8. A tesztfióknak ezután be kell tudnia lépni.
9. Hozz létre második tesztkérelmet, és ellenőrizd az indokolt elutasítást is. Az elutasított fiók nem léphet be.

### Delegálási lánc ellenőrzése

1. **Elnök:** a feladat felelőslistájában saját magát, Alelnököket és Projektvezetőket lásson; közvetlen Teamtagot ne.
2. **Alelnök:** csak a saját portfólió Teamvezetőit lássa.
3. **Teamvezető:** csak a saját Team tagjait lássa.
4. **Projektvezető:** csak az általa vezetett aktív projektek tagjait lássa.
5. **Tag:** csak saját feladatot hozhasson létre.
6. Minden szinten hozz létre egy tesztfeladatot, léptesd állapotban, és küldj hozzá hozzászólást.

## 9. Biztonságos frissítés meglévő telepítésről

A PHP 7.4/Laravel 8 → PHP 8.3/Laravel 13 átálláshoz a rövid, sorrendhelyes eljárás a [RACKHOST-QUICK-DEPLOY.md](RACKHOST-QUICK-DEPLOY.md) fájlban található. Ne másold rá vakon az új fájlokat a működő core mappára: staging mappát és visszaállítható mappacserét használj.

## 10. Mentés és visszaállítás

Minden kiadás előtt mentsd:

- a teljes MySQL adatbázist;
- `/cphome/nxt02408/fakt-app-core/.env`;
- `/cphome/nxt02408/fakt-app-core/storage/app/private`;
- az aktuális core és public mappákat.

Sikertelen frissítésnél állítsd vissza az adatbázismentést, a régi core/public mappákat és a hozzájuk tartozó PHP-verziót. A visszaállítást legalább havonta próbáld ki ellenőrzött környezetben.

## 11. Gyakori hibák az aktuális kiadásnál

### A `/register` 404-et ad

- Biztosan az aktuális GitHub Actions artifact került fel, nem a repository forrás-ZIP-je.
- Futtasd az `optimize:clear`, majd `optimize` parancsot.
- Ellenőrizd, hogy a core `config/fortify.php` fájlban engedélyezett a regisztráció.

### A regisztráció sikeres, de nem jelenik meg az Admin oldalon

- Futtasd a `migrate --force` parancsot; az új `approval_status` mezők nélkül a kiadás nem tekinthető telepítettnek.
- Ellenőrizd, hogy az elnöki fióknak az aktív félévben aktív `president` szerepe van.
- Nézd meg a `storage/logs/laravel.log` végét.

### A jóváhagyási email nem érkezik meg

- Ellenőrizd a cPanel SMTP-adatokat a `.env` fájlban.
- Ellenőrizd, hogy pontosan egy `schedule:run` cron fut percenként.
- A scheduler indítja a rövid életű `queue:work --stop-when-empty` feldolgozót; ezért az email 1–2 percet késhet.
- Nézd meg a `jobs` és `failed_jobs` táblát phpMyAdminban, valamint a Laravel logot.

### Egy vezető nem látja a kívánt felelőst

- Ellenőrizd az aktív félévet, a kinevezés `starts_at`, `ends_at`, `revoked_at` értékeit és a Team-/projekttagságot.
- A függő vagy elutasított fiók szándékosan nem delegálható.
- Az Elnök szándékosan nem delegál közvetlenül Teamtagnak; először Alelnöknek vagy Projektvezetőnek kell kiosztania.

### 500-as hiba közvetlenül migráció után

1. Ne futtass újabb, találomra választott Artisan parancsokat.
2. Ellenőrizd a `fakt-deploy.log` és `storage/logs/laravel.log` végét.
3. Ellenőrizd a `storage` és `bootstrap/cache` írási jogát.
4. Futtasd egyszer az `optimize:clear`, majd `optimize` parancsot.
5. Ha a hiba fennmarad, hajtsd végre a dokumentált visszaállítást az adatbázismentéssel együtt.
