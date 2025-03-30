<?php
/**
 * Template para análise em tempo real do plugin Análise de Visitantes
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap analise-visitantes-tempo-real">
    <h1>Análise em Tempo Real</h1>
    
    <div class="dashboard-row">
        <!-- Contadores principais -->
        <div class="dashboard-cell contador">
            <h2>Usuários Online</h2>
            <div class="big-number" id="usuarios-online"><?php echo $usuarios_online; ?></div>
            <div class="auto-update">Atualização automática a cada 15 segundos</div>
        </div>
        
        <div class="dashboard-cell contador">
            <h2>Visitas (24 horas)</h2>
            <div class="big-number"><?php echo $visitas_24h; ?></div>
            <div class="trend" id="trend-24h">
                <span class="trend-up" id="trend-up" style="display:none;"><span class="dashicons dashicons-arrow-up-alt"></span> +<span id="trend-up-value">0</span>%</span>
                <span class="trend-down" id="trend-down" style="display:none;"><span class="dashicons dashicons-arrow-down-alt"></span> -<span id="trend-down-value">0</span>%</span>
                <span class="trend-stable" id="trend-stable">Comparado com as 24h anteriores</span>
            </div>
        </div>
        
        <div class="dashboard-cell grafico">
            <h2>Atividade por Hora</h2>
            <canvas id="hourly-chart" width="100%" height="200"></canvas>
        </div>
    </div>
    
    <div class="dashboard-row">
        <!-- Links mais acessados (Pedido pelo usuário) -->
        <div class="dashboard-cell">
            <h2>Links Mais Acessados</h2>
            <div class="search-filter">
                <input type="text" id="search-pages" placeholder="Filtrar páginas..." class="regular-text">
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="60%">Página</th>
                        <th width="20%">Visualizações</th>
                        <th width="20%">% do Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paginas_populares)): ?>
                    <tr>
                        <td colspan="3">Nenhuma página visualizada nas últimas 24 horas.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($paginas_populares as $pagina): ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url($pagina->page_url); ?>" target="_blank">
                                    <?php echo esc_html($pagina->page_title); ?>
                                </a>
                                <div class="row-actions">
                                    <span class="view"><a href="<?php echo esc_url($pagina->page_url); ?>" target="_blank">Ver página</a> | </span>
                                    <span class="edit"><a href="<?php echo admin_url('admin.php?page=analise-visitantes-mapa&filter_page=' . urlencode($pagina->page_url)); ?>">Ver localização geográfica</a></span>
                                </div>
                            </td>
                            <td><?php echo $pagina->count; ?></td>
                            <td><?php echo round(($pagina->count / $visitas_24h) * 100, 1); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Referências que trouxeram visitantes (Pedido pelo usuário) -->
        <div class="dashboard-cell">
            <h2>Principais Fontes de Tráfego</h2>
            <div class="search-filter">
                <input type="text" id="search-refs" placeholder="Filtrar referências..." class="regular-text">
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="60%">Origem</th>
                        <th width="20%">Visitas</th>
                        <th width="20%">% do Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($principais_referrers)): ?>
                    <tr>
                        <td colspan="3">Nenhuma referência externa nas últimas 24 horas.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($principais_referrers as $ref): ?>
                        <tr>
                            <td>
                                <?php if ($ref->referrer): ?>
                                    <a href="<?php echo esc_url($ref->referrer); ?>" target="_blank">
                                        <?php 
                                        $domain = parse_url($ref->referrer, PHP_URL_HOST);
                                        echo $domain ? $domain : esc_url($ref->referrer); 
                                        ?>
                                    </a>
                                    <div class="row-actions">
                                        <span class="view"><a href="<?php echo esc_url($ref->referrer); ?>" target="_blank">Visitar site</a></span>
                                    </div>
                                <?php else: ?>
                                    Acesso Direto
                                <?php endif; ?>
                            </td>
                            <td><?php echo $ref->count; ?></td>
                            <td><?php echo round(($ref->count / $visitas_24h) * 100, 1); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="dashboard-row">
        <!-- Links internos mais clicados (caminhos de navegação) -->
        <div class="dashboard-cell">
            <h2>Rotas de Navegação Mais Comuns</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>De</th>
                        <th>Para</th>
                        <th width="15%">Ocorrências</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($links_internos)): ?>
                    <tr>
                        <td colspan="3">Nenhuma rota de navegação identificada nas últimas 24 horas.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($links_internos as $link): ?>
                        <tr>
                            <td>
                                <?php 
                                $origem_titulo = $wpdb->get_var($wpdb->prepare(
                                    "SELECT page_title FROM $table_name WHERE page_url = %s LIMIT 1",
                                    $link->origem
                                ));
                                echo esc_html($origem_titulo ? $origem_titulo : basename($link->origem)); 
                                ?>
                            </td>
                            <td>
                                <?php 
                                $destino_titulo = $wpdb->get_var($wpdb->prepare(
                                    "SELECT page_title FROM $table_name WHERE page_url = %s LIMIT 1",
                                    $link->destino
                                ));
                                echo esc_html($destino_titulo ? $destino_titulo : basename($link->destino)); 
                                ?>
                            </td>
                            <td><?php echo $link->count; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Páginas de saída -->
        <div class="dashboard-cell">
            <h2>Páginas de Saída</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Página</th>
                        <th width="20%">Saídas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paginas_saida)): ?>
                    <tr>
                        <td colspan="2">Nenhuma página de saída identificada nas últimas 24 horas.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($paginas_saida as $saida): ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url($saida->page_url); ?>" target="_blank">
                                    <?php echo esc_html($saida->page_title); ?>
                                </a>
                            </td>
                            <td><?php echo $saida->count; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="dashboard-row" id="realtime-container">
        <div class="dashboard-cell full-width">
            <h2>Atividade em Tempo Real <span class="live-indicator">LIVE</span></h2>
            <table class="wp-list-table widefat fixed striped" id="realtime-activity">
                <thead>
                    <tr>
                        <th width="20%">Hora</th>
                        <th width="30%">Página</th>
                        <th width="15%">Origem</th>
                        <th width="15%">Localização</th>
                        <th width="20%">Dispositivo / Navegador</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ultimas_visitas)): ?>
                    <tr>
                        <td colspan="5">Nenhuma atividade registrada nas últimas 24 horas.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($ultimas_visitas as $visita): ?>
                        <tr>
                            <td class="timestamp" data-time="<?php echo esc_attr($visita->date_time); ?>">
                                <?php 
                                $time_diff = human_time_diff(strtotime($visita->date_time), current_time('timestamp'));
                                echo $time_diff . ' atrás';
                                ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url($visita->page_url); ?>" target="_blank">
                                    <?php echo esc_html($visita->page_title); ?>
                                </a>
                            </td>
                            <td>
                                <?php 
                                if ($visita->referrer) {
                                    $domain = parse_url($visita->referrer, PHP_URL_HOST);
                                    echo $domain ? $domain : 'Link externo';
                                } else {
                                    echo 'Acesso Direto';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($visita->country && $visita->country != 'Desconhecido') {
                                    echo esc_html($visita->country);
                                    if ($visita->city && $visita->city != 'Desconhecido') {
                                        echo ' / ' . esc_html($visita->city);
                                    }
                                } else {
                                    echo 'Localização indisponível';
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo esc_html($visita->device_type); ?> / <?php echo esc_html($visita->browser); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Configuração do gráfico de atividade por hora
    var ctx = document.getElementById('hourly-chart').getContext('2d');
    
    // Preparar dados para o gráfico
    var hoursLabels = [];
    var hoursData = [];
    
    <?php
    // Preencher array de 0 a 23 horas
    $horas_dados = array_fill(0, 24, 0);
    
    // Adicionar dados existentes
    foreach ($visitas_por_hora as $visita) {
        $horas_dados[$visita->hora] = $visita->count;
    }
    ?>
    
    // Criar arrays para o gráfico
    <?php for ($i = 0; $i < 24; $i++): ?>
        hoursLabels.push('<?php echo sprintf('%02d:00', $i); ?>');
        hoursData.push(<?php echo $horas_dados[$i]; ?>);
    <?php endfor; ?>
    
    var hourlyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: hoursLabels,
            datasets: [{
                label: 'Visitas por Hora',
                data: hoursData,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
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
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Função para atualizar os timestamps relativos
    function atualizarTimestamps() {
        $('.timestamp').each(function() {
            var timestamp = $(this).data('time');
            var momentTime = moment(timestamp);
            var now = moment();
            var diff = now.diff(momentTime, 'minutes');
            
            if (diff < 60) {
                $(this).text(diff + ' minutos atrás');
            } else {
                $(this).text(moment(timestamp).fromNow());
            }
        });
    }
    
    // Atualizar timestamps a cada minuto
    setInterval(atualizarTimestamps, 60000);
    
    // Filtro para tabelas
    $('#search-pages').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $(this).closest('.dashboard-cell').find('tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    
    $('#search-refs').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $(this).closest('.dashboard-cell').find('tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    
    // Atualizar dados em tempo real
    function atualizarDadosTempoReal() {
        $.ajax({
            url: analiseVisitantesData.ajaxurl,
            type: 'POST',
            data: {
                action: 'atualizar_tempo_real',
                nonce: analiseVisitantesData.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Atualizar usuários online
                    $('#usuarios-online').text(response.data.usuarios_online);
                    
                    // Atualizar tendência (comparação com 24h anteriores)
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
                    
                    // Adicionar novas atividades em tempo real
                    if (response.data.novas_visitas && response.data.novas_visitas.length > 0) {
                        // Adicionar novas linhas no início da tabela
                        var tbody = $('#realtime-activity tbody');
                        
                        $.each(response.data.novas_visitas, function(index, visita) {
                            var newRow = '<tr class="new-activity">' +
                                '<td class="timestamp" data-time="' + visita.date_time + '">' + visita.time_ago + '</td>' +
                                '<td><a href="' + visita.page_url + '" target="_blank">' + visita.page_title + '</a></td>' +
                                '<td>' + visita.referrer + '</td>' +
                                '<td>' + visita.location + '</td>' +
                                '<td>' + visita.device + ' / ' + visita.browser + '</td>' +
                                '</tr>';
                                
                            tbody.prepend(newRow);
                            
                            // Remover a classe após a animação
                            setTimeout(function() {
                                tbody.find('tr.new-activity').first().removeClass('new-activity');
                            }, 3000);
                            
                            // Limitar a quantidade de linhas (manter apenas as 30 mais recentes)
                            if (tbody.find('tr').length > 30) {
                                tbody.find('tr').last().remove();
                            }
                        });
                    }
                }
            }
        });
    }
    
    // Executar a atualização a cada 15 segundos
    setInterval(atualizarDadosTempoReal, 15000);
});
</script> 