# Jogosultsági mátrix

| Művelet | Elnök | Alelnök | Teamvezető | Projektvezető | Tag | Alumni |
|---|---:|---:|---:|---:|---:|---:|
| Alelnök kinevezése/visszahívása | igen | nem | nem | nem | nem | nem |
| Teamvezető kinevezése | igen | saját portfólió | nem | nem | nem | nem |
| Teamtag kijelölése | igen | saját terület | saját Team | nem | nem | nem |
| Projekt kezelése | igen | saját terület | hatókör szerint | kijelölt projekt | nem | nem |
| Regisztráció jóváhagyása/elutasítása | igen | nem | nem | nem | nem | nem |
| Feladat delegálása | Alelnök/Projektvezető | saját Teamvezetők | saját Teamtagok | saját projekttagok | saját magának | saját magának |
| Kurzus elbírálása | igen | Szakmaiság | nem | nem | nem | nem |
| Személyes feladat/naptár | igen | igen | igen | igen | igen | alumni események |
| Életút-döntés és státusz | igen | nem | nem | nem | saját kérelem | nem |
| Alumni címtár/mentorálás | igen | igen | igen | igen | igen | igen |

Minden szerep és tagság kezdő-/záródátummal él. Az Elnök rendszeradminisztrátori joga kizárólag aktív `president` szerepkijelölésből származik. A közvetlen rekordazonosítós végpontok újra ellenőrzik a hatókört; a kliensoldali elrejtés önmagában nem jogosultsági védelem.

Az önregisztrált fiók `pending` állapotban indul. Ebben az állapotban nem léphet be a belső alkalmazásba, nem jelenhet meg kinevezhető vagy delegálható személyként, és a privát ICS-tokenje sem használható. Jóváhagyáskor a tagi profil `active`, elutasításkor `rejected` állapotot kap; a döntés indoklással és auditbejegyzéssel megmarad.
