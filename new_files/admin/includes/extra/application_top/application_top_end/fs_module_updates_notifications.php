<?php
if (!defined(MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_STATUS) || MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_STATUS != 'true') return;
if (pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME) == 'module_export') return;
use RobinTheHood\ModifiedModuleLoaderClient\DependencyManager;
use RobinTheHood\ModifiedModuleLoaderClient\ModuleFilter;
use RobinTheHood\ModifiedModuleLoaderClient\Loader\RemoteModuleLoader;
require_once DIR_WS_INCLUDES . '/application_top.php';
require_once DIR_FS_DOCUMENT_ROOT . '/ModifiedModuleLoaderClient/vendor/autoload.php';
require_once DIR_FS_INC . '/xtc_php_mail.inc.php';
$customersId = $_SESSION['customer_id'] ? $_SESSION['customer_id'] : 0;

if ($customersId == 0) return;

$customersSubscriptionsQuery = xtc_db_query("SELECT subscribed_for_module_updates, subscribed_for_new_modules FROM " . TABLE_CUSTOMERS . " WHERE customers_id = '" . $customersId . "'");
$customersSubscriptionsResult = xtc_db_fetch_array($customersSubscriptionsQuery);
$notifyUpdate = $customersSubscriptionsResult['subscribed_for_module_updates'] == '1' ? true : false;
$notifyNewModule = $customersSubscriptionsResult['subscribed_for_new_modules'] == '1' ? true : false;

$lastCheck = MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_LAST_CHECK ? strtotime(MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_LAST_CHECK) : '';

switch (MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_CHECK_FOR_UPDATES) {
  case 0:
    $cycle = 1 * 60 * 60 * 24;
  break;
  case 1:
    $cycle = 7 * 60 * 60 * 24;
  break;
  case 2:
    $cycle = date('t') * 60 * 60 * 24;
  break;
  default:
    $cycle = 7 * 60 * 60 * 24;
  break;
}

if (trim($lastCheck) != '' && $cycle < (time() - $lastCheck)) {
  xtc_db_query("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value=now() WHERE configuration_key = 'MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_LAST_CHECK'");
  $dependencyManager = new DependencyManager;
  $updatableModules = ModuleFilter::filterUpdatable($dependencyManager->getInstalledModules());
  $remoteModuleLoader = RemoteModuleLoader::getModuleLoader();

  foreach($remoteModuleLoader->loadAllLatestVersions() as $module) {
    $moduleArray[] = $module->getName();
  }

  if (MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_AVAILABLE_MODULES) {
    $yesterdaysModuleArray = unserialize(MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_AVAILABLE_MODULES);  
    $newModules = array_diff($moduleArray, $yesterdaysModuleArray);
    if ($newModules && $notifyNewModule) {
      xtc_php_mail(MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_EMAIL_ADDRESS,
                  MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_EMAIL_ADDRESS,
                  MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_EMAIL_ADDRESS,
                  MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_EMAIL_ADDRESS,
                  '',
                  MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_EMAIL_ADDRESS,
                  MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_EMAIL_ADDRESS,
                  '',
                  '',
                  TEXT_NEW_MODULES_AVAILABLE,
                  sprintf(TEXT_FOLLOWING_MODULES_ARE_AVAILABLE, implode(',', $newModules)),
                  sprintf(TEXT_FOLLOWING_MODULES_ARE_AVAILABLE, implode(',', $newModules))
                  );
      xtc_db_query("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '" . serialize($moduleArray) . "' WHERE configuration_key = 'MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_AVAILABLE_MODULES'");
    }
  } 
  if (sizeof($updatableModules) > 0) {
    foreach($updatableModules as $module) {
      $modulesToUpdate[] = $module->getName();
    }
    xtc_php_mail(MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_EMAIL_ADDRESS,
                MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_EMAIL_ADDRESS,
                MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_EMAIL_ADDRESS,
                MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_EMAIL_ADDRESS,
                '',
                MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_EMAIL_ADDRESS,
                MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_EMAIL_ADDRESS,
                '',
                '',
                TEXT_MODULE_UPDATES_AVAILABLE,
                sprintf(TEXT_FOLLOWING_MODULES_ARE_AVAILABLE_FOR_UPDATE, implode(',', $modulesToUpdate)),
                sprintf(TEXT_FOLLOWING_MODULES_ARE_AVAILABLE_FOR_UPDATE, implode(',', $modulesToUpdate))
                );
  }
} 

?>