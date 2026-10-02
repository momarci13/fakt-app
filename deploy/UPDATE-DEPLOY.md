# Frissítés egy már futó telepítésre (Rackhost cPanel)

Ez az útmutató egy **már működő** FAKT-telepítést frissít új kiadásra úgy, hogy az adatbázis,
az `.env` és a feltöltött fájlok megmaradnak. Új, üres telepítéshez a `FRESH-DEPLOY.md` kell.

A 2026. októberi kiadás tartalma:

- Alelnök és Teamvezető kinevezése és visszavonása a **Szervezet** oldalon.
- A scheduler a Rackhost `proc_open()` tiltása mellett is fut: újra egyetlen cron sor kell.
- A regisztrációs űrlap a valós jelszószabályt mutatja (legalább 15 karakter).
- A preflight már nem kéri a megszűnt `SECURITY_REQUIRE_PRIVILEGED_MFA` beállítást.

**Ebben a kiadásban nincs adatbázis-migráció.** Ne futtass `migrate`, `migrate:fresh`,
`db:seed`, `key:generate` vagy `fakt:bootstrap-president` parancsot.

---

## 0. Mentés

1. cPanel → **Backup** → *Download a MySQL Database Backup* → `nxt02408_faktapp`.
2. File Manager → töltsd le a `/cphome/nxt02408/fakt-app-core/.env` fájlt.

## 1. Csomag

1. GitHub → **Actions** → *cPanel release package* → zöld futás → `fakt-cpanel-release` artifact.
2. Csomagold ki a gépeden. PowerShellben ellenőrizd:
   `Get-FileHash fakt-cpanel-release.zip -Algorithm SHA256` = a `.sha256` fájl tartalma.

## 2. Feltöltés külön mappába

1. File Manager → a home mappában (`/cphome/nxt02408`) hozz létre egy `fakt-release` mappát.
2. Töltsd fel ide a `fakt-cpanel-release.zip` fájlt, és csomagold ki **ebben a mappában**.
   Így a `fakt-release/fakt-app-core` és a `fakt-release/fakt-app-public` jön létre, és nem
   keveredik az élő mappákkal.

## 3. Csere (innentől 2-3 percig nem elérhető az oldal)

1. Nevezd át: `fakt-app-core` → `fakt-app-core-prev`.
2. Nevezd át: `public_html/fakt-app` → `public_html/fakt-app-prev`.
3. Helyezd át (**Move**): `fakt-release/fakt-app-core` → `/cphome/nxt02408/fakt-app-core`.
4. Helyezd át: `fakt-release/fakt-app-public` → `/cphome/nxt02408/public_html/fakt-app`.
5. Másold (**Copy**): `fakt-app-core-prev/.env` → `fakt-app-core/.env`.
6. Ha a `fakt-app-core-prev/storage/app/private` mappában van fájl (feltöltött bizonyítékok,
   dokumentumok), másold a tartalmát a `fakt-app-core/storage/app/private` mappába.
7. Jogosultságok: `fakt-app-core/storage` és `fakt-app-core/bootstrap/cache` rekurzívan `0755`
   (szükség esetén `0775`). Soha nem `0777`.
8. Nyisd meg a `public_html/fakt-app/.htaccess` fájlt: a legelején ott kell lennie az
   `AddHandler application/x-httpd-ea-php83 .php .php8 .phtml` sornak.

## 4. Production cache

Ideiglenes, percenkénti cron. Egy futás után töröld:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan optimize:clear >> /cphome/nxt02408/fakt-deploy.log 2>&1 && /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan optimize >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

A naplóban minden sor `DONE` legyen.

## 5. Cron: vissza egyetlen sorra

1. cPanel → **Cron Jobs**: töröld az összes FAKT sort (a `queue:work --once` ciklust, a
   `fakt:recurring-tasks`, `fakt:due-reminders`, `fakt:retention` sort, és a régi
   `schedule:run` sort is).
2. Hozd létre pontosan ezt az egyet, **Once Per Minute**, üres Email mezővel:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan schedule:run >> /dev/null 2>&1
```

## 6. Ellenőrzés

1. Inkognitó ablak → `https://app.fakt.org.hu/login` betölt, az elnöki fiókkal be tudsz lépni.
2. **Szervezet** oldal: minden portfólión „Alelnök kinevezése”, minden Teamen
   „Teamvezető kinevezése” űrlap látszik.
3. Két perc után a phpMyAdminban a `jobs` tábla üres, a `failed_jobs` tábla üres.
4. A legújabb `fakt-app-core/storage/logs/laravel-*.log` fájlban nincs `proc_open` sor.
   (Ha nincs ilyen fájl, az is rendben van.)
5. Próbaregisztráció inkognitóban: a kérelem megjelenik az Admin → **Regisztrációk** alatt,
   és egy percen belül megérkezik az e-mail az elnöknek.

## 7. Takarítás

Csak ha a 6. pont minden eleme rendben van: töröld a `fakt-app-core-prev`, a
`public_html/fakt-app-prev` és a `fakt-release` mappát, valamint a `fakt-deploy.log` fájlt.

## Visszaállítás

Nevezd vissza a két `-prev` mappát az eredeti nevére (előtte az újakat nevezd át vagy töröld),
majd futtasd újra a 4. pont cronját. Az adatbázishoz nem kell nyúlni, mert ez a kiadás nem
módosítja a sémát.
