$( document ).ready(function() {
    $('#picker').colpick({
        layout: 'hex',
        submit: 0,
        colorScheme: 'dark',
        onChange: function (hsb, hex, rgb, el, bySetColor) {
            $(el).css('border-color', '#' + hex);

            // Add hex code to input value
            $('#picker').val(hex);

            // Fill the text box just if the color was set using the picker, and not the colpickSetColor function.
            if (!bySetColor) $(el).val(hex);
        }
    }).keyup(function () {
        $(this).colpickSetColor(this.value);
    });
});