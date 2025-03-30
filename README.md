# Análise de Visitantes

Plugin WordPress para análise completa de visitantes do site com estatísticas em tempo real, mapas geográficos e relatórios detalhados.

## Descrição

O plugin **Análise de Visitantes** oferece uma solução completa para rastreamento e análise de visitantes em sites WordPress, sem depender de serviços externos como o Google Analytics. Foi projetado para ser leve, rápido e com foco em privacidade, sendo uma alternativa eficiente para sites que desejam ter controle total sobre seus dados de visitantes.

### Principais Recursos

- **Dashboard Intuitivo**: Visualize métricas importantes como visitantes únicos, visualizações de página e visitantes online.
- **Monitoramento em Tempo Real**: Acompanhe visitantes que estão navegando no site neste momento.
- **Mapa Geográfico de Visitantes**: Visualize de onde vêm seus visitantes com um mapa interativo.
- **Análise de Dispositivos**: Saiba quais navegadores, sistemas operacionais e tipos de dispositivos seus visitantes utilizam.
- **Páginas Mais Populares**: Identifique quais conteúdos atraem mais visitantes.
- **Otimizado para Performance**: Projetado para mínimo impacto no tempo de carregamento do site.
- **Compatível com Cache**: Funciona perfeitamente mesmo com plugins de cache ativados.
- **Privacidade por Design**: Em conformidade com LGPD/GDPR, sem armazenar IPs ou dados pessoais.
- **Exportação de Dados**: Exporte relatórios facilmente para análises externas.
- **Relatórios Detalhados**: Analise dados de visitantes por período, comparando diferentes intervalos de tempo.

## Requisitos

- WordPress 5.0 ou superior
- PHP 7.0 ou superior
- MySQL 5.6 ou superior
- Recomendado: PHP 7.4+ para melhor performance

## Instalação

### Método Rápido (Recomendado)

1. Acesse o [repositório do plugin no GitHub](https://github.com/bitcoinp2p-com-br/analise-visitantes)
2. Clique no botão "Code" e selecione "Download ZIP"
3. No seu painel WordPress, vá para "Plugins" > "Adicionar Novo" > "Enviar Plugin"
4. Escolha o arquivo ZIP baixado e clique em "Instalar Agora"
5. Após a instalação, clique em "Ativar Plugin"
6. Acesse o menu "Análise de Visitantes" no painel administrativo

### Método Alternativo

1. Faça o upload dos arquivos do plugin para o diretório `/wp-content/plugins/analise-visitantes/`
2. Ative o plugin através do menu 'Plugins' no WordPress
3. Acesse o menu 'Análise de Visitantes' no painel administrativo
4. Configure as opções básicas em 'Configurações'

## Uso

Após a ativação, o plugin começa automaticamente a rastrear as visitas ao seu site. Nenhuma configuração adicional é necessária, mas você pode personalizar várias opções:

### Dashboard

O dashboard principal fornece uma visão geral das estatísticas do site, com:

- Contadores de visitantes únicos, visualizações de página e visitantes online
- Gráfico de tendências para visualizar padrões de tráfego
- Lista das páginas mais visitadas
- Distribuição de visitantes por país
- Estatísticas de dispositivos e navegadores

### Tempo Real

A página de monitoramento em tempo real exibe:

- Contador de visitantes atualmente online
- Lista em tempo real das últimas visualizações de página
- Detalhes de cada visitante como localização, dispositivo e página visualizada

### Mapa

O mapa interativo mostra:

- Distribuição geográfica dos visitantes
- Concentração de visitas por país
- Detalhes ao passar o mouse sobre cada marcador

### Configurações

Configure o plugin de acordo com suas necessidades:

- Período de retenção de dados (padrão: 90 dias)
- Ativar/desativar rastreamento geográfico
- Ignorar visitas de administradores
- Ignorar robôs e crawlers

## Considerações de Privacidade

Este plugin foi projetado considerando a privacidade dos visitantes:

- Não armazena endereços IP completos
- Não utiliza cookies para rastreamento (apenas para funcionalidade)
- Todas as informações são armazenadas apenas no seu próprio banco de dados
- Compatível com requisitos da LGPD e GDPR

## Otimização de Performance

O plugin utiliza diversas técnicas para garantir que o rastreamento de visitantes não afete a performance do site:

- Uso de `requestIdleCallback` para execução de código quando o navegador está ocioso
- Sistema de cache interno para reduzir consultas ao banco de dados
- Compatibilidade com plugins de cache
- Uso de `navigator.sendBeacon` para rastreamento de saídas sem afetar a navegação

## FAQ

### O plugin afeta o tempo de carregamento do site?
Não significativamente. O rastreamento é realizado de forma assíncrona após o carregamento da página, sem bloquear a renderização.

### Este plugin é compatível com plugins de cache?
Sim, foi projetado para funcionar corretamente mesmo quando o conteúdo é servido a partir do cache.

### O plugin funciona em sites com muito tráfego?
Sim, o plugin é otimizado para sites com alto volume de tráfego. Para sites muito grandes, recomendamos configurar um período de retenção de dados mais curto.

### Os dados de visitantes ficam armazenados onde?
Todos os dados são armazenados apenas no seu próprio banco de dados WordPress. Nenhuma informação é enviada para servidores externos.

## Suporte

Para suporte, dúvidas ou sugestões, entre em contato através do repositório do GitHub ou pelo e-mail suporte@seu-dominio.com.

## Licença

Este plugin é licenciado sob a GPL v2 ou posterior. Veja o arquivo LICENSE para mais detalhes.