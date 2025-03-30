/**
 * JavaScript para o monitoramento em tempo real
 */

// Variáveis globais
var avRealtimeTimer = null;
var avLastTimestamp = 0;

/**
 * Inicializar quando o documento estiver pronto
 */
jQuery(document).ready(function($) {
    // Inicializar contador online
    updateOnlineCounter(avRealtimeData.online);
    
    // Preencher tabela de visitantes
    updateVisitorsTable(avRealtimeData.visitors);
    
    // Configurar atualização automática
    startAutoRefresh();
    
    // Botão de atualização manual
    $('#av-refresh-realtime').on('click', function() {
        refreshRealtimeData();
    });
});

/**
 * Iniciar atualização automática
 */
function startAutoRefresh() {
    // Limpar timer existente
    if (avRealtimeTimer) {
        clearInterval(avRealtimeTimer);
    }
    
    // Iniciar novo timer para atualização a cada X segundos
    avRealtimeTimer = setInterval(function() {
        refreshRealtimeData();
    }, avRealtimeData.refreshInterval);
}

/**
 * Atualizar contador de visitantes online
 * 
 * @param {number} count Número de visitantes online
 */
function updateOnlineCounter(count) {
    var $counter = jQuery('#av-online-count');
    var currentCount = parseInt($counter.text());
    
    // Apenas atualizar se o valor for diferente
    if (currentCount !== count) {
        // Animação simples
        $counter.addClass('av-counter-update');
        
        setTimeout(function() {
            $counter.text(count);
            $counter.removeClass('av-counter-update');
        }, 300);
    }
}

/**
 * Atualizar tabela de visitantes recentes
 * 
 * @param {Array} visitors Lista de visitantes recentes
 */
function updateVisitorsTable(visitors) {
    var $container = jQuery('#av-realtime-visitors-list');
    var html = '';
    
    if (visitors.length === 0) {
        html = '<div class="av-no-visitors">Nenhum visitante registrado nos últimos 15 minutos.</div>';
    } else {
        // Encontrar timestamp mais recente
        var maxTimestamp = 0;
        visitors.forEach(function(visitor) {
            if (visitor.timestamp > maxTimestamp) {
                maxTimestamp = visitor.timestamp;
            }
        });
        
        // Atualizar timestamp mais recente
        avLastTimestamp = Math.max(avLastTimestamp, maxTimestamp);
        
        // Criar HTML para cada visitante
        visitors.forEach(function(visitor) {
            var timeAgo = formatTimeAgo(visitor.timestamp);
            var isNew = visitor.timestamp > avLastTimestamp - 60; // Visitantes dos últimos 60 segundos
            
            html += '<div class="av-visitor-item' + (isNew ? ' av-new-visitor' : '') + '">';
            
            // Coluna da página
            html += '<div class="av-visitor-page">';
            html += '<a href="' + visitor.url + '" target="_blank" title="' + visitor.page + '">';
            html += truncateText(visitor.page, 40);
            html += '</a>';
            html += '</div>';
            
            // Coluna da localização
            html += '<div class="av-visitor-location">';
            if (visitor.country_code) {
                html += '<img src="https://flagcdn.com/16x12/' + visitor.country_code.toLowerCase() + '.png" alt="' + visitor.country + '" /> ';
            }
            html += visitor.city ? visitor.city + ', ' + visitor.country : visitor.country;
            html += '</div>';
            
            // Coluna do dispositivo
            html += '<div class="av-visitor-device">';
            html += formatDeviceInfo(visitor.device, visitor.browser, visitor.os);
            html += '</div>';
            
            // Coluna do tempo
            html += '<div class="av-visitor-time">';
            html += timeAgo;
            html += '</div>';
            
            html += '</div>';
        });
    }
    
    $container.html(html);
}

/**
 * Atualizar dados em tempo real via AJAX
 */
function refreshRealtimeData() {
    jQuery('.av-loading-indicator').addClass('active');
    
    jQuery.ajax({
        url: ajaxurl,
        method: 'POST',
        data: {
            action: 'av_atualizar_tempo_real',
            nonce: avRealtimeData.nonce,
            last_timestamp: avLastTimestamp
        },
        success: function(response) {
            if (response.success) {
                // Atualizar contador online
                updateOnlineCounter(response.data.online);
                
                // Atualizar visitantes se houver novos
                if (response.data.visitors.length > 0) {
                    updateVisitorsTable(response.data.visitors);
                }
            }
            
            jQuery('.av-loading-indicator').removeClass('active');
        },
        error: function() {
            jQuery('.av-loading-indicator').removeClass('active');
        }
    });
}

/**
 * Formatar informações do dispositivo
 * 
 * @param {string} device Tipo de dispositivo
 * @param {string} browser Navegador
 * @param {string} os Sistema operacional
 * @return {string} Texto formatado
 */
function formatDeviceInfo(device, browser, os) {
    var deviceIcons = {
        'desktop': '<span class="dashicons dashicons-desktop"></span>',
        'mobile': '<span class="dashicons dashicons-smartphone"></span>',
        'tablet': '<span class="dashicons dashicons-tablet"></span>'
    };
    
    var icon = deviceIcons[device] || deviceIcons['desktop'];
    return icon + ' ' + browser + (os ? ' / ' + os : '');
}

/**
 * Formatar tempo relativo
 * 
 * @param {number} timestamp Timestamp Unix
 * @return {string} Texto formatado
 */
function formatTimeAgo(timestamp) {
    var now = Math.floor(Date.now() / 1000);
    var seconds = now - timestamp;
    
    if (seconds < 10) {
        return avRealtimeData.labels.just_now;
    } else if (seconds < 60) {
        return seconds + ' ' + avRealtimeData.labels.seconds_ago;
    } else {
        var minutes = Math.floor(seconds / 60);
        return minutes + ' ' + avRealtimeData.labels.minutes_ago;
    }
}

/**
 * Truncar texto com tamanho máximo
 * 
 * @param {string} text Texto a truncar
 * @param {number} maxLength Comprimento máximo
 * @return {string} Texto truncado
 */
function truncateText(text, maxLength) {
    if (text.length <= maxLength) {
        return text;
    }
    
    return text.substring(0, maxLength - 3) + '...';
} 