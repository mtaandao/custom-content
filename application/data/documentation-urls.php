<?php

// Google Analytics
// ?utm_source=typesplugin&utm_campaign=types&utm_medium=%CURRENT-SCREEN%&utm_term=EMPTY&utm_content=EMPTY

$urls = array(
	'learn-how-template'               => 'https://mn-types.com/documentation/user-guides/benefits-of-templates-for-custom-types/',
	'learn-how-archive'                => 'https://mn-types.com/documentation/user-guides/what-archives-are-and-why-they-are-so-important/',
	'learn-how-views'                  => 'https://mn-types.com/documentation/user-guides/learn-what-you-can-do-with-views/',
	'learn-how-forms'                  => 'https://mn-types.com/home/cred/',
	'learn-how-post-types'             => 'https://mn-types.com/documentation/user-guides/create-a-custom-post-type/',
	'learn-how-fields'                 => 'https://mn-types.com/documentation/user-guides/using-custom-fields/',
	'learn-how-taxonomies'             => 'https://mn-types.com/documentation/user-guides/create-custom-taxonomies/',
	'creating-templates-with-toolset'  => 'https://mn-types.com/documentation/user-guides/learn-about-creating-templates-with-toolset/',
	'creating-templates-with-php'      => 'https://mn-types.com/documentation/user-guides/creating-templates-for-single-custom-posts-in-php/',
	'creating-archives-with-toolset'   => 'https://mn-types.com/documentation/user-guides/learn-about-creating-archives-with-toolset/',
	'creating-archives-with-php'       => 'https://mn-types.com/documentation/user-guides/creating-templates-custom-post-type-archives-php/',
	'how-views-work'                   => 'https://mn-types.com/documentation/user-guides/learn-what-you-can-do-with-views/',
	'how-to-add-views-to-layouts'      => 'https://mn-types.com/documentation/user-guides/views/',
	'learn-views'                      => 'https://mn-types.com/documentation/user-guides/learn-what-you-can-do-with-views/',
	'how-cred-work'                    => 'https://mn-types.com/documentation/user-guides/learn-what-you-can-do-with-cred/',
	'how-to-add-forms-to-layouts'      => 'https://mn-types.com/documentation/user-guides/creating-cred-forms/',
	'learn-cred'                       => 'https://mn-types.com/documentation/user-guides/learn-what-you-can-do-with-cred/',
	'free-trial'                       => 'https://mn-types.com/?add-to-cart=363363&buy_now=1',
	'adding-custom-fields-with-php'    => 'https://mn-types.com/documentation/user-guides/displaying-mtaandao-custom-fields/#1',
	'themes-compatible-with-layouts'   => 'https://mn-types.com/documentation/user-guides/layouts-theme-integration/#popular-integrated-themes',
	'layouts-integration-instructions' => 'https://mn-types.com/documentation/user-guides/layouts-theme-integration/#replacing-mn-loop-with-layouts',
	'adding-views-to-layouts'          => 'https://mn-types.com/documentation/user-guides/adding-views-to-layouts/',
	'adding-forms-to-layouts'          => 'https://mn-types.com/documentation/user-guides/adding-cred-forms-to-layouts/',
	'using-post-fields'                => 'https://mn-types.com/user-guides/using-custom-fields/',
	'adding-fields'                    => 'https://mn-types.com/documentation/user-guides/using-custom-fields/#introduction-to-mtaandao-custom-fields',
	'displaying-fields'                => 'https://mn-types.com/documentation/user-guides/displaying-mtaandao-custom-fields/',
	'adding-user-fields'               => 'https://mn-types.com/documentation/user-guides/user-fields/',
	'displaying-user-fields'           => 'https://mn-types.com/documentation/user-guides/displaying-mtaandao-user-fields/',
	'adding-term-fields'               => 'https://mn-types.com/documentation/user-guides/term-fields/',
	'displaying-term-fields'           => 'https://mn-types.com/documentation/user-guides/displaying-mtaandao-term-fields/',
	'custom-post-types'                => 'https://mn-types.com/documentation/user-guides/create-a-custom-post-type/',
	'custom-taxonomy'                  => 'https://mn-types.com/documentation/user-guides/create-custom-taxonomies/',
	'post-relationship'                => 'https://mn-types.com/documentation/user-guides/creating-post-type-relationships/',
	'compare-toolset-php'              => 'https://mn-types.com/landing/toolset-vs-php/',
	'types-fields-api'                 => 'https://mn-types.com/documentation/functions/',
	'parent-child'                     => 'https://mn-types.com/documentation/user-guides/many-to-many-post-relationship/',
	'custom-post-archives'             => 'https://mn-types.com/documentation/user-guides/creating-mtaandao-custom-post-archives/',
	'using-taxonomy'                   => 'https://mn-types.com/documentation/user-guides/create-custom-taxonomies/',
	'custom-taxonomy-archives'         => 'https://mn-types.com/documentation/user-guides/creating-mtaandao-custom-taxonomy-archives/',
	'repeating-fields-group'           => 'https://mn-types.com/documentation/user-guides/creating-groups-of-repeating-fields-using-fields-tables/',
	'single-pages'                     => 'https://mn-types.com/documentation/user-guides/view-templates/',
	'content-templates'                => 'https://mn-types.com/documentation/user-guides/view-templates/',
	'views-user-guide'                 => 'https://mn-types.com/documentation/user-guides/views/',
	'mn-types'                         => 'https://mn-types.com/',
	'date-filters'                     => 'http://mn-types.com/documentation/user-guides/date-filters/',
	'getting-started-types'            => 'https://mn-types.com/documentation/user-guides/getting-starting-with-types/',
);

// Visual Composer
if( defined( 'MNB_VC_VERSION' ) ) {
	$urls['learn-how-template']         = 'https://mn-types.com/documentation/user-guides/benefits-of-templates-for-custom-types-vc/';
	$urls['creating-templates-with-toolset'] = 'https://mn-types.com/documentation/user-guides/benefits-of-templates-for-custom-types-vc/';
}
// Beaver Builder
else if( class_exists( 'FLBuilderLoader' ) ) {
	$urls['learn-how-template']         = 'https://mn-types.com/documentation/user-guides/benefits-of-templates-for-custom-types-bb/';
	$urls['creating-templates-with-toolset'] = 'https://mn-types.com/documentation/user-guides/benefits-of-templates-for-custom-types-bb/';
}
// Layouts
else if( defined( 'MNDDL_DEVELOPMENT' ) || defined( 'MNDDL_PRODUCTION' ) ) {
	$urls['learn-how-template']         = 'https://mn-types.com/documentation/user-guides/benefits-of-templates-for-custom-types-layouts/';
	$urls['creating-templates-with-toolset'] = 'https://mn-types.com/documentation/user-guides/benefits-of-templates-for-custom-types-layouts/';
}

return $urls;