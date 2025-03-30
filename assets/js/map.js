/**
 * JavaScript para o mapa de visitantes
 */

var avVisitorsMap = null;
var avMarkers = [];

/**
 * Inicializar quando o documento estiver pronto
 */
jQuery(document).ready(function($) {
    // Inicializar o mapa
    initMap();
    
    // Botão de atualização
    $('#av-refresh-map').on('click', function() {
        refreshMapData();
    });
});

/**
 * Inicializar o mapa de visitantes
 */
function initMap() {
    // Criar o mapa
    avVisitorsMap = L.map('av-visitors-map').setView([0, 0], 2);
    
    // Adicionar camada base
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18
    }).addTo(avVisitorsMap);
    
    // Adicionar escala
    L.control.scale({
        imperial: false
    }).addTo(avVisitorsMap);
    
    // Adicionar marcadores
    addVisitorMarkers(avMapData.countries);
}

/**
 * Adicionar marcadores de visitantes ao mapa
 * 
 * @param {Array} countries Lista de países com coordenadas
 */
function addVisitorMarkers(countries) {
    // Limpar marcadores existentes
    clearMarkers();
    
    // Verificar se há países para exibir
    if (!countries || countries.length === 0) {
        showNoDataMessage();
        return;
    }
    
    // Criar grupo de marcadores
    var markers = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 40,
        iconCreateFunction: createCustomClusterIcon
    });
    
    // Coordenadas de limite para ajustar a visualização
    var bounds = L.latLngBounds();
    
    // Adicionar cada país como um marcador
    countries.forEach(function(country) {
        // Verificar se as coordenadas são válidas
        if (!country.lat || !country.lng) {
            return;
        }
        
        // Criar marcador
        var marker = L.marker([country.lat, country.lng], {
            icon: createVisitorIcon(country.visits),
            title: country.country
        });
        
        // Adicionar popup com informações
        marker.bindPopup(createPopupContent(country));
        
        // Adicionar ao grupo de marcadores
        markers.addLayer(marker);
        
        // Salvar na lista de marcadores
        avMarkers.push(marker);
        
        // Expandir os limites para incluir esse marcador
        bounds.extend([country.lat, country.lng]);
    });
    
    // Adicionar grupo ao mapa
    avVisitorsMap.addLayer(markers);
    
    // Ajustar visualização para mostrar todos os marcadores
    if (bounds.isValid()) {
        avVisitorsMap.fitBounds(bounds, {
            padding: [50, 50]
        });
    }
}

/**
 * Criar conteúdo do popup para um país
 * 
 * @param {Object} country Dados do país
 * @return {string} HTML do popup
 */
function createPopupContent(country) {
    var html = '<div class="av-map-popup">';
    
    // Título com bandeira e nome do país
    html += '<div class="av-popup-title">';
    if (country.code) {
        html += '<img src="https://flagcdn.com/16x12/' + country.code.toLowerCase() + '.png" alt="' + country.country + '" /> ';
    }
    html += country.country;
    html += '</div>';
    
    // Visitas
    html += '<div class="av-popup-visits">';
    html += '<strong>' + avMapData.labels.visits + ':</strong> ' + country.visits;
    html += '</div>';
    
    // Última visita
    if (country.last_visit) {
        html += '<div class="av-popup-last-visit">';
        html += '<strong>' + avMapData.labels.last_visit + ':</strong> ' + formatDate(country.last_visit);
        html += '</div>';
    }
    
    html += '</div>';
    return html;
}

/**
 * Criar ícone personalizado para marcador com base no número de visitas
 * 
 * @param {number} visits Número de visitas
 * @return {L.Icon} Ícone do Leaflet
 */
function createVisitorIcon(visits) {
    // Determinar tamanho e cor com base nas visitas
    var size = calculateMarkerSize(visits);
    var color = calculateMarkerColor(visits);
    
    // Criar ícone personalizado
    return L.divIcon({
        html: '<div style="background-color:' + color + ';" class="av-map-marker">' + visits + '</div>',
        className: 'av-visitor-marker',
        iconSize: [size, size],
        iconAnchor: [size/2, size/2]
    });
}

/**
 * Criar ícone personalizado para clusters
 * 
 * @param {L.MarkerCluster} cluster Cluster de marcadores
 * @return {L.Icon} Ícone do cluster
 */
function createCustomClusterIcon(cluster) {
    // Contar total de visitas no cluster
    var totalVisits = 0;
    var markers = cluster.getAllChildMarkers();
    
    markers.forEach(function(marker) {
        var visits = parseInt(marker.getIcon().options.html.match(/\d+/)[0]);
        totalVisits += visits;
    });
    
    // Determinar tamanho e cor com base nas visitas
    var size = calculateMarkerSize(totalVisits);
    var color = calculateMarkerColor(totalVisits);
    
    // Criar ícone personalizado para o cluster
    return L.divIcon({
        html: '<div style="background-color:' + color + ';" class="av-map-cluster">' + totalVisits + '</div>',
        className: 'av-visitor-cluster',
        iconSize: [size, size],
        iconAnchor: [size/2, size/2]
    });
}

/**
 * Calcular tamanho do marcador com base no número de visitas
 * 
 * @param {number} visits Número de visitas
 * @return {number} Tamanho do marcador em pixels
 */
function calculateMarkerSize(visits) {
    // Tamanho base
    var baseSize = 30;
    
    // Ajustar tamanho conforme número de visitas
    if (visits < 10) {
        return baseSize;
    } else if (visits < 50) {
        return baseSize + 5;
    } else if (visits < 100) {
        return baseSize + 10;
    } else if (visits < 500) {
        return baseSize + 15;
    } else {
        return baseSize + 20;
    }
}

/**
 * Calcular cor do marcador com base no número de visitas
 * 
 * @param {number} visits Número de visitas
 * @return {string} Código de cor em hexadecimal
 */
function calculateMarkerColor(visits) {
    // Cores por faixa de visitas
    if (visits < 10) {
        return '#2196F3'; // Azul
    } else if (visits < 50) {
        return '#4CAF50'; // Verde
    } else if (visits < 100) {
        return '#FF9800'; // Laranja
    } else if (visits < 500) {
        return '#F44336'; // Vermelho
    } else {
        return '#9C27B0'; // Roxo
    }
}

/**
 * Limpar todos os marcadores do mapa
 */
function clearMarkers() {
    // Limpar lista de marcadores
    avMarkers.forEach(function(marker) {
        avVisitorsMap.removeLayer(marker);
    });
    
    avMarkers = [];
    
    // Remover todos os layers
    avVisitorsMap.eachLayer(function(layer) {
        if (layer instanceof L.MarkerClusterGroup) {
            avVisitorsMap.removeLayer(layer);
        }
    });
}

/**
 * Mostrar mensagem quando não há dados para exibir
 */
function showNoDataMessage() {
    var center = avVisitorsMap.getCenter();
    
    var marker = L.marker(center, {
        icon: L.divIcon({
            html: '<div class="av-no-data">Nenhum dado de localização disponível</div>',
            className: 'av-no-data-marker',
            iconSize: [200, 30],
            iconAnchor: [100, 15]
        })
    }).addTo(avVisitorsMap);
    
    avMarkers.push(marker);
}

/**
 * Atualizar dados do mapa via AJAX
 */
function refreshMapData() {
    jQuery('.av-loading-overlay').addClass('active');
    
    jQuery.ajax({
        url: ajaxurl,
        method: 'POST',
        data: {
            action: 'av_atualizar_mapa',
            nonce: avMapData.nonce
        },
        success: function(response) {
            if (response.success) {
                // Atualizar marcadores com novos dados
                addVisitorMarkers(response.data.countries);
                
                // Atualizar dados globais
                avMapData.countries = response.data.countries;
            }
            
            jQuery('.av-loading-overlay').removeClass('active');
        },
        error: function() {
            jQuery('.av-loading-overlay').removeClass('active');
        }
    });
}

/**
 * Formatar data
 * 
 * @param {string} dateString String de data em formato MySQL
 * @return {string} Data formatada
 */
function formatDate(dateString) {
    var date = new Date(dateString);
    
    if (isNaN(date.getTime())) {
        return dateString;
    }
    
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
} 