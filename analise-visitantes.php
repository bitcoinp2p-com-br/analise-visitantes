<?php
/**
 * Plugin Name: Análise de Visitantes
 * Plugin URI: https://bitcoinp2p.com.br/plugins
 * Description: Plugin leve e focado em privacidade para monitorar visualizações de página, sem usar cookies ou serviços de terceiros. Inspirado no Statify.
 * Version: 2.0
 * Author: BitcoinP2P
 * Author URI: https://bitcoinp2p.com.br
 * Text Domain: analise-visitantes
 * 
 * Características:
 * - Visualizações de página sem rastreamento de visitantes
 * - Principais páginas e referências
 * - Sem uso de cookies
 * - Dados anônimos
 * - Interface simplificada
 * - Retenção de dados por apenas 30 dias
 * - Conformidade com LGPD/GDPR sem necessidade de consentimento
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Certificar que não há saída antes dos cabeçalhos
ob_start();

class Analise_Visitantes {
    
    // Construtor
    public function __construct() {
        // Hooks para inicializar o plugin
        add_action('init', array($this, 'inicializar_sessao'));
        
        // Usar diferentes hooks para registrar visitas para compatibilidade máxima
        add_action('wp_footer', array($this, 'registrar_visita_via_script'));
        add_action('wp_head', array($this, 'adicionar_noscript_tracker'));
        add_action('template_redirect', array($this, 'registrar_visita_direta'), 999);
        
        add_action('admin_menu', array($this, 'adicionar_menu_admin'));
        
        // Shortcode para exibir estatísticas no frontend
        add_shortcode('estatisticas_visitantes', array($this, 'shortcode_estatisticas'));
        
        // Registrar script e estilos
        add_action('admin_enqueue_scripts', array($this, 'registrar_scripts_admin'));
        add_action('wp_enqueue_scripts', array($this, 'registrar_scripts_frontend'));
        
        // Ativar o AJAX para contador de usuários online e rastreamento
        add_action('wp_ajax_atualizar_usuarios_online', array($this, 'atualizar_usuarios_online'));
        add_action('wp_ajax_nopriv_atualizar_usuarios_online', array($this, 'atualizar_usuarios_online'));
        add_action('wp_ajax_registrar_visita_ajax', array($this, 'registrar_visita_ajax'));
        add_action('wp_ajax_nopriv_registrar_visita_ajax', array($this, 'registrar_visita_ajax'));
        
        // AJAX para atualizar estatísticas com filtros
        add_action('wp_ajax_atualizar_estatisticas', array($this, 'atualizar_estatisticas_ajax'));
        
        // AJAX para análise em tempo real
        add_action('wp_ajax_atualizar_tempo_real', array($this, 'atualizar_tempo_real_ajax'));
        
        // Evento para resetar contadores diários à meia-noite (horário Brasil)
        add_action('reset_visitantes_diarios', array($this, 'reset_visitantes_diarios'));
        
        // Agendamento para limpar dados antigos e resetar contadores
        register_activation_hook(__FILE__, array($this, 'ativar_agendamento'));
        register_deactivation_hook(__FILE__, array($this, 'desativar_agendamento'));
        add_action('limpar_dados_antigos', array($this, 'limpar_dados_antigos'));
    }
    
    /**
     * AJAX para atualizar estatísticas com filtros de período e limite
     */
    public function atualizar_estatisticas_ajax() {
        // Verificar nonce
        check_ajax_referer('atualizar_estatisticas_nonce', 'nonce');
        
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissão negada');
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'analise_visitantes';
        
        // Obter parâmetros
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'today';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        
        // Definir datas de acordo com o período selecionado
        $data_fim = current_time('Y-m-d H:i:s');
        $data_inicio = current_time('Y-m-d') . ' 00:00:00'; // Hoje
        
        switch ($period) {
            case 'yesterday':
                $data_inicio = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $data_fim = date('Y-m-d 23:59:59', strtotime('-1 day'));
                break;
                
            case 'week':
                $data_inicio = date('Y-m-d 00:00:00', strtotime('monday this week'));
                break;
                
            case 'month':
                $data_inicio = date('Y-m-01 00:00:00');
                break;
                
            case 'last30':
                $data_inicio = date('Y-m-d 00:00:00', strtotime('-30 days'));
                break;
        }
        
        // Total de visitas no período
        $total_visitas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE date_time BETWEEN %s AND %s",
            $data_inicio, $data_fim
        ));
        
        // Origens das visitas
        $origens = $wpdb->get_results($wpdb->prepare(
            "SELECT referrer, COUNT(*) as count 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s 
             AND referrer != '' 
             GROUP BY referrer 
             ORDER BY count DESC 
             LIMIT %d",
            $data_inicio, $data_fim, $limit
        ));
        
        // Páginas mais visitadas
        $paginas = $wpdb->get_results($wpdb->prepare(
            "SELECT page_title, page_url, COUNT(*) as count 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s 
             GROUP BY page_url 
             ORDER BY count DESC 
             LIMIT %d",
            $data_inicio, $data_fim, $limit
        ));
        
        // Visitas por dia (para o gráfico)
        $visitas_por_dia = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(date_time) as dia, COUNT(*) as count 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s 
             GROUP BY dia 
             ORDER BY dia ASC",
            $data_inicio, $data_fim
        ));
        
        // Preparar dados para o gráfico
        $chart_labels = array();
        $chart_data = array();
        
        foreach ($visitas_por_dia as $visita) {
            $chart_labels[] = date('d/m', strtotime($visita->dia));
            $chart_data[] = $visita->count;
        }
        
        // Retornar resposta
        wp_send_json_success(array(
            'total_visitas' => $total_visitas,
            'origens' => $origens,
            'paginas' => $paginas,
            'chart_labels' => $chart_labels,
            'chart_data' => $chart_data
        ));
    }
    
    // Método que adiciona script para rastreamento via AJAX (para contornar caches)
    public function registrar_visita_via_script() {
        // Não adicionar em áreas admin
        if (is_admin()) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        (function() {
            var trackVisit = function() {
                if (typeof jQuery !== 'undefined') {
                    jQuery.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'registrar_visita_ajax',
                            page_id: <?php echo get_the_ID(); ?>,
                            page_title: '<?php echo esc_js(get_the_title()); ?>',
                            page_url: '<?php echo esc_js(get_permalink()); ?>',
                            referrer: document.referrer || '',
                            nonce: '<?php echo wp_create_nonce('registrar_visita_nonce'); ?>'
                        },
                        success: function(response) {
                            console.log('Visita registrada');
                        }
                    });
                }
            };
            
            // Executar após um pequeno delay para não atrasar o carregamento da página
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                setTimeout(trackVisit, 1000);
            } else {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(trackVisit, 1000);
                });
            }
        })();
        </script>
        <?php
    }
    
    // Fallback para navegadores sem JavaScript
    public function adicionar_noscript_tracker() {
        if (is_admin()) {
            return;
        }
        
        $img_url = add_query_arg(array(
            'action' => 'registrar_visita_ajax',
            'page_id' => get_the_ID(),
            'noscript' => '1',
            'rand' => mt_rand(1, 1000000) // Evitar cache
        ), admin_url('admin-ajax.php'));
        
        echo '<noscript><img src="' . esc_url($img_url) . '" alt="" style="position:absolute; visibility:hidden;" width="1" height="1" /></noscript>';
    }
    
    // Método para registrar visita via AJAX
    public function registrar_visita_ajax() {
        // Verificar se é solicitação noscript (via imagem)
        $noscript = isset($_GET['noscript']) && $_GET['noscript'] === '1';
        
        if ($noscript) {
            // Enviar uma imagem transparente de 1x1 pixel
            header('Content-Type: image/gif');
            echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        }
        
        // Registrar a visita
        $this->registrar_visita();
        
        if (!$noscript) {
            wp_send_json_success(array('status' => 'success'));
        }
        
        exit;
    }
    
    // Método para registrar visita diretamente (para usuários sem JavaScript)
    public function registrar_visita_direta() {
        // Não executar em solicitações AJAX ou admin
        if (wp_doing_ajax() || is_admin()) {
            return;
        }
        
        // Executar apenas uma vez por carregamento de página
        static $executed = false;
        if ($executed) {
            return;
        }
        $executed = true;
        
        // Verificar se o usuário tem JavaScript desativado ou plugin de cache
        if (!isset($_COOKIE['av_has_js'])) {
            // Registrar a visita
            $this->registrar_visita();
            
            // Definir cookie para verificar JavaScript na próxima vez
            setcookie('av_has_js', '1', time() + 86400, COOKIEPATH, COOKIE_DOMAIN, is_ssl());
        }
    }
    
    // Inicializar sessão para rastrear visitantes (sem cookies, estilo Statify)
    public function inicializar_sessao() {
        // Verifica se o PHP está rodando em CLI (linha de comando)
        if (php_sapi_name() === 'cli') {
            return;
        }
        
        // Não iniciar sessão para bots
        if ($this->is_bot()) {
            return;
        }
        
        // No estilo Statify, não usamos cookies ou sessões PHP para rastrear visitantes
        // Em vez disso, vamos usar um identificador temporário baseado em IP+User-Agent
        // que será usado apenas para evitar contagens duplicadas na mesma visita
        
        // Criar hash anônimo do visitante (não é armazenado em cookie)
        $visitor_hash = md5($this->obter_ip_visitante() . $_SERVER['HTTP_USER_AGENT'] . date('Y-m-d'));
        
        // Armazenamos esse hash apenas em transients do WordPress (temporários)
        // para evitar contagens duplicadas, mas sem comprometer a privacidade
        
        // Nota: Os logs de debug foram removidos para maior privacidade
    }
    
    // Registrar visita (estilo Statify - sem cookies)
    public function registrar_visita() {
        global $wpdb;
        
        // Ignorar bots e crawlers
        if ($this->is_bot()) {
            return;
        }
        
        // Ignorar usuários logados no admin, se necessário
        if (is_admin() && !isset($_GET['preview'])) {
            return;
        }
        
        // Definir timezone para Brasil
        date_default_timezone_set('America/Sao_Paulo');
        
        // Criar um hash anônimo para o visitante (não armazenado em cookies)
        $ip = $this->obter_ip_visitante();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $visitor_hash = md5($ip . $user_agent . date('Y-m-d'));
        
        // Obter a página atual
        $current_page = get_permalink();
        $page_id = get_the_ID();
        
        // Verificar se devemos registrar uma nova visita (evitar duplicatas)
        $should_record = true;
        
        // Criar uma chave única para esta visita específica (página + hash do visitante)
        $visit_key = 'statify_' . md5($current_page . $visitor_hash);
        
        // Verificar se já temos um registro dessa visita recente (últimos 30 minutos)
        if (get_transient($visit_key)) {
            $should_record = false;
        } else {
            // Marcar esta visita como registrada por 30 minutos
            set_transient($visit_key, 1, 30 * MINUTE_IN_SECONDS);
        }
        
        // Se não devemos registrar, apenas retornar
        if (!$should_record) {
            return;
        }
        
        // Incrementar contador de visitas diárias
        $this->incrementar_contador_diario();
        
        // Obter informações sobre a visita
        $page_title = get_the_title();
        $page_url = $current_page;
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $date_time = current_time('mysql'); // Usando a hora atual do WordPress
        
        // Obter informações do dispositivo
        $device_info = $this->detectar_dispositivo_navegador($user_agent);
        
        // Nome da tabela de visitas
        $table_name = $wpdb->prefix . 'analise_visitantes';
        
        // Criar a tabela se não existir
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $this->criar_tabelas();
        }
        
        // No estilo Statify, vamos armazenar menos dados para maior privacidade
        // e conformidade com regulamentações de proteção de dados
        $result = $wpdb->insert(
            $table_name,
            array(
                'visitor_id' => $visitor_hash, // Hash anônimo, não rastreável
                'page_id' => $page_id,
                'page_title' => $page_title,
                'page_url' => $page_url,
                'referrer' => $referrer,
                'ip' => '', // Não armazenamos o IP real para maior privacidade
                'user_agent' => $user_agent,
                'date_time' => $date_time,
                'country' => '', // Opcional: podemos eliminar dados geográficos para maior privacidade
                'city' => '',
                'latitude' => 0,
                'longitude' => 0,
                'device_type' => $device_info['device_type'],
                'browser' => $device_info['browser'],
                'operating_system' => $device_info['operating_system']
            )
        );
        
        // Verificar se o registro foi bem-sucedido
        if ($result) {
            // Atualizar estatísticas por dispositivo
            $this->atualizar_estatisticas_dispositivo(
                $device_info['device_type'],
                $device_info['browser'],
                $device_info['operating_system']
            );
        }
    }
    
    // Incrementar contador de visitas diárias
    private function incrementar_contador_diario() {
        // Verificar se é necessário inicializar o contador
        if (!get_option('av_visitas_hoje')) {
            update_option('av_visitas_hoje', 0);
            update_option('av_ultimo_reset', date('Y-m-d H:i:s'));
        }
        
        // Verificar a data do último reset
        $ultimo_reset = get_option('av_ultimo_reset');
        $data_reset = date('Y-m-d', strtotime($ultimo_reset));
        $data_atual = date('Y-m-d');
        
        // Se o último reset foi em um dia anterior, resetar contador
        if ($data_reset < $data_atual) {
            // Chamar o reset manualmente
            $this->reset_visitantes_diarios();
        }
        
        // Incrementar contador
        $visitas_hoje = get_option('av_visitas_hoje', 0);
        update_option('av_visitas_hoje', $visitas_hoje + 1);
    }
    
    // Verificar se é um bot/crawler
    private function is_bot() {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }
        
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $bot_patterns = array(
            'bot', 'spider', 'crawler', 'slurp', 'googlebot', 'bingbot', 'yandex', 'baidu',
            'facebookexternalhit', 'ahrefsbot', 'semrushbot', 'mj12bot', 'dotbot', 'seznambot',
            'rogerbot', 'exabot', 'msn', 'qwantify', 'screaming', 'pingdom', 'archive.org'
        );
        
        foreach ($bot_patterns as $pattern) {
            if (strpos($user_agent, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    // Obter IP do visitante
    private function obter_ip_visitante() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    
    // Obter informações geográficas do IP
    private function obter_info_geografica($ip) {
        // Evitar consultas para IPs locais
        if ($ip == '127.0.0.1' || $ip == '::1' || strpos($ip, '192.168.') === 0) {
            return array(
                'country' => 'Local',
                'country_code' => 'LO',
                'city' => 'Local',
                'latitude' => 0,
                'longitude' => 0
            );
        }
        
        // Cache das informações para evitar múltiplas requisições para o mesmo IP
        $cache_key = 'geoloc_' . md5($ip);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Tentativa de obter dados do IP via ipinfo.io (sem token para uso básico)
        $url = 'https://ipinfo.io/' . $ip . '/json';
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return array(
                'country' => 'Desconhecido',
                'country_code' => 'XX',
                'city' => 'Desconhecido',
                'latitude' => 0,
                'longitude' => 0
            );
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($data) || !isset($data['country'])) {
            return array(
                'country' => 'Desconhecido',
                'country_code' => 'XX',
                'city' => 'Desconhecido',
                'latitude' => 0,
                'longitude' => 0
            );
        }
        
        // Extrair coordenadas se disponíveis
        $coords = isset($data['loc']) ? explode(',', $data['loc']) : array(0, 0);
        
        $geo_data = array(
            'country' => isset($data['country']) ? $data['country'] : 'Desconhecido',
            'country_code' => isset($data['country']) ? $data['country'] : 'XX',
            'city' => isset($data['city']) ? $data['city'] : 'Desconhecido',
            'latitude' => $coords[0],
            'longitude' => isset($coords[1]) ? $coords[1] : 0
        );
        
        // Armazenar em cache por 7 dias
        set_transient($cache_key, $geo_data, 7 * DAY_IN_SECONDS);
        
        return $geo_data;
    }
    
    // Detectar dispositivo, navegador e sistema operacional
    private function detectar_dispositivo_navegador($user_agent) {
        $device_type = 'desktop';
        
        // Detectar dispositivo móvel
        if (preg_match('/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i', $user_agent)) {
            $device_type = 'mobile';
            
            // Verificar se é tablet
            if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $user_agent)) {
                $device_type = 'tablet';
            }
        }
        
        // Detectar navegador
        $browser = 'Outro';
        if (preg_match('/MSIE/i', $user_agent) || preg_match('/Trident/i', $user_agent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Edge/i', $user_agent)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/Firefox/i', $user_agent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari/i', $user_agent) && !preg_match('/Chrome/i', $user_agent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Chrome/i', $user_agent) && !preg_match('/OPR/i', $user_agent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Opera/i', $user_agent) || preg_match('/OPR/i', $user_agent)) {
            $browser = 'Opera';
        }
        
        // Detectar sistema operacional
        $os = 'Outro';
        if (preg_match('/windows|win32|win64/i', $user_agent)) {
            $os = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
            $os = 'macOS';
        } elseif (preg_match('/android/i', $user_agent)) {
            $os = 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $user_agent)) {
            $os = 'iOS';
        } elseif (preg_match('/linux/i', $user_agent)) {
            $os = 'Linux';
        }
        
        return array(
            'device_type' => $device_type,
            'browser' => $browser,
            'operating_system' => $os
        );
    }
    
    // Criar tabelas necessárias
    public function criar_tabelas() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabela de visitas
        $table_name = $wpdb->prefix . 'analise_visitantes';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            visitor_id varchar(255) NOT NULL,
            page_id mediumint(9) NOT NULL,
            page_title varchar(255) NOT NULL,
            page_url varchar(255) NOT NULL,
            referrer varchar(255) NOT NULL,
            ip varchar(100) NOT NULL,
            user_agent text NOT NULL,
            date_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            country varchar(100) DEFAULT '',
            city varchar(100) DEFAULT '',
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            device_type varchar(20) DEFAULT '',
            browser varchar(50) DEFAULT '',
            operating_system varchar(50) DEFAULT '',
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // Tabela de usuários online
        $table_online = $wpdb->prefix . 'analise_visitantes_online';
        $sql_online = "CREATE TABLE $table_online (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            visitor_id varchar(255) NOT NULL,
            ip varchar(100) NOT NULL,
            last_activity datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY visitor_id (visitor_id)
        ) $charset_collate;";
        
        // Tabela de estatísticas por país
        $table_country = $wpdb->prefix . 'analise_visitantes_paises';
        $sql_country = "CREATE TABLE $table_country (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            country_code varchar(2) NOT NULL,
            country_name varchar(100) NOT NULL,
            visits int(11) DEFAULT 0 NOT NULL,
            last_visit datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY country_code (country_code)
        ) $charset_collate;";
        
        // Tabela de estatísticas por dispositivo
        $table_device = $wpdb->prefix . 'analise_visitantes_dispositivos';
        $sql_device = "CREATE TABLE $table_device (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            device_type varchar(20) NOT NULL,
            browser varchar(50) NOT NULL,
            operating_system varchar(50) NOT NULL,
            visits int(11) DEFAULT 0 NOT NULL,
            last_visit datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY device_browser_os (device_type, browser, operating_system)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql_online);
        dbDelta($sql_country);
        dbDelta($sql_device);
    }
    
    // Atualizar usuários online
    public function atualizar_usuarios_online() {
        global $wpdb;
        
        // Limpar sessões expiradas (15 minutos)
        $table_online = $wpdb->prefix . 'analise_visitantes_online';
        $expiry_time = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        
        // Primeiro, verificar se a tabela existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_online'") != $table_online) {
            $this->criar_tabelas();
        }
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_online WHERE last_activity < %s",
            $expiry_time
        ));
        
        // Atualizar ou inserir o visitante atual
        if (isset($_SESSION['visitor_id'])) {
            $visitor_id = $_SESSION['visitor_id'];
            $ip = $this->obter_ip_visitante();
            $current_time = current_time('mysql');
            
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_online WHERE visitor_id = %s",
                $visitor_id
            ));
            
            if ($exists) {
                $wpdb->update(
                    $table_online,
                    array('last_activity' => $current_time),
                    array('visitor_id' => $visitor_id)
                );
            } else {
                $wpdb->insert(
                    $table_online,
                    array(
                        'visitor_id' => $visitor_id,
                        'ip' => $ip,
                        'last_activity' => $current_time
                    )
                );
            }
        }
        
        // Retornar contagem para AJAX
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_online");
            echo json_encode(array('count' => $count));
            wp_die();
        }
    }
    
    // Adicionar menu no painel administrativo
    public function adicionar_menu_admin() {
        add_menu_page(
            'Análise de Visitantes',
            'Análise de Visitantes',
            'manage_options',
            'analise-visitantes',
            array($this, 'pagina_admin'),
            'dashicons-chart-line',
            99
        );
        
        // Submenu para Mapa Geográfico
        add_submenu_page(
            'analise-visitantes',
            'Mapa Geográfico',
            'Mapa Geográfico',
            'manage_options',
            'analise-visitantes-mapa',
            array($this, 'pagina_mapa_geografico')
        );
        
        // Submenu para Análise de Dispositivos
        add_submenu_page(
            'analise-visitantes',
            'Dispositivos',
            'Dispositivos',
            'manage_options',
            'analise-visitantes-dispositivos',
            array($this, 'pagina_analise_dispositivos')
        );
        
        // Submenu para Comparação de Períodos
        add_submenu_page(
            'analise-visitantes',
            'Comparação de Períodos',
            'Comparação de Períodos',
            'manage_options',
            'analise-visitantes-comparacao',
            array($this, 'pagina_comparacao_periodos')
        );
        
        // Submenu para Links e Referências em Tempo Real
        add_submenu_page(
            'analise-visitantes',
            'Análise em Tempo Real',
            'Tempo Real',
            'manage_options',
            'analise-visitantes-tempo-real',
            array($this, 'pagina_tempo_real')
        );
    }
    
    // Página do painel administrativo
    public function pagina_admin() {
        global $wpdb;
        
        // Obter estatísticas
        $table_name = $wpdb->prefix . 'analise_visitantes';
        $table_online = $wpdb->prefix . 'analise_visitantes_online';
        
        // Verificar se as tabelas existem
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $this->criar_tabelas();
        }
        
        // Limpar sessões expiradas (15 minutos) para garantir contagem correta de usuários online
        $expiry_time = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_online'") == $table_online) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $table_online WHERE last_activity < %s",
                $expiry_time
            ));
        }
        
        // Obter período selecionado (default: hoje)
        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'today';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        
        // Definir datas de acordo com o período selecionado
        $data_fim = current_time('Y-m-d H:i:s');
        $data_inicio = current_time('Y-m-d') . ' 00:00:00'; // Hoje
        
        switch ($period) {
            case 'yesterday':
                $data_inicio = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $data_fim = date('Y-m-d 23:59:59', strtotime('-1 day'));
                break;
                
            case 'week':
                $data_inicio = date('Y-m-d 00:00:00', strtotime('monday this week'));
                break;
                
            case 'month':
                $data_inicio = date('Y-m-01 00:00:00');
                break;
                
            case 'last30':
                $data_inicio = date('Y-m-d 00:00:00', strtotime('-30 days'));
                break;
        }
        
        // Total de visitas no período selecionado
        $total_visitas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE date_time BETWEEN %s AND %s",
            $data_inicio, $data_fim
        ));
        
        // Total acumulado (histórico completo)
        $total_acumulado = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Usuários online
        $usuarios_online = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_online'") == $table_online) {
            $usuarios_online = $wpdb->get_var("SELECT COUNT(*) FROM $table_online");
        }
        
        // Páginas mais visitadas no período selecionado
        $paginas_populares = $wpdb->get_results($wpdb->prepare(
            "SELECT page_title, page_url, COUNT(*) as count 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s
             GROUP BY page_url 
             ORDER BY count DESC 
             LIMIT %d",
            $data_inicio, $data_fim, $limit
        ));
        
        // Origens das visitas no período selecionado
        $origens = $wpdb->get_results($wpdb->prepare(
            "SELECT referrer, COUNT(*) as count 
             FROM $table_name 
             WHERE referrer != '' AND date_time BETWEEN %s AND %s
             GROUP BY referrer 
             ORDER BY count DESC 
             LIMIT %d",
            $data_inicio, $data_fim, $limit
        ));
        
        // Visitas por dia (para o período selecionado)
        $visitas_por_dia = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(date_time) as dia, COUNT(*) as count 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s
             GROUP BY dia 
             ORDER BY dia ASC",
            $data_inicio, $data_fim
        ));
        
        // Se não houver dados para o período, criar um array vazio para evitar erro no gráfico
        if (empty($visitas_por_dia)) {
            $visitas_por_dia = array();
            
            // Definir o range de dias com base no período
            $dias = 1; // default: hoje
            
            switch ($period) {
                case 'week':
                    $dias = 7;
                    break;
                case 'month':
                    $dias = date('t'); // dias no mês atual
                    break;
                case 'last30':
                    $dias = 30;
                    break;
            }
            
            for ($i = 0; $i < $dias; $i++) {
                $dia = date('Y-m-d', strtotime("-$i days", strtotime($data_fim)));
                $visitas_por_dia[] = (object) array('dia' => $dia, 'count' => 0);
            }
            // Inverter array para ordem crescente de data
            $visitas_por_dia = array_reverse($visitas_por_dia);
        }
        
        // Exibir o painel
        include(plugin_dir_path(__FILE__) . 'views/admin-dashboard.php');
    }
    
    // Página do Mapa Geográfico
    public function pagina_mapa_geografico() {
        global $wpdb;
        
        // Estatísticas por país
        $table_country = $wpdb->prefix . 'analise_visitantes_paises';
        $paises = $wpdb->get_results(
            "SELECT * FROM $table_country ORDER BY visits DESC"
        );
        
        // Estatísticas por cidade
        $table_name = $wpdb->prefix . 'analise_visitantes';
        $cidades = $wpdb->get_results(
            "SELECT city, country, COUNT(*) as visits 
             FROM $table_name 
             WHERE city != '' AND city != 'Desconhecido' 
             GROUP BY city, country 
             ORDER BY visits DESC 
             LIMIT 20"
        );
        
        // Coordenadas para o mapa
        $localizacoes = $wpdb->get_results(
            "SELECT city, country, latitude, longitude, COUNT(*) as visits 
             FROM $table_name 
             WHERE latitude != 0 AND longitude != 0 
             GROUP BY city, country, latitude, longitude 
             ORDER BY visits DESC"
        );
        
        // Coordenadas por país (para círculos no mapa)
        $paises_loc = $wpdb->get_results(
            "SELECT c.country_name, c.visits, 
                    COALESCE(AVG(a.latitude), 0) as latitude, 
                    COALESCE(AVG(a.longitude), 0) as longitude
             FROM $table_country c
             LEFT JOIN $table_name a ON c.country_code = a.country
             GROUP BY c.country_name, c.visits
             HAVING latitude != 0 AND longitude != 0
             ORDER BY c.visits DESC"
        );
        
        // Carregar o template
        include(plugin_dir_path(__FILE__) . 'views/mapa-geografico.php');
    }
    
    // Página de Análise de Dispositivos
    public function pagina_analise_dispositivos() {
        global $wpdb;
        
        // Tabela de dispositivos
        $table_device = $wpdb->prefix . 'analise_visitantes_dispositivos';
        
        // Dispositivos detalhados
        $dispositivos = $wpdb->get_results(
            "SELECT * FROM $table_device ORDER BY visits DESC LIMIT 30"
        );
        
        // Resumo por tipo de dispositivo
        $dispositivos_resumo = $wpdb->get_results(
            "SELECT device_type, SUM(visits) as count 
             FROM $table_device 
             GROUP BY device_type 
             ORDER BY count DESC"
        );
        
        // Resumo por navegador
        $navegadores_resumo = $wpdb->get_results(
            "SELECT browser, SUM(visits) as count 
             FROM $table_device 
             GROUP BY browser 
             ORDER BY count DESC"
        );
        
        // Resumo por sistema operacional
        $os_resumo = $wpdb->get_results(
            "SELECT operating_system, SUM(visits) as count 
             FROM $table_device 
             GROUP BY operating_system 
             ORDER BY count DESC"
        );
        
        // Carregar o template
        include(plugin_dir_path(__FILE__) . 'views/analise-dispositivos.php');
    }
    
    // Página de Comparação de Períodos
    public function pagina_comparacao_periodos() {
        global $wpdb;
        
        // Datas padrão para análise (período atual vs período anterior)
        $data_fim = date('Y-m-d');
        $data_inicio = date('Y-m-d', strtotime('-30 days'));
        $data_inicio_anterior = date('Y-m-d', strtotime('-60 days'));
        $data_fim_anterior = date('Y-m-d', strtotime('-31 days'));
        
        // Verificar se há parâmetros de data na URL
        if (isset($_GET['data_inicio'], $_GET['data_fim'])) {
            $data_inicio = sanitize_text_field($_GET['data_inicio']);
            $data_fim = sanitize_text_field($_GET['data_fim']);
            
            // Calcular período anterior com a mesma duração
            $duracao = floor((strtotime($data_fim) - strtotime($data_inicio)) / 86400);
            $data_fim_anterior = date('Y-m-d', strtotime("$data_inicio -1 day"));
            $data_inicio_anterior = date('Y-m-d', strtotime("$data_inicio -" . ($duracao + 1) . " days"));
        }
        
        // Tabela de visitas
        $table_name = $wpdb->prefix . 'analise_visitantes';
        
        // Total de visitas dos períodos
        $total_periodo_atual = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE DATE(date_time) BETWEEN %s AND %s",
            $data_inicio, $data_fim
        ));
        
        $total_periodo_anterior = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE DATE(date_time) BETWEEN %s AND %s",
            $data_inicio_anterior, $data_fim_anterior
        ));
        
        // Calcular variação percentual
        $variacao_percentual = 0;
        if ($total_periodo_anterior > 0) {
            $variacao_percentual = (($total_periodo_atual - $total_periodo_anterior) / $total_periodo_anterior) * 100;
        }
        
        // Visitas por dia do período atual
        $visitas_por_dia_atual = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(date_time) as dia, COUNT(*) as count 
             FROM $table_name 
             WHERE DATE(date_time) BETWEEN %s AND %s 
             GROUP BY dia 
             ORDER BY dia ASC",
            $data_inicio, $data_fim
        ));
        
        // Visitas por dia do período anterior
        $visitas_por_dia_anterior = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(date_time) as dia, COUNT(*) as count 
             FROM $table_name 
             WHERE DATE(date_time) BETWEEN %s AND %s 
             GROUP BY dia 
             ORDER BY dia ASC",
            $data_inicio_anterior, $data_fim_anterior
        ));
        
        // Páginas mais visitadas em cada período
        $paginas_populares_atual = $wpdb->get_results($wpdb->prepare(
            "SELECT page_title, page_url, COUNT(*) as count 
             FROM $table_name 
             WHERE DATE(date_time) BETWEEN %s AND %s
             GROUP BY page_url 
             ORDER BY count DESC 
             LIMIT 10",
            $data_inicio, $data_fim
        ));
        
        $paginas_populares_anterior = $wpdb->get_results($wpdb->prepare(
            "SELECT page_title, page_url, COUNT(*) as count 
             FROM $table_name 
             WHERE DATE(date_time) BETWEEN %s AND %s
             GROUP BY page_url 
             ORDER BY count DESC 
             LIMIT 10",
            $data_inicio_anterior, $data_fim_anterior
        ));
        
        // Carregar o template
        include(plugin_dir_path(__FILE__) . 'views/comparacao-periodos.php');
    }
    
    // Página para análise em tempo real
    public function pagina_tempo_real() {
        global $wpdb;
        
        // Obter dados das últimas 24 horas
        $table_name = $wpdb->prefix . 'analise_visitantes';
        $table_online = $wpdb->prefix . 'analise_visitantes_online';
        
        // Últimas 24 horas
        $data_inicio = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $data_fim = current_time('Y-m-d H:i:s');
        
        // Usuários online agora
        $usuarios_online = $wpdb->get_var("SELECT COUNT(*) FROM $table_online");
        
        // Visitas das últimas 24 horas
        $visitas_24h = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE date_time BETWEEN %s AND %s",
            $data_inicio, $data_fim
        ));
        
        // Páginas mais visitadas nas últimas 24 horas
        $paginas_populares = $wpdb->get_results($wpdb->prepare(
            "SELECT page_title, page_url, COUNT(*) as count 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s
             GROUP BY page_url 
             ORDER BY count DESC 
             LIMIT 20",
            $data_inicio, $data_fim
        ));
        
        // Referências que mais trouxeram visitantes nas últimas 24 horas
        $principais_referrers = $wpdb->get_results($wpdb->prepare(
            "SELECT referrer, COUNT(*) as count 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s
             AND referrer != ''
             GROUP BY referrer 
             ORDER BY count DESC 
             LIMIT 20",
            $data_inicio, $data_fim
        ));
        
        // Atividade de visitas por hora
        $visitas_por_hora = $wpdb->get_results($wpdb->prepare(
            "SELECT HOUR(date_time) as hora, COUNT(*) as count 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s
             GROUP BY hora
             ORDER BY hora ASC",
            $data_inicio, $data_fim
        ));
        
        // Últimas visitas (em tempo real)
        $ultimas_visitas = $wpdb->get_results($wpdb->prepare(
            "SELECT page_title, page_url, referrer, country, city, device_type, browser, date_time
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s
             ORDER BY date_time DESC 
             LIMIT 30",
            $data_inicio, $data_fim
        ));
        
        // Páginas de saída (última página visitada antes de sair)
        $paginas_saida = $wpdb->get_results($wpdb->prepare(
            "SELECT t1.page_title, t1.page_url, COUNT(*) as count
             FROM $table_name t1
             LEFT JOIN $table_name t2 ON t1.visitor_id = t2.visitor_id AND t1.date_time < t2.date_time
             WHERE t1.date_time BETWEEN %s AND %s AND t2.id IS NULL
             GROUP BY t1.page_url
             ORDER BY count DESC
             LIMIT 10",
            $data_inicio, $data_fim
        ));
        
        // Links mais clicados (referências internas)
        $links_internos = $wpdb->get_results($wpdb->prepare(
            "SELECT t1.page_url as origem, t2.page_url as destino, COUNT(*) as count
             FROM $table_name t1
             JOIN $table_name t2 ON t1.visitor_id = t2.visitor_id
             WHERE t1.date_time BETWEEN %s AND %s
             AND t2.date_time BETWEEN %s AND %s
             AND t2.date_time > t1.date_time
             AND t1.date_time = (
                 SELECT MAX(t3.date_time)
                 FROM $table_name t3
                 WHERE t3.visitor_id = t1.visitor_id
                 AND t3.date_time < t2.date_time
             )
             GROUP BY origem, destino
             ORDER BY count DESC
             LIMIT 20",
            $data_inicio, $data_fim, $data_inicio, $data_fim
        ));
        
        // Carregar template
        include(plugin_dir_path(__FILE__) . 'views/analise-tempo-real.php');
    }
    
    // Registrar script e estilos para o painel administrativo
    public function registrar_scripts_admin($hook) {
        // Verificar se estamos em uma página do nosso plugin
        if (strpos($hook, 'analise-visitantes') === false) {
            return;
        }
        
        // Registrar e enfileirar o estilo
        wp_enqueue_style('analise-visitantes-admin-style', plugin_dir_url(__FILE__) . 'views/css/admin-style.css');
        
        // Registrar e enfileirar o script principal
        wp_enqueue_script('analise-visitantes-admin-script', plugin_dir_url(__FILE__) . 'views/js/admin-script.js', array('jquery'), '', true);
        
        // Passar dados para o script
        wp_localize_script('analise-visitantes-admin-script', 'analiseVisitantesData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url('admin.php'),
            'nonce' => wp_create_nonce('atualizar_estatisticas_nonce'),
            'pluginUrl' => plugin_dir_url(__FILE__),
            'currentPage' => isset($_GET['page']) ? $_GET['page'] : 'analise-visitantes'
        ));
        
        // Chart.js para gráficos
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js', array(), '3.7.0', true);
        
        // Leaflet.js para mapas geográficos (apenas na página de mapa)
        if (isset($_GET['page']) && $_GET['page'] == 'analise-visitantes-mapa') {
            wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.css');
            wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', array(), '1.7.1', true);
        }
        
        // Moment.js para exibir datas/horas em tempo real
        if (isset($_GET['page']) && $_GET['page'] == 'analise-visitantes-tempo-real') {
            wp_enqueue_script('moment-js', 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js', array(), '2.29.1', true);
            wp_enqueue_script('moment-timezone', 'https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.34/moment-timezone-with-data.min.js', array('moment-js'), '0.5.34', true);
            
            // Script específico para análise em tempo real
            wp_enqueue_script('analise-tempo-real', plugin_dir_url(__FILE__) . 'views/js/analise-tempo-real.js', array('jquery', 'moment-js'), '', true);
        }
    }
    
    // Registrar scripts e estilos para o frontend
    public function registrar_scripts_frontend() {
        // Verificar se é uma página ou post
        $page_id = 0;
        if (is_singular()) {
            $page_id = get_the_ID();
        }
        
        // Registrar e enfileirar estilos
        wp_register_style('analise-visitantes-frontend', plugin_dir_url(__FILE__) . 'views/css/frontend-style.css', array(), '1.1.0');
        wp_enqueue_style('analise-visitantes-frontend');
        
        // Registrar e enfileirar scripts
        wp_register_script('analise-visitantes-frontend', plugin_dir_url(__FILE__) . 'views/js/frontend-script.js', array('jquery'), '1.1.0', true);
        wp_enqueue_script('analise-visitantes-frontend');
        
        // Passar dados para o script
        wp_localize_script('analise-visitantes-frontend', 'analiseVisitantesData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'pageId' => $page_id,
            'nonce' => wp_create_nonce('analise_visitantes_nonce'),
            'siteUrl' => get_site_url(),
            'version' => '1.1'
        ));
    }
    
    // Resetar contador de visitas diárias à meia-noite (horário brasileiro)
    public function reset_visitantes_diarios() {
        global $wpdb;
        
        // Criar/atualizar registro para contador diário
        $table_stats = $wpdb->prefix . 'analise_visitantes_stats';
        
        // Verificar se a tabela existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_stats'") != $table_stats) {
            $this->criar_tabela_stats();
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
    
    // Criar tabela de estatísticas diárias
    public function criar_tabela_stats() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabela de estatísticas diárias
        $table_stats = $wpdb->prefix . 'analise_visitantes_stats';
        $sql_stats = "CREATE TABLE $table_stats (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            data date NOT NULL,
            visitas int(11) DEFAULT 0 NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY data (data)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_stats);
    }
    
    // Agendar limpeza de dados antigos e reset diário
    public function ativar_agendamento() {
        $this->criar_tabelas();
        $this->criar_tabela_stats();
        
        // Definir timezone para Brasil
        date_default_timezone_set('America/Sao_Paulo');
        
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
            
            // Inicializar contador se não existir
            if (!get_option('av_visitas_hoje')) {
                update_option('av_visitas_hoje', 0);
                update_option('av_ultimo_reset', date('Y-m-d H:i:s'));
            }
        }
    }
    
    // Desativar agendamento
    public function desativar_agendamento() {
        wp_clear_scheduled_hook('limpar_dados_antigos');
        wp_clear_scheduled_hook('reset_visitantes_diarios');
    }
    
    // Limpar dados antigos (mais de 30 dias, como o Statify)
    public function limpar_dados_antigos() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'analise_visitantes';
        $expiry_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE date_time < %s",
            $expiry_date
        ));
        
        // Limpar também os transients expirados
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_statify_%' AND option_value < " . time());
    }
    
    // Atualizar estatísticas por país
    private function atualizar_estatisticas_pais($country_code, $country_name) {
        global $wpdb;
        
        if (empty($country_code) || $country_code == 'XX') {
            return;
        }
        
        $table_country = $wpdb->prefix . 'analise_visitantes_paises';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_country WHERE country_code = %s",
            $country_code
        ));
        
        $date_time = current_time('mysql');
        
        if ($exists) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_country SET visits = visits + 1, last_visit = %s WHERE country_code = %s",
                $date_time, $country_code
            ));
        } else {
            $wpdb->insert(
                $table_country,
                array(
                    'country_code' => $country_code,
                    'country_name' => $country_name,
                    'visits' => 1,
                    'last_visit' => $date_time
                )
            );
        }
    }
    
    // Atualizar estatísticas por dispositivo
    private function atualizar_estatisticas_dispositivo($device_type, $browser, $os) {
        global $wpdb;
        
        $table_device = $wpdb->prefix . 'analise_visitantes_dispositivos';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_device WHERE device_type = %s AND browser = %s AND operating_system = %s",
            $device_type, $browser, $os
        ));
        
        $date_time = current_time('mysql');
        
        if ($exists) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_device SET visits = visits + 1, last_visit = %s 
                 WHERE device_type = %s AND browser = %s AND operating_system = %s",
                $date_time, $device_type, $browser, $os
            ));
        } else {
            $wpdb->insert(
                $table_device,
                array(
                    'device_type' => $device_type,
                    'browser' => $browser,
                    'operating_system' => $os,
                    'visits' => 1,
                    'last_visit' => $date_time
                )
            );
        }
    }
    
    // Função AJAX para atualizar dados em tempo real
    public function atualizar_tempo_real_ajax() {
        check_ajax_referer('atualizar_estatisticas_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'analise_visitantes';
        $table_online = $wpdb->prefix . 'analise_visitantes_online';
        
        // Usuários online
        $usuarios_online = $wpdb->get_var("SELECT COUNT(*) FROM $table_online");
        
        // Últimas 24 horas
        $data_inicio_24h = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $data_fim = current_time('Y-m-d H:i:s');
        
        // Visitas das últimas 24 horas
        $visitas_24h = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE date_time BETWEEN %s AND %s",
            $data_inicio_24h, $data_fim
        ));
        
        // Comparação com as 24 horas anteriores
        $data_inicio_anterior = date('Y-m-d H:i:s', strtotime('-48 hours'));
        $data_fim_anterior = $data_inicio_24h;
        
        $visitas_anterior = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE date_time BETWEEN %s AND %s",
            $data_inicio_anterior, $data_fim_anterior
        ));
        
        // Calcular tendência (percentual de variação)
        $tendencia = 0;
        if ($visitas_anterior > 0) {
            $tendencia = round((($visitas_24h - $visitas_anterior) / $visitas_anterior) * 100);
        }
        
        // Verificar se há um parâmetro para a última atividade conhecida
        $novas_visitas = array();
        if (isset($_POST['ultima_atividade']) && !empty($_POST['ultima_atividade'])) {
            $ultima_atividade = sanitize_text_field($_POST['ultima_atividade']);
            
            // Obter novas visitas desde a última atualização
            $novas = $wpdb->get_results($wpdb->prepare(
                "SELECT id, page_title, page_url, referrer, country, city, device_type, browser, date_time
                 FROM $table_name 
                 WHERE date_time > %s
                 ORDER BY date_time DESC 
                 LIMIT 10",
                $ultima_atividade
            ));
            
            if ($novas) {
                foreach ($novas as $visita) {
                    // Formatar referrer
                    $referrer_display = '';
                    if ($visita->referrer) {
                        $domain = parse_url($visita->referrer, PHP_URL_HOST);
                        $referrer_display = $domain ? $domain : 'Link externo';
                    } else {
                        $referrer_display = 'Acesso Direto';
                    }
                    
                    // Formatar localização
                    $location_display = '';
                    if ($visita->country && $visita->country != 'Desconhecido') {
                        $location_display = $visita->country;
                        if ($visita->city && $visita->city != 'Desconhecido') {
                            $location_display .= ' / ' . $visita->city;
                        }
                    } else {
                        $location_display = 'Localização indisponível';
                    }
                    
                    // Adicionar ao array
                    $novas_visitas[] = array(
                        'id' => $visita->id,
                        'page_title' => $visita->page_title,
                        'page_url' => $visita->page_url,
                        'referrer' => $referrer_display,
                        'country' => $visita->country,
                        'city' => $visita->city,
                        'location' => $location_display,
                        'device_type' => $visita->device_type,
                        'browser' => $visita->browser,
                        'date_time' => $visita->date_time,
                        'time_ago' => human_time_diff(strtotime($visita->date_time), current_time('timestamp')) . ' atrás'
                    );
                }
            }
        }
        
        // Retornar os dados
        wp_send_json_success(array(
            'usuarios_online' => $usuarios_online,
            'visitas_24h' => $visitas_24h,
            'tendencia' => $tendencia,
            'novas_visitas' => $novas_visitas
        ));
    }
}

/**
 * Cria os arquivos necessários durante a ativação
 */
function analise_visitantes_criar_arquivos() {
    // Criar diretórios se não existirem
    $plugin_dir = plugin_dir_path(__FILE__);
    
    if (!file_exists($plugin_dir . 'views')) {
        mkdir($plugin_dir . 'views', 0755, true);
    }
    
    if (!file_exists($plugin_dir . 'js')) {
        mkdir($plugin_dir . 'js', 0755, true);
    }
    
    if (!file_exists($plugin_dir . 'css')) {
        mkdir($plugin_dir . 'css', 0755, true);
    }
}

// Registrar função para ser executada durante a ativação
register_activation_hook(__FILE__, 'analise_visitantes_criar_arquivos');

// Inicializar o plugin
$analise_visitantes = new Analise_Visitantes();