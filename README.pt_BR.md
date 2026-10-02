# Response Exporter Plugin for OJS/OMP

[English](README.md) | **Português (Brasil)**

O **Response Exporter** é um plugin de relatório para os sistemas [Open Journal Systems (OJS)](https://pkp.sfu.ca/ojs/) e [Open Monograph Press (OMP)](https://pkp.sfu.ca/omp/) da PKP. Ele permite a exportação das respostas de formulários de avaliação preenchidos por revisores durante o processo editorial.

Este plugin gera arquivos CSV contendo dados de revisores, autores e respostas dos formulários de avaliação. Também é possível exportar apenas respostas numéricas, úteis para análises estatísticas.


## Funcionalidades

- Exporta dados de revisões realizadas por submissão.
- Inclui informações detalhadas dos revisores (nome, e-mail, datas de vencimento).
- Inclui o e-mail do autor relacionado à submissão.
- Exporta as respostas dos formulários de avaliação, com uma coluna por pergunta do formulário.
- Mostra o texto da opção (e não sua posição interna) em botões de opção, listas suspensas e caixas de seleção.
- Opção para exportar somente respostas numéricas.
- Funciona com MySQL/MariaDB e PostgreSQL.


## Compatibilidade

A release **1.0.0.0** segue o estilo de plugin com namespace (PSR-4) introduzido no OJS/OMP 3.4 e é compatível com:

- **OJS/OMP 3.4.x** (PHP 8.0.2 ou superior)
- **OJS/OMP 3.5.x** (PHP 8.2 ou superior)

O OJS/OMP 3.3 e versões anteriores não são suportados.


## Instalação

1. Copie a pasta do plugin `responseExporter` para o diretório `plugins/reports/` da sua instalação do OJS ou OMP. Ela precisa ficar em `plugins/reports/` (e não em `plugins/generic/`); caso contrário, as classes do plugin não são encontradas.
2. Acesse o painel de administração do sistema como Gerente ou Editor.
3. Vá até **Plugins > Relatórios** e habilite o plugin `Response Exporter`.


## Uso

1. Acesse o menu:  
   `Estatísticas > Relatórios`
2. Clique em **Exportar** no item `Response Exporter`.
3. Um arquivo CSV será gerado e baixado automaticamente com os dados coletados.


## Configurações

O plugin possui uma configuração opcional:

- **Exportar apenas respostas numéricas**: útil para relatórios quantitativos.

Para configurar:

1. Clique em **Configurações** no menu do plugin.
2. Marque ou desmarque a opção `Exportar apenas respostas numéricas`.


## Formato do Arquivo CSV

Cada linha do CSV representa uma avaliação. Os cabeçalhos das colunas seguem o idioma da interface do usuário; em português, são:

- `Id da submissão`
- `Prazo para avaliação`
- `Prazo de resposta` (prazo para responder ao convite de avaliação)
- `Id do Avaliador`
- `E-mail do avaliador`
- `Sobrenome do avaliador`
- `Nome do avaliador`
- `E-mail do autor`: e-mail do contato principal da submissão ou, se não houver contato principal, do primeiro autor. Fica vazio quando a submissão não tem autores.
- Uma coluna por pergunta do formulário de avaliação, com o texto da pergunta no cabeçalho, na mesma ordem do formulário. Quando há respostas de mais de um formulário, o cabeçalho recebe o título do formulário como prefixo (`Título do formulário - Pergunta`).

Valores das respostas:

- Campos de texto: o texto digitado pelo avaliador.
- Botões de opção e listas suspensas: o texto da opção escolhida.
- Caixas de seleção: os textos das opções marcadas, separados por `; `.

Com **Exportar apenas respostas numéricas** ativado, só são exportados os valores que são números (por exemplo, um campo de texto com `8.5` ou um botão de opção cujo texto é `4`), e só as perguntas com respostas numéricas ganham coluna.

> As colunas de respostas só aparecem quando a revista ou editora tem avaliações concluídas que usaram um formulário de avaliação.


## Requisitos

- OJS ou OMP 3.4.x ou 3.5.x.
- MySQL/MariaDB ou PostgreSQL.
- Formulários de avaliação (**Configurações > Fluxo de trabalho > Avaliação > Formulários de avaliação**) associados às avaliações, para que haja respostas a exportar.


## Desenvolvimento

O plugin é composto pelas seguintes classes principais:

- `ResponseExporterPlugin`: Classe que registra e gerencia o plugin.
- `ResponseExporterManager`: Responsável pela geração e exportação do CSV, incluindo a conversão da posição das opções gravada no banco para o texto da opção.
- `ResponseExporterDAO`: Lida com as consultas ao banco de dados para obter revisores e respostas.
- `ResponseExporterSettingsForm`: Formulário de configuração no painel administrativo.

Todas as classes vivem sob o namespace `APP\plugins\reports\responseExporter`, seguindo o padrão adotado pelos plugins nativos do OJS/OMP 3.4+ (ex.: `pkp/reviewReport`).


### Testes

O plugin segue o [guia de testes da PKP](https://docs.pkp.sfu.ca/dev/testing/en/plugins-themes): testes de integração escritos com Cypress rodam no GitHub Actions por meio da [pkp/pkp-github-actions](https://github.com/pkp/pkp-github-actions), sobre os [datasets de teste da PKP](https://github.com/pkp/datasets).

- `cypress/tests/functional/ResponseExporter.cy.js`: habilita o plugin, verifica se ele aparece em **Estatísticas > Relatórios**, exporta o CSV com todas as respostas e só com as numéricas, e confere as colunas e os valores.
- `.github/actions/seedReviewForms.php`: os datasets têm avaliações, mas não têm formulários de avaliação; este script adiciona dois formulários com respostas ao contexto `publicknowledge` antes dos testes.
- `.github/actions/tests.sh`: roda o script acima e depois o Cypress; é chamado pela GitHub Action.
- `.github/workflows/main.yml`: roda os testes para OJS e OMP 3.4 e 3.5, com MySQL e PostgreSQL.

Para rodar os testes localmente, monte o OJS ou o OMP a partir do código-fonte, como descrito em [Getting started](https://docs.pkp.sfu.ca/dev/testing/en/getting-started), carregue o dataset correspondente, coloque este plugin em `plugins/reports/responseExporter` e rode, na raiz da aplicação:

```
php plugins/reports/responseExporter/.github/actions/seedReviewForms.php
npx cypress run --config '{"specPattern":["plugins/reports/responseExporter/cypress/tests/functional/*.cy.js"]}'
```

Os arquivos de teste ficam fora do pacote de release (veja `.gitattributes`).


## Licença

Distribuído sob a mesma licença dos sistemas PKP (GNU General Public License). Consulte a [licença do plugin](LICENSE) para mais informações. Consulte a [licença oficial](https://pkp.sfu.ca/software/ojs/license/) para mais informações.

---

## Autor

Desenvolvido por [Felipe Maia Barbosa](https://github.com/webfelipemaia).  
Contribuições, sugestões e correções são bem-vindas!


## Melhorias Futuras

- [ ] Tornar a formatação de datas configurável.
- [ ] Adicionar suporte à exportação dos dados em formato JSON, além do CSV.


## Contribuindo

Pull requests e issues são bem-vindos!  
Para contribuir:

1. Faça um fork do repositório.
2. Crie um branch para sua feature ou correção.
3. Envie um pull request com uma descrição clara da mudança proposta.
