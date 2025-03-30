<?php
/**
 * Classe de Dashboard do Plugin Análise de Visitantes
 * 
 * Responsável pela interface administrativa do plugin
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class Analise_Visitantes_Dashboard {
    
    /**
     * Instância única da classe (Singleton)
     * @var Analise_Visitantes_Dashboard
     */
    private static $instance = null;
    
    /**
     * Obtém a instância única da classe
     * 
     * @return Analise_Visitantes_Dashboard
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Construtor
     */
    private function __construct() {
        $this->setup_actions();
    }
    
    /**
     * Configurar ações e filtros
     */
    private function setup_actions() {
        // Adicionar menu no admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Adicionar scripts e estilos
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Ajax para atualização em tempo real
        add_action('wp_ajax_av_atualizar_dashboard', array($this, 'ajax_update_dashboard'));
        
        // Ações para configurações
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Adicionar menu no painel administrativo
     */
    public function add_admin_menu() {
        // Menu principal
        add_menu_page(
            'Análise de Visitantes', 
            'Análise de Visitantes', 
            'manage_options', 
            'analise-visitantes', 
            array($this, 'display_dashboard_page'),
            'dashicons-chart-area',
            30
        );
        
        // Submenus
        add_submenu_page(
            'analise-visitantes',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'analise-visitantes',
            array($this, 'display_dashboard_page')
        );
        
        add_submenu_page(
            'analise-visitantes',
            'Visitantes em Tempo Real',
            'Tempo Real',
            'manage_options',
            'analise-visitantes-realtime',
            array($this, 'display_realtime_page')
        );
        
        add_submenu_page(
            'analise-visitantes',
            'Relatórios',
            'Relatórios',
            'manage_options',
            'analise-visitantes-reports',
            array($this, 'display_reports_page')
        );
        
        add_submenu_page(
            'analise-visitantes',
            'Mapa de Visitantes',
            'Mapa',
            'manage_options',
            'analise-visitantes-map',
            array($this, 'display_map_page')
        );
        
        add_submenu_page(
            'analise-visitantes',
            'Configurações',
            'Configurações',
            'manage_options',
            'analise-visitantes-settings',
            array($this, 'display_settings_page')
        );
    }
    
    /**
     * Adicionar scripts e estilos para o admin
     */
    public function enqueue_admin_scripts($hook) {
        // Verificar se estamos em uma página do nosso plugin
        if (strpos($hook, 'analise-visitantes') === false) {
            return;
        }
        
        // Registrar e adicionar CSS
        wp_register_style(
            'analise-visitantes-admin',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css',
            array(),
            '1.0.0'
        );
        wp_enqueue_style('analise-visitantes-admin');
        
        // Registrar e adicionar JavaScript
        wp_register_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        wp_register_script(
            'leaflet-js',
            'https://unpkg.com/leaflet@1.9.3/dist/leaflet.js',
            array(),
            '1.9.3',
            true
        );
        
        wp_register_style(
            'leaflet-css',
            'https://unpkg.com/leaflet@1.9.3/dist/leaflet.css',
            array(),
            '1.9.3'
        );
        
        wp_register_script(
            'analise-visitantes-dashboard',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/dashboard.js',
            array('jquery', 'chart-js'),
            '1.0.0',
            true
        );
        
        wp_register_script(
            'analise-visitantes-map',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/map.js',
            array('jquery', 'leaflet-js'),
            '1.0.0',
            true
        );
        
        wp_register_script(
            'analise-visitantes-realtime',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/realtime.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Adicionar scripts conforme a página atual
        if (isset($_GET['page'])) {
            if ($_GET['page'] === 'analise-visitantes') {
                wp_enqueue_script('chart-js');
                wp_enqueue_script('analise-visitantes-dashboard');
                $this->localize_dashboard_data();
            } else if ($_GET['page'] === 'analise-visitantes-map') {
                wp_enqueue_style('leaflet-css');
                wp_enqueue_script('leaflet-js');
                wp_enqueue_script('analise-visitantes-map');
                $this->localize_map_data();
            } else if ($_GET['page'] === 'analise-visitantes-realtime') {
                wp_enqueue_script('analise-visitantes-realtime');
                $this->localize_realtime_data();
            }
        }
    }
    
    /**
     * Localizar dados para o JavaScript do Dashboard
     */
    private function localize_dashboard_data() {
        // Obter dados do período atual
        $stats = $this->get_current_period_stats();
        
        // Configurar ajax
        wp_localize_script('analise-visitantes-dashboard', 'avDashboard', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('av_dashboard_nonce'),
            'stats' => $stats,
            'labels' => array(
                'visitors' => 'Visitantes',
                'pageviews' => 'Visualizações',
                'today' => 'Hoje',
                'yesterday' => 'Ontem',
                'days' => array('Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'),
                'months' => array('Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez')
            )
        ));
    }
    
    /**
     * Localizar dados para o JavaScript do Mapa
     */
    private function localize_map_data() {
        global $wpdb;
        
        // Dados dos países para o mapa
        $countries = $wpdb->get_results(
            "SELECT country_code, country_name, visits, MAX(last_visit) as last_visit 
             FROM {$wpdb->prefix}analise_visitantes_paises 
             GROUP BY country_code, country_name, visits 
             ORDER BY visits DESC",
            ARRAY_A
        );
        
        // Se não houver dados, iniciar array vazio
        if (!$countries) {
            $countries = array();
        }
        
        // Obter coordenadas para os países
        $geo_data = array();
        
        foreach ($countries as $country) {
            // Obter a média das coordenadas para cada país
            $coords = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT AVG(latitude) as lat, AVG(longitude) as lng 
                     FROM {$wpdb->prefix}analise_visitantes 
                     WHERE country = %s AND latitude != 0 AND longitude != 0",
                    $country['country_code']
                ),
                ARRAY_A
            );
            
            if ($coords && $coords['lat'] && $coords['lng']) {
                $geo_data[] = array(
                    'country' => $country['country_name'],
                    'code' => $country['country_code'],
                    'visits' => intval($country['visits']),
                    'lat' => floatval($coords['lat']),
                    'lng' => floatval($coords['lng']),
                    'last_visit' => $country['last_visit']
                );
            }
        }
        
        wp_localize_script('analise-visitantes-map', 'avMapData', array(
            'countries' => $geo_data,
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('av_map_nonce'),
            'labels' => array(
                'country' => 'País',
                'visits' => 'Visitas',
                'last_visit' => 'Última visita'
            )
        ));
    }
    
    /**
     * Localizar dados para o JavaScript do Tempo Real
     */
    private function localize_realtime_data() {
        global $wpdb;
        
        // Dados dos visitantes online
        $online_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}analise_visitantes_online"
        );
        
        // Obter dados dos últimos visitantes (últimos 15 minutos)
        $recent_visitors = $wpdb->get_results(
            "SELECT v.*, 
             FROM_UNIXTIME(UNIX_TIMESTAMP(date_time)) as visit_time,
             DATE_FORMAT(date_time, '%H:%i:%s') as time_formatted
             FROM {$wpdb->prefix}analise_visitantes v
             WHERE date_time >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
             ORDER BY date_time DESC
             LIMIT 20",
            ARRAY_A
        );
        
        if (!$recent_visitors) {
            $recent_visitors = array();
        }
        
        // Formatar dados para exibição
        $visitors_data = array();
        foreach ($recent_visitors as $visitor) {
            $geo = new Analise_Visitantes_Geolocation();
            $country_name = $geo->get_country_name($visitor['country']);
            
            $visitors_data[] = array(
                'page' => $visitor['page_title'],
                'url' => $visitor['page_url'],
                'country' => $country_name,
                'country_code' => $visitor['country'],
                'city' => $visitor['city'],
                'device' => $visitor['device_type'],
                'browser' => $visitor['browser'],
                'os' => $visitor['operating_system'],
                'time' => $visitor['time_formatted'],
                'timestamp' => strtotime($visitor['visit_time'])
            );
        }
        
        wp_localize_script('analise-visitantes-realtime', 'avRealtimeData', array(
            'online' => intval($online_count),
            'visitors' => $visitors_data,
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('av_realtime_nonce'),
            'refreshInterval' => 10000, // 10 segundos
            'labels' => array(
                'online_now' => 'Visitantes online agora',
                'last_pageviews' => 'Últimas visualizações de página',
                'page' => 'Página',
                'time' => 'Horário',
                'location' => 'Localização',
                'device' => 'Dispositivo',
                'just_now' => 'Agora',
                'seconds_ago' => 'segundos atrás',
                'minutes_ago' => 'minutos atrás',
                'refresh' => 'Atualizar dados'
            )
        ));
    }
    
    /**
     * Exibir a página principal do Dashboard
     */
    public function display_dashboard_page() {
        // Incluir o template do dashboard
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/views/dashboard.php';
    }
    
    /**
     * Exibir a página de visitantes em tempo real
     */
    public function display_realtime_page() {
        // Incluir o template da página de tempo real
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/views/realtime.php';
    }
    
    /**
     * Exibir a página de relatórios
     */
    public function display_reports_page() {
        // Incluir o template da página de relatórios
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/views/reports.php';
    }
    
    /**
     * Exibir a página do mapa de visitantes
     */
    public function display_map_page() {
        // Incluir o template da página de mapa
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/views/map.php';
    }
    
    /**
     * Exibir a página de configurações
     */
    public function display_settings_page() {
        // Incluir o template da página de configurações
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/views/settings.php';
    }
    
    /**
     * Registrar configurações
     */
    public function register_settings() {
        // Registrar grupo de configurações
        register_setting('analise_visitantes_options', 'av_retention_days', array(
            'type' => 'integer',
            'description' => 'Dias para manter dados de visitantes',
            'default' => 90,
        ));
        
        register_setting('analise_visitantes_options', 'av_geo_tracking', array(
            'type' => 'boolean',
            'description' => 'Ativar rastreamento geográfico',
            'default' => true,
        ));
        
        register_setting('analise_visitantes_options', 'av_rastrear_admin', array(
            'type' => 'boolean',
            'description' => 'Rastrear administradores',
            'default' => false,
        ));
        
        register_setting('analise_visitantes_options', 'av_ignorar_bots', array(
            'type' => 'boolean',
            'description' => 'Ignorar bots e crawlers',
            'default' => true,
        ));
        
        // Seção de configurações gerais
        add_settings_section(
            'av_general_settings',
            'Configurações Gerais',
            array($this, 'general_settings_section_callback'),
            'analise-visitantes-settings'
        );
        
        // Campos da seção geral
        add_settings_field(
            'av_retention_days',
            'Dias para retenção de dados',
            array($this, 'render_retention_days_field'),
            'analise-visitantes-settings',
            'av_general_settings'
        );
        
        add_settings_field(
            'av_geo_tracking',
            'Rastreamento geográfico',
            array($this, 'render_geo_tracking_field'),
            'analise-visitantes-settings',
            'av_general_settings'
        );
        
        add_settings_field(
            'av_rastrear_admin',
            'Rastrear administradores',
            array($this, 'render_track_admin_field'),
            'analise-visitantes-settings',
            'av_general_settings'
        );
        
        add_settings_field(
            'av_ignorar_bots',
            'Ignorar bots e crawlers',
            array($this, 'render_ignore_bots_field'),
            'analise-visitantes-settings',
            'av_general_settings'
        );
    }
    
    /**
     * Callback para a seção de configurações gerais
     */
    public function general_settings_section_callback() {
        echo '<p>Configure as opções gerais do plugin de Análise de Visitantes abaixo:</p>';
    }
    
    /**
     * Renderizar campo de dias de retenção
     */
    public function render_retention_days_field() {
        $value = get_option('av_retention_days', 90);
        echo '<input type="number" name="av_retention_days" value="' . esc_attr($value) . '" min="1" max="365" />';
        echo '<p class="description">Número de dias para manter os dados de visitantes no banco de dados. Registros mais antigos serão removidos automaticamente.</p>';
    }
    
    /**
     * Renderizar campo de rastreamento geográfico
     */
    public function render_geo_tracking_field() {
        $value = get_option('av_geo_tracking', true);
        echo '<input type="checkbox" name="av_geo_tracking" value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">Ativar rastreamento geográfico para identificar a localização dos visitantes.</p>';
    }
    
    /**
     * Renderizar campo de rastreamento de administradores
     */
    public function render_track_admin_field() {
        $value = get_option('av_rastrear_admin', false);
        echo '<input type="checkbox" name="av_rastrear_admin" value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">Rastrear visitas de usuários administradores. Desativado por padrão para não contaminar as estatísticas.</p>';
    }
    
    /**
     * Renderizar campo de ignorar bots
     */
    public function render_ignore_bots_field() {
        $value = get_option('av_ignorar_bots', true);
        echo '<input type="checkbox" name="av_ignorar_bots" value="1" ' . checked(1, $value, false) . ' />';
        echo '<p class="description">Ignorar bots e crawlers nas estatísticas de visitantes.</p>';
    }
    
    /**
     * Obter estatísticas do período atual
     * 
     * @return array Dados estatísticos
     */
    public function get_current_period_stats() {
        global $wpdb;
        
        // Definir período (padrão: últimos 30 dias)
        $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
        $days = ($days < 1 || $days > 365) ? 30 : $days;
        
        // Data inicial e final
        $end_date = current_time('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-$days days", strtotime($end_date)));
        
        // Dados de visitantes diários para o gráfico
        $visitors_data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                 DATE(date_time) as date,
                 COUNT(DISTINCT visitor_id) as visitors,
                 COUNT(*) as pageviews
                 FROM {$wpdb->prefix}analise_visitantes
                 WHERE DATE(date_time) BETWEEN %s AND %s
                 GROUP BY DATE(date_time)
                 ORDER BY date ASC",
                $start_date, $end_date
            ),
            ARRAY_A
        );
        
        // Se não houver dados, retornar array vazio
        if (!$visitors_data) {
            $visitors_data = array();
        }
        
        // Preencher datas faltantes
        $dates = array();
        $visitors = array();
        $pageviews = array();
        
        $current = strtotime($start_date);
        $end = strtotime($end_date);
        
        // Criar array com todas as datas do período
        while ($current <= $end) {
            $date = date('Y-m-d', $current);
            $dates[] = $date;
            $visitors[$date] = 0;
            $pageviews[$date] = 0;
            $current = strtotime('+1 day', $current);
        }
        
        // Preencher com os dados reais
        foreach ($visitors_data as $data) {
            $visitors[$data['date']] = intval($data['visitors']);
            $pageviews[$data['date']] = intval($data['pageviews']);
        }
        
        // Totais do período
        $total_visitors = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT visitor_id) 
                 FROM {$wpdb->prefix}analise_visitantes
                 WHERE DATE(date_time) BETWEEN %s AND %s",
                $start_date, $end_date
            )
        );
        
        $total_pageviews = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) 
                 FROM {$wpdb->prefix}analise_visitantes
                 WHERE DATE(date_time) BETWEEN %s AND %s",
                $start_date, $end_date
            )
        );
        
        // Páginas mais visitadas
        $top_pages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                 page_title, page_url, COUNT(*) as views
                 FROM {$wpdb->prefix}analise_visitantes
                 WHERE DATE(date_time) BETWEEN %s AND %s
                 GROUP BY page_title, page_url
                 ORDER BY views DESC
                 LIMIT 10",
                $start_date, $end_date
            ),
            ARRAY_A
        );
        
        if (!$top_pages) {
            $top_pages = array();
        }
        
        // Browsers mais utilizados
        $browsers = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                 browser, COUNT(*) as count
                 FROM {$wpdb->prefix}analise_visitantes
                 WHERE DATE(date_time) BETWEEN %s AND %s
                 GROUP BY browser
                 ORDER BY count DESC
                 LIMIT 5",
                $start_date, $end_date
            ),
            ARRAY_A
        );
        
        if (!$browsers) {
            $browsers = array();
        }
        
        // Dispositivos mais utilizados
        $devices = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                 device_type, COUNT(*) as count
                 FROM {$wpdb->prefix}analise_visitantes
                 WHERE DATE(date_time) BETWEEN %s AND %s
                 GROUP BY device_type
                 ORDER BY count DESC",
                $start_date, $end_date
            ),
            ARRAY_A
        );
        
        if (!$devices) {
            $devices = array();
        }
        
        // Países de origem
        $countries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                 country, COUNT(*) as count
                 FROM {$wpdb->prefix}analise_visitantes
                 WHERE DATE(date_time) BETWEEN %s AND %s
                 AND country != ''
                 GROUP BY country
                 ORDER BY count DESC
                 LIMIT 10",
                $start_date, $end_date
            ),
            ARRAY_A
        );
        
        if (!$countries) {
            $countries = array();
        }
        
        // Formatar países
        $geo = new Analise_Visitantes_Geolocation();
        foreach ($countries as &$country) {
            $country['name'] = $geo->get_country_name($country['country']);
            $country['flag'] = $geo->get_country_flag($country['country']);
        }
        
        // Visitantes online agora
        $online_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}analise_visitantes_online"
        );
        
        // Organizar os dados para retorno
        return array(
            'period' => array(
                'start' => $start_date,
                'end' => $end_date,
                'days' => $days
            ),
            'summary' => array(
                'total_visitors' => intval($total_visitors),
                'total_pageviews' => intval($total_pageviews),
                'online_now' => intval($online_count)
            ),
            'charts' => array(
                'dates' => $dates,
                'visitors' => array_values($visitors),
                'pageviews' => array_values($pageviews)
            ),
            'tables' => array(
                'top_pages' => $top_pages,
                'browsers' => $browsers,
                'devices' => $devices,
                'countries' => $countries
            )
        );
    }
    
    /**
     * Callback AJAX para atualizar dashboard
     */
    public function ajax_update_dashboard() {
        // Verificar nonce
        check_ajax_referer('av_dashboard_nonce', 'nonce');
        
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissão negada');
        }
        
        // Obter estatísticas atualizadas
        $stats = $this->get_current_period_stats();
        
        // Enviar dados como JSON
        wp_send_json_success($stats);
    }
} 