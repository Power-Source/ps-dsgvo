(function ($, window, document, undefined) {
    'use strict';

    var ajaxURL = psdsgvoData.ajaxURL,
        ajaxSecurity = psdsgvoData.ajaxSecurity,
        delay = (function () {
            var timer = 0;
            return function (callback, ms) {
                clearTimeout(timer);
                timer = setTimeout(callback, ms);
            };
        })(),
        $psdsgvo = $('.psdsgvo'),
        $checkbox = $('input[type="checkbox"]', $('.psdsgvo-checkbox, .psdsgvo-setting', $psdsgvo)),
        $selectAll = $('.psdsgvo-select-all', $psdsgvo),
        $formProcessDeleteRequests = $('.psdsgvo-form--process-delete-requests'),
        /**
         * @param $checkboxes
         * @returns {Array}
         * @private
         */
        _getValuesByCheckedBoxes = function ($checkboxes) {
            var output = [];
            if ($checkboxes.length) {
                $checkboxes.each(function () {
                    var $this = $(this),
                        value = $this.val();
                    if ($this.is(':checked') && value > 0) {
                        output.push(value);
                    }
                });
            }
            return output;
        },
        /**
         * @param $element
         * @returns {*}
         * @private
         */
        _getElementAjaxData = function ($element) {
            var data = $element.data();
            if (!data.option) {
                data.option = $element.attr('name');
            }
            if ($element.is('input')) {
                data.value = $element.val();
                if ($element.is('input[type="checkbox"]')) {
                    data.enabled = ($element.is(':checked'));
                }
            }
            return data;
        },
        /**
         * @param $element
         * @private
         */
        _doProcessSettings = function ($element) {
            $element.addClass('processing');
            var $checkboxContainer = $element.closest('.psdsgvo-checkbox'),
                $checkboxData = ($checkboxContainer.length) ? $checkboxContainer.next('.psdsgvo-checkbox-data') : false;
            $.ajax({
                url: ajaxURL,
                type: 'POST',
                dataType: 'JSON',
                data: {
                    action: 'psdsgvo_process_settings',
                    security: ajaxSecurity,
                    data: _getElementAjaxData($element)
                },
                success: function (response) {
                    if (response) {
                        if (response.error) {
                            if ($element.is(':checked')) {
                                $element.prop('checked', false);
                            }
                            $element.addClass('alert');
                        } else {
                            if ($checkboxData.length) {
                                if ($element.is(':checked')) {
                                    $checkboxData.stop(true, true).slideDown('fast');
                                } else {
                                    $checkboxData.stop(true, true).slideUp('fast');
                                }
                            }
                            if (response.redirect) {
                                document.location.href = currentPage;
                            }
                        }
                    }
                },
                complete: function () {
                    $element.removeClass('processing');
                    delay(function () {
                        $element.removeClass('alert');
                    }, 2000);
                }
            });
        },
        _ajax = function (values, $form, delay) {
            var value = values.slice(0, 1);
            if (value.length > 0) {
                var $feedback = $('.psdsgvo-message', $form),
                    $row = $('tr[data-id="' + value[0] + '"]', $form);
                $row.removeClass('psdsgvo-status--error');
                $row.addClass('psdsgvo-status--processing');
                $feedback.attr('style', 'display: none;');
                $feedback.removeClass('psdsgvo-message--error');
                $feedback.empty();
                setTimeout(function () {
                    $.ajax({
                        url: ajaxURL,
                        type: 'POST',
                        dataType: 'JSON',
                        data: {
                            action: 'psdsgvo_process_delete_request',
                            security: ajaxSecurity,
                            data: {
                                id: value[0]
                            }
                        },
                        success: function (response) {
                            if (response) {
                                $row.removeClass('psdsgvo-status--processing');
                                if (response.error) {
                                    $row.addClass('psdsgvo-status--error');
                                    $feedback.html(response.error);
                                    $feedback.addClass('psdsgvo-message--error');
                                    $feedback.removeAttr('style');
                                } else {
                                    values.splice(0, 1);
                                    $('input[type="checkbox"]', $row).remove();
                                    $row.addClass('psdsgvo-status--removed');
                                    $('.dashicons-no', $row).removeClass('dashicons-no').addClass('dashicons-yes');
                                    _ajax(values, $form, 500);

                                }
                            }
                        }
                    });
                }, (delay || 0));
            }
        },
        initCheckboxes = function () {
            if (!$checkbox.length) {
                return;
            }
            $checkbox.on('change', function (e) {
                if ($(this).data('option')) {
                    e.preventDefault();
                    _doProcessSettings($(this));
                }
            });
        },
        initSelectAll = function () {
            if (!$selectAll.length) {
                return;
            }
            $selectAll.on('change', function () {
                var $this = $(this),
                    checked = $this.is(':checked'),
                    $checkboxes = $('tbody input[type="checkbox"]', $this.closest('table'));
                $checkboxes.prop('checked', checked);
            });
        },
        initProcessDeleteRequests = function () {
            if (!$formProcessDeleteRequests.length) {
                return;
            }
            $formProcessDeleteRequests.on('submit', function (e) {
                e.preventDefault();
                var $this = $(this),
                    $checkboxes = $('.psdsgvo-checkbox', $this);
                $selectAll.prop('checked', false);
                _ajax(_getValuesByCheckedBoxes($checkboxes), $this);
            });
        };

    $(function () {
        if (!$psdsgvo.length) {
            return;
        }
        initCheckboxes();
        initSelectAll();
        initProcessDeleteRequests();

        var $snippet = document.getElementById('psdsgvo_snippet');
        if ($snippet !== null) {
            var editor = CodeMirror.fromTextArea($snippet, {
                mode: 'text/html',
                lineNumbers: true,
                matchBrackets: true,
                indentUnit: 4
            });
        }
    });
})(jQuery, window, document);