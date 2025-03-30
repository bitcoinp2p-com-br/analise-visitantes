<?php
/**
 * Classe Pública do Plugin Análise de Visitantes
 * 
 * Responsável pelas funcionalidades no front-end
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class Analise_Visitantes_Public {
    
    /**
     * Instância única da classe (Singleton)
     * @var Analise_Visitantes_Public
     */
    private static $instance = null;
    
    /**
     * Obtém a instância única da classe
     * 
     * @return Analise_Visitantes_Public
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
        // Registrar shortcode para exibir estatísticas no front-end
        add_shortcode('analise_visitantes', array($this, 'shortcode_display'));
        
        // Adicionar CSS no front-end
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Incluir CSS e JS no front-end
     */
    public function enqueue_frontend_assets() {
        // Carregar CSS
        wp_enqueue_style(
            'analise-visitantes-frontend',
            ANALISE_VISITANTES_URL . 'views/css/frontend-style.css',
            array(),
            ANALISE_VISITANTES_VERSION
        );
    }
    
    /**
     * Processar shortcode para exibir estatísticas
     * 
     * @param array $atts Atributos do shortcode
     * @return string Conteúdo HTML
     */
    public function shortcode_display($atts) {
        // Extrair atributos
        $atts = shortcode_atts(array(
            'show' => 'all', // all, online, total, top_pages
            'limit' => 5     // número de páginas populares a exibir
        ), $atts, 'analise_visitantes');
        
        // Iniciar buffer de saída
        ob_start();
        
        // Div principal
        echo '<div class="estatisticas-visitantes">';
        
        // Mostrar usuários online
        if ($atts['show'] === 'all' || $atts['show'] === 'online') {
            $this->display_online_counter();
        }
        
        // Mostrar total de visitas
        if ($atts['show'] === 'all' || $atts['show'] === 'total') {
            $this->display_total_counter();
        }
        
        // Mostrar páginas populares
        if ($atts['show'] === 'all' || $atts['show'] === 'top_pages') {
            $this->display_popular_pages(intval($atts['limit']));
        }
        
        echo '</div>';
        
        // Retornar conteúdo
        return ob_get_clean();
    }
    
    /**
     * Exibir contador de usuários online
     */
    private function display_online_counter() {
        // Obter instância do rastreamento em tempo real
        $realtime = Analise_Visitantes_Realtime::get_instance();
        $count = $realtime->count_online_visitors();
        
        echo '<div class="usuarios-online">';
        echo '<span class="count">' . number_format_i18n($count) . '</span>';
        echo '<span class="label">Usuários Online</span>';
        echo '</div>';
    }
    
    /**
     * Exibir contador total de visitas hoje
     */
    private function display_total_counter() {
        $hoje = get_option('av_visitas_hoje', 0);
        
        echo '<div class="total-visitas">';
        echo '<span class="count">' . number_format_i18n($hoje) . '</span>';
        echo '<span class="label">Visitas Hoje</span>';
        echo '</div>';
    }
    
    /**
     * Exibir páginas mais populares
     * 
     * @param int $limit Número de páginas a exibir
     */
    private function display_popular_pages($limit) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'analise_visitantes';
        
        // Definir intervalo de tempo (últimos 7 dias)
        $end_date = date('Y-m-d 23:59:59');
        $start_date = date('Y-m-d 00:00:00', strtotime('-7 days'));
        
        // Obter páginas mais visitadas
        $pages = $wpdb->get_results($wpdb->prepare(
            "SELECT page_url, page_title, COUNT(*) as views
             FROM $table_name
             WHERE date_time BETWEEN %s AND %s
             GROUP BY page_url, page_title
             ORDER BY views DESC
             LIMIT %d",
            $start_date, $end_date, $limit
        ), ARRAY_A);
        
        if (!empty($pages)) {
            echo '<div class="paginas-populares">';
            echo '<h4>Páginas Mais Visitadas</h4>';
            echo '<ul>';
            
            foreach ($pages as $page) {
                $title = empty($page['page_title']) ? basename($page['page_url']) : $page['page_title'];
                
                echo '<li>';
                echo '<a href="' . esc_url($page['page_url']) . '">' . esc_html($title) . '</a>';
                echo ' (' . number_format_i18n($page['views']) . ' visitas)';
                echo '</li>';
            }
            
            echo '</ul>';
            echo '</div>';
        }
    }
    
    /**
     * Obter dados para um widget no tema
     * 
     * @return array Dados formatados para uso em widgets
     */
    public function get_widget_data() {
        $realtime = Analise_Visitantes_Realtime::get_instance();
        
        return array(
            'online' => $realtime->count_online_visitors(),
            'hoje' => get_option('av_visitas_hoje', 0)
        );
    }
} 