<?php
/**
 * Classe de configurações do plugin WP AI Agent N8N
 * 
 * @package WP_AI_Agent_N8N
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Função helper para gerar campos de formulário repetitivos
 */
function wpain_render_form_field($field_name, $field_config, $type = 'text') {
    $field_id = "wpain_form_{$field_name}";
    $field_value = get_option($field_id, $field_config['default'] ?? '');
    
    echo "<tr>";
    echo "<th scope='row'>";
    echo "<label for='{$field_id}'>{$field_config['label']}</label>";
    echo "</th>";
    echo "<td>";
    
    if ($type === 'textarea') {
        echo "<textarea id='{$field_id}' name='{$field_id}' rows='3' class='large-text'>" . esc_textarea($field_value) . "</textarea>";
    } else {
        echo "<input type='{$type}' id='{$field_id}' name='{$field_id}' value='" . esc_attr($field_value) . "' class='regular-text' />";
    }
    
    if (!empty($field_config['description'])) {
        echo "<p class='description'>{$field_config['description']}</p>";
    }
    echo "</td>";
    echo "</tr>";
}

class WPAIN_Settings {
    
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
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Registra as configurações
     */
    public function register_settings() {
        // Configurações da Evolution API
        register_setting('wpain_evolution_group', 'wpain_evolution_url', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        
        register_setting('wpain_evolution_group', 'wpain_evolution_apikey', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('wpain_evolution_group', 'wpain_evolution_instance', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        // Configurações do N8N
        register_setting('wpain_n8n_group', 'wpain_n8n_webhook', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        
        // Configurações do Chat
        register_setting('wpain_chat_group', 'wpain_chat_enabled', array(
            'type' => 'boolean',
            'sanitize_callback' => function($val) { return (bool)$val; },
            'default' => false
        ));
        
        register_setting('wpain_chat_group', 'wpain_chat_title', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Olá! 👋'
        ));
        
        register_setting('wpain_chat_group', 'wpain_chat_subtitle', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Como posso ajudar você hoje?'
        ));
        
        register_setting('wpain_chat_group', 'wpain_chat_placeholder', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Digite sua pergunta...'
        ));
        
        register_setting('wpain_chat_group', 'wpain_chat_welcome', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => 'Olá! 👋 Eu sou o assistente virtual. Como posso te ajudar hoje?'
        ));
        
        // Configurações do Formulário
        register_setting('wpain_form_group', 'wpain_form_enabled', array(
            'type' => 'boolean',
            'sanitize_callback' => function($val) { return (bool)$val; },
            'default' => false
        ));
        
        register_setting('wpain_form_group', 'wpain_form_whatsapp_validation', array(
            'type' => 'boolean',
            'sanitize_callback' => function($val) { return (bool)$val; },
            'default' => true
        ));
        
        register_setting('wpain_form_group', 'wpain_form_title', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Fale com nosso Assistente Virtual'
        ));
        
        // Campos do formulário (configuração simplificada)
        $form_fields = array(
            'nome' => array('label' => 'Nome', 'placeholder' => ''),
            'email' => array('label' => 'E-mail', 'placeholder' => ''),
            'whatsapp' => array('label' => 'WhatsApp', 'placeholder' => '(11) 99999-9999'),
            'mensagem' => array('label' => 'Sua Pergunta/Solicitação', 'placeholder' => 'Digite sua pergunta ou solicitação para o assistente virtual...')
        );
        
        foreach ($form_fields as $field => $config) {
            register_setting('wpain_form_group', "wpain_form_{$field}_label", array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => $config['label']
            ));
            
            register_setting('wpain_form_group', "wpain_form_{$field}_placeholder", array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => $config['placeholder']
            ));
        }
        
        // Botão do formulário
        register_setting('wpain_form_group', 'wpain_form_button_text', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '🤖 Enviar para o Bot'
        ));
        
        register_setting('wpain_form_group', 'wpain_form_button_loading_text', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '⏳ Enviando para o bot...'
        ));
        
        // Configurações do WhatsApp (para implementação futura)
        register_setting('wpain_whatsapp_group', 'wpain_whatsapp_enabled', array(
            'type' => 'boolean',
            'sanitize_callback' => function($val) { return (bool)$val; },
            'default' => false
        ));
    }
    
    /**
     * Renderiza a página administrativa
     */
    public static function render_admin_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'connection';
        ?>
        <div class="wrap">
            <h1>WP AI Agent N8N</h1>
            <p>Configure seu agente de IA para integração com N8N e Evolution API</p>
            
            <!-- Aviso importante sobre N8N -->
            <div style="margin:16px 0 24px 0; padding:16px 20px; border-left:6px solid #f59e0b; background:#fffbeb; border-radius:8px;">
                <div style="font-size:16px; color:#92400e; font-weight:700; margin-bottom:6px;">⚠️ Importante</div>
                <div style="font-size:15px; color:#78350f; line-height:1.5;">
                    Para usar respostas do Agente de IA, você precisa de um fluxo N8N para receber o webhook.
                    <a href="https://github.com/RelaxSolucoes/Fluxo-Wordpress-IA" target="_blank" rel="noopener noreferrer" style="font-weight:700; color:#b45309; text-decoration: underline;">
                        Veja o exemplo de fluxo de trabalho neste link
                    </a>.
                </div>
            </div>
            
            <!-- Abas de navegação -->
            <nav class="nav-tab-wrapper">
                <a href="?page=wp-ai-agent-n8n&tab=connection" 
                   class="nav-tab <?php echo $active_tab === 'connection' ? 'nav-tab-active' : ''; ?>">
                    🔌 Conexão
                </a>
                <a href="?page=wp-ai-agent-n8n&tab=chat" 
                   class="nav-tab <?php echo $active_tab === 'chat' ? 'nav-tab-active' : ''; ?>">
                    💬 Chat
                </a>
                <a href="?page=wp-ai-agent-n8n&tab=form" 
                   class="nav-tab <?php echo $active_tab === 'form' ? 'nav-tab-active' : ''; ?>">
                    📝 Formulário
                </a>
                <a href="?page=wp-ai-agent-n8n&tab=whatsapp" 
                   class="nav-tab <?php echo $active_tab === 'whatsapp' ? 'nav-tab-active' : ''; ?>">
                    📱 WhatsApp
                </a>
            </nav>
            
            <!-- Conteúdo das abas -->
            <div class="tab-content">
                <?php
                switch ($active_tab) {
                    case 'connection':
                        self::render_connection_tab();
                        break;
                    case 'chat':
                        self::render_chat_tab();
                        break;
                    case 'form':
                        self::render_form_tab();
                        break;
                    case 'whatsapp':
                        self::render_whatsapp_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderiza aba de conexão
     */
    private static function render_connection_tab() {
        ?>
        <div class="tab-pane">
            <h2>🔌 Configurações de Conexão</h2>
            
            <!-- Evolution API -->
            <div class="connection-section">
                <h3>Evolution API</h3>
                <p>Configure a conexão com sua instância da Evolution API para integração com WhatsApp.</p>
                
                <!-- CTA Evolution API -->
                <div class="wpain-cta-box" style="margin: 20px 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: rgba(255,255,255,0.1); border-radius: 50%; opacity: 0.3;"></div>
                    <div style="position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background: rgba(255,255,255,0.05); border-radius: 50%; opacity: 0.4;"></div>
                    
                    <div class="wpain-cta-content" style="position: relative; z-index: 2;">
                        <h3 style="margin: 0 0 12px 0; color: white; font-size: 18px; font-weight: 600;">
                            <span style="font-size: 20px;">❌</span> Não tem uma API Evolution?
                        </h3>
                        <p style="margin: 0 0 16px 0; color: rgba(255,255,255,0.9); line-height: 1.5;">
                            <span style="font-size: 16px;">🎯</span> <strong>Conecte seu assistente de IA ao WhatsApp em minutos!</strong><br>
                            <span style="font-size: 16px;">✨</span> Ative sua instância agora e tenha respostas automáticas inteligentes no WhatsApp.<br>
                            <span style="font-size: 16px;">🧭</span> Clique em <strong>"🚀 Teste Grátis Evolution API"</strong>, crie sua conta e receba suas <strong>Credenciais</strong>. Cole nas configurações abaixo e teste grátis por <strong>7 dias</strong>.
                        </p>
                        
                        <a href="https://whats-evolution.vercel.app/" 
                           class="button button-primary" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           style="background: #ffffff; color: #667eea; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: all 0.3s ease;"
                           onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(0,0,0,0.2)';"
                           onmouseout="this.style.transform='translateY(0px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)';">
                            <span style="font-size: 16px;">🚀</span> Teste Grátis Evolution API
                        </a>
                    </div>
                </div>
                
                <form method="post" action="options.php" class="connection-form">
                    <?php settings_fields('wpain_evolution_group'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="wpain_evolution_url">URL da API</label>
                            </th>
                            <td>
                                <input type="url" id="wpain_evolution_url" name="wpain_evolution_url" 
                                       value="<?php echo esc_attr(get_option('wpain_evolution_url')); ?>" 
                                       class="regular-text" placeholder="https://sua-api.com" />
                                <p class="description">URL base da sua Evolution API (ex: https://api.evolution.com)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="wpain_evolution_apikey">API Key</label>
                            </th>
                            <td>
                                <input type="text" id="wpain_evolution_apikey" name="wpain_evolution_apikey" 
                                       value="<?php echo esc_attr(get_option('wpain_evolution_apikey')); ?>" 
                                       class="regular-text" placeholder="sua-api-key" />
                                <p class="description">Chave de API da Evolution</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="wpain_evolution_instance">Nome da Instância</label>
                            </th>
                            <td>
                                <input type="text" id="wpain_evolution_instance" name="wpain_evolution_instance" 
                                       value="<?php echo esc_attr(get_option('wpain_evolution_instance')); ?>" 
                                       class="regular-text" placeholder="minha-instancia" />
                                <p class="description">Nome da instância WhatsApp configurada</p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="connection-actions">
                        <button type="button" class="button button-secondary" id="test-connection">
                            🧪 Testar Conexão
                        </button>
                        <span class="spinner" id="connection-spinner" style="float: none; margin-left: 10px;"></span>
                        <div id="connection-result" class="connection-result"></div>
                    </div>
                    
                    <?php submit_button('Salvar Configurações Evolution API'); ?>
                 </form>
             </div>
            
            <!-- N8N Webhook -->
            <div class="connection-section">
                <h3>N8N Webhook</h3>
                <p>Configure o webhook do N8N para receber mensagens do chat e formulário.</p>
                
                <form method="post" action="options.php" class="connection-form">
                    <?php settings_fields('wpain_n8n_group'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="wpain_n8n_webhook">URL do Webhook</label>
                            </th>
                            <td>
                                <input type="url" id="wpain_n8n_webhook" name="wpain_n8n_webhook" 
                                       value="<?php echo esc_attr(get_option('wpain_n8n_webhook')); ?>" 
                                       class="regular-text" placeholder="https://seu-n8n.com/webhook/xyz" />
                                <p class="description">URL do webhook configurado no N8N</p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="connection-actions">
                        <button type="button" class="button button-secondary" id="test-webhook">
                            🧪 Testar Webhook
                        </button>
                        <span class="spinner" id="webhook-spinner" style="float: none; margin-left: 10px;"></span>
                        <div id="webhook-result" class="connection-result"></div>
                    </div>
                    
                    <?php submit_button('Salvar Configurações N8N'); ?>
                </form>
                
                <!-- Configuração detalhada do N8N -->
                <div style="margin-top: 20px; padding: 16px; background: #f0f5ff; border-left: 4px solid #667eea; border-radius: 8px;">
                    <h4 style="margin-top: 0; color: #1e40af;">📋 Como configurar o N8N (Passo a Passo)</h4>
                    
                    <div style="margin-bottom: 16px;">
                        <a href="https://github.com/RelaxSolucoes/Fluxo-Wordpress-IA" target="_blank" rel="noopener noreferrer" 
                           class="button button-primary" style="text-decoration: none;">
                            📥 Baixar Fluxo Modelo
                        </a>
                        <a href="https://n8n.io/" target="_blank" rel="noopener noreferrer" 
                           class="button button-secondary" style="margin-left: 10px; text-decoration: none;">
                            🌐 N8N.io
                        </a>
                    </div>
                    
                    <ol style="margin: 8px 0; padding-left: 20px; color: #1e40af; line-height: 1.6;">
                        <li><strong>N8N instalado</strong> (servidor próprio ou N8N Cloud)</li>
                        <li><strong>Baixar fluxo</strong> usando o botão acima</li>
                        <li><strong>Importar</strong> o arquivo JSON no N8N (Import from File)</li>
                        <li><strong>OpenAI Chat Model:</strong> criar ou escolher credencial OpenAI ou substituir por outro modelo</li>
                        <li><strong>Ajustar prompt</strong> em <code>AI Agent</code> → <code>System Message</code></li>
                        <li><strong>Copiar Chat URL</strong> em <code>When chat message received</code></li>
                        <li><strong>Ativar o fluxo</strong> (Save & Activate)</li>
                        <li><strong>Colar</strong> a URL no campo <strong>URL do Webhook</strong> acima</li>
                        <li><strong>Clicar em 🧪 Testar Webhook</strong></li>
                        <li><strong>Se ✅ Webhook testado com sucesso!</strong> → <strong>Salvar</strong></li>
                    </ol>
                    
                    <div style="margin-top: 16px; padding: 12px; background: rgba(34, 197, 94, 0.1); border-radius: 6px;">
                        <strong style="color: #166534;">✅ Resultado esperado:</strong>
                        <br><small style="color: #15803d;">Chat e formulário integrados com IA + resposta automática no WhatsApp</small>
                    </div>
                    
                    <p style="margin: 16px 0 0 0; font-size: 13px; color: #64748b;">
                        <strong>💡 Dica:</strong> O fluxo funciona com OpenAI, Anthropic, Ollama e outros modelos. Personalize o System Message para seu negócio.
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderiza aba do chat
     */
    private static function render_chat_tab() {
        ?>
        <div class="tab-pane">
            <h2>💬 Configurações do Chat</h2>
            <p>Configure o widget de chat que será exibido no seu site.</p>
            
            <form method="post" action="options.php">
                <?php settings_fields('wpain_chat_group'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wpain_chat_enabled">Ativar Chat</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="wpain_chat_enabled" name="wpain_chat_enabled" 
                                       value="1" <?php checked(get_option('wpain_chat_enabled'), true); ?> />
                                Ativar widget de chat no site
                            </label>
                            <p class="description">Quando ativado, o chat será exibido automaticamente</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpain_chat_title">Título do Chat</label>
                        </th>
                        <td>
                            <input type="text" id="wpain_chat_title" name="wpain_chat_title" 
                                   value="<?php echo esc_attr(get_option('wpain_chat_title')); ?>" 
                                   class="regular-text" />
                            <p class="description">Título principal do widget de chat</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpain_chat_subtitle">Subtítulo</label>
                        </th>
                        <td>
                            <input type="text" id="wpain_chat_subtitle" name="wpain_chat_subtitle" 
                                   value="<?php echo esc_attr(get_option('wpain_chat_subtitle')); ?>" 
                                   class="regular-text" />
                            <p class="description">Subtítulo descritivo do chat</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpain_chat_placeholder">Placeholder do Input</label>
                        </th>
                        <td>
                            <input type="text" id="wpain_chat_placeholder" name="wpain_chat_placeholder" 
                                   value="<?php echo esc_attr(get_option('wpain_chat_placeholder')); ?>" 
                                   class="regular-text" />
                            <p class="description">Texto de exemplo no campo de entrada</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpain_chat_welcome">Mensagem de Boas-vindas</label>
                        </th>
                        <td>
                            <textarea id="wpain_chat_welcome" name="wpain_chat_welcome" 
                                      rows="3" class="large-text"><?php echo esc_textarea(get_option('wpain_chat_welcome')); ?></textarea>
                            <p class="description">Mensagem inicial exibida quando o chat é aberto</p>
                        </td>
                    </tr>
                </table>
                
                <h3>Como usar</h3>
                <div class="usage-info">
                    <p><strong>Shortcode:</strong> <code>[wpain_chat]</code></p>
                    <p><strong>Inserção automática:</strong> O chat será exibido automaticamente quando ativado</p>
                    <p><strong>Personalização:</strong> Use os campos acima para personalizar textos e comportamento</p>
                </div>
                
                <!-- Informações sobre integração N8N -->
                <div style="margin-top: 20px; padding: 16px; background: #f0fdf4; border-left: 4px solid #22c55e; border-radius: 8px;">
                    <h4 style="margin-top: 0; color: #166534;">💬 Chat Inteligente com N8N</h4>
                    <p style="margin: 8px 0; color: #166534;">
                        O widget de chat se conecta diretamente ao N8N para fornecer respostas inteligentes. 
                        Funcionalidades disponíveis:
                    </p>
                    <ul style="margin: 8px 0; padding-left: 20px; color: #166534;">
                        <li>Respostas em tempo real via IA</li>
                        <li>Integração com base de conhecimento</li>
                        <li>Roteamento inteligente de mensagens</li>
                        <li>Histórico de conversas</li>
                        <li>Suporte multilíngue</li>
                    </ul>
                    
                    <p style="margin: 16px 0 0 0; font-size: 13px; color: #15803d;">
                        <strong>🚀 Próximos passos:</strong> 
                        <a href="https://github.com/RelaxSolucoes/Fluxo-Wordpress-IA" target="_blank" rel="noopener noreferrer" style="color: #15803d;">
                            Configure o fluxo N8N para ativar o chat inteligente
                        </a>
                    </p>
                </div>
                
                <?php submit_button('Salvar Configurações do Chat'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Renderiza aba do formulário
     */
    private static function render_form_tab() {
        ?>
        <div class="tab-pane">
            <h2>📝 Configurações do Formulário</h2>
            <p>Configure o formulário de contato que envia mensagens para o N8N.</p>
            
            <form method="post" action="options.php">
                <?php settings_fields('wpain_form_group'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wpain_form_enabled">Ativar Formulário</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="wpain_form_enabled" name="wpain_form_enabled" 
                                       value="1" <?php checked(get_option('wpain_form_enabled'), true); ?> />
                                Ativar formulário de contato
                            </label>
                            <p class="description">Quando ativado, o formulário estará disponível via shortcode</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpain_form_whatsapp_validation">Validação de WhatsApp</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="wpain_form_whatsapp_validation" name="wpain_form_whatsapp_validation" 
                                       value="1" <?php checked(get_option('wpain_form_whatsapp_validation'), true); ?> />
                                Validar número de WhatsApp antes de enviar
                            </label>
                            <p class="description">Quando ativado, o botão de envio só será liberado após validação do WhatsApp. Quando desativado, o formulário pode ser enviado sem validação.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpain_form_title">Título do Formulário</label>
                        </th>
                        <td>
                            <input type="text" id="wpain_form_title" name="wpain_form_title" 
                                   value="<?php echo esc_attr(get_option('wpain_form_title')); ?>" 
                                   class="regular-text" />
                            <p class="description">Título exibido acima do formulário</p>
                        </td>
                    </tr>
                </table>
                
                <h3>Personalização dos Campos</h3>
                <table class="form-table">
                    <?php
                    // Configuração dos campos do formulário
                    $form_fields = array(
                        'nome_label' => array('label' => 'Label do Nome', 'default' => 'Nome', 'description' => 'Texto do label para o campo nome'),
                        'nome_placeholder' => array('label' => 'Placeholder do Nome', 'default' => '', 'description' => 'Texto de exemplo para o campo nome (opcional)'),
                        'email_label' => array('label' => 'Label do E-mail', 'default' => 'E-mail', 'description' => 'Texto do label para o campo e-mail'),
                        'email_placeholder' => array('label' => 'Placeholder do E-mail', 'default' => '', 'description' => 'Texto de exemplo para o campo e-mail (opcional)'),
                        'whatsapp_label' => array('label' => 'Label do WhatsApp', 'default' => 'WhatsApp', 'description' => 'Texto do label para o campo WhatsApp'),
                        'whatsapp_placeholder' => array('label' => 'Placeholder do WhatsApp', 'default' => '(11) 99999-9999', 'description' => 'Texto de exemplo para o campo WhatsApp'),
                        'mensagem_label' => array('label' => 'Label da Mensagem', 'default' => 'Sua Pergunta/Solicitação', 'description' => 'Texto do label para o campo mensagem'),
                        'mensagem_placeholder' => array('label' => 'Placeholder da Mensagem', 'default' => 'Digite sua pergunta ou solicitação para o assistente virtual...', 'description' => 'Texto de exemplo para o campo mensagem')
                    );
                    
                    foreach ($form_fields as $field_name => $field_config) {
                        wpain_render_form_field($field_name, $field_config);
                    }
                    ?>
                </table>
                
                <h3>Personalização do Botão</h3>
                <table class="form-table">
                    <?php
                    $button_fields = array(
                        'button_text' => array('label' => 'Texto do Botão', 'default' => '🤖 Enviar para o Bot', 'description' => 'Texto exibido no botão de envio'),
                        'button_loading_text' => array('label' => 'Texto de Loading', 'default' => '⏳ Enviando para o bot...', 'description' => 'Texto exibido durante o envio')
                    );
                    
                    foreach ($button_fields as $field_name => $field_config) {
                        wpain_render_form_field($field_name, $field_config);
                    }
                    ?>
                </table>
                
                <h3>Como usar</h3>
                <div class="usage-info">
                    <p><strong>Shortcode:</strong> <code>[wpain_form]</code></p>
                    <p><strong>Personalização:</strong> Use o shortcode em qualquer página ou post</p>
                    <p><strong>Integração:</strong> O formulário envia dados para o webhook do N8N configurado</p>
                </div>
                
                <!-- Informações sobre integração N8N -->
                <div style="margin-top: 20px; padding: 16px; background: #f0f9ff; border-left: 4px solid #0ea5e9; border-radius: 8px;">
                    <h4 style="margin-top: 0; color: #0c4a6e;">🔗 Integração com N8N</h4>
                    <p style="margin: 8px 0; color: #0c4a6e;">
                        Quando um usuário envia o formulário, os dados são enviados para o N8N através do webhook configurado. 
                        O N8N processa a solicitação com IA e pode:
                    </p>
                    <ul style="margin: 8px 0; padding-left: 20px; color: #0c4a6e;">
                        <li>Processar mensagens com inteligência artificial</li>
                        <li>Enviar resposta no WhatsApp via Evolution API</li>
                        <li>Manter contexto da conversa com memória</li>
                        <li>Detectar origem da mensagem (formulário, chat ou WhatsApp)</li>
                    </ul>
                    
                    <p style="margin: 16px 0 0 0; font-size: 13px; color: #0369a1;">
                        <strong>📚 Documentação:</strong> 
                        <a href="https://github.com/RelaxSolucoes/Fluxo-Wordpress-IA" target="_blank" rel="noopener noreferrer" style="color: #0369a1;">
                            Consulte o fluxo modelo para entender a estrutura dos dados
                        </a>
                    </p>
                </div>
                
                <?php submit_button('Salvar Configurações do Formulário'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Renderiza aba do WhatsApp
     */
    private static function render_whatsapp_tab() {
        $evolution_url = get_option('wpain_evolution_url', '');
        $evolution_apikey = get_option('wpain_evolution_apikey', '');
        $evolution_instance = get_option('wpain_evolution_instance', '');
        $n8n_webhook = get_option('wpain_n8n_webhook', '');
        
        ?>
        <div class="tab-pane">
            <h2>📱 Integração N8N com WhatsApp</h2>
            <p>Gerencie integrações N8N na Evolution API para processamento de mensagens WhatsApp.</p>
            
            <!-- Verificação de configuração -->
            <?php if (empty($evolution_url) || empty($evolution_apikey) || empty($evolution_instance)): ?>
                <div class="notice notice-error">
                    <p><strong>⚠️ Configuração Necessária</strong></p>
                    <p>Para usar esta funcionalidade, você precisa configurar a Evolution API na aba "Conexão".</p>
                    <p><a href="?page=wp-ai-agent-n8n&tab=connection" class="button button-primary">Configurar Evolution API</a></p>
                </div>
            <?php else: ?>
                <div class="integration-status">
                    <h3>📊 Status das Integrações N8N</h3>
                    
                    <div id="n8n-status-loading" class="wpain-loading">
                        <p>🔄 Verificando integrações N8N...</p>
                    </div>
                    
                    <div id="n8n-status-results" style="display: none;"></div>
                </div>
                

                <div id="n8n-create-section" class="create-integration" style="margin-top: 30px; display: none;">
                    <h3>➕ Criar Nova Integração N8N</h3>
                    <p>Nenhuma integração compatível encontrada. Crie uma nova integração N8N.</p>
                    
                    <?php if (empty($n8n_webhook)): ?>
                        <div class="notice notice-warning">
                            <p><strong>⚠️ Webhook N8N não configurado</strong></p>
                            <p>Para criar uma integração, você precisa configurar o webhook do N8N na aba "🔌 Conexão".</p>
                            <p><a href="?page=wp-ai-agent-n8n&tab=connection" class="button button-primary">Configurar Webhook N8N</a></p>
                        </div>
                    <?php else: ?>
                        <p style="margin-bottom: 15px; color: #666;">
                            <strong>Webhook configurado:</strong> <?php echo esc_html($n8n_webhook); ?>
                        </p>
                        
                        <button type="button" id="create-n8n-btn" class="button button-primary">
                            ➕ Criar Integração N8N
                        </button>
                        
                        <div id="create-status" style="margin-top: 10px;"></div>
                    <?php endif; ?>
                </div>
                
                <!-- Informações sobre a integração -->
                <div style="margin-top: 30px; padding: 20px; background: #f0f9ff; border-left: 4px solid #0ea5e9; border-radius: 8px;">
                    <h4 style="margin-top: 0; color: #0c4a6e;">🔗 Como Funciona a Integração</h4>
                    <p style="margin: 8px 0; color: #0c4a6e;">
                        Esta funcionalidade gerencia automaticamente integrações N8N na Evolution API:
                    </p>
                    <ul style="margin: 8px 0; padding-left: 20px; color: #0c4a6e;">
                        <li><strong>Detecção Automática:</strong> Verifica integrações N8N automaticamente</li>
                        <li><strong>Gerenciamento Inteligente:</strong> Oferece apenas ações necessárias</li>
                        <li><strong>Preservação de Dados:</strong> Mantém configurações existentes intactas</li>
                        <li><strong>Múltiplas Integrações:</strong> Permite escolher qual integração usar</li>
                    </ul>
                    
                    <p style="margin: 16px 0 0 0; font-size: 13px; color: #0369a1;">
                        <strong>🎯 Comportamento:</strong> Se houver múltiplas integrações, você pode escolher qual usar. Se não houver nenhuma, será criada automaticamente com os valores da aba "🔌 Conexão".
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
                <script>
        // JavaScript movido para arquivo externo: assets/js/admin.js
        // Funcionalidade de WhatsApp integrations será carregada automaticamente
        </script>
        
        <!-- CSS movido para arquivo externo: assets/css/admin.css -->
        <?php
    }
}
