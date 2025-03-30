<?php
/**
 * Classe de Rastreamento do Plugin Análise de Visitantes
 * 
 * Responsável pelo rastreamento e registro de visitas no site
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class Analise_Visitantes_Tracking {
    
    /**
     * Instância única da classe (Singleton)
     * @var Analise_Visitantes_Tracking
     */
    private static $instance = null;
    
    /**
     * Obtém a instância única da classe
     * 
     * @return Analise_Visitantes_Tracking
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Construtor - inicializa o rastreamento
     */
    private function __construct() {
        $this->setup_actions();
    }
    
    /**
     * Configurar ações de rastreamento
     */
    private function setup_actions() {
        // Usar diferentes hooks para registrar visitas para compatibilidade máxima
        add_action('wp_footer', array($this, 'enqueue_tracking_script'));
        add_action('wp_head', array($this, 'add_noscript_tracker'));
        add_action('template_redirect', array($this, 'track_visit_direct'), 999);
        
        // Ativar o AJAX para rastreamento e usuários online
        add_action('wp_ajax_atualizar_usuarios_online', array($this, 'update_users_online'));
        add_action('wp_ajax_nopriv_atualizar_usuarios_online', array($this, 'update_users_online'));
        add_action('wp_ajax_registrar_visita_ajax', array($this, 'track_visit_ajax'));
        add_action('wp_ajax_nopriv_registrar_visita_ajax', array($this, 'track_visit_ajax'));
        add_action('wp_ajax_verificar_cache', array($this, 'check_cache'));
        add_action('wp_ajax_nopriv_verificar_cache', array($this, 'check_cache'));
    }
    
    /**
     * Adicionar script de rastreamento no rodapé
     */
    public function enqueue_tracking_script() {
        // Verificar se não estamos em área de administração
        if (is_admin()) {
            return;
        }
        
        // Verificar se devemos rastrear administradores
        if (current_user_can('manage_options') && !get_option('av_rastrear_admin', false)) {
            return;
        }
        
        // Criar nonce para segurança
        $nonce = wp_create_nonce('registrar_visita_nonce');
        
        // Script otimizado para não bloquear carregamento da página
        ?>
        <script type="text/javascript">
        (function() {
            function trackPageview() {
                if (typeof jQuery !== 'undefined') {
                    // Use requestIdleCallback para não afetar a performance da página
                    var trackVisit = function() {
                        jQuery.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'registrar_visita_ajax',
                                page_id: <?php echo get_the_ID(); ?>,
                                page_title: '<?php echo esc_js(get_the_title()); ?>',
                                page_url: '<?php echo esc_js(get_permalink()); ?>',
                                referrer: document.referrer || '',
                                nonce: '<?php echo $nonce; ?>'
                            },
                            success: function(response) {
                                console.debug('Visita registrada');
                            }
                        });
                    };
                    
                    // Verificar se cache está ativo
                    var verificarCache = function() {
                        var cacheTeste = localStorage.getItem('av_cache_test');
                        var agora = new Date().getTime();
                        
                        if (!cacheTeste || (agora - cacheTeste) > 60000) {
                            localStorage.setItem('av_cache_test', agora);
                            
                            jQuery.ajax({
                                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                method: 'POST',
                                data: {
                                    action: 'verificar_cache',
                                    nonce: '<?php echo $nonce; ?>',
                                    cache_test: agora
                                }
                            }).done(function(response) {
                                if (response.cache_detectado) {
                                    localStorage.setItem('av_modo_cache', '1');
                                }
                            });
                        }
                    };
                    
                    // Usar requestIdleCallback quando disponível
                    if ('requestIdleCallback' in window) {
                        requestIdleCallback(trackVisit, { timeout: 2000 });
                        requestIdleCallback(verificarCache, { timeout: 3000 });
                    } else {
                        setTimeout(trackVisit, 1000);
                        setTimeout(verificarCache, 1500);
                    }
                    
                    // Usar navigator.sendBeacon para capturar saídas
                    if ('sendBeacon' in navigator) {
                        window.addEventListener('beforeunload', function() {
                            var data = new FormData();
                            data.append('action', 'registrar_visita_ajax');
                            data.append('page_id', <?php echo get_the_ID(); ?>);
                            data.append('page_title', '<?php echo esc_js(get_the_title()); ?>');
                            data.append('page_url', '<?php echo esc_js(get_permalink()); ?>');
                            data.append('referrer', document.referrer || '');
                            data.append('nonce', '<?php echo $nonce; ?>');
                            data.append('event', 'exit');
                            
                            navigator.sendBeacon('<?php echo admin_url('admin-ajax.php'); ?>', data);
                        });
                    }
                }
            }
            
            // Executar após carregamento completo sem bloquear
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                setTimeout(trackPageview, 100);
            } else {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(trackPageview, 100);
                });
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Adicionar tracker para navegadores sem JavaScript
     */
    public function add_noscript_tracker() {
        if (is_admin()) {
            return;
        }
        
        // Verificar se devemos rastrear administradores
        if (current_user_can('manage_options') && !get_option('av_rastrear_admin', false)) {
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
    
    /**
     * Método para registrar visita via AJAX
     */
    public function track_visit_ajax() {
        // Verificar se é solicitação noscript (via imagem)
        $noscript = isset($_GET['noscript']) && $_GET['noscript'] === '1';
        
        if ($noscript) {
            // Enviar uma imagem transparente de 1x1 pixel
            header('Content-Type: image/gif');
            echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        }
        
        // Registrar a visita
        $this->track_visit();
        
        if (!$noscript) {
            wp_send_json_success(array('status' => 'success'));
        }
        
        exit;
    }
    
    /**
     * Método para registrar visita diretamente (para usuários sem JavaScript)
     */
    public function track_visit_direct() {
        // Verificar se devemos rastrear administradores
        if (is_admin() || (current_user_can('manage_options') && !get_option('av_rastrear_admin', false))) {
            return;
        }
        
        // Verificar se a página atual é uma página real e não um feed, sitemap, etc.
        if (is_feed() || is_robots() || is_trackback() || is_preview()) {
            return;
        }
        
        // Obter a requisição atual
        $request = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        
        // Ignorar requisições para arquivos
        if (preg_match('#\.(ico|jpg|jpeg|png|gif|js|css|svg)(\?.*)?$#', $request)) {
            return;
        }
        
        // Verificar se o visitor_id já existe na sessão para evitar dupla contagem
        if (isset($_SESSION['av_tracked']) && $_SESSION['av_tracked'] === true) {
            return;
        }
        
        // Rastrear a visita
        $this->track_visit();
        
        // Marcar como rastreado nesta sessão
        $_SESSION['av_tracked'] = true;
    }
    
    /**
     * Verificar se o cache está ativo
     */
    public function check_cache() {
        // Verificar nonce
        check_ajax_referer('registrar_visita_nonce', 'nonce');
        
        $cache_detectado = false;
        
        // Verificar se o parâmetro de teste está presente
        if (isset($_POST['cache_test'])) {
            $cache_test = sanitize_text_field($_POST['cache_test']);
            
            // Armazenar o valor recebido na opção do WordPress
            $last_check = get_option('av_cache_check', '');
            
            // Se o valor for diferente do último verificado, não há cache
            if ($last_check === $cache_test) {
                $cache_detectado = true;
            }
            
            // Atualizar o valor para próxima verificação
            update_option('av_cache_check', $cache_test);
        }
        
        wp_send_json(array(
            'cache_detectado' => $cache_detectado
        ));
    }
    
    /**
     * Registrar visita (método principal otimizado)
     */
    public function track_visit() {
        global $wpdb;
        
        // Ignorar bots e crawlers se configurado
        if (get_option('av_ignorar_bots', true) && $this->is_bot()) {
            return;
        }
        
        // Ignorar usuários logados no admin, se necessário
        if (is_admin() && !isset($_GET['preview'])) {
            return;
        }
        
        // Atualizar contador de visitas online
        $this->update_online_counter();
        
        // Definir timezone para Brasil
        date_default_timezone_set('America/Sao_Paulo');
        
        // Obter informações sobre o visitante
        $ip = $this->get_visitor_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $visitor_hash = md5($ip . $user_agent . date('Y-m-d'));
        
        // Obter a página atual
        $current_page = isset($_POST['page_url']) ? sanitize_text_field($_POST['page_url']) : get_permalink();
        $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : get_the_ID();
        $page_title = isset($_POST['page_title']) ? sanitize_text_field($_POST['page_title']) : get_the_title();
        $referrer = isset($_POST['referrer']) ? sanitize_text_field($_POST['referrer']) : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
        
        // Verificar se é uma visita de saída (beforeunload)
        $is_exit = isset($_POST['event']) && $_POST['event'] === 'exit';
        
        // Criar uma chave única para esta visita específica (página + hash do visitante)
        $visit_key = 'av_visit_' . md5($current_page . $visitor_hash);
        
        // Verificar se já temos um registro dessa visita recente (últimos 30 minutos)
        // Ignorar esta verificação para eventos de saída para rastrear caminhos no site
        $should_record = true;
        if (!$is_exit && get_transient($visit_key)) {
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
        $this->increment_daily_counter();
        
        // Obter data atual
        $date_time = current_time('mysql');
        
        // Obter informações do dispositivo
        $device_info = $this->detect_device_browser($user_agent);
        
        // Nome da tabela de visitas
        $table_name = $wpdb->prefix . 'analise_visitantes';
        
        // Verificar se a tabela existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            // Se a tabela não existir, recrear as tabelas
            $core = Analise_Visitantes_Core::get_instance();
            $core->create_tables();
        }
        
        // Verificar se geolocalização está ativada
        $geo_info = array(
            'country' => '',
            'country_code' => '',
            'city' => '',
            'latitude' => 0,
            'longitude' => 0
        );
        
        if (get_option('av_geo_tracking', true)) {
            // Obter informações geográficas usando a classe Geolocation
            $geo_class = Analise_Visitantes_Geolocation::get_instance();
            $geo_info = $geo_class->get_geo_info($ip);
        }
        
        // Preparar dados básicos para inserção
        $data = array(
            'visitor_id' => $visitor_hash,
            'page_id' => $page_id,
            'page_title' => $page_title,
            'page_url' => $current_page,
            'referrer' => $referrer,
            'ip' => '', // Não armazenamos o IP real para maior privacidade
            'user_agent' => $user_agent,
            'date_time' => $date_time,
            'device_type' => $device_info['device_type'],
            'browser' => $device_info['browser'],
            'operating_system' => $device_info['operating_system']
        );
        
        // Verificar se as colunas de geolocalização existem
        $country_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'country'");
        
        // Adicionar dados de geolocalização apenas se as colunas existirem
        if ($country_exists) {
            $data['country'] = $geo_info['country_code'];
            $data['city'] = $geo_info['city'];
            $data['latitude'] = $geo_info['latitude'];
            $data['longitude'] = $geo_info['longitude'];
        }
        
        // Inserir dados de forma segura 
        try {
            $result = $wpdb->insert($table_name, $data);
            
            // Se o registro foi bem-sucedido, atualizar estatísticas agregadas
            if ($result) {
                // Atualizar estatísticas por país
                if (!empty($geo_info['country_code']) && $geo_info['country_code'] != 'XX') {
                    $this->update_country_stats($geo_info['country_code'], $geo_info['country']);
                }
                
                // Atualizar estatísticas por dispositivo
                $this->update_device_stats(
                    $device_info['device_type'],
                    $device_info['browser'],
                    $device_info['operating_system']
                );
                
                // Atualizar tabela de agregação para relatórios rápidos
                $this->update_aggregated_stats($current_page, $device_info);
            }
        } catch (Exception $e) {
            // Registrar erro no log do WordPress para debug
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Erro ao registrar visita: ' . $e->getMessage());
            }
            
            // Tentar corrigir tabela e tentar novamente
            $core = Analise_Visitantes_Core::get_instance();
            $core->create_tables();
        }
    }
    
    /**
     * Incrementar contador de visitas diárias (método otimizado)
     */
    private function increment_daily_counter() {
        // Usar opção direta no WordPress sem transients
        $visitas_hoje = get_option('av_visitas_hoje', 0);
        $visitas_hoje++;
        
        // Verificar a data do último reset
        $ultimo_reset = get_option('av_ultimo_reset');
        $data_reset = date('Y-m-d', strtotime($ultimo_reset));
        $data_atual = date('Y-m-d');
        
        // Se o último reset foi em um dia anterior, resetar contador
        if ($data_reset < $data_atual) {
            // Chamar a instância principal para resetar contadores
            $core = Analise_Visitantes_Core::get_instance();
            $core->reset_daily_visitors();
            
            // Iniciar um novo contador para hoje
            $visitas_hoje = 1;
        }
        
        update_option('av_visitas_hoje', $visitas_hoje);
    }
    
    /**
     * Atualizar contadores de visitantes online
     */
    public function update_online_counter() {
        global $wpdb;
        
        // Tabela de visitantes online
        $table_online = $wpdb->prefix . 'analise_visitantes_online';
        
        // Verificar se a tabela existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_online'") != $table_online) {
            // Criar tabela se não existir
            $core = Analise_Visitantes_Core::get_instance();
            $core->create_tables();
        }
        
        // Obter ID da sessão atual
        if (!session_id()) {
            session_start();
        }
        $session_id = session_id();
        
        // Obter dados do visitante
        $ip = $this->get_visitor_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $current_page = isset($_POST['page_url']) ? sanitize_text_field($_POST['page_url']) : (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
        $date_time = current_time('mysql');
        
        // Limpar sessões expiradas (15 minutos)
        $expiry_time = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_online WHERE last_activity < %s",
            $expiry_time
        ));
        
        // Verificar se a sessão já existe
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_online WHERE session_id = %s",
            $session_id
        ));
        
        if ($exists) {
            // Atualizar registro existente
            $wpdb->update(
                $table_online,
                array(
                    'page_url' => $current_page,
                    'last_activity' => $date_time
                ),
                array('session_id' => $session_id)
            );
        } else {
            // Inserir novo registro
            $wpdb->insert(
                $table_online,
                array(
                    'session_id' => $session_id,
                    'ip' => $ip,
                    'page_url' => $current_page,
                    'last_activity' => $date_time,
                    'user_agent' => $user_agent
                )
            );
        }
        
        // Se for requisição AJAX para atualização de contagem
        if (isset($_POST['action']) && $_POST['action'] === 'atualizar_usuarios_online') {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_online");
            echo json_encode(array('count' => $count));
            exit;
        }
    }
    
    /**
     * Atualizar estatísticas por país
     * 
     * @param string $country_code Código do país
     * @param string $country_name Nome do país
     */
    private function update_country_stats($country_code, $country_name) {
        global $wpdb;
        $table_country = $wpdb->prefix . 'analise_visitantes_paises';
        
        // Verificar se o país já existe
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_country WHERE country_code = %s",
            $country_code
        ));
        
        $date_time = current_time('mysql');
        
        if ($exists) {
            // Atualizar contagem
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_country SET visits = visits + 1, last_visit = %s WHERE country_code = %s",
                $date_time, $country_code
            ));
        } else {
            // Inserir novo país
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
    
    /**
     * Atualizar estatísticas por dispositivo
     * 
     * @param string $device_type Tipo de dispositivo
     * @param string $browser Navegador
     * @param string $os Sistema operacional
     */
    private function update_device_stats($device_type, $browser, $os) {
        global $wpdb;
        $table_device = $wpdb->prefix . 'analise_visitantes_dispositivos';
        
        // Verificar se a combinação já existe
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_device 
             WHERE device_type = %s AND browser = %s AND operating_system = %s",
            $device_type, $browser, $os
        ));
        
        $date_time = current_time('mysql');
        
        if ($exists) {
            // Atualizar contagem
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_device SET visits = visits + 1, last_visit = %s 
                 WHERE device_type = %s AND browser = %s AND operating_system = %s",
                $date_time, $device_type, $browser, $os
            ));
        } else {
            // Inserir novo registro
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
    
    /**
     * Atualizar tabela de agregação para relatórios rápidos
     * 
     * @param string $page_url URL da página
     * @param array $device_info Informações do dispositivo
     */
    private function update_aggregated_stats($page_url, $device_info) {
        global $wpdb;
        $table_agregado = $wpdb->prefix . 'analise_visitantes_agregado';
        
        // Data atual
        $data_hoje = date('Y-m-d');
        
        // Verificar se já existe registro para esta página hoje
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_agregado WHERE data = %s AND page_url = %s",
            $data_hoje, $page_url
        ));
        
        if ($exists) {
            // Atualizar contagem e dispositivos
            $current_data = $wpdb->get_var($wpdb->prepare(
                "SELECT dispositivos_json FROM $table_agregado WHERE id = %d",
                $exists
            ));
            
            $devices_array = json_decode($current_data, true) ?: array();
            $device_key = $device_info['device_type'] . '|' . $device_info['browser'];
            
            if (isset($devices_array[$device_key])) {
                $devices_array[$device_key]++;
            } else {
                $devices_array[$device_key] = 1;
            }
            
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_agregado SET visualizacoes = visualizacoes + 1, dispositivos_json = %s WHERE id = %d",
                json_encode($devices_array), $exists
            ));
        } else {
            // Criar novo registro
            $devices_array = array(
                $device_info['device_type'] . '|' . $device_info['browser'] => 1
            );
            
            $wpdb->insert(
                $table_agregado,
                array(
                    'data' => $data_hoje,
                    'page_url' => $page_url,
                    'visualizacoes' => 1,
                    'dispositivos_json' => json_encode($devices_array)
                )
            );
        }
    }
    
    /**
     * Verificar se é um bot/crawler
     * 
     * @return bool
     */
    private function is_bot() {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }
        
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $bot_patterns = array(
            'bot', 'spider', 'crawler', 'slurp', 'googlebot', 'bingbot', 'yandex', 'baidu',
            'facebookexternalhit', 'ahrefsbot', 'semrushbot', 'mj12bot', 'dotbot', 'seznambot',
            'rogerbot', 'exabot', 'msn', 'qwantify', 'screaming', 'pingdom', 'archive.org',
            'applebot', 'twitterbot', 'whatsapp', 'telegram', 'ia_archiver', 'baiduspider',
            'duckduckbot', 'teoma', 'sogou', 'daum', 'jabse.com', 'webcrawler', 'nutch',
            'pinterest', 'feedly', 'bitlybot', 'mastodon', 'x.com', 'twttr'
        );
        
        foreach ($bot_patterns as $pattern) {
            if (strpos($user_agent, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Obter IP do visitante
     * 
     * @return string
     */
    private function get_visitor_ip() {
        $ip = '';
        
        // Vários cabeçalhos possíveis para obter o IP real
        $ip_headers = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                break;
            }
        }
        
        return $ip;
    }
    
    /**
     * Detectar tipo de dispositivo, navegador e sistema operacional
     * 
     * @param string $user_agent
     * @return array
     */
    private function detect_device_browser($user_agent) {
        // Cache para evitar processamento repetitivo
        static $cache = array();
        
        // Usar hash do user-agent como chave de cache
        $ua_hash = md5($user_agent);
        
        if (isset($cache[$ua_hash])) {
            return $cache[$ua_hash];
        }
        
        // Dispositivo (mobile, tablet, desktop)
        $device_type = 'desktop';
        
        $mobile_patterns = array(
            'mobile', 'android', 'iphone', 'ipod', 'windows phone', 'blackberry', 
            'webos', 'opera mini', 'opera mobi', 'palmos', 'midp', 'wap browser', 
            'symbian', 'j2me', 'smartphone', 'phone'
        );
        
        $tablet_patterns = array(
            'ipad', 'android(?!.*mobile)', 'tablet', 'kindle', 'playbook', 'galaxy tab', 
            'nexus 7', 'nexus 10', 'huawei mediapad', 'surface'
        );
        
        $user_agent_lower = strtolower($user_agent);
        
        foreach ($tablet_patterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $user_agent_lower)) {
                $device_type = 'tablet';
                break;
            }
        }
        
        if ($device_type === 'desktop') {
            foreach ($mobile_patterns as $pattern) {
                if (strpos($user_agent_lower, $pattern) !== false) {
                    $device_type = 'mobile';
                    break;
                }
            }
        }
        
        // Navegador
        $browser = 'Outro';
        
        if (preg_match('/MSIE|Trident/i', $user_agent)) {
            $browser = 'Internet Explorer';
            if (preg_match('/rv:11/i', $user_agent)) {
                $browser = 'Internet Explorer 11';
            } else if (preg_match('/MSIE (\d+)/i', $user_agent, $matches)) {
                $browser = 'Internet Explorer ' . $matches[1];
            }
        } else if (preg_match('/Edg/i', $user_agent)) {
            $browser = 'Microsoft Edge';
        } else if (preg_match('/Firefox/i', $user_agent)) {
            $browser = 'Firefox';
        } else if (preg_match('/Chrome/i', $user_agent) && !preg_match('/Chromium|OPR|Edge/i', $user_agent)) {
            $browser = 'Chrome';
        } else if (preg_match('/Safari/i', $user_agent) && !preg_match('/Chrome|Chromium|OPR|Edge/i', $user_agent)) {
            $browser = 'Safari';
        } else if (preg_match('/Opera|OPR/i', $user_agent)) {
            $browser = 'Opera';
        } else if (preg_match('/Samsung/i', $user_agent)) {
            $browser = 'Samsung Internet';
        } else if (preg_match('/UCBrowser/i', $user_agent)) {
            $browser = 'UC Browser';
        }
        
        // Sistema operacional
        $os = 'Outro';
        
        if (preg_match('/Windows NT 10/i', $user_agent)) {
            $os = 'Windows 10';
        } else if (preg_match('/Windows NT 6.3/i', $user_agent)) {
            $os = 'Windows 8.1';
        } else if (preg_match('/Windows NT 6.2/i', $user_agent)) {
            $os = 'Windows 8';
        } else if (preg_match('/Windows NT 6.1/i', $user_agent)) {
            $os = 'Windows 7';
        } else if (preg_match('/Windows NT 6.0/i', $user_agent)) {
            $os = 'Windows Vista';
        } else if (preg_match('/Windows NT 5.1/i', $user_agent)) {
            $os = 'Windows XP';
        } else if (preg_match('/Windows NT/i', $user_agent)) {
            $os = 'Windows NT';
        } else if (preg_match('/Mac OS X/i', $user_agent)) {
            if (preg_match('/iPhone|iPad|iPod/i', $user_agent)) {
                $os = 'iOS';
            } else {
                $os = 'macOS';
            }
        } else if (preg_match('/Linux/i', $user_agent)) {
            if (preg_match('/Android/i', $user_agent)) {
                $os = 'Android';
            } else {
                $os = 'Linux';
            }
        } else if (preg_match('/Ubuntu/i', $user_agent)) {
            $os = 'Ubuntu';
        } else if (preg_match('/CrOS/i', $user_agent)) {
            $os = 'Chrome OS';
        }
        
        // Armazenar resultado no cache
        $result = array(
            'device_type' => $device_type,
            'browser' => $browser,
            'operating_system' => $os
        );
        
        $cache[$ua_hash] = $result;
        
        return $result;
    }
} 