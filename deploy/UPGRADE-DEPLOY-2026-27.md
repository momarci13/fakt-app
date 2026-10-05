# 2026/27-es fejlesztési kiadások telepítése (Rackhost cPanel)

Ez az útmutató a `docs/UPGRADE-PLAN-2026-27.md` szakaszainak (Phase 0–3) kiadásait telepíti egy
**már futó** rendszerre. Az adatbázis, az `.env` és a feltöltött fájlok megmaradnak.

> **Amíg a Phase 0 nincs telepítve: az Admin oldalon ne hozz létre új félévet bepipált
> „Aktiválás” mezővel!** Az aktiválás után a rendszer senkit nem tekint Elnöknek: az Admin oldal
> 403-at ad, és a `fakt:bootstrap-president` parancs is megtagadja a helyreállítást. A Phase 0
> ezt javítja.

Két kiadástípus van:

| Típus | Mikor | Melyik eljárás |
|---|---|---|
| **A – migráció nélkül** | a kiadás leírása szerint „nincs adatbázis-migráció” (pl. Phase 0) | `deploy/UPDATE-DEPLOY.md`, utána a 7. lépés ellenőrzései |
| **B – migrációval** | új tábla vagy oszlop (pl. Phase 1, Phase 2) | ez a dokumentum, 0–9. lépés |

A migrációk ebben a tervben **csak bővítenek**: új táblát vagy oszlopot adnak hozzá, semmit nem
törölnek és nem neveznek át. Ezért a régi kód az új sémán is fut, és a visszaállításhoz
(9. lépés) nem kell az adatbázishoz nyúlni.

Soha ne futtasd: `migrate:fresh`, `migrate:rollback`, `db:seed`, `key:generate`.

---

## Előkészítés a saját gépeden

1. GitHubon a kiadás pull requestje **Merge**-elve van a `main` ágba.
2. GitHub → **Actions** → *cPanel release package* → a `main` legutóbbi **zöld** futása →
   alul **Artifacts** → `fakt-cpanel-release` letöltése.
3. Csomagold ki a letöltött ZIP-et. PowerShellben:

   ```powershell
   Get-FileHash fakt-cpanel-release.zip -Algorithm SHA256
   ```

   Az eredmény egyezzen a mellette lévő `.sha256` fájl tartalmával.
4. Olvasd el a kiadás leírását (a PR szövegét): van-e migráció, és kell-e egyszeri parancs
   (pl. `fakt:backfill-course-sessions`).
5. Legyen kéznél a `nxt02408_faktdep` jelszava. Ha nem tudod: cPanel → **MySQL Databases** →
   *Current Users* → `nxt02408_faktdep` → **Change Password**. Csak betűt és számot használj,
   mert a `#` az `.env`-ben megjegyzést kezd.

## 0. Mentés

1. cPanel → **Backup** → *Download a MySQL Database Backup* → `nxt02408_faktapp`.
2. File Manager → töltsd le: `/cphome/nxt02408/fakt-app-core/.env`.
3. Ha a `fakt-app-core/storage/app/private` mappában van fájl: jelöld ki a mappát →
   **Compress** → töltsd le a ZIP-et.

A mentést a takarítás (8. lépés) után is tartsd meg a következő kiadásig.

## 1. Feltöltés külön mappába

1. File Manager → `/cphome/nxt02408` → **+ Folder** → `fakt-release`.
2. Töltsd fel ide a `fakt-cpanel-release.zip` fájlt → jobb klikk → **Extract** ebbe a mappába.
   Így létrejön a `fakt-release/fakt-app-core` és a `fakt-release/fakt-app-public`.

## 2. Csere (innentől néhány percig nem elérhető az oldal)

1. Nevezd át: `fakt-app-core` → `fakt-app-core-prev`.
2. Nevezd át: `public_html/fakt-app` → `public_html/fakt-app-prev`.
3. **Move**: `fakt-release/fakt-app-core` → `/cphome/nxt02408/fakt-app-core`.
4. **Move**: `fakt-release/fakt-app-public` → `/cphome/nxt02408/public_html/fakt-app`.
5. **Copy**: `fakt-app-core-prev/.env` → `fakt-app-core/.env`.
6. Ha volt fájl a `fakt-app-core-prev/storage/app/private` mappában, másold át a tartalmát a
   `fakt-app-core/storage/app/private` mappába.
7. Jogosultságok: `fakt-app-core/storage` és `fakt-app-core/bootstrap/cache` rekurzívan `0755`
   (ha írási hiba van, `0775`). **Soha nem `0777`.**
8. Nyisd meg a `public_html/fakt-app/.htaccess` fájlt. A legelső sor ez legyen:
   `AddHandler application/x-httpd-ea-php83 .php .php8 .phtml`

## 3. Karbantartási mód és cache törlése

cPanel → **Cron Jobs** → *Add New Cron Job* → **Once Per Minute**, az Email mező üres.
Parancs (egy sor):

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan down --retry=60 >> /cphome/nxt02408/fakt-deploy.log 2>&1 && /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan optimize:clear >> /cphome/nxt02408/fakt-deploy.log 2>&1 && /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan migrate:status >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

Várj egy percet, **töröld a cront**, és nézd meg a `/cphome/nxt02408/fakt-deploy.log` fájlt:

- a `down` sor után: „Application is now in maintenance mode.”,
- az `optimize:clear` minden sora `DONE`,
- a `migrate:status` táblázat végén `Pending` állapotú sorok: ezek az új migrációk. A számuk
  egyezzen a kiadás leírásával.

Az oldal most „karbantartás” (503) oldalt mutat. Ez a várt állapot.

## 4. Telepítő adatbázis-user bekapcsolása

1. File Manager → `fakt-app-core/.env` → **Edit**.
2. Írd át ezt a két sort (a jelszót sehova ne másold ki, ne küldd el):

   ```text
   DB_USERNAME=nxt02408_faktdep
   DB_PASSWORD=<a faktdep jelszava>
   ```

3. **Save Changes**.

Az oldal karbantartási módban van, ezért ezzel a userrel most senki nem fér hozzá az alkalmazáshoz.

## 5. Migráció és egyszeri parancsok

Ideiglenes **Once Per Minute** cron:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan migrate --force >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

Egy perc múlva töröld a cront. A naplóban minden migráció `DONE` legyen, `FAIL` vagy
`SQLSTATE` sor ne legyen.

Ha a kiadás leírása egyszeri parancsot kér, például Phase 1-nél a kurzusalkalmak legyártását,
futtasd külön cronnal, szintén egyszer:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan fakt:backfill-course-sessions >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

> **Ha a migráció hibát ad:** ne próbálkozz újra, és ne futtass `migrate:rollback`-et. Állítsd
> vissza a 4. lépés `.env` sorait, és kövesd a 9. lépést. A naplót (jelszó nincs benne) küldd
> el a fejlesztőnek.

## 6. Vissza a futtató userre, cache, karbantartás vége

1. `fakt-app-core/.env` → **Edit** → vissza:

   ```text
   DB_USERNAME=nxt02408_faktruntime
   DB_PASSWORD=<a faktruntime jelszava, ahogy a mentett .env-ben volt>
   ```

   (A legegyszerűbb: nyisd meg a 0. lépésben letöltött `.env`-et, és onnan másold vissza a két sort.)
2. Ideiglenes **Once Per Minute** cron, egy futás után töröld:

   ```text
   /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan optimize >> /cphome/nxt02408/fakt-deploy.log 2>&1 && /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan up >> /cphome/nxt02408/fakt-deploy.log 2>&1
   ```

3. A naplóban az `optimize` sorai `DONE`, a végén „Application is now live.”
4. cPanel → **Cron Jobs**: pontosan **egy** FAKT-sor maradjon (ez nem változik):

   ```text
   /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan schedule:run >> /dev/null 2>&1
   ```

5. cPanel → **MySQL Databases** → ellenőrizd, hogy a `nxt02408_faktruntime` user továbbra is
   csak SELECT/INSERT/UPDATE/DELETE joggal van az adatbázishoz rendelve.

## 7. Ellenőrzés

Mindig:

1. Inkognitó ablak → `https://app.fakt.org.hu/login` → belépés az elnöki fiókkal.
2. Két perc után phpMyAdmin: a `jobs` és a `failed_jobs` tábla üres.
3. A legújabb `fakt-app-core/storage/logs/laravel-*.log` fájlban nincs `ERROR`, `proc_open`
   vagy `SQLSTATE` sor. Ha nincs ilyen fájl, az is rendben van.

Kiadásonként:

| Kiadás | Mit nézz meg |
|---|---|
| **Phase 0** | Admin → Félév: új félév aktiválásakor felajánlja a vezetőség átvitelét, és **nem engedi** elnök nélkül aktiválni. *Ne aktiválj új félévet élesben csak a teszt kedvéért!* Az oldalak érezhetően gyorsabbak (Feladatok, Dashboard). |
| **Phase 1** | Kurzusok: egy heti kurzus **minden** alkalma látszik a Naptárban. Egy jóváhagyott tagnak, az oktatónak és a KTSZT-felelősnek is megjelenik a saját naptárában. A privát ICS-link a Google Naptárban is mutatja az alkalmakat (a Google akár 8–24 óra után frissít, ez normális). Egy jelentkezés jóváhagyása után a tag értesítést kap: „bekerült a naptáradba”. |
| **Phase 2** | Szervezet: a „Ki kicsoda” nézet minden tisztséget mutat. Egy helyettesítés beállítható, és a lejárat után magától megszűnik. A delegálási lista szerepkörönként a `docs/PERMISSIONS.md` szerint alakul. |

## 8. Takarítás

Csak ha a 7. lépés minden pontja rendben van, töröld:
- a `fakt-app-core-prev` és a `public_html/fakt-app-prev` mappát,
- a `fakt-release` mappát,
- a `fakt-deploy.log` fájlt.

A gépeden lévő mentést (adatbázis, `.env`) tartsd meg a következő kiadásig.

## 9. Visszaállítás

**A kód rossz, a migráció lefutott** (a gyakoribb eset):

1. Nevezd át az új mappákat (`fakt-app-core` → `fakt-app-core-bad`, `public_html/fakt-app` →
   `public_html/fakt-app-bad`), a `-prev` mappákat pedig vissza az eredeti nevükre.
2. Ellenőrizd, hogy az `.env`-ben a `nxt02408_faktruntime` user van.
3. Futtasd a 6. lépés 2. pontjának cronját (`optimize` + `up`).

Az új táblák és oszlopok bent maradnak, de a régi kód nem használja őket, ezért ez biztonságos.

**A migráció félúton megállt** (MySQL-ben a DDL nem vonható vissza tranzakcióval):

1. Végezd el a fenti visszaállítást.
2. phpMyAdmin → `nxt02408_faktapp` → **Import** → a 0. lépésben mentett `.sql` fájl. Ez a mentés
   óta készült adatokat felülírja, de a karbantartási mód miatt ilyen nincs.
3. Küldd el a `fakt-deploy.log` fájlt a fejlesztőnek, mielőtt törölnéd.
