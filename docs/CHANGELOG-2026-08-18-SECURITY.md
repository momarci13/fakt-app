# Változásnapló – biztonsági megerősítés

Kiadás dátuma: 2026. augusztus 18.

## Alkalmazás

- központi hostile-input tripwire méret-, mélység-, UTF-8-, Unicode-, injekció- és útvonalbejárás-védelemmel;
- azonos bejelentkezési hiba ismeretlen, hibás, függő és elutasított fióknál;
- szigorúbb login, regisztráció, reset, feltöltés, módosítás és érzékeny művelet rate limit;
- production vezetői MFA, érzékeny műveletek jelszó-megerősítése, 60 perces titkosított session;
- jelszócsere/reset és elutasítás utáni session-visszavonás;
- exact trusted host és CSP/HSTS/no-store/anti-frame biztonsági fejlécek;
- dokumentum-, bizonyíték- és importfájlok tartalomalapú ellenőrzése, ZIP-bomba/makró/aktív PDF/képlet tiltása;
- aktív félév- és hatókörvizsgálat eseményeknél, feladatoknál, szervezetnél, kurzusnál, életútnál és mentorálásnál;
- audit titokmaszkolás és IP-pseudonimizálás; külön payloadmentes security log;
- Inertia felhasználói megosztás minimalizálva, ICS-token modellből rejtve.

## Kiadás és ellátási lánc

- GitHub Actions pontos commit SHA-ra rögzített actionökkel;
- Composer és npm audit, Dependabot, teljes regresszió és támadási tesztek;
- release ZIP integritás- és tartalomellenőrzés, külön SHA-256 fájl;
- szigorított public `.htaccess` és public bootstrap;
- production `.env`-et is ellenőrző Rackhost preflight.

## Üzemeltetési változás

- PHP 8.3 kötelező; a PHP 7.4-es példány közvetlenül nem frissíthető fájlráírással;
- runtime MySQL user csak `SELECT/INSERT/UPDATE/DELETE`, a migrációs user ideiglenes;
- vezetők első belépéskor TOTP-t állítanak be;
- a telepítés SHA-256 ellenőrzéssel, teljes mentéssel, staging mappákkal és visszaállítási kapuval történik;
- `.htaccess` 500 esetén a kiadás nem folytatható: logvizsgálat vagy rollback szükséges.

Adatbázis-séma migráció ebben a biztonsági kiadásban nincs; a korábbi függő migrációkat továbbra is `migrate --force` futtatja.
