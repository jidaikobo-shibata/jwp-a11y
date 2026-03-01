<?php
namespace A11yc;

include(dirname(__DIR__).'/checklist/inc_submenu.php');

echo '<form action="'.Util::s(A11YC_BULK_URL.'criterion&amp;focus='.$focus.'&amp;criterion='.$criterion.'&amp;integrate=1').'" method="POST">';
echo wp_nonce_field('jwp_a11y_bulk_action', 'jwp_a11y_nonce', true, false);
$vvv = $yml['criterions'][$criterion];
$iclchks = array();
$results = array();
$issues  = array();
$cs      = array();
$url     = '';
$page_id = '0';

include(dirname(__DIR__).'/checklist/inc_criterion_form.php');

echo '<div id="a11yc_submit">';
echo '<input type="hidden" value="1" />';
echo '<input type="submit" value="'.A11YC_LANG_CTRL_SEND.'" />';
echo '</div>';

echo '</form>';
