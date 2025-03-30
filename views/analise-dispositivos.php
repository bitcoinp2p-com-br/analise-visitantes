<?php
/**
 * Template para análise de dispositivos e navegadores
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap analise-visitantes-dispositivos">
    <h2>Análise de Dispositivos e Navegadores</h2>
    
    <div class="dashboard-row">
        <div class="dashboard-cell chart-cell">
            <h3>Distribuição por Tipo de Dispositivo</h3>
            <canvas id="device-chart" width="400" height="400"></canvas>
        </div>
        
        <div class="dashboard-cell chart-cell">
            <h3>Navegadores Mais Usados</h3>
            <canvas id="browser-chart" width="400" height="400"></canvas>
        </div>
        
        <div class="dashboard-cell chart-cell">
            <h3>Sistemas Operacionais</h3>
            <canvas id="os-chart" width="400" height="400"></canvas>
        </div>
    </div>
    
    <div class="dashboard-row">
        <div class="dashboard-cell">
            <h3>Detalhes de Dispositivos e Navegadores</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Dispositivo</th>
                        <th>Navegador</th>
                        <th>Sistema Operacional</th>
                        <th width="100">Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dispositivos)): ?>
                    <tr>
                        <td colspan="4">Nenhum dado de dispositivo encontrado.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($dispositivos as $disp): ?>
                        <tr>
                            <td><?php echo esc_html($disp->device_type); ?></td>
                            <td><?php echo esc_html($disp->browser); ?></td>
                            <td><?php echo esc_html($disp->operating_system); ?></td>
                            <td><?php echo $disp->visits; ?></td>
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
    // Gráfico de Dispositivos
    var deviceCtx = document.getElementById('device-chart').getContext('2d');
    var deviceData = {
        labels: [<?php 
            $device_labels = array();
            foreach ($dispositivos_resumo as $device) {
                $device_labels[] = "'" . $device->device_type . "'";
            }
            echo implode(', ', $device_labels);
        ?>],
        datasets: [{
            data: [<?php 
                $device_counts = array();
                foreach ($dispositivos_resumo as $device) {
                    $device_counts[] = $device->count;
                }
                echo implode(', ', $device_counts);
            ?>],
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)'
            ],
            borderWidth: 1
        }]
    };
    
    var deviceChart = new Chart(deviceCtx, {
        type: 'pie',
        data: deviceData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Distribuição por Tipo de Dispositivo'
                }
            }
        }
    });
    
    // Gráfico de Navegadores
    var browserCtx = document.getElementById('browser-chart').getContext('2d');
    var browserData = {
        labels: [<?php 
            $browser_labels = array();
            foreach ($navegadores_resumo as $browser) {
                $browser_labels[] = "'" . $browser->browser . "'";
            }
            echo implode(', ', $browser_labels);
        ?>],
        datasets: [{
            data: [<?php 
                $browser_counts = array();
                foreach ($navegadores_resumo as $browser) {
                    $browser_counts[] = $browser->count;
                }
                echo implode(', ', $browser_counts);
            ?>],
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }]
    };
    
    var browserChart = new Chart(browserCtx, {
        type: 'doughnut',
        data: browserData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Navegadores Mais Usados'
                }
            }
        }
    });
    
    // Gráfico de Sistemas Operacionais
    var osCtx = document.getElementById('os-chart').getContext('2d');
    var osData = {
        labels: [<?php 
            $os_labels = array();
            foreach ($os_resumo as $os) {
                $os_labels[] = "'" . $os->operating_system . "'";
            }
            echo implode(', ', $os_labels);
        ?>],
        datasets: [{
            data: [<?php 
                $os_counts = array();
                foreach ($os_resumo as $os) {
                    $os_counts[] = $os->count;
                }
                echo implode(', ', $os_counts);
            ?>],
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }]
    };
    
    var osChart = new Chart(osCtx, {
        type: 'pie',
        data: osData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Sistemas Operacionais'
                }
            }
        }
    });
});
</script> 