# Erweiterte Rechte-Verwaltung für IServ

Dieses Modul ergänzt die IServ-Verwaltung um eine Bearbeitung von Rechten,
Gruppenmerkmalen und Gruppenbesitzern für mehrere Gruppen gleichzeitig.

Die Gruppen können anhand ihres Namens ausgewählt werden:

- alle Gruppen,
- Name beginnt mit / endet mit / enthält einen Wert,
- regulärer Ausdruck.

Die Bearbeitung erfolgt über die IDM-API im Namen des angemeldeten Administrators.
Daher ist der Zugriff auf das Modul ausschließlich mit einer
Administrator-Authentifizierung möglich.

## Entwicklung

Abhängigkeiten installieren und die Prüfungen ausführen:

```sh
iservmake run_tools
```

Für lokale Portal-Web-Installationen richtet anschließend
`iservmake iservinstall` die benötigten Dateien ein.

## Architektur

- Portal-Web-Modul-ID: `stsbl/advanced-privilege`
- Modulpfad: `/iserv/stsbl/advanced-privilege/`
- PHP-Quelltext: `app/src` (PSR-4)
- IDM-Anfragen übernehmen den von authentication-bundle entpackten SATA.
- Die Besitzer-Auswahl nutzt einen eigenen administrativen IDM-Autocomplete-Endpunkt.
