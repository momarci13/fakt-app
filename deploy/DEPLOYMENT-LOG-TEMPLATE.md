# FAKT Rackhost telepítési napló – kitöltendő sablon

Ezt a fájlt másold le minden éles telepítés előtt, és töltsd ki. Jelszót, `APP_KEY` értéket, ICS-tokent vagy más titkot soha ne írj bele.

## Kiadás

- Dátum és idő:
- Végrehajtó:
- Git commit SHA:
- GitHub Actions futás URL-je:
- Artifact neve:
- Artifact SHA-256:
- SHA-256 egyezés ellenőrizve: igen / nem
- Telepítési mód: első telepítés / meglévő rendszer frissítése
- Tervezett karbantartási ablak:

## Telepítés előtti állapot

- [ ] GitHub `tests` workflow sikeres
- [ ] GitHub `cPanel release package` workflow sikeres
- [ ] MySQL export elkészült
- [ ] `.env` külön mentése elkészült
- [ ] `storage/app/private` mentése elkészült
- [ ] régi core és public mappa mentése elkészült
- [ ] szabad tárhely ellenőrizve
- [ ] PHP 8.3 elérhető az `app.fakt.org.hu` domainhez
- [ ] PHP 7.4 visszaállítási lehetőség és régi cron feljegyezve
- [ ] külön deploy és runtime MySQL user létrehozva
- [ ] runtime usernek csak SELECT/INSERT/UPDATE/DELETE joga van
- [ ] cPanel/GitHub végrehajtó MFA-ja aktív
- Mentések pontos helye:
- Jelenlegi alkalmazás verziója/commitja:

## Staging ellenőrzés

- Új core útvonal:
- Új public útvonal:
- Preflight futás ideje:
- Preflight eredménye:
- `.env` ellenőrző személy:
- `APP_KEY` változatlan: igen / nem alkalmazható
- production host/session/MFA környezeti értékek ellenőrizve: igen / nem
- Privát fájlok átmásolva: igen / nem alkalmazható

## Élesítés

- Maintenance mód kezdete:
- Régi scheduler leállítva:
- Mappacsere ideje:
- `migrate --force` eredménye:
- Deploy DB user eltávolítva az adatbázisról:
- Runtime DB user beállítva és DDL-tiltás igazolva:
- `optimize:clear` eredménye:
- `optimize` eredménye:
- `up` ideje:
- Új scheduler létrehozva:

## Funkcionális ellenőrzés

- [ ] `/login` betölt
- [ ] `/register` betölt
- [ ] elnöki belépés sikeres
- [ ] elnöki TOTP MFA megerősítve, recovery kód offline mentve
- [ ] függő regisztráció létrejön
- [ ] függő regisztráció nem tud belépni
- [ ] elnöki jóváhagyás működik
- [ ] email-értesítés megérkezik
- [ ] delegálási lánc felelősei helyesek
- [ ] jogosulatlan delegálás blokkolt
- [ ] feladatkomment működik
- [ ] ICS feed működik
- [ ] védett fájl letölthető jogosultként
- [ ] böngészőkonzol hibamentes
- [ ] Laravel logban nincs új ERROR
- [ ] CSP, nosniff, frame deny, no-referrer és HSTS fejlécek jelen vannak
- [ ] `.env`, `artisan`, `composer.json`, `.git/config` nem publikus
- [ ] romboló SQL/script/path traversal tesztadat 422 választ kapott
- [ ] más Team/projekt rekordazonosítója 403/404 választ kapott
- [ ] álcázott vagy aktív feltöltés elutasított
- [ ] runtime MySQL user nem rendelkezik DROP/ALTER/CREATE/FILE/GRANT joggal
- [ ] security log nem tartalmaz teszt payloadot vagy titkot
- [ ] sikeres állapot utáni új mentés elkészült

## Eredmény

- Telepítés vége:
- Eredmény: sikeres / visszaállítva / részben sikeres
- Észlelt probléma:
- Elvégzett korrekció:
- Következő ellenőrzés időpontja:
- Backup mappák tervezett törlése:

## Visszaállítás – csak ha szükséges

- Visszaállítás oka:
- Adatbázis visszaállítás ideje:
- Core/public visszaállítás ideje:
- Scheduler visszaállítva:
- Szolgáltatás újra elérhető:
- Utólagos hibaelemzés felelőse:
