# Futtatókörnyezet és kiadásbiztonság

Az alkalmazás PHP 8.3-at, Laravel 13-at, Inertia 3-at és Symfony Mailert használ. A `composer.lock` rögzíti a kiadási csomagba kerülő pontos backend függőségeket; a GitHub Actions minden kiadásnál tiszta telepítést, frontend buildet és Composer auditot futtat.

Biztonsági alapelvek:

- productionben `APP_ENV=production` és `APP_DEBUG=false` kötelező;
- az `.env`, az `APP_KEY`, az adatbázis- és postafiókjelszavak soha nem kerülnek GitHubra vagy `public_html` alá;
- a privát fájlok a `storage/app/private` mappában maradnak, és csak jogosultság-ellenőrzött controlleren keresztül tölthetők le;
- az email és `*_email` mezőkben a rendszer elutasítja a CR/LF karaktereket;
- a szerveren kizárólag a sikeres `cPanel release package` artifact telepíthető;
- frissítés előtt adatbázis- és fájlmentés, utána migráció, cache-újraépítés és funkcionális ellenőrzés kötelező.

Ha a `composer audit` magas súlyosságú, az alkalmazást ténylegesen érintő hibát jelez, a kiadást meg kell állítani, a függőséget frissíteni és a teszteket újra futtatni.
