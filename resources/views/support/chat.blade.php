@extends('layouts.app')

@section('content')
<div class="container-fluid support-container p-0">
    <div class="support-header d-flex justify-content-between align-items-center p-3">
        <div class="d-flex align-items-center">
            <a href="{{ route('dashboard') }}" class="btn-back me-3">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="header-title">Техническая поддержка</span>
        </div>
        @if($ticket->isOpen())
        <form action="{{ route('support.close-ticket', $ticket->id) }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="btn-action">
                <i class="fas fa-times"></i>
            </button>
        </form>
        @endif
    </div>

    <div class="chat-container">
        <div id="chat-messages" class="chat-messages">
            @foreach($messages as $message)
                <div class="message-wrapper {{ $message->is_from_admin ? 'admin-message' : 'user-message' }}">
                    <div class="message-bubble">
                        <div class="message-text">{{ $message->message }}</div>
                        <div class="message-time">{{ $message->time }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="message-form-container">
            <form id="message-form" class="message-form">
                @csrf
                <input type="hidden" name="ticket_id" value="{{ $ticket->id }}">
                <div class="input-group">
                    <textarea name="message" id="message-input" class="form-control" placeholder="Введите сообщение..." rows="1" required></textarea>
                    <button type="submit" class="btn-send">
                        Send
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Виджет чата поддержки (плавающая кнопка) -->
<div class="support-widget-container">
    <div class="support-widget">
        <div class="support-widget-header">
            <h5>Техническая поддержка</h5>
            <button class="support-widget-close">&times;</button>
        </div>
        <div class="support-widget-body">
            <div class="support-widget-messages"></div>
            <form class="support-widget-form">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Введите сообщение...">
                    <button type="submit" class="btn-send">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <button class="support-widget-button">
        <i class="fas fa-comment-dots"></i>
    </button>
</div>

<style>
    /* Основные стили */
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        background-color: #f5f7fa;
        color: #333;
    }
    
    /* Заголовок чата */
    .support-header {
        background-color: #2962ff;
        color: white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
    }
    
    .header-title {
        font-size: 18px;
        font-weight: 500;
    }
    
    .btn-back, .btn-action {
        background: none;
        border: none;
        color: white;
        font-size: 18px;
        cursor: pointer;
    }
    
    /* Контейнер сообщений */
    .support-container {
        height: 100vh;
        display: flex;
        flex-direction: column;
    }
    
    .chat-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding-top: 60px; /* Высота шапки */
        padding-bottom: 70px; /* Высота формы ввода */
    }
    
    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
    }
    
    /* Стили сообщений */
    .message-wrapper {
        margin-bottom: 16px;
        display: flex;
    }
    
    .user-message {
        justify-content: flex-end;
    }
    
    .admin-message {
        justify-content: flex-start;
    }
    
    .message-bubble {
        max-width: 80%;
        padding: 12px 16px;
        border-radius: 18px;
        position: relative;
    }
    
    .user-message .message-bubble {
        background-color: #2962ff;
        color: white;
        border-radius: 18px 18px 0 18px;
    }
    
    .admin-message .message-bubble {
        background-color: #f2f3f5;
        color: #333;
        border-radius: 18px 18px 18px 0;
    }
    
    .message-text {
        font-size: 15px;
        line-height: 1.4;
    }
    
    .message-time {
        font-size: 11px;
        opacity: 0.7;
        text-align: right;
        margin-top: 4px;
    }
    
    .user-message .message-time {
        color: rgba(255, 255, 255, 0.8);
    }
    
    /* Форма отправки сообщения */
    .message-form-container {
        background-color: white;
        padding: 12px;
        border-top: 1px solid #e5e5e5;
        /* position: fixed; */
        bottom: 0;
        left: 0;
        right: 0;
    }
    
    .message-form .input-group {
        display: flex;
        align-items: center;
        background-color: #f2f3f5;
        border-radius: 24px;
        padding: 4px 8px;
    }
    
    .message-form .form-control {
        border: none;
        background: transparent;
        padding: 8px 12px;
        resize: none;
        max-height: 100px;
        font-size: 15px;
        width: 100%;
    }
    
    .message-form .form-control:focus {
        outline: none;
        box-shadow: none;
    }
    
    .btn-send {
        background-color: #2962ff;
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .btn-send:hover {
        background-color: #1c54e8;
    }
    
    /* Виджет чата (плавающая кнопка) */
    .support-widget-container {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        display: none; /* По умолчанию скрыт на странице чата */
    }
    
    .support-widget-button {
        width: 56px;
        height: 56px;
        border-radius: 28px;
        background-color: #2962ff;
        color: white;
        border: none;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        font-size: 24px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        right: 0;
        bottom: 0;
    }
    
    .support-widget {
        width: 320px;
        height: 400px;
        background-color: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        position: absolute;
        right: 0;
        bottom: 70px;
        display: none;
    }
    
    .support-widget-header {
        padding: 15px;
        background-color: #2962ff;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .support-widget-header h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 500;
    }
    
    .support-widget-close {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
    }
    
    .support-widget-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 15px;
    }
    
    .support-widget-messages {
        flex: 1;
        overflow-y: auto;
        margin-bottom: 15px;
    }
    
    .support-widget-form {
        border-top: 1px solid #e5e5e5;
        padding-top: 15px;
    }
    
    .support-widget-form .input-group {
        display: flex;
        align-items: center;
        background-color: #f2f3f5;
        border-radius: 24px;
        padding: 4px 8px;
    }
    
    .support-widget-form .form-control {
        border: none;
        background: transparent;
        padding: 8px 12px;
        font-size: 14px;
    }
    
    .support-widget-form .form-control:focus {
        outline: none;
        box-shadow: none;
    }
</style>

<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const messagesList = document.getElementById('chat-messages');
        const messageForm = document.getElementById('message-form');
        const messageInput = document.getElementById('message-input');
        const ticketId = {{ $ticket->id }};

        // Автоматическая настройка высоты текстового поля
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Прокрутка чата вниз при загрузке
        scrollToBottom();

        // Инициализация Pusher с публичным каналом для тестирования
        const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
            cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
            encrypted: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            }
        });

        // Подписка на канал
        const channel = pusher.subscribe('private-ticket.' + ticketId);
        
        console.log('Attempting to subscribe to channel:', 'ticket.' + ticketId);
        
        // Диагностика подписки
        channel.bind('pusher:subscription_succeeded', function() {
            console.log('Successfully subscribed to channel!');
        });
        
        // Прослушивание событий нового сообщения
        channel.bind('new-message', function(data) {
            console.log('Received message:', data);
            appendMessage(data.message);
            scrollToBottom();
        });

        // Отправка сообщения
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const messageText = messageInput.value.trim();
            if (!messageText) return;
            
            fetch('{{ route('support.send-message') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    ticket_id: ticketId,
                    message: messageText
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    messageInput.style.height = 'auto';
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
            });
        });

        // Добавление сообщения в чат
        function appendMessage(message) {
            const isAdmin = message.is_from_admin;
            const messageClass = isAdmin ? 'admin-message' : 'user-message';
            
            const date = new Date(message.created_at);
            const time = date.getHours().toString().padStart(2, '0') + ':' + 
                         date.getMinutes().toString().padStart(2, '0');
            
            const messageHtml = `
                <div class="message-wrapper ${messageClass}">
                    <div class="message-bubble">
                        <div class="message-text">${message.message}</div>
                        <div class="message-time">${time}</div>
                    </div>
                </div>
            `;
            
            messagesList.insertAdjacentHTML('beforeend', messageHtml);
        }

        // Прокрутка чата вниз
        function scrollToBottom() {
            messagesList.scrollTop = messagesList.scrollHeight;
        }

        // Функционал виджета чата поддержки
        const widgetContainer = document.querySelector('.support-widget-container');
        const widgetButton = document.querySelector('.support-widget-button');
        const widget = document.querySelector('.support-widget');
        const widgetClose = document.querySelector('.support-widget-close');
        
        // Показываем контейнер виджета только если мы не на странице чата
        if (!window.location.pathname.includes('/support/chat')) {
            widgetContainer.style.display = 'block';
            
            // Открытие/закрытие виджета
            widgetButton.addEventListener('click', function() {
                widget.style.display = widget.style.display === 'none' ? 'flex' : 'none';
            });
            
            widgetClose.addEventListener('click', function() {
                widget.style.display = 'none';
            });
            
            // Обработка формы виджета
            const widgetForm = document.querySelector('.support-widget-form');
            const widgetInput = widgetForm.querySelector('input');
            
            widgetForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const messageText = widgetInput.value.trim();
                if (!messageText) return;
                
                // Перенаправление на полную страницу чата
                window.location.href = '{{ route("support.chat") }}';
            });
        }
    });
</script>
@endsection