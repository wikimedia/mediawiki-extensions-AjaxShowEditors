<?php

global $wgAjaxShowEditorsMessages;
$wgAjaxShowEditorsMessages = array();

$wgAjaxShowEditorsMessages['en'] = array(
	'ajax-se-title' => 'Currently editing:',
	'ajax-se-pending' => 'pending refresh ... (click this box or start editing)',
	'ajax-se-idling' => '($1s ago)',
);
$wgAjaxShowEditorsMessages['fi'] = array(
	'ajax-se-title'   => 'Samanaikaiset muokkaajat:',
	'ajax-se-pending' => 'odotetaan päivitystä… (napsauta tästä tai aloita muokkaaminen)',
	'ajax-se-idling'  => '($1 s sitten)',
);
$wgAjaxShowEditorsMessages['id'] = array(
	'ajax-se-title'   => 'Sedang menyunting:',
	'ajax-se-pending' => 'pemuatan ulang ditunda ... (klik kotak ini atau mulai menyunting)',
	'ajax-se-idling'  => '($1d lalu)',
);
?>
