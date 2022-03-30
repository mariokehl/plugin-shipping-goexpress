# Versionshinweise für GO! Express

## v1.0.3 (30.03.2022)

### Geändert
- Die Standard-URLs der Webservice-Endpunkte in der Plugin-Konfiguration wurden angepasst
- In der Beschreibung gibt es nun einen Warnhinweis bezüglich Abholzeiten

### Behoben
- Das Plugin registriert nun einen eigenen Versanddienstleister, so dass der Assistent zur Konfiguration der Versandeinstellungen durchlaufen werden kann. Dies behebt die Fehlermeldung "Plugin does not have configuration for this shipping profile" im Versand-Prozess

### TODO
- Überprüfe die Konfiguration der Versanddienstleister und wähle unter **Aufträge » Versand » Optionen** in der Spalte Versanddienstleister statt Sonstiges den neuen Eintrag _**GO! Express Webservice**_  aus

## v1.0.2 (22.03.2022)

### Hinzugefügt
- Es ist nun möglich in den Versandeinstellungen in der Plugin-Konfiguration einen Abholhinweis zu hinterlegen (dieser erscheint auf dem Label)
- In den Versandeinstellungen gibt es eine neue Option "Samstagszustellung aktiv". Sobald diese Option ausgewählt ist, werden Anmeldungen am Freitag automatisch als Samstagszustellung durchgeführt

### Geändert
- Telefonnummer des Warenempfängers wird nun, sofern an der Liefer-/Rechnungsadresse vorhanden, als Ansprechpartner übertragen
- Ist am Auftrag eine externe Auftragsnummer gepflegt, so wird diese statt der Auftrags-ID übertragen und auf dem Label angedruckt

### Behoben
- Die Beschreibung des Plugins wurde verbessert (falsche Angabe zur Auswahl des Versanddienstleisters korrigiert; Tooltip in den Versendereinstellungen ergänzt)

## v1.0.1 (17.02.2022)

### Hinzugefügt
- Konfigurationsoption für Webservice-Endpunkte unter **Allgemein**

### Geändert
- Kleinere Anpassungen im Benutzerhandbuch gemäß Feedback

### TODO
- Plugin-Konfiguration aktualisieren und die Webservice-Endpunkte gemäß den von GO! übergebenen Zugangsdaten eintragen

## v1.0.0 (11.02.2022)

### Hinzugefügt
- Erstveröffentlichung
