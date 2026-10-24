<?php

namespace PSDSGVO\Includes\Extensions;

use PSDSGVO\Includes\Helper;
use PSDSGVO\Includes\Consent;

/**
 * Class ContentEmbeds
 * @package PSDSGVO\Includes\Extensions
 * 
 * Verwaltet externe Content-Embeds (YouTube, Vimeo, Google Maps, etc.)
 * und ersetzt sie mit Consent-Platzhaltern
 */
class ContentEmbeds {
    const ID = 'content_embeds';
    
    /** @var null */
    private static $instance = null;

    /**
     * Verfügbare Embed-Typen
     * @return array
     */
    public function getEmbedTypes() {
        return array(
            'youtube' => array(
                'label' => 'YouTube',
                'description' => __('YouTube Videos', 'psource-dsgvo'),
                'patterns' => array(
                    'youtube.com/embed',
                    'youtube.com/watch',
                    'youtu.be',
                ),
                'cookie_info' => __('YouTube verwendet Cookies um Nutzerverhalten zu tracken.', 'psource-dsgvo'),
            ),
            'vimeo' => array(
                'label' => 'Vimeo',
                'description' => __('Vimeo Videos', 'psource-dsgvo'),
                'patterns' => array(
                    'vimeo.com',
                    'player.vimeo.com',
                ),
                'cookie_info' => __('Vimeo verwendet Cookies um Nutzerverhalten zu tracken.', 'psource-dsgvo'),
            ),
            'google_maps' => array(
                'label' => 'Google Maps',
                'description' => __('Google Maps Karten', 'psource-dsgvo'),
                'patterns' => array(
                    'maps.google.com',
                    'google.com/maps',
                    'maps.googleapis.com',
                ),
                'cookie_info' => __('Google Maps verwendet Cookies und sammelt Standortdaten.', 'psource-dsgvo'),
            ),
            'twitter' => array(
                'label' => 'Twitter/X',
                'description' => __('Twitter/X Embeds', 'psource-dsgvo'),
                'patterns' => array(
                    'twitter.com',
                    'platform.twitter.com',
                    'x.com',
                ),
                'cookie_info' => __('Twitter verwendet Cookies um Nutzerverhalten zu tracken.', 'psource-dsgvo'),
            ),
            'instagram' => array(
                'label' => 'Instagram',
                'description' => __('Instagram Posts', 'psource-dsgvo'),
                'patterns' => array(
                    'instagram.com',
                ),
                'cookie_info' => __('Instagram (Facebook) verwendet Cookies um Nutzerverhalten zu tracken.', 'psource-dsgvo'),
            ),
            'facebook' => array(
                'label' => 'Facebook',
                'description' => __('Facebook Embeds', 'psource-dsgvo'),
                'patterns' => array(
                    'facebook.com',
                    'fb.com',
                ),
                'cookie_info' => __('Facebook verwendet Cookies um Nutzerverhalten zu tracken.', 'psource-dsgvo'),
            ),
            'spotify' => array(
                'label' => 'Spotify',
                'description' => __('Spotify Player', 'psource-dsgvo'),
                'patterns' => array(
                    'spotify.com',
                    'open.spotify.com',
                ),
                'cookie_info' => __('Spotify verwendet Cookies um Nutzerverhalten zu tracken.', 'psource-dsgvo'),
            ),
        );
    }

    /**
     * Prüft ob ein bestimmter Embed-Typ aktiviert (blockiert) ist
     * @param string $type
     * @return bool
     */
    public function isEmbedTypeEnabled($type) {
        // Standardmäßig: Alle Embeds blockieren (außer YouTube, das wird speziell behandelt)
        // Nur wenn explizit deaktiviert, nicht blockieren
        $disabled = get_option(PS_DSGVO_C_PREFIX . '_embed_settings_' . $type . '_disabled', 0);
        return empty($disabled); // Wenn nicht deaktiviert, dann blockieren (aktiviert)
    }

    /**
     * Gibt die Platzhalter-Nachricht für einen Embed-Typ zurück
     * @param string $type
     * @return string
     */
    public function getPlaceholderMessage($type) {
        $embedTypes = $this->getEmbedTypes();
        $default = sprintf(
            __('Dieser Inhalt wird von %s geladen. Um diesen Inhalt zu sehen, müssen Sie der Verwendung von Cookies zustimmen.', 'psource-dsgvo'),
            $embedTypes[$type]['label']
        );
        
        $custom = get_option(PS_DSGVO_C_PREFIX . '_embed_settings_' . $type . '_message', '');
        return !empty($custom) ? $custom : $default;
    }

    /**
     * Filtert den Content und ersetzt Embeds mit Platzhaltern
     * basierend auf aktiven Consents
     * @param string $content
     * @return string
     */
    public function filterContent($content) {
        // Sicherheitsprüfung
        if (empty($content) || !is_string($content)) {
            return $content;
        }
        
        // PERFORMANCE: Early-return wenn keine iframes vorhanden
        if (strpos($content, '<iframe') === false) {
            return $content;
        }
        
        // Prüfe ob Consents existieren und aktiv sind
        if (!Consent::databaseTableExists() || !Consent::isActive()) {
            return $content; // Keine Consents aktiv, keine Blockierung
        }
        
        // Blockiere YouTube-Videos wenn ein Consent existiert
        // (Consent wurde automatisch beim ersten Laden erstellt)
        $consents = Consent::getInstance()->getList();
        
        if (!empty($consents)) {
            // Blockiere alle Embed-Typen
            foreach ($this->getEmbedTypes() as $type => $config) {
                $content = $this->replaceEmbeds($content, $type);
            }
        }
        
        return $content;
    }

    /**
     * Ersetzt Embeds eines bestimmten Typs mit Platzhaltern
     * @param string $content
     * @param string $type
     * @return string|null
     */
    private function replaceEmbeds($content, $type) {
        $embedTypes = $this->getEmbedTypes();
        if (!isset($embedTypes[$type]) || empty($content)) {
            return $content;
        }
        
        $config = $embedTypes[$type];
        
        // Prüfe ob User bereits Consent gegeben hat
        if ($this->hasUserConsent($type)) {
            return $content;
        }

        // Einfach: Suche nach <iframe ... mit src-Attribut
        // und prüfe jedes iframe, ob es eine der konfigurierten URLs enthält
        $pattern = '/<iframe[^>]*src=["\']([^"\']+)["\'][^>]*>.*?<\/iframe>/i';
        
        $content = preg_replace_callback($pattern, function($matches) use ($type, $config) {
            $src = $matches[1];
            
            // Prüfe ob diese URL zu diesem Typ passt
            foreach ($config['patterns'] as $pattern) {
                if (stripos($src, $pattern) !== false) {
                    // URL passt zu diesem Typ - ersetze mit Platzhalter
                    return $this->createPlaceholder($type, $src, $config);
                }
            }
            
            // URL passt nicht zu diesem Typ - Original zurückgeben
            return $matches[0];
        }, $content);

        return $content;
    }

    /**
     * Erstellt einen Platzhalter für den Embed
     * @param string $type
     * @param string $originalUrl
     * @param array $config
     * @return string
     */
    private function createPlaceholder($type, $originalUrl, $config) {
        $message = $this->getPlaceholderMessage($type);
        $cookieInfo = $config['cookie_info'];
        
        ob_start();
        ?>
        <div class="psdsgvo-embed-placeholder" data-embed-type="<?php echo esc_attr($type); ?>" data-embed-url="<?php echo esc_attr($originalUrl); ?>">
            <div class="psdsgvo-embed-placeholder-content">
                <h4><?php echo esc_html($config['label']); ?></h4>
                <p><?php echo esc_html($message); ?></p>
                <p class="psdsgvo-embed-cookie-info"><small><?php echo esc_html($cookieInfo); ?></small></p>
                <button class="psdsgvo-embed-consent-btn" data-embed-type="<?php echo esc_attr($type); ?>">
                    <?php _e('Inhalt laden', 'psource-dsgvo'); ?>
                </button>
                <p class="psdsgvo-embed-privacy-link">
                    <small>
                        <a href="<?php echo esc_url(Helper::getPrivacyPolicyUrl()); ?>" target="_blank">
                            <?php _e('Datenschutzerklärung', 'psource-dsgvo'); ?>
                        </a>
                    </small>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Prüft ob User Consent für einen Embed-Typ gegeben hat
     * @param string $type
     * @return bool
     */
    private function hasUserConsent($type) {
        // Prüfe Cookie
        $cookieName = 'psdsgvo_embed_' . $type;
        return isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] === '1';
    }

    /**
     * @return null|ContentEmbeds
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
