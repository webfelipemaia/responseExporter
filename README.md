# Response Exporter Plugin for OJS/OMP

O **Response Exporter** é um plugin de relatório para os sistemas [Open Journal Systems (OJS)](https://pkp.sfu.ca/ojs/) e [Open Monograph Press (OMP)](https://pkp.sfu.ca/omp/) da PKP. Ele permite a exportação das respostas de formulários de avaliação preenchidos por revisores durante o processo editorial.

Este plugin gera arquivos CSV contendo dados de revisores, autores e respostas dos formulários de avaliação. Também é possível exportar apenas respostas numéricas, úteis para análises estatísticas.


## Funcionalidades

- Exporta dados de revisões realizadas por submissão.
- Inclui informações detalhadas dos revisores (nome, e-mail, datas de vencimento).
- Inclui o e-mail do autor relacionado à submissão.
- Exporta respostas de formulários de avaliação (textuais ou apenas numéricas).
- Opção para exportar somente respostas numéricas.


## Compatibilidade

A partir da release **2.0.0.0**, o código do plugin foi portado para o estilo de plugin com namespace (PSR-4) exigido a partir do OJS/OMP 3.4, e é compatível com:

- **OJS/OMP 3.4.x**
- **OJS/OMP 3.5.x**

> Isso foi necessário porque o OJS/OMP 3.5 removeu por completo a função `import()` e a classe `AppLocale`, usadas pelo estilo legado de plugin (`.inc.php`, sem namespace). Veja [CHANGELOG.md](CHANGELOG.md) para os detalhes técnicos.

Para instalações em **OJS/OMP 3.2 ou 3.3**, use a release **1.0.0.0** (branch/tag anterior a este port), que mantém o estilo legado de plugin compatível com essas versões.


## Instalação

1. Copie a pasta do plugin `responseExporter` para o diretório `plugins/reports/` da sua instalação do OJS ou OMP.
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

Cada linha do CSV representa uma avaliação/revisão, com as seguintes colunas:

- `submission_id`: ID da submissão.
- `review_date_due`: Data limite da avaliação.
- `review_date_response_due`: Data limite para resposta do convite.
- `reviewer_id`: ID do revisor.
- `reviewer_email`: E-mail do revisor.
- `reviewer_familyName`: Sobrenome do revisor.
- `reviewer_givenName`: Primeiro nome do revisor.
- `author_email`: E-mail do autor (da submissão ou monografia).
- `response_value_1`, `response_value_2`, ...: Colunas com as respostas do formulário de avaliação.


## Requisitos

- OJS ou OMP 3.x com suporte a plugins de relatório.
- Formulários de avaliação com respostas registradas em `review_form_responses`.


## Desenvolvimento

O plugin é composto pelas seguintes classes principais:

- `ResponseExporterPlugin`: Classe que registra e gerencia o plugin.
- `ResponseExporterManager`: Responsável pela geração e exportação do CSV.
- `ResponseExporterDAO`: Lida com as consultas ao banco de dados para obter revisores e respostas.
- `ResponseExporterSettingsForm`: Formulário de configuração no painel administrativo.

Todas as classes vivem sob o namespace `APP\plugins\reports\responseExporter`, seguindo o padrão adotado pelos plugins nativos do OJS/OMP 3.4+ (ex.: `pkp/reviewReport`).


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
