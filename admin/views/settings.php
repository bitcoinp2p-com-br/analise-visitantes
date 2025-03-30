<?php
/**
 * Template da página de configurações
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
?>

<div class="wrap analise-visitantes-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-settings"></span> 
        Configurações - Análise de Visitantes
    </h1>
    
    <div class="av-settings-container">
        <form method="post" action="options.php">
            <?php
            settings_fields('analise_visitantes_options');
            do_settings_sections('analise-visitantes-settings');
            ?>
            
            <div class="av-settings-section">
                <h2>Configurações de Dados</h2>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="av_retention_days">Dias para retenção de dados</label>
                        </th>
                        <td>
                            <input type="number" name="av_retention_days" id="av_retention_days" value="<?php echo esc_attr(get_option('av_retention_days', 90)); ?>" min="1" max="365" />
                            <p class="description">Período (em dias) para manter os dados de visitantes no banco de dados. Registros mais antigos serão removidos automaticamente.</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="av-settings-section">
                <h2>Configurações de Rastreamento</h2>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="av_geo_tracking">Rastreamento geográfico</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="av_geo_tracking" id="av_geo_tracking" value="1" <?php checked(1, get_option('av_geo_tracking', true)); ?> />
                                Ativar rastreamento geográfico para identificar a localização dos visitantes
                            </label>
                            <p class="description">Permite identificar país, cidade e coordenadas dos visitantes. Desativar esta opção para melhor performance.</p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">
                            <label for="av_rastrear_admin">Rastrear administradores</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="av_rastrear_admin" id="av_rastrear_admin" value="1" <?php checked(1, get_option('av_rastrear_admin', false)); ?> />
                                Incluir visitas de usuários administradores nas estatísticas
                            </label>
                            <p class="description">Quando desativado, as visitas de usuários administradores não são contabilizadas nas estatísticas.</p>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">
                            <label for="av_ignorar_bots">Ignorar bots</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="av_ignorar_bots" id="av_ignorar_bots" value="1" <?php checked(1, get_option('av_ignorar_bots', true)); ?> />
                                Ignorar bots e crawlers nas estatísticas
                            </label>
                            <p class="description">O plugin tenta identificar e ignorar bots, crawlers e spiders para estatísticas mais precisas.</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="av-settings-section">
                <h2>Banco de Dados</h2>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Tabelas do Plugin</th>
                        <td>
                            <p>O plugin utiliza as seguintes tabelas:</p>
                            <ul class="av-table-list">
                                <li><code><?php echo $wpdb->prefix; ?>analise_visitantes</code> - Registro de visitas</li>
                                <li><code><?php echo $wpdb->prefix; ?>analise_visitantes_online</code> - Visitantes online</li>
                                <li><code><?php echo $wpdb->prefix; ?>analise_visitantes_paises</code> - Estatísticas de países</li>
                                <li><code><?php echo $wpdb->prefix; ?>analise_visitantes_dispositivos</code> - Estatísticas de dispositivos</li>
                                <li><code><?php echo $wpdb->prefix; ?>analise_visitantes_agregado</code> - Dados agregados para relatórios</li>
                            </ul>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">Manutenção de Dados</th>
                        <td>
                            <p>
                                <a href="#" id="av-optimize-tables" class="button">Otimizar Tabelas</a>
                                <span id="av-optimize-result" class="av-action-result"></span>
                            </p>
                            <p class="description">Otimizar as tabelas do banco de dados para melhor performance.</p>
                            
                            <hr>
                            
                            <p>
                                <a href="#" id="av-clear-data" class="button button-secondary">Limpar Todos os Dados</a>
                                <span id="av-clear-result" class="av-action-result"></span>
                            </p>
                            <p class="description"><strong>Atenção:</strong> Esta ação irá remover <strong>TODOS</strong> os dados de visitantes e não pode ser desfeita!</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button('Salvar Configurações'); ?>
        </form>
    </div>
</div>

<style>
    /* Estilos específicos da página de configurações */
    .av-settings-container {
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-top: 20px;
        max-width: 900px;
    }
    
    .av-settings-section {
        margin-bottom: 30px;
        border-bottom: 1px solid #eee;
        padding-bottom: 20px;
    }
    
    .av-settings-section:last-child {
        border-bottom: none;
    }
    
    .av-settings-section h2 {
        margin-top: 0;
        margin-bottom: 20px;
        padding-bottom: 10px;
        font-size: 18px;
        color: #333;
    }
    
    .av-table-list {
        margin: 0;
        padding: 0;
        list-style: none;
    }
    
    .av-table-list li {
        margin-bottom: 5px;
        padding: 5px 0;
    }
    
    .av-table-list code {
        background: #f6f6f6;
        padding: 2px 5px;
    }
    
    .av-action-result {
        margin-left: 10px;
        padding: 3px 8px;
        border-radius: 3px;
        display: none;
    }
    
    .av-action-result.success {
        display: inline-block;
        background-color: #e7f7e6;
        color: #2e7d32;
        border: 1px solid #a3d9a5;
    }
    
    .av-action-result.error {
        display: inline-block;
        background-color: #ffebee;
        color: #c62828;
        border: 1px solid #ffb4ad;
    }
</style>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Otimizar tabelas
        $('#av-optimize-tables').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $result = $('#av-optimize-result');
            
            $button.prop('disabled', true);
            $button.text('Otimizando...');
            $result.removeClass('success error').hide();
            
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'av_otimizar_tabelas',
                    nonce: '<?php echo wp_create_nonce('av_otimizar_tabelas_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $result.addClass('success').text('Tabelas otimizadas com sucesso!').show();
                    } else {
                        $result.addClass('error').text('Erro: ' + response.data).show();
                    }
                    
                    $button.prop('disabled', false);
                    $button.text('Otimizar Tabelas');
                },
                error: function() {
                    $result.addClass('error').text('Erro na comunicação com o servidor').show();
                    $button.prop('disabled', false);
                    $button.text('Otimizar Tabelas');
                }
            });
        });
        
        // Limpar todos os dados
        $('#av-clear-data').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('ATENÇÃO: Todos os dados de visitantes serão permanentemente removidos. Esta ação não pode ser desfeita. Deseja continuar?')) {
                return;
            }
            
            var $button = $(this);
            var $result = $('#av-clear-result');
            
            $button.prop('disabled', true);
            $button.text('Limpando dados...');
            $result.removeClass('success error').hide();
            
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'av_limpar_dados',
                    nonce: '<?php echo wp_create_nonce('av_limpar_dados_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $result.addClass('success').text('Todos os dados foram removidos!').show();
                    } else {
                        $result.addClass('error').text('Erro: ' + response.data).show();
                    }
                    
                    $button.prop('disabled', false);
                    $button.text('Limpar Todos os Dados');
                },
                error: function() {
                    $result.addClass('error').text('Erro na comunicação com o servidor').show();
                    $button.prop('disabled', false);
                    $button.text('Limpar Todos os Dados');
                }
            });
        });
    });
</script> 