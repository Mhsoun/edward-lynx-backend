<?php
    // $currentStep = 0;
    //
    // if ($candidate->hasAnswered()) {
    //     if ($candidate->userReport() == null) {
    //         $currentStep = 1;
    //     } else {
    //         $currentStep = 2;
    //     }
    // }

    $steps = [
        (object)['step' => '1', 'text' => Lang::get('surveys.progressCandidateStep1'), 'active' => $currentStep == 0, 'done' => $currentStep >= 1],
        (object)['step' => '2', 'text' => Lang::get('surveys.progressCandidateStep2'), 'active' => $currentStep == 1, 'done' => $currentStep >= 2],
        (object)['step' => '3', 'text' => Lang::get('surveys.progressCandidateStep3'), 'active' => $currentStep == 2, 'done' => false]
    ];
?>

<div class="stepwizard">
    <div class="stepwizard-row setup-panel">
        @foreach ($steps as $step)
            <div class="stepwizard-step" style="width: 33.33%;">
                <span {{ !$step->done && !$step->active ? 'disabled="disabled"' : '' }}
                      class="progressProcessButton btn {{ $step->active ? 'btn-primary' : 'btn-default' }} btn-circle">
                    {{ $step->step }}
                </span>
                <p>{{ $step->text }}</p>
            </div>
        @endforeach
    </div>
</div>
