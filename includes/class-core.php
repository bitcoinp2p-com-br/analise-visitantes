<?php
/**
 * Classe principal do plugin Análise de Visitantes
 * 
 * Gerencia as funcionalidades centrais do plugin
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class Analise_Visitantes_Core {
    
    /**
     * Versão do plugin
     * @var string
     */
    protected $version;
    
    /**
     * Instância única da classe (Singleton)
     * @var Analise_Visitantes_Core
     */
    private static $instance = null;
    
    /**
     * Obtém a instância única da classe
     * 
     * @return Analise_Visitantes_Core
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Construtor - inicializa o plugin
     */
    private function __construct() {
        $this->version = '2.1.0';
        $this->load_dependencies();
        $this->setup_actions();
    }
    
    /**
     * Carrega as dependências necessárias
     */
    private function load_dependencies() {
        // Carregar arquivos de classe
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-tracking.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-reports.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-geolocation.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-devices.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-realtime.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-settings.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-public.php';
    }
    
    /**
     * Configura as ações principais do plugin
     */
    private function setup_actions() {
        // Ativar rastreamento de sessão
        add_action('init', array($this, 'initialize_session'));
        
        // Hooks de ativação e desativação
        register_activation_hook(ANALISE_VISITANTES_FILE, array($this, 'activate'));
        register_deactivation_hook(ANALISE_VISITANTES_FILE, array($this, 'deactivate'));
        
        // Agendamento para limpeza de dados
        add_action('limpar_dados_antigos', array($this, 'clean_old_data'));
        add_action('reset_visitantes_diarios', array($this, 'reset_daily_visitors'));
    }
    
    /**
     * Inicializa a sessão se necessário
     */
    public function initialize_session() {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
        
        // Definir timezone para Brasil
        date_default_timezone_set('America/Sao_Paulo');
    }
    
    /**
     * Ação executada na ativação do plugin
     */
    public function activate() {
        // Criar tabelas necessárias
        $this->create_tables();
        
        // Agendar limpeza de dados e reset diário
        $this->schedule_tasks();
        
        // Inicializar contador se não existir
        if (!get_option('av_visitas_hoje')) {
            update_option('av_visitas_hoje', 0);
            update_option('av_ultimo_reset', date('Y-m-d H:i:s'));
        }
        
        // Configurar opções padrão
        $this->set_default_options();
    }
    
    /**
     * Ação executada na desativação do plugin
     */
    public function deactivate() {
        // Remover agendamentos
        wp_clear_scheduled_hook('limpar_dados_antigos');
        wp_clear_scheduled_hook('reset_visitantes_diarios');
    }
    
    /**
     * Criar tabelas do banco de dados
     */
    public function create_tables() {
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Verificar se a tabela principal existe e está com a estrutura correta
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if ($table_exists) {
            // Verificar se a coluna 'country' existe
            $country_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'country'");
            
            // Se a coluna não existir, tentar adicionar colunas manualmente
            if (!$country_exists) {
                $wpdb->query("ALTER TABLE $table_name 
                    ADD COLUMN `country` varchar(2) DEFAULT '',
                    ADD COLUMN `city` varchar(50) DEFAULT '',
                    ADD COLUMN `latitude` decimal(10,8) DEFAULT 0,
                    ADD COLUMN `longitude` decimal(11,8) DEFAULT 0");
            }
        }
        
        // Executar criação/atualização das tabelas
        dbDelta($sql);
        dbDelta($sql_online);
        dbDelta($sql_country);
        dbDelta($sql_device);
        dbDelta($sql_stats);
        dbDelta($sql_agregado);
    }
    
    /**
     * Agendar tarefas
     */
    private function schedule_tasks() {
        // Agendar limpeza mensal
        if (!wp_next_scheduled('limpar_dados_antigos')) {
            wp_schedule_event(time(), 'daily', 'limpar_dados_antigos');
        }
        
        // Agendar reset diário à meia-noite (horário Brasil)
        if (!wp_next_scheduled('reset_visitantes_diarios')) {
            // Calcular próxima meia-noite no horário brasileiro
            $agora = time();
            $meia_noite = strtotime('tomorrow 00:01:00');
            
            wp_schedule_event($meia_noite, 'daily', 'reset_visitantes_diarios');
        }
    }
    
    /**
     * Configurar opções padrão
     */
    private function set_default_options() {
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
     * Limpar dados antigos
     */
    public function clean_old_data() {
        global $wpdb;
        
        // Obter período de retenção (padrão: 30 dias)
        $retencao = apply_filters('analise_visitantes_retencao', get_option('av_retencao_dados', 30));
        
        $table_name = $wpdb->prefix . 'analise_visitantes';
        $expiry_date = date('Y-m-d H:i:s', strtotime('-' . $retencao . ' days'));
        
        // Remover registros antigos
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE date_time < %s",
            $expiry_date
        ));
        
        // Limpar também os transients expirados
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_av_%' AND option_value < " . time());
        
        // Limpar agregações antigas
        $table_agregado = $wpdb->prefix . 'analise_visitantes_agregado';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_agregado WHERE data < %s",
            date('Y-m-d', strtotime('-' . $retencao . ' days'))
        ));
        
        // Otimizar tabelas periodicamente (uma vez por mês)
        if (date('j') == '1') { // No primeiro dia do mês
            $this->optimize_tables();
        }
    }
    
    /**
     * Otimizar tabelas do banco de dados
     */
    public function optimize_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'analise_visitantes',
            $wpdb->prefix . 'analise_visitantes_online',
            $wpdb->prefix . 'analise_visitantes_paises',
            $wpdb->prefix . 'analise_visitantes_dispositivos',
            $wpdb->prefix . 'analise_visitantes_stats',
            $wpdb->prefix . 'analise_visitantes_agregado'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE $table");
        }
    }
    
    /**
     * Resetar contadores diários
     */
    public function reset_daily_visitors() {
        global $wpdb;
        
        // Criar/atualizar registro para contador diário
        $table_stats = $wpdb->prefix . 'analise_visitantes_stats';
        
        // Verificar se a tabela existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_stats'") != $table_stats) {
            $this->create_tables();
        }
        
        // Registrar estatísticas do dia anterior antes de resetar
        $hoje = date('Y-m-d');
        $ontem = date('Y-m-d', strtotime('-1 day'));
        
        // Obter a contagem atual antes de resetar
        $contagem_atual = get_option('av_visitas_hoje', 0);
        
        // Armazenar estatísticas do dia que terminou
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table_stats (data, visitas) VALUES (%s, %d) 
             ON DUPLICATE KEY UPDATE visitas = %d",
            $ontem, $contagem_atual, $contagem_atual
        ));
        
        // Resetar contador para o novo dia
        update_option('av_visitas_hoje', 0);
        update_option('av_ultimo_reset', $hoje . ' 00:01:00');
        
        error_log('Análise de Visitantes: Contadores diários resetados à meia-noite (horário Brasil). Dia anterior: ' . $contagem_atual . ' visitas.');
    }
    
    /**
     * Obter versão do plugin
     * 
     * @return string Versão do plugin
     */
    public function get_version() {
        return $this->version;
    }
} 