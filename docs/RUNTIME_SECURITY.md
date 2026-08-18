# Futtatókörnyezet és kiadásbiztonság

Az alkalmazás PHP 8.3-at, Laravel 13-at, Inertia 3-at és Symfony Mailert használ. A `composer.lock` és `package-lock.json` rögzíti a kiadási függőségeket; a GitHub Actions minden kiadásnál tiszta telepítést, teljes tesztet, frontend buildet, Composer/npm auditot és ZIP-integritásvizsgálatot futtat.

Biztonsági alapelvek:

- productionben `APP_ENV=production` és `APP_DEBUG=false` kötelező;
- az `.env`, az `APP_KEY`, az adatbázis- és postafiókjelszavak soha nem kerülnek GitHubra vagy `public_html` alá;
- a privát fájlok a `storage/app/private` mappában maradnak, és csak jogosultság-ellenőrzött controlleren keresztül tölthetők le;
- minden kérésen központi méret-, mélység-, UTF-8-, vezérlőkarakter- és ismert hostile-input tripwire fut, a mezőspecifikus allowlist validáció mellett;
- a runtime adatbázis-felhasználó csak `SELECT`, `INSERT`, `UPDATE`, `DELETE` jogot kap; a migrációs user ideiglenes;
- productionben a vezetői TOTP MFA, titkosított Secure session, trusted host és biztonsági válaszfejlécek kötelezők;
- a feltöltések szerveroldali MIME-, szerkezet-, aktívtartalom- és archívumméret-ellenőrzést kapnak;
- a szerveren kizárólag a sikeres `cPanel release package` artifact telepíthető;
- frissítés előtt adatbázis- és fájlmentés, utána migráció, cache-újraépítés és funkcionális ellenőrzés kötelező.

Ha a `composer audit` vagy `npm audit --audit-level=high` hibát jelez, a kiadást meg kell állítani, a függőséget frissíteni és a teszteket újra futtatni. A teljes kontroll- és incidenskezelési leírás: [SECURITY-HARDENING.md](SECURITY-HARDENING.md).
