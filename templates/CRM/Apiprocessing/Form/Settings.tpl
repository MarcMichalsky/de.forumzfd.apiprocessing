<div class="crm-block crm-form-block">
  {* HEADER *}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>
  <div class="help-block" id="help">
    {ts}You can configure the settings that will be used in the API traffic between the public website(s) and CiviCRM here.{/ts}
  </div>
  <div class="crm-section">
    <div class="label">{$form.forumzfd_error_activity_type_id.label}</div>
    <div class="content">{$form.forumzfd_error_activity_type_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.forumzfd_error_activity_assignee_id.label}</div>
    <div class="content">{$form.forumzfd_error_activity_assignee_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.akademie_error_activity_type_id.label}</div>
    <div class="content">{$form.akademie_error_activity_type_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.akademie_error_activity_assignee_id.label}</div>
    <div class="content">{$form.akademie_error_activity_assignee_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.new_contacts_group_id.label}</div>
    <div class="content">{$form.new_contacts_group_id.html}</div>
    <div class="clear"></div>
  </div>
 <div class="crm-section">
    <div class="label">{$form.fzfd_petition_signed_activity_type_id.label}</div>
    <div class="content">{$form.fzfd_petition_signed_activity_type_id.html}</div>
    <div class="clear"></div>
  </div>

  {* FOOTER *}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
