# Changelog

**English** | [Português (Brasil)](CHANGELOG.pt_BR.md)

## 1.0.0.0 — 2026-10-01

Initial public release, for OJS/OMP 3.4.x and 3.5.x.

### Features

- Report plugin (**Statistics > Reports**) that exports a CSV file with one row per review assignment of the journal or press.
- Reviewer data (ID, email, given and family name), review and response due dates, and the email of the submission's primary contact.
- One column per review form question, headed by the question text and kept in the order defined in the form. When answers from more than one review form are exported, the header is prefixed with the form title.
- Radio button, drop-down box and checkbox answers are exported as the option labels, not as the option positions stored in the database.
- Setting to export numerical responses only, including numeric option labels (e.g. a 1–5 grade scale built with radio buttons).
- MySQL/MariaDB and PostgreSQL support.
- English and Brazilian Portuguese translations.

### Technical notes

- Namespaced plugin (`APP\plugins\reports\responseExporter`), loaded through PSR-4 autoloading as required since OJS/OMP 3.4; it does not use the `import()` function or the `AppLocale` class, both removed in OJS/OMP 3.5.
- The plugin must be installed under `plugins/reports/responseExporter`.
- Review form responses are joined to each row by `review_id` (the review assignment), never by the reviewer's user id.
- Only the review form responses of the current journal or press are read.
