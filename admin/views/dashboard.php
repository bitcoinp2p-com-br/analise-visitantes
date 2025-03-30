<?php
/**
 * Template da página principal do dashboard
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Adicionar informações de depuração
$debug_info = array();
$debug_info[] = 'Template dashboard.php carregado com sucesso';

// Armazenar informações de debug para análise
if (WP_DEBUG) {
    error_log('Análise de Visitantes - Dashboard: '.implode(', ', $debug_info));
}

// Obter período selecionado
$days = isset($_GET['days']) ? intval($_GET['days']) : 30;

// Verificar se os scripts necessários foram carregados
global $wp_scripts;
$scripts_necessarios = array('chart-js', 'analise-visitantes-dashboard');

// Importante para depuração
if (WP_DEBUG) {
    foreach ($scripts_necessarios as $script) {
        if (!wp_script_is($script, 'enqueued') && !wp_script_is($script, 'done')) {
            error_log("Análise de Visitantes - Dashboard: Script {$script} não está carregado.");
        }
    }
}
?>

<div class="wrap analise-visitantes-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-chart-area"></span> 
        Dashboard - Análise de Visitantes
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
        
        <div class="av-refresh-button">
            <button id="av-refresh-stats" class="button button-secondary">
                <span class="dashicons dashicons-update"></span> Atualizar
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
    
    <div id="av-dashboard-content" class="av-dashboard-content">
        <!-- Cards de Resumo -->
        <div class="av-summary-cards">
            <div class="av-card av-card-visitors">
                <div class="av-card-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="av-card-content">
                    <div class="av-card-value" id="av-total-visitors">0</div>
                    <div class="av-card-label">Visitantes</div>
                </div>
            </div>
            
            <div class="av-card av-card-pageviews">
                <div class="av-card-icon">
                    <span class="dashicons dashicons-visibility"></span>
                </div>
                <div class="av-card-content">
                    <div class="av-card-value" id="av-total-pageviews">0</div>
                    <div class="av-card-label">Visualizações</div>
                </div>
            </div>
            
            <div class="av-card av-card-online">
                <div class="av-card-icon">
                    <span class="dashicons dashicons-admin-users"></span>
                </div>
                <div class="av-card-content">
                    <div class="av-card-value" id="av-online-now">0</div>
                    <div class="av-card-label">Online Agora</div>
                </div>
            </div>
        </div>
        
        <!-- Gráficos -->
        <div class="av-charts-row">
            <div class="av-chart-container">
                <h2 class="av-chart-title">Visitantes e Visualizações</h2>
                <div class="av-chart-wrapper">
                    <canvas id="av-visitors-chart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Tabelas de Dados -->
        <div class="av-data-tables">
            <div class="av-table-row">
                <div class="av-table-column">
                    <div class="av-data-table av-pages-table">
                        <h3>Páginas mais visitadas</h3>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th width="80">Visualizações</th>
                                </tr>
                            </thead>
                            <tbody id="av-top-pages">
                                <tr>
                                    <td colspan="2">Carregando dados...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="av-table-column">
                    <div class="av-data-table av-browsers-table">
                        <h3>Navegadores</h3>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th>Navegador</th>
                                    <th width="80">Visitas</th>
                                </tr>
                            </thead>
                            <tbody id="av-browsers">
                                <tr>
                                    <td colspan="2">Carregando dados...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="av-data-table av-devices-table">
                        <h3>Dispositivos</h3>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th width="80">Visitas</th>
                                </tr>
                            </thead>
                            <tbody id="av-devices">
                                <tr>
                                    <td colspan="2">Carregando dados...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="av-table-column">
                    <div class="av-data-table av-countries-table">
                        <h3>Países</h3>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th>País</th>
                                    <th width="80">Visitas</th>
                                </tr>
                            </thead>
                            <tbody id="av-countries">
                                <tr>
                                    <td colspan="2">Carregando dados...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Mudar período
        $('#av-period-select').on('change', function() {
            window.location.href = '<?php echo admin_url('admin.php?page=analise-visitantes'); ?>&days=' + $(this).val();
        });
        
        // Atualizar estatísticas
        $('#av-refresh-stats').on('click', function() {
            $('.av-loading-overlay').addClass('active');
            
            var data = {
                action: 'av_atualizar_dashboard',
                nonce: avDashboard.nonce,
                days: <?php echo $days; ?>
            };
            
            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    updateDashboard(response.data);
                }
                
                $('.av-loading-overlay').removeClass('active');
            });
        });
        
        // Inicializar o dashboard
        initDashboard();
    });
</script> 