<?php

/**
 * @file .github/actions/seedReviewForms.php
 *
 * Copyright (c) 2025 Arquivo Nacional
 * Copyright (c) 2025 Felipe Maia Barbosa
 * Distributed under the GNU GPL v3 . For full terms see the file LICENSE.
 *
 * @brief Test fixture: adds two review forms with answers to existing review
 *  assignments of the "publicknowledge" context of a PKP test dataset, so the
 *  Cypress tests have review form responses to export. The PKP datasets have
 *  review assignments but no review forms.
 *
 *  Run from the application root: php plugins/reports/responseExporter/.github/actions/seedReviewForms.php
 *  It is idempotent: forms created by a previous run are removed first.
 *  Not part of the release package (see .gitattributes).
 */

if (PHP_SAPI !== 'cli') {
    exit;
}

require getcwd() . '/tools/bootstrap.php';

use APP\core\Application;
use Illuminate\Support\Facades\DB;
use PKP\cliTool\CommandLineTool;
use PKP\reviewForm\ReviewFormElement;

class SeedReviewForms extends CommandLineTool
{
    public const FORM_TITLES = ['RE Form A', 'RE Form B'];

    public function execute()
    {
        $context = Application::getContextDAO()->getByPath('publicknowledge');
        if (!$context) {
            throw new Exception('Context "publicknowledge" not found; is a PKP test dataset loaded?');
        }
        $locale = $context->getPrimaryLocale();
        $assocType = Application::getContextAssocType();

        DB::transaction(function () use ($context, $locale, $assocType) {
            $this->removePreviousRun();

            $reviewIds = DB::table('review_assignments as ra')
                ->join('submissions as s', 's.submission_id', '=', 'ra.submission_id')
                ->where('s.context_id', $context->getId())
                ->orderBy('ra.review_id')
                ->limit(3)
                ->pluck('ra.review_id')
                ->all();
            if (count($reviewIds) < 3) {
                throw new Exception('The dataset needs at least 3 review assignments in "publicknowledge".');
            }

            $formA = $this->createForm(self::FORM_TITLES[0], 1, $assocType, $context->getId(), $locale);
            $formB = $this->createForm(self::FORM_TITLES[1], 2, $assocType, $context->getId(), $locale);

            // Elements are created out of order on purpose: the export must follow "seq"
            $comments = $this->createElement($formA, 3, ReviewFormElement::REVIEW_FORM_ELEMENT_TYPE_TEXTAREA, '<p>Comments</p>', null, $locale);
            $grade = $this->createElement($formA, 1, ReviewFormElement::REVIEW_FORM_ELEMENT_TYPE_RADIO_BUTTONS, 'Overall grade', ['1', '2', '3', '4', '5'], $locale);
            $originality = $this->createElement($formA, 2, ReviewFormElement::REVIEW_FORM_ELEMENT_TYPE_SMALL_TEXT_FIELD, 'Originality score', null, $locale);
            $strengths = $this->createElement($formA, 4, ReviewFormElement::REVIEW_FORM_ELEMENT_TYPE_CHECKBOXES, 'Strengths', ['Clarity', 'Method', 'Data'], $locale);
            $recommendation = $this->createElement($formA, 5, ReviewFormElement::REVIEW_FORM_ELEMENT_TYPE_DROP_DOWN_BOX, 'Recommendation', ['Accept', 'Reject'], $locale);
            $score = $this->createElement($formB, 1, ReviewFormElement::REVIEW_FORM_ELEMENT_TYPE_SMALL_TEXT_FIELD, 'Score', null, $locale);

            [$first, $second, $third] = $reviewIds;
            DB::table('review_assignments')->whereIn('review_id', [$first, $second])->update(['review_form_id' => $formA]);
            DB::table('review_assignments')->where('review_id', $third)->update(['review_form_id' => $formB]);

            $this->answer($first, $comments, 'string', 'Very good');
            $this->answer($first, $grade, 'int', '3');                // option "4"
            $this->answer($first, $originality, 'string', '7.5');
            $this->answer($first, $strengths, 'object', '["0","2"]'); // "Clarity; Data"
            $this->answer($first, $recommendation, 'int', '0');       // "Accept"
            $this->answer($second, $grade, 'int', '0');               // option "1"
            $this->answer($second, $comments, 'string', 'Weak');
            $this->answer($second, $recommendation, 'int', '1');      // "Reject"
            $this->answer($third, $score, 'string', '9');

            echo 'Review form answers added to review assignments ' . implode(', ', $reviewIds) . "\n";
        });
    }

    protected function removePreviousRun(): void
    {
        $formIds = DB::table('review_form_settings')
            ->where('setting_name', 'title')
            ->whereIn('setting_value', self::FORM_TITLES)
            ->pluck('review_form_id')
            ->all();
        if (!$formIds) {
            return;
        }
        $elementIds = DB::table('review_form_elements')->whereIn('review_form_id', $formIds)->pluck('review_form_element_id')->all();
        DB::table('review_form_responses')->whereIn('review_form_element_id', $elementIds)->delete();
        DB::table('review_form_element_settings')->whereIn('review_form_element_id', $elementIds)->delete();
        DB::table('review_form_elements')->whereIn('review_form_id', $formIds)->delete();
        DB::table('review_assignments')->whereIn('review_form_id', $formIds)->update(['review_form_id' => null]);
        DB::table('review_form_settings')->whereIn('review_form_id', $formIds)->delete();
        DB::table('review_forms')->whereIn('review_form_id', $formIds)->delete();
    }

    protected function createForm(string $title, int $seq, int $assocType, int $contextId, string $locale): int
    {
        $formId = DB::table('review_forms')->insertGetId(
            ['assoc_type' => $assocType, 'assoc_id' => $contextId, 'seq' => $seq, 'is_active' => 1],
            'review_form_id'
        );
        $this->setting('review_form_settings', 'review_form_id', $formId, 'title', $title, $locale);
        return $formId;
    }

    protected function createElement(int $formId, int $seq, int $type, string $question, ?array $options, string $locale): int
    {
        $elementId = DB::table('review_form_elements')->insertGetId(
            ['review_form_id' => $formId, 'seq' => $seq, 'element_type' => $type, 'required' => 0, 'included' => 1],
            'review_form_element_id'
        );
        $this->setting('review_form_element_settings', 'review_form_element_id', $elementId, 'question', $question, $locale);
        if ($options !== null) {
            $this->setting('review_form_element_settings', 'review_form_element_id', $elementId, 'possibleResponses', json_encode($options), $locale, 'object');
        }
        return $elementId;
    }

    protected function setting(string $table, string $key, int $id, string $name, string $value, string $locale, string $type = 'string'): void
    {
        DB::table($table)->insert([$key => $id, 'locale' => $locale, 'setting_name' => $name, 'setting_value' => $value, 'setting_type' => $type]);
    }

    protected function answer(int $reviewId, int $elementId, string $type, string $value): void
    {
        DB::table('review_form_responses')->insert([
            'review_id' => $reviewId,
            'review_form_element_id' => $elementId,
            'response_type' => $type,
            'response_value' => $value,
        ]);
    }
}

(new SeedReviewForms($argv ?? []))->execute();
