# 2026/27-es nagy kiadás telepítése (Rackhost cPanel)

Ez az útmutató egy **már futó** FAKT-telepítést frissít úgy, hogy az adatbázis, az `.env` és a
feltöltött fájlok megmaradnak. Új, üres telepítéshez a `FRESH-DEPLOY.md` kell.

**Ez a kiadás tartalmazza az októberi frissítést is** (`UPDATE-DEPLOY.md`), amely nem lett
telepítve. Azt nem kell külön elvégezni: ez az útmutató a cron-sorok rendbetételét is tartalmazza.

## Mi kerül élesbe

- **Kizárás elleni védelem:** új félév csak akkor aktiválható, ha van Elnöke. Aktiváláskor a még
  futó mandátumok átkerülnek (Elnök és Alelnök július 1. – június 30., Teamvezető félévente).
  Félévkezelés az Admin oldalon.
- **Kurzusok a saját naptárban:** minden alkalom külön esemény (téli/nyári időszámítás-helyesen).
  A jóváhagyott és a várólistás jelentkezők látják. Időpontszavazás, automatikus beosztás,
  kurzusonként 2 megengedett hiányzás, Elnökségi felmentés.
- **Gyorsabb oldalak:** a Feladatok oldal 70 helyett kb. 20 adatbázis-lekérdezéssel töltődik be.
- **8 új funkció:** havi naptárnézet és szűrők; „Google Naptár” és `.ics` minden eseményhez; napi
  email-összesítő; QR-kódos bejelentkezés; Vezetői áttekintés; Rendszerállapot oldal;
  CSV-exportok; Tagnévsor.

**Ebben a kiadásban van adatbázis-migráció** (egy darab:
`2026_10_06_000000_add_course_sessions_waivers_and_digest`). Csak új táblákat és oszlopokat ad
hozzá, és a futó Elnök/Alelnök/Teamvezető kinevezések záródátumát a mandátum végére állítja.
Semmit nem töröl. Ezért a visszaállításhoz (10. lépés) általában nem kell az adatbázishoz nyúlni.

Soha ne futtasd: `migrate:fresh`, `migrate:rollback`, `db:seed`, `key:generate`.

Számolj kb. 30–40 perccel. Az oldal kb. 5–10 percig nem lesz elérhető (karbantartási mód).

---

## Előkészítés a saját gépeden

1. GitHubon a kiadás pull requestje **Merge**-elve van a `main` ágba
   (**Pull requests** → a PR → **Merge pull request** → **Confirm merge**).
2. GitHub → **Actions** → *cPanel release package* → a `main` legutóbbi **zöld** futása →
   alul **Artifacts** → `fakt-cpanel-release` letöltése.
3. Csomagold ki a letöltött ZIP-et. PowerShellben:

   ```powershell
   Get-FileHash fakt-cpanel-release.zip -Algorithm SHA256
   ```

   Az eredmény egyezzen a mellette lévő `.sha256` fájl tartalmával.
4. Legyen kéznél a `nxt02408_faktdep` jelszava. Ha nem tudod: cPanel → **MySQL Databases** →
   *Current Users* → `nxt02408_faktdep` → **Change Password**. Csak betűt és számot használj,
   mert a `#` az `.env`-ben megjegyzést kezd.

## 0. Mentés

1. cPanel → **Backup** → *Download a MySQL Database Backup* → `nxt02408_faktapp`.
2. File Manager → töltsd le: `/cphome/nxt02408/fakt-app-core/.env`.
3. Ha a `fakt-app-core/storage/app/private` mappában van fájl: jelöld ki a mappát →
   **Compress** → töltsd le a ZIP-et.

A mentést a takarítás (9. lépés) után is tartsd meg a következő kiadásig.

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

## 3. Cron-sorok rendbetétele

Mivel az októberi frissítés kimaradt, még a régi, ideiglenes cron-sorok futnak.

1. cPanel → **Cron Jobs** → *Current Cron Jobs*: **töröld az összes FAKT-sort**: a
   `queue:work --once` ciklust, a `fakt:recurring-tasks`, `fakt:due-reminders`,
   `fakt:retention` sort, és ha van, a régi `schedule:run` sort is.
2. Ebben a lépésben még **ne** add hozzá az új sort, az a 7. lépésben jön.

## 4. Karbantartási mód és cache törlése

cPanel → **Cron Jobs** → *Add New Cron Job* → **Once Per Minute**, az Email mező üres.
Parancs (egy sor):

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan down --retry=60 >> /cphome/nxt02408/fakt-deploy.log 2>&1 && /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan optimize:clear >> /cphome/nxt02408/fakt-deploy.log 2>&1 && /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan migrate:status >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

Várj egy percet, **töröld a cront**, és nézd meg a `/cphome/nxt02408/fakt-deploy.log` fájlt:

- a `down` után: „Application is now in maintenance mode.”,
- az `optimize:clear` minden sora `DONE`,
- a `migrate:status` táblázat végén **pontosan egy** `Pending` sor:
  `2026_10_06_000000_add_course_sessions_waivers_and_digest`.

Az oldal most „karbantartás” (503) oldalt mutat. Ez a várt állapot.

## 5. Telepítő adatbázis-user bekapcsolása

1. File Manager → `fakt-app-core/.env` → **Edit**.
2. Írd át ezt a két sort (a jelszót sehova ne másold ki, ne küldd el):

   ```text
   DB_USERNAME=nxt02408_faktdep
   DB_PASSWORD=<a faktdep jelszava>
   ```

3. **Save Changes**.

## 6. Migráció és a kurzusalkalmak legyártása

Ideiglenes **Once Per Minute** cron, egy futás után töröld:

```text
/usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan migrate --force >> /cphome/nxt02408/fakt-deploy.log 2>&1 && /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan fakt:backfill-course-sessions >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

A naplóban:
- a migráció sora `DONE`, `FAIL` vagy `SQLSTATE` nincs;
- a végén: „… kurzus, összesen … alkalom a naptárban.” (Ha még nincs kurzus, „0 kurzus”.)

> **Ha a migráció hibát ad:** ne próbálkozz újra, és ne futtass `migrate:rollback`-et. Állítsd
> vissza az 5. lépés `.env` sorait, és kövesd a 10. lépést. A naplót (jelszó nincs benne) küldd
> el a fejlesztőnek.

## 7. Vissza a futtató userre, cache, karbantartás vége, ütemező

1. `fakt-app-core/.env` → **Edit** → vissza:

   ```text
   DB_USERNAME=nxt02408_faktruntime
   DB_PASSWORD=<a faktruntime jelszava, ahogy a mentett .env-ben volt>
   ```

   A legegyszerűbb: nyisd meg a 0. lépésben letöltött `.env`-et, és onnan másold vissza a két sort.
2. Ideiglenes **Once Per Minute** cron, egy futás után töröld:

   ```text
   /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan optimize >> /cphome/nxt02408/fakt-deploy.log 2>&1 && /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan up >> /cphome/nxt02408/fakt-deploy.log 2>&1
   ```

   A naplóban az `optimize` sorai `DONE`, a végén „Application is now live.”
3. Most add hozzá a **végleges, egyetlen** FAKT cron-sort, **Once Per Minute**, üres Email mezővel:

   ```text
   /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan schedule:run >> /dev/null 2>&1
   ```

   Ez futtatja a levélküldést (queue), az emlékeztetőket, az ismétlődő feladatokat, az
   adatmegőrzést és a reggel 7:30-as napi összesítőt is.
4. cPanel → **MySQL Databases**: a `nxt02408_faktruntime` user továbbra is csak
   SELECT/INSERT/UPDATE/DELETE joggal legyen az adatbázishoz rendelve.

## 8. Ellenőrzés

### 8.1 Rendszerállapot (2 perc után)

Inkognitó ablak → `https://app.fakt.org.hu/login` → belépés az elnöki fiókkal →
bal oldalt **Rendszerállapot**. Minden sor zöld pipa legyen, különösen:

| Sor | Elvárt |
|---|---|
| PHP verzió | 8.3.x |
| Környezet / Debug mód | production / ki |
| Config cache | igen |
| Függő migrációk | nincs |
| Ütemező (cron) | utolsó futás 1–2 percen belül |
| Várakozó feladat / Sikertelen feladat | 0 / 0 (vagy pár perc alatt kiürül) |
| Utolsó hibák a naplóban | nincs `proc_open` sor |

Kattints a **Tesztlevél nekem** gombra: egy percen belül meg kell érkeznie a Gmail-fiókba.

### 8.2 A félév dátumainak rendbetétele (egyszer, most)

A jelenlegi félévet a telepítő „Induló félév” néven, 2026.09.30. – 2027.03.30. dátummal hozta
létre. Ez nem illik az új mandátumrendhez.

1. **Adminisztráció** → *Félévek és mandátumok* → az aktív félév sorában: név `2026 ősz`, kezdet
   `2026-07-01`, vég `2026-12-31` → **Mentés**.
2. *Új félév*: név `2027 tavasz`, kezdet `2027-01-01`, vég `2027-06-30` → **Félév létrehozása**.
   **Ne aktiváld!** Januárban aktiváld (elég az első tavaszi héten). Aktiváláskor az Elnök és az
   Alelnökök automatikusan átkerülnek, a Teamvezetőket újra ki kell nevezni.
3. Júniusban: hozd létre a `2027 ősz` félévet, a sorában **jelöld ki a következő Elnököt**, és csak
   utána aktiváld. Elnök nélkül az alkalmazás nem engedi az aktiválást.

### 8.3 Funkciók

1. **Szervezet**: a portfóliókon és Teameken látszanak a kinevezési űrlapok.
2. **Kurzusok** (Elnökként vagy KTSZT-tagként): hozz létre egy próbakurzust *Hetente, 6 alkalom*
   beállítással. Megjelenik „6 alkalom”. A **Naptár** havi nézetében (rács ikon) mind a 6 alkalom
   látszik. Próbáld ki az *Időpontszavazás* módot is két időponttal. Utána a próbakurzusokat
   nem kell törölni, de ne hagyd jelentkezésre nyitva.
3. **Naptár**: egy eseménynél a „Google Naptár” link előre kitöltött eseményt nyit. A „QR
   bejelentkezés” link a kezdés előtt 30 perccel kódot mutat.
4. **Beállítások → Profil**: az *Email-értesítések* résznél választható a napi összesítő.
5. Próbaregisztráció inkognitóban: a kérelem megjelenik az Admin → **Regisztrációk** alatt, és
   egy percen belül email érkezik az Elnöknek (ez mindig azonnali, nem összesített).
6. **Vezetői áttekintés**, **Tagnévsor**, **Felmentések** megnyílik hibaüzenet nélkül.

## 9. Takarítás

Csak ha a 8. lépés minden pontja rendben van, töröld:
- a `fakt-app-core-prev` és a `public_html/fakt-app-prev` mappát,
- a `fakt-release` mappát,
- a `fakt-deploy.log` fájlt,
- ha még megvan: a `fakt-president.log` fájlt (egyszer használatos jelszó van benne!) és a friss
  telepítésből maradt `-old` mappákat.

## 10. Visszaállítás

**A kód rossz, a migráció lefutott** (a gyakoribb eset):

1. Nevezd át az új mappákat (`fakt-app-core` → `fakt-app-core-bad`, `public_html/fakt-app` →
   `public_html/fakt-app-bad`), a `-prev` mappákat pedig vissza az eredeti nevükre.
2. Ellenőrizd, hogy az `.env`-ben a `nxt02408_faktruntime` user van.
3. Futtasd a 7. lépés 2. pontjának cronját (`optimize` + `up`).

Az új táblák és oszlopok bent maradnak, de a régi kód nem használja őket, ezért ez biztonságos.
A cron-sor (`schedule:run`) maradhat: a régi kód ütemezője is ezzel működik.

**A migráció félúton megállt** (MySQL-ben a DDL nem vonható vissza tranzakcióval):

1. Végezd el a fenti visszaállítást.
2. phpMyAdmin → `nxt02408_faktapp` → **Import** → a 0. lépésben mentett fájl. Ez a mentés óta
   készült adatokat felülírja, de a karbantartási mód miatt ilyen nincs.
3. Küldd el a `fakt-deploy.log` fájlt a fejlesztőnek, mielőtt törölnéd.
