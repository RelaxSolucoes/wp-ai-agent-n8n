# Changelog

Todas as mudanças notáveis deste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [1.0.0] - 2024-12-19

### 🎉 Lançamento Inicial

Esta é a primeira versão estável do **WP AI Agent N8N**, um plugin WordPress profissional para integração com agentes de IA via N8N e Evolution API.

### ✨ Recursos Adicionados

#### 🤖 Core do Plugin
- **Sistema de integração N8N**: Comunicação bidirecional com workflows N8N
- **Evolution API**: Integração completa com WhatsApp via Evolution API
- **Arquitetura modular**: Sistema orientado a objetos bem estruturado
- **Auto-atualizações**: Sistema automático de atualizações via GitHub
- **Verificação de requisitos**: Validação automática de PHP e WordPress

#### 💬 Widget de Chat
- **Chat interativo**: Widget flutuante responsivo e moderno
- **Configuração flexível**: Títulos, subtítulos e mensagens personalizáveis
- **Sessões únicas**: Cada conversa tem ID único para rastreamento
- **Shortcode**: `[wpain_chat]` para inserção manual
- **API JavaScript**: Controle programático via `WPAINChat` global

#### 📝 Formulário de Contato
- **Campos personalizáveis**: Nome, email, WhatsApp e mensagem editáveis
- **Validação robusta**: Validação client-side e server-side
- **Integração N8N**: Envio automático para workflow configurado
- **Shortcode**: `[wpain_form]` para inserção flexível
- **Design responsivo**: Interface adaptável a todos os dispositivos

#### 📱 Integração WhatsApp
- **Evolution API**: Integração nativa com Evolution API
- **Envio automático**: Mensagens podem ser enviadas via WhatsApp
- **Múltiplas instâncias**: Suporte para diferentes instâncias WhatsApp
- **Formatação avançada**: Suporte completo para formatação de mensagens
- **Controle de status**: Verificação de conexão e status da API

#### ⚙️ Interface Administrativa
- **Painel organizado**: Interface intuitiva dividida em abas
- **Configuração de conexões**: Gerenciamento centralizado de APIs
- **Personalização visual**: Customização completa de aparência
- **Testes de conectividade**: Botões para testar conexões
- **Documentação integrada**: Ajuda contextual em cada seção

#### 🔒 Segurança e Validação
- **Nonces WordPress**: Proteção CSRF em todas as requisições
- **Sanitização de dados**: Limpeza rigorosa de inputs
- **Escape de outputs**: Prevenção de XSS
- **Validação de permissões**: Controle de acesso administrativo
- **Headers de segurança**: Configurações de segurança apropriadas

#### 🎨 Recursos de Design
- **CSS moderno**: Estilos responsivos e profissionais
- **Animações suaves**: Transições e efeitos visuais
- **Tema adaptável**: Compatível com temas WordPress
- **Ícones vetoriais**: Iconografia moderna e escalável
- **Layout flexível**: Adaptação automática a diferentes tamanhos

### 🔧 Recursos Técnicos

#### Requisitos Mínimos
- **WordPress**: 5.8 ou superior
- **PHP**: 7.4 ou superior
- **Navegadores**: Chrome 60+, Firefox 55+, Safari 12+, Edge 79+

#### APIs Suportadas
- **N8N**: Qualquer versão 0.200+
- **Evolution API**: Versão 2.0+
- **WordPress REST API**: Integração nativa

#### Compatibilidade
- **Multisite**: Compatível com WordPress Multisite
- **Temas**: Compatível com qualquer tema WordPress
- **Plugins**: Testado com principais plugins do mercado
- **SSL**: Suporte completo para HTTPS

### 📚 Documentação

#### Shortcodes Disponíveis
```php
// Chat Widget
[wpain_chat mode="window" title="Suporte"]

// Formulário de Contato  
[wpain_form title="Entre em Contato"]
```

#### API JavaScript
```javascript
// Controle do Chat
WPAINChat.open();           // Abrir chat
WPAINChat.close();          // Fechar chat
WPAINChat.sendMessage();    // Enviar mensagem
WPAINChat.addMessage(text, sender); // Adicionar mensagem
```

#### Hooks WordPress
- `wpain_before_chat_render`: Antes de renderizar o chat
- `wpain_after_form_submit`: Após envio do formulário
- `wpain_message_sent`: Após envio de mensagem
- `wpain_whatsapp_message`: Antes de enviar WhatsApp

### 🔧 Configuração

#### Estrutura de Dados Enviados
```json
{
  "chatInput": "Mensagem do usuário",
  "sessionId": "ID único da sessão",
  "source": "n8n_chat_widget",
  "sourceType": "chat_widget",
  "page_url": "URL da página atual",
  "page_title": "Título da página",
  "user_agent": "User agent do navegador",
  "timestamp": "2024-12-19T00:00:00Z",
  "userInfo": {
    "nome": "Nome do usuário",
    "email": "email@exemplo.com",
    "telefone": "11999999999",
    "assunto": "Assunto da mensagem"
  },
  "responseConfig": {
    "shouldRespond": true,
    "responseTarget": "chat_widget"
  }
}
```

### 🏗️ Arquitetura

#### Classes Principais
- **WPAIN_Loader**: Gerenciador principal do plugin
- **WPAIN_Settings**: Configurações e opções
- **WPAIN_AI_Agent**: Lógica de integração com IA
- **WPAIN_Form_Handler**: Processamento de formulários
- **WPAIN_Admin_Renderer**: Interface administrativa

#### Estrutura de Arquivos
```
wp-ai-agent-n8n/
├── assets/          # CSS, JS e recursos estáticos
├── includes/        # Classes PHP principais
├── lib/            # Bibliotecas de terceiros
├── README.md       # Documentação principal
└── wp-ai-agent-n8n.php # Arquivo principal
```

### 🚀 Instalação e Uso

1. **Upload**: Envie para `/wp-content/plugins/`
2. **Ativação**: Ative via painel administrativo
3. **Configuração**: Configure em "AI Agent N8N"
4. **Implementação**: Use shortcodes ou ativação automática

### 🎯 Casos de Uso

- **Atendimento ao Cliente**: Chat automático para suporte
- **Geração de Leads**: Formulários inteligentes
- **Integração CRM**: Conexão com sistemas externos
- **Automação Marketing**: Workflows automatizados
- **Suporte Multicanal**: WhatsApp + Web unificados

### 🤝 Créditos

- **Desenvolvimento**: [Relax Soluções](https://relaxsolucoes.online)
- **N8N Integration**: Baseado na API oficial do N8N
- **Evolution API**: Integração com Evolution API
- **Auto-updater**: Plugin Update Checker Library

### 📄 Licença

GPL v2 ou posterior - Veja [LICENSE](LICENSE) para detalhes.

### 🔗 Links Úteis

- **Website**: [Relax Soluções](https://relaxsolucoes.online)
- **Repositório**: [GitHub](https://github.com/RelaxSolucoes/wp-ai-agent-n8n)
- **Documentação**: [Wiki](https://github.com/RelaxSolucoes/wp-ai-agent-n8n/wiki)
- **Suporte**: [Issues](https://github.com/RelaxSolucoes/wp-ai-agent-n8n/issues)

---

**Feito com ❤️ pela equipe Relax Soluções**
