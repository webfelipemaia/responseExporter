<?php

/**
 * @file plugins/reports/responseExporter/ResponseExporterDAO.php
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

namespace APP\plugins\reports\responseExporter;

use APP\core\Application;
use PKP\db\DAO;

class ResponseExporterDAO extends DAO
{
    /**
     * Returns one row per review assignment of the context, with reviewer data
     * and the email of the submission's primary contact.
     *
     * @param int $contextId
     *
     * @return iterable
     */
    public function getReviewInfo($contextId)
    {
        $site = Application::get()->getRequest()->getSite();
        $locale = $site->getPrimaryLocale();

        // The author is resolved through the publication's primary contact (or its first
        // author when no primary contact is set), so each review assignment yields exactly
        // one row regardless of the number of authors.
        return $this->retrieve(
            'SELECT ra.review_id AS review_id,
                    ra.reviewer_id AS reviewer_id,
                    ra.submission_id AS submission_id,
                    ra.date_due AS review_date_due,
                    ra.date_response_due AS review_date_response_due,
                    reviewer.email AS reviewer_email,
                    rusg.setting_value AS reviewer_given_name,
                    rusf.setting_value AS reviewer_family_name,
                    a.email AS author_email
            FROM review_assignments ra
            JOIN submissions su ON ra.submission_id = su.submission_id
            LEFT JOIN publications p ON p.publication_id = su.current_publication_id
            LEFT JOIN authors a ON a.author_id = COALESCE(
                p.primary_contact_id,
                (SELECT fa.author_id FROM authors fa WHERE fa.publication_id = p.publication_id ORDER BY fa.seq, fa.author_id LIMIT 1)
            )
            LEFT JOIN users reviewer ON reviewer.user_id = ra.reviewer_id
            LEFT JOIN user_settings rusg ON (reviewer.user_id = rusg.user_id AND rusg.setting_name = ? AND rusg.locale = ?)
            LEFT JOIN user_settings rusf ON (reviewer.user_id = rusf.user_id AND rusf.setting_name = ? AND rusf.locale = ?)
            WHERE su.context_id = ?
            ORDER BY ra.submission_id, ra.review_id',
            [
                'givenName',
                $locale,
                'familyName',
                $locale,
                (int) $contextId
            ]
        );
    }

    /**
     * Returns the review form responses of the context, ordered by review form
     * and by the position of each element (question) inside its form.
     *
     * @param int $contextId
     *
     * @return iterable
     */
    public function getResponses($contextId)
    {
        return $this->retrieve(
            'SELECT rfr.review_id AS review_id,
                    rfr.review_form_element_id AS review_form_element_id,
                    rfr.response_type AS response_type,
                    rfr.response_value AS response_value
            FROM review_form_responses rfr
            JOIN review_form_elements rfe ON rfe.review_form_element_id = rfr.review_form_element_id
            JOIN review_assignments ra ON ra.review_id = rfr.review_id
            JOIN submissions su ON su.submission_id = ra.submission_id
            WHERE su.context_id = ?
            ORDER BY rfe.review_form_id, rfe.seq, rfe.review_form_element_id',
            [(int) $contextId]
        );
    }
}
