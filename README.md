# PSOURCE DSGVO

**Deutsch** | [English](README.en.md)

[![Version](https://img.shields.io/badge/Version-1.0.1-2271b1?style=flat-square)](readme.txt)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)
![WordPress](https://img.shields.io/badge/WordPress-bis%207.1.0-21759b?style=flat-square&logo=wordpress&logoColor=white)
![ClassicPress](https://img.shields.io/badge/ClassicPress-2.7.4-03768e?style=flat-square)
[![Lizenz](https://img.shields.io/badge/Lizenz-GPL--2.0--or--later-2ea44f?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)

**Einwilligungen verwalten, externe Inhalte erst nach Zustimmung laden und Datenschutzanfragen in ClasicPress bearbeiten.**

PSOURCE DSGVO unterstützt Dich dabei, Datenschutzfunktionen auf Deiner Website oder in Deinem Webshop umzusetzen. Das Plugin verbindet eine konfigurierbare Einwilligungsleiste mit Formular-Integrationen, Platzhaltern für externe Inhalte sowie Auskunfts- und Anonymisierungsanfragen.

> **Wichtig:** Die Installation dieses Plugins allein macht Deine Website nicht DSGVO-konform. Du bleibst für die Auswahl der Dienste, die Rechtsgrundlagen, verständliche Einwilligungstexte und Deine Datenschutzerklärung verantwortlich. Das Plugin ersetzt keine Rechtsberatung.


## Funktionen

- **Einwilligungen:** Lege einzelne Einwilligungen mit Titel, Beschreibung und zugehörigem Code-Snippet an.
- **Einwilligungsleiste und Einstellungsdialog:** Besucher können ihre Auswahl speichern und den Dialog später erneut öffnen.
- **Skriptsteuerung:** Ordne Snippets dem Header, dem Beginn des Seiteninhalts im `body` oder dem Footer zu. Optional lassen sich Snippets mit `script`-Tags umschließen.
- **Externe Inhalte:** Erkannte iframe-Einbettungen können durch Platzhalter mit einer Schaltfläche zum Nachladen ersetzt werden.
- **Formular-Integrationen:** Ergänze unterstützte Formulare um Datenschutz-Kontrollkästchen und erfasse die Zustimmung über die jeweilige Integration.
- **Datenauskunft:** Besucher können einen Zugriffslink für Daten anfordern, die ihrer E-Mail-Adresse zugeordnet sind.
- **Anonymisierungsanfragen:** Besucher wählen gefundene Daten aus; Administratoren können entsprechende Anfragen bearbeiten.
- **Checkliste:** Hinweise helfen Dir, typische Stellen der Datenerhebung auf Deiner Website zu überprüfen.
- **Sprachen:** Deutsche Quelltexte und mitgelieferte englische Übersetzungen für `en_US` und `en_GB`.

## Unterstützte Integrationen

| Integration | Bereich |
| --- | --- |
| ClasicPress-Kommentare | Datenschutz-Kontrollkästchen und Erfassung der Zustimmung |
| ClasicPress-Registrierung | Zustimmung bei der Registrierung |
| Contact Form 7 | Datenschutz-Formularfeld für ausgewählte Formulare |
| Gravity Forms | Datenschutzfeld für ausgewählte Formulare |
| WooCommerce | Datenschutz-Kontrollkästchen für Checkout und Registrierung |

Die Integration muss jeweils im Plugin aktiviert und eingerichtet werden. Bei optionalen Integrationen muss auch das entsprechende Drittanbieter-Plugin installiert und aktiv sein.

Die Auskunfts- und Anonymisierungsfunktionen berücksichtigen ClasicPress-Benutzer, ClasicPress-Kommentare und unterstützte WooCommerce-Bestelldaten. Sie sind **kein vollständiger Export oder Löschmechanismus für sämtliche Daten anderer Plugins oder externer Dienste**.

## Installation

1. Sichere Deine Website und Datenbank. Probiere die Einrichtung zunächst in einer Testumgebung aus.
2. Lade das Plugin aus den [GitHub-Releases](https://github.com/Power-Source/ps-dsgvo/releases) herunter.
3. Installiere das ZIP über **Plugins → Installieren → Plugin hochladen**. Alternativ kannst Du den Plugin-Ordner nach `wp-content/plugins/ps-dsgvo` kopieren.
4. Aktiviere **PSOURCE DSGVO**.
5. Öffne **Werkzeuge → PSOURCE DSGVO**.

Für den Betrieb werden ClasicPress, eine funktionierende Datenbankverbindung und JavaScript im Browser benötigt. Für Auskunfts- und Bestätigungsnachrichten muss der E-Mail-Versand Deiner Website funktionieren. Zeitgesteuerte Aufgaben verwenden WP-Cron.

Die älteren Versionsangaben in [readme.txt](./readme.txt) sind keine Zusicherung aktueller Kompatibilität. Prüfe Deine konkrete ClasicPress-, PHP- und gegebenenfalls WooCommerce-Version vor dem produktiven Einsatz.

### Updates

Du kannst neue Versionen manuell installieren. Für automatische Updates von GitHub weist das Plugin auf den optionalen [PS Update Manager](https://github.com/Power-Source/ps-update-manager) hin.

## Einrichtung

### 1. Integrationen aktivieren

Öffne den Reiter **Integrationen** und aktiviere die benötigten Bereiche. Passe die Kontrollkästchen- und Fehlermeldungstexte an Deine tatsächliche Datenverarbeitung an. Bei Formular-Plugins kannst Du die einzubeziehenden Formulare auswählen.

Prüfe anschließend jedes betroffene Formular: Wird das Feld angezeigt? Wird eine fehlende Zustimmung korrekt behandelt? Funktioniert die Übermittlung weiterhin?

### 2. Datenschutzerklärung verknüpfen

Unter **Einstellungen** kannst Du eine Datenschutzseite auswählen oder einen externen Link hinterlegen. Passe auch den Linktext an.

In Textfeldern, die diese Ersetzung unterstützen, lässt sich der Platzhalter `%privacy_policy%` für den Datenschutzlink verwenden. Beachte die Hinweise direkt am jeweiligen Feld: Nicht jedes Textfeld unterstützt diesen Platzhalter oder HTML.

### 3. Einwilligungen und Skripte anlegen

1. Öffne **Einwilligungen** und füge einen Eintrag hinzu.
2. Vergib einen verständlichen Titel und beschreibe Zweck und Anbieter.
3. Hinterlege das Code-Snippet und wähle die Platzierung.
4. Entscheide, ob das Snippet bereits eigene `script`-Tags enthält.
5. Aktiviere den Eintrag und teste das Verhalten vor und nach der Zustimmung.

Als **erforderlich** markierte Einwilligungen werden immer aktiviert und sind nicht abwählbar. Nutze diese Einstellung nur für tatsächlich notwendige Funktionen.

> Entferne parallel eingebundene Tracking- oder Werbeskripte aus Deinem Theme, Tag-Manager oder anderen Plugins. Ein hier verwaltetes Snippet verhindert nicht, dass derselbe Dienst an anderer Stelle unabhängig geladen wird.

Unter **Einstellungen** kannst Du Texte und Farben der Einwilligungsleiste sowie den Einstellungsdialog anpassen. Die gespeicherte Auswahl gilt für den jeweiligen Browser und das jeweilige Gerät; die Leiste verwendet eine Laufzeit von 365 Tagen. Über **Zustimmungsleiste zurücksetzen** kannst Du eine erneute Abfrage für die Besucher auslösen.

### 4. Externe Inhalte prüfen

Der Reiter **Externe Inhalte** enthält Einstellungen und eigene Platzhalter-Nachrichten für:

- YouTube
- Vimeo
- Google Maps
- Twitter/X
- Instagram
- Facebook
- Spotify

Die Ersetzung setzt eine aktive Einwilligungsverwaltung mit mindestens einer aktiven Einwilligung voraus. Sie erfasst erkannte iframe-Einbettungen in den gefilterten Beitrags- und Text-Widget-Inhalten. Beim Nachladen werden nur HTTP(S)-Adressen akzeptiert.

**Grenzen der Erkennung:** Das Plugin ist kein allgemeiner Netzwerkblocker. Direkt im Theme ausgegebene Inhalte, Skript-Widgets, andere Einbettungsformate und nachträglich per JavaScript erzeugte Elemente werden nicht automatisch vollständig erfasst. Prüfe für Deine konkrete Einbettung in den Browser-Entwicklerwerkzeugen, ob vor der Zustimmung bereits Verbindungen zum Anbieter entstehen.

Die Zustimmung zu einem Embed-Anbieter wird separat in einem Cookie für diesen Anbieter gespeichert. Verwechsle diese Cookies nicht mit der Auswahl für die Skript-Einwilligungsleiste.

### 5. Datenauskunft einrichten

Aktiviere unter **Einstellungen** die Funktion **Benutzerdaten anfordern**. Das Plugin kann dafür eine private Seite mit dem benötigten Shortcode anlegen. Prüfe ihren Inhalt und veröffentliche sie erst nach Deiner Einrichtung.

Der Ablauf:

1. Ein Besucher gibt seine E-Mail-Adresse ein und stimmt der Anfrage zu.
2. Das Plugin verschickt einen Zugriffslink per E-Mail.
3. Der Link ist auf 24 Stunden begrenzt und an das ursprüngliche Gerät, die IP-Adresse und die Browsersitzung gebunden.
4. Auf der Auskunftsseite können Anonymisierungen für gefundene Daten angefordert werden.
5. Du bearbeitest diese unter **Anfragen**.

Teste diesen Ablauf einschließlich E-Mail-Zustellung und Sitzungsbindung. Ein Gerätewechsel, eine geänderte IP-Adresse oder eine neue Browsersitzung kann den Zugriff verhindern.

## Shortcodes

### Formular für Datenauskunft

```text
[psdsgvo_access_request_form]
```

Die Auskunftsfunktion muss aktiviert sein. Verwende dafür die in den Einstellungen zugeordnete Seite.

### Link zu den Datenschutzeinstellungen

```text
[psdsgvo_consents_settings_link]Datenschutzeinstellungen öffnen[/psdsgvo_consents_settings_link]
```

Optional kannst Du eine eigene CSS-Klasse ergänzen:

```text
[psdsgvo_consents_settings_link class="mein-datenschutzlink"]Meine Einstellungen[/psdsgvo_consents_settings_link]
```

Der Link benötigt mindestens eine aktive Einwilligung. Alternativ lässt sich die CSS-Klasse `psdsgvo-consents-settings-link` an einem geeigneten Menüelement verwenden.

## Deutsch und Englisch

**Die Quelltexte bleiben deutsch.** Englisch wird über ClasicPress-Sprachdateien geladen:

| Sprache | Bereitstellung |
| --- | --- |
| Deutsch | Quelltexte |
| English (United States) | `en_US`, PO- und MO-Datei |
| English (UK) | `en_GB`, PO- und MO-Datei |

- **Frontend:** Die Website-Sprache unter **Einstellungen → Allgemein** ist maßgeblich.
- **Backend:** Die im ClasicPress-Benutzerprofil eingestellte Sprache wird berücksichtigt.
- **Eigene Inhalte:** Gespeicherte Beschriftungen, eigene Platzhalter-Nachrichten und bereits angelegte Einwilligungen werden nicht automatisch übersetzt oder überschrieben.

Die Sprachdateien liegen in [languages/](./languages/); die Text-Domain lautet `psource-dsgvo`. Für eigene mehrsprachige Einwilligungstexte ist eine passende redaktionelle bzw. technische Lösung erforderlich.

## Datenschutz und Betrieb

- **Keine Rechtsgarantie:** Prüfe Einwilligungstexte, notwendige Dienste und Datenschutzhinweise fachlich.
- **Keine pauschale Anbietersperre:** Kontrolliere tatsächliche Netzwerkzugriffe vor einer Zustimmung.
- **Cookies und Sitzungen:** Berücksichtige die Einwilligungs-Cookies und die Sitzungsbindung der Auskunftsfunktion in Deiner Dokumentation.
- **Anfragedaten:** Für die Bearbeitung werden unter anderem E-Mail-Adresse, IP-Adresse und Sitzungsinformationen verarbeitet.
- **Protokolle:** Die Embed-Verarbeitung schreibt Diagnosemeldungen in das PHP-Fehlerprotokoll, teilweise mit Ausschnitten der Embed-URL. Prüfe Zugriffsschutz und Aufbewahrung Deiner Serverprotokolle.
- **WP-Cron:** Ablauf- und Aufräumaufgaben hängen von einer funktionierenden Cron-Ausführung ab. Die zeitgesteuerte Anonymisierung älterer Anfragedatensätze ist nicht mit der Bearbeitung aller angefragten Nutzerdaten gleichzusetzen.
- **Caching:** Teste Seiten-Cache, CDN und Optimierungs-Plugins gemeinsam mit Einwilligungsleiste, Platzhaltern und Auskunftsseite.

### Deaktivierung und Deinstallation

Das Deaktivieren ist nicht dasselbe wie das Löschen des Plugins.

**Achtung beim Löschen über ClasicPress:** Die Deinstallation entfernt Plugin-Optionen und Plugin-Datenbanktabellen, räumt die geplanten Plugin-Aufgaben auf und verschiebt die vom Plugin markierte Auskunftsseite in den Papierkorb. Erstelle vorher eine Sicherung, wenn Du Einstellungen oder Anfragedaten behalten möchtest.

## Entwicklung und Übersetzungen

### Sprachdateien aktualisieren

Im Plugin-Verzeichnis mit [WP-CLI](https://wp-cli.org/):

```bash
wp i18n make-pot . languages/psource-dsgvo.pot --domain=psource-dsgvo --exclude=assets/vendor,tests
```

Gleiche anschließend die englischen PO-Dateien mit der aktualisierten Vorlage ab, ergänze die Übersetzungen und kompiliere sie:

```bash
wp i18n make-mo languages
```

### Tests

JavaScript-Regressionstests mit einer Node.js-Version, die den integrierten Test-Runner unterstützt:

```bash
node --test tests/code-scanning.test.js
```

Übersetzungstests mit PHP und einer lokalen ClasicPress-Installation:

```bash
php tests/localization.php
```

Bei abweichender Verzeichnisstruktur:

```bash
WP_TEST_ROOT=/pfad/zu/wordpress php tests/localization.php
```

Der Übersetzungstest benötigt keine Datenbankverbindung. Er prüft unter anderem die Sprachkataloge, Platzhalter, deutsche und englische Standardtexte sowie den Erhalt eigener Beschriftungen. Er ersetzt keinen vollständigen Funktionstest im Browser.

## Unterstützung und Beiträge

Fehlerberichte und Verbesserungsvorschläge sind über [GitHub Issues](https://github.com/Power-Source/ps-dsgvo/issues) willkommen. Nenne nach Möglichkeit:

- ClasicPress-, PHP- und Plugin-Version;
- verwendete Integration und deren Version;
- Schritte zum Nachstellen;
- erwartetes und tatsächliches Verhalten.

Entferne personenbezogene Daten, Zugangsdaten und Tokens aus Screenshots und Protokollen. Code-Beiträge kannst Du als Pull Request einreichen.

## Lizenz

PSOURCE DSGVO steht unter der **GNU General Public License, Version 2 oder später**. Siehe [gpl.txt](./gpl.txt). Mitgelieferte Drittanbieter-Bibliotheken können eigene Lizenzhinweise enthalten.
