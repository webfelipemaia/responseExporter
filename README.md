# Response Exporter Plugin for OJS/OMP

**English** | [Português (Brasil)](README.pt_BR.md)

**Response Exporter** is a report plugin for PKP's [Open Journal Systems (OJS)](https://pkp.sfu.ca/ojs/) and [Open Monograph Press (OMP)](https://pkp.sfu.ca/omp/). It exports the review form responses submitted by reviewers during the editorial process.

The plugin generates CSV files containing reviewer data, author data and review form responses. It can also export numerical responses only, which is useful for statistical analysis.


## Features

- Exports the reviews carried out for each submission.
- Includes detailed reviewer information (name, email, due dates).
- Includes the email of the author related to the submission.
- Exports review form responses, one column per review form question.
- Shows the option label (not its internal position) for radio buttons, drop-down boxes and checkboxes.
- Option to export numerical responses only.
- Works with MySQL/MariaDB and PostgreSQL.


## Compatibility

Release **1.0.0.0** follows the namespaced (PSR-4) plugin style introduced in OJS/OMP 3.4 and is compatible with:

- **OJS/OMP 3.4.x** (PHP 8.0.2 or later)
- **OJS/OMP 3.5.x** (PHP 8.2 or later)

OJS/OMP 3.3 and earlier are not supported.


## Installation

1. Copy the `responseExporter` plugin folder into the `plugins/reports/` directory of your OJS or OMP installation. It must be under `plugins/reports/` (not `plugins/generic/`), otherwise the plugin classes cannot be found.
2. Log in to the administration dashboard as a Manager or Editor.
3. Go to **Plugins > Reports** and enable the `Response Exporter` plugin.


## Usage

1. Open the menu:  
   `Statistics > Reports`
2. Click **Export** on the `Response Exporter` item.
3. A CSV file with the collected data will be generated and downloaded automatically.


## Settings

The plugin has one optional setting:

- **Export numerical responses only**: useful for quantitative reports.

To configure it:

1. Click **Settings** in the plugin menu.
2. Check or uncheck the `Export numerical responses only` option.


## CSV File Format

Each CSV row represents one review assignment. Column headers are written in the user's interface language; in English they are:

- `Submission ID`
- `Review Due Date`
- `Response Due Date` (due date for responding to the review invitation)
- `Reviewer ID`
- `Reviewer Email`
- `Reviewer Last Name`
- `Reviewer First Name`
- `Author Email`: email of the submission's primary contact, or of its first author when no primary contact is set. Empty when the submission has no authors.
- One column per review form question, headed by the question text and ordered as in the form. When more than one review form has answers, the header is prefixed with the form title (`Form title - Question`).

Response values:

- Text fields: the text typed by the reviewer.
- Radio buttons and drop-down boxes: the label of the chosen option.
- Checkboxes: the labels of the checked options, separated by `; `.

With **Export numerical responses only** enabled, only values that are numbers are exported (for example, a text field containing `8.5` or a radio button whose option label is `4`), and only the questions with numerical answers get a column.

> Response columns only appear when the journal or press has completed reviews that used a review form.


## Requirements

- OJS or OMP 3.4.x or 3.5.x.
- MySQL/MariaDB or PostgreSQL.
- Review forms (**Settings > Workflow > Review > Review Forms**) assigned to the reviews, so that there are responses to export.


## Development

The plugin consists of the following main classes:

- `ResponseExporterPlugin`: Registers and manages the plugin.
- `ResponseExporterManager`: Generates and exports the CSV, including converting stored option positions back to option labels.
- `ResponseExporterDAO`: Handles the database queries that fetch reviewers and responses.
- `ResponseExporterSettingsForm`: Settings form in the administration dashboard.

All classes live under the `APP\plugins\reports\responseExporter` namespace, following the pattern adopted by the native OJS/OMP 3.4+ plugins (e.g. `pkp/reviewReport`).


### Tests

The plugin follows the [PKP testing guide](https://docs.pkp.sfu.ca/dev/testing/en/plugins-themes): integration tests written with Cypress run on GitHub Actions through [pkp/pkp-github-actions](https://github.com/pkp/pkp-github-actions), against the [PKP test datasets](https://github.com/pkp/datasets).

- `cypress/tests/functional/ResponseExporter.cy.js`: enables the plugin, checks it is listed under **Statistics > Reports**, exports the CSV with all responses and with numerical responses only, and checks the columns and values.
- `.github/actions/seedReviewForms.php`: the datasets have review assignments but no review forms, so this script adds two review forms with answers to the `publicknowledge` context before the tests run.
- `.github/actions/tests.sh`: runs the script above and then Cypress; it is called by the GitHub Action.
- `.github/workflows/main.yml`: runs the tests for OJS and OMP 3.4 and 3.5, with MySQL and PostgreSQL.

To run the tests locally, set up OJS or OMP from source as described in [Getting started](https://docs.pkp.sfu.ca/dev/testing/en/getting-started), load the matching dataset, place this plugin at `plugins/reports/responseExporter` and run, from the application root:

```
php plugins/reports/responseExporter/.github/actions/seedReviewForms.php
npx cypress run --config '{"specPattern":["plugins/reports/responseExporter/cypress/tests/functional/*.cy.js"]}'
```

The test files are excluded from the release package (see `.gitattributes`).


## License

Distributed under the same license as the PKP applications (GNU General Public License). See the [plugin license](LICENSE) for more information. See the [official license](https://pkp.sfu.ca/software/ojs/license/) for more information.

---

## Author

Developed by [Felipe Maia Barbosa](https://github.com/webfelipemaia).  
Contributions, suggestions and fixes are welcome!


## Future Improvements

- [ ] Make date formatting configurable.
- [ ] Add support for exporting data as JSON in addition to CSV.


## Contributing

Pull requests and issues are welcome!  
To contribute:

1. Fork the repository.
2. Create a branch for your feature or fix.
3. Open a pull request with a clear description of the proposed change.
