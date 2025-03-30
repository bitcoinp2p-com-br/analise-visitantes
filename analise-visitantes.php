<?php
/**
 * Plugin Name: Análise de Visitantes
 * Plugin URI: https://github.com/bitcoinp2p-com-br/analise-visitantes
 * Description: Plugin para análise de visitantes do site com estatísticas em tempo real, mapas geográficos e relatórios.
 * Version: 1.0.0
 * Author: BitcoinP2P
 * Author URI: https://bitcoinp2p.com.br
 * Text Domain: analise-visitantes
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes
define('ANALISE_VISITANTES_VERSION', '1.0.0');
define('ANALISE_VISITANTES_FILE', __FILE__);
define('ANALISE_VISITANTES_PATH', plugin_dir_path(__FILE__));
define('ANALISE_VISITANTES_URL', plugin_dir_url(__FILE__));

/**
 * Função para verificar e criar tabelas do banco de dados
 */
function analise_visitantes_criar_tabelas() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Tabela principal de visitas
    $table_name = $wpdb->prefix . 'analise_visitantes';
    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        visitor_id varchar(64) NOT NULL,
        page_id bigint(20) NOT NULL DEFAULT 0,
        page_title varchar(255) DEFAULT '',
        page_url varchar(255) NOT NULL,
        referrer varchar(255) DEFAULT '',
        ip varchar(45) DEFAULT '',
        user_agent text DEFAULT NULL,
        date_time datetime NOT NULL,
        country varchar(2) DEFAULT '',
        city varchar(50) DEFAULT '',
        latitude decimal(10,8) DEFAULT 0,
        longitude decimal(11,8) DEFAULT 0,
        device_type varchar(20) DEFAULT '',
        browser varchar(50) DEFAULT '',
        operating_system varchar(50) DEFAULT '',
        PRIMARY KEY  (id),
        KEY date_time (date_time),
        KEY visitor_id (visitor_id),
        KEY page_url (page_url(191))
    ) $charset_collate;";
    
    // Tabela para visitantes online
    $table_online = $wpdb->prefix . 'analise_visitantes_online';
    $sql_online = "CREATE TABLE $table_online (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        session_id varchar(64) NOT NULL,
        ip varchar(45) DEFAULT '',
        page_url varchar(255) NOT NULL,
        last_activity datetime NOT NULL,
        user_agent text,
        PRIMARY KEY  (id),
        UNIQUE KEY session_id (session_id)
    ) $charset_collate;";
    
    // Tabela para estatísticas por países
    $table_country = $wpdb->prefix . 'analise_visitantes_paises';
    $sql_country = "CREATE TABLE $table_country (
        id int(11) NOT NULL AUTO_INCREMENT,
        country_code varchar(2) NOT NULL,
        country_name varchar(50) NOT NULL,
        visits int(11) NOT NULL DEFAULT 0,
        last_visit datetime,
        PRIMARY KEY  (id),
        UNIQUE KEY country_code (country_code)
    ) $charset_collate;";
    
    // Tabela para estatísticas de dispositivos
    $table_device = $wpdb->prefix . 'analise_visitantes_dispositivos';
    $sql_device = "CREATE TABLE $table_device (
        id int(11) NOT NULL AUTO_INCREMENT,
        device_type varchar(20) NOT NULL,
        browser varchar(50) NOT NULL,
        operating_system varchar(50) NOT NULL,
        visits int(11) NOT NULL DEFAULT 0,
        last_visit datetime,
        PRIMARY KEY  (id),
        UNIQUE KEY device_signature (device_type, browser(50), operating_system(50))
    ) $charset_collate;";
    
    // Tabela para estatísticas diárias
    $table_stats = $wpdb->prefix . 'analise_visitantes_stats';
    $sql_stats = "CREATE TABLE $table_stats (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        data date NOT NULL,
        visitas int(11) DEFAULT 0 NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY data (data)
    ) $charset_collate;";
    
    // Tabela de agregação para relatórios rápidos
    $table_agregado = $wpdb->prefix . 'analise_visitantes_agregado';
    $sql_agregado = "CREATE TABLE $table_agregado (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        data date NOT NULL,
        page_url varchar(255) NOT NULL,
        visualizacoes int(11) NOT NULL DEFAULT 0,
        dispositivos_json longtext,
        PRIMARY KEY (id),
        UNIQUE KEY idx_data_url (data, page_url(191))
    ) $charset_collate;";
    
    // Carregar o arquivo necessário para dbDelta
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Executar dbDelta para cada tabela
    $result_main = dbDelta($sql);
    $result_online = dbDelta($sql_online);
    $result_country = dbDelta($sql_country);
    $result_device = dbDelta($sql_device);
    $result_stats = dbDelta($sql_stats);
    $result_agregado = dbDelta($sql_agregado);
    
    // Verificar se as tabelas necessárias existem após a criação
    $tabelas_ok = true;
    
    // Lista de tabelas a verificar
    $tabelas = array(
        $table_name,
        $table_online,
        $table_country,
        $table_device,
        $table_stats,
        $table_agregado
    );
    
    foreach ($tabelas as $tabela) {
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabela'") != $tabela) {
            $tabelas_ok = false;
            break;
        }
    }
    
    return $tabelas_ok;
}

/**
 * Função para verificar se tabelas possuem colunas corretas
 */
function analise_visitantes_verificar_colunas() {
    global $wpdb;
        $table_name = $wpdb->prefix . 'analise_visitantes';
        
    // Verificar se a coluna 'country' existe na tabela principal
    $country_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'country'");
    
    if (!$country_exists) {
        // Tentar adicionar as colunas faltantes
        $wpdb->query("ALTER TABLE $table_name 
            ADD COLUMN `country` varchar(2) DEFAULT '',
            ADD COLUMN `city` varchar(50) DEFAULT '',
            ADD COLUMN `latitude` decimal(10,8) DEFAULT 0,
            ADD COLUMN `longitude` decimal(11,8) DEFAULT 0");
        
        // Verificar novamente após tentativa de adição
        $country_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'country'");
    }
    
    return $country_exists ? true : false;
}

/**
 * Função de ativação do plugin
 */
function analise_visitantes_activate() {
    // Criar tabelas
    $tabelas_ok = analise_visitantes_criar_tabelas();
    
    // Verificar colunas
    $colunas_ok = analise_visitantes_verificar_colunas();
    
    // Salvar status para verificação posterior
    update_option('analise_visitantes_tabelas_ok', $tabelas_ok);
    update_option('analise_visitantes_colunas_ok', $colunas_ok);
    update_option('analise_visitantes_ativado', time());
    
    // Inicializar contador se não existir
    if (!get_option('av_visitas_hoje')) {
        update_option('av_visitas_hoje', 0);
        update_option('av_ultimo_reset', date('Y-m-d H:i:s'));
    }
    
    // Configurar opções padrão
    $default_options = array(
        'retencao_dados' => 30, // dias
        'rastrear_admin' => false,
        'ignorar_bots' => true,
        'geo_tracking' => true
    );
    
    foreach ($default_options as $option => $value) {
        if (get_option('av_' . $option) === false) {
            update_option('av_' . $option, $value);
        }
    }
}

/**
 * Função de desativação do plugin
 */
function analise_visitantes_deactivate() {
    // Remover tarefas agendadas
    wp_clear_scheduled_hook('limpar_dados_antigos');
    wp_clear_scheduled_hook('reset_visitantes_diarios');
}

/**
 * Função para verificar se o plugin pode ser inicializado
 */
function analise_visitantes_pode_inicializar() {
    $tabelas_ok = get_option('analise_visitantes_tabelas_ok', false);
    $colunas_ok = get_option('analise_visitantes_colunas_ok', false);
    
    return $tabelas_ok && $colunas_ok;
}

/**
 * Função para adicionar aviso de erro no admin
 */
function analise_visitantes_admin_erro() {
    $tabelas_ok = get_option('analise_visitantes_tabelas_ok', false);
    $colunas_ok = get_option('analise_visitantes_colunas_ok', false);
    
    $class = 'notice notice-error';
    if (!$tabelas_ok) {
        $message = 'Erro ao ativar o plugin Análise de Visitantes: Não foi possível criar as tabelas no banco de dados.';
    } elseif (!$colunas_ok) {
        $message = 'Erro ao ativar o plugin Análise de Visitantes: Colunas necessárias não puderam ser adicionadas ao banco de dados.';
    } else {
        $message = 'Erro ao ativar o plugin Análise de Visitantes. Verifique os logs para mais informações.';
    }
    
    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}

/**
 * Função para adicionar menu no admin (modo de segurança)
 */
function analise_visitantes_adicionar_menu_seguranca() {
        add_menu_page(
            'Análise de Visitantes',
            'Análise de Visitantes',
            'manage_options',
        'analise-visitantes-seguranca', 
        'analise_visitantes_pagina_seguranca',
        'dashicons-chart-area',
        30
    );
}

/**
 * Função para exibir página de segurança
 */
function analise_visitantes_pagina_seguranca() {
    ?>
    <div class="wrap">
        <h1>Análise de Visitantes - Modo de Segurança</h1>
        <div class="notice notice-warning">
            <p>O plugin está em modo de segurança devido a problemas na estrutura do banco de dados.</p>
        </div>
        
        <div style="background: white; padding: 15px; margin-top: 20px; border-radius: 5px; border: 1px solid #ccc;">
            <h2>Diagnóstico</h2>
            <?php
            global $wpdb;
        $table_name = $wpdb->prefix . 'analise_visitantes';
            
            echo '<p>Verificando tabela principal: ';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                echo '<span style="color:green;">✓ OK</span>';
            } else {
                echo '<span style="color:red;">✗ Não encontrada</span>';
            }
            echo '</p>';
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                echo '<p>Verificando colunas necessárias:</p>';
                echo '<ul>';
                
                $colunas = array('country', 'city', 'latitude', 'longitude');
                foreach ($colunas as $coluna) {
                    echo '<li>' . $coluna . ': ';
                    if ($wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE '$coluna'")) {
                        echo '<span style="color:green;">✓ OK</span>';
                    } else {
                        echo '<span style="color:red;">✗ Não encontrada</span>';
                    }
                    echo '</li>';
                }
                
                echo '</ul>';
            }
            ?>
            
            <h2>Ações</h2>
            <form method="post" action="">
                <?php wp_nonce_field('analise_visitantes_reparar', 'nonce_reparar'); ?>
                <p>
                    <button type="submit" name="reparar_tabelas" class="button button-primary">Tentar Reparar Tabelas</button>
                </p>
            </form>
            
            <?php
            // Processar tentativa de reparo
            if (isset($_POST['reparar_tabelas']) && isset($_POST['nonce_reparar']) && wp_verify_nonce($_POST['nonce_reparar'], 'analise_visitantes_reparar')) {
                $tabelas_ok = analise_visitantes_criar_tabelas();
                $colunas_ok = analise_visitantes_verificar_colunas();
                
                update_option('analise_visitantes_tabelas_ok', $tabelas_ok);
                update_option('analise_visitantes_colunas_ok', $colunas_ok);
                
                if ($tabelas_ok && $colunas_ok) {
                    echo '<div class="notice notice-success"><p>Reparo concluído com sucesso! <a href="' . admin_url('plugins.php') . '">Retorne à página de plugins</a> para desativar e reativar o plugin.</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Não foi possível reparar todas as tabelas ou colunas. Por favor, entre em contato com o suporte.</p></div>';
                }
            }
            ?>
        </div>
    </div>
    <?php
}

// Registrar hooks de ativação e desativação
register_activation_hook(__FILE__, 'analise_visitantes_activate');
register_deactivation_hook(__FILE__, 'analise_visitantes_deactivate');

// Verificar condições para inicializar o plugin completo ou apenas o modo de segurança
add_action('plugins_loaded', 'analise_visitantes_inicializacao');

/**
 * Função principal de inicialização do plugin
 */
function analise_visitantes_inicializacao() {
    if (analise_visitantes_pode_inicializar()) {
        // O banco de dados está OK, inicializar o plugin normalmente
        analise_visitantes_init();
    } else {
        // Algo está errado com o banco de dados, inicializar modo de segurança
        if (is_admin()) {
            add_action('admin_notices', 'analise_visitantes_admin_erro');
            add_action('admin_menu', 'analise_visitantes_adicionar_menu_seguranca');
        }
    }
}

/**
 * Inicializar o plugin
 */
function analise_visitantes_init() {
    // Ativar o modo de depuração no log se necessário
    if (!defined('WP_DEBUG_LOG')) {
        define('WP_DEBUG_LOG', true);
    }
    
    // Verificar estrutura de arquivos
    $arquivos_necessarios = array(
        'includes/class-core.php',
        'includes/class-tracking.php',
        'includes/class-reports.php',
        'includes/class-geolocation.php',
        'includes/class-devices.php',
        'includes/class-realtime.php',
        'admin/class-admin.php',
        'admin/class-settings.php',
        'admin/class-dashboard.php',
        'public/class-public.php',
        'admin/views/dashboard.php',
        'admin/views/realtime.php',
        'admin/views/reports.php',
        'admin/views/map.php',
        'admin/views/settings.php',
    );
    
    $arquivos_faltando = array();
    
    foreach ($arquivos_necessarios as $arquivo) {
        $caminho_completo = ANALISE_VISITANTES_PATH . $arquivo;
        if (!file_exists($caminho_completo)) {
            $arquivos_faltando[] = $arquivo;
            error_log("Análise de Visitantes: Arquivo necessário não encontrado: {$arquivo}");
        }
    }
    
    if (!empty($arquivos_faltando)) {
        error_log("Análise de Visitantes: Arquivos faltando: " . implode(', ', $arquivos_faltando));
        update_option('analise_visitantes_arquivos_faltando', $arquivos_faltando);
    } else {
        delete_option('analise_visitantes_arquivos_faltando');
    }
    
    // Carregar arquivos principais
    require_once ANALISE_VISITANTES_PATH . 'includes/class-core.php';
    require_once ANALISE_VISITANTES_PATH . 'includes/class-tracking.php';
    require_once ANALISE_VISITANTES_PATH . 'includes/class-reports.php';
    require_once ANALISE_VISITANTES_PATH . 'includes/class-geolocation.php';
    require_once ANALISE_VISITANTES_PATH . 'includes/class-devices.php';
    require_once ANALISE_VISITANTES_PATH . 'includes/class-realtime.php';
    require_once ANALISE_VISITANTES_PATH . 'admin/class-admin.php';
    require_once ANALISE_VISITANTES_PATH . 'admin/class-settings.php';
    require_once ANALISE_VISITANTES_PATH . 'admin/class-dashboard.php';
    require_once ANALISE_VISITANTES_PATH . 'public/class-public.php';

    // Inicializar classes principais
    $core = Analise_Visitantes_Core::get_instance();
    
    // Inicializar Tracking apenas no front-end
    if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
        $tracking = Analise_Visitantes_Tracking::get_instance();
    }
    
    // Inicializar componentes administrativos
    if (is_admin()) {
        $admin = Analise_Visitantes_Admin::get_instance();
        $dashboard = Analise_Visitantes_Dashboard::get_instance();
        $reports = Analise_Visitantes_Reports::get_instance();
        
        // Adicionar aviso de arquivos faltando
        if (get_option('analise_visitantes_arquivos_faltando')) {
            add_action('admin_notices', 'analise_visitantes_arquivos_faltando_notice');
        }
    }
    
    // Componentes públicos
    $public = Analise_Visitantes_Public::get_instance();
    
    // Inicializar outros componentes que podem ser usados em qualquer contexto
    $devices = Analise_Visitantes_Devices::get_instance();
    $realtime = Analise_Visitantes_Realtime::get_instance();
    $geo = Analise_Visitantes_Geolocation::get_instance();
}

/**
 * Exibe um aviso sobre arquivos faltando
 */
function analise_visitantes_arquivos_faltando_notice() {
    $arquivos_faltando = get_option('analise_visitantes_arquivos_faltando', array());
    
    if (!empty($arquivos_faltando)) {
        echo '<div class="notice notice-error">';
        echo '<p><strong>Análise de Visitantes:</strong> Alguns arquivos necessários não foram encontrados:</p>';
        echo '<ul>';
        foreach ($arquivos_faltando as $arquivo) {
            echo '<li>' . esc_html($arquivo) . '</li>';
        }
        echo '</ul>';
        echo '<p>Isso pode causar problemas de funcionamento. Por favor, reinstale o plugin.</p>';
        echo '</div>';
    }
}