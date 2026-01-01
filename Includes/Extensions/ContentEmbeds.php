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
     */
    private $embedTypes = array(
        'youtube' => array(
            'label' => 'YouTube',
            'description' => 'YouTube Videos',
            'patterns' => array(
                'youtube.com/embed',
                'youtube.com/watch',
                'youtu.be',
            ),
            'cookie_info' => 'YouTube verwendet Cookies um Nutzerverhalten zu tracken.',
        ),
        'vimeo' => array(
            'label' => 'Vimeo',
            'description' => 'Vimeo Videos',
            'patterns' => array(
                'vimeo.com',
                'player.vimeo.com',
            ),
            'cookie_info' => 'Vimeo verwendet Cookies um Nutzerverhalten zu tracken.',
        ),
        'google_maps' => array(
            'label' => 'Google Maps',
            'description' => 'Google Maps Karten',
            'patterns' => array(
                'maps.google.com',
                'google.com/maps',
                'maps.googleapis.com',
            ),
            'cookie_info' => 'Google Maps verwendet Cookies und sammelt Standortdaten.',
        ),
        'twitter' => array(
            'label' => 'Twitter/X',
            'description' => 'Twitter/X Embeds',
            'patterns' => array(
                'twitter.com',
                'platform.twitter.com',
                'x.com',
            ),
            'cookie_info' => 'Twitter verwendet Cookies um Nutzerverhalten zu tracken.',
        ),
        'instagram' => array(
            'label' => 'Instagram',
            'description' => 'Instagram Posts',
            'patterns' => array(
                'instagram.com',
            ),
            'cookie_info' => 'Instagram (Facebook) verwendet Cookies um Nutzerverhalten zu tracken.',
        ),
        'facebook' => array(
            'label' => 'Facebook',
            'description' => 'Facebook Embeds',
            'patterns' => array(
                'facebook.com',
                'fb.com',
            ),
            'cookie_info' => 'Facebook verwendet Cookies um Nutzerverhalten zu tracken.',
        ),
        'spotify' => array(
            'label' => 'Spotify',
            'description' => 'Spotify Player',
            'patterns' => array(
                'spotify.com',
                'open.spotify.com',
            ),
            'cookie_info' => 'Spotify verwendet Cookies um Nutzerverhalten zu tracken.',
        ),
    );

    /**
     * Gibt alle verfügbaren Embed-Typen zurück
     * @return array
     */
    public function getEmbedTypes() {
        return $this->embedTypes;
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
        $default = sprintf(
            __('Dieser Inhalt wird von %s geladen. Um diesen Inhalt zu sehen, müssen Sie der Verwendung von Cookies zustimmen.', PS_DSGVO_C_SLUG),
            $this->embedTypes[$type]['label']
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
        
        // Prüfe ob Consents existieren und aktiv sind
        if (!Consent::databaseTableExists() || !Consent::isActive()) {
            error_log('PSDSGVO: Consents not active. DB exists: ' . (Consent::databaseTableExists() ? 'yes' : 'no') . ', Is active: ' . (Consent::isActive() ? 'yes' : 'no'));
            return $content; // Keine Consents aktiv, keine Blockierung
        }
        
        // Blockiere YouTube-Videos wenn ein Consent existiert
        // (Consent wurde automatisch beim ersten Laden erstellt)
        $consents = Consent::getInstance()->getList();
        error_log('PSDSGVO: Found ' . count($consents) . ' consents');
        
        if (!empty($consents)) {
            // Blockiere alle Embed-Typen
            foreach ($this->embedTypes as $type => $config) {
                $oldContent = $content;
                $content = $this->replaceEmbeds($content, $type);
                if ($oldContent !== $content) {
                    error_log('PSDSGVO: Replaced content for type: ' . $type);
                }
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
        if (!isset($this->embedTypes[$type]) || empty($content)) {
            return $content;
        }
        
        $config = $this->embedTypes[$type];
        
        // Prüfe ob User bereits Consent gegeben hat
        if ($this->hasUserConsent($type)) {
            error_log('PSDSGVO: User has consent for ' . $type . ', skipping replacement');
            return $content;
        }

        // Einfach: Suche nach <iframe ... mit src-Attribut
        // und prüfe jedes iframe, ob es eine der konfigurierten URLs enthält
        $pattern = '/<iframe[^>]*src=["\']([^"\']+)["\'][^>]*>.*?<\/iframe>/i';
        
        error_log('PSDSGVO: Searching for iframes of type ' . $type);
        
        $content = preg_replace_callback($pattern, function($matches) use ($type, $config) {
            $src = $matches[1];
            error_log('PSDSGVO: Found iframe with src: ' . substr($src, 0, 100));
            
            // Prüfe ob diese URL zu diesem Typ passt
            foreach ($config['patterns'] as $pattern) {
                if (stripos($src, $pattern) !== false) {
                    error_log('PSDSGVO: Matched pattern ' . $pattern . ', replacing with placeholder');
                    // URL passt zu diesem Typ - ersetze mit Platzhalter
                    return $this->createPlaceholder($type, $src, $config);
                }
            }
            
            error_log('PSDSGVO: No pattern matched for src: ' . $src);
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
                    <?php _e('Inhalt laden', PS_DSGVO_C_SLUG); ?>
                </button>
                <p class="psdsgvo-embed-privacy-link">
                    <small>
                        <a href="<?php echo esc_url(Helper::getPrivacyPolicyUrl()); ?>" target="_blank">
                            <?php _e('Datenschutzerklärung', PS_DSGVO_C_SLUG); ?>
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
