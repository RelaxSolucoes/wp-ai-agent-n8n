<?php
/**
 * Renderizador da interface administrativa do plugin WP AI Agent N8N
 * 
 * @package WP_AI_Agent_N8N
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPAIN_Admin_Renderer {
    
    /**
     * Handler de configurações
     */
    private $config_handler;
    
    /**
     * Aba ativa atual
     */
    private $active_tab;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->config_handler = new WPAIN_Config_Handler();
        $this->active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'connection';
    }
    
    /**
     * Renderiza a página principal
     */
    public function render_page() {
        ?>
        <div class="wrap wpain-admin-page">
            <?php $this->render_header(); ?>
            <?php $this->render_navigation(); ?>
            <?php $this->render_tab_content(); ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza o cabeçalho da página
     */
    private function render_header() {
        ?>
        <div class="wpain-header">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p class="wpain-description">
                <?php esc_html_e('Configure seu agente de IA para integração com N8N e Evolution API', 'wp-ai-agent-n8n'); ?>
            </p>
            
            <?php $this->render_important_notice(); ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza aviso importante sobre N8N
     */
    private function render_important_notice() {
        ?>
        <div class="wpain-notice wpain-notice-warning">
            <div class="wpain-notice-icon">⚠️</div>
            <div class="wpain-notice-content">
                <div class="wpain-notice-title"><?php esc_html_e('Importante', 'wp-ai-agent-n8n'); ?></div>
                <div class="wpain-notice-text">
                    <?php 
                    printf(
                        esc_html__('Para usar respostas do Agente de IA, você precisa de um fluxo N8N para receber o webhook. %s', 'wp-ai-agent-n8n'),
                        '<a href="https://github.com/RelaxSolucoes/Fluxo-Wordpress-IA" target="_blank" rel="noopener noreferrer" class="wpain-link">' . 
                        esc_html__('Veja o exemplo de fluxo de trabalho neste link', 'wp-ai-agent-n8n') . '</a>'
                    );
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderiza a navegação por abas
     */
    private function render_navigation() {
        $tabs = array(
            'connection' => array(
                'icon' => '🔌',
                'title' => __('Conexão', 'wp-ai-agent-n8n')
            ),
            'chat' => array(
                'icon' => '💬',
                'title' => __('Chat', 'wp-ai-agent-n8n')
            ),
            'form' => array(
                'icon' => '📝',
                'title' => __('Formulário', 'wp-ai-agent-n8n')
            ),
            'whatsapp' => array(
                'icon' => '📱',
                'title' => __('WhatsApp', 'wp-ai-agent-n8n')
            )
        );
        ?>
        <nav class="nav-tab-wrapper wpain-nav-tabs">
            <?php foreach ($tabs as $tab_key => $tab_data): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wp-ai-agent-n8n&tab=' . $tab_key)); ?>" 
                   class="nav-tab <?php echo $this->active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                    <span class="wpain-tab-icon"><?php echo $tab_data['icon']; ?></span>
                    <?php echo esc_html($tab_data['title']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php
    }
    
    /**
     * Renderiza o conteúdo da aba ativa
     */
    private function render_tab_content() {
        ?>
        <div class="wpain-tab-content">
            <?php
            switch ($this->active_tab) {
                case 'connection':
                    $this->render_connection_tab();
                    break;
                case 'chat':
                    $this->render_chat_tab();
                    break;
                case 'form':
                    $this->render_form_tab();
                    break;
                case 'whatsapp':
                    $this->render_whatsapp_tab();
                    break;
                default:
                    $this->render_connection_tab();
            }
            ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza aba de conexão
     */
    private function render_connection_tab() {
        ?>
        <div class="wpain-tab-pane">
            <h2 class="wpain-section-title">
                <span class="wpain-icon">🔌</span>
                <?php esc_html_e('Configurações de Conexão', 'wp-ai-agent-n8n'); ?>
            </h2>
            
            <?php $this->render_evolution_section(); ?>
            <?php $this->render_n8n_section(); ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza seção Evolution API
     */
    private function render_evolution_section() {
        $evolution_config = $this->config_handler->get_config_group('evolution');
        ?>
        <div class="wpain-section">
            <div class="wpain-section-header">
                <h3><?php echo esc_html($evolution_config['title']); ?></h3>
                <p><?php esc_html_e('Configure a conexão com sua instância da Evolution API para integração com WhatsApp.', 'wp-ai-agent-n8n'); ?></p>
            </div>
            
            <form method="post" action="options.php" class="wpain-form">
                <?php settings_fields($evolution_config['group']); ?>
                
                <div class="wpain-form-grid">
                    <?php $this->render_config_fields($evolution_config['fields']); ?>
                </div>
                
                <div class="wpain-form-actions">
                    <button type="button" class="button button-secondary wpain-test-btn" data-test="evolution">
                        <span class="wpain-btn-icon">🧪</span>
                        <?php esc_html_e('Testar Conexão', 'wp-ai-agent-n8n'); ?>
                    </button>
                    <?php submit_button(__('Salvar Configurações Evolution API', 'wp-ai-agent-n8n'), 'primary', 'submit', false); ?>
                </div>
                
                <div id="evolution-test-result" class="wpain-test-result"></div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Renderiza seção N8N
     */
    private function render_n8n_section() {
        $n8n_config = $this->config_handler->get_config_group('n8n');
        ?>
        <div class="wpain-section">
            <div class="wpain-section-header">
                <h3><?php echo esc_html($n8n_config['title']); ?></h3>
                <p><?php esc_html_e('Configure o webhook do N8N para receber mensagens do chat e formulário.', 'wp-ai-agent-n8n'); ?></p>
            </div>
            
            <form method="post" action="options.php" class="wpain-form">
                <?php settings_fields($n8n_config['group']); ?>
                
                <div class="wpain-form-grid">
                    <?php $this->render_config_fields($n8n_config['fields']); ?>
                </div>
                
                <div class="wpain-form-actions">
                    <button type="button" class="button button-secondary wpain-test-btn" data-test="n8n">
                        <span class="wpain-btn-icon">🧪</span>
                        <?php esc_html_e('Testar Webhook', 'wp-ai-agent-n8n'); ?>
                    </button>
                    <?php submit_button(__('Salvar Configurações N8N', 'wp-ai-agent-n8n'), 'primary', 'submit', false); ?>
                </div>
                
                <div id="n8n-test-result" class="wpain-test-result"></div>
            </form>
            
            <?php $this->render_n8n_info(); ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza informações sobre N8N
     */
    private function render_n8n_info() {
        ?>
        <div class="wpain-info-box wpain-info-blue">
            <h4>
                <span class="wpain-info-icon">📋</span>
                <?php esc_html_e('Como configurar o N8N', 'wp-ai-agent-n8n'); ?>
            </h4>
            
            <ol class="wpain-list">
                <li><?php esc_html_e('Instale o N8N em seu servidor ou use o N8N Cloud', 'wp-ai-agent-n8n'); ?></li>
                <li><?php esc_html_e('Importe o fluxo modelo disponível no GitHub', 'wp-ai-agent-n8n'); ?></li>
                <li><?php esc_html_e('Configure o webhook com a URL gerada', 'wp-ai-agent-n8n'); ?></li>
                <li><?php esc_html_e('Cole a URL do webhook no campo acima', 'wp-ai-agent-n8n'); ?></li>
            </ol>
            
            <div class="wpain-info-actions">
                <a href="https://github.com/RelaxSolucoes/Fluxo-Wordpress-IA" target="_blank" rel="noopener noreferrer" 
                   class="button button-primary">
                    <span class="wpain-btn-icon">📥</span>
                    <?php esc_html_e('Baixar Fluxo Modelo N8N', 'wp-ai-agent-n8n'); ?>
                </a>
                <a href="https://n8n.io/" target="_blank" rel="noopener noreferrer" 
                   class="button button-secondary">
                    <span class="wpain-btn-icon">🌐</span>
                    <?php esc_html_e('Visitar N8N.io', 'wp-ai-agent-n8n'); ?>
                </a>
            </div>
            
            <p class="wpain-info-tip">
                <strong><?php esc_html_e('💡 Dica:', 'wp-ai-agent-n8n'); ?></strong>
                <?php esc_html_e('O fluxo modelo inclui integração com IA, WhatsApp via Evolution API e roteamento inteligente de mensagens.', 'wp-ai-agent-n8n'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Renderiza aba do chat
     */
    private function render_chat_tab() {
        $chat_config = $this->config_handler->get_config_group('chat');
        ?>
        <div class="wpain-tab-pane">
            <h2 class="wpain-section-title">
                <span class="wpain-icon">💬</span>
                <?php esc_html_e('Configurações do Chat', 'wp-ai-agent-n8n'); ?>
            </h2>
            <p><?php esc_html_e('Configure o widget de chat que será exibido no seu site.', 'wp-ai-agent-n8n'); ?></p>
            
            <form method="post" action="options.php" class="wpain-form">
                <?php settings_fields($chat_config['group']); ?>
                
                <div class="wpain-form-grid">
                    <?php $this->render_config_fields($chat_config['fields']); ?>
                </div>
                
                <?php $this->render_usage_info('chat'); ?>
                <?php $this->render_chat_features(); ?>
                
                <?php submit_button(__('Salvar Configurações do Chat', 'wp-ai-agent-n8n')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Renderiza recursos do chat
     */
    private function render_chat_features() {
        ?>
        <div class="wpain-info-box wpain-info-green">
            <h4>
                <span class="wpain-info-icon">💬</span>
                <?php esc_html_e('Chat Inteligente com N8N', 'wp-ai-agent-n8n'); ?>
            </h4>
            
            <p><?php esc_html_e('O widget de chat se conecta diretamente ao N8N para fornecer respostas inteligentes. Funcionalidades disponíveis:', 'wp-ai-agent-n8n'); ?></p>
            
            <ul class="wpain-list">
                <li><?php esc_html_e('Respostas em tempo real via IA', 'wp-ai-agent-n8n'); ?></li>
                <li><?php esc_html_e('Integração com base de conhecimento', 'wp-ai-agent-n8n'); ?></li>
                <li><?php esc_html_e('Roteamento inteligente de mensagens', 'wp-ai-agent-n8n'); ?></li>
                <li><?php esc_html_e('Histórico de conversas', 'wp-ai-agent-n8n'); ?></li>
                <li><?php esc_html_e('Suporte multilíngue', 'wp-ai-agent-n8n'); ?></li>
            </ul>
            
            <p class="wpain-info-tip">
                <strong><?php esc_html_e('🚀 Próximos passos:', 'wp-ai-agent-n8n'); ?></strong>
                <a href="https://github.com/RelaxSolucoes/Fluxo-Wordpress-IA" target="_blank" rel="noopener noreferrer" class="wpain-link">
                    <?php esc_html_e('Configure o fluxo N8N para ativar o chat inteligente', 'wp-ai-agent-n8n'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Renderiza aba do formulário
     */
    private function render_form_tab() {
        $form_config = $this->config_handler->get_config_group('form');
        ?>
        <div class="wpain-tab-pane">
            <h2 class="wpain-section-title">
                <span class="wpain-icon">📝</span>
                <?php esc_html_e('Configurações do Formulário', 'wp-ai-agent-n8n'); ?>
            </h2>
            <p><?php esc_html_e('Configure o formulário de contato que envia mensagens para o N8N.', 'wp-ai-agent-n8n'); ?></p>
            
            <form method="post" action="options.php" class="wpain-form">
                <?php settings_fields($form_config['group']); ?>
                
                <div class="wpain-form-grid">
                    <?php $this->render_config_fields($form_config['fields']); ?>
                </div>
                
                <?php $this->render_usage_info('form'); ?>
                <?php $this->render_form_integration_info(); ?>
                
                <?php submit_button(__('Salvar Configurações do Formulário', 'wp-ai-agent-n8n')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Renderiza informações sobre integração do formulário
     */
    private function render_form_integration_info() {
        ?>
        <div class="wpain-info-box wpain-info-blue">
            <h4>
                <span class="wpain-info-icon">🔗</span>
                <?php esc_html_e('Integração com N8N', 'wp-ai-agent-n8n'); ?>
            </h4>
            
            <p><?php esc_html_e('Quando um usuário envia o formulário, os dados são enviados para o N8N através do webhook configurado. O N8N processa a solicitação com IA e pode:', 'wp-ai-agent-n8n'); ?></p>
            
            <ul class="wpain-list">
                <li><?php esc_html_e('Processar mensagens com inteligência artificial', 'wp-ai-agent-n8n'); ?></li>
                <li><?php esc_html_e('Enviar resposta no WhatsApp via Evolution API', 'wp-ai-agent-n8n'); ?></li>
                <li><?php esc_html_e('Manter contexto da conversa com memória', 'wp-ai-agent-n8n'); ?></li>
                <li><?php esc_html_e('Detectar origem da mensagem (formulário, chat ou WhatsApp)', 'wp-ai-agent-n8n'); ?></li>
            </ul>
            
            <p class="wpain-info-tip">
                <strong><?php esc_html_e('📚 Documentação:', 'wp-ai-agent-n8n'); ?></strong>
                <a href="https://github.com/RelaxSolucoes/Fluxo-Wordpress-IA" target="_blank" rel="noopener noreferrer" class="wpain-link">
                    <?php esc_html_e('Consulte o fluxo modelo para entender a estrutura dos dados', 'wp-ai-agent-n8n'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Renderiza aba do WhatsApp
     */
    private function render_whatsapp_tab() {
        $is_evolution_configured = $this->config_handler->validate_evolution_config();
        ?>
        <div class="wpain-tab-pane">
            <h2 class="wpain-section-title">
                <span class="wpain-icon">📱</span>
                <?php esc_html_e('Integração N8N com WhatsApp', 'wp-ai-agent-n8n'); ?>
            </h2>
            <p><?php esc_html_e('Gerencie integrações N8N na Evolution API para processamento de mensagens WhatsApp.', 'wp-ai-agent-n8n'); ?></p>
            
            <?php if (!$is_evolution_configured): ?>
                <?php $this->render_evolution_required_notice(); ?>
            <?php else: ?>
                <div id="whatsapp-content">
                    <div class="wpain-loading" id="whatsapp-loading">
                        <p><?php esc_html_e('🔄 Verificando integrações N8N...', 'wp-ai-agent-n8n'); ?></p>
                    </div>
                    <div id="whatsapp-results" style="display: none;"></div>
                </div>
                
                <?php $this->render_whatsapp_info(); ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza aviso de configuração Evolution necessária
     */
    private function render_evolution_required_notice() {
        ?>
        <div class="wpain-notice wpain-notice-error">
            <div class="wpain-notice-icon">⚠️</div>
            <div class="wpain-notice-content">
                <div class="wpain-notice-title"><?php esc_html_e('Configuração Necessária', 'wp-ai-agent-n8n'); ?></div>
                <div class="wpain-notice-text">
                    <?php esc_html_e('Para usar esta funcionalidade, você precisa configurar a Evolution API na aba "Conexão".', 'wp-ai-agent-n8n'); ?>
                </div>
                <div class="wpain-notice-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wp-ai-agent-n8n&tab=connection')); ?>" 
                       class="button button-primary">
                        <?php esc_html_e('Configurar Evolution API', 'wp-ai-agent-n8n'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderiza informações sobre WhatsApp
     */
    private function render_whatsapp_info() {
        ?>
        <div class="wpain-info-box wpain-info-blue">
            <h4>
                <span class="wpain-info-icon">🔗</span>
                <?php esc_html_e('Como Funciona a Integração', 'wp-ai-agent-n8n'); ?>
            </h4>
            
            <p><?php esc_html_e('Esta funcionalidade gerencia automaticamente integrações N8N na Evolution API:', 'wp-ai-agent-n8n'); ?></p>
            
            <ul class="wpain-list">
                <li><strong><?php esc_html_e('Detecção Automática:', 'wp-ai-agent-n8n'); ?></strong> <?php esc_html_e('Verifica integrações N8N automaticamente', 'wp-ai-agent-n8n'); ?></li>
                <li><strong><?php esc_html_e('Gerenciamento Inteligente:', 'wp-ai-agent-n8n'); ?></strong> <?php esc_html_e('Oferece apenas ações necessárias', 'wp-ai-agent-n8n'); ?></li>
                <li><strong><?php esc_html_e('Preservação de Dados:', 'wp-ai-agent-n8n'); ?></strong> <?php esc_html_e('Mantém configurações existentes intactas', 'wp-ai-agent-n8n'); ?></li>
                <li><strong><?php esc_html_e('Múltiplas Integrações:', 'wp-ai-agent-n8n'); ?></strong> <?php esc_html_e('Permite escolher qual integração usar', 'wp-ai-agent-n8n'); ?></li>
            </ul>
            
            <p class="wpain-info-tip">
                <strong><?php esc_html_e('🎯 Comportamento:', 'wp-ai-agent-n8n'); ?></strong>
                <?php esc_html_e('Se houver múltiplas integrações, você pode escolher qual usar. Se não houver nenhuma, será criada automaticamente com os valores da aba "🔌 Conexão".', 'wp-ai-agent-n8n'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Renderiza campos de configuração
     */
    private function render_config_fields($fields) {
        foreach ($fields as $field_name => $field_config) {
            $this->render_single_field($field_name, $field_config);
        }
    }
    
    /**
     * Renderiza um campo individual
     */
    private function render_single_field($field_name, $field_config) {
        $value = get_option($field_name, $field_config['default']);
        $field_type = $field_config['type'];
        ?>
        <div class="wpain-form-field">
            <label for="<?php echo esc_attr($field_name); ?>" class="wpain-field-label">
                <?php echo esc_html($field_config['label']); ?>
            </label>
            
            <?php if ($field_type === 'boolean'): ?>
                <div class="wpain-checkbox-wrapper">
                    <input type="checkbox" 
                           id="<?php echo esc_attr($field_name); ?>" 
                           name="<?php echo esc_attr($field_name); ?>" 
                           value="1" 
                           <?php checked($value, true); ?>
                           class="wpain-checkbox" />
                    <span class="wpain-checkbox-description">
                        <?php echo esc_html($field_config['description']); ?>
                    </span>
                </div>
            <?php else: ?>
                <?php if (strpos($field_name, 'welcome') !== false): ?>
                    <textarea id="<?php echo esc_attr($field_name); ?>" 
                              name="<?php echo esc_attr($field_name); ?>" 
                              rows="3" 
                              class="wpain-textarea"><?php echo esc_textarea($value); ?></textarea>
                <?php else: ?>
                    <input type="<?php echo $field_name === 'wpain_evolution_url' || $field_name === 'wpain_n8n_webhook' ? 'url' : 'text'; ?>" 
                           id="<?php echo esc_attr($field_name); ?>" 
                           name="<?php echo esc_attr($field_name); ?>" 
                           value="<?php echo esc_attr($value); ?>" 
                           class="wpain-input" />
                <?php endif; ?>
                
                <?php if (!empty($field_config['description'])): ?>
                    <p class="wpain-field-description"><?php echo esc_html($field_config['description']); ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Renderiza informações de uso
     */
    private function render_usage_info($type) {
        $shortcode = $type === 'chat' ? '[wpain_chat]' : '[wpain_form]';
        $title = $type === 'chat' ? __('Chat', 'wp-ai-agent-n8n') : __('Formulário', 'wp-ai-agent-n8n');
        ?>
        <div class="wpain-usage-info">
            <h3><?php esc_html_e('Como usar', 'wp-ai-agent-n8n'); ?></h3>
            <div class="wpain-usage-grid">
                <div class="wpain-usage-item">
                    <strong><?php esc_html_e('Shortcode:', 'wp-ai-agent-n8n'); ?></strong>
                    <code class="wpain-code"><?php echo esc_html($shortcode); ?></code>
                </div>
                <div class="wpain-usage-item">
                    <strong><?php esc_html_e('Inserção:', 'wp-ai-agent-n8n'); ?></strong>
                    <?php printf(esc_html__('Use o shortcode em qualquer página ou post para exibir o %s', 'wp-ai-agent-n8n'), strtolower($title)); ?>
                </div>
            </div>
        </div>
        <?php
    }
}
