<?php
namespace A11yc;

include(dirname(__DIR__).'/checklist/inc_submenu.php');

echo '<form action="'.Util::s(A11YC_BULK_URL.'criterion&amp;focus='.$focus.'&amp;criterion='.$criterion).'" method="POST">';
echo wp_nonce_field('jwp_a11y_bulk_action', 'jwp_a11y_nonce', true, false);
$vvv = $yml['criterions'][$criterion];
foreach ($pages as $page):
  $iclchks = Arr::get($page, 'iclchks', array());
  $results = Arr::get($page, 'results', array());
  $issues  = Arr::get($page, 'issues', array());
  $cs      = Arr::get($page, 'cs', array());
  $url     = Arr::get($page, 'url', '');
  $page_id = Arr::get($page, 'dbid', '');

  echo '<h2>'.Util::s($page['title']).' ('.Util::s($page['url']).')</h2>';
  include(dirname(__DIR__).'/checklist/inc_criterion_form.php');
endforeach;

echo '<div id="a11yc_submit">';
echo '<input type="submit" value="'.A11YC_LANG_CTRL_SEND.'" />';
echo '</div>';

echo '</form>';
