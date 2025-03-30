/**
 * Script para análise em tempo real
 * Fornece atualizações automáticas e interatividade para a página de análise em tempo real
 */

jQuery(document).ready(function($) {
    // Verificar se estamos na página de análise em tempo real
    if (!$('.analise-visitantes-tempo-real').length) {
        return;
    }
    
    // Inicializar momento.js com locale PT-BR
    moment.locale('pt-br');
    
    // Efeito pulsante para o indicador LIVE
    setInterval(function() {
        $('.live-indicator').toggleClass('pulse');
    }, 1000);
    
    // Função para atualizar os timestamps relativos
    function atualizarTimestamps() {
        $('.timestamp').each(function() {
            var timestamp = $(this).data('time');
            $(this).text(moment(timestamp).fromNow());
        });
    }
    
    // Atualizar timestamps ao carregar
    atualizarTimestamps();
    
    // Atualizar timestamps a cada 30 segundos
    setInterval(atualizarTimestamps, 30000);
    
    // Filtrar tabelas com campo de busca
    $('.search-filter input').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        var target = $(this).closest('.dashboard-cell').find('table tbody tr');
        
        target.each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(value) > -1);
        });
    });
    
    // Alternar visualização entre gráficos e tabelas
    $('.toggle-view').on('click', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        $('#' + target + '-chart, #' + target + '-table').toggle();
        $(this).find('span').toggleClass('dashicons-chart-bar dashicons-list-view');
    });
    
    // Função para acompanhar a última atividade conhecida
    var ultimaAtividade = '';
    if ($('#realtime-activity tbody tr').length > 0) {
        ultimaAtividade = $('#realtime-activity tbody tr:first-child .timestamp').data('time');
    }
    
    // Função para obter atualizações em tempo real
    function obterAtualizacoes() {
        $.ajax({
            url: analiseVisitantesData.ajaxurl,
            type: 'POST',
            data: {
                action: 'atualizar_tempo_real',
                ultima_atividade: ultimaAtividade,
                nonce: analiseVisitantesData.nonce
            },
            success: function(response) {
                if (!response.success) {
                    return;
                }
                
                // Atualizar usuários online
                $('#usuarios-online').text(response.data.usuarios_online);
                
                // Atualizar visitas nas últimas 24 horas
                if (response.data.visitas_24h) {
                    $('.big-number:eq(1)').text(response.data.visitas_24h);
                }
                
                // Atualizar tendência de tráfego
                if (response.data.tendencia !== undefined) {
                    if (response.data.tendencia > 0) {
                        $('#trend-up').show();
                        $('#trend-down, #trend-stable').hide();
                        $('#trend-up-value').text(response.data.tendencia);
                    } else if (response.data.tendencia < 0) {
                        $('#trend-down').show();
                        $('#trend-up, #trend-stable').hide();
                        $('#trend-down-value').text(Math.abs(response.data.tendencia));
                    } else {
                        $('#trend-stable').show();
                        $('#trend-up, #trend-down').hide();
                    }
                }
                
                // Adicionar novas atividades
                if (response.data.novas_visitas && response.data.novas_visitas.length > 0) {
                    var tbody = $('#realtime-activity tbody');
                    var emptyRow = tbody.find('tr td[colspan]').parent();
                    
                    // Remover mensagem "nenhuma atividade" se existir
                    if (emptyRow.length) {
                        emptyRow.remove();
                    }
                    
                    // Adicionar novas linhas no início
                    $.each(response.data.novas_visitas, function(index, visita) {
                        // Atualizar última atividade conhecida
                        if (index === 0) {
                            ultimaAtividade = visita.date_time;
                        }
                        
                        // Criar a nova linha
                        var newRow = $('<tr class="new-activity"></tr>');
                        
                        // Adicionar células
                        newRow.append('<td class="timestamp" data-time="' + visita.date_time + '">' + moment(visita.date_time).fromNow() + '</td>');
                        newRow.append('<td><a href="' + visita.page_url + '" target="_blank">' + visita.page_title + '</a></td>');
                        
                        // Origem (referrer)
                        var referrerCell = '';
                        if (visita.referrer) {
                            var domain = new URL(visita.referrer).hostname;
                            referrerCell = domain;
                        } else {
                            referrerCell = 'Acesso Direto';
                        }
                        newRow.append('<td>' + referrerCell + '</td>');
                        
                        // Localização
                        var locationCell = '';
                        if (visita.country && visita.country !== 'Desconhecido') {
                            locationCell = visita.country;
                            if (visita.city && visita.city !== 'Desconhecido') {
                                locationCell += ' / ' + visita.city;
                            }
                        } else {
                            locationCell = 'Localização indisponível';
                        }
                        newRow.append('<td>' + locationCell + '</td>');
                        
                        // Dispositivo e navegador
                        newRow.append('<td>' + visita.device_type + ' / ' + visita.browser + '</td>');
                        
                        // Adicionar a linha à tabela
                        tbody.prepend(newRow);
                        
                        // Remover a classe após a animação
                        setTimeout(function() {
                            newRow.removeClass('new-activity');
                        }, 3000);
                    });
                    
                    // Limitar a quantidade de linhas (manter apenas as 30 mais recentes)
                    if (tbody.find('tr').length > 30) {
                        tbody.find('tr').slice(30).remove();
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro ao atualizar dados em tempo real:', error);
            }
        });
    }
    
    // Atualizar a cada 15 segundos
    setInterval(obterAtualizacoes, 15000);
    
    // Disparar uma atualização inicial após 5 segundos
    setTimeout(obterAtualizacoes, 5000);
}); 