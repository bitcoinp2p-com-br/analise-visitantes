<?php
/**
 * Template da página de relatórios
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Obter período selecionado
$days = isset($_GET['days']) ? intval($_GET['days']) : 30;
$tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : 'visitantes';
?>

<div class="wrap analise-visitantes-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-clipboard"></span> 
        Relatórios - Análise de Visitantes
    </h1>
    
    <div class="av-dashboard-controls">
        <div class="av-period-selector">
            <span class="av-period-label">Período:</span>
            <select id="av-period-select" class="av-select">
                <option value="7" <?php selected($days, 7); ?>>Últimos 7 dias</option>
                <option value="15" <?php selected($days, 15); ?>>Últimos 15 dias</option>
                <option value="30" <?php selected($days, 30); ?>>Últimos 30 dias</option>
                <option value="60" <?php selected($days, 60); ?>>Últimos 60 dias</option>
                <option value="90" <?php selected($days, 90); ?>>Últimos 90 dias</option>
            </select>
        </div>
        
        <div class="av-report-type">
            <span class="av-type-label">Tipo de Relatório:</span>
            <select id="av-report-type-select" class="av-select">
                <option value="visitantes" <?php selected($tipo, 'visitantes'); ?>>Visitantes</option>
                <option value="paginas" <?php selected($tipo, 'paginas'); ?>>Páginas</option>
                <option value="referencia" <?php selected($tipo, 'referencia'); ?>>Referências</option>
                <option value="localizacao" <?php selected($tipo, 'localizacao'); ?>>Localização</option>
                <option value="dispositivos" <?php selected($tipo, 'dispositivos'); ?>>Dispositivos</option>
            </select>
        </div>
        
        <div class="av-refresh-button">
            <button id="av-generate-report" class="button button-primary">
                <span class="dashicons dashicons-chart-bar"></span> Gerar Relatório
            </button>
            <button id="av-export-csv" class="button button-secondary">
                <span class="dashicons dashicons-media-spreadsheet"></span> Exportar CSV
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
    
    <div id="av-report-content" class="av-report-content">
        <!-- Conteúdo do relatório -->
        <div class="av-report-summary">
            <div class="av-summary-card">
                <div class="av-summary-title">Total de Visitantes</div>
                <div class="av-summary-value" id="av-total-visitors">0</div>
            </div>
            
            <div class="av-summary-card">
                <div class="av-summary-title">Total de Visualizações</div>
                <div class="av-summary-value" id="av-total-pageviews">0</div>
            </div>
            
            <div class="av-summary-card">
                <div class="av-summary-title">Média por Dia</div>
                <div class="av-summary-value" id="av-avg-visitors">0</div>
            </div>
        </div>
        
        <!-- Gráfico principal -->
        <div class="av-report-chart-container">
            <div class="av-report-chart-wrapper">
                <canvas id="av-report-chart"></canvas>
            </div>
        </div>
        
        <!-- Tabela de dados detalhados -->
        <div class="av-report-table-container">
            <h3 id="av-report-table-title">Dados Detalhados</h3>
            <table class="widefat striped av-report-table">
                <thead id="av-report-table-head">
                    <tr>
                        <th>Data</th>
                        <th>Visitantes</th>
                        <th>Visualizações</th>
                    </tr>
                </thead>
                <tbody id="av-report-table-body">
                    <tr>
                        <td colspan="3">Carregando dados...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Mudar período ou tipo de relatório
        $('#av-period-select, #av-report-type-select').on('change', function() {
            let days = $('#av-period-select').val();
            let tipo = $('#av-report-type-select').val();
            
            window.location.href = '<?php echo admin_url('admin.php?page=analise-visitantes-reports'); ?>&days=' + days + '&tipo=' + tipo;
        });
        
        // Gerar relatório
        $('#av-generate-report').on('click', function() {
            $('.av-loading-overlay').addClass('active');
            
            var data = {
                action: 'av_gerar_relatorio',
                nonce: avReports.nonce,
                days: <?php echo $days; ?>,
                tipo: '<?php echo $tipo; ?>'
            };
            
            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    updateReportView(response.data);
                } else {
                    alert('Erro ao gerar relatório');
                }
                
                $('.av-loading-overlay').removeClass('active');
            });
        });
        
        // Exportar CSV
        $('#av-export-csv').on('click', function() {
            window.location.href = ajaxurl + '?action=av_exportar_csv&nonce=' + avReports.nonce + 
                                  '&days=<?php echo $days; ?>&tipo=<?php echo $tipo; ?>';
        });
        
        // Inicializar o relatório
        initReport();
        
        // Função para inicializar o relatório
        function initReport() {
            $('.av-loading-overlay').addClass('active');
            
            var data = {
                action: 'av_gerar_relatorio',
                nonce: avReports.nonce,
                days: <?php echo $days; ?>,
                tipo: '<?php echo $tipo; ?>'
            };
            
            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    updateReportView(response.data);
                }
                
                $('.av-loading-overlay').removeClass('active');
            });
        }
        
        // Função para atualizar a visualização do relatório
        function updateReportView(data) {
            // Atualizar estatísticas gerais
            $('#av-total-visitors').text(data.summary.total_visitors);
            $('#av-total-pageviews').text(data.summary.total_pageviews);
            $('#av-avg-visitors').text(data.summary.average_per_day);
            
            // Atualizar o título e cabeçalho da tabela com base no tipo de relatório
            updateTableStructure(data.type);
            
            // Limpar corpo da tabela
            $('#av-report-table-body').empty();
            
            // Preencher dados na tabela
            if (data.items && data.items.length > 0) {
                $.each(data.items, function(index, item) {
                    let row = '<tr>';
                    
                    // Estrutura da linha depende do tipo de relatório
                    switch(data.type) {
                        case 'visitantes':
                            row += '<td>' + item.date + '</td>';
                            row += '<td>' + item.visitors + '</td>';
                            row += '<td>' + item.pageviews + '</td>';
                            break;
                        case 'paginas':
                            row += '<td>' + item.title + '</td>';
                            row += '<td>' + item.url + '</td>';
                            row += '<td>' + item.views + '</td>';
                            break;
                        case 'referencia':
                            row += '<td>' + item.domain + '</td>';
                            row += '<td>' + item.url + '</td>';
                            row += '<td>' + item.count + '</td>';
                            break;
                        case 'localizacao':
                            row += '<td>' + item.country + '</td>';
                            row += '<td>' + item.count + '</td>';
                            row += '<td>' + item.percentage + '%</td>';
                            break;
                        case 'dispositivos':
                            row += '<td>' + item.device + '</td>';
                            row += '<td>' + item.browser + '</td>';
                            row += '<td>' + item.count + '</td>';
                            break;
                    }
                    
                    row += '</tr>';
                    $('#av-report-table-body').append(row);
                });
            } else {
                $('#av-report-table-body').append('<tr><td colspan="3">Nenhum dado disponível para o período selecionado</td></tr>');
            }
            
            // Inicializar o gráfico
            initChart(data);
        }
        
        // Função para atualizar estrutura da tabela com base no tipo
        function updateTableStructure(type) {
            let title = '';
            let headers = '';
            
            switch(type) {
                case 'visitantes':
                    title = 'Visitantes por Dia';
                    headers = '<tr><th>Data</th><th>Visitantes</th><th>Visualizações</th></tr>';
                    break;
                case 'paginas':
                    title = 'Páginas Mais Visitadas';
                    headers = '<tr><th>Título</th><th>URL</th><th>Visualizações</th></tr>';
                    break;
                case 'referencia':
                    title = 'Referências de Tráfego';
                    headers = '<tr><th>Domínio</th><th>URL</th><th>Visitas</th></tr>';
                    break;
                case 'localizacao':
                    title = 'Localização dos Visitantes';
                    headers = '<tr><th>País</th><th>Visitas</th><th>Porcentagem</th></tr>';
                    break;
                case 'dispositivos':
                    title = 'Dispositivos e Navegadores';
                    headers = '<tr><th>Dispositivo</th><th>Navegador</th><th>Visitas</th></tr>';
                    break;
            }
            
            $('#av-report-table-title').text(title);
            $('#av-report-table-head').html(headers);
        }
        
        // Função para inicializar o gráfico do relatório
        function initChart(data) {
            const ctx = document.getElementById('av-report-chart').getContext('2d');
            
            // Destruir gráfico existente, se houver
            if (window.reportChart) {
                window.reportChart.destroy();
            }
            
            // Configuração do gráfico com base no tipo
            let chartConfig = {
                type: 'line',
                data: {
                    labels: data.chart.labels,
                    datasets: [{
                        label: 'Visitantes',
                        data: data.chart.visitors,
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            };
            
            // Adicionar dataset de visualizações para tipo 'visitantes'
            if (data.type === 'visitantes') {
                chartConfig.data.datasets.push({
                    label: 'Visualizações',
                    data: data.chart.pageviews,
                    borderColor: '#72aee6',
                    backgroundColor: 'rgba(114, 174, 230, 0.1)',
                    tension: 0.3,
                    fill: true
                });
            }
            
            // Para outros tipos, ajustar o gráfico adequadamente
            if (data.type === 'paginas' || data.type === 'referencia' || 
                data.type === 'localizacao' || data.type === 'dispositivos') {
                
                chartConfig.type = 'bar';
                chartConfig.data.datasets[0].label = data.chart.datasetLabel || 'Contagem';
                chartConfig.data.datasets[0].backgroundColor = 'rgba(34, 113, 177, 0.7)';
                chartConfig.data.datasets[0].borderColor = '#2271b1';
                
                // Remover propriedades específicas de 'line'
                delete chartConfig.data.datasets[0].tension;
                delete chartConfig.data.datasets[0].fill;
            }
            
            // Renderizar o gráfico
            window.reportChart = new Chart(ctx, chartConfig);
        }
    });
</script> 