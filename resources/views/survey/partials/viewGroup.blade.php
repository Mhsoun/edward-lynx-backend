<h3>{{ Lang::get('surveys.roles') }}</h3>
<table class="table">
    <col>
    <col style="width: 30em">
    <tr>
        <th>{{ Lang::get('surveys.status') }}</th>
        <th>{{ Lang::get('surveys.recipientName') }}</th>
        <th>{{ Lang::get('surveys.numberOfCompleted') }}</th>
    </tr>

    @foreach ($survey->rolesWithMembers() as $roleGroup)
        <?php
            $viewLink = action('SurveyController@showRole', [
                'id' => $survey->id,
                'roleId' => $roleGroup->id,
            ]);

            $statusText = "";
            $hasBounced = $roleGroup->members->filter(function ($member) {
                return $member->bounced;
            })->count() > 0;

            if ($hasBounced) {
                $statusText = 'alert-warning';
            }
        ?>
        <tr class="{{ $statusText }}">
            <td>
                @if ($hasBounced)
                    <span class="glyphicon glyphicon-warning-sign" title="{{ Lang::get('surveys.roleHasBouncedEmails') }}">
                        <span class="status" style="display: none">error</span>
                    </span>
                @else
                    <span class="status" style="display: none">no</span>
                @endif
            </td>
            <td>
                <a href="{{ $viewLink }}">
                    {{ $roleGroup->name }} {{ $roleGroup->toEvaluate ? ' (' . Lang::get('surveys.toEvaluate') . ')' : '' }}
                </a>
            </td>
            <td>{{ count($roleGroup->hasAnswered) }}/{{ count($roleGroup->members) }}</td>
        </tr>
    @endforeach
</table>
