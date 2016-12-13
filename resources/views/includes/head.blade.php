<meta charset="utf-8">
<meta name="description" content="">
<meta name="author" content="Scotch">
<title>Edward Lynx</title>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
<link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/main.css') }}">
<link rel="stylesheet" href="{{ asset('css/snippet.css') }}">
<link rel="stylesheet" href="{{ asset('css/colpick.css') }}">

<script type="text/javascript">
	//We need to set the CSRF token else the request will fail
	   $.ajaxSetup({
            processData: false,
            beforeSend: function(req, settings) {
                var baseUrl = "{{ url('/') }}"

                if (settings.url[0] != "/") {
                    baseUrl += "/";
                }

                settings.url = baseUrl + settings.url;
                settings.data._token = "{{ csrf_token() }}";
                settings.data = jQuery.param(settings.data, false);
                return true;
            }
        });
</script>
