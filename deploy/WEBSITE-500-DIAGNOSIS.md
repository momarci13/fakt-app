# A weboldal HTTP 500 hibájának felderítése

## Amit már tudunk

Két mérés szűkíti le a kört:

1. A `https://app.fakt.org.hu/robots.txt` **HTTP 200**-at ad. Tehát az Apache, a virtualhost, az SSL,
   a document root és a `.htaccess` **rendben van**. A hiba a PHP rétegben keletkezik.
2. A `https://app.fakt.org.hu/login` **HTTP 500**-at ad, miközben a `fakt-deploy.log` szerint az
   `optimize:clear` és az `optimize` hibátlanul lefut.

Egy tiszta `artisan` futás **nem** bizonyítja, hogy a weboldal működik. A cron az
`/usr/local/bin/ea-php83` binárist teljes elérési úttal hívja, a weboldalt viszont az a PHP futtatja,
amit a cPanel **MultiPHP Manager** rendelt ehhez az aldomainhez. A kettő külön beállítás.

| Hiba | `artisan optimize` | Böngésző | Laravel napló |
| --- | --- | --- | --- |
| **Az aldomain PHP handlere nem 8.3** | zöld | HTTP 500 | **üres** |
| Hibás hosszúságú `APP_KEY` | zöld | HTTP 500 | üres |
| A runtime DB-usernek nincs `INSERT` joga | zöld | HTTP 500 | van bejegyzés |
| `storage/` nem írható a webkiszolgálónak | zöld | HTTP 500 | üres |
| Hiányzó `sessions` tábla | zöld | HTTP 500 | van bejegyzés |
| Rossz `APP_TRUSTED_HOST` | zöld | HTTP 400 | van bejegyzés |

## 1. A legvalószínűbb ok: rossz PHP handler az aldomainen

Az alkalmazás korábban PHP 7.4-en futott, és a 8.3-ra váltás a telepítési útmutató 7.2. lépése:
**cPanel → MultiPHP Manager → `app.fakt.org.hu` → PHP 8.3 → Apply.** Ez kézi kattintás, könnyű
kihagyni, és a `deploy/rackhost-preflight.php` sem veszi észre, mert az maga is cronból, `ea-php83`
alatt fut.

Ha az aldomain 8.3-nál régebbi PHP-n van, a kérés a Laravel elindulása **előtt** hal meg:

- PHP 7.x esetén a `index.php` `str_starts_with()` hívása: `Call to undefined function`;
- PHP 8.0–8.2 esetén a `vendor/composer/platform_check.php` dob:
  `Your Composer dependencies require a PHP version ">= 8.3.0"`.

Mindkettő a Laravel hibakezelője előtt történik, ezért a `storage/logs/laravel-*.log` **üres marad**.
Pontosan ezt látod.

## 1b. MEGERŐSÍTVE: a MultiPHP Manager 8.3-at mutat, a szerver mégis 7.4-et futtat

A védelem bekapcsolása után a weboldal ezt írta ki: **PHP 7.4.33** — miközben a cPanel
MultiPHP Manager felületén az `app.fakt.org.hu` sorban `PHP 8.3 (ea-php83)` szerepel.
A felületi érték ilyenkor megtévesztő. Az ok két dolog együttállása:

1. A `PHP-FPM` oszlop minden domainnél tiltott. FPM nélkül azt, hogy melyik PHP futtatja a
   kérést, kizárólag a `.htaccess` `AddHandler` sora dönti el.
2. Az `app.fakt.org.hu` dokumentumgyökere `/cphome/nxt02408/public_html/fakt-app`, tehát a
   `public_html` **alatt** van. A `fakt.org.hu` PHP 7.4-en fut, és az Apache a kérés
   kiszolgálásakor a `/cphome/nxt02408/public_html/.htaccess` fájlt is beolvassa. Az ottani
   `ea-php74` handler blokk így öröklődik az aldomain mappájába.

A cPanel ezt normális esetben úgy oldja meg, hogy a saját handler blokkját kiírja az aldomain
dokumentumgyökerének `.htaccess` fájljába. A telepítés 8. lépése viszont a **teljes public
mappát lecseréli**, és ezzel ezt a blokkot letörli. Innentől az örökölt PHP 7.4 érvényes.

### Azonnali javítás

cPanel → **MultiPHP Manager** → pipáld ki az `app.fakt.org.hu` sort → állítsd **PHP 8.2**-re →
**Apply** → majd újra **PHP 8.3** → **Apply**. A verzió tényleges megváltoztatása kényszeríti a
cPanelt, hogy újraírja a handler blokkot. Ha csak a már beállított 8.3-at alkalmazod újra,
a cPanel nem feltétlenül ír ki semmit.

Ha ez nem segít, írd be kézzel a `/cphome/nxt02408/public_html/fakt-app/.htaccess` fájl
**legelejére**:

```apache
# php -- BEGIN cPanel-generated handler, do not edit
AddHandler application/x-httpd-ea-php83 .php .php8 .phtml
# php -- END cPanel-generated handler, do not edit
```

Ellenőrzésként nézd meg a `/cphome/nxt02408/public_html/.htaccess` fájlt: abban meg kell
találnod az `ea-php74` handler blokkot. Ez a bizonyíték az öröklődésre. **Ne módosítsd**,
mert az a `fakt.org.hu` oldalt szolgálja ki.

### Hogy ne fordulhasson elő újra

A kiadási csomag mostantól maga hozza a handler blokkot: a `deploy/cpanel-php-handler.htaccess`
tartalma a build során a `fakt-app-public/.htaccess` fájl elejére kerül. Így a public mappa
cseréje többé nem törli a PHP 8.3 beállítást.

## 2. A javítás: önmagát megmagyarázó belépési pont

A `deploy/cpanel-public-index.php` és a `public/index.php` mostantól tartalmaz egy védelmet, amely

- csak PHP 5.4+ szintaxist és függvényeket használ az autoloader betöltése előtt, ezért **akkor is
  lefut, ha a szerver régi PHP-n van**;
- PHP 8.3 alatt HTTP 503-mal és magyar nyelvű magyarázattal válaszol, kiírva a tényleges PHP verziót
  és a pontos cPanel lépést;
- minden más, a Laravel elindulása előtti hibát (autoloader, Composer platform check, `bootstrap/app.php`)
  elkap, és a `storage/logs/bootstrap-error.log` fájlba írja — a böngésző továbbra is semleges
  üzenetet kap, útvonalak nélkül.

### Telepítés — egyetlen fájl

```text
töltsd fel: /cphome/nxt02408/public_html/fakt-app/index.php
forrás:     a kiadási csomag fakt-app-public/index.php fájlja
```

Nem kell migráció, Composer, npm vagy újratelepítés. Készíts előtte másolatot
`index.php.before-guard` néven ugyanabban a mappában.

Frissítés után nyisd meg inkognitóban a `https://app.fakt.org.hu/login` címet:

- **Ha a PHP verzióról szóló magyar üzenet jelenik meg** — megvan az ok. Menj a
  cPanel → MultiPHP Manager → `app.fakt.org.hu` → PHP 8.3 → Apply lépésre, majd töltsd újra az oldalt.
- **Ha `Service temporarily unavailable.` jelenik meg** — a hiba más, de már van nyoma:
  nézd meg a `/cphome/nxt02408/fakt-app-core/storage/logs/bootstrap-error.log` fájlt.
- **Ha továbbra is üres HTTP 500 jön** — akkor a hiba a Laravelen belül van, tehát a 3. lépés következik.

## 3. Ha a védelem átengedi a kérést: `fakt:diagnose`

A `fakt:diagnose` parancs a CLI-folyamaton belül lejátszik egy valódi HTTP kérést a teljes
middleware-láncon, elkapja a kivételt, és ellenőrzi az `APP_KEY`-t, a jogosultságokat, az
adatbázistáblákat, a DB-user írásjogát, a naplócsatornákat, valamint kiírja a
`bootstrap-error.log` és a Laravel napló végét. Az `APP_DEBUG` végig `false` marad.

```text
töltsd fel: /cphome/nxt02408/fakt-app-core/app/Console/Commands/Diagnose.php

ideiglenes, percenként futó cron:
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan fakt:diagnose >> /cphome/nxt02408/fakt-diagnose.log 2>&1
```

Egy futás után töröld a cron sort, és olvasd el a `fakt-diagnose.log` `[HIBA]` sorait.

Fontos korlát: a `fakt:diagnose` a **CLI** PHP-t látja, a weboldalét nem. A web PHP verzióját
kizárólag a 2. lépés védelme tudja megmutatni.

## 4. Gyakori okok és javításuk

### `[HIBA] Az APP_KEY hossza nem illik a(z) AES-256-CBC cipherhez`

**Ne futtass `key:generate` parancsot**, amíg nem tisztáztad, hogy az eredeti kulcs visszaállítható-e:
kulcscsere után minden munkamenet és minden titkosított mező olvashatatlanná válik. Előbb a mentett
`.env` fájlból állítsd vissza az eredeti kulcsot.

### `[HIBA] A DB-usernek nincs írás joga a(z) cache táblán`

A telepítés 9. lépése a `nxt02408_faktruntime` userre vált. cPanel → **MySQL Databases → Add User To
Database**: a usernek mind a négy jog kell (`SELECT`, `INSERT`, `UPDATE`, `DELETE`). A session driver
minden kérésnél ír, ezért `INSERT` jog nélkül minden oldal 500-at ad, miközben az `artisan` zöld marad.

### `[HIBA] Nem írható: .../storage/...`

A `storage` és `bootstrap/cache` mappák rekurzívan `0755` (szükség esetén `0775`) legyenek, soha nem
`0777`. Ellenőrizd a tulajdonost is: a File Managerrel kicsomagolt fájlok más tulajdonost kaphatnak,
mint amivel a PHP-FPM fut.

### Minden `[OK]`, de a böngésző hibázik

Ekkor a PHP-kód rendben van. Nézd meg a cPanel **Errors** oldalát. Mivel a `robots.txt` 200-at ad, az
`.htaccess` és az `AllowOverride` már kizárható.
