<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use App\Models\DevelopmentPlanGoal;
use App\Services\Firebase\FirebaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\Firebase\FirebaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class DevelopmentPlanGoalReminder extends Notification
{
    use Queueable;

    /**
     * Goal title.
     * 
     * @var string
     */
    public $goal;

    /**
     * Due Date.
     * 
     * @var Carbon\Carbon
     */
    public $dueDate;

    /**
     * ID of the development plan.
     * 
     * @var int
     */
    public $devPlanId;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(DevelopmentPlanGoal $goal)
    {
        $this->goal = $goal->title;
        $this->dueDate = $goal->dueDate;
        $this->devPlanId = $goal->developmentPlan->id;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', FirebaseChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(trans('developmentPlan.reminderTitle', [
                'goal'  => $this->goal
            ]))
            ->line(trans('developmentPlan.reminderMessage', [
                'recipient' => $notifiable->name,
                'goal'      => $this->goal,
                'due'       => $this->dueDate->diffForHumans()
            ]));
    }

    /**
     * Get the firebase representation of the notification.
     * 
     * @param   mixed   $notifiable
     * @return  App\Services\Firebase\FirebaseNotification
     */
    public function toFirebase($notifiable)
    {
        return (new FirebaseNotification)
            ->title(trans('developmentPlan.reminderTitle', [
                'goal'  => $this->goal
            ]))
            ->body(trans('developmentPlan.reminderMessage', [
                'recipient' => $notifiable->name,
                'goal'      => $this->goal,
                'due'       => $this->dueDate->diffForHumans()
            ]))
            ->data([
                'type'  => 'dev-plan',
                'id'    => $this->devPlanId
            ])->to($notifiable->deviceTokens());
    }
}
