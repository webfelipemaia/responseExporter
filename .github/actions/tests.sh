#!/bin/bash

# @file .github/actions/tests.sh
#
# Copyright (c) 2025 Arquivo Nacional
# Copyright (c) 2025 Felipe Maia Barbosa
# Distributed under the GNU GPL v3 . For full terms see the file LICENSE.
#
# Plugin tests, run by pkp/pkp-github-actions from the application root
# after the PKP test dataset has been loaded.

set -e

echo "Add review forms with answers to the test dataset"
php plugins/reports/responseExporter/.github/actions/seedReviewForms.php

# The CI serves the application with the single-threaded "php -S" server. Warm it up
# (and log how long the first page takes) so Cypress' first visit does not hit its
# 30s response timeout while the server is still busy with the first request.
echo "Warm up the web server"
for attempt in 1 2 3; do
	if curl -s -o /dev/null -m 300 -w "Login page: HTTP %{http_code} in %{time_total}s\n" http://localhost/index.php/publicknowledge/en/login; then
		break
	fi
	echo "Warm-up request ${attempt} failed; last lines of the PHP server log:"
	tail -n 20 access.log || true
done

echo "Run cypress tests"
npx cypress run --headless --browser chrome --config '{"specPattern":["plugins/reports/responseExporter/cypress/tests/functional/*.cy.js"]}'
