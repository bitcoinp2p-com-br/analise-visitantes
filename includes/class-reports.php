<?php
/**
 * Classe de Relatórios do Plugin Análise de Visitantes
 * 
 * Responsável pela geração de relatórios e estatísticas
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class Analise_Visitantes_Reports {
    
    /**
     * Instância única da classe (Singleton)
     * @var Analise_Visitantes_Reports
     */
    private static $instance = null;
    
    /**
     * Obtém a instância única da classe
     * 
     * @return Analise_Visitantes_Reports
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
        // Adicionar hooks para relatórios
        add_action('admin_init', array($this, 'register_reports'));
    }
    
    /**
     * Registrar tipos de relatórios
     */
    public function register_reports() {
        // Esta função será chamada quando o plugin for inicializado
    }
    
    /**
     * Gerar relatório por período
     * 
     * @param string $start_date Data inicial
     * @param string $end_date Data final
     * @return array Dados do relatório
     */
    public function generate_period_report($start_date, $end_date) {
        global $wpdb;
        
        // Tabela principal de visitas
        $table_name = $wpdb->prefix . 'analise_visitantes';
        
        // Consultar total de visitas no período
        $total_visits = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE date_time BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        // Consultar visitantes únicos no período
        $unique_visitors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT visitor_id) FROM $table_name WHERE date_time BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        // Consultar páginas mais visitadas
        $top_pages = $wpdb->get_results($wpdb->prepare(
            "SELECT page_url, page_title, COUNT(*) as views 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s 
             GROUP BY page_url 
             ORDER BY views DESC 
             LIMIT 10",
            $start_date, $end_date
        ), ARRAY_A);
        
        return array(
            'total_visits' => $total_visits ?: 0,
            'unique_visitors' => $unique_visitors ?: 0,
            'top_pages' => $top_pages ?: array()
        );
    }
    
    /**
     * Gerar relatórios por dispositivo
     * 
     * @param string $start_date Data inicial
     * @param string $end_date Data final
     * @return array Dados do relatório
     */
    public function generate_device_report($start_date, $end_date) {
        global $wpdb;
        
        // Tabela principal de visitas
        $table_name = $wpdb->prefix . 'analise_visitantes';
        
        // Consultar dispositivos
        $devices = $wpdb->get_results($wpdb->prepare(
            "SELECT device_type, COUNT(*) as count 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s 
             GROUP BY device_type 
             ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
        
        // Consultar navegadores
        $browsers = $wpdb->get_results($wpdb->prepare(
            "SELECT browser, COUNT(*) as count 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s 
             GROUP BY browser 
             ORDER BY count DESC 
             LIMIT 10",
            $start_date, $end_date
        ), ARRAY_A);
        
        // Consultar sistemas operacionais
        $operating_systems = $wpdb->get_results($wpdb->prepare(
            "SELECT operating_system, COUNT(*) as count 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s 
             GROUP BY operating_system 
             ORDER BY count DESC 
             LIMIT 10",
            $start_date, $end_date
        ), ARRAY_A);
        
        return array(
            'devices' => $devices ?: array(),
            'browsers' => $browsers ?: array(),
            'operating_systems' => $operating_systems ?: array()
        );
    }
    
    /**
     * Gerar relatório geográfico
     * 
     * @param string $start_date Data inicial
     * @param string $end_date Data final
     * @return array Dados do relatório
     */
    public function generate_geo_report($start_date, $end_date) {
        global $wpdb;
        
        // Tabela principal de visitas
        $table_name = $wpdb->prefix . 'analise_visitantes';
        
        // Consultar países
        $countries = $wpdb->get_results($wpdb->prepare(
            "SELECT country, COUNT(*) as count 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s 
             AND country != '' 
             GROUP BY country 
             ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
        
        // Consultar cidades
        $cities = $wpdb->get_results($wpdb->prepare(
            "SELECT city, country, COUNT(*) as count 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s 
             AND city != '' 
             GROUP BY city, country 
             ORDER BY count DESC 
             LIMIT 20",
            $start_date, $end_date
        ), ARRAY_A);
        
        return array(
            'countries' => $countries ?: array(),
            'cities' => $cities ?: array()
        );
    }
} 