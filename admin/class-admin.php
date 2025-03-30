<?php
/**
 * Classe de Administração do Plugin Análise de Visitantes
 * 
 * Responsável pela administração geral do plugin
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class Analise_Visitantes_Admin {
    
    /**
     * Instância única da classe (Singleton)
     * @var Analise_Visitantes_Admin
     */
    private static $instance = null;
    
    /**
     * Obtém a instância única da classe
     * 
     * @return Analise_Visitantes_Admin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Construtor - inicializa a classe
     */
    private function __construct() {
        $this->setup_actions();
    }
    
    /**
     * Configurar ações e filtros
     */
    private function setup_actions() {
        // Adicionar recursos ao admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Adicionar widget ao dashboard
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        
        // Adicionar links de ação ao plugin
        add_filter('plugin_action_links_' . plugin_basename(ANALISE_VISITANTES_FILE), array($this, 'add_plugin_action_links'));
    }
    
    /**
     * Adicionar menu no admin
     */
    public function add_admin_menu() {
        // Menu principal
        add_menu_page(
            'Análise de Visitantes',
            'Análise de Visitantes',
            'manage_options',
            'analise-visitantes',
            array($this, 'render_dashboard_page'),
            'dashicons-chart-area',
            30
        );
        
        // Não é necessário adicionar o submenu Dashboard, pois o WordPress já cria
        // automaticamente um submenu com o mesmo nome do menu principal
        // Remova esta parte para evitar duplicação
        
        // Submenus
        add_submenu_page(
            'analise-visitantes',
            'Tempo Real',
            'Tempo Real',
            'manage_options',
            'analise-visitantes-realtime',
            array($this, 'render_realtime_page')
        );
        
        add_submenu_page(
            'analise-visitantes',
            'Relatórios',
            'Relatórios',
            'manage_options',
            'analise-visitantes-reports',
            array($this, 'render_reports_page')
        );
        
        add_submenu_page(
            'analise-visitantes',
            'Mapa de Visitantes',
            'Mapa',
            'manage_options',
            'analise-visitantes-map',
            array($this, 'render_map_page')
        );
        
        add_submenu_page(
            'analise-visitantes',
            'Configurações',
            'Configurações',
            'manage_options',
            'analise-visitantes-settings',
            array($this, 'render_settings_page')
        );
        
        // Adicionar temporariamente um submenu de diagnóstico
        add_submenu_page(
            'analise-visitantes',
            'Diagnóstico',
            'Diagnóstico',
            'manage_options',
            'analise-visitantes-diagnostico',
            array($this, 'verificar_permissoes_arquivos')
        );
    }
    
    /**
     * Incluir recursos CSS e JS no admin
     */
    public function enqueue_admin_assets($hook) {
        // Verificar se estamos em uma página do plugin
        if (strpos($hook, 'analise-visitantes') === false) {
            return;
        }
        
        // Incluir CSS admin
        wp_enqueue_style(
            'analise-visitantes-admin-style',
            ANALISE_VISITANTES_URL . 'assets/css/admin.css',
            array(),
            ANALISE_VISITANTES_VERSION
        );
        
        // Incluir scripts baseados na página atual
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
            
            // Scripts para o dashboard
            if ($page === 'analise-visitantes') {
                wp_enqueue_script(
                    'chart-js',
                    'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
                    array(),
                    '3.9.1',
                    true
                );
                
                wp_enqueue_script(
                    'analise-visitantes-dashboard',
                    ANALISE_VISITANTES_URL . 'assets/js/dashboard.js',
                    array('jquery', 'chart-js'),
                    ANALISE_VISITANTES_VERSION,
                    true
                );
                
                // Dados para o script
                wp_localize_script('analise-visitantes-dashboard', 'avDashboard', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('av_dashboard_nonce'),
                    'labels' => array(
                        'visitors' => 'Visitantes',
                        'pageviews' => 'Visualizações',
                        'months' => array(
                            'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun',
                            'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'
                        )
                    ),
                    'stats' => $this->get_dashboard_data()
                ));
            }
            
            // Scripts para tempo real
            else if ($page === 'analise-visitantes-realtime') {
                wp_enqueue_script(
                    'analise-visitantes-realtime',
                    ANALISE_VISITANTES_URL . 'assets/js/realtime.js',
                    array('jquery'),
                    ANALISE_VISITANTES_VERSION,
                    true
                );
                
                // Dados para o script
                wp_localize_script('analise-visitantes-realtime', 'avRealtime', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('av_realtime_nonce'),
                    'refreshInterval' => 10000 // 10 segundos
                ));
            }
            
            // Scripts para mapa
            else if ($page === 'analise-visitantes-map') {
                wp_enqueue_style(
                    'leaflet-css',
                    'https://unpkg.com/leaflet@1.9.3/dist/leaflet.css',
                    array(),
                    '1.9.3'
                );
                
                wp_enqueue_script(
                    'leaflet-js',
                    'https://unpkg.com/leaflet@1.9.3/dist/leaflet.js',
                    array(),
                    '1.9.3',
                    true
                );
                
                wp_enqueue_script(
                    'analise-visitantes-map',
                    ANALISE_VISITANTES_URL . 'assets/js/map.js',
                    array('jquery', 'leaflet-js'),
                    ANALISE_VISITANTES_VERSION,
                    true
                );
                
                // Dados para o script
                wp_localize_script('analise-visitantes-map', 'avMap', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('av_map_nonce'),
                    'data' => $this->get_map_data()
                ));
            }
        }
    }
    
    /**
     * Adicionar widget ao dashboard do WordPress
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'analise_visitantes_widget',
            'Análise de Visitantes - Resumo',
            array($this, 'render_dashboard_widget')
        );
    }
    
    /**
     * Renderizar widget do dashboard
     */
    public function render_dashboard_widget() {
        $data = $this->get_dashboard_summary();
        
        ?>
        <div class="av-dashboard-widget">
            <div class="av-summary">
                <div class="av-stat">
                    <span class="av-count"><?php echo number_format_i18n($data['hoje']); ?></span>
                    <span class="av-label">Visitas Hoje</span>
                </div>
                <div class="av-stat">
                    <span class="av-count"><?php echo number_format_i18n($data['ontem']); ?></span>
                    <span class="av-label">Visitas Ontem</span>
                </div>
                <div class="av-stat">
                    <span class="av-count"><?php echo number_format_i18n($data['online']); ?></span>
                    <span class="av-label">Online Agora</span>
                </div>
            </div>
            <div class="av-links">
                <a href="<?php echo admin_url('admin.php?page=analise-visitantes'); ?>" class="button button-primary">Ver Dashboard</a>
                <a href="<?php echo admin_url('admin.php?page=analise-visitantes-realtime'); ?>" class="button">Tempo Real</a>
            </div>
        </div>
        <style>
            .av-dashboard-widget .av-summary {
                display: flex;
                justify-content: space-between;
                margin-bottom: 15px;
            }
            .av-dashboard-widget .av-stat {
                text-align: center;
                flex: 1;
            }
            .av-dashboard-widget .av-count {
                display: block;
                font-size: 24px;
                font-weight: 600;
                color: #0073aa;
            }
            .av-dashboard-widget .av-label {
                display: block;
                font-size: 12px;
                color: #666;
            }
            .av-dashboard-widget .av-links {
                display: flex;
                justify-content: space-between;
            }
        </style>
        <?php
    }
    
    /**
     * Obter resumo para o widget do dashboard
     */
    private function get_dashboard_summary() {
        $core = Analise_Visitantes_Core::get_instance();
        $realtime = Analise_Visitantes_Realtime::get_instance();
        
        $hoje = get_option('av_visitas_hoje', 0);
        $online = $realtime->count_online_visitors();
        
        // Obter visitas de ontem
        global $wpdb;
        $table_stats = $wpdb->prefix . 'analise_visitantes_stats';
        $ontem = date('Y-m-d', strtotime('-1 day'));
        
        $ontem_count = $wpdb->get_var($wpdb->prepare(
            "SELECT visitas FROM $table_stats WHERE data = %s",
            $ontem
        ));
        
        if (!$ontem_count) {
            $ontem_count = 0;
        }
        
        return array(
            'hoje' => $hoje,
            'ontem' => $ontem_count,
            'online' => $online
        );
    }
    
    /**
     * Obter dados para o dashboard
     */
    private function get_dashboard_data() {
        // Tabela de visitas
        global $wpdb;
        $table_name = $wpdb->prefix . 'analise_visitantes';
        
        // Definir intervalo (últimos 30 dias)
        $end_date = date('Y-m-d 23:59:59');
        $start_date = date('Y-m-d 00:00:00', strtotime('-30 days'));
        
        // Dados vazios para retorno inicial
        $data = array(
            'summary' => array(
                'total_visitors' => 0,
                'total_pageviews' => 0,
                'online_now' => 0
            ),
            'charts' => array(
                'dates' => array(),
                'visitors' => array(),
                'pageviews' => array()
            ),
            'tables' => array(
                'top_pages' => array(),
                'browsers' => array(),
                'devices' => array(),
                'countries' => array()
            )
        );
        
        // Verificar se a tabela existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return $data;
        }
        
        // Obter resumo
        $visitors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT visitor_id) FROM $table_name WHERE date_time BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        $pageviews = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE date_time BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        // Obter dados para o gráfico
        $chart_data = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(date_time) as date, 
                    COUNT(DISTINCT visitor_id) as visitors, 
                    COUNT(*) as pageviews
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s
             GROUP BY DATE(date_time)
             ORDER BY date ASC",
            $start_date, $end_date
        ), ARRAY_A);
        
        // Preencher dados do gráfico
        $dates = array();
        $visitors_data = array();
        $pageviews_data = array();
        
        foreach ($chart_data as $day) {
            $dates[] = $day['date'];
            $visitors_data[] = intval($day['visitors']);
            $pageviews_data[] = intval($day['pageviews']);
        }
        
        // Obter páginas mais visitadas
        $top_pages = $wpdb->get_results($wpdb->prepare(
            "SELECT page_url, page_title, COUNT(*) as views
             FROM $table_name
             WHERE date_time BETWEEN %s AND %s
             GROUP BY page_url, page_title
             ORDER BY views DESC
             LIMIT 10",
            $start_date, $end_date
        ), ARRAY_A);
        
        // Obter navegadores mais usados
        $browsers = $wpdb->get_results($wpdb->prepare(
            "SELECT browser, COUNT(*) as count
             FROM $table_name
             WHERE date_time BETWEEN %s AND %s
             GROUP BY browser
             ORDER BY count DESC
             LIMIT 5",
            $start_date, $end_date
        ), ARRAY_A);
        
        // Obter dispositivos
        $devices = $wpdb->get_results($wpdb->prepare(
            "SELECT device_type, COUNT(*) as count
             FROM $table_name
             WHERE date_time BETWEEN %s AND %s
             GROUP BY device_type
             ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
        
        // Obter países
        $countries = $wpdb->get_results($wpdb->prepare(
            "SELECT country, COUNT(*) as count
             FROM $table_name
             WHERE date_time BETWEEN %s AND %s
             AND country != ''
             GROUP BY country
             ORDER BY count DESC
             LIMIT 10",
            $start_date, $end_date
        ), ARRAY_A);
        
        // Obter contagem de usuários online
        $realtime = Analise_Visitantes_Realtime::get_instance();
        $online_now = $realtime->count_online_visitors();
        
        // Preencher o array de retorno
        $data['summary'] = array(
            'total_visitors' => intval($visitors),
            'total_pageviews' => intval($pageviews),
            'online_now' => $online_now
        );
        
        $data['charts'] = array(
            'dates' => $dates,
            'visitors' => $visitors_data,
            'pageviews' => $pageviews_data
        );
        
        $data['tables'] = array(
            'top_pages' => $top_pages,
            'browsers' => $browsers,
            'devices' => $devices,
            'countries' => $countries
        );
        
        return $data;
    }
    
    /**
     * Obter dados para o mapa
     */
    private function get_map_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'analise_visitantes';
        
        // Definir intervalo (últimos 30 dias)
        $end_date = date('Y-m-d 23:59:59');
        $start_date = date('Y-m-d 00:00:00', strtotime('-30 days'));
        
        // Obter dados geográficos
        $locations = $wpdb->get_results($wpdb->prepare(
            "SELECT country, city, latitude, longitude, COUNT(*) as count
             FROM $table_name
             WHERE date_time BETWEEN %s AND %s
             AND latitude != 0 AND longitude != 0
             GROUP BY country, city, latitude, longitude
             ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
        
        return $locations;
    }
    
    /**
     * Adicionar links de ação ao plugin
     */
    public function add_plugin_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=analise-visitantes') . '">Dashboard</a>',
            '<a href="' . admin_url('admin.php?page=analise-visitantes-settings') . '">Configurações</a>'
        );
        
        return array_merge($plugin_links, $links);
    }
    
    /**
     * Renderizar página do dashboard
     */
    public function render_dashboard_page() {
        // Verificar se o arquivo existe
        $template_path = ANALISE_VISITANTES_PATH . 'admin/views/dashboard.php';
        
        if (!file_exists($template_path)) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Erro: Arquivo de template não encontrado em: ' . esc_html($template_path) . '</p></div></div>';
            return;
        }
        
        // Incluir o template usando require para mostrar erros
        require_once $template_path;
    }
    
    /**
     * Renderizar página de tempo real
     */
    public function render_realtime_page() {
        require_once ANALISE_VISITANTES_PATH . 'admin/views/realtime.php';
    }
    
    /**
     * Renderizar página de relatórios
     */
    public function render_reports_page() {
        require_once ANALISE_VISITANTES_PATH . 'admin/views/reports.php';
    }
    
    /**
     * Renderizar página de mapa
     */
    public function render_map_page() {
        require_once ANALISE_VISITANTES_PATH . 'admin/views/map.php';
    }
    
    /**
     * Renderizar página de configurações
     */
    public function render_settings_page() {
        require_once ANALISE_VISITANTES_PATH . 'admin/views/settings.php';
    }
    
    /**
     * Função de diagnóstico temporária para verificar problemas com arquivos
     */
    public function verificar_permissoes_arquivos() {
        $diretorio_admin = ANALISE_VISITANTES_PATH . 'admin/views/';
        $diretorio_views = ANALISE_VISITANTES_PATH . 'views/';
        
        echo '<div class="wrap">';
        echo '<h1>Diagnóstico de Arquivos</h1>';
        
        echo '<h2>Arquivos em admin/views/</h2>';
        $this->listar_arquivos_diretorio($diretorio_admin);
        
        echo '<h2>Arquivos em views/ (se existir)</h2>';
        $this->listar_arquivos_diretorio($diretorio_views);
        
        echo '<h2>Caminhos importantes</h2>';
        echo '<ul>';
        echo '<li>Diretório do plugin: ' . esc_html(ANALISE_VISITANTES_PATH) . '</li>';
        echo '<li>URL do plugin: ' . esc_html(ANALISE_VISITANTES_URL) . '</li>';
        echo '</ul>';
        
        echo '</div>';
    }
    
    /**
     * Função auxiliar para listar arquivos em um diretório
     */
    private function listar_arquivos_diretorio($diretorio) {
        if (!file_exists($diretorio)) {
            echo '<p>Diretório não encontrado: ' . esc_html($diretorio) . '</p>';
            return;
        }
        
        $arquivos = glob($diretorio . '*.php');
        
        if (empty($arquivos)) {
            echo '<p>Nenhum arquivo PHP encontrado em: ' . esc_html($diretorio) . '</p>';
        } else {
            echo '<ul>';
            foreach ($arquivos as $arquivo) {
                echo '<li>';
                echo 'Arquivo: ' . esc_html(basename($arquivo));
                echo ' - Existe: ' . (file_exists($arquivo) ? 'Sim' : 'Não');
                echo ' - Legível: ' . (is_readable($arquivo) ? 'Sim' : 'Não');
                echo '</li>';
            }
            echo '</ul>';
        }
    }
} 