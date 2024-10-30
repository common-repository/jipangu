<?php
/**
 * Script for Settings view
 */
?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rest_nonce = document.querySelector('#_wpnonce_rest').value;
        const rest_url = "<?php echo esc_js( rest_url( 'jipangu/v1' ) ); ?>";

        /**
         * Show the result of an action.
         * @param {string} message
         * @returns {void}
         */
        const showResult = (message) => {
            (function ($) {
                if ($('#action-result-dialog').length > 0) {
                    $('#action-result-dialog').find('p').html(message);
                    $('#action-result-dialog').dialog('open');
                } else {
                    alert(message);
                }
            })(jQuery);
        };

        /**
         * Show the spinner.
         * @returns {void}
         */
        const showSpinner = () => {
            const spinner = document.querySelector('.spinner');
            if (spinner) {
                spinner.classList.add('is-active');
                document.querySelector('.wrap').querySelectorAll('input,select').forEach(function(element) {
                    element.disabled = true;
                });
            }
        };

        /**
         * Hide the spinner.
         * @returns {void}
         */
        const hideSpinner = (snippet_id, snippet_src) => {
            const spinner = document.querySelector('.spinner');
            if (spinner) {
                spinner.classList.remove('is-active');
                document.querySelector('.wrap').querySelectorAll('input,select').forEach(function(element) {
                    element.disabled = false;
                });
            }
        };

        /**
         * Fetch data using POST method.
         * @param {string} path
         * @param {object} data
         * @returns {Promise<object>}
         */
        const postFetch = async function(path, data) {
            const response = await fetch(rest_url + path, {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': rest_nonce,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            const body = await response.json();
            if (!response.ok || body?.success === false ) {
                throw new Error(body.message);
            }
            return body;
        };

        /**
         * Toggle the localhost field.
         * @returns {void}
         */
        const toggleLocalhostField = function() {
            const jipangu_server = document.querySelector('#jipangu_server').value;
            const jipangu_localhost_port = document.querySelector('#jipangu_localhost_port');
            if (jipangu_server === 'localhost') {
                jipangu_localhost_port.closest('tr').style.display = '';
            } else {
                jipangu_localhost_port.closest('tr').style.display = 'none';
                jipangu_localhost_port.value = '';
            }
        }

        /**
         * Initialize the script.
         * @returns {void}
         */
        const initialize = function () {
            toggleLocalhostField();
        }

        document.querySelector('#sync-with-jipangu').addEventListener('click', async function() {
            const jipangu_token = document.querySelector('#jipangu_token').value;
            if (!jipangu_token) {
                showResult("<?php esc_html_e( 'Please enter the Jipangu Token.', 'jipangu' ); ?>");
                return;
            }

            showSpinner();
            try {

                const response = await postFetch('/sync_with_jipangu', { jipangu_token });

                showResult("<?php esc_html_e( 'Synced Successfully.', 'jipangu' ); ?>");

            } catch (error) {
                showResult(error.message);
            } finally {
                hideSpinner();
            }
        });

        document.querySelector('#jipangu_server').addEventListener('change', function() {
            toggleLocalhostField();
        });

        initialize();
    });
</script>
