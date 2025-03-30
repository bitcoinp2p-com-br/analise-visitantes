// Admin script for Análise de Visitantes plugin
jQuery(document).ready(function($) {
    // Navegação por abas
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        
        // Remover classe ativa de todas as abas e painéis
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-panel').removeClass('active').hide();
        
        // Adicionar classe ativa na aba clicada
        $(this).addClass('nav-tab-active');
        
        // Mostrar o painel correspondente
        var target = $(this).attr('href').substring(1);
        $('#tab-' + target).addClass('active').show();
    });
    
    // Inicializar - mostrar o painel padrão
    $('.nav-tab-wrapper a:first').trigger('click');

    // Atualizar contador de usuários online imediatamente e depois a cada 30 segundos
    function atualizarUsuariosOnline() {
        $.ajax({
            url: analiseVisitantesData.ajaxurl,
            type: 'POST',
            data: {
                action: 'atualizar_usuarios_online'
            },
            success: function(response) {
                try {
                    var data = JSON.parse(response);
                    $('#usuarios-online').text(data.count);
                } catch (e) {
                    console.error('Erro ao processar resposta AJAX:', e);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição AJAX:', error);
            }
        });
    }
    
    // Executar uma vez ao carregar a página
    atualizarUsuariosOnline();
    
    // Configurar execução a cada 30 segundos
    setInterval(atualizarUsuariosOnline, 30000); // 30 segundos
    
    // Atualizar a página a cada 5 minutos para refrescar todos os dados
    // Desativado temporariamente para não interromper interações do usuário
    /*
    setInterval(function() {
        if ($('body').hasClass('toplevel_page_analise-visitantes')) {
            location.reload();
        }
    }, 300000); // 5 minutos
    */
    
    // Aplicar filtros ao clicar no botão
    $('#filter-button').on('click', function(e) {
        e.preventDefault();
        
        var period = $('#period-selector').val();
        var limit = $('#limit-selector').val();
        
        window.location.href = analiseVisitantesData.adminUrl + '?page=analise-visitantes&period=' + period + '&limit=' + limit;
    });
    
    // Tornar as tabelas de dados ordenáveis (se houver jQuery UI)
    if ($.fn.sortable) {
        $('.wp-list-table tbody').sortable({
            items: 'tr',
            cursor: 'move',
            axis: 'y',
            handle: 'td:first-child',
            placeholder: 'ui-state-highlight',
            start: function(e, ui) {
                ui.placeholder.height(ui.item.height());
            }
        });
    }
});