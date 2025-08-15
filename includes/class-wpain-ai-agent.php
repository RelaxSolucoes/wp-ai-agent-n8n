<?php
/**
 * Classe do Agente de IA do plugin WP AI Agent N8N
 * 
 * @package WP_AI_Agent_N8N
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPAIN_AI_Agent {
    
    /**
     * Instância única da classe
     */
    private static $instance = null;
    
    /**
     * Inicializa a classe
     */
    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Construtor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Inicializa hooks e ações
     */
    private function init_hooks() {
        // Adiciona o container do chat no footer
        add_action('wp_footer', array($this, 'add_chat_container'));
        
        // Carrega assets do chat
        add_action('wp_enqueue_scripts', array($this, 'enqueue_chat_assets'));
        
        // AJAX para envio de mensagens
        add_action('wp_ajax_wpain_send_message', array($this, 'handle_send_message'));
        add_action('wp_ajax_nopriv_wpain_send_message', array($this, 'handle_send_message'));
    }
    
    /**
     * Adiciona o container do chat no footer
     */
    public function add_chat_container() {
        if (!get_option('wpain_chat_enabled', false)) {
            return;
        }
        
        echo '<div id="wpain-chat-container"></div>';
    }
    
    /**
     * Carrega assets do chat
     */
    public function enqueue_chat_assets() {
        if (!get_option('wpain_chat_enabled', false)) {
            return;
        }
        
        // CSS do N8N Chat
        wp_enqueue_style(
            'wpain-n8n-chat-style',
            'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css',
            array(),
            '0.50.0'
        );
        
        // Script do chat
        wp_enqueue_script(
            'wpain-chat-script',
            WPAIN_URL . 'assets/js/chat.js',
            array(),
            WPAIN_VERSION,
            true
        );
        
        // Localiza o script com configurações
        wp_localize_script('wpain-chat-script', 'wpain_chat_config', array(
            'webhook_url' => get_option('wpain_n8n_webhook', ''),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpain_chat_nonce'),
            'settings' => array(
                'title' => get_option('wpain_chat_title', 'Olá! 👋'),
                'subtitle' => get_option('wpain_chat_subtitle', 'Como posso ajudar você hoje?'),
                'placeholder' => get_option('wpain_chat_placeholder', 'Digite sua pergunta...'),
                'welcome_message' => get_option('wpain_chat_welcome', 'Olá! 👋 Eu sou o assistente virtual. Como posso te ajudar hoje?')
            ),
            'strings' => array(
                'sending' => 'Enviando...',
                'error' => 'Erro ao enviar mensagem. Tente novamente.',
                'connection_error' => 'Erro de conexão. Verifique sua internet.'
            )
        ));
    }
    
    /**
     * Manipula envio de mensagens via AJAX
     */
    public function handle_send_message() {
        check_ajax_referer('wpain_chat_nonce', 'nonce');
        
        $message = sanitize_textarea_field($_POST['message']);
        $session_id = sanitize_text_field($_POST['session_id']);
        
        if (empty($message)) {
            wp_send_json_error('Mensagem é obrigatória');
        }
        
        $webhook_url = get_option('wpain_n8n_webhook', '');
        if (empty($webhook_url)) {
            wp_send_json_error('Webhook do N8N não configurado');
        }
        
        // Prepara dados para o N8N
        $payload = array(
            'chatInput' => $message,
            'sessionId' => $session_id ?: 'web_' . time() . '_' . uniqid(),
            'source' => 'n8n_chat_widget',
            'sourceType' => 'chat_widget',
            'channel' => 'chat_widget', // Campo correto para o fluxo N8N
            'page_url' => $_POST['page_url'] ?? '',
            'page_title' => $_POST['page_title'] ?? '',
            'user_agent' => $_POST['user_agent'] ?? '',
            'timestamp' => current_time('c'),
            'userInfo' => array(
                'nome' => null,
                'email' => null,
                'telefone' => null,
                'assunto' => 'Chat Widget'
            ),
            'responseConfig' => array(
                'shouldRespond' => true,
                'responseTarget' => 'chat_widget'
            ),
            'metadata' => array(
                'wordpress' => true,
                'site_url' => home_url(),
                'ajax_proxy' => true
            )
        );
        
        // Injeta provider com informações da Evolution API (não expostas no frontend)
        $evolution_url = get_option('wpain_evolution_url', '');
        $evolution_apikey = get_option('wpain_evolution_apikey', '');
        $evolution_instance = get_option('wpain_evolution_instance', '');
        
        if (!empty($evolution_url) && !empty($evolution_apikey) && !empty($evolution_instance)) {
            $payload['provider'] = array(
                'instanceName' => $evolution_instance,
                'serverUrl' => rtrim($evolution_url, '/'),
                'apiKey' => $evolution_apikey
            );
        }
        
        // Se usuário estiver logado, adiciona informações
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $payload['userInfo']['nome'] = $current_user->display_name;
            $payload['userInfo']['email'] = $current_user->user_email;
        }
        
        // Envia para o N8N
        $response = wp_remote_post($webhook_url, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($payload),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Erro na conexão: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code === 200) {
            $response_data = json_decode($body, true);
            
            // Retorna resposta do N8N ou mensagem padrão
            if ($response_data && isset($response_data['output'])) {
                wp_send_json_success(array(
                    'message' => $response_data['output'],
                    'session_id' => $payload['sessionId']
                ));
            } else {
                wp_send_json_success(array(
                    'message' => 'Mensagem recebida! Em breve entraremos em contato.',
                    'session_id' => $payload['sessionId']
                ));
            }
        } else {
            wp_send_json_error('Erro no servidor: ' . $status_code);
        }
    }
    
    /**
     * Gera o HTML do widget de chat
     */
    public static function render_chat_widget() {
        if (!get_option('wpain_chat_enabled', false)) {
            return '';
        }
        
        $settings = array(
            'title' => get_option('wpain_chat_title', 'Olá! 👋'),
            'subtitle' => get_option('wpain_chat_subtitle', 'Como posso ajudar você hoje?'),
            'placeholder' => get_option('wpain_chat_placeholder', 'Digite sua pergunta...'),
            'welcome_message' => get_option('wpain_chat_welcome', 'Olá! 👋 Eu sou o assistente virtual. Como posso te ajudar hoje?')
        );
        
        ob_start();
        ?>
        <div id="wpain-chat-widget" class="wpain-chat-widget">
            <div class="wpain-chat-header">
                <div class="wpain-chat-avatar">🤖</div>
                <div class="wpain-chat-info">
                    <h4><?php echo esc_html($settings['title']); ?></h4>
                    <p><?php echo esc_html($settings['subtitle']); ?></p>
                </div>
                <button class="wpain-chat-close" id="wpain-chat-close">&times;</button>
            </div>
            
            <div class="wpain-chat-messages" id="wpain-chat-messages">
                <div class="wpain-message wpain-message-bot">
                    <div class="wpain-message-content">
                        <?php echo esc_html($settings['welcome_message']); ?>
                    </div>
                </div>
            </div>
            
            <div class="wpain-chat-input">
                <input type="text" id="wpain-chat-input-field" 
                       placeholder="<?php echo esc_attr($settings['placeholder']); ?>" />
                <button id="wpain-chat-send">📤</button>
            </div>
        </div>
        
        <div class="wpain-chat-toggle" id="wpain-chat-toggle">
            <span>💬</span>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Gera o HTML do chat para shortcode
     */
    public static function render_chat_shortcode($atts = array()) {
        $atts = shortcode_atts(array(
            'mode' => 'window',
            'title' => get_option('wpain_chat_title', 'Olá! 👋')
        ), $atts);
        
        if ($atts['mode'] === 'inline') {
            return self::render_chat_widget();
        }
        
        // Para modo window, retorna apenas o container
        return '<div id="wpain-chat-shortcode" data-mode="' . esc_attr($atts['mode']) . '" data-title="' . esc_attr($atts['title']) . '"></div>';
    }
    
    /**
     * Verifica se o chat deve ser exibido automaticamente
     */
    public static function should_auto_display() {
        if (!get_option('wpain_chat_enabled', false)) {
            return false;
        }
        
        // Pode adicionar lógica adicional aqui (ex: páginas específicas, horários, etc.)
        return true;
    }
    
    /**
     * Obtém configurações do chat
     */
    public static function get_chat_settings() {
        return array(
            'enabled' => get_option('wpain_chat_enabled', false),
            'title' => get_option('wpain_chat_title', 'Olá! 👋'),
            'subtitle' => get_option('wpain_chat_subtitle', 'Como posso ajudar você hoje?'),
            'placeholder' => get_option('wpain_chat_placeholder', 'Digite sua pergunta...'),
            'welcome_message' => get_option('wpain_chat_welcome', 'Olá! 👋 Eu sou o assistente virtual. Como posso te ajudar hoje?'),
            'webhook_url' => get_option('wpain_n8n_webhook', '')
        );
    }
}
