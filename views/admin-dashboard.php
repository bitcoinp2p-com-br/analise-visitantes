<?php
/**
 * Template para o painel de administração do plugin Análise de Visitantes
 * Modelado no estilo do Statify para clareza e facilidade de uso
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap analise-visitantes-dashboard statify-style">
    <h1>Análise de Visitantes</h1>
    
    <div class="nav-tab-wrapper">
        <a href="#visitas" class="nav-tab nav-tab-active"><?php _e('Visualizações', 'analise-visitantes'); ?></a>
        <a href="#referrers" class="nav-tab"><?php _e('Referências', 'analise-visitantes'); ?></a>
        <a href="#targets" class="nav-tab"><?php _e('Destinos', 'analise-visitantes'); ?></a>
    </div>
    
    <div class="dashboard-container">
        <!-- Filtro de período -->
        <div class="period-selection">
            <label for="period-selector"><?php _e('Período:', 'analise-visitantes'); ?></label>
            <select id="period-selector" name="period">
                <option value="today" <?php selected(isset($_GET['period']) ? $_GET['period'] : 'today', 'today'); ?>><?php _e('Hoje', 'analise-visitantes'); ?></option>
                <option value="yesterday" <?php selected(isset($_GET['period']) ? $_GET['period'] : '', 'yesterday'); ?>><?php _e('Ontem', 'analise-visitantes'); ?></option>
                <option value="week" <?php selected(isset($_GET['period']) ? $_GET['period'] : '', 'week'); ?>><?php _e('Esta semana', 'analise-visitantes'); ?></option>
                <option value="month" <?php selected(isset($_GET['period']) ? $_GET['period'] : '', 'month'); ?>><?php _e('Este mês', 'analise-visitantes'); ?></option>
                <option value="last30" <?php selected(isset($_GET['period']) ? $_GET['period'] : '', 'last30'); ?>><?php _e('Últimos 30 dias', 'analise-visitantes'); ?></option>
            </select>
            
            <label for="limit-selector"><?php _e('Mostrar:', 'analise-visitantes'); ?></label>
            <select id="limit-selector" name="limit">
                <option value="10" <?php selected(isset($_GET['limit']) ? $_GET['limit'] : '10', '10'); ?>>10</option>
                <option value="20" <?php selected(isset($_GET['limit']) ? $_GET['limit'] : '', '20'); ?>>20</option>
                <option value="30" <?php selected(isset($_GET['limit']) ? $_GET['limit'] : '', '30'); ?>>30</option>
                <option value="50" <?php selected(isset($_GET['limit']) ? $_GET['limit'] : '', '50'); ?>>50</option>
            </select>
            
            <button id="filter-button" class="button button-primary"><?php _e('Filtrar', 'analise-visitantes'); ?></button>
        </div>
        
        <!-- Contagem total de visualizações -->
        <div class="total-views-container">
            <div class="big-number"><?php echo $total_visitas; ?></div>
            <div class="description"><?php _e('Visualizações de página', 'analise-visitantes'); ?></div>
        </div>
        
        <!-- Gráfico interativo -->
        <div class="chart-container tab-panel active" id="tab-visitas">
            <canvas id="visitas-chart" width="100%" height="300"></canvas>
            <script>
            var ctx = document.getElementById('visitas-chart').getContext('2d');
            var chartData = {
                labels: [<?php 
                    $labels = array();
                    foreach ($visitas_por_dia as $visita) {
                        $labels[] = "'" . date('d/m', strtotime($visita->dia)) . "'";
                    }
                    echo implode(', ', $labels);
                ?>],
                datasets: [{
                    label: 'Visualizações',
                    data: [<?php 
                        $counts = array();
                        foreach ($visitas_por_dia as $visita) {
                            $counts[] = $visita->count;
                        }
                        echo implode(', ', $counts);
                    ?>],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            };
            
            var myChart = new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        },
                        legend: {
                            display: false
                        }
                    },
                    hover: {
                        mode: 'nearest',
                        intersect: true
                    }
                }
            });
            </script>
        </div>
        
        <!-- Tabelas de dados -->
        <div class="data-tables-container">
            <!-- Referências -->
            <div class="data-table tab-panel" id="tab-referrers">
                <h3><?php _e('Principais Referências', 'analise-visitantes'); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Referência', 'analise-visitantes'); ?></th>
                            <th width="100"><?php _e('Visualizações', 'analise-visitantes'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($origens)): ?>
                        <tr>
                            <td colspan="2"><?php _e('Nenhuma referência encontrada.', 'analise-visitantes'); ?></td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($origens as $origem): ?>
                            <tr>
                                <td>
                                    <?php if ($origem->referrer): ?>
                                        <a href="<?php echo esc_url($origem->referrer); ?>" target="_blank">
                                            <?php echo esc_url($origem->referrer); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php _e('Acesso Direto', 'analise-visitantes'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $origem->count; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Páginas de destino -->
            <div class="data-table tab-panel" id="tab-targets">
                <h3><?php _e('Principais Páginas', 'analise-visitantes'); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Página', 'analise-visitantes'); ?></th>
                            <th width="100"><?php _e('Visualizações', 'analise-visitantes'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($paginas_populares)): ?>
                        <tr>
                            <td colspan="2"><?php _e('Nenhuma página encontrada.', 'analise-visitantes'); ?></td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($paginas_populares as $pagina): ?>
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
    
    <div class="statify-info">
        <p><?php _e('O plugin Análise de Visitantes rastreia visualizações de página sem usar cookies ou terceiros.', 'analise-visitantes'); ?></p>
        <p><?php _e('Os dados são armazenados anonimamente e ficam disponíveis por 30 dias.', 'analise-visitantes'); ?></p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Navegação por abas
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        
        // Remover classe ativa de todas as abas e painéis
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-panel').removeClass('active');
        
        // Adicionar classe ativa na aba clicada
        $(this).addClass('nav-tab-active');
        
        // Mostrar o painel correspondente
        var target = $(this).attr('href').substring(1);
        $('#tab-' + target).addClass('active');
    });
    
    // Inicializar - mostrar apenas o primeiro painel
    $('.tab-panel').not(':first').hide();
    
    // Redirecionar para a página com parâmetros de filtro
    $('#filter-button').on('click', function(e) {
        e.preventDefault();
        
        var period = $('#period-selector').val();
        var limit = $('#limit-selector').val();
        
        window.location.href = '<?php echo admin_url('admin.php?page=analise-visitantes'); ?>&period=' + period + '&limit=' + limit;
    });
    
    // Atualizar dados quando mudar o período (para AJAX)
    $('#period-selector, #limit-selector').on('change', function() {
        // Não precisamos mais deste código pois estamos usando redirecionamento
        // Mantido como referência para implementação futura com AJAX
    });
    
    function updateTable(selector, data) {
        var $tbody = $(selector);
        $tbody.empty();
        
        if (data.length === 0) {
            $tbody.append('<tr><td colspan="2">Nenhum resultado encontrado.</td></tr>');
            return;
        }
        
        $.each(data, function(index, item) {
            var $row = $('<tr></tr>');
            
            // Adicionar células específicas com base no tipo de tabela
            if (selector.indexOf('referrers') > -1) {
                var url = item.referrer ? item.referrer : 'Acesso Direto';
                var link = item.referrer ? '<a href="' + item.referrer + '" target="_blank">' + item.referrer + '</a>' : 'Acesso Direto';
                
                $row.append('<td>' + link + '</td>');
                $row.append('<td>' + item.count + '</td>');
            } else {
                $row.append('<td><a href="' + item.page_url + '" target="_blank">' + item.page_title + '</a></td>');
                $row.append('<td>' + item.count + '</td>');
            }
            
            $tbody.append($row);
        });
    }
});
</script>