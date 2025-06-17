<?php

/**
 * @file plugins/reports/responseExporter/ResponseExporterPlugin.inc.php
 *
 * Copyright (c) 2025 Arquivo Nacional
 * Copyright (c) 2025 Felipe Maia Barbosa
 * Distributed under the GNU GPL v3 . For full terms see the file LICENSE.
 *
 * @class ResponseExporterSettingsForm
 * @ingroup plugins_reports_responseExporter
 *
 * @brief Response report plugin
 */

import('lib.pkp.classes.form.Form');

class ResponseExporterSettingsForm extends Form
{
    // TODO: migrar para boas práticas de orientação a objetos
    /** @var int */
    public $_journalId;

    /** @var object */
    public $_plugin;

    /**
     * Constructor
     * @param $plugin ResponseExporterPlugin
     * @param $journalId int
     */
    public function __construct($plugin, $journalId)
    {
        $this->_journalId = $journalId;
        $this->_plugin = $plugin;

        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $this->addCheck(new FormValidator($this, 'numericalAnswersEnabled', 'optional', 'plugins.reports.responseExporter.settings.validation'));
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * Initialize form data.
     */
    public function initData()
    {
        $initialValue = $this->convertBooleanToString(
            $this->_plugin->getSetting($this->_journalId, 'numericalAnswersEnabled')
        );
        $this->_data = array(
            'numericalAnswersEnabled' => $initialValue,
        );
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        $this->readUserVars(array('numericalAnswersEnabled'));
    }

    /**
     * @copydoc Form::fetch()
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->_plugin->getName());
        return parent::fetch($request, $template, $display);
    }

    /**
     * Converts a string to a boolean value.
     *
     * @param string $value The string to be converted.
     * @return bool Returns true if the string is "true", "1", "yes", or "on" (case-insensitive).
     *              Returns false for any other value.
     */
    public function convertStringToBoolean($value)
    {

        $lowerValue = strtolower($value);
        return in_array($lowerValue, ['true', '1', 'yes', 'on']);
    }

    /**
     * Converts a boolean value to a string ("true" or "false").
     *
     * @param bool $value The boolean value to be converted.
     * @return string Returns "true" if the value is true, otherwise returns "false".
     */
    public function convertBooleanToString($value)
    {
        return $value === true ? 'true' : 'false';
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $booleanValue = $this->convertStringToBoolean($this->getData('numericalAnswersEnabled'));
        $this->_plugin->updateSetting(
            $this->_journalId,
            'numericalAnswersEnabled',
            $booleanValue,
            'bool'
        );
        parent::execute(...$functionArgs);
    }
}
