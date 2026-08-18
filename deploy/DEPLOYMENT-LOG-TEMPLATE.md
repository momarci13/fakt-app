# FAKT Rackhost telepítési napló – kitöltendő sablon

Ezt a fájlt másold le minden éles telepítés előtt, és töltsd ki. Jelszót, `APP_KEY` értéket, ICS-tokent vagy más titkot soha ne írj bele.

## Kiadás

- Dátum és idő:
- Végrehajtó:
- Git commit SHA:
- GitHub Actions futás URL-je:
- Artifact neve:
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
- Mentések pontos helye:
- Jelenlegi alkalmazás verziója/commitja:

## Staging ellenőrzés

- Új core útvonal:
- Új public útvonal:
- Preflight futás ideje:
- Preflight eredménye:
- `.env` ellenőrző személy:
- `APP_KEY` változatlan: igen / nem alkalmazható
- Privát fájlok átmásolva: igen / nem alkalmazható

## Élesítés

- Maintenance mód kezdete:
- Régi scheduler leállítva:
- Mappacsere ideje:
- `migrate --force` eredménye:
- `optimize:clear` eredménye:
- `optimize` eredménye:
- `up` ideje:
- Új scheduler létrehozva:

## Funkcionális ellenőrzés

- [ ] `/login` betölt
- [ ] `/register` betölt
- [ ] elnöki belépés sikeres
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
