(function (window, document, undefined) {
    'use strict';

    if (typeof psdsgvoData === 'undefined') {
        return;
    }

    /**
     * @param name
     * @returns {*}
     * @private
     */
    var _readCookie = function (name) {
            if (name) {
                for (var e = encodeURIComponent(name) + '=', o = document.cookie.split(';'), r = 0; r < o.length; r++) {
                    for (var n = o[r]; ' ' === n.charAt(0);) {
                        n = n.substring(1, n.length);
                    }
                    if (n.indexOf(e) === 0) {
                        return decodeURIComponent(n.substring(e.length, n.length));
                    }
                }
            }
            return null;
        },
        /**
         * @param name
         * @param data
         * @param days
         * @private
         */
        _saveCookie = function (name, data, days) {
            var date = new Date();
            data = (data) ? data : '';
            days = (days) ? days : 365;
            date.setTime(date.getTime() + 24 * days * 60 * 60 * 1e3);
            document.cookie = name + '=' + encodeURIComponent(data) + '; expires=' + date.toGMTString() + '; path=' + path;
        },
        /**
         * @param data
         * @returns {string}
         * @private
         */
        _objectToParametersString = function (data) {
            return Object.keys(data).map(function (key) {
                var value = data[key];
                if (typeof value === 'object') {
                    value = JSON.stringify(value);
                }
                return key + '=' + value;
            }).join('&');
        },
        /**
         * @param $checkboxes
         * @returns {Array}
         * @private
         */
        _getValuesByCheckedBoxes = function ($checkboxes) {
            var output = [];
            if ($checkboxes.length) {
                $checkboxes.forEach(function (e) {
                    var value = parseInt(e.value);
                    if (e.checked && value > 0) {
                        output.push(value);
                    }
                });
            }
            return output;
        },
        ajaxLoading = false,
        ajaxURL = psdsgvoData.ajaxURL,
        ajaxSecurity = psdsgvoData.ajaxSecurity,
        isMultisite = psdsgvoData.isMultisite,
        blogId = psdsgvoData.blogId,
        path = psdsgvoData.path,
        consents = (typeof psdsgvoData.consents !== 'undefined') ? psdsgvoData.consents : [],
        consentCookieName,
        consentCookie,
        /**
         * @param data
         * @param values
         * @param $form
         * @param delay
         * @private
         */
        _doAjax = function (data, values, $form, delay) {
            var $feedback = $form.querySelector('.psdsgvo-message'),
                value = values.slice(0, 1);
            if (value.length > 0) {
                var $row = $form.querySelector('tr[data-id="' + value[0] + '"]');
                $row.classList.remove('psdsgvo-status--error');
                $row.classList.add('psdsgvo-status--processing');
                $feedback.setAttribute('style', 'display: none;');
                $feedback.classList.remove('psdsgvo-message--error');
                $feedback.innerHTML = '';
                setTimeout(function () {
                    var request = new XMLHttpRequest();
                    data.data.value = value[0];
                    request.open('POST', ajaxURL);
                    request.setRequestHeader('Content-type', 'application/x-www-form-urlencoded; charset=UTF-8');
                    request.send(_objectToParametersString(data));
                    request.addEventListener('load', function () {
                        if (request.response) {
                            var response = JSON.parse(request.response);
                            $row.classList.remove('psdsgvo-status--processing');
                            if (response.error) {
                                $row.classList.add('psdsgvo-status--error');
                                $feedback.innerHTML = response.error;
                                $feedback.classList.add('psdsgvo-message--error');
                                $feedback.removeAttribute('style');
                            } else {
                                values.splice(0, 1);
                                $row.querySelector('input[type="checkbox"]').remove();
                                $row.classList.add('psdsgvo-status--removed');
                                _doAjax(data, values, $form, 500);
                            }
                        }
                    });
                }, (delay || 0));
            }
        },
        trapFocus = function(element) {
            var focusableEls = element.querySelectorAll('a[href]:not([disabled]), button:not([disabled]), textarea:not([disabled]), input[type="text"]:not([disabled]), input[type="radio"]:not([disabled]), input[type="checkbox"]:not([disabled]), select:not([disabled])'),
                firstFocusableEl = focusableEls[0],
                lastFocusableEl = focusableEls[focusableEls.length - 1],
                KEYCODE_TAB = 9;

            element.addEventListener('keydown', function(e) {
                var isTabPressed = (e.key === 'Tab' || e.keyCode === KEYCODE_TAB);

                if (!isTabPressed) {
                    return;
                }

                if ( e.shiftKey ) /* shift + tab */ {
                    if (document.activeElement === firstFocusableEl) {
                        lastFocusableEl.focus();
                        e.preventDefault();
                    }
                } else /* tab */ {
                    if (document.activeElement === lastFocusableEl) {
                        firstFocusableEl.focus();
                        e.preventDefault();
                    }
                }

            });
        },
        initConsentBar = function () {
            if (consentCookie !== null) {
                return;
            }
            var $consentBar = document.querySelector('.psdsgvo-consent-bar');
            if ($consentBar === null) {
                return;
            }

            // Move consent bar to the be the first element in the <body>
            var $body = document.querySelector('body');
            $body.prepend($consentBar);

            // Show bar
            $consentBar.style.display = 'block';

            var $button = $consentBar.querySelector('.psdsgvo-consent-bar__button');
            if ($button !== null) {
                $button.addEventListener('click', function (e) {
                    e.preventDefault();
                    _saveCookie(consentCookieName, 'accept');
                    window.location.reload(true);
                });
            }
        },
        initConsentModal = function () {
            var $consentModal = document.querySelector('#psdsgvo-consent-modal');
            if ($consentModal === null) {
                return;
            }
            if (typeof MicroModal === 'undefined') {
                return;
            }
            var $modalTrigger = document.querySelector('[data-micromodal-trigger=psdsgvo-consent-modal]');
            trapFocus($consentModal);

            MicroModal.init({
                disableScroll: true,
                disableFocus: true,
                onShow: function() {
                    if ($modalTrigger) {
                        $modalTrigger.setAttribute('aria-expanded', 'true');
                    }
                },
                onClose: function ($consentModal) {
                    var $descriptions = $consentModal.querySelectorAll('.psdsgvo-consent-modal__description'),
                        $buttons = $consentModal.querySelectorAll('.psdsgvo-consent-modal__navigation > a'),
                        $checkboxes = $consentModal.querySelectorAll('input[type="checkbox"]');

                    if ($descriptions.length > 0) {
                        for (var i = 0; i < $descriptions.length; i++) {
                            $descriptions[i].style.display = ((i === 0) ? 'block' : 'none');
                        }
                    }
                    if ($buttons.length > 0) {
                        for (var i = 0; i < $buttons.length; i++) {
                            $buttons[i].classList.remove('psdsgvo-button--active');
                        }
                    }
                    if ($checkboxes.length > 0) {
                        for (var i = 0; i < $checkboxes.length; i++) {
                            $checkboxes[i].checked = false;
                        }
                    }
                    if ($modalTrigger) {
                        $modalTrigger.setAttribute('aria-expanded', 'false');
                    }
                }
            });

            var $settingsLink = document.querySelector('.psdsgvo-consents-settings-link');
            if ($settingsLink !== null) {
                $settingsLink.addEventListener('click', function (e) {
                    e.preventDefault();
                    MicroModal.show('psdsgvo-consent-modal');
                });
            }

            var $buttons = $consentModal.querySelectorAll('.psdsgvo-consent-modal__navigation > a');
            if ($buttons.length > 0) {
                var $descriptions = $consentModal.querySelectorAll('.psdsgvo-consent-modal__description');
                for (var i = 0; i < $buttons.length; i++) {
                    $buttons[i].addEventListener('click', function (e) {
                        e.preventDefault();
                        var $target = $consentModal.querySelector('.psdsgvo-consent-modal__description[data-target="' + this.dataset.target + '"]');
                        if ($target !== null) {
                            for (var i = 0; i < $buttons.length; i++) {
                                $buttons[i].classList.remove('psdsgvo-button--active');
                            }
                            this.classList.add('psdsgvo-button--active');
                            for (var i = 0; i < $descriptions.length; i++) {
                                $descriptions[i].style.display = 'none';
                            }
                            $target.style.display = 'block';
                        }
                    });
                }
            }

            var $buttonSave = $consentModal.querySelector('.psdsgvo-button--secondary');
            if ($buttonSave !== null) {
                $buttonSave.addEventListener('click', function (e) {
                    e.preventDefault();
                    var $checkboxes = $consentModal.querySelectorAll('input[type="checkbox"]'),
                        checked = [];

                    if ($checkboxes.length > 0) {
                        for (var i = 0; i < $checkboxes.length; i++) {
                            var $checkbox = $checkboxes[i],
                                value = $checkbox.value;
                            if ($checkbox.checked === true && !isNaN(value)) {
                                checked.push(parseInt(value));
                            }
                        }
                        if (checked.length > 0) {
                            _saveCookie(consentCookieName, checked);
                        } else {
                            _saveCookie(consentCookieName, 'decline');
                        }
                    } else {
                        // Accept all
                        _saveCookie(consentCookieName, 'accept');
                    }

                    window.location.reload(true);
                });
            }
        },
        initLoadConsents = function () {
            if (typeof postscribe === 'undefined') {
                return;
            }

            /**
             * @param placement
             * @returns {HTMLHeadElement | Element | string | HTMLElement}
             * @private
             */
            var _getTargetByPlacement = function (placement) {
                    var output;
                    switch (placement) {
                        case 'head' :
                            output = document.head;
                            break;
                        case 'body' :
                            output = document.querySelector('#psdsgvo-consent-body');
                            if (output === null) {
                                var bodyElement = document.createElement('div');
                                bodyElement.id = 'psdsgvo-consent-body';
                                document.body.prepend(bodyElement);
                                output = '#' + bodyElement.id;
                            }
                            break;
                        case 'footer' :
                            output = document.body;
                            break;
                    }
                    return output;
                },
                /**
                 * @param consent
                 */
                loadConsent = function (consent) {
                    var target = _getTargetByPlacement(consent.placement);
                    if (target !== null) {
                        postscribe(target, consent.content);
                    }
                };

            // Load consents by cookie
            var ids = (consentCookie !== null && consentCookie !== 'accept') ? consentCookie.split(',') : [];
            for (var i = 0; i < consents.length; i++) {
                if (consents.hasOwnProperty(i)) {
                    var consent = consents[i];
                    if (ids.indexOf(consent.id) >= 0 || consent.required || consentCookie === 'accept') {
                        loadConsent(consent);
                    }
                }
            }
        },
        initFormAccessRequest = function () {
            var $formAccessRequest = document.querySelector('.psdsgvo-form--access-request');
            if ($formAccessRequest === null) {
                return;
            }

            var $feedback = $formAccessRequest.querySelector('.psdsgvo-message'),
                $emailAddress = $formAccessRequest.querySelector('#psdsgvo-form__email'),
                $consent = $formAccessRequest.querySelector('#psdsgvo-form__consent');

            $formAccessRequest.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!ajaxLoading) {
                    ajaxLoading = true;
                    $feedback.style.display = 'none';
                    $feedback.classList.remove('psdsgvo-message--success', 'psdsgvo-message--error');
                    $feedback.innerHTML = '';

                    var data = {
                            action: 'psdsgvo_process_action',
                            security: ajaxSecurity,
                            data: {
                                type: 'access_request',
                                email: $emailAddress.value,
                                consent: $consent.checked
                            }
                        },
                        request = new XMLHttpRequest();

                    data = _objectToParametersString(data);
                    request.open('POST', ajaxURL, true);
                    request.setRequestHeader('Content-type', 'application/x-www-form-urlencoded; charset=UTF-8');
                    request.send(data);
                    request.addEventListener('load', function () {
                        if (request.response) {
                            var response = JSON.parse(request.response);
                            if (response.message) {
                                $formAccessRequest.reset();
                                $emailAddress.blur();
                                $feedback.innerHTML = response.message;
                                $feedback.classList.add('psdsgvo-message--success');
                                $feedback.removeAttribute('style');
                            }
                            if (response.error) {
                                $emailAddress.focus();
                                $feedback.innerHTML = response.error;
                                $feedback.classList.add('psdsgvo-message--error');
                                $feedback.removeAttribute('style');
                            }
                        }
                        ajaxLoading = false;
                    });
                }
            });
        },
        initFormDeleteRequest = function () {
            var $formDeleteRequest = document.querySelectorAll('.psdsgvo-form--delete-request');
            if ($formDeleteRequest.length < 1) {
                return;
            }

            $formDeleteRequest.forEach(function ($form) {
                var $selectAll = $form.querySelector('.psdsgvo-select-all');

                $form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var $this = e.target,
                        $checkboxes = $this.querySelectorAll('.psdsgvo-checkbox'),
                        data = {
                            action: 'psdsgvo_process_action',
                            security: ajaxSecurity,
                            data: {
                                type: 'delete_request',
                                token: psdsgvoData.token,
                                settings: JSON.parse($this.dataset.psdsgvo)
                            }
                        };
                    $selectAll.checked = false;
                    _doAjax(data, _getValuesByCheckedBoxes($checkboxes), $this);
                });

                if ($selectAll !== null) {
                    $selectAll.addEventListener('change', function (e) {
                        var $this = e.target,
                            checked = $this.checked,
                            $checkboxes = $form.querySelectorAll('.psdsgvo-checkbox');
                        $checkboxes.forEach(function (e) {
                            e.checked = checked;
                        });
                    });
                }
            });
        };

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof consents === 'object' && consents.length > 0) {
            consentCookieName = ((isMultisite) ? blogId + '-psdsgvo-consent-' : 'psdsgvo-consent-') + psdsgvoData.consentVersion;
            consentCookie = _readCookie(consentCookieName);
            initConsentBar();
            initConsentModal();
            initLoadConsents();
        }
        initFormAccessRequest();
        initFormDeleteRequest();
        initEmbedConsent();
    });
    
    /**
     * Handle embed consent buttons
     */
    var initEmbedConsent = function() {
        var embedButtons = document.querySelectorAll('.psdsgvo-embed-consent-btn');
        
        embedButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var embedType = this.getAttribute('data-embed-type');
                var placeholder = this.closest('.psdsgvo-embed-placeholder');
                var embedUrl = placeholder.getAttribute('data-embed-url');
                
                // Set cookie for this embed type (365 days)
                var cookieName = 'psdsgvo_embed_' + embedType;
                _saveCookie(cookieName, '1', 365);
                
                // Replace placeholder with actual embed
                var iframe = document.createElement('iframe');
                iframe.src = embedUrl;
                iframe.width = '100%';
                iframe.height = '400';
                iframe.frameBorder = '0';
                iframe.allowFullscreen = true;
                
                placeholder.parentNode.replaceChild(iframe, placeholder);
            });
        });
    };
    
})(window, document);
