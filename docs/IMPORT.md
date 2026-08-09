# Tagimport formátum

Az adminfelület CSV és egyszerű XLSX fájl első munkalapját fogadja, legfeljebb 10 MB méretig.

| Oszlop | Kötelező | Példa | Szabály |
|---|---:|---|---|
| `name` | igen | Minta Anna | nem üres |
| `email` | igen | anna@example.hu | érvényes és egyedi |
| `member_status` | igen | active | active, passive, senior vagy alumni |
| `cohort_year` | nem | 2025 | 2008–2100 |

Magyar fejléc-aliasok (`név`, `státusz`, `évfolyam`) is elfogadottak. A CSV lehet vessző- vagy pontosvessző-elválasztású, UTF-8 kódolás ajánlott.

Az első lépés csak staging rekordokat hoz létre: előnézet, soronkénti validáció, fájlon belüli és adatbázis-duplikáció keresése, valamint egyeztetési összesítés készül. Hibás batch nem alkalmazható. Alkalmazáskor a felhasználók és profilok tranzakcióban jönnek létre. A batch visszavonható; a már használt fiókok (`last_seen_at` kitöltve) biztonsági okból nem törlődnek.
