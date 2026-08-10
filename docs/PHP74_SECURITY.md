# PHP 7.4 kompatibilitási ág – biztonsági megjegyzések

A jelenlegi Rackhost csomag PHP 7.4-re korlátozza az alkalmazást, ezért ez az ág Laravel 8.83-at használ. Mindkettő támogatási ideje lejárt; a célállapot támogatott PHP 8.3+ és aktuális Laravel verzió.

A Composer audit jelenleg három `laravel/framework` advisoryt jelez:

- a wildcard fájlvalidációs megkerülés nem érinti az appot, mert minden feltöltés egyedi `file` vagy `evidence` mezőn, nem `files.*` wildcarddal történik;
- a local-disk temporary signed URL probléma nem érinti az appot, mert a privát fájlok saját jogosultság-ellenőrzött controlleren keresztül tölthetők le;
- az email CR/LF problémára az app globálisan elutasítja a sortörést tartalmazó `email` és `*_email` bemeneteket. A Laravel 8 ezen felül SwiftMailert, nem az advisoryban leírt Symfony Mailer/Mime kombinációt használja.

Ezek alkalmazásszintű kockázatcsökkentések, nem helyettesítik a támogatott platformra történő frissítést. A Composer telepítés ezért csak tudatosan, a `--no-blocking` kapcsolóval fut; az audit eredményét minden kiadásnál át kell nézni.
