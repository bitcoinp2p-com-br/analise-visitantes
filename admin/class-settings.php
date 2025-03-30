<?php
/**
 * Classe de Configurações do Plugin Análise de Visitantes
 * 
 * Responsável pelas configurações do plugin
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class Analise_Visitantes_Settings {
    
    /**
     * Instância única da classe (Singleton)
     * @var Analise_Visitantes_Settings
     */
    private static $instance = null;
    
    /**
     * Obtém a instância única da classe
     * 
     * @return Analise_Visitantes_Settings
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
        // Adicionar ações para as configurações
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Registrar configurações
     */
    public function register_settings() {
        // Registrar grupo de configurações
        register_setting('analise_visitantes_options', 'av_retencao_dados');
        register_setting('analise_visitantes_options', 'av_rastrear_admin');
        register_setting('analise_visitantes_options', 'av_ignorar_bots');
        register_setting('analise_visitantes_options', 'av_geo_tracking');
        
        // Seção geral
        add_settings_section(
            'analise_visitantes_general',
            'Configurações Gerais',
            array($this, 'render_general_section'),
            'analise-visitantes-settings'
        );
        
        // Campos da seção geral
        add_settings_field(
            'av_retencao_dados',
            'Retenção de Dados',
            array($this, 'render_retention_field'),
            'analise-visitantes-settings',
            'analise_visitantes_general'
        );
        
        add_settings_field(
            'av_rastrear_admin',
            'Rastrear Administradores',
            array($this, 'render_track_admins_field'),
            'analise-visitantes-settings',
            'analise_visitantes_general'
        );
        
        add_settings_field(
            'av_ignorar_bots',
            'Ignorar Bots',
            array($this, 'render_ignore_bots_field'),
            'analise-visitantes-settings',
            'analise_visitantes_general'
        );
        
        add_settings_field(
            'av_geo_tracking',
            'Rastreamento Geográfico',
            array($this, 'render_geo_tracking_field'),
            'analise-visitantes-settings',
            'analise_visitantes_general'
        );
    }
    
    /**
     * Renderizar descrição da seção geral
     */
    public function render_general_section() {
        echo '<p>Configurações gerais para o plugin Análise de Visitantes.</p>';
    }
    
    /**
     * Renderizar campo de retenção de dados
     */
    public function render_retention_field() {
        $value = get_option('av_retencao_dados', 30);
        ?>
        <select name="av_retencao_dados" id="av_retencao_dados">
            <option value="7" <?php selected($value, 7); ?>>7 dias</option>
            <option value="15" <?php selected($value, 15); ?>>15 dias</option>
            <option value="30" <?php selected($value, 30); ?>>30 dias</option>
            <option value="60" <?php selected($value, 60); ?>>60 dias</option>
            <option value="90" <?php selected($value, 90); ?>>90 dias</option>
            <option value="180" <?php selected($value, 180); ?>>6 meses</option>
            <option value="365" <?php selected($value, 365); ?>>1 ano</option>
        </select>
        <p class="description">Por quanto tempo manter os dados de visitantes no banco de dados.</p>
        <?php
    }
    
    /**
     * Renderizar campo para rastrear administradores
     */
    public function render_track_admins_field() {
        $value = get_option('av_rastrear_admin', false);
        ?>
        <label>
            <input type="checkbox" name="av_rastrear_admin" value="1" <?php checked($value, true); ?>>
            Rastrear visitas de administradores
        </label>
        <p class="description">Se ativado, as visitas de usuários administradores também serão registradas.</p>
        <?php
    }
    
    /**
     * Renderizar campo para ignorar bots
     */
    public function render_ignore_bots_field() {
        $value = get_option('av_ignorar_bots', true);
        ?>
        <label>
            <input type="checkbox" name="av_ignorar_bots" value="1" <?php checked($value, true); ?>>
            Ignorar visitas de bots e crawlers
        </label>
        <p class="description">Se ativado, visitas de bots conhecidos (Google, Bing, etc) não serão contabilizadas.</p>
        <?php
    }
    
    /**
     * Renderizar campo para rastreamento geográfico
     */
    public function render_geo_tracking_field() {
        $value = get_option('av_geo_tracking', true);
        ?>
        <label>
            <input type="checkbox" name="av_geo_tracking" value="1" <?php checked($value, true); ?>>
            Ativar rastreamento geográfico
        </label>
        <p class="description">Se ativado, serão coletadas informações sobre a localização dos visitantes (país, cidade, etc).</p>
        <?php
    }
    
    /**
     * Obter todas as configurações
     * 
     * @return array Configurações do plugin
     */
    public function get_all_settings() {
        return array(
            'retencao_dados' => get_option('av_retencao_dados', 30),
            'rastrear_admin' => get_option('av_rastrear_admin', false),
            'ignorar_bots' => get_option('av_ignorar_bots', true),
            'geo_tracking' => get_option('av_geo_tracking', true)
        );
    }
    
    /**
     * Limpar dados antigos baseado nas configurações
     */
    public function clean_old_data() {
        global $wpdb;
        
        $retention_days = get_option('av_retencao_dados', 30);
        $table_name = $wpdb->prefix . 'analise_visitantes';
        
        $date_limit = date('Y-m-d H:i:s', strtotime('-' . $retention_days . ' days'));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE date_time < %s",
            $date_limit
        ));
    }
    
    /**
     * Exportar dados para CSV
     * 
     * @param string $start_date Data inicial
     * @param string $end_date Data final
     * @return string Caminho para o arquivo CSV
     */
    public function export_data_csv($start_date, $end_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'analise_visitantes';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT page_title, page_url, date_time, country, city, device_type, browser, operating_system 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s 
             ORDER BY date_time DESC",
            $start_date, $end_date
        ), ARRAY_A);
        
        if (empty($results)) {
            return false;
        }
        
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/analise-visitantes-export-' . date('Y-m-d-H-i') . '.csv';
        $file_url = $upload_dir['baseurl'] . '/analise-visitantes-export-' . date('Y-m-d-H-i') . '.csv';
        
        $fp = fopen($file_path, 'w');
        
        // Cabeçalho do CSV
        fputcsv($fp, array(
            'Título da Página',
            'URL',
            'Data e Hora',
            'País',
            'Cidade',
            'Dispositivo',
            'Navegador',
            'Sistema Operacional'
        ));
        
        // Dados
        foreach ($results as $row) {
            fputcsv($fp, array(
                $row['page_title'],
                $row['page_url'],
                $row['date_time'],
                $row['country'],
                $row['city'],
                $row['device_type'],
                $row['browser'],
                $row['operating_system']
            ));
        }
        
        fclose($fp);
        
        return $file_url;
    }
} 