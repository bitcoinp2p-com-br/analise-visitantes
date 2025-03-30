# Análise de Visitantes

Plugin leve e focado em privacidade para monitorar visualizações de página no WordPress, sem usar cookies ou serviços de terceiros.

## Características Principais

- **Rastreamento sem Cookies**: Coleta dados sem necessidade de avisos LGPD/GDPR
- **Mapa Geográfico de Visitantes**: Visualização interativa da origem das visitas
- **Análise de Dispositivos**: Estatísticas de dispositivos móveis, desktop e navegadores
- **Análise em Tempo Real**: Monitore visitantes e tráfego em tempo real
- **Comparação de Períodos**: Compare dados entre diferentes períodos
- **Interfaces Intuitivas**: Dashboard simplificado e visual

## Privacidade e Conformidade

- Não usa cookies para rastreamento
- Dados anonimizados (IPs não são armazenados)
- Conformidade com LGPD/GDPR sem necessidade de consentimento
- Retenção de dados limitada (30 dias por padrão)

## Funcionalidades em Destaque

### Dashboard Principal
- Visão geral de visitas diárias, semanais e mensais
- Gráficos interativos de tendências de tráfego
- Estatísticas de páginas mais populares e referências

### Mapa Geográfico
- Visualização interativa da distribuição global de visitantes
- Estatísticas por país e cidade
- Filtros por página e período

### Análise de Dispositivos
- Distribuição de visitas por tipo de dispositivo (mobile, desktop, tablet)
- Estatísticas de navegadores e sistemas operacionais
- Gráficos comparativos

### Análise em Tempo Real
- Monitoramento de visitantes online em tempo real
- Lista dinâmica das páginas mais acessadas
- Tracking dos sites que redirecionam visitantes para seu site
- Análise de rotas de navegação mais comuns
- Visualização de atividade recente com atualizações automáticas

### Comparação de Períodos
- Comparação visual entre diferentes períodos
- Cálculo de variação percentual
- Identificação de tendências de crescimento ou queda

## Instalação

1. Faça upload da pasta `analise-visitantes` para o diretório `/wp-content/plugins/` do seu WordPress
2. Ative o plugin através do menu 'Plugins' no WordPress
3. Acesse as estatísticas no menu 'Análise de Visitantes'

## Requisitos

- WordPress 5.0 ou superior
- PHP 7.2 ou superior
- Permissões de banco de dados para criar tabelas

## Integração com Temas

O plugin inclui um shortcode para exibir estatísticas no frontend:

```
[estatisticas_visitantes mostrar_online="sim" mostrar_total="sim" mostrar_paginas="sim" limite_paginas="5"]
```

## Customização

O plugin pode ser facilmente customizado através de ganchos (hooks) e filtros.

### Configuração de Retenção de Dados

```php
// Alterar período de retenção para 60 dias (padrão: 30)
add_filter('analise_visitantes_retencao', function() {
    return 60;
});
```

### Personalização Visual

O plugin utiliza classes CSS com prefixo `analise-visitantes-` para facilitar a customização visual.

## Solução de Problemas

### Dados Geográficos Não Aparecem
Certifique-se de que seu servidor tem acesso ao serviço de geolocalização utilizado pelo plugin.

### Estatísticas Não São Atualizadas
Verifique se os hooks do WordPress estão sendo executados corretamente, especialmente se estiver usando plugins de cache.

## Agradecimentos

Este plugin foi inspirado pelo Statify e utiliza as seguintes bibliotecas:

- Chart.js para gráficos interativos
- Leaflet.js para mapas geográficos
- Moment.js para manipulação de tempo em análises em tempo real

## Contribuições

Contribuições são bem-vindas! Sinta-se à vontade para reportar bugs, sugerir melhorias ou enviar pull requests.

## Licença

GPLv2 ou posterior