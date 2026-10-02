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

echo "Run cypress tests"
npx cypress run --headless --browser chrome --config '{"specPattern":["plugins/reports/responseExporter/cypress/tests/functional/*.cy.js"]}'
