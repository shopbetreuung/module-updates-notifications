<?php
defined('_VALID_XTC') or die('Direct Access to this location is not allowed.');

use RobinTheHood\ModifiedStdModule\Classes\StdModule;
use RobinTheHood\ModifiedModuleLoaderClient\Loader\RemoteModuleLoader;

require_once DIR_FS_DOCUMENT_ROOT . '/vendor-no-composer/autoload.php';
require_once DIR_FS_DOCUMENT_ROOT . '/ModifiedModuleLoaderClient/vendor/autoload.php';
require_once DIR_FS_INC . '/xtc_php_mail.inc.php';

class fs_module_updates_notifications extends StdModule
{
    public function __construct()
    {
        $this->init('MODULE_FS_MODULE_UPDATES_NOTIFICATIONS');
        $this->addKey('EMAIL_ADDRESS');
        $this->addKey('AVAILABLE_MODULES');
        $this->addKey('CHECK_FOR_UPDATES');
        $this->addKey('LAST_CHECK');
    }

    public function keys()
    {
        return ['MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_STATUS', 'MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_EMAIL_ADDRESS', 'MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_CHECK_FOR_UPDATES'];
    }

    private function sendConfirmationMail($type, $customersId, $emailAddress)
    {
        global $messageStack;

        switch ($type) {
            case 'updates':
                $select_field = 'subscribed_for_new_modules';
                $update_field = 'subscribed_for_module_updates';
                $action = 'activate_subscription_for_updates';
                $text_subject_subscription_to = TEXT_SUBSCRIPTION_TO_MODULE_UPDATES_SUBJECT;
            break;
            case 'new_modules':
                $select_field = 'subscribed_for_module_updates';
                $update_field = 'subscribed_for_new_modules';
                $action = 'activate_subscription_for_new_modules';
                $text_subject_subscription_to = TEXT_SUBSCRIPTION_TO_NEW_MODULES_SUBJECT;
            break;
            default:
                $select_field = '';
                $action = 'activate_subscription_all';
                $text_subject_subscription_to = TEXT_SUBSCRIPTION_TO_ALL_SUBJECT;
            break;
        }

        $code = substr(md5(uniqid(mt_rand(), true)) , 0, 16);
        $link = xtc_href_link_admin('admin/fs_module_updates_notifications.php', 'action=' . $action . '&code='.$code);
        
        if ($select_field != '') {
            $customerSubscribeStatusQuery = xtc_db_query("SELECT " . $select_field . " FROM " . TABLE_CUSTOMERS . " WHERE customers_id = " . $customersId);
            $customerSubscribeStatusResult = xtc_db_fetch_array($customerSubscribeStatusQuery);
            if ($customerSubscribeStatusResult[$select_field] == '1') {
                xtc_db_query("UPDATE " . TABLE_CUSTOMERS . " SET " . $update_field . " = 1 WHERE customers_id = " . $customersId);
                $messageStack->add_session(TEXT_SUBSCRIBED_FOR_UPDATES, 'success');
                xtc_redirect(xtc_href_link_admin('admin/' . FILENAME_MODULE_EXPORT, 'set=system&module=fs_module_updates_notifications&action=edit'));
            }

            xtc_php_mail($emailAddress,
                         $emailAddress,
                         $emailAddress,
                         $emailAddress,
                         '',
                         $emailAddress,
                         $emailAddress,
                         '',
                         '',
                         $text_subject_subscription_to,
                         sprintf(TEXT_SUBSCRIPTION_BODY, $link, $link),
                         sprintf(TEXT_SUBSCRIPTION_BODY, $link, $link)
                         );
            xtc_db_query("UPDATE " . TABLE_CUSTOMERS . " SET " . $update_field . " = '" . $code . "' WHERE customers_id = " . $customersId);
            $messageStack->add_session(sprintf(TEXT_SUBSCRIBED, $emailAddress), 'success');
            xtc_redirect(xtc_href_link_admin('admin/' . FILENAME_MODULE_EXPORT, 'set=system&module=fs_module_updates_notifications'));
        } else {
            xtc_php_mail($emailAddress,
                         $emailAddress,
                         $emailAddress,
                         $emailAddress,
                         '',
                         $emailAddress,
                         $emailAddress,
                         '',
                         '',
                         $text_subject_subscription_to,
                         sprintf(TEXT_SUBSCRIPTION_BODY, $link, $link),
                         sprintf(TEXT_SUBSCRIPTION_BODY, $link, $link)
                         );
            xtc_db_query("UPDATE " . TABLE_CUSTOMERS . " SET subscribed_for_new_modules = '" . $code . "', subscribed_for_module_updates = '" . $code . "'  WHERE customers_id = " . $customersId);
            $messageStack->add_session(sprintf(TEXT_SUBSCRIBED, $emailAddress), 'success');
            xtc_redirect(xtc_href_link_admin('admin/' . FILENAME_MODULE_EXPORT, 'set=system&module=fs_module_updates_notifications'));
        }
    }

    public function process($file) 
    {
        global $messageStack;
        $customersId = $_SESSION['customer_id'] ? $_SESSION['customer_id'] : 0;

        if ($customersId <= 0) return;

        $emailAddress = $_POST['configuration']['MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_EMAIL_ADDRESS'];

        if (!isset($emailAddress) || !xtc_validate_email($emailAddress)) {
            if (trim($emailAddress) == '') {
                $messageStack->add_session(TEXT_UNSUBSCRIBED, 'success');
                xtc_db_query("UPDATE " . TABLE_CUSTOMERS . " SET subscribed_for_module_updates = '0', subscribed_for_new_modules = '0' WHERE customers_id = " . $customersId);
                xtc_redirect(xtc_href_link_admin('admin/' . FILENAME_MODULE_EXPORT, 'set=system&module=fs_module_updates_notifications'));
            }
            $messageStack->add_session(TEXT_ERROR_EMAIL_NOT_VALID, 'error');
            xtc_db_query("UPDATE " . TABLE_CONFIGURATION . " 
                          SET configuration_value = '" . xtc_db_input(MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_EMAIL_ADDRESS) . "' 
                          WHERE configuration_key = 'MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_EMAIL_ADDRESS'");
            xtc_redirect(xtc_href_link_admin('admin/' . FILENAME_MODULE_EXPORT, 'set=system&module=fs_module_updates_notifications'));
        }

        if (isset($_POST['is_subscribed_for_new_modules']) 
            && $_POST['is_subscribed_for_new_modules'] == '1' 
            && isset($_POST['is_subscribed_for_updates']) 
            && $_POST['is_subscribed_for_updates'] == '1') 
        {
            $this->sendConfirmationMail('all', $customersId, $emailAddress);
        }

        if (isset($_POST['is_subscribed_for_new_modules']) && $_POST['is_subscribed_for_new_modules'] == '1') {
            $this->sendConfirmationMail('new_modules', $customersId, $emailAddress);
        }

        if (isset($_POST['is_subscribed_for_updates']) && $_POST['is_subscribed_for_updates'] == '1') {
            $this->sendConfirmationMail('updates', $customersId, $emailAddress);
        }

    }
       
    public function display()
    {
        if (isset($_SESSION['customer_id'])) {
            $subscribedQuery = xtc_db_query("SELECT subscribed_for_module_updates, subscribed_for_new_modules FROM " . TABLE_CUSTOMERS . " WHERE customers_id = " . $_SESSION['customer_id']);
            $subscribedResult = xtc_db_fetch_array($subscribedQuery);

            if ($subscribedResult['subscribed_for_module_updates'] == '0') {
                $subscribeUpdate = xtc_button(BUTTON_SUBSCRIBE_TO_UPDATES, 'button', 'name="subscribe_module_updates"') . '<span id="text_subscribe_updates">' . TEXT_SUBSCRIBE_TO_UPDATES . '</span>';
                $subscribeUpdate .= xtc_draw_input_field('is_subscribed_for_updates', '0', '', '', 'hidden');
            } else if ($subscribedResult['subscribed_for_module_updates'] == '1') {
                $subscribeUpdate = TEXT_SUBSCRIBED_TO_UPDATES;
            } else {
                $subscribeUpdate = TEXT_AWAITING_CONFIRMATION;
            }   

            if ($subscribedResult['subscribed_for_new_modules'] == '0') {
                $subscribeNewModule = xtc_button(BUTTON_SUBSCRIBE_TO_NEW_MODULES, 'button', 'name="subscribe_new_modules"') . '<span id="text_subscribe_new_modules">' . TEXT_SUBSCRIBE_TO_NEW_MODULES . '</span>';
                $subscribeUpdate .= xtc_draw_input_field('is_subscribed_for_new_modules', '0', '', '', 'hidden');
            } else if ($subscribedResult['subscribed_for_new_modules'] == '1') {
                $subscribeNewModule = TEXT_SUBSCRIBED_TO_NEW_MODULES;
            } else {
                $subscribeNewModule = TEXT_AWAITING_CONFIRMATION;
            }
        }

        return [
            'text' => '<hr/>' .
                       $subscribeUpdate . '<hr/>' .
                       $subscribeNewModule . '<hr/>
            <br /><div align="center">' . xtc_button(BUTTON_SAVE) . xtc_button_link(BUTTON_CANCEL, xtc_href_link(FILENAME_MODULE_EXPORT, 'set=' . $_GET['set'] . '&module=fs_module_updates_notifications')) . "</div>"
        ];
    }

    public function install()
    {
        xtc_db_query("ALTER TABLE `customers` 
                        ADD `subscribed_for_module_updates` VARCHAR(24) NULL DEFAULT '0', 
                        ADD `subscribed_for_new_modules` VARCHAR(24) NULL DEFAULT '0';");
        $remoteModuleLoader = RemoteModuleLoader::getModuleLoader();
        $moduleArray = [];
        foreach($remoteModuleLoader->loadAllLatestVersions() as $module) {
            $moduleArray[] = xtc_db_input($module->getName());
        }
        $this->addConfiguration('EMAIL_ADDRESS', '', 6, 1);
        $this->addConfiguration('AVAILABLE_MODULES', serialize($moduleArray), 6, 2);
        $this->addConfiguration('CHECK_FOR_UPDATES', 1, 6, 3, "xtc_cfg_select_option([0 => '" . TEXT_CHECK_DAILY . "', 1 => '" . TEXT_CHECK_WEEKLY . "', 2 => '" . TEXT_CHECK_MONTHLY . "'],");
        $this->addConfiguration('LAST_CHECK', '', 6, 4);
        parent::install();
        $this->setAdminAccess('fs_module_updates_notifications');
    }

    public function remove()
    {
        xtc_db_query("ALTER TABLE " . TABLE_CUSTOMERS . " DROP `subscribed_for_module_updates`, DROP `subscribed_for_new_modules`;");
        parent::remove();
        $this->deleteConfiguration('EMAIL_ADDRESS');
        $this->deleteConfiguration('AVAILABLE_MODULES');
        $this->deleteConfiguration('CHECK_FOR_UPDATES');
        $this->deleteConfiguration('LAST_CHECK');
        $this->deleteAdminAccess('fs_module_updates_notifications');
    }

}