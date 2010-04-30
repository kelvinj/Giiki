<?php
$term = $_GET['term'];

$pages = $g->search($term);

$results = array();
foreach ($pages as $p) {
	$results[] = array(
		'id' => $p->id,
		'label' => $p->get_name(),
		'value' => $p->get_name(),
		'url' => $p->name
	);
}

usort($results, 'sort_results');

echo json_encode($results);

/**
 * Sorts results
 */
function sort_results ($a, $b) {
	global $term;

	$a_has_term_in_title = (stristr($a['value'], $term) !== false);
	$b_has_term_in_title = (stristr($b['value'], $term) !== false);
	$cmp = strcasecmp($a['value'], $b['value']);

	if ($a_has_term_in_title xor $b_has_term_in_title) {
		if ($a_has_term_in_title) {
			return -1;
		}
		else {
			return 1;
		}
	}
	else {
		if ($a['value']=='home') {
			return -1;
		}
		else if ($b['value']=='home') {
			return 1;
		}

		return $cmp;
	}
}