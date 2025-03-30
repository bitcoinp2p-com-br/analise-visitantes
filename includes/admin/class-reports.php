<?php
/**
 * Classe para gerenciar os relatórios do plugin
 * 
 * @package Analise_Visitantes
 * @subpackage Admin
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
     * Instância global do wpdb
     * @var wpdb
     */
    private $db;
    
    /**
     * Nome da tabela principal do plugin
     * @var string
     */
    private $table_name;
    
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
        global $wpdb;
        $this->db = $wpdb;
        $this->table_name = $wpdb->prefix . 'analise_visitantes';
        
        $this->setup_actions();
    }
    
    /**
     * Configurar ações e filtros
     */
    private function setup_actions() {
        // AJAX para gerar relatórios
        add_action('wp_ajax_av_gerar_relatorio', array($this, 'ajax_gerar_relatorio'));
        
        // AJAX para exportar CSV
        add_action('wp_ajax_av_exportar_csv', array($this, 'ajax_exportar_csv'));
    }
    
    /**
     * AJAX para gerar relatórios
     */
    public function ajax_gerar_relatorio() {
        // Verificar nonce
        check_ajax_referer('av_reports_nonce', 'nonce');
        
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada'));
        }
        
        // Obter parâmetros
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $tipo = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : 'visitantes';
        
        // Gerar relatório
        $data = array();
        
        switch ($tipo) {
            case 'visitantes':
                $data = $this->get_visitor_report($days);
                break;
            
            case 'paginas':
                $data = $this->get_pages_report($days);
                break;
            
            case 'referencia':
                $data = $this->get_referrer_report($days);
                break;
            
            case 'localizacao':
                $data = $this->get_location_report($days);
                break;
            
            case 'dispositivos':
                $data = $this->get_device_report($days);
                break;
            
            default:
                $data = $this->get_visitor_report($days);
                break;
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX para exportar CSV
     */
    public function ajax_exportar_csv() {
        // Verificar nonce
        check_ajax_referer('av_reports_nonce', 'nonce');
        
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_die('Permissão negada');
        }
        
        // Obter parâmetros
        $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
        $tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : 'visitantes';
        
        // Gerar dados para o CSV
        switch ($tipo) {
            case 'visitantes':
                $data = $this->get_visitor_report($days);
                $filename = 'visitantes-report';
                break;
            
            case 'paginas':
                $data = $this->get_pages_report($days);
                $filename = 'paginas-report';
                break;
            
            case 'referencia':
                $data = $this->get_referrer_report($days);
                $filename = 'referencias-report';
                break;
            
            case 'localizacao':
                $data = $this->get_location_report($days);
                $filename = 'localizacao-report';
                break;
            
            case 'dispositivos':
                $data = $this->get_device_report($days);
                $filename = 'dispositivos-report';
                break;
            
            default:
                $data = $this->get_visitor_report($days);
                $filename = 'report';
                break;
        }
        
        $date = date('Y-m-d');
        $filename = sanitize_file_name($filename . '-' . $date . '.csv');
        
        // Configurar cabeçalhos para download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        // Abrir saída para escrita CSV
        $output = fopen('php://output', 'w');
        
        // Para UTF-8 funcionar corretamente
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        
        // Cabeçalhos do CSV
        switch ($tipo) {
            case 'visitantes':
                fputcsv($output, array('Data', 'Visitantes', 'Visualizações'));
                
                // Dados
                if (!empty($data['items'])) {
                    foreach ($data['items'] as $item) {
                        fputcsv($output, array(
                            $item['date'],
                            $item['visitors'],
                            $item['pageviews']
                        ));
                    }
                }
                break;
            
            case 'paginas':
                fputcsv($output, array('Título', 'URL', 'Visualizações'));
                
                // Dados
                if (!empty($data['items'])) {
                    foreach ($data['items'] as $item) {
                        fputcsv($output, array(
                            $item['title'],
                            $item['url'],
                            $item['views']
                        ));
                    }
                }
                break;
            
            case 'referencia':
                fputcsv($output, array('Domínio', 'URL', 'Visitas'));
                
                // Dados
                if (!empty($data['items'])) {
                    foreach ($data['items'] as $item) {
                        fputcsv($output, array(
                            $item['domain'],
                            $item['url'],
                            $item['count']
                        ));
                    }
                }
                break;
            
            case 'localizacao':
                fputcsv($output, array('País', 'Visitas', 'Porcentagem'));
                
                // Dados
                if (!empty($data['items'])) {
                    foreach ($data['items'] as $item) {
                        fputcsv($output, array(
                            $item['country'],
                            $item['count'],
                            $item['percentage']
                        ));
                    }
                }
                break;
            
            case 'dispositivos':
                fputcsv($output, array('Dispositivo', 'Navegador', 'Visitas'));
                
                // Dados
                if (!empty($data['items'])) {
                    foreach ($data['items'] as $item) {
                        fputcsv($output, array(
                            $item['device'],
                            $item['browser'],
                            $item['count']
                        ));
                    }
                }
                break;
        }
        
        // Finalizar e sair
        fclose($output);
        exit;
    }
    
    /**
     * Obter relatório de visitantes
     * 
     * @param int $days Número de dias para o relatório
     * @return array Dados do relatório
     */
    private function get_visitor_report($days) {
        // Calcular período
        $end_date = current_time('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-$days days", strtotime($end_date)));
        
        // Consultar visitantes por dia
        $visitors_data = $this->db->get_results(
            $this->db->prepare(
                "SELECT 
                    DATE(date_created) as visit_date, 
                    COUNT(DISTINCT visitor_id) as visitors,
                    COUNT(*) as pageviews
                FROM {$this->table_name}
                WHERE date_created BETWEEN %s AND %s
                GROUP BY DATE(date_created)
                ORDER BY DATE(date_created) ASC",
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            ),
            ARRAY_A
        );
        
        // Total de visitantes e pageviews no período
        $totals = $this->db->get_row(
            $this->db->prepare(
                "SELECT 
                    COUNT(DISTINCT visitor_id) as total_visitors,
                    COUNT(*) as total_pageviews
                FROM {$this->table_name}
                WHERE date_created BETWEEN %s AND %s",
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            ),
            ARRAY_A
        );
        
        // Formatar dados para retorno
        $items = array();
        $labels = array();
        $visitors_series = array();
        $pageviews_series = array();
        
        // Preencher array com todas as datas do período
        $date_period = new DatePeriod(
            new DateTime($start_date),
            new DateInterval('P1D'),
            new DateTime(date('Y-m-d', strtotime('+1 day', strtotime($end_date))))
        );
        
        foreach ($date_period as $date) {
            $date_str = $date->format('Y-m-d');
            $date_formatted = $date->format('d/m/Y');
            $labels[] = $date_formatted;
            
            // Verificar se temos dados para esta data
            $found = false;
            
            foreach ($visitors_data as $row) {
                if ($row['visit_date'] == $date_str) {
                    $items[] = array(
                        'date' => $date_formatted,
                        'visitors' => intval($row['visitors']),
                        'pageviews' => intval($row['pageviews'])
                    );
                    
                    $visitors_series[] = intval($row['visitors']);
                    $pageviews_series[] = intval($row['pageviews']);
                    
                    $found = true;
                    break;
                }
            }
            
            // Se não temos dados para esta data, adicionar zeros
            if (!$found) {
                $items[] = array(
                    'date' => $date_formatted,
                    'visitors' => 0,
                    'pageviews' => 0
                );
                
                $visitors_series[] = 0;
                $pageviews_series[] = 0;
            }
        }
        
        // Calcular média diária
        $average_per_day = count($items) > 0 ? round($totals['total_visitors'] / count($items), 1) : 0;
        
        // Montar array de retorno
        return array(
            'type' => 'visitantes',
            'items' => $items,
            'summary' => array(
                'total_visitors' => $totals ? intval($totals['total_visitors']) : 0,
                'total_pageviews' => $totals ? intval($totals['total_pageviews']) : 0,
                'average_per_day' => $average_per_day
            ),
            'chart' => array(
                'labels' => $labels,
                'visitors' => $visitors_series,
                'pageviews' => $pageviews_series
            )
        );
    }
    
    /**
     * Obter relatório de páginas mais visitadas
     * 
     * @param int $days Número de dias para o relatório
     * @return array Dados do relatório
     */
    private function get_pages_report($days) {
        // Calcular período
        $end_date = current_time('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-$days days", strtotime($end_date)));
        
        // Consultar páginas mais visitadas
        $pages_data = $this->db->get_results(
            $this->db->prepare(
                "SELECT 
                    page_title, 
                    page_url, 
                    COUNT(*) as views
                FROM {$this->table_name}
                WHERE date_created BETWEEN %s AND %s
                GROUP BY page_url
                ORDER BY views DESC
                LIMIT 50",
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            ),
            ARRAY_A
        );
        
        // Total de visualizações e visitantes no período
        $totals = $this->db->get_row(
            $this->db->prepare(
                "SELECT 
                    COUNT(DISTINCT visitor_id) as total_visitors,
                    COUNT(*) as total_pageviews
                FROM {$this->table_name}
                WHERE date_created BETWEEN %s AND %s",
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            ),
            ARRAY_A
        );
        
        // Formatar dados para retorno
        $items = array();
        $labels = array();
        $views_series = array();
        
        foreach ($pages_data as $row) {
            $title = !empty($row['page_title']) ? $row['page_title'] : '(Sem título)';
            
            $items[] = array(
                'title' => $title,
                'url' => $row['page_url'],
                'views' => intval($row['views'])
            );
            
            // Limitar o tamanho do título para o gráfico
            $short_title = strlen($title) > 30 ? substr($title, 0, 27) . '...' : $title;
            $labels[] = $short_title;
            $views_series[] = intval($row['views']);
        }
        
        // Limitar para os 10 primeiros para o gráfico
        $chart_labels = array_slice($labels, 0, 10);
        $chart_series = array_slice($views_series, 0, 10);
        
        // Calcular média diária
        $total_days = $days > 0 ? $days : 1;
        $average_per_day = round($totals['total_pageviews'] / $total_days, 1);
        
        // Montar array de retorno
        return array(
            'type' => 'paginas',
            'items' => $items,
            'summary' => array(
                'total_visitors' => $totals ? intval($totals['total_visitors']) : 0,
                'total_pageviews' => $totals ? intval($totals['total_pageviews']) : 0,
                'average_per_day' => $average_per_day
            ),
            'chart' => array(
                'labels' => $chart_labels,
                'visitors' => $chart_series,
                'datasetLabel' => 'Visualizações'
            )
        );
    }
    
    /**
     * Obter relatório de referências de tráfego
     * 
     * @param int $days Número de dias para o relatório
     * @return array Dados do relatório
     */
    private function get_referrer_report($days) {
        // Calcular período
        $end_date = current_time('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-$days days", strtotime($end_date)));
        
        // Consultar referências
        $referrer_data = $this->db->get_results(
            $this->db->prepare(
                "SELECT 
                    referrer,
                    COUNT(*) as count
                FROM {$this->table_name}
                WHERE 
                    date_created BETWEEN %s AND %s
                    AND referrer IS NOT NULL
                    AND referrer != ''
                GROUP BY referrer
                ORDER BY count DESC
                LIMIT 50",
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            ),
            ARRAY_A
        );
        
        // Total de visualizações e visitantes no período
        $totals = $this->db->get_row(
            $this->db->prepare(
                "SELECT 
                    COUNT(DISTINCT visitor_id) as total_visitors,
                    COUNT(*) as total_pageviews
                FROM {$this->table_name}
                WHERE date_created BETWEEN %s AND %s",
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            ),
            ARRAY_A
        );
        
        // Formatar dados para retorno
        $items = array();
        $labels = array();
        $count_series = array();
        
        foreach ($referrer_data as $row) {
            $url = $row['referrer'];
            $domain = parse_url($url, PHP_URL_HOST);
            
            if (empty($domain)) {
                $domain = 'Acesso Direto';
            }
            
            $items[] = array(
                'domain' => $domain,
                'url' => $url,
                'count' => intval($row['count'])
            );
            
            $labels[] = $domain;
            $count_series[] = intval($row['count']);
        }
        
        // Adicionar entrada para acesso direto se não houver
        $direct_access_count = $this->db->get_var(
            $this->db->prepare(
                "SELECT 
                    COUNT(*) 
                FROM {$this->table_name}
                WHERE 
                    date_created BETWEEN %s AND %s
                    AND (referrer IS NULL OR referrer = '')",
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            )
        );
        
        if ($direct_access_count > 0) {
            array_unshift($items, array(
                'domain' => 'Acesso Direto',
                'url' => '',
                'count' => intval($direct_access_count)
            ));
            
            array_unshift($labels, 'Acesso Direto');
            array_unshift($count_series, intval($direct_access_count));
        }
        
        // Limitar para os 10 primeiros para o gráfico
        $chart_labels = array_slice($labels, 0, 10);
        $chart_series = array_slice($count_series, 0, 10);
        
        // Calcular média diária
        $total_days = $days > 0 ? $days : 1;
        $average_per_day = round($totals['total_visitors'] / $total_days, 1);
        
        // Montar array de retorno
        return array(
            'type' => 'referencia',
            'items' => $items,
            'summary' => array(
                'total_visitors' => $totals ? intval($totals['total_visitors']) : 0,
                'total_pageviews' => $totals ? intval($totals['total_pageviews']) : 0,
                'average_per_day' => $average_per_day
            ),
            'chart' => array(
                'labels' => $chart_labels,
                'visitors' => $chart_series,
                'datasetLabel' => 'Visitas por Origem'
            )
        );
    }
    
    /**
     * Obter relatório de localização dos visitantes
     * 
     * @param int $days Número de dias para o relatório
     * @return array Dados do relatório
     */
    private function get_location_report($days) {
        // Calcular período
        $end_date = current_time('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-$days days", strtotime($end_date)));
        
        // Consultar países
        $countries_data = $this->db->get_results(
            $this->db->prepare(
                "SELECT 
                    country,
                    COUNT(DISTINCT visitor_id) as count
                FROM {$this->table_name}
                WHERE 
                    date_created BETWEEN %s AND %s
                    AND country IS NOT NULL
                    AND country != ''
                GROUP BY country
                ORDER BY count DESC",
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            ),
            ARRAY_A
        );
        
        // Total de visitantes no período (para cálculo de porcentagem)
        $total_visitors = $this->db->get_var(
            $this->db->prepare(
                "SELECT 
                    COUNT(DISTINCT visitor_id)
                FROM {$this->table_name}
                WHERE date_created BETWEEN %s AND %s",
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            )
        );
        
        // Total de visualizações no período
        $total_pageviews = $this->db->get_var(
            $this->db->prepare(
                "SELECT 
                    COUNT(*)
                FROM {$this->table_name}
                WHERE date_created BETWEEN %s AND %s",
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            )
        );
        
        // Formatar dados para retorno
        $items = array();
        $labels = array();
        $count_series = array();
        
        foreach ($countries_data as $row) {
            $country = !empty($row['country']) ? $row['country'] : 'Desconhecido';
            $count = intval($row['count']);
            $percentage = $total_visitors > 0 ? round(($count / $total_visitors) * 100, 1) : 0;
            
            $items[] = array(
                'country' => $country,
                'count' => $count,
                'percentage' => $percentage
            );
            
            $labels[] = $country;
            $count_series[] = $count;
        }
        
        // Adicionar entrada para país desconhecido
        $unknown_country_count = $this->db->get_var(
            $this->db->prepare(
                "SELECT 
                    COUNT(DISTINCT visitor_id) 
                FROM {$this->table_name}
                WHERE 
                    date_created BETWEEN %s AND %s
                    AND (country IS NULL OR country = '')",
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            )
        );
        
        if ($unknown_country_count > 0) {
            $percentage = $total_visitors > 0 ? round(($unknown_country_count / $total_visitors) * 100, 1) : 0;
            
            $items[] = array(
                'country' => 'Desconhecido',
                'count' => intval($unknown_country_count),
                'percentage' => $percentage
            );
            
            $labels[] = 'Desconhecido';
            $count_series[] = intval($unknown_country_count);
        }
        
        // Limitar para os 10 primeiros para o gráfico
        $chart_labels = array_slice($labels, 0, 10);
        $chart_series = array_slice($count_series, 0, 10);
        
        // Calcular média diária
        $total_days = $days > 0 ? $days : 1;
        $average_per_day = round($total_visitors / $total_days, 1);
        
        // Montar array de retorno
        return array(
            'type' => 'localizacao',
            'items' => $items,
            'summary' => array(
                'total_visitors' => intval($total_visitors),
                'total_pageviews' => intval($total_pageviews),
                'average_per_day' => $average_per_day
            ),
            'chart' => array(
                'labels' => $chart_labels,
                'visitors' => $chart_series,
                'datasetLabel' => 'Visitas por País'
            )
        );
    }
    
    /**
     * Obter relatório de dispositivos
     * 
     * @param int $days Número de dias para o relatório
     * @return array Dados do relatório
     */
    private function get_device_report($days) {
        // Calcular período
        $end_date = current_time('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-$days days", strtotime($end_date)));
        
        // Consultar dispositivos e navegadores
        $devices_data = $this->db->get_results(
            $this->db->prepare(
                "SELECT 
                    device_type,
                    browser,
                    COUNT(DISTINCT visitor_id) as count
                FROM {$this->table_name}
                WHERE date_created BETWEEN %s AND %s
                GROUP BY device_type, browser
                ORDER BY count DESC",
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            ),
            ARRAY_A
        );
        
        // Total de visitantes e pageviews no período
        $totals = $this->db->get_row(
            $this->db->prepare(
                "SELECT 
                    COUNT(DISTINCT visitor_id) as total_visitors,
                    COUNT(*) as total_pageviews
                FROM {$this->table_name}
                WHERE date_created BETWEEN %s AND %s",
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            ),
            ARRAY_A
        );
        
        // Formatar dados para retorno
        $items = array();
        
        // Dados para o gráfico - por tipo de dispositivo
        $device_stats = array();
        
        foreach ($devices_data as $row) {
            $device = !empty($row['device_type']) ? ucfirst($row['device_type']) : 'Desconhecido';
            $browser = !empty($row['browser']) ? ucfirst($row['browser']) : 'Desconhecido';
            
            $items[] = array(
                'device' => $device,
                'browser' => $browser,
                'count' => intval($row['count'])
            );
            
            // Acumular dados para o gráfico
            if (!isset($device_stats[$device])) {
                $device_stats[$device] = 0;
            }
            
            $device_stats[$device] += intval($row['count']);
        }
        
        // Preparar dados para o gráfico
        $labels = array_keys($device_stats);
        $series = array_values($device_stats);
        
        // Calcular média diária
        $total_days = $days > 0 ? $days : 1;
        $average_per_day = round($totals['total_visitors'] / $total_days, 1);
        
        // Montar array de retorno
        return array(
            'type' => 'dispositivos',
            'items' => $items,
            'summary' => array(
                'total_visitors' => $totals ? intval($totals['total_visitors']) : 0,
                'total_pageviews' => $totals ? intval($totals['total_pageviews']) : 0,
                'average_per_day' => $average_per_day
            ),
            'chart' => array(
                'labels' => $labels,
                'visitors' => $series,
                'datasetLabel' => 'Visitas por Dispositivo'
            )
        );
    }
} 