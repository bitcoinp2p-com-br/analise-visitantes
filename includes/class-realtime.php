<?php
/**
 * Classe de Monitoramento em Tempo Real do Plugin Análise de Visitantes
 * 
 * Responsável pelo rastreamento e exibição de visitantes em tempo real
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class Analise_Visitantes_Realtime {
    
    /**
     * Instância única da classe (Singleton)
     * @var Analise_Visitantes_Realtime
     */
    private static $instance = null;
    
    /**
     * Obtém a instância única da classe
     * 
     * @return Analise_Visitantes_Realtime
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
        // Adicionar hooks para AJAX
        add_action('wp_ajax_av_get_realtime_data', array($this, 'get_realtime_data'));
        add_action('wp_ajax_av_get_online_count', array($this, 'get_online_count'));
    }
    
    /**
     * Obter contagem de visitantes online
     */
    public function get_online_count() {
        global $wpdb;
        $count = $this->count_online_visitors();
        wp_send_json_success(array('count' => $count));
    }
    
    /**
     * Contar número de visitantes online
     * 
     * @return int
     */
    public function count_online_visitors() {
        global $wpdb;
        
        // Tabela de visitantes online
        $table_online = $wpdb->prefix . 'analise_visitantes_online';
        
        // Verificar se a tabela existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_online'") != $table_online) {
            return 0;
        }
        
        // Remover sessões expiradas (15 minutos)
        $expiry_time = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_online WHERE last_activity < %s",
            $expiry_time
        ));
        
        // Contar visitantes únicos
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_online");
        
        return intval($count);
    }
    
    /**
     * Obter dados de visitantes em tempo real para AJAX
     */
    public function get_realtime_data() {
        check_ajax_referer('av_realtime_nonce', 'security');
        
        $visitors = $this->get_online_visitors();
        $count = $this->count_online_visitors();
        
        wp_send_json_success(array(
            'visitors' => $visitors,
            'count' => $count,
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * Obter lista de visitantes online
     * 
     * @param int $limit Número máximo de visitantes a retornar
     * @return array
     */
    public function get_online_visitors($limit = 50) {
        global $wpdb;
        
        // Tabela de visitantes online
        $table_online = $wpdb->prefix . 'analise_visitantes_online';
        $table_visits = $wpdb->prefix . 'analise_visitantes';
        
        // Verificar se as tabelas existem
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_online'") != $table_online) {
            return array();
        }
        
        // Remover sessões expiradas (15 minutos)
        $expiry_time = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_online WHERE last_activity < %s",
            $expiry_time
        ));
        
        // Obter visitantes online com dados adicionais
        $visitors = $wpdb->get_results($wpdb->prepare(
            "SELECT o.session_id, o.page_url, o.last_activity, o.user_agent,
                    MAX(v.country) as country, 
                    MAX(v.city) as city,
                    MAX(v.device_type) as device_type, 
                    MAX(v.browser) as browser,
                    MAX(v.operating_system) as operating_system
             FROM $table_online o
             LEFT JOIN $table_visits v ON MD5(CONCAT(o.ip, o.user_agent, DATE(o.last_activity))) = v.visitor_id
             GROUP BY o.session_id
             ORDER BY o.last_activity DESC
             LIMIT %d",
            $limit
        ), ARRAY_A);
        
        // Formatar os dados para exibição
        $formatted_visitors = array();
        
        if ($visitors) {
            foreach ($visitors as $visitor) {
                // Obter nome da página a partir da URL
                $page_title = '';
                $url_parts = parse_url($visitor['page_url']);
                
                if (isset($url_parts['path'])) {
                    $path = $url_parts['path'];
                    $path = rtrim($path, '/');
                    $path_parts = explode('/', $path);
                    $page_title = end($path_parts);
                    $page_title = str_replace(array('-', '_'), ' ', $page_title);
                    $page_title = ucwords($page_title);
                }
                
                if (empty($page_title)) {
                    $page_title = 'Página Inicial';
                }
                
                // Calcular tempo online
                $last_activity = strtotime($visitor['last_activity']);
                $now = current_time('timestamp');
                $time_ago = $now - $last_activity;
                
                if ($time_ago < 60) {
                    $time_str = 'agora mesmo';
                } else if ($time_ago < 3600) {
                    $minutes = floor($time_ago / 60);
                    $time_str = $minutes . ' min atrás';
                } else {
                    $hours = floor($time_ago / 3600);
                    $minutes = floor(($time_ago % 3600) / 60);
                    $time_str = $hours . 'h ' . $minutes . 'm atrás';
                }
                
                $formatted_visitors[] = array(
                    'page_url' => $visitor['page_url'],
                    'page_title' => $page_title,
                    'time_ago' => $time_str,
                    'country' => $visitor['country'],
                    'city' => $visitor['city'],
                    'device_type' => $visitor['device_type'],
                    'browser' => $visitor['browser'],
                    'operating_system' => $visitor['operating_system'],
                    'last_activity' => $visitor['last_activity']
                );
            }
        }
        
        return $formatted_visitors;
    }
} 