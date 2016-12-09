<?php

return array(
	'control' => array(
		'#pattern' => '<tr><td><TITLE></td><td><ERROR><BEFORE><ELEMENT><LABEL><AFTER></td></tr>',
		'#title' => __( 'Validation', 'mncf' ),
		'#attributes' => array(
			'class' => 'js-mncf-validation-checkbox',
		),
	),
	'message' => array(
		'#label' => __( 'Validation error message', 'mncf' ),
		'#attributes' => array(
			'class' => 'widefat'
		)
	)
);