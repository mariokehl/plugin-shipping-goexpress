# Versionshinweise für GO! Express

## v1.0.7 (23.06.2022)

### Geändert
- Die Angabe "Minimales Gewicht (g)" in der Plugin-Konfiguration ist nun nicht mehr verpflichtend. Ist dort kein Zahl eingetragen, wird automatisch ein Minimum in Höhe von 200g übermittelt.

### Behoben
- Indikator hinsichtlich PHP 8 Kompatibilität nach Prüfung des Quellcodes gesetzt

## v1.0.6 (03.06.2022)

### Hinzugefügt
- Neue Option "Absender-Daten aus Lager holen" unter **Fortgeschritten**, so dass über eine Lager-Konfiguration individuelle Absender-/Abholadressen pro Lager übermittelt werden. Dazu müssen die Adressdaten der Lager unter **Einstellungen » Waren » Lager** gepflegt sein und eine korrekte Lager-Konfiguration eingetragen sein
- In der Plugin-Konfiguration wurde eine Auswahloption des PDF-Labeldruckers unter **Fortgeschritten** ergänzt: Standard, Citizen und Zebra
- Insofern das Versandpaket keine Gewichtsangabe hat, kann über die Option "Minimales Gewicht (g)" unter **Versand** ein Standard-Gewicht hinterlegt werden
- Neue Möglichkeit einen Zustellhinweis pro Paket im Bereich **Versand** zu konfigurieren (überschreibt den zuvor eingestellten Zustellhinweis pro Auftrag!)
- Option "Telefonnummer zusätzlich im Feld Abteilung übertragen" im Bereich **Versand** um die Telefonnummer des Kunden auf dem Versandetikett sichtbar zu machen

### Geändert
- Die Ermittlung der Landesvorwahl zur Weitergabe der Telefonnummer an GO! wurde hinsichtlich mehrerer europäischer Lieferländer erweitert
- Wir haben aufgeräumt und viel zu viel Kaffee getrunken

## v1.0.5 (06.05.2022)

### Geändert
- In der Beschreibung wurden die Querverweise in Richtung plentymarkets Handbuch angepasst

## v1.0.4 (06.04.2022)

### Hinzugefügt
- Neue Konfiguration "Vorlaufzeit": alle Sendungen nach X Minuten vor Beginn der Abholzeit werden auf den nächsten Werktag verschoben
- Es ist nun über eine Auswahl in der Konfiguration möglich zu bestimmen, welche Nummer als Kundenreferenz übertragen wird: Auftrags-ID, Ext. Auftragsnummer oder beide

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
- Es ist nun möglich in den Versandeinstellungen in der Plugin-Konfiguration einen "Abholhinweis" zu hinterlegen (dieser erscheint auf dem Label)
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
