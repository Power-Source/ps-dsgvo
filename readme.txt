=== PSOURCE DSGVO ===
Contributors: PSOURCE
Tags: gdpr, law, regulations, compliance, data, protection, privacy, data protection, eu, avg, comments, woocommerce, wc, contact form 7, cf7
Requires at least: 4.5
Tested up to: 7.1
ClassicPress 2.7.4
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Dieses Plugin unterstützt Website-Betreiber bei der Einhaltung der europäischen Datenschutzbestimmungen (DSGVO).

== Description ==

Dieses Plugin unterstützt Website- und Webshop-Betreiber bei der Einhaltung der europäischen Datenschutzgrundverordnung (DSGVO). Die Aktivierung dieses Plugins garantiert jedoch nicht die vollständige DSGVO-Konformität Ihrer Website.

- Fügen Sie Einwilligungen hinzu und geben Sie Ihren Besuchern die volle Kontrolle.

- Protokollierung der Einwilligungen für unterstützte Plugins.

- Hinzufügen von Kontrollkästchen zu unterstützten Plugins für die explizite Einwilligung der Besucher.

- Auskunftsrecht durch verschlüsselte Audit-Logs und Double-Opt-in-E-Mail.

Recht auf Vergessenwerden durch Anonymisierung von Nutzerdaten.

== Sprachen ==

Die Quelltexte bleiben deutsch. Englische Sprachdateien für English (United States)
und English (UK) werden mitgeliefert; die Text-Domain ist `psource-dsgvo`.
Die Website-Sprache unter Einstellungen > Allgemein bestimmt die Sprache im
Frontend. Im Backend wird die im Benutzerprofil eingestellte Sprache verwendet.
Individuell gespeicherte Texte und bereits angelegte Einwilligungen werden nicht
automatisch übersetzt oder überschrieben.

Sprachdateien aktualisieren (WP-CLI):
`wp i18n make-pot . languages/psource-dsgvo.pot --domain=psource-dsgvo --exclude=assets/vendor,tests`
Nach dem Aktualisieren der PO-Dateien:
`wp i18n make-mo languages`

Übersetzungen prüfen: `php tests/localization.php`
Der Test verwendet die lokale ClasicPress-Installation ohne Datenbankzugriff.
Bei einer anderen Verzeichnisstruktur kann `WP_TEST_ROOT` gesetzt werden.

== Changelog ==

= 1.0.2 =
* Überflüssige Debug-Ausgaben bei der Verarbeitung externer Content-Embeds entfernt.

= 1.0.1 =
* Englische Übersetzungen für en_US und en_GB ergänzt; deutsche Quelltexte beibehalten.
* Text-Domain und Laden der Übersetzungen vereinheitlicht; Embed-Texte übersetzbar gemacht.
* Content-Embeds akzeptieren nur HTTP(S)-URLs; ungültige URLs setzen kein Consent-Cookie.
* Fehlerhafte Maskierung der Whitespace-Prüfung in CodeMirror korrigiert.

= 1.0.0 =
* Initial release.
