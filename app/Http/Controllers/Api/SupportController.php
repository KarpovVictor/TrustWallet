<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportMessage;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Pusher\Pusher;

class SupportController extends Controller
{
    protected $telegramService;
    protected $pusher;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
        
        $this->pusher = new Pusher(
            config('broadcasting.connections.pusher.key'),
            config('broadcasting.connections.pusher.secret'),
            config('broadcasting.connections.pusher.app_id'),
            config('broadcasting.connections.pusher.options')
        );
    }

    public function index()
    {
        $user = Auth::user();
        
        $ticket = SupportTicket::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();
            
        if (!$ticket) {
            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'status' => 'open',
                'ticket_number' => $this->generateTicketNumber(),
                'subject' => 'General question',
            ]);

            SupportMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => null,
                'message' => 'Hello! Please describe your question and we will try to help you as soon as possible.',
                'is_from_admin' => true,
            ]);
        }
        
        $messages = SupportMessage::where('ticket_id', $ticket->id)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => [
                'ticket' => $ticket,
                'messages' => $messages
            ]
        ]);
    }
    
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'ticket_id' => 'required|exists:support_tickets,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = Auth::user();
        $ticketId = $request->input('ticket_id');
        $messageText = $request->input('message');
        
        $message = SupportMessage::create([
            'ticket_id' => $ticketId,
            'user_id' => $user->id,
            'message' => $messageText,
            'is_from_admin' => false,
        ]);
        
        $message->load('user');
        
        $ticketNumber = SupportTicket::find($ticketId)->ticket_number;
        $this->telegramService->sendSupportMessage($user->id, $ticketNumber, $messageText);
        
        $this->broadcastMessage($message);
        
        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }
    
    public function receiveAdminMessage(Request $request)
    {
        if ($request->header('X-Telegram-Bot-Api-Secret-Token') !== config('services.telegram.webhook_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $data = $request->all();
        
        if (!isset($data['message']) || !isset($data['message']['text'])) {
            return response()->json(['status' => 'not a message']);
        }
        
        $messageText = $data['message']['text'];
        $fromId = $data['message']['from']['id'];

        if ($messageText === '/start') {
            $this->telegramService->sendMessage(
                "Добро пожаловать в систему поддержки! Вы можете отвечать на тикеты, используя формат:\n" .
                "#TICKET-XXX REPLY: ваш ответ"
            );
            return response()->json(['status' => 'welcome message sent']);
        }
        
        if (!in_array($fromId, config('services.telegram.admin_user_ids', []))) {
            return response()->json(['error' => 'Unauthorized sender'], 403);
        }
        
        if (!preg_match('/#([A-Z0-9-]+)\s+REPLY:\s+(.+)/s', $messageText, $matches)) {
            return response()->json(['error' => 'Invalid message format'], 400);
        }
        
        $ticketNumber = $matches[1];
        $replyText = $matches[2];
        
        $ticket = SupportTicket::where('ticket_number', $ticketNumber)->first();
        if (!$ticket) {
            return response()->json(['error' => 'Ticket not found'], 404);
        }
        
        $message = SupportMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => null,
            'message' => $replyText,
            'is_from_admin' => true,
        ]);
        
        $message->load('user');
        
        $this->broadcastMessage($message);
        
        return response()->json(['status' => 'success']);
    }
    
    public function closeTicket($id)
    {
        $user = Auth::user();
        
        $ticket = SupportTicket::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();
            
        $ticket->status = 'closed';
        $ticket->save();
        
        $this->telegramService->sendTicketClosed($user->id, $ticket->ticket_number);
        
        return response()->json([
            'success' => true,
            'message' => 'Your request has been closed.'
        ]);
    }
    
    public function getMessages($id)
    {
        $user = Auth::user();
        
        $ticket = SupportTicket::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();
            
        $messages = SupportMessage::where('ticket_id', $ticket->id)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }
    
    private function generateTicketNumber()
    {
        $prefix = 'TICKET';
        $random = strtoupper(Str::random(6));
        $ticketNumber = "{$prefix}-{$random}";
        
        while (SupportTicket::where('ticket_number', $ticketNumber)->exists()) {
            $random = strtoupper(Str::random(6));
            $ticketNumber = "{$prefix}-{$random}";
        }
        
        return $ticketNumber;
    }
    
    private function broadcastMessage(SupportMessage $message)
    {
        $this->pusher->trigger('private-ticket.' . $message->ticket_id, 'new-message', [
            'message' => [
                'id' => $message->id,
                'ticket_id' => $message->ticket_id,
                'user_id' => $message->user_id,
                'message' => $message->message,
                'is_from_admin' => $message->is_from_admin,
                'created_at' => $message->created_at,
                'user' => $message->user
            ]
        ]);
    }
}