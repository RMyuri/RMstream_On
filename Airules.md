# Airules - Regras do Projeto RMStream

## Objetivo do Projeto
RMStream é uma plataforma de streaming inspirada no YouTube, com tema escuro e detalhes em verde. O objetivo é criar uma interface intuitiva para buscar, visualizar e gerenciar vídeos do YouTube.

## Funcionalidades Principais
- Busca e visualização de vídeos do YouTube via API
- Página Room para assistir vídeos com sugestões relacionadas
- Perfis de canais com lista de vídeos
- Carregamento contínuo (infinite scroll) na busca

## Padrões de Design
- Esquema de cores: Fundo escuro (#181818), detalhes em verde (#1db954)
- Cards de vídeo padronizados com thumbnail à esquerda e informações à direita
- Design responsivo para mobile e desktop
- Mensagens de status para feedback sobre API

## Chaves de API
- A API Key do YouTube está configurada como: "AIzaSyBRVg9qK01Uf0iou5ts3bSyTi-FAO1bXNw"
- Importante: Essa chave deve estar ativa para o projeto funcionar

## Regras de Desenvolvimento
- Documente todas as decisões importantes de arquitetura.
- Sempre atualize este arquivo ao adicionar ou alterar regras do projeto.
- Use comentários claros e objetivos no código.
- Siga o padrão de nomenclatura definido para variáveis, funções e arquivos.
- Mantenha o código limpo e organizado.
- Atualize o Changelog.md a cada alteração relevante.
- Sempre atualize os links das páginas no index ao criar ou alterar páginas.
- Evite código JavaScript duplicado após o fechamento das tags HTML.
- Use o modelo de componentes definido para cards de vídeo.

## Estrutura de Arquivos
- `/views/`: Páginas PHP (index.php, room.php, search.php, channel.php)
- `/public/css/`: Folhas de estilo (style.css, room.css, search.css, channel.css)
- `/public/js/`: Scripts JavaScript (room.js, search.js, channel.js)
- `Airules.md`: Documentação de regras e arquitetura
- `Changelog.md`: Histórico de alterações
