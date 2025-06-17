<?php

/**
 * @file plugins/reports/responseExporter/ResponseExporterPlugin.inc.php
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

import('lib.pkp.classes.plugins.GenericPlugin');

class ResponseExporterPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::register()
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if (!Config::getVar('reports', 'installed') || defined('RUNNING_UPGRADE')) {
            return true;
        }

        $this->import('ResponseExporterDAO');
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
        import('lib.pkp.classes.linkAction.request.AjaxModal');
        import('lib.pkp.classes.linkAction.request.RedirectAction');
        return array_merge(
            $this->getEnabled() ? array(
                new LinkAction(
                    'settings',
                    new AjaxModal(
                        $router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'reports')),
                        $this->getDisplayName()
                    ),
                    __('manager.plugins.settings'),
                    null
                ),
            ) : array(),
            $this->getEnabled() ? array(
                new LinkAction(
                    'export',
                    new RedirectAction($dispatcher->url(
                        $request,
                        ROUTE_PAGE,
                        null,
                        'stats',
                        'reports',
                        'report',
                        array('pluginName' => $this->getName())
                    )),
                    __('manager.statistics.reports'),
                    null
                ),
            ) : array(),
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

                AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON, LOCALE_COMPONENT_PKP_MANAGER);
                $templateMgr = TemplateManager::getManager($request);
                $templateMgr->registerPlugin('function', 'plugin_url', array($this, 'smartyPluginUrl'));

                $this->import('ResponseExporterSettingsForm');
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

        if (!class_exists('ResponseExporterManager')) {
            $this->import('ResponseExporterManager');
        }
        $responseExporter = new ResponseExporterManager();

        if (!class_exists('ResponseExporterDAO')) {
            $this->import('ResponseExporterDAO');
        }
        $responseExporterDAO = new ResponseExporterDAO();

        $responseExporter->setResponseExporterDAO($responseExporterDAO);
        $responseExporter->display($args, $request);
    }
}
