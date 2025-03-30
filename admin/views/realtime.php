<?php
/**
 * Template da página de visitantes em tempo real
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap analise-visitantes-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-visibility"></span> 
        Visitantes em Tempo Real
    </h1>
    
    <div class="av-dashboard-controls">
        <div class="av-realtime-info">
            <span>Atualizando a cada <?php echo intval(avRealtimeData['refreshInterval'] / 1000); ?> segundos</span>
        </div>
        
        <div class="av-refresh-button">
            <button id="av-refresh-realtime" class="button button-secondary">
                <span class="dashicons dashicons-update"></span> Atualizar Agora
            </button>
        </div>
    </div>
    
    <div class="av-loading-indicator">
        <div class="av-spinner">
            <div class="av-bounce1"></div>
            <div class="av-bounce2"></div>
            <div class="av-bounce3"></div>
        </div>
    </div>
    
    <div class="av-realtime-header">
        <div class="av-online-counter">
            <span class="dashicons dashicons-admin-users"></span>
            <span id="av-online-count"><?php echo intval(avRealtimeData['online']); ?></span> visitantes online agora
        </div>
    </div>
    
    <div class="av-realtime-visitors">
        <h2 class="av-realtime-title">
            <span class="dashicons dashicons-welcome-view-site"></span>
            Últimas visualizações de página
        </h2>
        
        <div class="av-realtime-visitors-header">
            <div class="av-visitor-page">Página</div>
            <div class="av-visitor-location">Localização</div>
            <div class="av-visitor-device">Dispositivo</div>
            <div class="av-visitor-time">Horário</div>
        </div>
        
        <div id="av-realtime-visitors-list">
            <div class="av-no-visitors">Carregando dados...</div>
        </div>
    </div>
</div>

<style>
    /* Estilos específicos para esta página */
    .av-realtime-header {
        margin-bottom: 20px;
    }
    
    .av-online-counter {
        font-size: 24px;
        font-weight: 600;
        display: flex;
        align-items: center;
    }
    
    .av-online-counter .dashicons {
        color: #4CAF50;
        margin-right: 10px;
        font-size: 28px;
        width: 28px;
        height: 28px;
    }
    
    .av-realtime-visitors {
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-bottom: 30px;
    }
    
    .av-realtime-title {
        margin-top: 0;
        font-size: 18px;
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .av-realtime-title .dashicons {
        color: #2196F3;
        margin-right: 10px;
    }
    
    .av-realtime-visitors-header {
        display: flex;
        font-weight: 600;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 10px;
    }
    
    .av-visitor-item {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f2f2f2;
        transition: background-color 0.3s ease;
    }
    
    .av-visitor-item:hover {
        background-color: #f9f9f9;
    }
    
    .av-new-visitor {
        background-color: #e8f5e9;
        animation: highlightNew 3s ease-out;
    }
    
    .av-visitor-page {
        flex: 2;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .av-visitor-location {
        flex: 1;
        display: flex;
        align-items: center;
    }
    
    .av-visitor-location img {
        margin-right: 6px;
    }
    
    .av-visitor-device {
        flex: 1;
        display: flex;
        align-items: center;
    }
    
    .av-visitor-device .dashicons {
        margin-right: 6px;
        color: #607D8B;
    }
    
    .av-visitor-time {
        flex: 0 0 120px;
        text-align: right;
        color: #666;
    }
    
    .av-no-visitors {
        padding: 20px 0;
        text-align: center;
        color: #666;
        font-style: italic;
    }
    
    .av-counter-update {
        animation: pulse 0.5s ease-in-out;
    }
    
    .av-loading-indicator {
        position: fixed;
        top: 32px;
        right: 20px;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        padding: 8px 12px;
        display: flex;
        align-items: center;
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 9999;
    }
    
    .av-loading-indicator.active {
        opacity: 1;
    }
    
    @keyframes highlightNew {
        0% { background-color: #e8f5e9; }
        100% { background-color: transparent; }
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    @media screen and (max-width: 782px) {
        .av-visitor-location, .av-visitor-device {
            display: none;
        }
        
        .av-visitor-page {
            flex: 1;
        }
    }
</style> 