$(document).ready(function() {
    // The form element.
    var $contactFormSubmit = $("#contact-submit");

    // The form fields.
    var $name = $("#name"),
        $email = $("#email"),
        $telephone = $("#telephone"),
        $message = $("#message");

    // The error fields.
    var $name_error = $("#name_error"),
        $email_error = $("#email_error"),
        $telephone_error = $("#telephone_error"),
        $message_error = $("#message_error");

    $contactFormSubmit.click(function(e) {
        $name_error.hide();
        $email_error.hide();
        $telephone_error.hide();
        $message_error.hide();

        e.preventDefault();

        console.log('hallo');

        // The data to send to the api url.
        var data = {
            name: $name.val(),
            email: $email.val(),
            telephone: $telephone.val(),
            message: $message.val()
        };

        // Ajax request to api.
        $.ajax({
            type: "POST",
            url: "/api/contact/submit",
            data: data,
            success: function(data) {

                if(data.hasOwnProperty('errors')) {
                    $.each(data.errors, function(entry) {

                        switch(entry) {
                            case 'email':
                                $email_error.show();
                                break;
                            case 'name':
                                $name_error.show();
                                break;
                            case 'message':
                                $message_error.show();
                                break;
                        }

                    });
                }

                $("#contact-form").trigger('reset');

                $("#success_message").show();
            },
            dataType: "json"
        });
    });

});