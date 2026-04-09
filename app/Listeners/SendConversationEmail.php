<?php

namespace App\Listeners;

use App\Events\ConversationSaved;
use App\Mail\ConversationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

use function Laravel\Prompts\info;

class SendConversationEmail
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        info('Listener Fired!');
    }

    /**
     * Handle the event.
     */
    public function handle(ConversationSaved $event): void
    {
        $content['subject'] = $event->conversation->subject;
        $content['message'] = $event->conversation->message;
        $sendToUsers = $event->conversation->sentTo();
        Log::info('Users to send email to: ',  [$sendToUsers]);

        foreach ($sendToUsers as $user) {
            Mail::to($user?->email)->send(new ConversationMail($user, $content));
            Log::info('Conversation - Email sent by: ' . $event->conversation->sentBy->email . ' Sent to: ' . $user->email);
        }

        // update the conversation record to indicate that the email has been sent
        $event->conversation->update(['status' => 'sent']);
        Log::info('Conversation email sent and conversation status updated to sent.', ['conversation_id' => $event->conversation->id]);
    }
}
