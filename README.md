[comment]: <> (Ändere mich! - Readme)

# IServ Skeleton

Enthält das Grundpaket für Portal-Web-Module.

## Erste Schritte

1. Damit das Modul in der `dev`-Umgebung funktioniert, muss der Ordner `./app/var/` mit Schreibrechten angelegt werden.
2. `iservmake iservinstall` sollte dann den notwendigen Rest erledigen.

## Wie geht's weiter?

IServ bietet eine Reihe von Bundles und Bibliotheken an, auf die ein reguläres Web-Modul zurückgreifen kann:

* [AssetBundle](https://git.iserv.eu/iserv/lib/asset-bundle/-/blob/master/README.md) - Einfache Integration von `iserv-css` und `iserv-js`
* [AuthenticationBundle](https://git.iserv.eu/iserv/lib/authentication-bundle/-/blob/master/README.md) - Authentifizierung anhand des von Portal-Web ausgestellten JWTs.
* [BootstrapBundle](https://git.iserv.eu/iserv/lib/bootstrap-bundle/-/blob/master/README.md) - Einheitliche Darstellung mit dem IServ-Bootstrap-Theme
* [DoctrineBundle](https://git.iserv.eu/iserv/lib/doctrine-bundle/-/blob/master/README.md) - Zentrale Anpassungen für Doctrine-Nutzung
* [FormBundle](https://git.iserv.eu/iserv/lib/form-bundle/-/blob/master/README.md) - Zusätzliche Formulartypen und Javascript-Bibliotheken
* [ModuleBundle](https://git.iserv.eu/iserv/lib/module-bundle/-/blob/master/README.md) - Diverse grundlegende Werkzeuge für Module
* [TranslationGettextBundle](https://git.iserv.eu/iserv/lib/translation-gettext-bundle/-/blob/master/README.md) - Übersetzungen per Gettext
* [ModuleResponse Symfony Bridge](https://git.iserv.eu/iserv/lib/module-response-symfony-bridge/-/blob/master/README.md) - Modul-Antworten für Portal-Web als Symfony Response versenden

Weitere Bundles für spezielle Anforderungen:

* [AutocompleteBundle](https://git.iserv.eu/iserv/lib/autocomplete-bundle/-/blob/master/README.md) - Erweiterung für Autovervollständigung von Benutzern, Gruppen, E-Mail-Adressen
* [AppDetectorBundle](https://git.iserv.eu/iserv/lib/app-detector-bundle/-/blob/master/README.md) - Einfache Erkennung der IServ-App
* [FeatureBundle](https://git.iserv.eu/iserv/lib/feature-bundle/-/blob/master/README.md) - Steuerung von Funktionen per "Feature Flags"
* [NotificationBundle](https://git.iserv.eu/iserv/lib/notification-bundle/-/blob/master/README.md) - Versand von Benachrichtigungen per Symfony Messenger
* [CrudBundle](https://git.iserv.eu/iserv/lib/crud/-/blob/master/README.md) - CRUD-Bibliothek, wenn man ein CRUD nutzen möchte

Eine komplette Liste aller Bibliotheken kann in [Repman](https://repman.composer.iserv.eu/organization/iserv/package) oder [Gitlab](https://git.iserv.eu/iserv/lib) eingesehen werden.

## PHP-/Debian-Abhängigkeiten

Das Skeleton kommt mit einer Standardkonfiguration an PHP-Libs daher. Es wird keine Annahme über die installierten
PHP-Extensions getroffen. Für eine saubere Installation gilt also:

[comment]: <> (Ändere mich! - Composer)
1. `composer check-platform-reqs` zeigt welche Erweiterungen benötigt werden und welche installiert sind.
2. In der `composer.json` sind alle `ext-*` einzutragen, die der Modulcode selber benötigt.
3. In der `debian/control` sind alle `php-*`-Abhängigkeiten auf zusätzliche PHP-Debianpakete einzutragen. Bitte beachten, dass Erweiterungen wie z.B. `ext-json` aus dem PHP-Core kommen.
4. In der `composer.json` können abschließend Polyfills wie z.B. `symfony/polyfill-mbstring` unter `replaces` eingetragen werden.
5. Die Beschreibung in der `composer.json` sollte ebenfalls angepasst werden.

## Assets

Das Skeleton enthält eine beispielhafte Integration von CSS-, JS- und Bild-Assets und umfasst die komplette Unterstützung via Webpack (Less, Babel, Minifizierung, Manifest etc.).

Produktiv-Assets können mit `iservmake assets` im Wurzelverzeichnis des Moduls gebaut werden. Für die Entwicklung kann `npm run build:dev` oder `npm run watch` im `app`-Verzeichnis
genutzt werden.

## Übersetzungen per Gettext

Es sollte automatisch eine <new-module-name>.po Datei in pootle angelegt worden sein (Automagie, weil eine `messages.php` existiert).
Sowohl das Label in der `iserv-module.json` als auch der Eintrag in genau der `app/translations/messages.php` sind anzupassen.

## TODO

* Webpack-Tooling aktualisieren, wenn aktuelles Node verfügbar ist
