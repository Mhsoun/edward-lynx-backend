@include('includes/base-navbar', ['user' => isset($surveyOwner) ? $surveyOwner : null, 'showMenu' => false])