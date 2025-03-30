<?php
/**
 * Template para o mapa geográfico de visitantes
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap analise-visitantes-mapa">
    <h2>Mapa Geográfico de Visitantes</h2>
    
    <div class="map-container">
        <div id="visitor-map" style="width: 100%; height: 500px;"></div>
    </div>
    
    <div class="dashboard-row">
        <div class="dashboard-cell">
            <h3>Visitas por País</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>País</th>
                        <th width="100">Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paises)): ?>
                    <tr>
                        <td colspan="2">Nenhum dado de país encontrado.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($paises as $pais): ?>
                        <tr>
                            <td>
                                <?php echo esc_html($pais->country_name); ?>
                                <span class="country-code">(<?php echo esc_html($pais->country_code); ?>)</span>
                            </td>
                            <td><?php echo $pais->visits; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="dashboard-cell">
            <h3>Visitas por Cidade</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Cidade</th>
                        <th>País</th>
                        <th width="100">Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cidades)): ?>
                    <tr>
                        <td colspan="3">Nenhum dado de cidade encontrado.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($cidades as $cidade): ?>
                        <tr>
                            <td><?php echo esc_html($cidade->city); ?></td>
                            <td><?php echo esc_html($cidade->country); ?></td>
                            <td><?php echo $cidade->visits; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar o mapa usando Leaflet.js (biblioteca de mapas gratuita)
    var map = L.map('visitor-map').setView([20, 0], 2);
    
    // Adicionar camada de mapa
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Adicionar marcadores para cada localização
    <?php foreach ($localizacoes as $loc): ?>
    L.marker([<?php echo $loc->latitude; ?>, <?php echo $loc->longitude; ?>])
        .addTo(map)
        .bindPopup('<?php echo esc_js($loc->city) . ', ' . esc_js($loc->country); ?>: <?php echo $loc->visits; ?> visitas');
    <?php endforeach; ?>
    
    // Círculos proporcionais ao número de visitas
    <?php foreach ($paises_loc as $loc): ?>
    L.circle([<?php echo $loc->latitude; ?>, <?php echo $loc->longitude; ?>], {
        color: 'red',
        fillColor: '#f03',
        fillOpacity: 0.5,
        radius: <?php echo min(100000, max(30000, $loc->visits * 5000)); ?>
    }).addTo(map)
    .bindPopup('<?php echo esc_js($loc->country_name); ?>: <?php echo $loc->visits; ?> visitas');
    <?php endforeach; ?>
});
</script> 