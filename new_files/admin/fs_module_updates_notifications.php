<?php
if (!isset($_GET['action']) || trim($_GET['action']) == '') return;
if (!isset($_GET['code']) || trim($_GET['code']) == '') return;
require_once 'includes/application_top.php';
if (!defined(MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_STATUS) || MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_STATUS != 'true') return;
if (!isset($_SESSION['customer_id'])) return;

$action = $_GET['action'];
$code = $_GET['code'];
$customersId = $_SESSION['customer_id'];

if ($action == 'activate_subscription_for_updates') {
  global $messageStack;
  $subscription_activated = xtc_db_query("UPDATE " . TABLE_CUSTOMERS . " SET subscribed_for_module_updates = 1 WHERE customers_id = " . $customersId . " AND subscribed_for_module_updates = '" . $code . "'");
  if ($subscription_activated) {
    $messageStack->add_session(TEXT_SUBSCRIBED_FOR_UPDATES, 'success');
  } else {
    $messageStack->add_session(TEXT_NOT_SUBSCRIBED_FOR_UPDATES, 'error');
  }
  xtc_redirect(xtc_href_link_admin('admin/' . FILENAME_MODULE_EXPORT, 'set=system&module=fs_module_updates_notifications&action=edit'));
}

if ($action == 'activate_subscription_for_new_modules') {
  global $messageStack;
  $subscription_activated = xtc_db_query("UPDATE " . TABLE_CUSTOMERS . " SET subscribed_for_new_modules = 1 WHERE customers_id = " . $customersId . " AND subscribed_for_new_modules = '" . $code . "'");
  if ($subscription_activated) {
    $messageStack->add_session(TEXT_SUBSCRIBED_FOR_UPDATES, 'success');
  } else {
    $messageStack->add_session(TEXT_NOT_SUBSCRIBED_FOR_UPDATES, 'error');
  }
  xtc_redirect(xtc_href_link_admin('admin/' . FILENAME_MODULE_EXPORT, 'set=system&module=fs_module_updates_notifications&action=edit'));
}