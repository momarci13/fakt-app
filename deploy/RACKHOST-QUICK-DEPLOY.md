# Rackhost gyors telepítés

Ez a rövid útmutató az `app.fakt.org.hu` frissítéséhez készült. A publikus `fakt.org.hu` oldalt nem módosítja.

## 1. Csomag letöltése

1. GitHub → **Actions** → a legújabb sikeres **cPanel release package** futás.
2. Töltsd le a `fakt-cpanel-release` artifactot.
3. Csomagold ki az artifactot, majd a benne lévő `fakt-cpanel-release.zip` fájlt is.

## 2. Biztonsági mentés

cPanelben készíts mentést az adatbázisról, valamint ezekről:

- `/cphome/nxt02408/fakt-app-core/.env`
- `/cphome/nxt02408/fakt-app-core/storage/app/private`

## 3. Fájlok cseréje

1. A csomag `fakt-app-core` tartalmával frissítsd a `/cphome/nxt02408/fakt-app-core` mappát.
2. **Ne írd felül** a szerveren lévő `.env` fájlt és a `storage/app/private` tartalmát.
3. A `fakt-app-public` **tartalmával** frissítsd a `/cphome/nxt02408/public_html/fakt-app` mappát.
4. A rejtett `.htaccess` fájlt is másold át.

## 4. Frissítés futtatása

A cPanel **Cron Jobs** oldalán add hozzá ideiglenesen, **Once Per Minute** beállítással:

```text
/usr/local/bin/ea-php74 /cphome/nxt02408/fakt-app-core/artisan migrate --force >> /cphome/nxt02408/fakt-deploy.log 2>&1 && /usr/local/bin/ea-php74 /cphome/nxt02408/fakt-app-core/artisan optimize >> /cphome/nxt02408/fakt-deploy.log 2>&1
```

Várj egy percet, ellenőrizd a `fakt-deploy.log` végét, majd **azonnal töröld** ezt az ideiglenes cron sort.

## 5. Ellenőrzés

1. Nyisd meg inkognitó ablakban: `https://app.fakt.org.hu/login`.
2. A bejelentkezési felületnek meg kell jelennie; a konzolban nem lehet `component of null` hiba.
3. Maradjon meg az állandó, percenkénti scheduler cron:

```text
/usr/local/bin/ea-php74 /cphome/nxt02408/fakt-app-core/artisan schedule:run >> /dev/null 2>&1
```

Sikertelen frissítésnél állítsd vissza a mentett fájlokat és adatbázist. Soha ne futtasd productionben a `migrate:fresh` vagy `db:seed` parancsot.
