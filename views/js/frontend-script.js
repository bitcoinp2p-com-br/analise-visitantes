// Frontend script for Análise de Visitantes plugin
jQuery(document).ready(function($) {
    // Definir cookie para indicar que JS está ativo
    document.cookie = "av_has_js=1; path=/; max-age=86400;";
    
    // Atualizar contador de usuários online a cada 30 segundos
    if ($('.usuarios-online .count').length) {
        // Atualizar imediatamente na carga
        atualizarContador();
        
        // Configurar atualização periódica
        setInterval(atualizarContador, 30000); // 30 segundos
    }
    
    // Função para atualizar o contador
    function atualizarContador() {
        $.ajax({
            url: analiseVisitantesData.ajaxurl,
            type: 'POST',
            data: {
                action: 'atualizar_usuarios_online'
            },
            success: function(response) {
                try {
                    var data = JSON.parse(response);
                    $('.usuarios-online .count').text(data.count);
                } catch (e) {
                    console.error('Erro ao processar resposta AJAX:', e);
                }
            }
        });
    }
    
    // Rastreamento de eventos (cliques em links)
    $(document).on('click', 'a', function() {
        var $this = $(this);
        var href = $this.attr('href') || '';
        
        // Ignorar links internos da página
        if (href.startsWith('#')) {
            return;
        }
        
        // Ignorar links mailto e tel
        if (href.startsWith('mailto:') || href.startsWith('tel:')) {
            return;
        }
        
        // Se for link externo, registrar como evento
        if (href.indexOf(window.location.hostname) === -1 && href.indexOf('://') !== -1) {
            // Enviar evento de clique em link externo
            $.ajax({
                url: analiseVisitantesData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'registrar_visita_ajax',
                    page_id: analiseVisitantesData.pageId || 0,
                    page_title: document.title || '',
                    page_url: window.location.href,
                    referrer: document.referrer || '',
                    event_type: 'external_click',
                    event_data: href
                },
                async: true
            });
        }
    });
});