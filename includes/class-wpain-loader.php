<?php
/**
 * Classe principal do plugin WP AI Agent N8N
 * 
 * @package WP_AI_Agent_N8N
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPAIN_Loader {
    
    /**
     * Instância única da classe
     */
    private static $instance = null;
    
    /**
     * Validação comum para requisições AJAX
     */
    private function validate_ajax_request() {
        check_ajax_referer('wpain_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Acesso negado');
        }
    }
    
    /**
     * Inicializa o plugin
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
        // Admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Frontend
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        
        // AJAX
        add_action('wp_ajax_wpain_test_connection', array($this, 'test_connection'));
        add_action('wp_ajax_wpain_test_webhook', array($this, 'test_webhook'));
        add_action('wp_ajax_wpain_get_webhook', array($this, 'get_webhook'));
        add_action('wp_ajax_wpain_search_n8n_integrations', array($this, 'search_n8n_integrations'));
        add_action('wp_ajax_wpain_create_n8n_integration', array($this, 'create_n8n_integration'));
        add_action('wp_ajax_wpain_toggle_n8n_status', array($this, 'toggle_n8n_status'));
        add_action('wp_ajax_wpain_update_n8n_webhook', array($this, 'update_n8n_webhook'));
        
        // Shortcodes
        add_shortcode('wpain_chat', array($this, 'chat_shortcode'));
        add_shortcode('wpain_form', array($this, 'form_shortcode'));
        
        // Inicializa classes
        WPAIN_Settings::init();
        WPAIN_AI_Agent::init();
        WPAIN_Form_Handler::init();
    }
    
    /**
     * Adiciona menu administrativo
     */
    public function add_admin_menu() {
        add_menu_page(
            'WP AI Agent N8N',
            'AI Agent N8N',
            'manage_options',
            'wp-ai-agent-n8n',
            array($this, 'admin_page'),
            'dashicons-networking',
            30
        );
    }
    
    /**
     * Carrega scripts e estilos do admin
     */
    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_wp-ai-agent-n8n') {
            return;
        }
        
        // Garante que os dashicons sejam carregados
        wp_enqueue_style('dashicons');
        
        wp_enqueue_style(
            'wpain-admin-style',
            WPAIN_URL . 'assets/css/admin.css',
            array('dashicons'),
            WPAIN_VERSION
        );
        
        wp_enqueue_script(
            'wpain-admin-script',
            WPAIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WPAIN_VERSION,
            true
        );
        
        wp_localize_script('wpain-admin-script', 'wpain_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpain_nonce'),
            'n8n_webhook' => get_option('wpain_n8n_webhook', ''),
            'strings' => array(
                'testing_connection' => 'Testando conexão...',
                'connection_success' => 'Conexão testada com sucesso!',
                'connection_error' => 'Erro na conexão. Verifique as configurações.',
                'testing_webhook' => 'Testando webhook...',
                'webhook_success' => 'Webhook testado com sucesso!',
                'webhook_error' => 'Erro no webhook. Verifique a URL.',
                'saving' => 'Salvando...',
                'saved' => 'Configurações salvas com sucesso!'
            )
        ));
    }
    
    /**
     * Carrega scripts e estilos do frontend
     */
    public function frontend_enqueue_scripts() {
        // Só carrega se o chat estiver ativado
        if (get_option('wpain_chat_enabled', false)) {
            wp_enqueue_style(
                'wpain-chat-style',
                'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css',
                array(),
                '0.50.0'
            );
            
            wp_enqueue_script(
                'wpain-chat-script',
                WPAIN_URL . 'assets/js/chat.js',
                array(),
                WPAIN_VERSION,
                true
            );
        }
        
        // Só carrega se o formulário estiver ativado
        if (get_option('wpain_form_enabled', false)) {
            wp_enqueue_style(
                'wpain-form-style',
                WPAIN_URL . 'assets/css/form.css',
                array(),
                WPAIN_VERSION
            );
            
            wp_enqueue_script(
                'wpain-form-script',
                WPAIN_URL . 'assets/js/form.js',
                array('jquery'),
                WPAIN_VERSION,
                true
            );
            
            // Localiza script com configurações
            wp_localize_script('wpain-form-script', 'wpain_form_config', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpain_form_nonce'),
                'webhook_url' => get_option('wpain_n8n_webhook', ''),
                'whatsapp_validation' => get_option('wpain_form_whatsapp_validation', true)
            ));
        }
    }
    
    /**
     * Página administrativa
     */
    public function admin_page() {
        WPAIN_Settings::render_admin_page();
    }
    
    /**
     * Testa conexão com Evolution API
     */
    public function test_connection() {
        $this->validate_ajax_request();
        
        $url = sanitize_text_field($_POST['url']);
        $apikey = sanitize_text_field($_POST['apikey']);
        $instance = sanitize_text_field($_POST['instance']);
        
        if (empty($url) || empty($apikey) || empty($instance)) {
            wp_send_json_error('Todos os campos são obrigatórios');
        }
        
        // Testa conexão com Evolution API usando o endpoint correto
        $test_url = trailingslashit($url) . 'chat/whatsappNumbers/' . $instance;
        
        // Usa um número de teste para verificar a conexão
        $test_number = '5511999999999'; // Número de teste
        
        $response = wp_remote_post($test_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'apikey' => $apikey
            ),
            'body' => json_encode(array(
                'numbers' => array($test_number)
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Erro na conexão: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code === 200) {
            $response_data = json_decode($body, true);
            
            // Verifica se a resposta é válida
            if (is_array($response_data) && !empty($response_data)) {
                wp_send_json_success('Evolution API conectada com sucesso!');
            } else {
                wp_send_json_error('Resposta inválida da Evolution API');
            }
        } else {
            wp_send_json_error('Erro na API: ' . $status_code . ' - ' . $body);
        }
    }
    
    /**
     * Testa webhook do N8N
     */
    public function test_webhook() {
        $this->validate_ajax_request();
        
        $webhook_url = sanitize_text_field($_POST['webhook_url']);
        
        if (empty($webhook_url)) {
            wp_send_json_error('URL do webhook é obrigatória');
        }
        
        // Testa webhook com dados de exemplo
        $test_data = array(
            'chatInput' => 'Teste de conexão',
            'sessionId' => 'test_' . time(),
            'source' => 'test',
            'timestamp' => current_time('c')
        );
        
        $response = wp_remote_post($webhook_url, array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($test_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Erro na conexão: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            wp_send_json_success('Webhook testado com sucesso!');
        } else {
            wp_send_json_error('Erro no webhook: ' . $status_code);
        }
    }
    

    
    /**
     * Shortcode do chat
     */
    public function chat_shortcode($atts) {
        if (!get_option('wpain_chat_enabled', false)) {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'mode' => 'window',
            'title' => get_option('wpain_chat_title', 'Olá! 👋')
        ), $atts);
        
        return '<div id="wpain-chat" data-mode="' . esc_attr($atts['mode']) . '" data-title="' . esc_attr($atts['title']) . '"></div>';
    }
    
    /**
     * Shortcode do formulário
     */
    public function form_shortcode($atts) {
        if (!get_option('wpain_form_enabled', false)) {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'title' => get_option('wpain_form_title', 'Fale com nosso Assistente Virtual')
        ), $atts);
        
        return WPAIN_Form_Handler::render_form($atts['title']);
    }
    
    /**
     * Busca integrações N8N na Evolution API
     */
    public function search_n8n_integrations() {
        $this->validate_ajax_request();
        
        $evolution_url = get_option('wpain_evolution_url', '');
        $evolution_apikey = get_option('wpain_evolution_apikey', '');
        $evolution_instance = get_option('wpain_evolution_instance', '');
        
        if (empty($evolution_url) || empty($evolution_apikey) || empty($evolution_instance)) {
            wp_send_json_error('Evolution API não configurada');
        }
        
                // Busca integrações N8N usando o endpoint correto da Evolution API
        $search_url = trailingslashit($evolution_url) . 'n8n/find/' . $evolution_instance;
        
        $response = wp_remote_get($search_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'apikey' => $evolution_apikey
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Erro na conexão: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
                if ($status_code === 200) {
            $response_data = json_decode($body, true);
            
            // Verifica diferentes estruturas de resposta possíveis
            $integrations = array();
            
            if (isset($response_data['data']) && is_array($response_data['data'])) {
                // Estrutura: {"data": [...]}
                $integrations = $response_data['data'];
            } elseif (isset($response_data['result']) && is_array($response_data['result'])) {
                // Estrutura: {"result": [...]}
                $integrations = $response_data['result'];
            } elseif (is_array($response_data)) {
                // Estrutura: [...] (array direto)
                $integrations = $response_data;
            }
            
            if (!empty($integrations)) {
                $count = count($integrations);
                wp_send_json_success(array(
                    'message' => "Encontradas {$count} integração(ões) N8N",
                    'integrations' => $integrations
                ));
            } else {
                wp_send_json_success(array(
                    'message' => 'Nenhuma integração N8N encontrada',
                    'integrations' => array()
                ));
            }
        } else {
            wp_send_json_error('Erro na API: ' . $status_code . ' - ' . $body);
        }
    }
    
    /**
     * Cria nova integração N8N na Evolution API
     */
    public function create_n8n_integration() {
        $this->validate_ajax_request();
        
        $webhook_url = sanitize_text_field($_POST['webhook_url']);
        $description = sanitize_text_field($_POST['description']);
        
        if (empty($webhook_url)) {
            wp_send_json_error('URL do webhook é obrigatória');
        }
        
        $evolution_url = get_option('wpain_evolution_url', '');
        $evolution_apikey = get_option('wpain_evolution_apikey', '');
        $evolution_instance = get_option('wpain_evolution_instance', '');
        
        if (empty($evolution_url) || empty($evolution_apikey) || empty($evolution_instance)) {
            wp_send_json_error('Evolution API não configurada');
        }
        
        // Dados para criar a integração com valores padrão (100% compatível com Evolution API)
        $integration_data = array(
            'enabled' => true,
            'description' => $description ?: 'Integração criada através do plugin wp-ai-agent-n8n',
            'webhookUrl' => $webhook_url,
            'expire' => 60,
            'keywordFinish' => 'sair',
            'delayMessage' => 300,
            'unknownMessage' => 'Desculpe, não entendi...',
            'listeningFromMe' => false,
            'stopBotFromMe' => true,
            'keepOpen' => false,
            'debounceTime' => 0,
            'ignoreJids' => array(),
            'splitMessages' => true,
            'timePerChar' => 200,
            'triggerType' => 'all',
            'triggerOperator' => 'contains',
            'triggerValue' => ''
        );
        
        // Cria integração usando o endpoint correto
        $create_url = trailingslashit($evolution_url) . 'n8n/create/' . $evolution_instance;
        
        $response = wp_remote_post($create_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'apikey' => $evolution_apikey
            ),
            'body' => json_encode($integration_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Erro na conexão: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code === 201) {
            $response_data = json_decode($body, true);
            wp_send_json_success(array(
                'message' => 'Integração N8N criada com sucesso!',
                'integration' => $response_data
            ));
        } else {
            wp_send_json_error('Erro ao criar integração: ' . $status_code . ' - ' . $body);
        }
    }
    
    /**
     * Ativa/desativa integração N8N
     */
    public function toggle_n8n_status() {
        $this->validate_ajax_request();
        
        $integration_id = sanitize_text_field($_POST['integration_id']);
        // CORREÇÃO: Compara explicitamente com a string "false" em vez de usar (bool)
        $enabled = ($_POST['enabled'] !== 'false');
        
        if (empty($integration_id)) {
            wp_send_json_error('ID da integração é obrigatório');
        }
        
        $evolution_url = get_option('wpain_evolution_url', '');
        $evolution_apikey = get_option('wpain_evolution_apikey', '');
        $evolution_instance = get_option('wpain_evolution_instance', '');
        
        if (empty($evolution_url) || empty($evolution_apikey) || empty($evolution_instance)) {
            wp_send_json_error('Evolution API não configurada');
        }
        
        // Primeiro busca os dados atuais da integração
        $search_url = trailingslashit($evolution_url) . 'n8n/find/' . $evolution_instance;
        
        $search_response = wp_remote_get($search_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'apikey' => $evolution_apikey
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($search_response)) {
            wp_send_json_error('Erro ao buscar integração: ' . $search_response->get_error_message());
        }
        
        $search_status = wp_remote_retrieve_response_code($search_response);
        $search_body = wp_remote_retrieve_body($search_response);
        
        if ($search_status !== 200) {
            wp_send_json_error('Erro ao buscar integração: ' . $search_status . ' - ' . $search_body);
        }
        
        $search_data = json_decode($search_body, true);
        $current_integration = null;
        
        // Encontra a integração específica pelo ID
        // A Evolution API pode retornar diretamente um array ou dentro de 'data'
        $integrations_array = array();
        
        if (isset($search_data['data']) && is_array($search_data['data'])) {
            // Estrutura: {"data": [...]}
            $integrations_array = $search_data['data'];
        } elseif (is_array($search_data)) {
            // Estrutura: [...] (array direto)
            $integrations_array = $search_data;
        } else {
            wp_send_json_error('Estrutura de resposta inválida da Evolution API');
        }
        
        // Busca a integração pelo ID
        foreach ($integrations_array as $integration) {
            if (isset($integration['id']) && $integration['id'] === $integration_id) {
                $current_integration = $integration;
                break;
            }
        }
        
        if (!$current_integration) {
            wp_send_json_error('Integração não encontrada');
        }
        
        // CORREÇÃO: Para alterar o status enabled, devemos usar o endpoint UPDATE (PUT)
        // com todos os campos obrigatórios conforme o schema da Evolution API
        // Campos obrigatórios: enabled, webhookUrl, triggerType
        $update_data = array(
            'enabled' => $enabled,
            'webhookUrl' => $current_integration['webhookUrl'] ?? '',
            'triggerType' => $current_integration['triggerType'] ?? 'all',
            'description' => $current_integration['description'] ?? '',
            'expire' => $current_integration['expire'] ?? 60,
            'keywordFinish' => $current_integration['keywordFinish'] ?? 'sair',
            'delayMessage' => $current_integration['delayMessage'] ?? 300,
            'unknownMessage' => $current_integration['unknownMessage'] ?? 'Desculpe, não entendi...',
            'listeningFromMe' => $current_integration['listeningFromMe'] ?? false,
            'stopBotFromMe' => $current_integration['stopBotFromMe'] ?? true,
            'keepOpen' => $current_integration['keepOpen'] ?? false,
            'debounceTime' => $current_integration['debounceTime'] ?? 0,
            'ignoreJids' => $current_integration['ignoreJids'] ?? array(),
            'splitMessages' => $current_integration['splitMessages'] ?? true,
            'timePerChar' => $current_integration['timePerChar'] ?? 200,
            'triggerOperator' => $current_integration['triggerOperator'] ?? 'contains',
            'triggerValue' => $current_integration['triggerValue'] ?? ''
        );
        

        
        // Usa o endpoint UPDATE (PUT) para alterar o status
        $update_url = trailingslashit($evolution_url) . 'n8n/update/' . $integration_id . '/' . $evolution_instance;
        
        $response = wp_remote_request($update_url, array(
            'method' => 'PUT',
            'headers' => array(
                'Content-Type' => 'application/json',
                'apikey' => $evolution_apikey
            ),
            'body' => json_encode($update_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Erro na conexão: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code === 200) {
            $status_text = $enabled ? 'ativada' : 'desativada';
            wp_send_json_success("Integração {$status_text} com sucesso!");
        } else {
            wp_send_json_error('Erro ao alterar status: ' . $status_code . ' - ' . $body);
        }
    }
    
    /**
     * Atualiza webhook de integração N8N
     */
    public function update_n8n_webhook() {
        $this->validate_ajax_request();
        
        $integration_id = sanitize_text_field($_POST['integration_id']);
        $webhook_url = sanitize_text_field($_POST['webhook_url']);
        
        if (empty($integration_id) || empty($webhook_url)) {
            wp_send_json_error('ID da integração e URL do webhook são obrigatórios');
        }
        
        $evolution_url = get_option('wpain_evolution_url', '');
        $evolution_apikey = get_option('wpain_evolution_apikey', '');
        $evolution_instance = get_option('wpain_evolution_instance', '');
        
        if (empty($evolution_url) || empty($evolution_apikey) || empty($evolution_instance)) {
            wp_send_json_error('Evolution API não configurada');
        }
        
        // Primeiro busca os dados atuais da integração
        $search_url = trailingslashit($evolution_url) . 'n8n/find/' . $evolution_instance;
        
        $search_response = wp_remote_get($search_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'apikey' => $evolution_apikey
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($search_response)) {
            wp_send_json_error('Erro ao buscar integração: ' . $search_response->get_error_message());
        }
        
        $search_status = wp_remote_retrieve_response_code($search_response);
        $search_body = wp_remote_retrieve_body($search_response);
        
        if ($search_status !== 200) {
            wp_send_json_error('Erro ao buscar integração: ' . $search_status . ' - ' . $search_body);
        }
        
        $search_data = json_decode($search_body, true);
        $current_integration = null;
        

        
        // Encontra a integração específica pelo ID
        // A Evolution API pode retornar diretamente um array ou dentro de 'data'
        $integrations_array = array();
        
        if (isset($search_data['data']) && is_array($search_data['data'])) {
            // Estrutura: {"data": [...]}
            $integrations_array = $search_data['data'];
        } elseif (is_array($search_data)) {
            // Estrutura: [...] (array direto)
            $integrations_array = $search_data;
        } else {
            error_log('Estrutura da resposta inválida: ' . print_r($search_data, true));
            wp_send_json_error('Estrutura de resposta inválida da Evolution API');
        }
        
        // Busca a integração pelo ID
        foreach ($integrations_array as $integration) {
            if (isset($integration['id']) && $integration['id'] === $integration_id) {
                $current_integration = $integration;
                break;
            }
        }
        
        if (!$current_integration) {
            wp_send_json_error('Integração não encontrada. ID: ' . $integration_id . ' | Resposta: ' . json_encode($search_data));
        }
        
        // Mantém todos os dados atuais e altera apenas o webhookUrl
        $update_data = array(
            'webhookUrl' => $webhook_url,
            'enabled' => $current_integration['enabled'] ?? true,
            'description' => $current_integration['description'] ?? '',
            'expire' => $current_integration['expire'] ?? 0,
            'keywordFinish' => $current_integration['keywordFinish'] ?? 'sair',
            'delayMessage' => $current_integration['delayMessage'] ?? 0,
            'unknownMessage' => $current_integration['unknownMessage'] ?? 'Desculpe, não entendi...',
            'listeningFromMe' => $current_integration['listeningFromMe'] ?? false,
            'stopBotFromMe' => $current_integration['stopBotFromMe'] ?? false,
            'keepOpen' => $current_integration['keepOpen'] ?? false,
            'debounceTime' => $current_integration['debounceTime'] ?? 0,
            'ignoreJids' => $current_integration['ignoreJids'] ?? array(),
            'splitMessages' => $current_integration['splitMessages'] ?? false,
            'timePerChar' => $current_integration['timePerChar'] ?? 50,
            'triggerType' => $current_integration['triggerType'] ?? 'all',
            'triggerOperator' => $current_integration['triggerOperator'] ?? 'contains',
            'triggerValue' => $current_integration['triggerValue'] ?? ''
        );
        
        // Atualiza webhook usando o endpoint correto
        $update_url = trailingslashit($evolution_url) . 'n8n/update/' . $integration_id . '/' . $evolution_instance;
        
        $response = wp_remote_request($update_url, array(
            'method' => 'PUT',
            'headers' => array(
                'Content-Type' => 'application/json',
                'apikey' => $evolution_apikey
            ),
            'body' => json_encode($update_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Erro na conexão: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code === 200) {
            wp_send_json_success('Webhook atualizado com sucesso!');
        } else {
            wp_send_json_error('Erro ao atualizar webhook: ' . $status_code . ' - ' . $body);
        }
    }
    
    /**
     * Retorna o webhook configurado no plugin
     */
    public function get_webhook() {
        $this->validate_ajax_request();
        
        $webhook = get_option('wpain_n8n_webhook', '');
        wp_send_json_success(array('webhook' => $webhook));
    }
}
