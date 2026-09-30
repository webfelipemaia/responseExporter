# Changelog

## 2.0.0.0 — 2026-09-13

Port de compatibilidade para OJS/OMP 3.4 e 3.5, com correção de bugs encontrados durante a portabilidade.

### Compatibilidade (motivo do port)

O OJS/OMP 3.5 removeu por completo dois mecanismos usados pela release 1.0.0.0:

- **A função global `import()`**, usada em `ResponseExporterPlugin.inc.php` e `ResponseExporterDAO.inc.php` para carregar classes do core. Deprecated desde o 3.4.0, removida no 3.5.0.
- **A classe `AppLocale`**, usada em `ResponseExporterPlugin::manage()` e `ResponseExporterManager::display()`. Existia como classe deprecated (sem efeito prático — `requireComponents()` já era um no-op desde o 3.4.0) até o 3.4.x; não existe mais em nenhum lugar do código-fonte a partir do 3.5.0.

Qualquer um dos dois já é suficiente para o plugin falhar ao carregar (erro fatal capturado internamente pelo `PluginRegistry` do 3.5, que registra o erro em log e simplesmente não lista o plugin — sem derrubar o restante do site).

Mudanças feitas para restaurar a compatibilidade:

- Todas as classes passaram a viver sob o namespace `APP\plugins\reports\responseExporter`, com `use` explícito das classes do PKP (`PKP\plugins\GenericPlugin`, `PKP\plugins\ReportPlugin`, `PKP\db\DAO`, `PKP\linkAction\...`, `PKP\form\...`, etc.), no lugar de `import()`.
- Arquivos renomeados de `.inc.php` para `.php`, seguindo a convenção usada pelo próprio PKP a partir do 3.4 (ex.: `pkp/reviewReport`, `plugins/reports/counter` no core do OJS).
- As chamadas a `AppLocale::requireComponents(...)` foram removidas (eram no-op desde o 3.4.0 — todas as chaves de locale já são carregadas automaticamente).
- `index.php` simplificado para `return new \APP\plugins\reports\responseExporter\ResponseExporterPlugin();` — o `PluginRegistry` do 3.4+ localiza a classe diretamente via autoload PSR-4 (`\APP\plugins\reports\responseExporter\ResponseExporterPlugin`), sem precisar do wrapper legado.
- Pasta de locale `locale/en_US/` renomeada para `locale/en/`. A partir do 3.4.0, o OJS/OMP mescla variantes regionais de idioma nos códigos base (`en_US` → `en`, ver `MergeLocalesMigration`); a pasta antiga nunca seria lida em uma instalação 3.4+. `locale/pt_BR/` não foi afetado (o `pt_BR` continua sendo um locale distinto do `pt` genérico).

Esta release **não é compatível com OJS/OMP 3.2/3.3** (essas versões não têm as classes namespaced `PKP\...` usadas aqui). Para essas versões, use a release 1.0.0.0.

### Correções de bugs (independentes da portabilidade)

Encontradas ao rodar o plugin com dados reais durante os testes desta portabilidade; existiam desde a 1.0.0.0 e não têm relação com a versão do OJS/OMP:

- **`ResponseExporterManager::display()` associava as respostas ao revisor errado.** As respostas eram agrupadas por `review_id` (chave de `review_form_responses`, que referencia `review_assignments.review_id` — o id da própria avaliação), mas na hora de montar a linha do CSV a busca no array agrupado usava `reviewer_id` (id do usuário revisor) como chave. Como são dois espaços de ID numéricos independentes, sempre que um `reviewer_id` coincidia numericamente com o `review_id` de *outra* avaliação (de qualquer revisor, em qualquer submissão), as respostas dessa avaliação "vazavam" para a linha do revisor errado, e as respostas do revisor certo ficavam faltando. Confirmado de forma reprodutível testando com múltiplos revisores/avaliações reais. Corrigido: `ResponseExporterDAO::getReviewInfo()` agora também seleciona `ra.review_id`, e `ResponseExporterManager::display()` usa esse `review_id` (não mais `reviewer_id`) como chave de junção com as respostas.
- **`ResponseExporterPlugin::register()`** verificava `Config::getVar('reports', 'installed')`. Não existe seção `[reports]` em `config.inc.php` — a verificação padrão usada pelo próprio core é `Config::getVar('general', 'installed')`. Corrigido.
- **`ResponseExporterManager::display()`** chamava `$responseExporterDAO->getReviewInfo($context)`, passando o objeto `Context` inteiro para um parâmetro que a DAO usa como `(int) $contextId`. Corrigido para `$context->getId()`.
- **`ResponseExporterDAO::getReviewInfo()`**: o `LEFT JOIN users author` comparava `ra.submission_id = a.publication_id` — dois espaços de ID diferentes (submissão vs. publicação), o que praticamente sempre falhava e deixava `author_email` vazio. Corrigido para comparar apenas por e-mail (`author.email = a.email`), que é o dado que a consulta realmente precisa.
- Import morto `import('lib.pkp.classes.submission.SubmissionComment')` em `ResponseExporterDAO.inc.php` removido — a classe nunca foi usada no arquivo.

## 1.0.0.0 — 2025-03-18

Release inicial, compatível com OJS/OMP 3.2 e 3.3 (estilo de plugin legado, sem namespace).
