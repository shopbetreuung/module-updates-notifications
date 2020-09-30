<?php
defined( '_VALID_XTC' ) or die( 'Direct Access to this location is not allowed.' );
if (pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME) != 'module_export') return;
if (!defined(MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_STATUS) || MODULE_FS_MODULE_UPDATES_NOTIFICATIONS_STATUS != 'true') return;
?>

<script>
$(document).ready(function(){
  text_checked = '<?php echo TEXT_SAVE_TO_SUBSCRIBE; ?>';

  $('input[name="subscribe_module_updates"]').on('click', function(){
    toggleSubscriptionCheck('update', $(this));
  });

  $('input[name="subscribe_new_modules"]').on('click', function(){
    toggleSubscriptionCheck('new_modules', $(this));
  });
});


function toggleSubscriptionCheck(type, el)
{
  switch(type) {
    case 'update':
      var is_checked = $('input[name="is_subscribed_for_updates"]');
      var el_text_subscribe = $('#text_subscribe_updates');
      var text_unchecked = '<?php echo TEXT_SUBSCRIBE_TO_UPDATES; ?>';
      break;
    case 'new_modules':
      var is_checked = $('input[name="is_subscribed_for_new_modules"]');
      var el_text_subscribe = $('#text_subscribe_new_modules');
      var text_unchecked = '<?php echo TEXT_SUBSCRIBE_TO_NEW_MODULES; ?>';
      break;

    default:
  }
  el.toggleClass('checked_for_subscription');
  if (is_checked.val() == '1') {
    is_checked.val('0');
    el_text_subscribe.empty();
    el_text_subscribe.html(text_unchecked);
  } else {
    is_checked.val('1');
    el_text_subscribe.empty();
    el_text_subscribe.html(text_checked);
  }
}
</script>