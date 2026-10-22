# PSOURCE DSGVO

[Deutsch](README.md) | **English**

[![Version](https://img.shields.io/badge/Version-1.0.1-2271b1?style=flat-square)](readme.txt)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)
![WordPress](https://img.shields.io/badge/WordPress-up%20to%207.1.0-21759b?style=flat-square&logo=wordpress&logoColor=white)
![ClassicPress](https://img.shields.io/badge/ClassicPress-2.7.4-03768e?style=flat-square)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-2ea44f?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)

**Manage consent, load external content only after approval, and handle privacy requests in ClasicPress.**

PSOURCE DSGVO helps you implement privacy features on your website or webshop. The plugin combines a configurable consent bar with form integrations, placeholders for external content, and data access and anonymization requests.

> **Important:** Installing this plugin alone does not make your website GDPR-compliant. You remain responsible for selecting services, establishing legal grounds, providing clear consent wording, and maintaining your privacy policy. This plugin does not replace legal advice.

## Features

- **Consent management:** Create individual consents with a title, description, and associated code snippet.
- **Consent bar and settings dialog:** Visitors can save their choices and reopen the dialog later.
- **Script control:** Assign snippets to the header, the start of the page's `body`, or the footer. Optionally wrap snippets in `script` tags.
- **External content:** Recognized iframe embeds can be replaced with placeholders and a button to load the content.
- **Form integrations:** Add privacy checkboxes to supported forms and record consent through the respective integration.
- **Data access:** Visitors can request an access link for data associated with their email address.
- **Anonymization requests:** Visitors select the data found; administrators can process the resulting requests.
- **Checklist:** Guidance helps you review common points of data collection on your website.
- **Languages:** German source strings and bundled English translations for `en_US` and `en_GB`.

## Supported integrations

| Integration | Scope |
| --- | --- |
| ClasicPress comments | Privacy checkbox and consent recording |
| ClasicPress registration | Consent during registration |
| Contact Form 7 | Privacy form field for selected forms |
| Gravity Forms | Privacy field for selected forms |
| WooCommerce | Privacy checkbox for checkout and registration |

Each integration must be enabled and configured in the plugin. For optional integrations, the corresponding third-party plugin must also be installed and active.

The data access and anonymization features cover ClasicPress users, ClasicPress comments, and supported WooCommerce order data. They are **not a complete export or deletion mechanism for all data held by other plugins or external services**.

## Installation

1. Back up your website and database. Try the setup in a test environment first.
2. Download the plugin from [GitHub Releases](https://github.com/Power-Source/ps-dsgvo/releases).
3. Install the ZIP through **Plugins → Add New → Upload Plugin**. Alternatively, copy the plugin folder to `wp-content/plugins/ps-dsgvo`.
4. Activate **PSOURCE DSGVO**.
5. Open **Tools → PSOURCE DSGVO**.

The plugin requires ClasicPress, a working database connection, and JavaScript in the browser. Your website's email delivery must work for data access and confirmation messages. Scheduled tasks use WP-Cron.

The older version information in [readme.txt](./readme.txt) is not a guarantee of current compatibility. Test your specific ClasicPress, PHP, and, where applicable, WooCommerce versions before using the plugin in production.

### Updates

You can install new versions manually. For automatic updates from GitHub, the plugin points you to the optional [PS Update Manager](https://github.com/Power-Source/ps-update-manager).

## Setup

### 1. Enable integrations

Open the **Integrations** tab and enable the areas you need. Adapt the checkbox and error messages to your actual data processing. For form plugins, you can select which forms to include.

Then test each affected form: Is the field displayed? Is missing consent handled correctly? Does form submission still work?

### 2. Link your privacy policy

Under **Settings**, you can select a privacy policy page or provide an external link. Adjust the link text as well.

In text fields that support this substitution, you can use the `%privacy_policy%` placeholder for the privacy policy link. Follow the instructions next to each field: Not every text field supports this placeholder or HTML.

### 3. Create consents and scripts

1. Open **Consents** and add an entry.
2. Provide a clear title and describe the purpose and provider.
3. Add the code snippet and choose its placement.
4. Specify whether the snippet already includes its own `script` tags.
5. Activate the entry and test its behavior before and after consent.

Consents marked as **Required** are always enabled and cannot be deselected. Only use this setting for genuinely necessary functions.

> Remove duplicate tracking or advertising scripts embedded through your theme, tag manager, or other plugins. Managing a snippet here does not prevent the same service from loading independently elsewhere.

Under **Settings**, you can customize the consent bar's text and colors as well as the settings dialog. Saved choices apply to the individual browser and device; the bar uses a duration of 365 days. Use **Reset consent bar** to prompt visitors again.

### 4. Check external content

The **External content** tab provides settings and custom placeholder messages for:

- YouTube
- Vimeo
- Google Maps
- Twitter/X
- Instagram
- Facebook
- Spotify

Replacement requires active consent management with at least one active consent. It covers recognized iframe embeds in filtered post and text widget content. Only HTTP(S) addresses are accepted when loading content.

**Detection limits:** The plugin is not a general-purpose network blocker. Content rendered directly by a theme, script widgets, other embedding formats, and elements created later by JavaScript are not automatically covered in full. For your particular embed, use your browser's developer tools to check whether connections to the provider occur before consent.

Consent for an embed provider is stored separately in a cookie for that provider. Do not confuse these cookies with the choices made in the script consent bar.

### 5. Set up data access

Under **Settings**, enable **Request user data**. The plugin can create a private page containing the required shortcode. Review its content and publish it only after completing your setup.

The process:

1. A visitor enters their email address and consents to the request.
2. The plugin sends an access link by email.
3. The link is limited to 24 hours and tied to the original device, IP address, and browser session.
4. On the data access page, visitors can request anonymization of the data found.
5. You process these requests under **Requests**.

Test this process, including email delivery and session binding. Switching devices, changing IP addresses, or starting a new browser session may prevent access.

## Shortcodes

### Data access request form

```text
[psdsgvo_access_request_form]
```

The data access feature must be enabled. Use the page assigned in the settings.

### Link to privacy settings

```text
[psdsgvo_consents_settings_link]Open privacy settings[/psdsgvo_consents_settings_link]
```

You can optionally add a custom CSS class:

```text
[psdsgvo_consents_settings_link class="my-privacy-link"]My settings[/psdsgvo_consents_settings_link]
```

The link requires at least one active consent. Alternatively, you can apply the CSS class `psdsgvo-consents-settings-link` to a suitable menu item.

## German and English

**Source strings remain German.** English is loaded through ClasicPress language files:

| Language | Provided through |
| --- | --- |
| German | Source strings |
| English (United States) | `en_US`, PO and MO files |
| English (UK) | `en_GB`, PO and MO files |

- **Frontend:** The site language under **Settings → General** determines the language.
- **Backend:** The language selected in the ClasicPress user profile is respected.
- **Custom content:** Saved labels, custom placeholder messages, and existing consent entries are not automatically translated or overwritten.

Language files are located in [languages/](./languages/); the text domain is `psource-dsgvo`. Custom multilingual consent text requires an appropriate editorial or technical solution.

## Privacy and operation

- **No legal guarantee:** Have your consent wording, necessary services, and privacy notices reviewed appropriately.
- **No blanket provider blocking:** Check actual network requests before consent.
- **Cookies and sessions:** Account for consent cookies and the session binding of the data access feature in your documentation.
- **Request data:** Processing requests involves data such as email addresses, IP addresses, and session information.
- **Logs:** Embed processing writes diagnostic messages to the PHP error log, sometimes including portions of the embed URL. Review access controls and retention for your server logs.
- **WP-Cron:** Expiration and cleanup tasks depend on working cron execution. Scheduled anonymization of older request records is not the same as processing all user data covered by anonymization requests.
- **Caching:** Test page caching, CDNs, and optimization plugins together with the consent bar, placeholders, and data access page.

### Deactivation and uninstallation

Deactivating the plugin is not the same as deleting it.

**Take care when deleting through ClasicPress:** Uninstallation removes plugin options and database tables, clears scheduled plugin tasks, and moves the data access page marked by the plugin to the trash. Create a backup first if you want to retain settings or request data.

## Development and translations

### Update language files

From the plugin directory, using [WP-CLI](https://wp-cli.org/):

```bash
wp i18n make-pot . languages/psource-dsgvo.pot --domain=psource-dsgvo --exclude=assets/vendor,tests
```

Then update the English PO files against the revised template, add the translations, and compile them:

```bash
wp i18n make-mo languages
```

### Tests

Run the JavaScript regression tests with a Node.js version that supports the built-in test runner:

```bash
node --test tests/code-scanning.test.js
```

Run the translation tests with PHP and a local ClasicPress installation:

```bash
php tests/localization.php
```

For a different directory layout:

```bash
WP_TEST_ROOT=/path/to/wordpress php tests/localization.php
```

The translation test does not require a database connection. Among other things, it checks language catalogs, placeholders, German and English default text, and preservation of custom labels. It does not replace a full functional test in the browser.

## Support and contributions

Bug reports and improvement suggestions are welcome through [GitHub Issues](https://github.com/Power-Source/ps-dsgvo/issues). Where possible, include:

- ClasicPress, PHP, and plugin versions;
- the integration used and its version;
- steps to reproduce;
- expected and actual behavior.

Remove personal data, credentials, and tokens from screenshots and logs. Code contributions can be submitted as pull requests.

## License

PSOURCE DSGVO is licensed under the **GNU General Public License, version 2 or later**. See [gpl.txt](./gpl.txt). Bundled third-party libraries may include their own license notices.
