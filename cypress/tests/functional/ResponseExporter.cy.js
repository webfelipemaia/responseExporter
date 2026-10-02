/**
 * @file cypress/tests/functional/ResponseExporter.cy.js
 *
 * Copyright (c) 2025 Arquivo Nacional
 * Copyright (c) 2025 Felipe Maia Barbosa
 * Distributed under the GNU GPL v3 . For full terms see the file LICENSE.
 *
 * Runs against a PKP test dataset (OJS or OMP, "publicknowledge" context) after
 * .github/actions/seedReviewForms.php has added review forms with answers.
 */

describe('Response Exporter plugin tests', function() {
	const reportUrl = '/index.php/publicknowledge/stats/reports/report?pluginName=responseexporterplugin';

	// Minimal RFC 4180 parser: quoted fields, escaped quotes, commas and newlines inside quotes
	function parseCsv(text) {
		const rows = [];
		let row = [], field = '', quoted = false;
		text = text.replace(/^﻿/, '');
		for (let i = 0; i < text.length; i++) {
			const c = text[i];
			if (quoted) {
				if (c === '"' && text[i + 1] === '"') { field += '"'; i++; }
				else if (c === '"') { quoted = false; }
				else { field += c; }
			} else if (c === '"') { quoted = true; }
			else if (c === ',') { row.push(field); field = ''; }
			else if (c === '\n') { row.push(field); rows.push(row); row = []; field = ''; }
			else if (c !== '\r') { field += c; }
		}
		if (field !== '' || row.length) { row.push(field); rows.push(row); }
		return rows;
	}

	// Returns the CSV as a list of objects keyed by header, plus the header itself
	function exportReport() {
		return cy.request(reportUrl).then(response => {
			expect(response.headers['content-type']).to.contain('text/csv');
			expect(response.headers['content-disposition']).to.match(/attachment; filename=reviews-\d{8}\.csv/);
			const [header, ...rows] = parseCsv(response.body);
			rows.forEach(row => expect(row).to.have.length(header.length));
			return {
				header,
				// Question columns of the forms added by .github/actions/seedReviewForms.php
				seeded: header.filter(name => /^RE Form [AB] - /.test(name)),
				rows: rows.map(row => Object.fromEntries(header.map((name, i) => [name, row[i]])))
			};
		});
	}

	function openPluginsTab() {
		cy.visit('/index.php/publicknowledge/management/settings/website');
		cy.get('button[id="plugins-button"]').click();
	}

	function setNumericalOnly(enabled) {
		openPluginsTab();
		// Expand the plugin row to reveal its actions, as the PKP application tests do
		cy.get('tr[id^="component-grid-settings-plugins-settingsplugingrid-category-reports-row-responseexporterplugin"] a.show_extras').click();
		cy.get('a[id^="component-grid-settings-plugins-settingsplugingrid-category-reports-row-responseexporterplugin-settings-button-"]').click();
		cy.get('form#reportSettingsForm').should('be.visible');
		cy.wait(1000); // Form init delay
		cy.get(enabled ? 'input#numericalAnswersTrue' : 'input#numericalAnswersFalse').check();
		cy.get('form#reportSettingsForm button[id^="submitFormButton"]').click();
		cy.get('form#reportSettingsForm').should('not.exist');
	}

	beforeEach(function() {
		cy.login('admin', 'admin', 'publicknowledge');
	});

	it('Enables the plugin', function() {
		openPluginsTab();
		cy.get('input[id^="select-cell-responseexporterplugin-enabled"]').then($checkbox => {
			if (!$checkbox.is(':checked')) {
				cy.wrap($checkbox).click();
				cy.get('div:contains(\'The plugin "Review Response Exporter Plugin" has been enabled.\')');
			}
		});
	});

	it('Lists the report on the statistics reports page', function() {
		cy.visit('/index.php/publicknowledge/stats/reports');
		cy.contains('Review Response Exporter Plugin');
	});

	it('Exports all responses, one column per question', function() {
		setNumericalOnly(false);
		exportReport().then(({header, seeded, rows}) => {
			expect(header.slice(0, 8)).to.deep.equal([
				'Submission ID', 'Review Due Date', 'Response Due Date', 'Reviewer ID',
				'Reviewer Email', 'Reviewer Last Name', 'Reviewer First Name', 'Author Email'
			]);
			// Questions follow the form sequence and are prefixed by the form title,
			// as answers from two review forms are present
			expect(seeded).to.deep.equal([
				'RE Form A - Overall grade', 'RE Form A - Originality score', 'RE Form A - Comments',
				'RE Form A - Strengths', 'RE Form A - Recommendation', 'RE Form B - Score'
			]);

			// One row per review assignment: a submission with several authors must not
			// produce rows that only differ by the author email
			expect(rows.length).to.be.greaterThan(3);
			const withoutAuthor = rows.map(row => JSON.stringify({...row, 'Author Email': null}));
			const authorsByRow = {};
			withoutAuthor.forEach((key, i) => (authorsByRow[key] = authorsByRow[key] || new Set()).add(rows[i]['Author Email']));
			Object.values(authorsByRow).forEach(authors => expect(authors.size).to.equal(1));
			rows.forEach(row => {
				expect(row['Reviewer Email']).to.contain('@');
				expect(row['Reviewer Last Name']).to.not.equal('');
			});

			const first = rows.find(row => row['RE Form A - Comments'] === 'Very good');
			expect(first, 'row with the first set of answers').to.exist;
			expect(first['RE Form A - Overall grade']).to.equal('4');
			expect(first['RE Form A - Originality score']).to.equal('7.5');
			expect(first['RE Form A - Strengths']).to.equal('Clarity; Data');
			expect(first['RE Form A - Recommendation']).to.equal('Accept');
			expect(first['RE Form B - Score']).to.equal('');

			const second = rows.find(row => row['RE Form A - Comments'] === 'Weak');
			expect(second, 'row with the second set of answers').to.exist;
			expect(second['RE Form A - Overall grade']).to.equal('1');
			expect(second['RE Form A - Originality score']).to.equal('');
			expect(second['RE Form A - Strengths']).to.equal('');
			expect(second['RE Form A - Recommendation']).to.equal('Reject');

			const third = rows.find(row => row['RE Form B - Score'] === '9');
			expect(third, 'row answered with the second form').to.exist;
			expect(third['RE Form A - Overall grade']).to.equal('');
		});
	});

	it('Exports numerical responses only', function() {
		setNumericalOnly(true);
		exportReport().then(({seeded, rows}) => {
			expect(seeded).to.deep.equal([
				'RE Form A - Overall grade', 'RE Form A - Originality score', 'RE Form B - Score'
			]);
			const first = rows.find(row => row['RE Form A - Originality score'] === '7.5');
			expect(first['RE Form A - Overall grade']).to.equal('4');
			expect(rows.find(row => row['RE Form B - Score'] === '9')).to.exist;
		});
		setNumericalOnly(false);
	});
});
