/**
 * JavaScript para o Dashboard do Plugin Análise de Visitantes
 */

var avVisitorsChart = null;

/**
 * Inicializar o dashboard
 */
function initDashboard() {
    // Preencher elementos com dados iniciais
    updateDashboardSummary(avDashboard.stats.summary);
    updateDashboardTables(avDashboard.stats.tables);
    
    // Inicializar o gráfico
    initVisitorsChart(
        avDashboard.stats.charts.dates,
        avDashboard.stats.charts.visitors,
        avDashboard.stats.charts.pageviews
    );
}

/**
 * Inicializar o gráfico de visitantes
 * 
 * @param {Array} dates Datas para o eixo X
 * @param {Array} visitors Dados de visitantes únicos
 * @param {Array} pageviews Dados de visualizações de página
 */
function initVisitorsChart(dates, visitors, pageviews) {
    var ctx = document.getElementById('av-visitors-chart').getContext('2d');
    
    // Formatar datas para exibição
    var formattedDates = formatDates(dates);
    
    // Configurar o gráfico
    avVisitorsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: formattedDates,
            datasets: [
                {
                    label: avDashboard.labels.visitors,
                    data: visitors,
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    borderColor: 'rgba(76, 175, 80, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(76, 175, 80, 1)',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.3
                },
                {
                    label: avDashboard.labels.pageviews,
                    data: pageviews,
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    borderColor: 'rgba(33, 150, 243, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(33, 150, 243, 1)',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    padding: 10,
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    borderColor: 'rgba(0, 0, 0, 0.1)',
                    borderWidth: 1,
                    titleColor: '#333',
                    bodyColor: '#666',
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

/**
 * Atualizar todo o dashboard com novos dados
 * 
 * @param {Object} data Dados do dashboard
 */
function updateDashboard(data) {
    // Atualizar resumo
    updateDashboardSummary(data.summary);
    
    // Atualizar tabelas
    updateDashboardTables(data.tables);
    
    // Atualizar gráfico
    updateVisitorsChart(
        data.charts.dates,
        data.charts.visitors,
        data.charts.pageviews
    );
}

/**
 * Atualizar seção de resumo do dashboard
 * 
 * @param {Object} summary Dados de resumo
 */
function updateDashboardSummary(summary) {
    jQuery('#av-total-visitors').text(formatNumber(summary.total_visitors));
    jQuery('#av-total-pageviews').text(formatNumber(summary.total_pageviews));
    jQuery('#av-online-now').text(formatNumber(summary.online_now));
}

/**
 * Atualizar tabelas de dados
 * 
 * @param {Object} tables Dados das tabelas
 */
function updateDashboardTables(tables) {
    // Tabela de páginas mais visitadas
    var pagesHtml = '';
    if (tables.top_pages.length === 0) {
        pagesHtml = '<tr><td colspan="2">Nenhuma página visitada no período.</td></tr>';
    } else {
        tables.top_pages.forEach(function(page) {
            pagesHtml += '<tr>';
            pagesHtml += '<td><a href="' + page.page_url + '" target="_blank">' + page.page_title + '</a></td>';
            pagesHtml += '<td>' + formatNumber(page.views) + '</td>';
            pagesHtml += '</tr>';
        });
    }
    jQuery('#av-top-pages').html(pagesHtml);
    
    // Tabela de navegadores
    var browsersHtml = '';
    if (tables.browsers.length === 0) {
        browsersHtml = '<tr><td colspan="2">Nenhum navegador registrado no período.</td></tr>';
    } else {
        tables.browsers.forEach(function(browser) {
            browsersHtml += '<tr>';
            browsersHtml += '<td>' + browser.browser + '</td>';
            browsersHtml += '<td>' + formatNumber(browser.count) + '</td>';
            browsersHtml += '</tr>';
        });
    }
    jQuery('#av-browsers').html(browsersHtml);
    
    // Tabela de dispositivos
    var devicesHtml = '';
    if (tables.devices.length === 0) {
        devicesHtml = '<tr><td colspan="2">Nenhum dispositivo registrado no período.</td></tr>';
    } else {
        tables.devices.forEach(function(device) {
            devicesHtml += '<tr>';
            devicesHtml += '<td>' + formatDeviceType(device.device_type) + '</td>';
            devicesHtml += '<td>' + formatNumber(device.count) + '</td>';
            devicesHtml += '</tr>';
        });
    }
    jQuery('#av-devices').html(devicesHtml);
    
    // Tabela de países
    var countriesHtml = '';
    if (tables.countries.length === 0) {
        countriesHtml = '<tr><td colspan="2">Nenhum país registrado no período.</td></tr>';
    } else {
        tables.countries.forEach(function(country) {
            countriesHtml += '<tr>';
            countriesHtml += '<td>' + (country.flag ? country.flag + ' ' : '') + country.name + '</td>';
            countriesHtml += '<td>' + formatNumber(country.count) + '</td>';
            countriesHtml += '</tr>';
        });
    }
    jQuery('#av-countries').html(countriesHtml);
}

/**
 * Atualizar o gráfico de visitantes
 * 
 * @param {Array} dates Datas para o eixo X
 * @param {Array} visitors Dados de visitantes únicos
 * @param {Array} pageviews Dados de visualizações de página
 */
function updateVisitorsChart(dates, visitors, pageviews) {
    // Formatar datas para exibição
    var formattedDates = formatDates(dates);
    
    // Atualizar dados do gráfico
    avVisitorsChart.data.labels = formattedDates;
    avVisitorsChart.data.datasets[0].data = visitors;
    avVisitorsChart.data.datasets[1].data = pageviews;
    
    // Atualizar o gráfico
    avVisitorsChart.update();
}

/**
 * Formatar números para exibição
 * 
 * @param {number} number Número a ser formatado
 * @return {string} Número formatado
 */
function formatNumber(number) {
    return new Intl.NumberFormat().format(number);
}

/**
 * Formatar tipo de dispositivo
 * 
 * @param {string} type Tipo de dispositivo
 * @return {string} Tipo formatado
 */
function formatDeviceType(type) {
    var types = {
        'desktop': 'Desktop',
        'mobile': 'Mobile',
        'tablet': 'Tablet'
    };
    
    return types[type] || type;
}

/**
 * Formatar datas para exibição
 * 
 * @param {Array} dates Array de datas no formato 'YYYY-MM-DD'
 * @return {Array} Array de datas formatadas
 */
function formatDates(dates) {
    var formattedDates = [];
    
    dates.forEach(function(dateStr) {
        // Converter string para objeto Date
        var date = new Date(dateStr);
        
        // Verificar se a data é válida
        if (isNaN(date.getTime())) {
            formattedDates.push(dateStr);
            return;
        }
        
        // Obter dia, mês e ano
        var day = date.getDate();
        var month = date.getMonth();
        
        // Formatar data (dia/mês)
        var formattedDate = day + '/' + avDashboard.labels.months[month];
        formattedDates.push(formattedDate);
    });
    
    return formattedDates;
} 