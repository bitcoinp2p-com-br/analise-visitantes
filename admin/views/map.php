<?php
/**
 * Template da página de mapa de visitantes
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap analise-visitantes-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-location-alt"></span> 
        Mapa de Visitantes
    </h1>
    
    <div class="av-dashboard-controls">
        <div class="av-map-info">
            <span>Mapa mostrando países de origem dos visitantes</span>
        </div>
        
        <div class="av-refresh-button">
            <button id="av-refresh-map" class="button button-secondary">
                <span class="dashicons dashicons-update"></span> Atualizar Mapa
            </button>
        </div>
    </div>
    
    <div class="av-loading-overlay">
        <div class="av-spinner">
            <div class="av-bounce1"></div>
            <div class="av-bounce2"></div>
            <div class="av-bounce3"></div>
        </div>
    </div>
    
    <div class="av-map-container">
        <h2 class="av-map-title">Distribuição Geográfica de Visitantes</h2>
        <div id="av-visitors-map"></div>
        <div class="av-map-legend">
            <p><strong>Legenda:</strong></p>
            <div class="av-legend-item">
                <span class="av-legend-marker" style="background-color: #2196F3;"></span>
                <span class="av-legend-text">Menos de 10 visitas</span>
            </div>
            <div class="av-legend-item">
                <span class="av-legend-marker" style="background-color: #4CAF50;"></span>
                <span class="av-legend-text">10-49 visitas</span>
            </div>
            <div class="av-legend-item">
                <span class="av-legend-marker" style="background-color: #FF9800;"></span>
                <span class="av-legend-text">50-99 visitas</span>
            </div>
            <div class="av-legend-item">
                <span class="av-legend-marker" style="background-color: #F44336;"></span>
                <span class="av-legend-text">100-499 visitas</span>
            </div>
            <div class="av-legend-item">
                <span class="av-legend-marker" style="background-color: #9C27B0;"></span>
                <span class="av-legend-text">500+ visitas</span>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos específicos do mapa */
    .av-map-container {
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-bottom: 30px;
    }
    
    .av-map-title {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 18px;
    }
    
    #av-visitors-map {
        height: 500px;
        width: 100%;
        border-radius: 4px;
        border: 1px solid #ddd;
        z-index: 1;
    }
    
    .av-map-legend {
        margin-top: 15px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .av-map-legend p {
        margin: 0 20px 0 0;
    }
    
    .av-legend-item {
        display: flex;
        align-items: center;
        margin-right: 20px;
        margin-bottom: 5px;
    }
    
    .av-legend-marker {
        display: inline-block;
        width: 15px;
        height: 15px;
        border-radius: 50%;
        margin-right: 5px;
    }
    
    .av-legend-text {
        font-size: 13px;
    }
    
    /* Estilos para os popups do mapa */
    .av-map-popup {
        padding: 5px;
        min-width: 150px;
    }
    
    .av-popup-title {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
    }
    
    .av-popup-title img {
        margin-right: 5px;
    }
    
    .av-popup-visits, .av-popup-last-visit {
        font-size: 12px;
        margin: 3px 0;
    }
    
    /* Estilos para os marcadores do mapa */
    .av-map-marker, .av-map-cluster {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: white;
        font-weight: bold;
        box-shadow: 0 1px 5px rgba(0, 0, 0, 0.3);
        border: 2px solid rgba(255, 255, 255, 0.8);
    }
    
    .av-no-data {
        background: rgba(255, 255, 255, 0.9);
        padding: 5px 10px;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        color: #666;
        font-style: italic;
        text-align: center;
    }
    
    /* Responsividade */
    @media screen and (max-width: 782px) {
        #av-visitors-map {
            height: 300px;
        }
        
        .av-map-legend {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .av-map-legend p {
            margin-bottom: 5px;
        }
    }
</style> 