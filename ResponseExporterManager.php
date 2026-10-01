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

use PKP\db\DAORegistry;
use PKP\plugins\ReportPlugin;
use PKP\reviewForm\ReviewFormDAO;
use PKP\reviewForm\ReviewFormElement;
use PKP\reviewForm\ReviewFormElementDAO;

class ResponseExporterManager extends ReportPlugin
{
    private $_responseExporterDAO;

    /** @var array<int, ?ReviewFormElement> Review form elements already loaded, by id */
    private $_reviewFormElements = [];

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
        $numericalOnly = !empty($args['numericalAnswersEnabled']);

        // TODO: make date formatting editable
        // TODO: enable extraction in json format
        header('content-type: text/csv; charset=utf-8');
        header('content-disposition: attachment; filename=reviews-' . date('Ymd') . '.csv');

        // One column per review form element (question), in form/sequence order.
        // Keying by element keeps every column aligned to the same question in all
        // rows, even when some answers are missing or filtered out.
        $columns = [];
        $groupedResponses = [];
        foreach ($responseExporterDAO->getResponses($context->getId()) as $response) {
            $element = $this->getReviewFormElement((int) $response->review_form_element_id);
            if (!$element) {
                continue;
            }

            $value = $this->formatResponse(
                $element,
                $responseExporterDAO->convertFromDB($response->response_value, $response->response_type)
            );
            if ($numericalOnly && !is_numeric($value)) {
                continue;
            }

            $columns[$element->getId()] = $element;
            $groupedResponses[$response->review_id][$element->getId()] = $value;
        }

        // CSV Header
        $header = [
            __('plugins.reports.responseExporter.submission.Id'),
            __('reviewer.submission.reviewDueDate'),
            __('reviewer.submission.responseDueDate'),
            __('plugins.reports.responseExporter.reviewer.id'),
            __('plugins.reports.responseExporter.reviewer.email'),
            __('plugins.reports.responseExporter.reviewer.familyName'),
            __('plugins.reports.responseExporter.reviewer.givenName'),
            __('plugins.reports.responseExporter.author.email'),
        ];

        $reviewFormIds = array_unique(array_map(fn ($element) => $element->getReviewFormId(), $columns));
        $columnNumber = 0;
        foreach ($columns as $element) {
            $columnNumber++;
            $label = $this->toPlainText($element->getLocalizedQuestion());
            if ($label === '') {
                $label = __('plugins.reports.responseExporter.response.value') . '_' . $columnNumber;
            }
            // Disambiguate questions when more than one review form is exported
            if (count($reviewFormIds) > 1) {
                $label = $this->getReviewFormTitle($element->getReviewFormId()) . ' - ' . $label;
            }
            $header[] = $label;
        }

        // Write data directly to script output
        $fp = fopen('php://output', 'wt');
        // Add BOM (Byte Order Mark) to ensure Excel opens the file correctly
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($fp, $header);
        foreach ($responseExporterDAO->getReviewInfo($context->getId()) as $reviewer) {
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

            // Responses are keyed by review_id (review_form_responses.review_id
            // references review_assignments.review_id), never by reviewer_id.
            $answers = $groupedResponses[$reviewer->review_id] ?? [];
            foreach (array_keys($columns) as $elementId) {
                $row[] = $answers[$elementId] ?? '';
            }

            fputcsv($fp, $row);
        }

        fclose($fp);
    }

    /**
     * Converts a stored response into the text shown to the reviewer.
     *
     * Radio buttons and checkboxes store the position of the chosen option(s);
     * drop-down boxes store the key of the chosen option. Both are translated
     * back to the option label.
     *
     * @param ReviewFormElement $element
     * @param mixed $value Value already converted from its database type
     *
     * @return string
     */
    protected function formatResponse($element, $value)
    {
        $options = (array) $element->getLocalizedPossibleResponses();
        switch ($element->getElementType()) {
            case ReviewFormElement::REVIEW_FORM_ELEMENT_TYPE_RADIO_BUTTONS:
                return $this->getOptionLabel(array_values($options), $value);
            case ReviewFormElement::REVIEW_FORM_ELEMENT_TYPE_DROP_DOWN_BOX:
                return $this->getOptionLabel($options, $value);
            case ReviewFormElement::REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES:
                $positions = array_values($options);
                return implode('; ', array_map(
                    fn ($position) => $this->getOptionLabel($positions, $position),
                    (array) $value
                ));
            default:
                return trim((string) $value);
        }
    }

    /**
     * Returns the label of an option, falling back to the stored value when
     * the option no longer exists in the form.
     *
     * @param array $options
     * @param mixed $key
     *
     * @return string
     */
    protected function getOptionLabel($options, $key)
    {
        if (is_scalar($key) && isset($options[$key])) {
            return $this->toPlainText($options[$key]);
        }
        return is_scalar($key) ? (string) $key : '';
    }

    /**
     * Strips the markup that rich-text form fields may contain.
     *
     * @param ?string $text
     *
     * @return string
     */
    protected function toPlainText($text)
    {
        return trim(html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Returns a review form element, cached for the duration of the export.
     *
     * @param int $elementId
     *
     * @return ?ReviewFormElement
     */
    protected function getReviewFormElement($elementId)
    {
        if (!array_key_exists($elementId, $this->_reviewFormElements)) {
            $reviewFormElementDao = DAORegistry::getDAO('ReviewFormElementDAO'); /** @var ReviewFormElementDAO $reviewFormElementDao */
            $this->_reviewFormElements[$elementId] = $reviewFormElementDao->getById($elementId);
        }
        return $this->_reviewFormElements[$elementId];
    }

    /**
     * Returns the localized title of a review form.
     *
     * @param int $reviewFormId
     *
     * @return string
     */
    protected function getReviewFormTitle($reviewFormId)
    {
        $reviewFormDao = DAORegistry::getDAO('ReviewFormDAO'); /** @var ReviewFormDAO $reviewFormDao */
        $reviewForm = $reviewFormDao->getById($reviewFormId);
        return $reviewForm ? $this->toPlainText($reviewForm->getLocalizedTitle()) : (string) $reviewFormId;
    }
}
