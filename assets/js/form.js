/**
 * WP AI Agent N8N - Form JavaScript
 */

jQuery(document).ready(function($) {
    
    const form = $('#wpain-form');
    const responseDiv = $('#wpain-form-response');
    const submitButton = form.find('.wpain-submit-button');
    
    // Configuração do webhook (será preenchida pelo PHP)
    const webhookUrl = wpain_form_config ? wpain_form_config.webhook_url : '';
    
    // Manipula envio do formulário
    form.on('submit', async function(e) {
        e.preventDefault();
        
        // Ativa estado de loading
        submitButton.prop('disabled', true);
        submitButton.addClass('loading');
        responseDiv.hide();
        
        // Coleta dados do formulário
        const formData = new FormData(form[0]);
        const data = Object.fromEntries(formData.entries());
        
        // Valida dados
        const errors = validateFormData(data);
        if (errors.length > 0) {
            showFormError(errors.join('<br>'));
            submitButton.prop('disabled', false);
            submitButton.removeClass('loading');
            return;
        }
        
        // Formata mensagem para o bot
        const chatInput = formatMessageForBot(data);
        
        // Gera sessionId baseado no WhatsApp (padrão WhatsApp)
        const whatsappSessionId = generateSessionId(data.whatsapp);
        const fallbackSessionId = generateSessionId();
        
        // Dados para N8N identificar origem e rotear resposta
        const payload = {
            chatInput: chatInput,
            action: 'sendMessage',
            sessionId: whatsappSessionId,
            remoteJid: whatsappSessionId, // Para compatibilidade com Evolution API
            source: 'web_form',
            sourceType: 'formulario_site',
            channel: 'web_form', // Campo correto para o fluxo N8N
            pushName: data.nome, // Nome do usuário (padrão WhatsApp)
            fromMe: false, // Mensagem vem do usuário
            userInfo: {
                nome: data.nome,
                email: data.email,
                whatsapp: data.whatsapp || null,
                telefone: data.whatsapp || null // Mantém compatibilidade com webhook
            },
            responseConfig: {
                shouldRespond: true,
                responseTarget: data.whatsapp ? 'whatsapp' : 'web_form', // WhatsApp se tem WhatsApp
                formSessionId: fallbackSessionId,
                whatsappSessionId: whatsappSessionId
            },
            metadata: {
                page_url: window.location.href,
                page_title: document.title,
                timestamp: new Date().toISOString(),
                user_agent: navigator.userAgent,
                phone_formatted: whatsappSessionId,
                wordpress: true,
                site_url: window.location.origin,
                ajax_proxy: true
            }
        };
        
        try {
            // Envia via AJAX do WordPress
            const response = await $.ajax({
                url: wpain_form_config.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpain_submit_form',
                    nonce: wpain_form_config.nonce,
                    nome: data.nome,
                    email: data.email,
                    whatsapp: data.whatsapp,
                    mensagem: data.mensagem,
                    page_url: window.location.href,
                    page_title: document.title,
                    user_agent: navigator.userAgent
                }
            });
            
            if (response.success) {
                // Verificar se tem WhatsApp para determinar tipo de resposta
                const temWhatsapp = data.whatsapp && data.whatsapp.trim();
                
                if (temWhatsapp) {
                    // Resposta vai para WhatsApp
                    showFormSuccess(`
                        ✅ Mensagem enviada com sucesso!<br>
                        <strong>📱 A resposta será enviada para seu WhatsApp: ${data.whatsapp}</strong><br>
                        <small>Verifique as mensagens em alguns instantes.</small>
                    `);
                } else {
                    // Resposta no próprio formulário
                    showFormSuccess('✅ Mensagem enviada com sucesso para o assistente!<br>Aguarde a resposta...');
                    
                    // Se o bot retornou uma resposta, mostrar
                    if (response.data && response.data.message) {
                        setTimeout(() => {
                            showBotResponse(response.data.message);
                        }, 1500);
                    }
                }
                
                form[0].reset();
                
                // Scroll para a resposta
                responseDiv[0].scrollIntoView({ behavior: 'smooth' });
                
            } else {
                throw new Error(response.data || 'Erro desconhecido');
            }
        } catch (error) {
            // Erro
            showFormError('❌ Erro ao conectar com o assistente.<br>Tente novamente em alguns instantes.');
            console.error('Erro:', error);
        } finally {
            // Remove loading
            submitButton.prop('disabled', false);
            submitButton.removeClass('loading');
        }
    });
    
    // Função para gerar sessionId no padrão WhatsApp
    function generateSessionId(telefone = null) {
        if (telefone && telefone.trim()) {
            // Limpar telefone (remover espaços, parênteses, hífens)
            let cleanPhone = telefone.replace(/[^\d]/g, '');
            
            // Se não começar com 55, adicionar
            if (!cleanPhone.startsWith('55')) {
                cleanPhone = '55' + cleanPhone;
            }
            
            return cleanPhone + '@s.whatsapp.net';
        }
        
        // Fallback para sessionId genérico se não houver telefone
        return 'form_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    // Função para formatar dados do formulário como mensagem de chat
    function formatMessageForBot(data) {
        let mensagem = "**Nova solicitação via formulário:**\n\n";
        
        mensagem += "**Nome:** " + data.nome + "\n";
        mensagem += "**E-mail:** " + data.email + "\n";
        
        if (data.whatsapp && data.whatsapp.trim()) {
            mensagem += "**WhatsApp:** " + data.whatsapp + "\n";
        }
        

        mensagem += "**Mensagem:**\n" + data.mensagem + "\n\n";
        mensagem += "**Página:** " + window.location.href + "\n";
        mensagem += "**Data:** " + new Date().toLocaleString('pt-BR');
        
        return mensagem;
    }
    
    // Função para formatar resposta do bot
    function formatBotResponse(mensagem) {
        if (!mensagem) return 'Processado com sucesso!';
        
        // Converter quebras de linha
        mensagem = mensagem.replace(/\n/g, '<br>');
        
        // Converter markdown básico
        mensagem = mensagem.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        mensagem = mensagem.replace(/\*(.*?)\*/g, '<em>$1</em>');
        
        return mensagem;
    }
    
    // Validação dos dados do formulário
    function validateFormData(data) {
        const errors = [];
        
        if (!data.nome || !data.nome.trim()) {
            errors.push('Nome é obrigatório');
        }
        
        if (!data.email || !data.email.trim()) {
            errors.push('E-mail é obrigatório');
        } else if (!isValidEmail(data.email)) {
            errors.push('E-mail inválido');
        }
        

        
        if (!data.mensagem || !data.mensagem.trim()) {
            errors.push('Mensagem é obrigatória');
        }
        
        return errors;
    }
    
    // Validação de e-mail
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Função unificada para mostrar mensagens
    function showMessage(message, type = 'success') {
        responseDiv.removeClass('success error bot-response').addClass(type);
        
        if (type === 'bot-response') {
            const formattedMessage = formatBotResponse(message);
            responseDiv.html(`
                <strong>🤖 Resposta do Assistente:</strong><br><br>
                <div style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 5px; margin-top: 10px;">
                    ${formattedMessage}
                </div>
            `);
        } else {
            responseDiv.html(message);
        }
        
        responseDiv.show();
    }
    
    // Funções de conveniência (mantidas para compatibilidade)
    function showFormSuccess(message) { showMessage(message, 'success'); }
    function showFormError(message) { showMessage(message, 'error'); }
    function showBotResponse(message) { showMessage(message, 'bot-response'); }
    
    // Validação em tempo real
    const requiredFields = form.find('[required]');
    requiredFields.each(function() {
        const field = $(this);
        
        field.on('blur', function() {
            if (this.value.trim() === '') {
                field.addClass('field-error');
                showFieldError(field, 'Este campo é obrigatório');
            } else {
                field.removeClass('field-error');
                hideFieldError(field);
                
                // Validação específica para e-mail
                if (this.type === 'email' && !isValidEmail(this.value)) {
                    field.addClass('field-error');
                    showFieldError(field, 'E-mail inválido');
                }
                
                // Validação específica para WhatsApp (só se estiver ativada)
                if (this.name === 'whatsapp' && wpain_form_config.whatsapp_validation) {
                    validateWhatsApp(this.value);
                }
                
                // Se validação estiver desativada, verifica se todos os campos obrigatórios estão preenchidos
                if (!wpain_form_config.whatsapp_validation) {
                    const requiredFields = form.find('[required]');
                    let allFieldsFilled = true;
                    
                    requiredFields.each(function() {
                        if (!$(this).val().trim()) {
                            allFieldsFilled = false;
                            return false; // break loop
                        }
                    });
                    
                    if (allFieldsFilled) {
                        $('.wpain-submit-button').prop('disabled', false).removeClass('disabled');
                    }
                }
            }
        });
        
        field.on('focus', function() {
            field.removeClass('field-error');
            hideFieldError(field);
        });
    });
    
    // Validação específica para WhatsApp (só se estiver ativada)
    $('#wpain-whatsapp').on('input', function() {
        const field = $(this);
        const value = field.val().trim();
        
        // Remove status de validação anterior
        hideWhatsAppValidation();
        
        // Se validação estiver desativada, habilita botão quando tem valor
        if (!wpain_form_config.whatsapp_validation) {
            if (value && value.trim()) {
                $('.wpain-submit-button').prop('disabled', false).removeClass('disabled');
                field.removeClass('field-error').addClass('field-success');
            } else {
                $('.wpain-submit-button').prop('disabled', true).addClass('disabled');
                field.removeClass('field-success');
            }
            return;
        }
        
        if (value && value.length >= 10) {
            // Valida WhatsApp quando tem pelo menos 10 dígitos
            // Remove formatação antes de validar
            const cleanValue = value.replace(/\D/g, '');
            if (cleanValue.length >= 10) {
                validateWhatsApp(cleanValue);
            }
        }
    });
    
    // Validação específica para e-mail
    $('#wpain-email').on('input', function() {
        const field = $(this);
        const value = field.val().trim();
        
        if (value && !isValidEmail(value)) {
            field.addClass('field-error');
            showFieldError(field, 'E-mail inválido');
        } else {
            field.removeClass('field-error');
            hideFieldError(field);
        }
    });
    
    // Função para validar WhatsApp via Evolution API
    function validateWhatsApp(whatsapp) {
        // Se validação estiver desativada, não faz nada
        if (!wpain_form_config.whatsapp_validation) {
            return;
        }
        
        const statusDiv = $('#whatsapp-validation-status');
        const submitButton = $('.wpain-submit-button');
        
        // Mostra status de validação
        statusDiv.html('<div class="validating">🔍 Validando WhatsApp...</div>');
        statusDiv.show();
        
        // Desabilita botão durante validação
        submitButton.prop('disabled', true);
        
        // Chama AJAX para validação
        $.ajax({
            url: wpain_form_config.ajax_url,
            type: 'POST',
            data: {
                action: 'wpain_validate_whatsapp',
                nonce: wpain_form_config.nonce,
                whatsapp: whatsapp
            },
            success: function(response) {
                if (response.success) {
                    // WhatsApp válido - mensagem simplificada
                    statusDiv.html('<div class="valid">✅ WhatsApp válido</div>');
                    statusDiv.removeClass('invalid').addClass('valid');
                    
                    // Habilita botão
                    submitButton.prop('disabled', false);
                    submitButton.removeClass('disabled');
                    
                    // Adiciona classe de sucesso ao campo
                    $('#wpain-whatsapp').removeClass('field-error').addClass('field-success');
                    
                } else {
                    // WhatsApp inválido
                    statusDiv.html('<div class="invalid">❌ ' + (response.data || 'WhatsApp inválido') + '</div>');
                    statusDiv.removeClass('valid').addClass('invalid');
                    
                    // Mantém botão desabilitado
                    submitButton.prop('disabled', true);
                    submitButton.addClass('disabled');
                    
                    // Adiciona classe de erro ao campo
                    $('#wpain-whatsapp').removeClass('field-success').addClass('field-error');
                }
            },
            error: function() {
                // Erro na validação
                statusDiv.html('<div class="error">⚠️ Erro na validação</div>');
                statusDiv.removeClass('valid invalid').addClass('error');
                
                // Mantém botão desabilitado
                submitButton.prop('disabled', true);
                submitButton.addClass('disabled');
            }
        });
    }
    
    // Função para ocultar status de validação
    function hideWhatsAppValidation() {
        // Se validação estiver desativada, não mostra status
        if (!wpain_form_config.whatsapp_validation) {
            return;
        }
        $('#whatsapp-validation-status').hide();
    }
    
    // Desabilita botão inicialmente apenas se validação estiver ativada
    if (wpain_form_config.whatsapp_validation) {
        $('.wpain-submit-button').prop('disabled', true).addClass('disabled');
    }
    
    // Funções para mostrar/ocultar erros de campo
    function showFieldError(field, message) {
        hideFieldError(field);
        
        const errorDiv = $('<div class="error-message">' + message + '</div>');
        field.after(errorDiv);
    }
    
    function hideFieldError(field) {
        field.siblings('.error-message').remove();
    }
    
    // Máscara para WhatsApp (suporte a 11 dígitos para celular)
    $('#wpain-whatsapp').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        
        if (value.length > 0) {
            if (value.length <= 2) {
                value = '(' + value;
            } else if (value.length <= 6) {
                value = '(' + value.substring(0, 2) + ') ' + value.substring(2);
            } else if (value.length <= 10) {
                value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 6) + '-' + value.substring(6);
            } else if (value.length <= 11) {
                // Suporte a 11 dígitos (celular com 9)
                value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 7) + '-' + value.substring(7);
            } else {
                // Para números maiores, mantém o formato de 11 dígitos
                value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 7) + '-' + value.substring(7, 11);
            }
        }
        
        this.value = value;
    });
    
    // Melhorias de UX
    form.find('input, textarea, select').on('keydown', function(e) {
        // Enter em campos de texto não submete o formulário
        if (e.key === 'Enter' && $(this).is('input, textarea')) {
            e.preventDefault();
            $(this).blur();
        }
    });
    
    // Auto-resize para textarea
    $('#wpain-mensagem').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Feedback visual para campos válidos
    form.find('input, textarea, select').on('input', function() {
        const field = $(this);
        const value = field.val().trim();
        
        if (field.prop('required') && value) {
            if (field.attr('type') === 'email' && isValidEmail(value)) {
                field.addClass('field-success');
            } else if (field.attr('type') !== 'email') {
                field.addClass('field-success');
            }
        } else {
            field.removeClass('field-success');
        }
    });
    
    // Inicialização
    console.log('🤖 WP AI Agent N8N Form inicializado!');
    
    // Verifica se há campos com valores para mostrar sucesso
    form.find('input, textarea, select').each(function() {
        const field = $(this);
        if (field.val().trim()) {
            field.trigger('input');
        }
    });
    
    // Se validação estiver desativada, verifica se todos os campos obrigatórios estão preenchidos
    if (!wpain_form_config.whatsapp_validation) {
        const requiredFields = form.find('[required]');
        let allFieldsFilled = true;
        
        requiredFields.each(function() {
            if (!$(this).val().trim()) {
                allFieldsFilled = false;
                return false; // break loop
            }
        });
        
        if (allFieldsFilled) {
            $('.wpain-submit-button').prop('disabled', false).removeClass('disabled');
        }
    }
    
    // Adiciona classe de loading ao formulário durante envio
    form.on('submit', function() {
        form.addClass('loading');
    });
    
    // Remove classe de loading após envio
    form.on('submit', function() {
        setTimeout(function() {
            form.removeClass('loading');
        }, 1000);
    });
});
