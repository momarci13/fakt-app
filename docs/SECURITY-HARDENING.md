# FAKT biztonsági megerősítés és üzemeltetési alapvonal

Kiadás: 2026. augusztus 18. Ez a dokumentum az alkalmazás biztonsági modelljét, az élesítés kötelező ellenőrzéseit és az incidenskezelés minimumát rögzíti. Nincs „feltörhetetlen” rendszer: a cél a rétegezett védelem, a legkisebb jogosultság, a gyors észlelés és a bizonyított visszaállíthatóság.

## Fenyegetési modell

Minden böngészőből, email-linkből, importfájlból, mellékletből, URL-paraméterből és rekordazonosítóból érkező adat megbízhatatlan. Kiemelt kockázatok:

- hitelesítőadat-próbálgatás, fiók- és jóváhagyási állapot felderítése;
- SQL/HTML/script/parancs injekció, útvonalbejárás és megtévesztő Unicode;
- jogosulatlan rekordazonosító-csere (IDOR) Team-, projekt-, esemény- és dokumentumhatárokon;
- veszélyes vagy álcázott fájl, Office ZIP-bomba, makró és aktív PDF;
- session-, CSRF-, host-header- és böngészőoldali támadás;
- túl széles adatbázis-jogosultság, titokszivárgás, hibás csomag vagy ellátási lánc;
- törlés, zsarolóvírus vagy hibás migráció utáni helyreállíthatatlanság.

## Beépített védelmi rétegek

### Hitelesítés

- email-cím normalizálás, Argon/bcrypt-kompatibilis Laravel hash és 15–128 karakteres erős jelszó;
- a hibás, függő és elutasított fiókok azonos hibaüzenetet kapnak;
- ismeretlen felhasználónál is jelszóhash-ellenőrzés csökkenti az időzítéses felderítést;
- belépés: 5/perc identitás+IP és 30/perc/IP; regisztráció és reset külön szigorú korláttal;
- tartós „remember me” cookie nincs; production session 60 perc, titkosított, Secure, HttpOnly és SameSite=Lax;
- jelszócsere/reset után a többi adatbázis-session és remember token érvénytelen;
- Elnök, Alelnök, Teamvezető és Projektvezető számára productionben kötelező a megerősített TOTP MFA;
- érzékeny admin-, szervezeti és tokenműveletekhez 15 percen belüli jelszó-megerősítés kell.

### Input, adatbázis és jogosultság

- minden kérésre központi mélység-, mezőszám-, méret-, UTF-8-, vezérlőkarakter- és támadási mintaellenőrzés fut;
- a `DROP/TRUNCATE/ALTER ... TABLE|DATABASE|SCHEMA`, UNION SELECT, SQL metaadat-/késleltető függvény, script, parancsvégrehajtás és path traversal kérés elutasított;
- a jelszavakat nem kulcsszólistával kezeljük: külön méret- és jelszó-szabályok védik őket;
- adatbázis-műveletek Eloquent/paraméterezett lekérdezéseket használnak, felhasználói SQL vagy táblanév nincs;
- a kliens által küldött azonosítót minden controller aktív félévhez és a felhasználó Team-/projekt-/vezetői hatóköréhez köti;
- a delegálás szerveroldalon Elnök → Alelnök/Projektvezető → Teamvezető → Teamtag láncot követ;
- a modellek import staging rekordjai explicit engedélyezett mezőlistát használnak.

Az inputminta-ellenőrzés kiegészítő tripwire; az elsődleges SQL-védelem a paraméterezés és az éles adatbázis-felhasználó DDL-jogainak megvonása. Így egy validációs hiba sem adhat `DROP`, `ALTER`, `CREATE`, `TRUNCATE`, `FILE` vagy `GRANT` jogot az alkalmazásnak.

### Feltöltés és letöltés

- legfeljebb 10 MB, engedélyezett kiterjesztés és szerveroldali MIME/tartalom-ellenőrzés;
- képstruktúra, PDF fejléc/aktív művelet és OOXML belső szerkezet ellenőrzése;
- Office makró, ActiveX, custom UI, útvonalbejárás, 2000 feletti bejegyzés és 50 MB feletti kibontott méret tiltott;
- CSV/XLSX importnál csak négy ismert oszlop, legfeljebb 1000 sor, képletinjektálás elleni `= + - @` védelem;
- véletlen tárolási név, privát — nem `public_html` alatti — tárhely és controlleren át történő jogosultság-ellenőrzött letöltés;
- a Laravel automatikus local-storage kiszolgáló route-ja tiltott, ezért aláírt URL-lel sem kerülhető meg a controller-jogosultság;
- letöltéskor `nosniff`, `no-store` és PDF/document sandbox jellegű válaszfejlécek.

### Web- és naplóvédelem

- exact production host allowlist, HTTPS/HSTS, CSP nonce, frame tiltás, MIME sniffing tiltás, szigorú referrer és Permissions Policy;
- Laravel CSRF token és login utáni session-regenerálás;
- a `.htaccess` tiltja a TRACE/TRACK metódust, korlátozza a request bodyt, és nem szolgál ki `.env`, Git, Composer, npm, Artisan vagy PHPUnit fájlt;
- auditnapló maszkolja a jelszó-, token-, titok-, ok-, szövegtörzs- és privát elérésiút-mezőket; IP-címet kulcsolt, nem visszafejthető ujjlenyomatként tárol;
- külön `security` log csak eseményosztályt és ujjlenyomatokat tárol, a támadó payloadot nem.

## Production adatbázis-jogok

Két külön cPanel MySQL felhasználó ajánlott:

1. `*_faktdeploy`: kizárólag a karbantartási ablakban, migrációhoz szükséges DDL+DML jogokkal;
2. `*_faktruntime`: folyamatos futáshoz csak `SELECT`, `INSERT`, `UPDATE`, `DELETE`.

Folyamat: mentés → `.env` ideiglenesen deploy user → `migrate --force` → `.env` runtime user → `optimize:clear && optimize` → deploy user eltávolítása az adatbázisból. Az alkalmazás runtime userének soha ne adj `ALL PRIVILEGES`, `DROP`, `ALTER`, `CREATE`, `INDEX`, `FILE`, `PROCESS`, `GRANT` vagy más adminjogot.

## Titkok és hozzáférések

- `.env`, `APP_KEY`, adatbázis-/SMTP-jelszó, MFA recovery code és ICS-token nem kerül Gitbe, ticketbe, screenshotba vagy telepítési naplóba;
- minden szolgáltatás külön, legalább 20 véletlen karakteres jelszót kap; személyváltás és incidens után rotálni kell;
- a cPanel és GitHub tulajdonosainak is kötelező MFA; megszűnt tisztségnél hozzáférés aznap visszavonandó;
- az `APP_KEY` frissítéskor változatlan. Kompromittálódásakor dokumentált incidensben kell rotálni, minden session/token visszavonásával.

## Kiadási és mentési kapu

Csak olyan GitHub Actions artifact telepíthető, amelynél a teszt és cPanel package workflow zöld. A letöltött belső ZIP SHA-256 értékét a mellékelt `.sha256` fájllal PowerShellben ellenőrizd:

```powershell
(Get-FileHash .\fakt-cpanel-release.zip -Algorithm SHA256).Hash.ToLower()
Get-Content .\fakt-cpanel-release.zip.sha256
```

Az értékek egyezése nélkül ne töltsd fel. Minden élesítés előtt külön MySQL-, `.env`-, privát fájl-, core- és public mentés kell; havonta dokumentált visszaállítási próba, napi titkosított mentés és 30 napos rotáció szükséges.

## Élesítés utáni biztonsági próba

- függő/elutasított/hibás fiók azonos üzenettel nem lép be; 6. gyors próbálkozás 429;
- vezető MFA nélkül csak a Biztonság oldalra jut; recovery code offline elmentve;
- `DROP TABLE users`, `<script>`, `UNION SELECT` és `../.env` tesztadat 422, valódi adat nem sérül;
- másik Team esemény-, feladat- és dokumentumazonosítója 403/404;
- álcázott PHP/PDF, aktív PDF és képletes CSV elutasított;
- válaszfejlécekben CSP, `nosniff`, DENY frame, no-referrer és HTTPS-en HSTS látható;
- `.env`, `artisan`, `composer.json`, `.git/config` URL-ről nem érhető el;
- runtime adatbázis-userrel `DROP TABLE` és `CREATE TABLE` phpMyAdminból/jóváhagyott kliensből megtagadott.

Ne valódi production táblán teszteld a DROP-tiltást: külön üres teszttáblán vagy a jogosultságlistában (`SHOW GRANTS`) igazold.

## Incidens esetén

1. Ne töröld a logokat; jegyezd fel UTC és Budapest idővel az észlelést.
2. Szükség esetén `artisan down`, majd cPanelben érintett session/DB/SMTP/GitHub hozzáférés visszavonása.
3. Készíts változatlan másolatot a logokról és adatbázisról; személyes adatot ne küldj nyilvános csatornára.
4. Ismert jó mentésből stagingben állíts vissza, rotáld a titkokat, majd ellenőrzött artifacttal élesíts.
5. Érintett személyes adat esetén a FAKT adatkezelője a vonatkozó incidensbejelentési kötelezettséget külön értékeli.
6. Dokumentáld a gyökérokot, érintett időszakot, intézkedést és megelőző kontrollt.

## Fennmaradó kockázat

A shared hosting korlátozza a WAF-, folyamatizolációs és valós idejű monitorozási lehetőségeket. A 250 fős használat működhet, de rendszeres sebezhetőségfrissítés, GitHub Dependabot, napi logellenőrzés, jogosultsági felülvizsgálat és mentési próba nélkül az alkalmazás nem tekinthető biztonságosan üzemeltetettnek.
