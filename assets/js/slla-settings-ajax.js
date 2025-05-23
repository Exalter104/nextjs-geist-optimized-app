jQuery(document).ready(function($) {
    $('#slla-send-test-email').on('click', function(e) {
        e.preventDefault();

        var $button = $(this);
        $button.prop('disabled', true).text('Sending...');

        $.ajax({
            url: slla_ajax_obj.ajax_url,
            method: 'POST',
            data: {
                action: 'slla_send_test_email',
                nonce: slla_ajax_obj.nonce
            },
            success: function(response) {
                alert('Test email sent (simulated).');
                console.log('Test email response:', response);
                $button.prop('disabled', false).text('Send Test Email');
            },
            error: function() {
                alert('Error sending test email.');
                $button.prop('disabled', false).text('Send Test Email');
            }
        });
    });
});
