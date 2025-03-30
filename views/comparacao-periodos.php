<?php
/**
 * Template para comparação de períodos
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap analise-visitantes-comparacao">
    <h2>Comparação de Períodos</h2>
    
    <div class="periodo-selection">
        <form method="get">
            <input type="hidden" name="page" value="analise-visitantes-comparacao">
            <label for="data_inicio">Data Inicial:</label>
            <input type="date" id="data_inicio" name="data_inicio" value="<?php echo esc_attr($data_inicio); ?>">
            
            <label for="data_fim">Data Final:</label>
            <input type="date" id="data_fim" name="data_fim" value="<?php echo esc_attr($data_fim); ?>">
            
            <button type="submit" class="button button-primary">Comparar Períodos</button>
        </form>
    </div>
    
    <div class="periodo-info">
        <div class="periodo-atual">
            <h3>Período Atual: <?php echo date('d/m/Y', strtotime($data_inicio)); ?> até <?php echo date('d/m/Y', strtotime($data_fim)); ?></h3>
            <p class="total-visitas">Total de Visitas: <strong><?php echo $total_periodo_atual; ?></strong></p>
        </div>
        
        <div class="periodo-anterior">
            <h3>Período Anterior: <?php echo date('d/m/Y', strtotime($data_inicio_anterior)); ?> até <?php echo date('d/m/Y', strtotime($data_fim_anterior)); ?></h3>
            <p class="total-visitas">Total de Visitas: <strong><?php echo $total_periodo_anterior; ?></strong></p>
        </div>
        
        <div class="variacao-percentual">
            <h3>Variação:</h3>
            <p class="percentual <?php echo $variacao_percentual >= 0 ? 'positivo' : 'negativo'; ?>">
                <?php echo number_format($variacao_percentual, 2); ?>%
                <?php if ($variacao_percentual >= 0): ?>
                    <span class="dashicons dashicons-arrow-up-alt"></span>
                <?php else: ?>
                    <span class="dashicons dashicons-arrow-down-alt"></span>
                <?php endif; ?>
            </p>
        </div>
    </div>
    
    <div class="dashboard-row">
        <div class="dashboard-cell full-width">
            <h3>Tendência de Visitas</h3>
            <canvas id="comparacao-chart" width="800" height="300"></canvas>
        </div>
    </div>
    
    <div class="dashboard-row">
        <div class="dashboard-cell">
            <h3>Páginas Mais Visitadas - Período Atual</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Página</th>
                        <th width="100">Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paginas_populares_atual)): ?>
                    <tr>
                        <td colspan="2">Nenhuma página visitada neste período.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($paginas_populares_atual as $pagina): ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url($pagina->page_url); ?>" target="_blank">
                                    <?php echo esc_html($pagina->page_title); ?>
                                </a>
                            </td>
                            <td><?php echo $pagina->count; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="dashboard-cell">
            <h3>Páginas Mais Visitadas - Período Anterior</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Página</th>
                        <th width="100">Visitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paginas_populares_anterior)): ?>
                    <tr>
                        <td colspan="2">Nenhuma página visitada neste período.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($paginas_populares_anterior as $pagina): ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url($pagina->page_url); ?>" target="_blank">
                                    <?php echo esc_html($pagina->page_title); ?>
                                </a>
                            </td>
                            <td><?php echo $pagina->count; ?></td>
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
    var ctx = document.getElementById('comparacao-chart').getContext('2d');
    
    // Preparar dados para o gráfico
    var labels = [];
    var dataAtual = [];
    var dataAnterior = [];
    
    // Criar array com todas as datas possíveis no intervalo
    var dataInicio = new Date('<?php echo $data_inicio; ?>');
    var dataFim = new Date('<?php echo $data_fim; ?>');
    var dataInicioPadrao = new Date(dataInicio);
    
    // Converter os dados do PHP para JavaScript
    var visitasPorDiaAtual = <?php echo json_encode($visitas_por_dia_atual); ?>;
    var visitasPorDiaAnterior = <?php echo json_encode($visitas_por_dia_anterior); ?>;
    
    // Mapear dados para arrays
    var dadosAtual = {};
    var dadosAnterior = {};
    
    visitasPorDiaAtual.forEach(function(item) {
        dadosAtual[item.dia] = parseInt(item.count);
    });
    
    visitasPorDiaAnterior.forEach(function(item) {
        dadosAnterior[item.dia] = parseInt(item.count);
    });
    
    // Construir arrays com valores para todas as datas
    for (var d = new Date(dataInicio); d <= dataFim; d.setDate(d.getDate() + 1)) {
        var dataFormatada = d.toISOString().split('T')[0];
        
        // Adicionar label formatado como dia/mês
        var dia = d.getDate().toString().padStart(2, '0');
        var mes = (d.getMonth() + 1).toString().padStart(2, '0');
        labels.push(dia + '/' + mes);
        
        // Adicionar dados ou zero se não houver visita nesse dia
        dataAtual.push(dadosAtual[dataFormatada] || 0);
        
        // Para o período anterior, calcular a data correspondente
        var dataCorrespondente = new Date(dataInicioPadrao);
        var diffDias = Math.round((d - dataInicio) / (1000 * 60 * 60 * 24));
        dataCorrespondente.setDate(dataCorrespondente.getDate() - (<?php echo $duracao; ?> + 1) + diffDias);
        
        var dataCorrespondenteFormatada = dataCorrespondente.toISOString().split('T')[0];
        
        dataAnterior.push(dadosAnterior[dataCorrespondenteFormatada] || 0);
    }
    
    // Criar gráfico
    var myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Período Atual',
                    data: dataAtual,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.1
                },
                {
                    label: 'Período Anterior',
                    data: dataAnterior,
                    backgroundColor: 'rgba(255, 206, 86, 0.2)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 2,
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script> 