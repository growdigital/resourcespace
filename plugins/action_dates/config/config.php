<?php
$action_dates_deletefield      = 0;
$action_dates_eligible_states  = array(0);
$action_dates_restrictfield    = 0;
$action_dates_reallydelete     = false;
$action_dates_email_admin_days = '';
$action_dates_new_state        = 3;
$action_dates_remove_from_collection = true;
$action_dates_extra_config = array();

// Add any new vars that specify metadata fields to this array to stop them being deleted if plugin is in use
// These are added in hooks/all.php
$action_dates_fieldvars = array("action_dates_deletefield","action_dates_restrictfield");
