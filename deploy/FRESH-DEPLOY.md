# Tiszta újratelepítés Rackhost cPanelen

Ez az útmutató a régi telepítés teljes eltakarításához és az áttervezett verzió nulláról történő
telepítéséhez készült. Az adatbázis tartalma **nem marad meg**: a séma migrációkból épül újra.

A szerver jelenlegi elrendezése:

```
/cphome/nxt02408/fakt-app-core            Laravel core
/cphome/nxt02408/public_html/fakt-app     dokumentumgyökér (app.fakt.org.hu)
```

---

## 0. Mentés — mielőtt bármit törölnél

Ezt akkor is végezd el, ha biztos vagy benne, hogy nincs megőrzendő adat.

1. cPanel → **Backup** → *Download a MySQL Database Backup* → `nxt02408_faktapp`.
2. Töltsd le a `/cphome/nxt02408/fakt-app-core/.env` fájlt. **Ebben van az `APP_KEY`.**
3. Töltsd le a `/cphome/nxt02408/fakt-app-core/storage/app/private` mappát, ha van benne feltöltött
   bizonyíték. Az adatbázis törlése után ezek a fájlok gazdátlanná válnak.

A letöltött mentést tartsd meg legalább a sikeres élesítés utáni két hétig.

---

## 1. Mit törölj

### 1.1 Mappák

| Útvonal | Művelet | Miért |
|---|---|---|
| `/cphome/nxt02408/fakt-app-core-backup` | törlés | A augusztusi frissítés maradéka |
| `/cphome/nxt02408/public_html/fakt-app-backup` | törlés | Ugyanaz, publikus oldal |
| `/cphome/nxt02408/fakt-app-core-next` | törlés, ha létezik | Félbehagyott staging |
| `/cphome/nxt02408/fakt-app-public-next` | törlés, ha létezik | Félbehagyott staging |
| `/cphome/nxt02408/fakt-app-core` | **átnevezés** `fakt-app-core-old` névre | Csak a sikeres élesítés után töröld |
| `/cphome/nxt02408/public_html/fakt-app` | **átnevezés** `fakt-app-old` névre | Ugyanaz |

Előbb átnevezés, törlés csak a 6. szakasz ellenőrzései után. Ez az egyetlen visszaútad.

### 1.2 Naplófájlok

| Útvonal | Miért |
|---|---|
| `/cphome/nxt02408/fakt-deploy.log` | Telepítési napló, nincs rá szükség |
| `/cphome/nxt02408/fakt-president.log` | **Tartalmazza az elnöki egyszer használatos jelszót.** Kötelező törölni |
| `/cphome/nxt02408/fakt-diagnose.log` | Diagnosztikai kimenet |
| `/cphome/nxt02408/fakt-scheduler-test.log` | Scheduler teszt |
| `fakt-app-core-old/storage/logs/*.log` | A régi mappával együtt megy |

### 1.3 Cron feladatok

cPanel → **Cron Jobs**: töröld **az összes** FAKT-hoz tartozó sort, beleértve a régi
`ea-php74` hivatkozásúakat is. A telepítés végén pontosan egy új sort hozol létre.

### 1.4 Amit a csomag automatikusan hoz, nem kell kézzel

`vendor`, `bootstrap/cache`, `public/build`, `node_modules` — ezek a régi mappával együtt
törlődnek, és az új kiadási csomagban benne vannak. Régiből **soha ne másolj át** ilyet.

---

## 2. Amit NEM szabad törölni

| Útvonal | Miért |
|---|---|
| A letöltött `.env` másolat | Az `APP_KEY` és a DB, SMTP hitelesítő adatok |
| Az SSL tanúsítvány és a domain beállítás | Nem érinti az újratelepítést |
| `nxt02408_faktdeploy` és `nxt02408_faktruntime` MySQL user | A 3. szakaszban mindkettőre szükség van |

Az `APP_KEY` megtartása a legegyszerűbb út. Ha a mentés elveszett, most kivételesen biztonságos új
kulcsot generálni, mert a teljes törlés után nem marad titkosított adat, amit olvasni kellene.
Minden más esetben továbbra is tilos a `key:generate`.

---

## 3. Adatbázis: törlés és újraépítés

A `migrate:fresh` eldobja a táblákat, ehhez `DROP` jog kell. A `nxt02408_faktruntime` usernek
szándékosan nincs, ezért a művelet idejére a deploy userre kell váltani.

1. cPanel → **MySQL Databases** → győződj meg róla, hogy `nxt02408_faktdeploy` **All Privileges**
   joggal rá van kötve a `nxt02408_faktapp` adatbázisra.
2. Az új core `.env` fájljában ideiglenesen:
   ```
   DB_USERNAME=nxt02408_faktdeploy
   DB_PASSWORD=<a deploy user jelszava>
   ```
3. Az 5. szakasz migrációs lépése után állítsd vissza:
   ```
   DB_USERNAME=nxt02408_faktruntime
   DB_PASSWORD=<a runtime user jelszava>
   ```
4. Ezután a **MySQL Databases** oldalon vedd le a deploy usert az adatbázisról.

Alternatíva, ha tiszta lapot akarsz: töröld az egész `nxt02408_faktapp` adatbázist, hozd létre újra
ugyanazzal a névvel, és kösd rá mindkét usert a fenti jogokkal. Ekkor a `migrate:fresh` helyett a
`migrate --force` is elég.

---

## 4. Az új `.env`

A `deploy/.env.cpanel.example` alapján, a régi `.env` értékeivel kitöltve. Ami **változik** az
áttervezett verzióban:

```
# Ez a sor törlendő, a kétlépcsős azonosítás megszűnt:
# SECURITY_REQUIRE_PRIVILEGED_MFA=true
```

Minden más marad: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://app.fakt.org.hu`,
`APP_TRUSTED_HOST=app.fakt.org.hu`, `SESSION_DRIVER=database`, `CACHE_STORE=database`,
`QUEUE_CONNECTION=database`, `BCRYPT_ROUNDS=13`.

---

## 5. Telepítés

1. **Csomag letöltése.** GitHub → Actions → *cPanel release package* → csak zöld futás artifactja.
   Ellenőrizd a SHA-256 értéket.
2. **Feltöltés.** `fakt-app-core` → `/cphome/nxt02408/fakt-app-core`,
   `fakt-app-public` → `/cphome/nxt02408/public_html/fakt-app`.
3. **`.env` bemásolása** a 4. szakasz szerint, a deploy DB userrel.
4. **Jogosultságok.** `storage` és `bootstrap/cache` rekurzívan `0755`, szükség esetén `0775`.
   Soha nem `0777`.
5. **PHP kezelő ellenőrzése.** Nyisd meg a `/cphome/nxt02408/public_html/fakt-app/.htaccess`
   fájlt, és győződj meg róla, hogy a legelején ott van:
   ```apache
   AddHandler application/x-httpd-ea-php83 .php .php8 .phtml
   ```
   A kiadási csomag ezt magával hozza. Ha hiányzik, a `public_html/.htaccess` örökölt `ea-php74`
   blokkja fog érvényesülni, és minden kérés elhasal. Részletek: `WEBSITE-500-DIAGNOSIS.md`.
6. **Preflight.** Ideiglenes percenkénti cron, egy futás után töröld:
   ```text
   /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/deploy/rackhost-preflight.php >> /cphome/nxt02408/fakt-deploy.log 2>&1
   ```
   Csak `[OK]` eredménnyel folytasd.
7. **Séma felépítése.** Ideiglenes percenkénti cron, egy futás után töröld:
   ```text
   /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan migrate:fresh --force >> /cphome/nxt02408/fakt-deploy.log 2>&1
   ```
   A naplóban nem lehet `ERROR`, `SQLSTATE` vagy jogosultsági hiba.
8. **Váltás runtime DB userre** a 3.3 és 3.4 lépés szerint.
9. **Elnöki fiók.** Ideiglenes percenkénti cron, egy futás után töröld. Most a **valódi** email-címet
   és nevet írd be, ne a mintaértéket:
   ```text
   /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan fakt:bootstrap-president valodi.cim@fakt.org.hu --name="Valódi Teljes Név" >> /cphome/nxt02408/fakt-president.log 2>&1
   ```
   A parancs egyszer használatos jelszót ír a naplóba. Belépés és jelszócsere után **töröld a
   `fakt-president.log` fájlt.**
10. **Production cache.** Ideiglenes percenkénti cron, egy futás után töröld:
    ```text
    /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan optimize:clear >> /cphome/nxt02408/fakt-deploy.log 2>&1 && /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan optimize >> /cphome/nxt02408/fakt-deploy.log 2>&1
    ```
11. **Állandó scheduler.** Pontosan egy sor, **Once Per Minute**:
    ```text
    /usr/local/bin/ea-php83 /cphome/nxt02408/fakt-app-core/artisan schedule:run >> /dev/null 2>&1
    ```
    A cron űrlap Email mezőjét hagyd üresen, különben napi 1440 levelet kapsz.

---

## 6. Ellenőrzés

1. Inkognitó ablak: `https://app.fakt.org.hu/login` — a bejelentkező oldal töltsön be.
   Ha PHP verzióról szóló üzenet jön, az 5.6 lépés a hibás.
2. Belépés az elnöki fiókkal. **Nem** kell kétlépcsős azonosítást beállítani — a funkció megszűnt.
3. `https://app.fakt.org.hu/robots.txt` → HTTP 200. Ha ez hibázik, a hiba Apache-szintű.
4. Nézd meg, hogy üres-e:
   `/cphome/nxt02408/fakt-app-core/storage/logs/bootstrap-error.log`
5. Várj egy percet, majd ellenőrizd, hogy a scheduler lefutott és nem hagyott hibát a
   `storage/logs/laravel-*.log` fájlban.

Csak ha mind az öt rendben van, töröld a `fakt-app-core-old` és a `public_html/fakt-app-old`
mappát, valamint a régi adatbázis-mentést a megőrzési idő letelte után.

---

## 7. Visszaállítás, ha valami elromlik

Nevezd vissza a két `-old` mappát az eredeti nevükre, töltsd vissza az adatbázis-mentést
phpMyAdmin → Import segítségével, és állítsd vissza a régi `.env` fájlt. Ezért nevezel át
a törlés helyett az 1.1 pontban.
