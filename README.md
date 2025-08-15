# WP AI Agent N8N 🤖

Plugin WordPress para integração com Agente de IA via N8N e Evolution API.

## 📋 Descrição

O **WP AI Agent N8N** é um plugin WordPress profissional que permite integrar seu site com um agente de IA configurado no N8N. Ele oferece:

- **Widget de Chat**: Chat interativo que se integra com seu fluxo N8N
- **Formulário de Contato**: Formulário que envia mensagens para o N8N
- **Integração Evolution API**: Conexão com WhatsApp via Evolution API
- **Interface Administrativa**: Painel de configuração intuitivo e organizado

## 🚀 Instalação

### 1. Upload do Plugin
1. Faça upload da pasta `wp-ai-agent-n8n` para o diretório `/wp-content/plugins/` do seu WordPress
2. Ative o plugin através do menu 'Plugins' no WordPress

### 2. Configuração Inicial
1. Acesse o menu **AI Agent N8N** no painel administrativo
2. Configure as conexões na aba **Conexão**
3. Ative as funcionalidades desejadas nas abas correspondentes

## ⚙️ Configuração

### 🔌 Aba de Conexão

#### Evolution API
- **URL da API**: URL base da sua Evolution API (ex: `https://api.evolution.com`)
- **API Key**: Chave de API da Evolution
- **Nome da Instância**: Nome da instância WhatsApp configurada

#### N8N Webhook
- **URL do Webhook**: URL do webhook configurado no N8N

### 💬 Aba do Chat

#### Configurações Básicas
- **Ativar Chat**: Habilita o widget de chat no site
- **Título do Chat**: Título principal do widget
- **Subtítulo**: Descrição do chat
- **Placeholder do Input**: Texto de exemplo no campo de entrada
- **Mensagem de Boas-vindas**: Mensagem inicial exibida

#### Como Usar
- **Shortcode**: `[wpain_chat]`
- **Inserção automática**: O chat será exibido automaticamente quando ativado
- **Personalização**: Use os campos de configuração para personalizar

### 📝 Aba do Formulário

#### Configurações
- **Ativar Formulário**: Habilita o formulário de contato
- **Título do Formulário**: Título exibido acima do formulário

#### Como Usar
- **Shortcode**: `[wpain_form]`
- **Personalização**: Use o shortcode em qualquer página ou post
- **Integração**: O formulário envia dados para o webhook do N8N

### 📱 Aba do WhatsApp

#### Configurações
- **Ativar WhatsApp**: Habilita a integração com WhatsApp via Evolution API
- **Envio Automático**: Mensagens podem ser enviadas automaticamente via WhatsApp
- **Integração Evolution API**: Utiliza a Evolution API configurada na aba de Conexão

#### Como Funciona
- O plugin se integra com a Evolution API para envio de mensagens
- Respostas do N8N podem ser automaticamente enviadas via WhatsApp
- Suporte completo para formatação de mensagens
- Controle de instâncias WhatsApp através da configuração

## 🔧 Uso Avançado

### Shortcodes Disponíveis

#### Chat
```php
[wpain_chat mode="window" title="Título Personalizado"]
```

**Parâmetros:**
- `mode`: `window` (padrão) ou `inline`
- `title`: Título personalizado do chat

#### Formulário
```php
[wpain_form title="Título Personalizado"]
```

**Parâmetros:**
- `title`: Título personalizado do formulário

### Integração com N8N

O plugin envia dados no seguinte formato para o N8N:

```json
{
  "chatInput": "Mensagem do usuário",
  "sessionId": "ID único da sessão",
  "source": "n8n_chat_widget",
  "sourceType": "chat_widget",
  "page_url": "URL da página atual",
  "page_title": "Título da página",
  "user_agent": "User agent do navegador",
  "timestamp": "2024-01-01T00:00:00Z",
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

### Integração com Evolution API

Para envio de mensagens via WhatsApp, o plugin usa a Evolution API:

**Endpoint**: `POST /message/sendText/{instance}`

**Headers**:
```
apikey: SUA_API_KEY
Content-Type: application/json
```

**Body**:
```json
{
  "number": "5511999999999@s.whatsapp.net",
  "text": "Mensagem da IA"
}
```

## 🎨 Personalização

### CSS Customizado

Você pode personalizar a aparência adicionando CSS customizado:

```css
/* Personalizar widget de chat */
.wpain-chat-widget {
    border-radius: 20px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.3);
}

/* Personalizar formulário */
.wpain-form-container {
    background: linear-gradient(135deg, #ff6b6b, #4ecdc4);
}
```

### JavaScript Customizado

O plugin expõe uma API global para controle programático:

```javascript
// Abrir chat
WPAINChat.open();

// Fechar chat
WPAINChat.close();

// Enviar mensagem
WPAINChat.sendMessage();

// Adicionar mensagem
WPAINChat.addMessage('Mensagem customizada', 'bot');
```

## 🔍 Troubleshooting

### Problemas Comuns

#### Chat não aparece
1. Verifique se o chat está ativado nas configurações
2. Confirme se o webhook do N8N está configurado
3. Verifique o console do navegador para erros

#### Formulário não envia
1. Verifique se o formulário está ativado
2. Confirme se o webhook do N8N está configurado
3. Verifique se todos os campos obrigatórios estão preenchidos

#### Erro de conexão com Evolution API
1. Verifique a URL da API
2. Confirme se a API Key está correta
3. Teste a conexão usando o botão "Testar Conexão"

### Logs e Debug

Ative o modo debug do WordPress para ver logs detalhados:

```php
// Adicione ao wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📚 Exemplos de Uso

### Exemplo 1: Chat em Página Específica
```php
// Adicione ao template da página
if (is_page('contato')) {
    echo do_shortcode('[wpain_chat title="Suporte ao Cliente"]');
}
```

### Exemplo 2: Formulário Personalizado
```php
// Adicione ao functions.php do tema
function custom_contact_form() {
    if (get_option('wpain_form_enabled')) {
        return do_shortcode('[wpain_form title="Entre em Contato"]');
    }
    return '';
}
```

### Exemplo 3: Integração com Hook
```php
// Adicione ao functions.php do tema
add_action('wp_footer', function() {
    if (get_option('wpain_chat_enabled') && is_front_page()) {
        echo '<script>setTimeout(() => WPAINChat.open(), 5000);</script>';
    }
});
```

## 🔒 Segurança

### Medidas Implementadas
- Validação de nonces para todas as requisições AJAX
- Sanitização de dados de entrada
- Verificação de permissões de usuário
- Escape de dados de saída

### Recomendações
- Mantenha o plugin sempre atualizado
- Use HTTPS para todas as conexões
- Configure corretamente as permissões de usuário
- Monitore logs de acesso

## 📱 Compatibilidade

### Requisitos Mínimos
- **PHP**: 7.4 ou superior
- **WordPress**: 5.8 ou superior
- **Navegadores**: Chrome 60+, Firefox 55+, Safari 12+, Edge 79+

### Testado em
- WordPress 6.0+
- PHP 7.4 - 8.2
- N8N 0.200+
- Evolution API 2.0+

## 🆘 Suporte

### Documentação
- [Guia de Configuração](https://github.com/relaxsolucoes/wp-ai-agent-n8n/wiki)
- [FAQ](https://github.com/relaxsolucoes/wp-ai-agent-n8n/wiki/FAQ)
- [Exemplos](https://github.com/relaxsolucoes/wp-ai-agent-n8n/wiki/Exemplos)

### Comunidade
- [GitHub Issues](https://github.com/relaxsolucoes/wp-ai-agent-n8n/issues)

### Suporte Técnico
- **Email**: chatrelaxbr@gmail.com
- **Website**: https://relaxsolucoes.online

## 📄 Licença

Este plugin é licenciado sob a GPL v2 ou posterior.

## 🙏 Agradecimentos

- **N8N Team** pelo excelente framework de automação
- **Evolution API** pela API de WhatsApp
- **WordPress Community** pela plataforma incrível
- **Relax Soluções** pelo desenvolvimento e manutenção

## 🔄 Changelog

### Versão 1.0.0
- ✅ Lançamento inicial
- ✅ Widget de chat integrado com N8N
- ✅ Formulário de contato
- ✅ Integração com Evolution API
- ✅ Interface administrativa organizada
- ✅ Shortcodes para inserção flexível
- ✅ Sistema de validação robusto
- ✅ Design responsivo e moderno

---

**Desenvolvido com ❤️ pela [Relax Soluções](https://relaxsolucoes.online)**
