{**
 * plugins/reports/responseExporter/templates/settingsForm.tpl
 *
 * Copyright (c) 2025 Arquivo Nacional
 * Copyright (c) 2025 Felipe Maia Barbosa
 * Distributed under the GNU GPL v3 . For full terms see the file LICENSE.
 *
 * Response Exporter plugin settings
 *
 *}
<script>
	$(function() {ldelim}
		$('#reportSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="reportSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="reports" plugin=$pluginName verb="settings" save=true}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="reportSettingsFormNotification"}

	<div id="description">{translate key="plugins.reports.responseExporter.settings.description"}</div>

	<h4>{translate key="plugins.reports.responseExporter.settings.form.numericalLabel"}</h4>

	{fbvFormArea id="reportSettingsFormArea"}
		{fbvFormSection list=true}
			{fbvElement type="radio" id="numericalAnswersTrue" name="numericalAnswersEnabled" value="true" checked=$numericalAnswersEnabled|compare:"true" label="plugins.reports.responseExporter.settings.form.radio.labelYes"}
			{fbvElement type="radio" id="numericalAnswersFalse" name="numericalAnswersEnabled" value="false" checked=$numericalAnswersEnabled|compare:"false" label="plugins.reports.responseExporter.settings.form.radio.labelNo"}
		{/fbvFormSection}
	{/fbvFormArea}
	
{fbvFormButtons submitText="common.save"}
	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</form>
