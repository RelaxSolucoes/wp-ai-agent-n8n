/**
 * WP AI Agent N8N - Admin JavaScript
 */

jQuery(document).ready(function($) {
    
    // Teste de conexão com Evolution API
    $('#test-connection').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const spinner = $('#connection-spinner');
        const result = $('#connection-result');
        
        // Obtém valores dos campos
        const url = $('#wpain_evolution_url').val();
        const apikey = $('#wpain_evolution_apikey').val();
        const instance = $('#wpain_evolution_instance').val();
        
        // Validação básica
        if (!url || !apikey || !instance) {
            result.html('<div class="connection-result error">❌ Todos os campos são obrigatórios</div>');
            result.show();
            return;
        }
        
        // Ativa estado de loading
        button.prop('disabled', true);
        spinner.show();
        result.hide();
        
        // Faz requisição AJAX
        $.ajax({
            url: wpain_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpain_test_connection',
                nonce: wpain_ajax.nonce,
                url: url,
                apikey: apikey,
                instance: instance
            },
            success: function(response) {
                if (response.success) {
                    result.html('<div class="connection-result success">✅ ' + response.data + '</div>');
                } else {
                    result.html('<div class="connection-result error">❌ ' + response.data + '</div>');
                }
            },
            error: function() {
                result.html('<div class="connection-result error">❌ Erro na requisição. Tente novamente.</div>');
            },
            complete: function() {
                button.prop('disabled', false);
                spinner.hide();
                result.show();
            }
        });
    });
    
    // Teste de webhook do N8N
    $('#test-webhook').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const spinner = $('#webhook-spinner');
        const result = $('#webhook-result');
        
        // Obtém valor do campo
        const webhookUrl = $('#wpain_n8n_webhook').val();
        
        // Validação básica
        if (!webhookUrl) {
            result.html('<div class="connection-result error">❌ URL do webhook é obrigatória</div>');
            result.show();
            return;
        }
        
        // Ativa estado de loading
        button.prop('disabled', true);
        spinner.show();
        result.hide();
        
        // Faz requisição AJAX
        $.ajax({
            url: wpain_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpain_test_webhook',
                nonce: wpain_ajax.nonce,
                webhook_url: webhookUrl
            },
            success: function(response) {
                if (response.success) {
                    result.html('<div class="connection-result success">✅ ' + response.data + '</div>');
                } else {
                    result.html('<div class="connection-result error">❌ ' + response.data + '</div>');
                }
            },
            error: function() {
                result.html('<div class="connection-result error">❌ Erro na requisição. Tente novamente.</div>');
            },
            complete: function() {
                button.prop('disabled', false);
                spinner.hide();
                result.show();
            }
        });
    });
    
    // Validação em tempo real dos campos
    $('.form-table input, .form-table textarea, .form-table select').on('blur', function() {
        const field = $(this);
        const value = field.val().trim();
        
        if (field.prop('required') && !value) {
            field.addClass('field-error');
            showFieldError(field, 'Este campo é obrigatório');
        } else if (field.attr('type') === 'email' && value && !isValidEmail(value)) {
            field.addClass('field-error');
            showFieldError(field, 'E-mail inválido');
        } else {
            field.removeClass('field-error');
            hideFieldError(field);
        }
    });
    
    $('.form-table input, .form-table textarea, .form-table select').on('focus', function() {
        const field = $(this);
        field.removeClass('field-error');
        hideFieldError(field);
    });
    
    // Validação de formulários antes do envio
    $('form').on('submit', function(e) {
        const form = $(this);
        let hasErrors = false;
        
        // Remove erros anteriores
        form.find('.field-error').removeClass('field-error');
        form.find('.error-message').remove();
        
        // Valida campos obrigatórios
        form.find('[required]').each(function() {
            const field = $(this);
            const value = field.val().trim();
            
            if (!value) {
                field.addClass('field-error');
                showFieldError(field, 'Este campo é obrigatório');
                hasErrors = true;
            }
        });
        
        // Valida e-mails
        form.find('input[type="email"]').each(function() {
            const field = $(this);
            const value = field.val().trim();
            
            if (value && !isValidEmail(value)) {
                field.addClass('field-error');
                showFieldError(field, 'E-mail inválido');
                hasErrors = true;
            }
        });
        
        // Valida URLs
        form.find('input[type="url"]').each(function() {
            const field = $(this);
            const value = field.val().trim();
            
            if (value && !isValidUrl(value)) {
                field.addClass('field-error');
                showFieldError(field, 'URL inválida');
                hasErrors = true;
            }
        });
        
        if (hasErrors) {
            e.preventDefault();
            
            // Scroll para o primeiro erro
            const firstError = form.find('.field-error').first();
            if (firstError.length) {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 500);
            }
            
            return false;
        }
        
        // Mostra mensagem de salvamento
        showSaveMessage();
    });
    
    // Funções auxiliares
    function showFieldError(field, message) {
        hideFieldError(field);
        
        const errorDiv = $('<div class="error-message">' + message + '</div>');
        field.after(errorDiv);
    }
    
    function hideFieldError(field) {
        field.siblings('.error-message').remove();
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
    
    function showSaveMessage() {
        // Cria notificação temporária
        const notification = $('<div class="notice notice-success is-dismissible" style="position: fixed; top: 50px; right: 20px; z-index: 9999; min-width: 300px;"><p>' + wpain_ajax.strings.saving + '</p></div>');
        
        $('body').append(notification);
        
        // Remove após 3 segundos
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Melhorias de UX
    $('.nav-tab').on('click', function() {
        // Adiciona classe para animação
        $('.tab-content').addClass('loading');
        
        // Remove classe após carregamento
        setTimeout(function() {
            $('.tab-content').removeClass('loading');
        }, 300);
    });
    
    // Tooltips para campos
    $('.form-table .description').each(function() {
        const description = $(this);
        const field = description.siblings('input, textarea, select');
        
        if (field.length) {
            field.attr('title', description.text());
        }
    });
    
    // Auto-save para campos de texto (opcional)
    let saveTimeout;
    $('.form-table input[type="text"], .form-table input[type="url"], .form-table textarea').on('input', function() {
        clearTimeout(saveTimeout);
        
        // Auto-save após 2 segundos de inatividade
        saveTimeout = setTimeout(function() {
            // Aqui você pode implementar auto-save se desejar

        }, 2000);
    });
    
    // Melhorias de acessibilidade
    $('.form-table input, .form-table textarea, .form-table select').on('keydown', function(e) {
        // Enter em campos de texto não submete o formulário
        if (e.key === 'Enter' && $(this).is('input[type="text"], textarea')) {
            e.preventDefault();
            $(this).blur();
        }
    });
    
    // Feedback visual para campos válidos
    $('.form-table input, .form-table textarea, .form-table select').on('input', function() {
        const field = $(this);
        const value = field.val().trim();
        
        if (field.prop('required') && value) {
            if (field.attr('type') === 'email' && isValidEmail(value)) {
                field.addClass('field-success');
            } else if (field.attr('type') === 'url' && isValidUrl(value)) {
                field.addClass('field-success');
            } else if (field.attr('type') !== 'email' && field.attr('type') !== 'url') {
                field.addClass('field-success');
            }
        } else {
            field.removeClass('field-success');
        }
    });
    
    // ========================================
    // GERENCIAMENTO AUTOMÁTICO DE INTEGRAÇÕES N8N
    // ========================================
    
    // Verifica automaticamente as integrações N8N quando a aba WhatsApp é carregada
    // Aguarda um pouco para garantir que o DOM esteja pronto
    setTimeout(function() {
        // Verifica se estamos na aba WhatsApp e se os elementos existem
        if (window.location.hash === '#whatsapp' || $('.nav-tab-active').attr('href').includes('tab=whatsapp')) {
            if ($('#n8n-status-loading').length > 0) {

                checkN8NIntegrations();
            }
        }
    }, 1000);
    
    // Listener para mudanças de aba
    $('.nav-tab').on('click', function() {
        const tab = $(this).attr('href').split('tab=')[1];
        if (tab === 'whatsapp') {
            // Aguarda a aba carregar completamente
            setTimeout(function() {
                if ($('#n8n-status-loading').length > 0) {

                    checkN8NIntegrations();
                }
            }, 500);
        }
    });
    
    // Listener para mudanças no webhook do plugin
    $('#wpain_n8n_webhook').on('input', function() {
        // Aguarda 1 segundo após parar de digitar para recarregar
        clearTimeout(window.webhookChangeTimeout);
        window.webhookChangeTimeout = setTimeout(function() {
            if ($('#n8n-status-loading').length > 0 && $('#n8n-status-results').is(':visible')) {

                checkN8NIntegrations();
            }
        }, 1000);
    });
    
    // Função para verificar automaticamente as integrações N8N
    function checkN8NIntegrations() {
        // Proteção contra múltiplas chamadas simultâneas
        if (window.isCheckingN8N) {
            return;
        }
        
        window.isCheckingN8N = true;
        
        const loadingDiv = $('#n8n-status-loading');
        const resultsDiv = $('#n8n-status-results');
        const createSection = $('#n8n-create-section');
        
        // Verifica se os elementos existem
        if (loadingDiv.length === 0 || resultsDiv.length === 0 || createSection.length === 0) {
            window.isCheckingN8N = false;
            return;
        }
        
        // Verifica se wpain_ajax está disponível
        if (typeof wpain_ajax === 'undefined' || !wpain_ajax.ajax_url || !wpain_ajax.nonce) {
            window.isCheckingN8N = false;
            return;
        }
        
        // Verifica se estamos na aba WhatsApp
        const activeTab = $('.nav-tab-active');
        if (!activeTab.length || !activeTab.attr('href') || !activeTab.attr('href').includes('tab=whatsapp')) {
            window.isCheckingN8N = false;
            return;
        }
        
        // Mostra loading
        loadingDiv.show();
        resultsDiv.hide();
        createSection.hide();
        

        
        // Primeiro busca o webhook do plugin, depois as integrações
        fetchPluginWebhook(function(webhook) {
            
            // Busca integrações via AJAX
            $.ajax({
                url: wpain_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpain_search_n8n_integrations',
                    nonce: wpain_ajax.nonce
                },
                success: function(response) {
                    loadingDiv.hide();
                    window.isCheckingN8N = false; // Libera a flag
                    
                    if (response.success) {
                        // Exibe o status e as integrações encontradas
                        displayN8NStatus(response.data.integrations);
                    } else {
                        showN8NError(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    loadingDiv.hide();
                    window.isCheckingN8N = false; // Libera a flag
                    showN8NError('Erro na requisição: ' + status + ' - ' + error);
                }
            });
        });
    }
    
    // Função auxiliar para obter o webhook configurado no plugin
    // Como o campo está na aba Connection, buscamos o valor diretamente do banco
    function getPluginWebhook() {
        // Primeiro tenta buscar do campo no DOM (se estiver na aba Connection)
        const webhookField = $('#wpain_n8n_webhook');
        if (webhookField.length > 0) {
            return (webhookField.val() || '').trim();
        }
        
        // Se não encontrar no DOM, usa o cache (que é preenchido via AJAX)
        return pluginWebhookCache;
    }
    
    // Variável global para armazenar o webhook do plugin
    let pluginWebhookCache = '';
    
    // Função para buscar o webhook do plugin via AJAX
    function fetchPluginWebhook(callback) {
        $.ajax({
            url: wpain_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpain_get_webhook',
                nonce: wpain_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    pluginWebhookCache = response.data.webhook || '';
                    if (callback) callback(pluginWebhookCache);
                } else {
                    pluginWebhookCache = '';
                    if (callback) callback('');
                }
            },
            error: function() {
                pluginWebhookCache = '';
                if (callback) callback('');
            }
        });
    }
    
    // Função para exibir o status das integrações N8N
    function displayN8NStatus(integrations) {
        const resultsDiv = $('#n8n-status-results');
        const createSection = $('#n8n-create-section');
        
        // Verifica se os elementos existem
        if (resultsDiv.length === 0 || createSection.length === 0) {
            return;
        }
        
        if (!integrations || integrations.length === 0) {
            // Nenhuma integração encontrada - mostra seção de criação
            createSection.show();
            resultsDiv.hide();
            return;
        }
        

        
        // Adiciona resumo do status no topo
        let html = '<div class="n8n-status-summary">';
        html += '<h4>📊 Resumo do Status</h4>';
        html += generateStatusSummary(integrations);
        html += '</div>';
        
        html += '<div class="n8n-integrations-status">';
        
        if (integrations.length === 1) {
            // Uma integração - mostra status e ações necessárias
            const integration = integrations[0];
            html += displaySingleIntegration(integration);
        } else {
            // Múltiplas integrações - permite escolher
            html += '<h4>🔀 Múltiplas Integrações Encontradas</h4>';
            html += '<p>Escolha qual integração usar:</p>';
            
            integrations.forEach((integration, index) => {
                html += displayIntegrationChoice(integration, index);
            });
        }
        
        html += '</div>';
        
        resultsDiv.html(html).show();
        createSection.hide();
    }
    
    // Função simplificada para exibir uma integração única
    function displaySingleIntegration(integration) {
        const isActive = integration.enabled;
        
        let html = '<div class="n8n-integration-single">';
        html += '<h4>🔗 Integração N8N</h4>';
        html += '<div class="integration-status">';
        html += buildIntegrationDisplay(integration);
        
        // Ações disponíveis baseadas no status real
        html += '<div class="integration-actions">';
        if (isActive) {
            html += '<button type="button" class="button button-secondary deactivate-integration" data-id="' + integration.id + '">❌ Desativar</button>';
        } else {
            html += '<button type="button" class="button button-primary activate-integration" data-id="' + integration.id + '">✅ Ativar</button>';
        }
        html += '</div></div></div>';
        
        return html;
    }
    
    // Função simplificada para exibir escolha entre múltiplas integrações
    function displayIntegrationChoice(integration, index) {
        const commonHtml = buildIntegrationDisplay(integration);
        
        let html = '<div class="n8n-integration-choice" style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px;">';
        html += '<h5>Opção ' + (index + 1) + '</h5>';
        html += commonHtml;
        html += '<button type="button" class="button button-primary use-integration" data-id="' + integration.id + '">';
        html += '🎯 Usar Esta Integração</button>';
        html += '</div>';
        
        return html;
    }
    
    // Função helper para evitar duplicação de código
    function buildIntegrationDisplay(integration) {
        const isActive = integration.enabled;
        const webhookOk = integration.webhookUrl && integration.webhookUrl.trim() !== '';
        const pluginWebhook = getPluginWebhook();
        const webhookMatch = webhookOk && pluginWebhook && integration.webhookUrl === pluginWebhook;
        
        let html = '<p><strong>Status:</strong> ';
        html += isActive ? '✅ <span class="status-active">Ativa</span>' : '❌ <span class="status-inactive">Inativa</span>';
        html += '</p>';
        
        html += '<p><strong>Webhook:</strong> ';
        if (webhookOk) {
            if (webhookMatch) {
                html += '✅ <span class="webhook-ok">Configurado e Compatível</span>';
                html += '<br><small>' + integration.webhookUrl + '</small>';
            } else {
                html += '⚠️ <span class="webhook-mismatch">Configurado mas Diferente</span>';
                html += '<br><small><strong>N8N:</strong> <span class="editable-webhook" data-id="' + integration.id + '" data-current="' + integration.webhookUrl + '">' + integration.webhookUrl + '</span> <button type="button" class="button button-small edit-webhook-inline" data-id="' + integration.id + '">✏️</button></small>';
                if (pluginWebhook) {
                    html += '<br><small><strong>Plugin:</strong> ' + pluginWebhook + '</small>';
                } else {
                    html += '<br><small><strong>Plugin:</strong> <span class="webhook-missing">Não configurado</span></small>';
                }
            }
        } else {
            html += '❌ <span class="webhook-missing">Não configurado</span>';
        }
        html += '</p>';
        
        html += '<p><strong>ID:</strong> ' + integration.id + '</p>';
        if (integration.description) {
            html += '<p><strong>Descrição:</strong> ' + integration.description + '</p>';
        }
        html += '<p><strong>Criado:</strong> ' + formatDate(integration.createdAt) + '</p>';
        
        return html;
    }
    
    // Função para gerar resumo do status das integrações
    function generateStatusSummary(integrations) {
        // Verifica se o campo webhook existe e obtém o valor com segurança
        const pluginWebhook = getPluginWebhook();
        
        let totalIntegrations = integrations.length;
        let activeIntegrations = 0;
        let compatibleWebhooks = 0;
        let mismatchedWebhooks = 0;
        let missingWebhooks = 0;
        
        integrations.forEach(integration => {
            if (integration.enabled) activeIntegrations++;
            
            if (integration.webhookUrl && integration.webhookUrl.trim() !== '') {
                if (pluginWebhook && integration.webhookUrl === pluginWebhook) {
                    compatibleWebhooks++;
                } else {
                    mismatchedWebhooks++;
                }
            } else {
                missingWebhooks++;
            }
        });
        
        let html = '<div class="status-summary-grid">';
        
        // Total de integrações
        html += '<div class="summary-item total">';
        html += '<span class="summary-number">' + totalIntegrations + '</span>';
        html += '<span class="summary-label">Total</span>';
        html += '</div>';
        
        // Integrações ativas
        html += '<div class="summary-item active">';
        html += '<span class="summary-number">' + activeIntegrations + '</span>';
        html += '<span class="summary-label">Ativas</span>';
        html += '</div>';
        
        // Webhooks compatíveis
        html += '<div class="summary-item compatible">';
        html += '<span class="summary-number">' + compatibleWebhooks + '</span>';
        html += '<span class="summary-label">Compatíveis</span>';
        html += '</div>';
        
        // Webhooks diferentes
        if (mismatchedWebhooks > 0) {
            html += '<div class="summary-item mismatched">';
            html += '<span class="summary-number">' + mismatchedWebhooks + '</span>';
            html += '<span class="summary-label">Diferentes</span>';
            html += '</div>';
        }
        
        // Webhooks ausentes
        if (missingWebhooks > 0) {
            html += '<div class="summary-item missing">';
            html += '<span class="summary-number">' + missingWebhooks + '</span>';
            html += '<span class="summary-label">Ausentes</span>';
            html += '</div>';
        }
        
        html += '</div>';
        
        // Recomendação baseada no status
        if (compatibleWebhooks === totalIntegrations && activeIntegrations === totalIntegrations) {
            html += '<div class="status-recommendation success">';
            html += '✅ Todas as integrações estão ativas e compatíveis!';
            html += '</div>';
        } else if (compatibleWebhooks === 0) {
            if (pluginWebhook) {
                html += '<div class="status-recommendation warning">';
                html += '⚠️ ' + mismatchedWebhooks + ' integração(ões) tem(êm) webhook diferente do plugin. Clique no ✏️ para editar diretamente na Evolution API.';
                html += '</div>';
            } else {
                html += '<div class="status-recommendation warning">';
                html += '⚠️ Nenhuma integração tem webhook compatível. Configure o webhook no plugin primeiro.';
                html += '</div>';
            }
        } else if (mismatchedWebhooks > 0) {
            html += '<div class="status-recommendation warning">';
            html += '⚠️ ' + mismatchedWebhooks + ' integração(ões) tem(êm) webhook diferente. Clique no ✏️ para editar diretamente na Evolution API.';
            html += '</div>';
        }
        
        return html;
    }
    
    // Função para exibir erro
    function showN8NError(message) {
        const resultsDiv = $('#n8n-status-results');
        if (resultsDiv.length > 0) {
            resultsDiv.html('<div class="notice notice-error"><p>❌ ' + message + '</p></div>').show();
        } else {
            console.error('Erro N8N:', message);
        }
    }
    
    // Função para formatar data
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR') + ', ' + date.toLocaleTimeString('pt-BR');
        } catch {
            return dateString;
        }
    }
    
    // Event handlers para ações das integrações
    $(document).on('click', '.activate-integration', function() {
        const integrationId = $(this).data('id');
        toggleIntegrationStatus(integrationId, true);
    });
    
    $(document).on('click', '.deactivate-integration', function() {
        const integrationId = $(this).data('id');
        toggleIntegrationStatus(integrationId, false);
    });
    
    $(document).on('click', '.edit-webhook', function() {
        const integrationId = $(this).data('id');
        editWebhook(integrationId);
    });
    
    // Event handler para edição inline do webhook
    $(document).on('click', '.edit-webhook-inline', function() {
        const integrationId = $(this).data('id');
        const webhookSpan = $(this).siblings('.editable-webhook');
        const currentWebhook = webhookSpan.data('current');
        
        // Converte o span em input para edição
        const input = $('<input type="text" class="webhook-input" value="' + currentWebhook + '" style="width: 300px; padding: 4px; margin-right: 5px;">');
        const saveBtn = $('<button type="button" class="button button-small save-webhook-inline" data-id="' + integrationId + '">💾</button>');
        const cancelBtn = $('<button type="button" class="button button-small cancel-webhook-inline">❌</button>');
        
        webhookSpan.hide();
        $(this).hide();
        
        webhookSpan.after(input);
        input.after(saveBtn);
        saveBtn.after(cancelBtn);
        
        input.focus();
    });
    
    // Event handler para salvar webhook inline
    $(document).on('click', '.save-webhook-inline', function() {
        const integrationId = $(this).data('id');
        const input = $(this).siblings('.webhook-input');
        const newWebhook = input.val().trim();
        

        
        if (!newWebhook) {
            showNotification('❌ URL do webhook não pode estar vazia', 'error');
            return;
        }
        
        if (!integrationId) {
            showNotification('❌ ID da integração não encontrado', 'error');
            return;
        }
        
        // Atualiza o webhook
        updateWebhookInline(integrationId, newWebhook);
    });
    
    // Event handler para cancelar edição inline
    $(document).on('click', '.cancel-webhook-inline', function() {
        const input = $(this).siblings('.webhook-input');
        const webhookSpan = input.siblings('.editable-webhook');
        const editBtn = webhookSpan.siblings('.edit-webhook-inline');
        
        input.remove();
        $(this).remove();
        $(this).siblings('.save-webhook-inline').remove();
        
        webhookSpan.show();
        editBtn.show();
    });
    
    $(document).on('click', '.use-integration', function() {
        const integrationId = $(this).data('id');
        showNotification('🎯 Integração selecionada para uso', 'success');
        checkN8NIntegrations();
    });
    
    // Função para ativar/desativar integração
    function toggleIntegrationStatus(integrationId, enabled) {
        const button = $('.integration-actions button[data-id="' + integrationId + '"]');
        const originalText = button.text();
        

        
        button.prop('disabled', true).text('🔄 Processando...');
        
        const ajaxData = {
            action: 'wpain_toggle_n8n_status',
            nonce: wpain_ajax.nonce,
            integration_id: integrationId,
            enabled: enabled
        };
        

        
        $.ajax({
            url: wpain_ajax.ajax_url,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    // Recarrega o status das integrações com delay para evitar loop
                    setTimeout(function() {
                        checkN8NIntegrations();
                    }, 1000);
                    showNotification('✅ ' + response.data, 'success');
                } else {
                    showNotification('❌ ' + response.data, 'error');
                    button.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                showNotification('❌ Erro na requisição', 'error');
                button.prop('disabled', false).text(originalText);
            }
        });
    }
    
    // Função para editar webhook
    function editWebhook(integrationId) {
        // Obtém o webhook configurado no plugin com segurança
        const pluginWebhook = getPluginWebhook();
        
        let promptMessage = 'Digite a nova URL do webhook:';
        let defaultValue = '';
        
        if (pluginWebhook) {
            promptMessage = 'Digite a nova URL do webhook (ou deixe em branco para usar a do plugin):';
            defaultValue = pluginWebhook;
        }
        
        const newWebhook = prompt(promptMessage, defaultValue);
        
        if (newWebhook === null) {
            return; // Usuário cancelou
        }
        
        // Se deixou em branco e tem webhook do plugin, usa o do plugin
        const finalWebhook = newWebhook.trim() || pluginWebhook;
        
        if (!finalWebhook) {
            showNotification('❌ Nenhum webhook disponível para sincronizar', 'error');
            return;
        }
        
        updateWebhookInline(integrationId, finalWebhook);
    }
    
    // Função para atualizar webhook inline
    function updateWebhookInline(integrationId, newWebhook) {

        
        $.ajax({
            url: wpain_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpain_update_n8n_webhook',
                nonce: wpain_ajax.nonce,
                integration_id: integrationId,
                webhook_url: newWebhook
            },
            success: function(response) {
                if (response.success) {
                    // Recarrega o status das integrações com delay para evitar loop
                    setTimeout(function() {
                        checkN8NIntegrations();
                    }, 1000);
                    showNotification('✅ ' + response.data, 'success');
                } else {
                    showNotification('❌ ' + response.data, 'error');
                }
            },
            error: function() {
                showNotification('❌ Erro na requisição', 'error');
            }
        });
    }
    
    // Função para usar integração específica

    
    // Função para mostrar notificações
    function showNotification(message, type) {
        const notification = $('<div class="notice notice-' + type + ' is-dismissible" style="position: fixed; top: 50px; right: 20px; z-index: 9999; min-width: 300px;"><p>' + message + '</p></div>');
        
        $('body').append(notification);
        
        // Remove após 5 segundos
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // === FUNCIONALIDADE N8N WHATSAPP ===
    

    

    
    // Criar nova integração N8N - simples clique no botão
    $(document).on('click', '#create-n8n-btn', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const statusDiv = $('#create-status');
        
        // Usa o webhook configurado no plugin (via JS global ou busca via AJAX)
        const webhookUrl = wpain_ajax.n8n_webhook || '';
        if (!webhookUrl || webhookUrl.trim() === '') {
            statusDiv.html('<div class="notice notice-error"><p>❌ Webhook N8N não configurado. Configure primeiro na aba "🔌 Conexão".</p></div>');
            return;
        }
        
        button.prop('disabled', true).text('➕ Criando...');
        statusDiv.html('<div class="notice notice-info"><p>➕ Criando nova integração N8N...</p></div>');
        
        // Valores fixos conforme solicitado
        const description = 'Integração criada através do plugin wp-ai-agent-n8n';
        
        $.ajax({
            url: wpain_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpain_create_n8n_integration',
                nonce: wpain_ajax.nonce,
                webhook_url: webhookUrl,
                description: description
            },
            success: function(response) {
                if (response.success) {
                    statusDiv.html('<div class="notice notice-success"><p>✅ ' + response.data.message + '</p></div>');
                    // Atualizar lista de integrações
                    checkN8NIntegrations();
                } else {
                    statusDiv.html('<div class="notice notice-error"><p>❌ ' + response.data + '</p></div>');
                }
            },
            error: function() {
                statusDiv.html('<div class="notice notice-error"><p>❌ Erro na comunicação com o servidor</p></div>');
            },
            complete: function() {
                button.prop('disabled', false).text('➕ Criar Integração N8N');
            }
        });
    });
    
    // A busca automática é feita pela função checkN8NIntegrations() 
    // que já é chamada automaticamente nos setTimeouts do início do arquivo
    
    // Inicialização
    console.log('🤖 WP AI Agent N8N Admin inicializado!');
    
    // Verifica se há campos com valores para mostrar sucesso
    $('.form-table input, .form-table textarea, .form-table select').each(function() {
        const field = $(this);
        if (field.val().trim()) {
            field.trigger('input');
        }
    });
});
