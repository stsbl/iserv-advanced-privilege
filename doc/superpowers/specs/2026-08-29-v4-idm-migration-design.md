# v4- und IDM-Migration von stsbl-iserv-advanced-privilege

## Ziel

Das Paket `stsbl-iserv-advanced-privilege` wird von einem IServ-v3-Bundle zu einem
Portal-Web-v4-Modul auf Symfony 6.4 migriert. Die bestehende Massenbearbeitung von
Gruppen (Besitzer, Privileges und Group Flags) bleibt erhalten, verwendet aber
nur die IDM-v1-API und keine CoreBundle-Entitäten, -Repositorys, `GroupManager`
oder direkten User-Backend-Aufrufe.

## Struktur und Paketierung

`igit init` mit dem `portal-web-skeleton` liefert die v4-Grundstruktur. Der
Produktivcode liegt direkt unter `app/src` und wird per PSR-4 aus
`app/composer.json` auf den Modulnamespace abgebildet. Templates liegen unter
`app/templates`, Übersetzungen unter `app/translations` und Tests unter
`app/tests`. Der bisherige `modules/`-Baum einschließlich Bundle-, Extension- und
MenuListener-Klassen wird entfernt.

Paketname, Modul-URL und technische Kennungen bleiben lowercase:
`stsbl-iserv-advanced-privilege`, `stsbl/advanced-privilege` und `/iserv/stsbl/advanced-privilege/`. Die
Debian-Metadaten werden anhand des Skeletons erweitert, ohne den bisherigen
Paketnamen oder die fachliche Beschreibung zu verlieren. `iserv-module.json`
registriert das Modul; die Administrationseinbindung erfolgt mit der v4-Admin-
Integration statt mit einem Legacy-Menülistener.

## Authentisierung und Berechtigung

Die v4-Standardbundles übernehmen Portal-Authentisierung und die modulnative
Antwortintegration. Alle Modulrouten verlangen `AUTHENTICATED_AS_ADMIN`; damit
ist eine frische Administrator-Authentifizierung erforderlich. Bis IServ ein
dediziertes Recht für die Benutzerverwaltung bereitstellt, wird kein
modullokales Privilege eingeführt und `ROLE_ADMIN` nicht verwendet.

## IDM-Gateway

Ein fokussierter Service kapselt `IServ\Library\IdmApiClient\IdmClientInterface`.
Er ist die einzige Fachkomponente, die IDM-Endpunkte anspricht. Eine
requestgebundene IDM-Credentials-Implementierung bezieht den von
`iserv/authentication-bundle` entpackten SATA und übergibt ihn für jeden
IDM-Request als `X-IServ-Authentication`. IDM autorisiert und protokolliert
dadurch die tatsächlich handelnde Person statt eines Modul-Servicekontos. Für
Listen und Formoptionen lädt der Gateway mit `_attributes` nur die nötigen
Daten:

- Gruppen mit UUID, Name, Besitzer und Tenant;
- aktive Benutzer mit UUID und Anzeigename;
- Privileges und Group Flags mit UUID und Anzeigename.

Die Zielauswahl filtert Gruppen clientseitig im Gateway anhand des bisherigen
Semantikvertrags: alle, Präfix, Suffix, enthält oder regulärer Ausdruck. Der
reguläre Ausdruck wird vor dem Abruf validiert.

Für jede gefundene Gruppe führt das Gateway die v1-Operationen aus:

- Besitzer: `PATCH /iserv/idm/api/v1/groups/{uuid}` mit einer Benutzer-IRI oder
  `null`;
- Privileges hinzufügen: `POST .../groups/{uuid}/privileges` mit
  `addedPrivileges`; entfernen: `DELETE .../privileges/{privilegeUuid}`;
- Group Flags hinzufügen: `POST .../groups/{uuid}/group_flags` mit
  `addedFlags`; entfernen: `DELETE .../group_flags/{groupFlagUuid}`.

IDM-Requestfehler werden auf eine modulinterne Ausnahme abgebildet, geloggt und
als sichere, verständliche Formularrückmeldung ausgegeben. Bereits entfernte
Zuordnungen werden nicht als Erfolg gezählt. Es gibt keine Fallbacks zum
User-Backend.

## HTTP- und UI-Verhalten

Die bestehende Adminseite bleibt als eine Seite mit drei Formularen erhalten:
Besitzer setzen, Privileges/Flags zuweisen und Privileges/Flags entziehen. Die
Routen werden als Symfony-Attribute relativ zum Modul registriert. Die
POST-Antwort enthält die bisherigen kategorisierten Meldungen als JSON, damit
bestehende Bestätigungs- und Ergebnis-Interaktion erhalten bleibt.

Das Admin-Menü erhält ein neues, selbst enthaltenes SVG unter `app/assets/img`.
Es wird im Stil bestehender IServ-Modulicons als schlichtes, einfarbiges
Schlüssel-/Gruppen-Symbol erstellt und über `iserv-module.json` eingebunden.

Die Formmodelle enthalten UUID-Listen statt Doctrine-Entity-Collections. Das
Besitzerfeld wird als einzelne Benutzerauswahl über einen eigenen God-Mode-
Autocomplete-Endpunkt realisiert. Dieser nutzt `iserv/autocomplete-bundle`,
fragt aktive IDM-Benutzer anhand von UUID, Benutzername und Namen ab und löst
bereits ausgewählte UUID-Werte erneut auf. Der Endpunkt und das Formular sind
wie die übrige Verwaltung nur mit `AUTHENTICATED_AS_ADMIN` erreichbar. Weitere
Form-Choice-Provider erzeugen die sichtbaren Auswahlmöglichkeiten aus IDM-Daten.

## Tests und Abnahme

Unit-Tests prüfen die Zielgruppenauswahl, reguläre-Ausdruck-Validierung sowie die
IDM-Requestbildung für Besitzer, Privileges und Flags einschließlich Fehlern.
Funktionelle Tests decken außerdem Suche und Werteauflösung des God-Mode-
Autocomplete-Endpunkts sowie dessen Administrator-Schutz ab.
Funktionelle Tests prüfen den Zugriffs- und Formularfluss mit einem Fake-
`IdmClientInterface`. Vor dem MR werden `iservmake lint tests`,
`iservmake iservinstall` und `iservchk` ausgeführt; falls die VM verfügbar ist,
folgt eine Browser-/e2e-Prüfung der Adminroute. CI wird nach Push geprüft und
fehlende Abhängigkeiten oder Toolfehler werden bis zum grünen MR behoben.
