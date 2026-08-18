# Változásnapló – jóváhagyásos regisztráció és delegálási lánc

Kiadás dátuma: 2026. augusztus 18.

## Összefoglaló

Ez a kiadás a korábbi, csak meghívásos hozzáférést kétcsatornás beléptetéssé alakítja: az Elnök továbbra is hozhat létre közvetlenül jóváhagyott fiókot, de a FAKT-tagok saját maguk is regisztrálhatnak. Az önregisztrált fiók kizárólag elnöki jóváhagyás és email-ellenőrzés után használhatja a belső alkalmazást.

A feladatkiosztás többé nem általános „vezetői hatókör”, hanem explicit delegálási lánc:

1. Elnök → Alelnök vagy Projektvezető;
2. Alelnök → a saját portfólió Teamvezetője;
3. Teamvezető → a saját Team aktív tagja;
4. Projektvezető → a saját aktív projekt tagja;
5. minden jóváhagyott felhasználó → saját maga.

## Adatbázis-változások

Új migráció: `2026_08_18_000000_add_account_approval_to_users_table.php`.

Új `users` mezők:

- `approval_status`: `pending`, `approved` vagy `rejected`;
- `registration_note`: a jelentkező bemutatkozása;
- `approved_by`, `approved_at`: a jóváhagyó Elnök és időpont;
- `rejected_at`, `rejection_reason`: elutasítás időpontja és indoka.

Kompatibilitási döntés: minden, a migráció előtt létező fiók automatikusan `approved` alapértéket kap. Emiatt egy meglévő Elnök vagy tag sem veszti el a hozzáférését a frissítéskor.

## Regisztrációs folyamat

- A `/register` oldal publikus és mobilon is használható.
- Kötelező: név, email, legalább 20 karakteres bemutatkozás, megfelelő jelszó és adatkezelési nyilatkozat.
- Opcionális: évfolyam.
- A létrejött felhasználó és profil `pending` állapotú.
- Az aktív Elnök alkalmazáson belüli és email-értesítést kap.
- A függő fiók helyes jelszóval sem léphet be.
- Az Elnök az Adminisztráció oldalon jóváhagyhat vagy indoklással elutasíthat.
- A döntés email-értesítést és auditbejegyzést hoz létre.
- Jóváhagyás után az email-cím ellenőrzése továbbra is kötelező.
- A közvetlen elnöki meghívás továbbra is elérhető; az így létrehozott fiók automatikusan jóváhagyott.

## Szerveroldali hozzáférésvédelem

A `pending` és `rejected` fiókok nem csak a felületen vannak elrejtve. Az alkalmazás szerveroldalon is kizárja őket:

- a hitelesített belső útvonalakról és a beállításokból;
- a szervezeti kinevezhető személyek közül;
- Team- és projekttagság kijelöléséből;
- feladatfelelősök közül;
- jelenléti résztvevők közül;
- alumni/mentor listából;
- a tokenes privát ICS-feedből.

## Feladatdelegálás

Új központi szabályréteg: `App\Support\TaskDelegation`.

Ez állítja elő a felületen megjelenő felelőslistát és ugyanez ellenőrzi a mentéskor küldött azonosítókat. Egy manipulált HTTP-kérés ezért nem kerülheti meg a hierarchiát. A több szereppel rendelkező személy jogosultságai összeadódnak, de az Elnök delegálása szándékosan csak Alelnökökre és Projektvezetőkre korlátozott.

## Felület- és használhatósági fejlesztések

- új, háromlépéses regisztrációs képernyő;
- elnöki adminpanel a függő kérelmek számával és döntési felülettel;
- a feladatoldalon látható az aktuális delegálási szint és szabály;
- feladatkereső cím, leírás, felelős, Team, projekt és kiosztó alapján;
- lejárt és sürgős feladatmutatók;
- lejárt kártyák vizuális kiemelése;
- a kártyán megjelenik a feladat kiosztója;
- beépített hozzászóláslista és gyors hozzászólás-küldés.

## Biztonsági javítás

A `User` modell `password` mezője `hashed` castot kapott. Ez az összes írási útvonalon – regisztráció, jelszó-reset, elnöki bootstrap, import és beállítások – megakadályozza a nyers jelszó tárolását, és a már hash-elt értékeket nem hash-eli újra.

## Audit események

Új eseménynevek:

- `registration_submitted`;
- `registration_approved`;
- `registration_rejected`.

Az audit tartalmazza az érintett rekordot, a művelet előtti és utáni állapotot, a művelet idejét, valamint elnöki döntésnél az aktort és IP-címet.

## Üzemeltetési hatás

- Kötelező: `php artisan migrate --force`.
- Kötelező: `php artisan optimize:clear`, majd `php artisan optimize`.
- Nem kell új `APP_KEY`.
- Nem kell új adatbázis.
- Nem kell seedelés.
- Nem kell kézzel módosítani a meglévő felhasználókat.
- Az email- és adatbázis-queue működése szükséges az értesítésekhez; ezt a percenkénti scheduler kezeli.

## Ellenőrzési lista

- publikus `/register` oldal betölt;
- regisztráció után a fiók függő állapotban marad;
- függő fiók nem tud belépni;
- Elnök látja és elbírálja a kérelmet;
- jóváhagyott, emailben ellenőrzött fiók be tud lépni;
- Elnök nem delegálhat közvetlenül Teamtagnak;
- Alelnök csak saját portfólió Teamvezetőjének delegálhat;
- Teamvezető csak saját Teamtagjának delegálhat;
- Projektvezető csak saját projekttagjának delegálhat;
- jogosulatlan kézi rekordazonosító 403 választ kap;
- scheduler feldolgozza az email-értesítéseket.
