<?php
/**
 * Classe de Geolocalização do Plugin Análise de Visitantes
 * 
 * Responsável por obter informações geográficas dos visitantes
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class Analise_Visitantes_Geolocation {
    
    /**
     * Instância única da classe (Singleton)
     * @var Analise_Visitantes_Geolocation
     */
    private static $instance = null;
    
    /**
     * URL da API de Geolocalização
     * @var string
     */
    private $api_url = 'https://ipapi.co/%s/json/';
    
    /**
     * API alternativa para geolocalização
     * @var string
     */
    private $api_fallback = 'https://ip-api.com/json/%s';
    
    /**
     * Obtém a instância única da classe
     * 
     * @return Analise_Visitantes_Geolocation
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
    private function __construct() {}
    
    /**
     * Obter informações geográficas baseadas no IP
     * 
     * @param string $ip Endereço IP
     * @return array Informações geográficas
     */
    public function get_geo_info($ip) {
        // Informações padrão caso não seja possível obter dados
        $geo_info = array(
            'country' => '',
            'country_code' => '',
            'city' => '',
            'latitude' => 0,
            'longitude' => 0
        );
        
        // Verificar se o IP é válido
        if (empty($ip) || $ip == '127.0.0.1' || $ip == 'localhost' || strpos($ip, '192.168.') === 0) {
            return $geo_info;
        }
        
        // Verificar cache para evitar múltiplas requisições
        $cache_key = 'av_geo_' . md5($ip);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Tentar obter dados da primeira API
        $geo_data = $this->fetch_from_api(sprintf($this->api_url, $ip));
        
        // Se falhar, tentar API alternativa
        if (empty($geo_data) || isset($geo_data['error'])) {
            $geo_data = $this->fetch_from_api(sprintf($this->api_fallback, $ip));
        }
        
        // Se ambas APIs falharem, usar banco de dados local
        if (empty($geo_data) || isset($geo_data['error'])) {
            $geo_data = $this->get_from_local_db($ip);
        }
        
        // Se obtiver dados, preencher array de retorno
        if (!empty($geo_data)) {
            // API ipapi.co
            if (isset($geo_data['country_name']) && isset($geo_data['country_code'])) {
                $geo_info['country'] = sanitize_text_field($geo_data['country_name']);
                $geo_info['country_code'] = sanitize_text_field($geo_data['country_code']);
                $geo_info['city'] = isset($geo_data['city']) ? sanitize_text_field($geo_data['city']) : '';
                $geo_info['latitude'] = isset($geo_data['latitude']) ? floatval($geo_data['latitude']) : 0;
                $geo_info['longitude'] = isset($geo_data['longitude']) ? floatval($geo_data['longitude']) : 0;
            } 
            // API ip-api.com
            else if (isset($geo_data['country']) && isset($geo_data['countryCode'])) {
                $geo_info['country'] = sanitize_text_field($geo_data['country']);
                $geo_info['country_code'] = sanitize_text_field($geo_data['countryCode']);
                $geo_info['city'] = isset($geo_data['city']) ? sanitize_text_field($geo_data['city']) : '';
                $geo_info['latitude'] = isset($geo_data['lat']) ? floatval($geo_data['lat']) : 0;
                $geo_info['longitude'] = isset($geo_data['lon']) ? floatval($geo_data['lon']) : 0;
            }
        }
        
        // Armazenar em cache por 7 dias para evitar múltiplas chamadas API
        set_transient($cache_key, $geo_info, 7 * DAY_IN_SECONDS);
        
        return $geo_info;
    }
    
    /**
     * Buscar dados de uma API de geolocalização
     * 
     * @param string $url URL da API
     * @return array|bool Dados da API ou false em caso de falha
     */
    private function fetch_from_api($url) {
        $response = wp_remote_get($url, array(
            'timeout' => 5,
            'headers' => array(
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
            )
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data) || !is_array($data)) {
            return false;
        }
        
        return $data;
    }
    
    /**
     * Obter dados de geolocalização do banco de dados local (fallback)
     * 
     * @param string $ip Endereço IP
     * @return array|bool Dados de geolocalização ou false
     */
    private function get_from_local_db($ip) {
        // Implementação básica usando banco de dados local simplificado
        // Por exemplo, IPs brasileiros comuns
        $ip_ranges = array(
            '177.' => array(
                'country' => 'Brasil',
                'country_code' => 'BR',
                'city' => 'Desconhecida',
                'latitude' => -15.7801,
                'longitude' => -47.9292
            ),
            '179.' => array(
                'country' => 'Brasil',
                'country_code' => 'BR',
                'city' => 'Desconhecida',
                'latitude' => -15.7801,
                'longitude' => -47.9292
            ),
            '187.' => array(
                'country' => 'Brasil',
                'country_code' => 'BR',
                'city' => 'Desconhecida',
                'latitude' => -15.7801,
                'longitude' => -47.9292
            ),
            '189.' => array(
                'country' => 'Brasil',
                'country_code' => 'BR',
                'city' => 'Desconhecida',
                'latitude' => -15.7801,
                'longitude' => -47.9292
            ),
            '191.' => array(
                'country' => 'Brasil',
                'country_code' => 'BR',
                'city' => 'Desconhecida',
                'latitude' => -15.7801,
                'longitude' => -47.9292
            ),
            '200.' => array(
                'country' => 'Brasil',
                'country_code' => 'BR',
                'city' => 'Desconhecida',
                'latitude' => -15.7801,
                'longitude' => -47.9292
            ),
            '201.' => array(
                'country' => 'Brasil',
                'country_code' => 'BR',
                'city' => 'Desconhecida',
                'latitude' => -15.7801,
                'longitude' => -47.9292
            ),
        );
        
        // Verificar se o IP corresponde a algum intervalo brasileiro
        foreach ($ip_ranges as $prefix => $data) {
            if (strpos($ip, $prefix) === 0) {
                return $data;
            }
        }
        
        // Para IPs que não correspondem a nenhum intervalo conhecido, retornar false
        return false;
    }
    
    /**
     * Obter dados adicionais como ASN e provedor de rede
     * 
     * @param string $ip Endereço IP
     * @return array Informações adicionais
     */
    public function get_extra_info($ip) {
        $extra_info = array(
            'asn' => '',
            'provider' => '',
            'organization' => ''
        );
        
        // Verificar se o IP é válido
        if (empty($ip) || $ip == '127.0.0.1' || $ip == 'localhost') {
            return $extra_info;
        }
        
        // Verificar cache
        $cache_key = 'av_extra_' . md5($ip);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Usar a API ipapi.co para obter dados adicionais
        $url = sprintf($this->api_url, $ip);
        $data = $this->fetch_from_api($url);
        
        if (!empty($data)) {
            $extra_info['asn'] = isset($data['asn']) ? sanitize_text_field($data['asn']) : '';
            $extra_info['provider'] = isset($data['org']) ? sanitize_text_field($data['org']) : '';
            $extra_info['organization'] = isset($data['org']) ? sanitize_text_field($data['org']) : '';
        }
        
        // Armazenar em cache por 30 dias
        set_transient($cache_key, $extra_info, 30 * DAY_IN_SECONDS);
        
        return $extra_info;
    }
    
    /**
     * Obter bandeira do país como ícone SVG
     * 
     * @param string $country_code Código do país (2 letras)
     * @return string HTML da bandeira
     */
    public function get_country_flag($country_code) {
        if (empty($country_code)) {
            return '';
        }
        
        $country_code = strtolower($country_code);
        
        // Caminho para o arquivo da bandeira (se existir localmente)
        $flag_path = plugin_dir_path(dirname(__FILE__)) . 'assets/flags/' . $country_code . '.svg';
        
        // Se a bandeira existir localmente
        if (file_exists($flag_path)) {
            $flag_url = plugin_dir_url(dirname(__FILE__)) . 'assets/flags/' . $country_code . '.svg';
            return '<img src="' . esc_url($flag_url) . '" alt="' . esc_attr($country_code) . '" class="country-flag" width="16" height="11" />';
        }
        
        // Caso contrário, usar serviço de bandeiras online
        $flag_url = 'https://flagcdn.com/16x12/' . $country_code . '.png';
        return '<img src="' . esc_url($flag_url) . '" alt="' . esc_attr($country_code) . '" class="country-flag" width="16" height="12" />';
    }
    
    /**
     * Mapear código de país para nome do país em português
     * 
     * @param string $country_code Código do país
     * @return string Nome do país
     */
    public function get_country_name($country_code) {
        if (empty($country_code)) {
            return 'Desconhecido';
        }
        
        $country_code = strtoupper($country_code);
        
        $countries = array(
            'BR' => 'Brasil',
            'US' => 'Estados Unidos',
            'PT' => 'Portugal',
            'ES' => 'Espanha',
            'FR' => 'França',
            'DE' => 'Alemanha',
            'IT' => 'Itália',
            'JP' => 'Japão',
            'CN' => 'China',
            'RU' => 'Rússia',
            'GB' => 'Reino Unido',
            'CA' => 'Canadá',
            'AU' => 'Austrália',
            'AR' => 'Argentina',
            'UY' => 'Uruguai',
            'PY' => 'Paraguai',
            'BO' => 'Bolívia',
            'PE' => 'Peru',
            'CL' => 'Chile',
            'CO' => 'Colômbia',
            'VE' => 'Venezuela',
            'MX' => 'México',
            'IN' => 'Índia',
            'ZA' => 'África do Sul'
        );
        
        return isset($countries[$country_code]) ? $countries[$country_code] : $country_code;
    }
} 