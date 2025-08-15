/**
 * WP AI Agent N8N - Chat JavaScript
 */

(function() {
    'use strict';
    
    // Configurações globais
    let chatConfig = window.wpain_chat_config || {};
    let chatSessionId = null;
    let isChatOpen = false;
    
    // Inicializa o chat quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChat);
    } else {
        initChat();
    }
    
    function initChat() {
        // Verifica se o chat está habilitado
        if (!chatConfig.settings || !chatConfig.webhook_url) {
            console.log('WP AI Agent N8N Chat: Configuração não encontrada');
            return;
        }
        
        // Cria o widget de chat
        createChatWidget();
        
        // Inicializa eventos
        initChatEvents();
        
        // Gera sessionId único
        chatSessionId = generateSessionId();
        
        console.log('🤖 WP AI Agent N8N Chat inicializado!');
    }
    
    function createChatWidget() {
        // Cria o container principal do chat
        const chatContainer = document.createElement('div');
        chatContainer.id = 'wpain-chat-widget';
        chatContainer.className = 'wpain-chat-widget';
        chatContainer.style.display = 'none';
        
        // HTML do widget
        chatContainer.innerHTML = `
            <div class="wpain-chat-header">
                <div class="wpain-chat-avatar">🤖</div>
                <div class="wpain-chat-info">
                    <h4>${chatConfig.settings.title || 'Olá! 👋'}</h4>
                    <p>${chatConfig.settings.subtitle || 'Como posso ajudar você hoje?'}</p>
                </div>
                <button class="wpain-chat-close" id="wpain-chat-close">&times;</button>
            </div>
            
            <div class="wpain-chat-messages" id="wpain-chat-messages">
                <div class="wpain-message wpain-message-bot">
                    <div class="wpain-message-content">
                        ${chatConfig.settings.welcome_message || 'Olá! 👋 Eu sou o assistente virtual. Como posso te ajudar hoje?'}
                    </div>
                </div>
            </div>
            
            <div class="wpain-chat-input">
                <input type="text" id="wpain-chat-input-field" 
                       placeholder="${chatConfig.settings.placeholder || 'Digite sua pergunta...'}" />
                <button id="wpain-chat-send">📤</button>
            </div>
        `;
        
        // Cria o botão de toggle
        const chatToggle = document.createElement('div');
        chatToggle.className = 'wpain-chat-toggle';
        chatToggle.id = 'wpain-chat-toggle';
        chatToggle.innerHTML = '<span>💬</span>';
        
        // Adiciona ao body
        document.body.appendChild(chatContainer);
        document.body.appendChild(chatToggle);
        
        // Adiciona estilos CSS inline (fallback)
        addChatStyles();
    }
    
    function addChatStyles() {
        if (document.getElementById('wpain-chat-styles')) return;
        
        const styles = document.createElement('style');
        styles.id = 'wpain-chat-styles';
        styles.textContent = `
            .wpain-chat-widget {
                position: fixed;
                bottom: 100px;
                right: 20px;
                width: 350px;
                height: 500px;
                background: white;
                border-radius: 15px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                z-index: 9999;
                display: flex;
                flex-direction: column;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                overflow: hidden;
            }
            
            .wpain-chat-toggle {
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 60px;
                height: 60px;
                background: linear-gradient(45deg, #667eea, #764ba2);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                z-index: 9998;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                transition: all 0.3s ease;
            }
            
            .wpain-chat-toggle:hover {
                transform: scale(1.1);
                box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            }
            
            .wpain-chat-toggle span {
                font-size: 24px;
                color: white;
            }
            
            .wpain-chat-header {
                background: linear-gradient(45deg, #667eea, #764ba2);
                color: white;
                padding: 20px;
                display: flex;
                align-items: center;
                gap: 15px;
            }
            
            .wpain-chat-avatar {
                font-size: 32px;
                width: 50px;
                height: 50px;
                background: rgba(255,255,255,0.2);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .wpain-chat-info h4 {
                margin: 0;
                font-size: 18px;
                font-weight: 600;
            }
            
            .wpain-chat-info p {
                margin: 5px 0 0 0;
                font-size: 14px;
                opacity: 0.9;
            }
            
            .wpain-chat-close {
                background: none;
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
                margin-left: auto;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: background 0.3s ease;
            }
            
            .wpain-chat-close:hover {
                background: rgba(255,255,255,0.2);
            }
            
            .wpain-chat-messages {
                flex: 1;
                padding: 20px;
                overflow-y: auto;
                background: #f8f9fa;
            }
            
            .wpain-message {
                margin-bottom: 15px;
                display: flex;
                flex-direction: column;
            }
            
            .wpain-message-user {
                align-items: flex-end;
            }
            
            .wpain-message-bot {
                align-items: flex-start;
            }
            
            .wpain-message-content {
                max-width: 80%;
                padding: 12px 16px;
                border-radius: 18px;
                font-size: 14px;
                line-height: 1.4;
                word-wrap: break-word;
            }
            
            .wpain-message-user .wpain-message-content {
                background: linear-gradient(45deg, #667eea, #764ba2);
                color: white;
            }
            
            .wpain-message-bot .wpain-message-content {
                background: white;
                color: #333;
                border: 1px solid #e9ecef;
            }
            
            .wpain-chat-input {
                padding: 20px;
                background: white;
                border-top: 1px solid #e9ecef;
                display: flex;
                gap: 10px;
            }
            
            .wpain-chat-input input {
                flex: 1;
                padding: 12px 16px;
                border: 1px solid #e9ecef;
                border-radius: 25px;
                font-size: 14px;
                outline: none;
                transition: border-color 0.3s ease;
            }
            
            .wpain-chat-input input:focus {
                border-color: #667eea;
            }
            
            .wpain-chat-input button {
                background: linear-gradient(45deg, #667eea, #764ba2);
                color: white;
                border: none;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            }
            
            .wpain-chat-input button:hover {
                transform: scale(1.1);
            }
            
            .wpain-chat-input button:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none;
            }
            
            .wpain-typing-indicator {
                display: flex;
                gap: 5px;
                padding: 12px 16px;
                background: white;
                border: 1px solid #e9ecef;
                border-radius: 18px;
                max-width: 80%;
                align-self: flex-start;
            }
            
            .wpain-typing-dot {
                width: 8px;
                height: 8px;
                background: #667eea;
                border-radius: 50%;
                animation: typing 1.4s infinite ease-in-out;
            }
            
            .wpain-typing-dot:nth-child(1) { animation-delay: -0.32s; }
            .wpain-typing-dot:nth-child(2) { animation-delay: -0.16s; }
            
            @keyframes typing {
                0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
                40% { transform: scale(1); opacity: 1; }
            }
            
            @media (max-width: 480px) {
                .wpain-chat-widget {
                    width: calc(100vw - 40px);
                    height: calc(100vh - 120px);
                    bottom: 80px;
                    right: 20px;
                    left: 20px;
                }
                
                .wpain-chat-toggle {
                    bottom: 15px;
                    right: 15px;
                }
            }
        `;
        
        document.head.appendChild(styles);
    }
    
    function initChatEvents() {
        // Toggle do chat
        document.getElementById('wpain-chat-toggle').addEventListener('click', toggleChat);
        
        // Fechar chat
        document.getElementById('wpain-chat-close').addEventListener('click', closeChat);
        
        // Enviar mensagem
        document.getElementById('wpain-chat-send').addEventListener('click', sendMessage);
        
        // Enter para enviar
        document.getElementById('wpain-chat-input-field').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // Focar no input quando abrir
        document.getElementById('wpain-chat-input-field').addEventListener('focus', function() {
            this.select();
        });
    }
    
    function toggleChat() {
        if (isChatOpen) {
            closeChat();
        } else {
            openChat();
        }
    }
    
    function openChat() {
        const chatWidget = document.getElementById('wpain-chat-widget');
        const chatToggle = document.getElementById('wpain-chat-toggle');
        
        chatWidget.style.display = 'flex';
        chatToggle.style.display = 'none';
        isChatOpen = true;
        
        // Foca no input
        setTimeout(() => {
            document.getElementById('wpain-chat-input-field').focus();
        }, 300);
        
        // Scroll para baixo
        scrollToBottom();
    }
    
    function closeChat() {
        const chatWidget = document.getElementById('wpain-chat-widget');
        const chatToggle = document.getElementById('wpain-chat-toggle');
        
        chatWidget.style.display = 'none';
        chatToggle.style.display = 'flex';
        isChatOpen = false;
    }
    
    function sendMessage() {
        const inputField = document.getElementById('wpain-chat-input-field');
        const message = inputField.value.trim();
        
        if (!message) return;
        
        // Adiciona mensagem do usuário
        addMessage(message, 'user');
        
        // Limpa input
        inputField.value = '';
        
        // Mostra indicador de digitação
        showTypingIndicator();
        
        // Envia mensagem
        sendMessageToBot(message);
    }
    
    function addMessage(content, type) {
        const messagesContainer = document.getElementById('wpain-chat-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `wpain-message wpain-message-${type}`;
        
        messageDiv.innerHTML = `
            <div class="wpain-message-content">
                ${escapeHtml(content)}
            </div>
        `;
        
        messagesContainer.appendChild(messageDiv);
        scrollToBottom();
    }
    
    function showTypingIndicator() {
        const messagesContainer = document.getElementById('wpain-chat-messages');
        const typingDiv = document.createElement('div');
        typingDiv.className = 'wpain-typing-indicator';
        typingDiv.id = 'wpain-typing';
        
        typingDiv.innerHTML = `
            <div class="wpain-typing-dot"></div>
            <div class="wpain-typing-dot"></div>
            <div class="wpain-typing-dot"></div>
        `;
        
        messagesContainer.appendChild(typingDiv);
        scrollToBottom();
    }
    
    function hideTypingIndicator() {
        const typingIndicator = document.getElementById('wpain-typing');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }
    
    async function sendMessageToBot(message) {
        try {
            // Prepara payload
            const payload = {
                chatInput: message,
                sessionId: chatSessionId,
                source: 'n8n_chat_widget',
                sourceType: 'chat_widget',
                page_url: window.location.href,
                page_title: document.title,
                user_agent: navigator.userAgent,
                timestamp: new Date().toISOString(),
                userInfo: {
                    nome: null,
                    email: null,
                    telefone: null,
                    assunto: 'Chat Widget'
                },
                responseConfig: {
                    shouldRespond: true,
                    responseTarget: 'chat_widget'
                }
            };
            
            // Envia para o N8N
            const response = await fetch(chatConfig.webhook_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            
            if (response.ok) {
                const result = await response.json();
                
                // Remove indicador de digitação
                hideTypingIndicator();
                
                // Adiciona resposta do bot
                if (result && result.output) {
                    addMessage(result.output, 'bot');
                } else {
                    addMessage('Mensagem recebida! Em breve entraremos em contato.', 'bot');
                }
            } else {
                throw new Error(`HTTP ${response.status}`);
            }
        } catch (error) {
            console.error('Erro ao enviar mensagem:', error);
            
            // Remove indicador de digitação
            hideTypingIndicator();
            
            // Adiciona mensagem de erro
            addMessage('Desculpe, ocorreu um erro. Tente novamente em alguns instantes.', 'bot');
        }
    }
    
    function scrollToBottom() {
        const messagesContainer = document.getElementById('wpain-chat-messages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    function generateSessionId() {
        return 'chat_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Exposição de funções para uso externo
    window.WPAINChat = {
        open: openChat,
        close: closeChat,
        sendMessage: sendMessage,
        addMessage: addMessage
    };
    
})();
