<?php

/**
 * @file plugins/reports/responseExporter/ResponseExporterManager.php
 *
 * Copyright (c) 2025 Arquivo Nacional
 * Copyright (c) 2025 Felipe Maia Barbosa
 * Distributed under the GNU GPL v3 . For full terms see the file LICENSE.
 *
 * @class ResponseExporterManager
 * @ingroup plugins_reports_responseExporter
 * @see ResponseExporterDAO
 *
 * @brief Response exporter plugin
 */

namespace APP\plugins\reports\responseExporter;

use PKP\plugins\ReportPlugin;

class ResponseExporterManager extends ReportPlugin
{
    private $_responseExporterDAO;

    /**
    * @copydoc Plugin::register()
    */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        $this->addLocaleData();
        return $success;
    }


    /**
     * @copydoc Plugin::getName()
     */
    public function getName(): string
    {
        return static::class;
    }

    /**
    * @copydoc Plugin::getDisplayName()
    */
    public function getDisplayName()
    {
        return __('plugins.reports.responseExporter.displayName');
    }

    /**
    * @copydoc Plugin::getDescription()
    */
    public function getDescription()
    {
        return __('plugins.reports.responseExporter.description');
    }

    /**
     * get date published
     * @return date
     */
    public function getResponseExporterDAO()
    {
        return $this->_responseExporterDAO;
    }

    /**
     * Define the DAO (Data Access Object) responsible for operations with response reports.
     *
     * @param ResponseExporterDAO $responseExporterDAO Object for accessing reporting data.
     * @return void
     */
    public function setResponseExporterDAO($responseExporterDAO)
    {
        $this->_responseExporterDAO = $responseExporterDAO;
    }

    /**
    * @copydoc ReportPlugin::display()
    */
    public function display($args, $request)
    {

        $context = $request->getContext();

        $responseExporterDAO = $this->_responseExporterDAO;
        // NOTE: AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION) was removed here.
        // It has been a no-op since 3.4.0 (all locale keys are already loaded) and the AppLocale
        // class itself no longer exists as of OJS/OMP 3.5.0.

        // TODO: make date formatting editable
        // TODO: enable extraction in json format
        header('content-type: text/comma-separated-values');
        header('content-disposition: attachment; filename=reviews-' . date('Ymd') . '.csv');

        $groupedResponses = [];

        // Decide which method to call based on the configuration
        $responses = !empty($args['numericalAnswersEnabled'])
            ? $responseExporterDAO->getNumericalResponses()
            : $responseExporterDAO->getResponses();

        foreach ($responses as $response) {
            $reviewId = $response->review_id;
            if (!isset($groupedResponses[$reviewId])) {
                $groupedResponses[$reviewId] = [];
            }
            $groupedResponses[$reviewId][] = $response->response_value;
        }

        // Determine the maximum number of columns for responses
        $maxResponseColumns = array_reduce(
            $groupedResponses,
            function ($acv, $item) {
                return max($acv, count($item));
            },
            0
        );

        // CSV Header
        $header = [
            'submission_id' => __('plugins.reports.reviews.submissionId'),
                    'review_date_due' => __('reviewer.submission.reviewDueDate'),
                    'review_date_response_due' => __('reviewer.submission.responseDueDate'),
                    'reviewer_id' => __('plugins.reports.responseExporter.reviewer.id'),
                    'reviewer_email' => __('plugins.reports.responseExporter.reviewer.email'),
                    'reviewer_familyName' => __('plugins.reports.responseExporter.reviewer.familyName'),
                    'reviewer_givenName' => __('plugins.reports.responseExporter.reviewer.givenName'),
                    'author_email' => __('plugins.reports.responseExporter.author.email'),
        ];

        $responseValueName = __('plugins.reports.responseExporter.response.value');

        for ($i = 1; $i <= $maxResponseColumns; $i++) {
            $header[] = $responseValueName . '_' . $i;
        }

        // Write data directly to script output
        $fp = fopen('php://output', 'wt');
        // Add BOM (Byte Order Mark) to ensure Excel opens the file correctly
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        // Write header to CSV
        fputcsv($fp, $header);
        // Write data to CSV
        foreach ($responseExporterDAO->getReviewInfo($context->getId()) as $reviewer) {
            // NOTE: this used to key $groupedResponses by $reviewer->reviewer_id (the reviewer's
            // user id). review_form_responses.review_id references review_assignments.review_id
            // (the review assignment's own id), an entirely different id space from reviewer_id.
            // Whenever a reviewer_id numerically matched some other review's review_id, that
            // review's answers silently leaked into this reviewer's row. Fixed to key by review_id.
            $reviewId = $reviewer->review_id;
            $row = [
                $reviewer->submission_id,
                $reviewer->review_date_due,
                $reviewer->review_date_response_due,
                $reviewer->reviewer_id,
                $reviewer->reviewer_email,
                $reviewer->reviewer_familyName,
                $reviewer->reviewer_givenName,
                $reviewer->author_email,
            ];

            // Add the answers
            if (isset($groupedResponses[$reviewId])) {
                $row = array_merge($row, $groupedResponses[$reviewId]);
            }

            // Fill with empty values ​​if necessary
            while (count($row) < count($header)) {
                $row[] = '';
            }

            fputcsv($fp, $row);
        }

        fclose($fp);
    }
}
