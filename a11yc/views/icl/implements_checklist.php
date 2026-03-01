<?php
namespace A11yc;

$techs = Yaml::each('techs');
$criterions = Yaml::each('criterions');
$icls = Model\Icl::fetchAll(true);
$html = '';

if ( ! $is_view):
	include('inc_submenu.php');
	$html.= '<form action="'.A11YC_ICL_URL.'index" method="POST">';
	$html.= isset($icl_action_nonce) ? $icl_action_nonce : '';
endif;

foreach (Model\Icl::fetchTree( ! $is_view) as $criterion => $parents):

	$html.= '<h2>'.Util::key2code($criterion).' '.Util::s($criterions[$criterion]['name']).' ['.Util::s($criterions[$criterion]['level']['name']).']</h2>';

	// main loop
	foreach ($parents as $parent => $ids):

		// draw situation
		if (isset($icls[$parent])):
			$html.= '<input type="hidden" name="icls[]" id="icls_'.intval($parent).'" value="'.intval($parent).'">';
			$html.= '<h3>'.Util::s($icls[$parent]['title']);
			if ( ! $is_view):
				if (Arr::get($icls[$parent], 'trash', 0) != 0):
					if (isset($icl_action_nonce) && ! empty($icl_action_nonce)):
						$html.= '<form action="'.Util::s(A11YC_ICL_URL.'undelete&amp;is_sit=1&amp;id='.intval($parent)).'" method="POST" style="display:inline;">'.$icl_action_nonce.'<button type="submit" class="button-link">'.A11YC_LANG_CTRL_UNDELETE.'</button></form> - ';
						$html.= '<form action="'.Util::s(A11YC_ICL_URL.'purge&amp;is_sit=1&amp;id='.intval($parent)).'" method="POST" style="display:inline;">'.$icl_action_nonce.'<button type="submit" class="button-link" data-a11yc-confirm="'.Util::s(sprintf(A11YC_LANG_CTRL_CONFIRM, A11YC_LANG_CTRL_PURGE)).'">'.A11YC_LANG_CTRL_PURGE.'</button></form>';
					else:
						$html.= '<a href="'.A11YC_ICL_URL.'undelete&amp;is_sit=1&amp;id='.intval($parent).'">'.A11YC_LANG_CTRL_UNDELETE.'</a> - ';
						$html.= '<a href="'.A11YC_ICL_URL.'purge&amp;is_sit=1&amp;id='.intval($parent).'" data-a11yc-confirm="'.sprintf(A11YC_LANG_CTRL_CONFIRM, A11YC_LANG_CTRL_PURGE).'">'.A11YC_LANG_CTRL_PURGE.'</a>';
					endif;
				else:
					$html.= '<a href="'.Util::s(A11YC_ICL_URL.'edit&amp;is_sit=1&amp;id='.intval($parent)).'">'.A11YC_LANG_CTRL_LABEL_EDIT.'</a>'.' - ';
					if (isset($icl_action_nonce) && ! empty($icl_action_nonce)):
						$html.= '<form action="'.Util::s(A11YC_ICL_URL.'delete&amp;is_sit=1&amp;id='.intval($parent)).'" method="POST" style="display:inline;">'.$icl_action_nonce.'<button type="submit" class="button-link">'.A11YC_LANG_CTRL_DELETE.'</button></form>';
					else:
						$html.= '<a href="'.A11YC_ICL_URL.'delete&amp;is_sit=1&amp;id='.intval($parent).'">'.A11YC_LANG_CTRL_DELETE.'</a>';
					endif;
				endif;
			endif;
			$html.= '</h3>';
		endif;

		// draw thead
		$html.= '<table class="a11yc_table">';
		$html.= '<thead>';
		$html.= '<tr>';
		if ( ! $is_view):
			$html.= '<th scope="col">'.A11YC_LANG_CTRL_CHECK.'</th>';
		endif;
		$html.= '<th scope="col" style="width:30%;">'.A11YC_LANG_ICL_IMPLEMENT.'</th>';
		if ($is_view):
			$html.= '<th scope="col" style="width:12%;">'.A11YC_LANG_ICL_ID.'</th>';
			$html.= '<th scope="col">'.A11YC_LANG_ICL_RELATED.'</th>';
		endif;
		$html.= '<th scope="col" style="width:30%;">'.A11YC_LANG_ICL_VALIDATE.'</th>';
		if ( ! $is_view):
			$html.= '<th scope="col">'.A11YC_LANG_CTRL_ACT.'</th>';
		endif;
		$html.= '</tr>';
		$html.= '</thead>';

		foreach ($ids as $id):
			if ( ! isset($icls[$id])) continue;
			$val = $icls[$id];

			// title and checkbox
			$html.= '<tr><td>';
			if ( ! $is_view):
				$checked = in_array($id, $checks) ? ' checked="checked"' : '';
				$html.= '<input type="checkbox" name="icls[]" id="icls_'.intval($id).'" value="'.intval($id).'"'.$checked.'></td>';
				$html.= '<td><label for="icls_'.intval($id).'">'.Util::s($val['title_short']).'</label>';
			else:
				$html.= Util::s($val['title_short']);
			endif;
			$html.= '</td><td>';

			if ($is_view):
				// identifier
					$html.= Util::s($val['identifier']);
				$html.= '</td><td>';

				// techs
				$ul = '';
				foreach ($val['techs'] as $tech):
					if ( ! isset($techs[$tech])) continue;

						$ul.= '<li>'.Util::s($techs[$tech]['title']).'</li>';
				endforeach;
				$html.= ! empty($ul) ? '<ul>'.$ul.'</ul>' : '';
				$html.= '</td><td>';
			endif;

			// inspection
			$html.= nl2br(Util::s($val['inspection']));
			$html.= '</td>';

			// ctrl
			if ( ! $is_view):
				$html.= '<td class="a11yc_result" style="white-space: nowrap;">';
				if (Arr::get($val, 'trash', 0) != 0):
						if (isset($icl_action_nonce) && ! empty($icl_action_nonce)):
							$html.= '<form action="'.Util::s(A11YC_ICL_URL.'undelete&amp;id='.intval($id)).'" method="POST" style="display:inline;">'.$icl_action_nonce.'<button type="submit" class="button-link">'.A11YC_LANG_CTRL_UNDELETE.'</button></form> - ';
							$html.= '<form action="'.Util::s(A11YC_ICL_URL.'purge&amp;id='.intval($id)).'" method="POST" style="display:inline;">'.$icl_action_nonce.'<button type="submit" class="button-link" data-a11yc-confirm="'.Util::s(sprintf(A11YC_LANG_CTRL_CONFIRM, A11YC_LANG_CTRL_PURGE)).'">'.A11YC_LANG_CTRL_PURGE.'</button></form>';
						else:
							$html.= '<a href="'.A11YC_ICL_URL.'undelete&amp;id='.intval($id).'">'.A11YC_LANG_CTRL_UNDELETE.'</a> - ';
							$html.= '<a href="'.A11YC_ICL_URL.'purge&amp;id='.intval($id).'" data-a11yc-confirm="'.sprintf(A11YC_LANG_CTRL_CONFIRM, A11YC_LANG_CTRL_PURGE).'">'.A11YC_LANG_CTRL_PURGE.'</a>';
						endif;
					else:
						$html.= '<a href="'.Util::s(A11YC_ICL_URL.'read&amp;id='.intval($id)).'">'.A11YC_LANG_CTRL_VIEW.'</a>'.' - ';
						$html.= '<a href="'.Util::s(A11YC_ICL_URL.'edit&amp;id='.intval($id)).'">'.A11YC_LANG_CTRL_LABEL_EDIT.'</a>'.' - ';
						if (isset($icl_action_nonce) && ! empty($icl_action_nonce)):
							$html.= '<form action="'.Util::s(A11YC_ICL_URL.'delete&amp;id='.intval($id)).'" method="POST" style="display:inline;">'.$icl_action_nonce.'<button type="submit" class="button-link">'.A11YC_LANG_CTRL_DELETE.'</button></form>';
						else:
							$html.= '<a href="'.A11YC_ICL_URL.'delete&amp;id='.intval($id).'">'.A11YC_LANG_CTRL_DELETE.'</a>';
						endif;
					endif;
				$html.= '</td>';
			endif;

			$html.= '</tr>';
		endforeach;
		$html.= '</table>';

	endforeach;

endforeach;

if ( ! $is_view):
	$html.= '<div id="a11yc_submit">';
	$html.= '<input type="submit" value="'.A11YC_LANG_CTRL_SEND.'">';
	$html.= '</div>';
	$html.= '</form>';
endif;

echo $html;
