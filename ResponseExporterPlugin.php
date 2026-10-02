<?php

/**
 * @file plugins/reports/responseExporter/ResponseExporterPlugin.php
 *
 * Copyright (c) 2025 Arquivo Nacional
 * Copyright (c) 2025 Felipe Maia Barbosa
 * Distributed under the GNU GPL v3 . For full terms see the file LICENSE.
 *
 * @class ResponseExporterPlugin
 * @ingroup plugins_reports_responseExporter
 *
 * @brief Class that performs operations using an instance of ResponseExporterManager to export reports.
 */

namespace APP\plugins\reports\responseExporter;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\config\Config;
use PKP\core\JSONMessage;
use PKP\core\PKPApplication;
use PKP\db\DAORegistry;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\linkAction\request\RedirectAction;
use PKP\plugins\GenericPlugin;

class ResponseExporterPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::register()
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        // NOTE: this used to check Config::getVar('reports', 'installed'), a section/key
        // that doesn't exist in config.inc.php. Fixed to the standard 'general.installed' check.
        if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) {
            return true;
        }

        $responseExporterDAO = new ResponseExporterDAO();
        DAORegistry::registerDAO('ResponseExporterDAO', $responseExporterDAO);

        return $success;
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
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $verb)
    {
        $router = $request->getRouter();
        $dispatcher = $request->getDispatcher();
        return array_merge(
            $this->getEnabled() ? [
                new LinkAction(
                    'settings',
                    new AjaxModal(
                        $router->url($request, null, null, 'manage', null, ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'reports']),
                        $this->getDisplayName()
                    ),
                    __('manager.plugins.settings'),
                    null
                ),
            ] : [],
            $this->getEnabled() ? [
                new LinkAction(
                    'export',
                    new RedirectAction($dispatcher->url(
                        $request,
                        PKPApplication::ROUTE_PAGE,
                        null,
                        'stats',
                        'reports',
                        // The path must be an array: Dispatcher::url() requires ?array since 3.5.0
                        ['report'],
                        ['pluginName' => $this->getName()]
                    )),
                    __('manager.statistics.reports'),
                    null
                ),
            ] : [],
            parent::getActions($request, $verb),
        );
    }

    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request)
    {
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $context = $request->getContext();

                // NOTE: AppLocale::requireComponents() was removed here. It was a no-op since 3.4.0
                // (all locale keys are already loaded) and the AppLocale class itself no longer
                // exists as of OJS/OMP 3.5.0.
                $templateMgr = TemplateManager::getManager($request);
                $templateMgr->registerPlugin('function', 'plugin_url', [$this, 'smartyPluginUrl']);

                $form = new ResponseExporterSettingsForm($this, $context->getId());

                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    if ($form->validate()) {
                        $form->execute();
                        return new JSONMessage(true);
                    }
                } else {
                    $form->initData();
                }
                return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

    /**
     * Displays content using the ResponseExporterManager's display() method.
     *
     * @uses  ReportPlugin::display().
     *
     * @return void
     */
    public function display($args, $request)
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();

        $numericalAnswers = $this->getSetting($context->getId(), 'numericalAnswersEnabled');
        $args['numericalAnswersEnabled'] = $numericalAnswers;

        $responseExporter = new ResponseExporterManager();
        $responseExporterDAO = new ResponseExporterDAO();

        $responseExporter->setResponseExporterDAO($responseExporterDAO);
        $responseExporter->display($args, $request);
    }
}
