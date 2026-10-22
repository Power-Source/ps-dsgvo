<?php

$root = dirname(__DIR__);
$wordpress = getenv('WP_TEST_ROOT') ?: dirname($root, 3);
if (!is_file($wordpress . '/wp-includes/pomo/po.php')) {
    fwrite(STDERR, "ClasicPress nicht gefunden. WP_TEST_ROOT auf das ClasicPress-Verzeichnis setzen.\n");
    exit(1);
}

require $wordpress . '/wp-includes/pomo/po.php';
require $wordpress . '/wp-includes/pomo/mo.php';
require $wordpress . '/wp-includes/plugin.php';
require $wordpress . '/wp-includes/l10n.php';
require $wordpress . '/wp-includes/class-wp-textdomain-registry.php';
foreach (array('translation-controller', 'translations', 'translation-file', 'translation-file-mo', 'translation-file-php') as $class) {
    $file = $wordpress . '/wp-includes/l10n/class-wp-' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
}

define('PS_DSGVO_C_PREFIX', 'psdsgvo');
define('WP_PLUGIN_DIR', dirname($root));
define('WP_LANG_DIR', dirname($root, 2) . '/languages');
$wp_textdomain_registry = new WP_Textdomain_Registry();
$options = array();
$activeLocale = 'de_DE';
add_filter('pre_determine_locale', function () use (&$activeLocale) {
    return $activeLocale;
});

function get_option($name, $default = false) {
    return isset($GLOBALS['options'][$name]) ? $GLOBALS['options'][$name] : $default;
}

function wp_kses($text, $allowed) {
    return $text;
}

function esc_attr($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function pruefe($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

foreach (array('Consent', 'Integration', 'Helper', 'Data', 'Cron', 'Extensions/CF7', 'Extensions/ContentEmbeds') as $class) {
    require $root . '/Includes/' . $class . '.php';
}

$template = new PO();
pruefe($template->import_from_file($root . '/languages/psource-dsgvo.pot'), 'POT nicht lesbar.');

foreach (array('en_US', 'en_GB') as $locale) {
    $po = new PO();
    $mo = new MO();
    pruefe($po->import_from_file("$root/languages/psource-dsgvo-$locale.po"), "$locale: PO nicht lesbar.");
    pruefe($mo->import_from_file("$root/languages/psource-dsgvo-$locale.mo"), "$locale: MO nicht lesbar.");
    pruefe(count($template->entries) === count($po->entries), "$locale: PO unvollständig.");
    pruefe(count($template->entries) === count($mo->entries), "$locale: MO unvollständig.");

    foreach ($template->entries as $key => $entry) {
        pruefe(isset($po->entries[$key], $mo->entries[$key]), "$locale: Fehlende Übersetzung: $key");
        $translation = $po->entries[$key]->translations[0];
        pruefe($translation !== '', "$locale: Leere Übersetzung: $key");
        pruefe($translation === $mo->translate($entry->singular, $entry->context), "$locale: MO veraltet: $key");
        preg_match_all('/%(?:\d+\$)?[sd]/', $entry->singular, $sourcePlaceholders);
        preg_match_all('/%(?:\d+\$)?[sd]/', $translation, $translatedPlaceholders);
        pruefe($sourcePlaceholders[0] === $translatedPlaceholders[0], "$locale: Veränderte Platzhalter: $key");
        pruefe(strpos($entry->singular, '%privacy_policy%') === false || strpos($translation, '%privacy_policy%') !== false, "$locale: Datenschutz-Platzhalter fehlt.");
    }

    $activeLocale = $locale;
    pruefe(load_plugin_textdomain('psource-dsgvo', false, basename($root) . '/languages'), "$locale: ClasicPress kann die Sprachdatei nicht laden.");
    pruefe(__('Einstellungen', 'psource-dsgvo') === 'Settings', "$locale: Admin-Text nicht übersetzt.");
    pruefe(_x('Neue hinzufügen', 'consent', 'psource-dsgvo') === 'Add new', "$locale: Kontext fehlt.");
    pruefe(\PSDSGVO\Includes\Consent::getModalTitle(false) === 'Privacy settings', "$locale: Modal nicht übersetzt.");
    pruefe(\PSDSGVO\Includes\Consent::getBarButtonText() === 'Accept', "$locale: Schaltfläche nicht übersetzt.");
    pruefe(\PSDSGVO\Includes\Integration::getPrivacyPolicyText() === 'Privacy Policy', "$locale: Datenschutz-Link nicht übersetzt.");
    pruefe(\PSDSGVO\Includes\Integration::getErrorMessage() === 'Please accept the privacy checkbox.', "$locale: Fehlermeldung nicht übersetzt.");
    pruefe(sprintf(__('%s - Deine Datenanfrage', 'psource-dsgvo'), 'Website') === 'Website - Your data request', "$locale: E-Mail-Betreff nicht übersetzt.");

    $embeds = \PSDSGVO\Includes\Extensions\ContentEmbeds::getInstance();
    pruefe($embeds->getEmbedTypes()['youtube']['cookie_info'] === 'YouTube uses cookies to track user behavior.', "$locale: Embed-Information nicht übersetzt.");
    pruefe(strpos($embeds->getPlaceholderMessage('youtube'), 'This content is loaded from YouTube.') === 0, "$locale: Platzhalter nicht übersetzt.");

    $options['psdsgvo_settings_consents_bar_button_text'] = 'Meine eigene Beschriftung';
    $options['psdsgvo_embed_settings_youtube_message'] = 'Meine eigene Nachricht';
    pruefe(\PSDSGVO\Includes\Consent::getBarButtonText() === 'Meine eigene Beschriftung', "$locale: Eigener Text verändert.");
    pruefe($embeds->getPlaceholderMessage('youtube') === 'Meine eigene Nachricht', "$locale: Eigene Nachricht verändert.");
    $options = array();

    $GLOBALS['l10n']['psource-dsgvo'] = new NOOP_Translations();
    pruefe(\PSDSGVO\Includes\Consent::getModalTitle(false) === 'Datenschutzeinstellungen', 'Deutscher Quelltext fehlt.');
    pruefe(\PSDSGVO\Includes\Consent::getBarButtonText() === 'Akzeptieren', 'Deutsche Schaltfläche verändert.');
    pruefe($embeds->getEmbedTypes()['youtube']['cookie_info'] === 'YouTube verwendet Cookies um Nutzerverhalten zu tracken.', 'Embed-Sprache wird zwischengespeichert.');
    echo "$locale: Katalog, Platzhalter, Oberfläche, Sprachwechsel und eigene Texte geprüft.\n";
}

$plugin = file_get_contents($root . '/ps-dsgvo.php');
pruefe(strpos($plugin, "define('PS_DSGVO_C_SLUG', 'ps-dsgvo-tool')") !== false, 'Bestehender Plugin-Slug verändert.');
pruefe(strpos($plugin, "load_plugin_textdomain('psource-dsgvo'") !== false, 'Text-Domain wird nicht geladen.');
pruefe(strpos($plugin, "add_action('init', array(PSDSGVO::getInstance(), 'init'), 0)") !== false, 'Übersetzungen werden nicht rechtzeitig initialisiert.');
echo count($template->entries) . " Meldungen pro Sprache vollständig übersetzt.\n";
