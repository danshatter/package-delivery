<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Bus;
use App\Rules\ValidMessageRecipient;
use App\Models\Message;
use App\Jobs\MarkAsDelivered;

class MessageController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get all sent messages and received messages of the authenticated user
     */
    public function index()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        auth()->user()->load([
            'receivedMessages' => fn($query) => $query->latest()->paginate($perPage),
            'sentMessages' => fn($query) => $query->latest()->paginate($perPage),
            'receivedMessages.sender',
            'sentMessages.receiver'
        ]);

        return $this->sendSuccess('Request successful', [
            'received_messages' => auth()->user()->receivedMessages,
            'sent_messages' => auth()->user()->sentMessages
        ]);
    }

    /**
     * Create a message
     */
    public function store()
    {
        $validator = validator()->make(request()->all(), [
            'to' => ['required', 'bail', 'exists:users,id', new ValidMessageRecipient(auth()->user())],
            'message' => ['required']
        ], [
            'to.exists' => 'This user does not exist'
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        // Create the message
        $message = auth()->user()->sentMessages()->create($validator->validated());

        /**
         * Possible firebase notification for receiver
         */

        return $this->sendSuccess('Message sent successfully', $message->load(['receiver']), 201);
    }

    /**
     * Get a message (For the sender only)
     */
    public function show($messageId)
    {
        $message = auth()->user()->sentMessages()->with(['receiver'])->find($messageId);

        if (is_null($message)) {
            return $this->sendErrorMessage('Message not found', 404);
        }

        return $this->sendSuccess('Request successful', $message);
    }

    /**
     * Get all unread messages (For the recipient only)
     */
    public function unread()
    {
        $messages = auth()->user()->receivedMessages()->with(['sender'])->unread()->get();

        /**
         * Mark messages as delivered
         */
        $batch = Bus::batch([])->dispatch();

        $messages->each(fn($message) => $batch->add(new MarkAsDelivered($message)));

        return $this->sendSuccess('Request successful', $messages);
    }

    /**
     * Get all undelivered messages (For the sender only)
     */
    public function undelivered()
    {
        $messages = auth()->user()->sentMessages()->with(['receiver'])->undelivered()->get();

        return $this->sendSuccess('Request successful', $messages);
    }

    /**
     * Mark a message as read (For the recipient)
     */
    public function read($messageId)
    {
        $message = auth()->user()->receivedMessages()->find($messageId);

        if (is_null($message)) {
            return $this->sendErrorMessage('Message not found', 404);
        }

        if ($message->isRead()) {
            return $this->sendSuccess('Message has already been read');
        }

        $message->markAsRead();

        return $this->sendSuccess('Message successfully marked as read');
    }

    /**
     * Mark a message as delivered (For the recipient)
     */
    public function delivered($messageId)
    {
        $message = auth()->user()->receivedMessages()->find($messageId);

        if (is_null($message)) {
            return $this->sendErrorMessage('Message not found', 404);
        }

        if ($message->isDelivered()) {
            return $this->sendSuccess('Message has already been delivered');
        }

        $message->markAsDelivered();

        return $this->sendSuccess('Message successfully marked as delivered');
    }

}
