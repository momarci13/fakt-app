# Jogosultsági mátrix

| Művelet | Elnök | Alelnök | Teamvezető | Projektvezető | KTSZT | Tag | Alumni |
|---|---|---|---|---|---|---|---|
| Alelnök kinevezése/visszahívása | igen | nem | nem | nem | nem | nem | nem |
| Teamvezető kinevezése | igen | saját portfólió | nem | nem | nem | nem | nem |
| Teamtag kijelölése | igen | saját terület | saját Team | nem | nem | nem | nem |
| **Projekt létrehozása** | **igen** | nem | nem | nem | nem | nem | nem |
| **Projektvezető kinevezése** | **igen** | nem | nem | nem | nem | nem | nem |
| **Projekttagok kezelése** | igen | nem | nem | **saját projekt** | nem | nem | nem |
| Regisztráció jóváhagyása/elutasítása | igen | nem | nem | nem | nem | nem | nem |
| Kurzus tervezése, tematika | igen | Szakmaiság | nem | nem | **igen** | nem | nem |
| Kurzusjelentkezés elbírálása, beosztás | igen | Szakmaiság | nem | nem | **igen** | nem | nem |
| Vitás kurzusteljesítés eldöntése | igen | Szakmaiság | nem | nem | **igen** | nem | nem |
| Plágiumügy eldöntése | igen | Szakmaiság | nem | nem | **igen** | nem | nem |
| **Választott Testületi tag kinevezése** | **igen** | nem | nem | nem | nem | nem | nem |
| Feladat delegálása | Alelnök/Projektvezető | saját Teamvezetők | saját Teamtagok | saját projekttagok | nem | saját magának | saját magának |
| Életút-döntés és státusz | igen | nem | nem | nem | nem | saját kérelem | nem |
| Alumni címtár/mentorálás | igen | igen | igen | igen | igen | igen | igen |

Minden szerep és tagság kezdő-/záródátummal él. Az Elnök rendszeradminisztrátori joga kizárólag aktív `president` szerepkijelölésből származik. A közvetlen rekordazonosítós végpontok újra ellenőrzik a hatókört; a kliensoldali elrejtés önmagában nem jogosultsági védelem.

Az önregisztrált fiók `pending` állapotban indul. Ebben az állapotban nem léphet be a belső alkalmazásba, nem jelenhet meg kinevezhető vagy delegálható személyként, és a privát ICS-tokenje sem használható. Jóváhagyáskor a tagi profil `active`, elutasításkor `rejected` állapotot kap; a döntés indoklással és auditbejegyzéssel megmarad.

## Kurzustervező és -szervező Testület (KTSZT)

A Testület legfeljebb öt tagból áll: két alanyi jogú és legfeljebb három választott tag (Testületi Határozat 2.1).

A **két alanyi jogú helyet az alkalmazás származtatja**, nem kinevezéssel tölti be: a Szakmaiságért felelős Alelnök és a Szakmaiság Teamvezető aktív szerepkijelöléséből. Ennek oka a Határozat 4.2 pontja: az a választott tag, aki később elnyeri valamelyik tisztséget, alanyi jogú taggá válik, és a választott mandátuma megszűnik. Kézzel kiosztott helyek esetén ez a szabály minden tisztségváltáskor csendben sérülne.

A **választott helyeket az Elnök tölti be** (`admin.ktszt.appoint`), legfeljebb hármat, jóváhagyott Szakkollégiumi Tag vagy Alumni Tag közül. A mandátum visszavonása `admin.ktszt.revoke`.

A Testületet a Szakmaiságért felelős Alelnök irányítja, hiányzása esetén a Szakmaiság Teamvezető (Határozat 3.3).

A Testület hatásköre a Határozat 1.2 pontjára korlátozódik. A Határozat 1.2.5 kifejezetten kimondja, hogy egyéb szervezeten belüli szakmai feladatokra nem terjed ki: kinevezés, jóváhagyás, pénzügy és tagsági döntés nem tartozik ide.

### Összeférhetetlenség

A Határozat 1.2.6 szerint a Testület tagja nem vesz részt olyan döntésben, amely a saját beadandóját, saját kurzusteljesítését vagy a vele szemben felmerült plágiumgyanút érinti.

Az alkalmazás ezt **szerkezetileg érvényesíti, nem emlékeztetéssel**: az érintett nem kapja meg a döntési végpontot arra a rekordra, amelynek ő az alanya. A szabály mindenkire vonatkozik, nem csak a Testületi tagokra — saját ügyében senki nem dönt. Társszerzős beadandó esetén minden társszerző kizáródik.

## Projekt és Projektvezető

Az SZMSZ hat Teamet ismer, a Vezetőség pedig 11 fős: 5 Elnökségi tag és 6 Teamvezető (SZMSZ 12.3). Egy hetedik Team ezt a létszámot és a hozzá kötött Vezetőségi Stratégia folyamatot is felborítaná.

Ezért a **Projekt önálló egység a hat Team mellett, nem azok között.** A `Projektvezető` az SZMSZ 8.4 szerint elismert tisztség, az SZMSZ 12.5 pedig felhatalmazza az Elnökséget legfeljebb egy évre szóló kiegészítő poszt létrehozására.

Minden projektbeli kinevezés dátumozott `role_assignment` sor `project_leader` vagy `project_member` szerepkörrel, a félévhez kötve. Így a Projekt tisztség helyesen beszámít abba a négy félévbe, amelyet a FAKT Diploma megkövetel, és az Elnökség félév végi `elfogadott` / `nem elfogadott` szavazásának van mihez kapcsolódnia.

A Projektben való tagság senkit nem tesz Teamtaggá, és nem változtatja meg a Vezetőség összetételét.
