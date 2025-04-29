<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportMessage;
use App\Models\User;
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

    /**
     * Create a new controller instance.
     *
     * @param TelegramService $telegramService
     * @return void
     */
    public function __construct(TelegramService $telegramService)
    {
        $this->middleware('auth')->except(['receiveAdminMessage']);
        $this->telegramService = $telegramService;
        
        // Initialize Pusher
        $this->pusher = new Pusher(
            config('broadcasting.connections.pusher.key'),
            config('broadcasting.connections.pusher.secret'),
            config('broadcasting.connections.pusher.app_id'),
            config('broadcasting.connections.pusher.options')
        );
    }

    /**
     * Display the support chat interface.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get or create active ticket for the user
        $ticket = SupportTicket::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();
            
        if (!$ticket) {
            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'status' => 'open',
                'ticket_number' => $this->generateTicketNumber(),
                'subject' => 'Общий вопрос',
            ]);

            SupportMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => null,
                'message' => 'Добрый день! Опишите, пожалуйста, ваш вопрос, и мы постараемся помочь вам как можно скорее.',
                'is_from_admin' => true,
            ]);
        }
        
        // Get messages for this ticket
        $messages = SupportMessage::where('ticket_id', $ticket->id)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();
            
        return view('support.chat', compact('ticket', 'messages'));
    }
    
    /**
     * Store a new support message.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
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
        
        // Create the message in the database
        $message = SupportMessage::create([
            'ticket_id' => $ticketId,
            'user_id' => $user->id,
            'message' => $messageText,
            'is_from_admin' => false,
        ]);
        
        // Load the ticket and user relationship
        $message->load('user');
        
        // Send the message to Telegram
        $ticketNumber = SupportTicket::find($ticketId)->ticket_number;
        $this->telegramService->sendSupportMessage($user->id, $ticketNumber, $messageText);
        
        // Broadcast the message to Pusher
        $this->broadcastMessage($message);
        
        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
    
    /**
     * Receive message from admin (via Webhook from Telegram).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function receiveAdminMessage(Request $request)
    {
        // Verify the request is from Telegram using token
        // \Log::info('HEADER: '.$request->header('X-Telegram-Bot-Api-Secret-Token'));
        if ($request->header('X-Telegram-Bot-Api-Secret-Token') !== config('services.telegram.webhook_token')) {
            return response()->json(['error' => 'Unauthorized'], 200);
        }
        
        $data = $request->all();

        \Log::info('data:', $data);
        
        // Check if this is a message from our Bot
        if (!isset($data['message']) || !isset($data['message']['text'])) {
            return response()->json(['status' => 'not a message']);
        }
        
        // Parse the message from Telegram
        $messageText = $data['message']['text'];
        $fromId = $data['message']['from']['id'];

        if ($messageText === '/start') {
            // Отправить приветственное сообщение обратно
            $this->telegramService->sendMessage(
                "Добро пожаловать в систему поддержки! Вы можете отвечать на тикеты, используя формат:\n" .
                "#TICKET-XXX REPLY: ваш ответ"
            );
            return response()->json(['status' => 'welcome message sent']);
        }
        
        // Make sure this is from an authorized admin
        if (!in_array($fromId, config('services.telegram.admin_user_ids', []))) {
            return response()->json(['error' => 'Unauthorized sender'], 200);
        }
        
        // Extract user ID and ticket number from the message
        // Format expected: #TICKET-123 REPLY: your message here
        if (!preg_match('/#([A-Z0-9-]+)\s+REPLY:\s+(.+)/s', $messageText, $matches)) {
            return response()->json(['error' => 'Invalid message format'], 200);
        }
        
        $ticketNumber = $matches[1];
        $replyText = $matches[2];
        
        // Find the ticket
        $ticket = SupportTicket::where('ticket_number', $ticketNumber)->first();
        if (!$ticket) {
            return response()->json(['error' => 'Ticket not found'], 200);
        }
        
        // Create admin message
        $message = SupportMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => null, // null for admin
            'message' => $replyText,
            'is_from_admin' => true,
        ]);
        
        // Load user relation (will be null for admin message)
        $message->load('user');
        
        // Broadcast the message to Pusher
        $this->broadcastMessage($message);
        
        return response()->json(['status' => 'success']);
    }
    
    /**
     * Close the support ticket.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function closeTicket($id)
    {
        $user = Auth::user();
        
        $ticket = SupportTicket::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();
            
        $ticket->status = 'closed';
        $ticket->save();
        
        // Notify admin that ticket was closed
        $this->telegramService->sendTicketClosed($user->id, $ticket->ticket_number);
        
        return redirect()->route('dashboard')->with('success', 'Ваше обращение было закрыто.');
    }
    
    /**
     * Get all messages for a ticket.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
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
            'messages' => $messages
        ]);
    }
    
    /**
     * Generate a unique ticket number.
     *
     * @return string
     */
    private function generateTicketNumber()
    {
        $prefix = 'TICKET';
        $random = strtoupper(Str::random(6));
        $ticketNumber = "{$prefix}-{$random}";
        
        // Make sure it's unique
        while (SupportTicket::where('ticket_number', $ticketNumber)->exists()) {
            $random = strtoupper(Str::random(6));
            $ticketNumber = "{$prefix}-{$random}";
        }
        
        return $ticketNumber;
    }
    
    /**
     * Broadcast the message using Pusher.
     *
     * @param  \App\Models\SupportMessage  $message
     * @return void
     */
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