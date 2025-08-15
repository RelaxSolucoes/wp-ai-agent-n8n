<?php
/**
 * Classe do manipulador de formulários do plugin WP AI Agent N8N
 * 
 * @package WP_AI_Agent_N8N
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPAIN_Form_Handler {
    
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
        // AJAX para envio de formulário
        add_action('wp_ajax_wpain_submit_form', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_wpain_submit_form', array($this, 'handle_form_submission'));
        
        // AJAX para validação do WhatsApp
        add_action('wp_ajax_wpain_validate_whatsapp', array($this, 'validate_whatsapp'));
        add_action('wp_ajax_nopriv_wpain_validate_whatsapp', array($this, 'validate_whatsapp'));
        
        // Shortcode do formulário
        add_shortcode('wpain_form', array($this, 'form_shortcode'));
    }
    
    /**
     * Manipula envio de formulário via AJAX
     */
    public function handle_form_submission() {
        check_ajax_referer('wpain_form_nonce', 'nonce');
        
        // Valida campos obrigatórios
        $nome = sanitize_text_field($_POST['nome'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $whatsapp = sanitize_text_field($_POST['whatsapp'] ?? '');

        $mensagem = sanitize_textarea_field($_POST['mensagem'] ?? '');
        
        if (empty($nome) || empty($email) || empty($whatsapp) || empty($mensagem)) {
            wp_send_json_error('Todos os campos obrigatórios devem ser preenchidos');
        }
        
        if (!is_email($email)) {
            wp_send_json_error('E-mail inválido');
        }
        
        $webhook_url = get_option('wpain_n8n_webhook', '');
        
        if (empty($webhook_url)) {
            wp_send_json_error('Webhook do N8N não configurado');
        }
        
        // Formata mensagem para o bot
        $chat_input = $this->format_message_for_bot(array(
            'nome' => $nome,
            'email' => $email,
            'whatsapp' => $whatsapp,
            'mensagem' => $mensagem
        ));
        
        // Gera sessionId baseado no WhatsApp (padrão WhatsApp)
        $whatsapp_session_id = $this->generate_session_id($whatsapp);
        $fallback_session_id = $this->generate_session_id();
        
        // Prepara payload para N8N
        $payload = array(
            'chatInput' => $chat_input,
            'action' => 'sendMessage',
            'sessionId' => $whatsapp_session_id,
            'remoteJid' => $whatsapp_session_id, // Para compatibilidade com Evolution API
            'source' => 'web_form',
            'sourceType' => 'formulario_site',
            'channel' => 'web_form', // Campo correto para o fluxo N8N
            'pushName' => $nome,
            'fromMe' => false,
            'userInfo' => array(
                'nome' => $nome,
                'email' => $email,
                'whatsapp' => $whatsapp,
                'telefone' => $whatsapp // Mantém compatibilidade com webhook
            ),
            'responseConfig' => array(
                'shouldRespond' => true,
                'responseTarget' => !empty($whatsapp) ? 'whatsapp' : 'web_form',
                'formSessionId' => $fallback_session_id,
                'whatsappSessionId' => $whatsapp_session_id
            ),
            'metadata' => array(
                'page_url' => $_POST['page_url'] ?? '',
                'page_title' => $_POST['page_title'] ?? '',
                'timestamp' => current_time('c'),
                'user_agent' => $_POST['user_agent'] ?? '',
                'phone_formatted' => $whatsapp_session_id,
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
            
            // Determina tipo de resposta baseado no WhatsApp
            $tem_whatsapp = !empty($whatsapp);
            
            if ($tem_whatsapp) {
                // Resposta vai para WhatsApp
                $success_message = sprintf(
                    '✅ Mensagem enviada com sucesso!<br><strong>📱 A resposta será enviada para seu WhatsApp: %s</strong><br><small>Verifique as mensagens em alguns instantes.</small>',
                    esc_html($whatsapp)
                );
            } else {
                // Resposta no próprio formulário
                $success_message = '✅ Mensagem enviada com sucesso para o assistente!<br>Aguarde a resposta...';
            }
            
            wp_send_json_success(array(
                'message' => $success_message,
                'whatsapp_response' => $tem_whatsapp,
                'session_id' => $payload['sessionId']
            ));
        } else {
            wp_send_json_error('Erro no servidor: ' . $status_code);
        }
    }
    
    /**
     * Gera sessionId no padrão WhatsApp
     */
    private function generate_session_id($telefone = null) {
        if ($telefone && trim($telefone)) {
            // Limpa telefone (remove espaços, parênteses, hífens)
            $clean_phone = preg_replace('/[^\d]/', '', $telefone);
            
            // Se não começar com 55, adiciona
            if (!str_starts_with($clean_phone, '55')) {
                $clean_phone = '55' . $clean_phone;
            }
            
            return $clean_phone . '@s.whatsapp.net';
        }
        
        // Fallback para sessionId genérico se não houver telefone
        return 'form_' . time() . '_' . uniqid();
    }
    
    /**
     * Formata dados do formulário como mensagem de chat
     */
    private function format_message_for_bot($data) {
        $mensagem = "**Nova solicitação via formulário:**\n\n";
        
        $mensagem .= "**Nome:** " . $data['nome'] . "\n";
        $mensagem .= "**E-mail:** " . $data['email'] . "\n";
        
        if (!empty($data['whatsapp'])) {
            $mensagem .= "**WhatsApp:** " . $data['whatsapp'] . "\n";
        }
        

        $mensagem .= "**Mensagem:**\n" . $data['mensagem'] . "\n\n";
        $mensagem .= "**Página:** " . ($_POST['page_url'] ?? 'Não informado') . "\n";
        $mensagem .= "**Data:** " . current_time('d/m/Y H:i:s');
        
        return $mensagem;
    }
    
    /**
     * Shortcode do formulário
     */
    public function form_shortcode($atts = array()) {
        if (!get_option('wpain_form_enabled', false)) {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'title' => get_option('wpain_form_title', 'Fale com nosso Assistente Virtual')
        ), $atts);
        
        return $this->render_form($atts['title']);
    }
    
    /**
     * Renderiza o formulário HTML
     */
    public static function render_form($title = '') {
        if (empty($title)) {
            $title = get_option('wpain_form_title', 'Fale com nosso Assistente Virtual');
        }
        
        // Obtém configurações dos campos
        $settings = self::get_form_settings();
        
        ob_start();
        ?>
        <div class="wpain-form-container">
            <h3><?php echo esc_html($title); ?></h3>
            
            <form id="wpain-form" class="wpain-form">
                <div class="wpain-form-row">
                    <div class="wpain-form-group wpain-form-half">
                        <label for="wpain-nome"><?php echo esc_html($settings['fields']['nome']['label']); ?> *</label>
                        <input type="text" id="wpain-nome" name="nome" 
                               placeholder="<?php echo esc_attr($settings['fields']['nome']['placeholder']); ?>" required>
                    </div>
                    <div class="wpain-form-group wpain-form-half">
                        <label for="wpain-email"><?php echo esc_html($settings['fields']['email']['label']); ?> *</label>
                        <input type="email" id="wpain-email" name="email" 
                               placeholder="<?php echo esc_attr($settings['fields']['email']['placeholder']); ?>" required>
                    </div>
                </div>
                
                <div class="wpain-form-group">
                    <label for="wpain-whatsapp"><?php echo esc_html($settings['fields']['whatsapp']['label']); ?> *</label>
                    <input type="tel" id="wpain-whatsapp" name="whatsapp" 
                           placeholder="<?php echo esc_attr($settings['fields']['whatsapp']['placeholder']); ?>" required>
                    <div class="whatsapp-validation-status" id="whatsapp-validation-status"></div>
                </div>
                
                <div class="wpain-form-group">
                    <label for="wpain-mensagem"><?php echo esc_html($settings['fields']['mensagem']['label']); ?> *</label>
                    <textarea id="wpain-mensagem" name="mensagem" rows="4" 
                              placeholder="<?php echo esc_attr($settings['fields']['mensagem']['placeholder']); ?>" required></textarea>
                </div>
                
                <div class="wpain-form-group">
                    <button type="submit" class="wpain-submit-button">
                        <span class="button-text"><?php echo esc_html($settings['button']['text']); ?></span>
                        <span class="button-loader"><?php echo esc_html($settings['button']['loading_text']); ?></span>
                    </button>
                </div>
            </form>
            
            <div id="wpain-form-response" class="wpain-form-response"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Valida dados do formulário
     */
    public static function validate_form_data($data) {
        $errors = array();
        
        if (empty($data['nome'])) {
            $errors[] = 'Nome é obrigatório';
        }
        
        if (empty($data['email'])) {
            $errors[] = 'E-mail é obrigatório';
        } elseif (!is_email($data['email'])) {
            $errors[] = 'E-mail inválido';
        }
        
        if (empty($data['whatsapp'])) {
            $errors[] = 'WhatsApp é obrigatório';
        }
        
        if (empty($data['mensagem'])) {
            $errors[] = 'Mensagem é obrigatória';
        }
        
        return $errors;
    }
    
    /**
     * Obtém configurações do formulário
     */
    public static function get_form_settings() {
        return array(
            'enabled' => get_option('wpain_form_enabled', false),
            'title' => get_option('wpain_form_title', 'Fale com nosso Assistente Virtual'),
            'webhook_url' => get_option('wpain_n8n_webhook', ''),
            'fields' => array(
                'nome' => array(
                    'label' => get_option('wpain_form_nome_label', 'Nome'),
                    'placeholder' => get_option('wpain_form_nome_placeholder', ''),
                    'required' => true
                ),
                'email' => array(
                    'label' => get_option('wpain_form_email_label', 'E-mail'),
                    'placeholder' => get_option('wpain_form_email_placeholder', ''),
                    'required' => true
                ),
                'whatsapp' => array(
                    'label' => get_option('wpain_form_whatsapp_label', 'WhatsApp'),
                    'placeholder' => get_option('wpain_form_whatsapp_placeholder', '(11) 99999-9999'),
                    'required' => true
                ),
                'mensagem' => array(
                    'label' => get_option('wpain_form_mensagem_label', 'Sua Pergunta/Solicitação'),
                    'placeholder' => get_option('wpain_form_mensagem_placeholder', 'Digite sua pergunta ou solicitação para o assistente virtual...'),
                    'required' => true
                )
            ),
            'button' => array(
                'text' => get_option('wpain_form_button_text', '🤖 Enviar para o Bot'),
                'loading_text' => get_option('wpain_form_button_loading_text', '⏳ Enviando para o bot...')
            )
        );
    }

    /**
     * Valida WhatsApp via Evolution API
     */
    public function validate_whatsapp() {
        check_ajax_referer('wpain_form_nonce', 'nonce');
        
        $whatsapp = sanitize_text_field($_POST['whatsapp'] ?? '');
        
        if (empty($whatsapp)) {
            wp_send_json_error('WhatsApp é obrigatório');
        }
        
        // Valida formato do WhatsApp
        $validated_whatsapp = $this->validate_whatsapp_format($whatsapp);
        if (!$validated_whatsapp) {
            wp_send_json_error('Formato de WhatsApp inválido');
        }
        
        // Verifica conexão com Evolution API
        $evolution_url = get_option('wpain_evolution_url', '');
        $evolution_apikey = get_option('wpain_evolution_apikey', '');
        $evolution_instance = get_option('wpain_evolution_instance', '');
        
        
        
        if (empty($evolution_url) || empty($evolution_apikey) || empty($evolution_instance)) {
            wp_send_json_error('Evolution API não configurada. URL: ' . ($evolution_url ?: 'vazia') . ', API Key: ' . ($evolution_apikey ? 'configurada' : 'vazia') . ', Instance: ' . ($evolution_instance ?: 'vazia'));
        }
        
        // Testa conexão com Evolution API usando o endpoint correto
        $test_url = trailingslashit($evolution_url) . 'chat/whatsappNumbers/' . $evolution_instance;
        
        
        
        // Usa o número validado para testar a conexão
        $test_number = $validated_whatsapp;
        
        $response = wp_remote_post($test_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'apikey' => $evolution_apikey
            ),
            'body' => json_encode(array(
                'numbers' => array($test_number)
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Erro na conexão com Evolution API');
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code === 200) {
            $response_data = json_decode($body, true);
            
            
            
            // Verifica se a resposta é um array e se contém dados válidos
            if (is_array($response_data) && !empty($response_data)) {
                $first_number = $response_data[0];
                
                // Verifica se o número existe e está conectado
                if (isset($first_number['exists']) && $first_number['exists'] === true) {
                    wp_send_json_success(array(
                        'message' => 'WhatsApp válido e Evolution API conectada',
                        'whatsapp' => $validated_whatsapp,
                        'valid' => true,
                        'jid' => $first_number['jid'] ?? '',
                        'number' => $first_number['number'] ?? ''
                    ));
                } else {
                    wp_send_json_error('❌ Esse número não é WhatsApp, digite um número válido.');
                }
            } else {
                wp_send_json_error('Resposta inválida da Evolution API');
            }
        } else {
            wp_send_json_error('Erro na Evolution API: ' . $status_code . ' - Resposta: ' . $body);
        }
    }

    /**
     * Valida formato do WhatsApp (baseado no plugin exemplo)
     */
    private function validate_whatsapp_format($whatsapp) {
        if (empty($whatsapp)) return false;
        
        // Remove TODOS os caracteres não numéricos
        $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);
        
        // Remove zeros à esquerda
        $whatsapp = ltrim($whatsapp, '0');
        
        if (empty($whatsapp)) return false;
        
        // CASOS POSSÍVEIS NO BRASIL:
        
        // 1. Número com DDI (55) já presente
        if (strlen($whatsapp) >= 12 && substr($whatsapp, 0, 2) == '55') {
            $ddd = substr($whatsapp, 2, 2);
            if ($ddd >= 11 && $ddd <= 99) {
                return $whatsapp;
            }
        }
        
        // 1.1. Número com 12 dígitos (DDD + 9XXXX-XXXX sem 55)
        if (strlen($whatsapp) == 12 && substr($whatsapp, 0, 2) != '55') {
            $ddd = substr($whatsapp, 0, 2);
            if ($ddd >= 11 && $ddd <= 99) {
                // Verifica se é celular (terceiro dígito deve ser 9)
                $terceiro_digito = substr($whatsapp, 2, 1);
                if ($terceiro_digito == '9') {
                    return '55' . $whatsapp;
                }
            }
        }
        
        // 2. Número com 11 dígitos (celular DDD + 9XXXX-XXXX)
        if (strlen($whatsapp) == 11) {
            $ddd = substr($whatsapp, 0, 2);
            if ($ddd >= 11 && $ddd <= 99) {
                // Verifica se é celular (terceiro dígito deve ser 9)
                $terceiro_digito = substr($whatsapp, 2, 1);
                if ($terceiro_digito == '9') {
                    return '55' . $whatsapp;
                } else {
                    // Se não é 9, pode ser um número antigo, adiciona 9
                    return '55' . substr($whatsapp, 0, 2) . '9' . substr($whatsapp, 2);
                }
            }
        }
        
        // 3. Número com 10 dígitos (fixo DDD + XXXX-XXXX)
        if (strlen($whatsapp) == 10) {
            $ddd = substr($whatsapp, 0, 2);
            $terceiro_digito = substr($whatsapp, 2, 1);
            
            if ($ddd >= 11 && $ddd <= 99) {
                // Se o terceiro dígito é 6-8, é celular sem o 9
                if ($terceiro_digito >= '6' && $terceiro_digito <= '8') {
                    return '55' . substr($whatsapp, 0, 2) . '9' . substr($whatsapp, 2);
                } else {
                    return '55' . $whatsapp;
                }
            }
        }
        
        // 4. Número com 9 dígitos (provavelmente faltou dígito do DDD)
        if (strlen($whatsapp) == 9) {
            return '551' . $whatsapp;
        }
        
        // 5. Número com 8 dígitos (XXXX-XXXX sem DDD)
        if (strlen($whatsapp) == 8) {
            return '5511' . $whatsapp;
        }
        
        // 6. Casos especiais - tenta adicionar 55 se não tem
        if (strlen($whatsapp) >= 8 && strlen($whatsapp) <= 13) {
            if (substr($whatsapp, 0, 2) != '55') {
                return '55' . $whatsapp;
            }
        }
        
        return false;
    }
}
