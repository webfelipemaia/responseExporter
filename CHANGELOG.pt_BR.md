# Changelog

[English](CHANGELOG.md) | **Português (Brasil)**

## 1.0.0.0 — 2026-10-01

Primeiro lançamento público, para OJS/OMP 3.4.x e 3.5.x.

### Funcionalidades

- Plugin de relatório (**Estatísticas > Relatórios**) que exporta um arquivo CSV com uma linha por avaliação da revista ou editora.
- Dados do avaliador (ID, e-mail, nome e sobrenome), prazos da avaliação e da resposta ao convite, e o e-mail do contato principal da submissão (ou do primeiro autor, quando não há contato principal).
- Uma coluna por pergunta do formulário de avaliação, com o texto da pergunta no cabeçalho, na ordem definida no formulário. Quando há respostas de mais de um formulário, o cabeçalho recebe o título do formulário como prefixo.
- Respostas de botões de opção, listas suspensas e caixas de seleção são exportadas com o texto das opções, e não com a posição gravada no banco de dados.
- Configuração para exportar apenas respostas numéricas, incluindo opções cujo texto é um número (por exemplo, uma escala de notas de 1 a 5 feita com botões de opção).
- Suporte a MySQL/MariaDB e PostgreSQL.
- Traduções para inglês e português do Brasil.

### Notas técnicas

- Plugin com namespace (`APP\plugins\reports\responseExporter`), carregado por autoload PSR-4, como exigido a partir do OJS/OMP 3.4; não usa a função `import()` nem a classe `AppLocale`, ambas removidas no OJS/OMP 3.5.
- O plugin precisa ser instalado em `plugins/reports/responseExporter`.
- As respostas do formulário são ligadas a cada linha pelo `review_id` (a avaliação), nunca pelo ID de usuário do avaliador.
- Só são lidas as respostas de formulário da revista ou editora atual.
- Testes de integração com Cypress, rodando no GitHub Actions (pkp/pkp-github-actions) para OJS e OMP 3.4 e 3.5, com MySQL e PostgreSQL.
