<?php
/**
 * Classe de Detecção de Dispositivos do Plugin Análise de Visitantes
 * 
 * Responsável pela identificação de dispositivos, navegadores e sistemas operacionais
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class Analise_Visitantes_Devices {
    
    /**
     * Instância única da classe (Singleton)
     * @var Analise_Visitantes_Devices
     */
    private static $instance = null;
    
    /**
     * Obtém a instância única da classe
     * 
     * @return Analise_Visitantes_Devices
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
        // Nenhuma ação necessária por enquanto
    }
    
    /**
     * Detectar tipo de dispositivo, navegador e sistema operacional
     * 
     * @param string $user_agent
     * @return array
     */
    public function detect_device($user_agent) {
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
    
    /**
     * Obter estatísticas de dispositivos
     * 
     * @param string $start_date Data inicial
     * @param string $end_date Data final
     * @return array
     */
    public function get_device_stats($start_date, $end_date) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'analise_visitantes';
        
        // Estatísticas por tipo de dispositivo
        $device_types = $wpdb->get_results($wpdb->prepare(
            "SELECT device_type, COUNT(*) as count 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s 
             GROUP BY device_type 
             ORDER BY count DESC",
            $start_date, $end_date
        ), ARRAY_A);
        
        // Estatísticas por navegador
        $browsers = $wpdb->get_results($wpdb->prepare(
            "SELECT browser, COUNT(*) as count 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s 
             GROUP BY browser 
             ORDER BY count DESC 
             LIMIT 10",
            $start_date, $end_date
        ), ARRAY_A);
        
        // Estatísticas por sistema operacional
        $os = $wpdb->get_results($wpdb->prepare(
            "SELECT operating_system, COUNT(*) as count 
             FROM $table_name 
             WHERE date_time BETWEEN %s AND %s 
             GROUP BY operating_system 
             ORDER BY count DESC 
             LIMIT 10",
            $start_date, $end_date
        ), ARRAY_A);
        
        return array(
            'device_types' => $device_types ?: array(),
            'browsers' => $browsers ?: array(),
            'operating_systems' => $os ?: array()
        );
    }
} 