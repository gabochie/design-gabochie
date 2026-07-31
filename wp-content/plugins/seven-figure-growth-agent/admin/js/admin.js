
jQuery(document).ready(function($) {
    // Save Settings AJAX
    $('#growth-agent-settings-form').on('submit', function(e) {
        e.preventDefault();
        var formData = {};
        $(this).serializeArray().forEach(function(item) {
            formData[item.name] = item.value;
        });

        $('#btn-save-settings').prop('disabled', true).text('Saving...');

        $.ajax({
            url: growthAgentData.apiUrl + '/settings',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', growthAgentData.nonce);
            },
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                $('#settings-status-message').html('<div class="notice notice-success inline"><p>Settings saved successfully!</p></div>');
                $('#btn-save-settings').prop('disabled', false).text('Save Settings');
            },
            error: function(err) {
                $('#settings-status-message').html('<div class="notice notice-error inline"><p>Error saving settings.</p></div>');
                $('#btn-save-settings').prop('disabled', false).text('Save Settings');
            }
        });
    });

    // Run Website Scan AJAX
    $('#growth-agent-scan-form').on('submit', function(e) {
        e.preventDefault();
        var formData = {};
        $(this).serializeArray().forEach(function(item) {
            formData[item.name] = item.value;
        });

        $('#scan-loading-spinner').show();
        $('#btn-run-scan').prop('disabled', true);

        $.ajax({
            url: growthAgentData.apiUrl + '/scan',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', growthAgentData.nonce);
            },
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                $('#scan-loading-spinner').hide();
                $('#btn-run-scan').prop('disabled', false);
                alert('Intelligence Scan Completed Successfully!');
            },
            error: function(err) {
                $('#scan-loading-spinner').hide();
                $('#btn-run-scan').prop('disabled', false);
                alert('Scan finished with status update.');
            }
        });
    });

    // Audit Tab Switching
    $('.audit-tab').on('click', function() {
        $('.audit-tab').removeClass('active');
        $(this).addClass('active');
    });
});
