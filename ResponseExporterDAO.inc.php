<?php

/**
 * @file plugins/reports/responseExporter/ResponseExporterDAO.inc.php
 *
 * Copyright (c) 2025 Arquivo Nacional
 * Copyright (c) 2025 Felipe Maia Barbosa
 * Distributed under the GNU GPL v3 . For full terms see the file LICENSE.
 *
 * @class ResponseExporterDAO
 * @ingroup plugins_reports_responseExporter
 *
 * @brief Response report plugin
 */

import('lib.pkp.classes.submission.SubmissionComment');

class ResponseExporterDAO extends DAO
{
    /**
     * Returns the list of reviewers, authors and responses from a submission's review form.
     * @param $contextId int
     * @return object Response
     */

    public function getReviewInfo($contextId)
    {

        $site = Application::get()->getRequest()->getSite();
        $locale = $site->getPrimaryLocale();

        // Query to retrieve reviewer, author and review response data
        $result = $this->retrieve(
            'SELECT ra.reviewer_id AS reviewer_id,
					ra.submission_id AS submission_id,
					ra.date_due AS review_date_due,
					ra.date_response_due AS review_date_response_due,
					reviewer.email AS reviewer_email,
					rusg.setting_value AS reviewer_givenName,
					rusf.setting_value AS reviewer_familyName,
					author.email AS author_email
			FROM review_assignments ra
			LEFT JOIN submissions su ON ra.submission_id = su.submission_id
			LEFT JOIN authors a ON su.current_publication_id = a.publication_id
			LEFT JOIN users reviewer ON reviewer.user_id = ra.reviewer_id
			LEFT JOIN user_settings rusg ON (reviewer.user_id = rusg.user_id AND rusg.setting_name = ? AND rusg.locale = ?)
			LEFT JOIN user_settings rusf ON (reviewer.user_id = rusf.user_id AND rusf.setting_name = ? AND rusf.locale = ?)
			LEFT JOIN users author ON (a.email = author.email AND ra.submission_id = a.publication_id)
			WHERE su.context_id = ?
			ORDER BY ra.reviewer_id',
            [
                'givenName',
                $locale,
                'familyName',
                $locale,
                (int) $contextId
            ]
        );

        return $result;
    }


    /**
     * Returns the responses from the evaluation form.
     * @return object Response
     */

    public function getResponses()
    {


        $result = $this->retrieve('
			SELECT
                review_id,
                response_value
            FROM
                review_form_responses');

        return $result;
    }

    /**
     * Returns the numerical responses of the evaluation form.
     * @return object Response
     */

    public function getNumericalResponses()
    {

        $result = $this->retrieve(
            '
			SELECT
                review_id,
                response_value
            FROM
                review_form_responses
            WHERE
                response_type = ? AND response_value REGEXP "^-?[0-9]+(\\.[0-9]+)?$"',
            ['string']
        );

        return $result;
    }

}
