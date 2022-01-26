# Was ist GO! Express? Wofür?

GO! Express & Logistics ist der größte konzernunabhängige Anbieter von Express- und Kurierdienstleistungen. Ein Schwerpunkt liegt traditionell auf der Zustellung über Nacht.

Ein Kurierdienst eignet sich hervorragend für zeitkritische oder hochwertige Warensendungen. Klassischer Anwendungsfall ist z.B. der Versand von tiefkühlpflichtigen Lebensmitteln über Nacht, so dass die Kühlkette nicht unterbrochen wird.

Verwende dieses Plugin, um GO! in deinem plentymarkets System zu integrieren. Danach ist es möglich, den bekannten Arbeitsschritt der Versandauftragsanmeldung im Versand-Center sowie im plentymarkets Client durchzuführen. 

## Quickstart

Um dieses Plugin zu benutzen, musst du als Versender bei GO! registriert sein. Du erhälst danach Benutzername und Passwort zur Konfiguration des Plugins.

**Nutze für deine Registrierung bei GO! Express einen der folgenden Wege:**

- Telefon: 0800 / 859 99 99
- [E-Mail](mailto:info@general-overnight.com)
- [Kontaktformular](https://www.general-overnight.com/deu_de/online-services/kontakt.html)

Bitte erwähne bei deiner Kontaktaufnahme, dass du das plentymarkets Plugin für GO! Express hier im Marketplace gefunden hast.

## Plugin-Konfiguration

Sobald dir die Benutzerdaten von GO! vorliegen, kannst du diese im Plugin hinterlegen und dein erstes Versandetikett generieren.

### Zugangsdaten hinterlegen

Für deinen Einstieg musst du zunächst den API-Zugriff ermöglichen.

1. Öffne das Menü **Plugins » Plugin-Set-Übersicht**.
2. Wähle das gewünschte Plugin-Set aus.
3. Klicke auf **GO! Express**.<br />→ Eine neue Ansicht öffnet sich.
4. Wähle den Bereich **General** im Akkordeon.
5. Trage deinen Benutzernamen und dein Passwort ein.
6. **Speichere** die Einstellungen.

Achte darauf, dass für alle Testszenarien der Modus auf **DEMO** steht. Du kannst nach Anpassung der Versendereinstellungen im Versand-Center Sendungen anmelden und erhälst die passende Transaktions-Nr. inkl. Label zurück.

Sobald du von GO! die Freigabe für den Produktivbetrieb erhalten hast, musst du hier den Schalter auf **FINAL** stellen.

### Versendereinstellungen

Hinterlege im Bereich **Sender** deine Adressdaten gemäß Registrierung. Zusätzlich kannst du unter **Shipping** deine Abholzeit und einen optionalen Zustellhinweis konfigurieren.

## GO! Express als Versandoption

Wenn das Plugin erfolgreich installiert und die Tests erfolgreich verlaufen sind, ist es an der Zeit den Versanddienstleister als Option im Checkout deines Shops auswählbar zu machen.

1. Aktiviere deine **[Lieferländer](https://knowledge.plentymarkets.com/fulfillment/versand-vorbereiten#100)**
2. Erstelle deine (Versand-)**[Regionen](https://knowledge.plentymarkets.com/fulfillment/versand-vorbereiten#400)**
3. Erstelle deinen **[Versanddienstleister](https://knowledge.plentymarkets.com/fulfillment/versand-vorbereiten#800)** _**GO! Express**_
  * Wähle _**GO! Express**_ in der Spalte _Versanddienstleister_ aus
  * Hinterlege `https://www.general-overnight.com/deu_de/sendungsverfolgung.html?reference=$PaketNr` als Tracking-URL
4. Erstelle deine **[Versandprofile](https://knowledge.plentymarkets.com/fulfillment/versand-vorbereiten#1000)** und **[Portotabellen](https://knowledge.plentymarkets.com/fulfillment/versand-vorbereiten#1500)** für _**GO! Express**_
