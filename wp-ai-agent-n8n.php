<?php
/**
 * Plugin Name: WP AI Agent N8N
 * Plugin URI: https://github.com/relaxsolucoes/wp-ai-agent-n8n
 * Description: Plugin WordPress para integração com Agente de IA via N8N e Evolution API
 * Version: 1.0.0
 * Author: Relax Soluções
 * Author URI: https://relaxsolucoes.online/
 * Text Domain: wp-ai-agent-n8n
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.8
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Constantes do plugin
define('WPAIN_VERSION', '1.0.0');
define('WPAIN_FILE', __FILE__);
define('WPAIN_PATH', plugin_dir_path(__FILE__));
define('WPAIN_URL', plugin_dir_url(__FILE__));
define('WPAIN_MIN_PHP_VERSION', '7.4');
define('WPAIN_MIN_WP_VERSION', '5.8');
define('WPAIN_GITHUB_REPO', 'RelaxSolucoes/wp-ai-agent-n8n');

/**
 * Verifica requisitos mínimos
 */
function wpain_check_requirements() {
    $errors = array();
    
    // Verifica versão do PHP
    if (version_compare(PHP_VERSION, WPAIN_MIN_PHP_VERSION, '<')) {
        $errors[] = sprintf(
            'O plugin WP AI Agent N8N requer PHP %s ou superior. Sua versão atual é %s.',
            WPAIN_MIN_PHP_VERSION,
            PHP_VERSION
        );
    }
    
    // Verifica versão do WordPress
    if (version_compare(get_bloginfo('version'), WPAIN_MIN_WP_VERSION, '<')) {
        $errors[] = sprintf(
            'O plugin WP AI Agent N8N requer WordPress %s ou superior. Sua versão atual é %s.',
            WPAIN_MIN_WP_VERSION,
            get_bloginfo('version')
        );
    }
    
    if (!empty($errors)) {
        add_action('admin_notices', function() use ($errors) {
            echo '<div class="notice notice-error"><p>';
            foreach ($errors as $error) {
                echo esc_html($error) . '<br>';
            }
            echo '</p></div>';
        });
        return false;
    }
    
    return true;
}

/**
 * Inicializa o plugin
 */
function wpain_init() {
    // Verifica requisitos
    if (!wpain_check_requirements()) {
        return;
    }
    
    // Carrega classes principais
    require_once WPAIN_PATH . 'includes/class-wpain-loader.php';
    require_once WPAIN_PATH . 'includes/class-wpain-settings.php';
    require_once WPAIN_PATH . 'includes/class-wpain-ai-agent.php';
    require_once WPAIN_PATH . 'includes/class-wpain-form-handler.php';
    
    // Inicializa o plugin
    WPAIN_Loader::init();
    
    // Adiciona link de configurações na página de plugins
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wpain_add_settings_link');
}
add_action('plugins_loaded', 'wpain_init');

/**
 * Adiciona link de configurações na página de plugins
 */
function wpain_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wp-ai-agent-n8n') . '">' . __('Configurações', 'wp-ai-agent-n8n') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// ===== AUTO-UPDATE GITHUB =====
function wpain_init_auto_updater() {
    // Só carrega a biblioteca se ela ainda não foi carregada por outro plugin
    if (!class_exists('YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory')) {
        require_once WPAIN_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';
    }
    
    $myUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/' . WPAIN_GITHUB_REPO,
        __FILE__,
        'wp-ai-agent-n8n'
    );
}
add_action('init', 'wpain_init_auto_updater');
// ===== FIM AUTO-UPDATE =====

/**
 * Ativação do plugin
 */
register_activation_hook(__FILE__, 'wpain_activate');
function wpain_activate() {
    // Cria opções padrão
    add_option('wpain_evolution_url', '');
    add_option('wpain_evolution_apikey', '');
    add_option('wpain_evolution_instance', '');
    add_option('wpain_n8n_webhook', '');
    
    // Configurações do chat
    add_option('wpain_chat_enabled', false);
    add_option('wpain_chat_title', 'Olá! 👋');
    add_option('wpain_chat_subtitle', 'Como posso ajudar você hoje?');
    add_option('wpain_chat_placeholder', 'Digite sua pergunta...');
    add_option('wpain_chat_welcome', 'Olá! 👋 Eu sou o assistente virtual. Como posso te ajudar hoje?');
    
    // Configurações do formulário
    add_option('wpain_form_enabled', false);
    add_option('wpain_form_title', 'Fale com nosso Assistente Virtual');
    
    // Campos do formulário (editáveis pelo usuário)
    add_option('wpain_form_nome_label', 'Nome');
    add_option('wpain_form_nome_placeholder', '');
    add_option('wpain_form_email_label', 'E-mail');
    add_option('wpain_form_email_placeholder', '');
    add_option('wpain_form_whatsapp_label', 'WhatsApp');
    add_option('wpain_form_whatsapp_placeholder', '(11) 99999-9999');
    add_option('wpain_form_mensagem_label', 'Sua Pergunta/Solicitação');
    add_option('wpain_form_mensagem_placeholder', 'Digite sua pergunta ou solicitação para o assistente virtual...');
    
    // Botão do formulário
    add_option('wpain_form_button_text', '🤖 Enviar para o Bot');
    add_option('wpain_form_button_loading_text', '⏳ Enviando para o bot...');
    
    // Configurações do WhatsApp (para implementação futura)
    add_option('wpain_whatsapp_enabled', false);
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Desativação do plugin
 */
register_deactivation_hook(__FILE__, 'wpain_deactivate');
function wpain_deactivate() {
    // Remove opções temporárias se necessário
    flush_rewrite_rules();
}

/**
 * Desinstalação do plugin
 */
register_uninstall_hook(__FILE__, 'wpain_uninstall');
function wpain_uninstall() {
    // Remove todas as opções
    delete_option('wpain_evolution_url');
    delete_option('wpain_evolution_apikey');
    delete_option('wpain_evolution_instance');
    delete_option('wpain_n8n_webhook');
    delete_option('wpain_chat_enabled');
    delete_option('wpain_chat_title');
    delete_option('wpain_chat_subtitle');
    delete_option('wpain_chat_placeholder');
    delete_option('wpain_chat_welcome');
    delete_option('wpain_form_enabled');
    delete_option('wpain_form_title');
    
    // Remove opções dos campos do formulário
    delete_option('wpain_form_nome_label');
    delete_option('wpain_form_nome_placeholder');
    delete_option('wpain_form_email_label');
    delete_option('wpain_form_email_placeholder');
    delete_option('wpain_form_whatsapp_label');
    delete_option('wpain_form_whatsapp_placeholder');
    delete_option('wpain_form_mensagem_label');
    delete_option('wpain_form_mensagem_placeholder');
    
    // Remove opções do botão
    delete_option('wpain_form_button_text');
    delete_option('wpain_form_button_loading_text');
    delete_option('wpain_whatsapp_enabled');
}
