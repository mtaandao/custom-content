<?php
/**
 * Types Clone Mtaandao Administration Template Header
 *
 * This comment applies to admin-footer.php too.
 * Remove all unnecessary output. Leave basic MN hooks only.
 * We do this because we need various scripts enqueued right.
 *
 * @todo After leaving Thickbox this should be reviewed
 * @package Types Mtaandao clones
 * @subpackage Administration
 */
@header( 'Content-Type: ' . get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ) );
if ( !defined( 'ADMIN' ) )
    require_once( './admin.php' );

// In case admin-header.php is included in a function.
global $title, $hook_suffix, $current_screen, $mn_locale, $pagenow, $mn_version,
 $current_site, $update_title, $total_update_count, $parent_file;

// Catch plugins that include admin-header.php before admin.php completes.
if ( empty( $current_screen ) )
    set_current_screen();

get_admin_page_title();
$title = esc_html( strip_tags( $title ) );

if ( is_network_admin() )
    $admin_title = __( 'Network Admin', 'mncf' );
elseif ( is_user_admin() )
    $admin_title = __( 'Global Dashboard', 'mncf' );
else
    $admin_title = get_bloginfo( 'name' );

if ( $admin_title == $title )
    $admin_title = sprintf( __( '%1$s &#8212; Mtaandao', 'mncf' ), $title );
else
    $admin_title = sprintf( __( '%1$s &lsaquo; %2$s &#8212; Mtaandao', 'mncf' ),
            $title, $admin_title );

$admin_title = apply_filters( 'admin_title', $admin_title, $title );

mn_user_settings();

_admin_html_begin();

?>
<title><?php echo $admin_title; ?></title>
<?php
mn_enqueue_style( 'colors' );
mn_enqueue_style( 'ie' );
mn_enqueue_script( 'utils' );

$admin_body_class = preg_replace( '/[^a-z0-9_-]+/i', '-', $hook_suffix );

?>
<script type="text/javascript">
    addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof mnOnload!='function'){mnOnload=func;}else{var oldonload=mnOnload;mnOnload=function(){oldonload();func();}}};
    var ajaxurl = '<?php echo admin_url( 'admin-ajax.php',
        'relative' ); ?>',
    pagenow = '<?php echo $current_screen->id; ?>',
    typenow = '<?php echo $current_screen->post_type; ?>',
    adminpage = '<?php echo $admin_body_class; ?>',
    thousandsSeparator = '<?php echo addslashes( $mn_locale->number_format['thousands_sep'] ); ?>',
    decimalPoint = '<?php echo addslashes( $mn_locale->number_format['decimal_point'] ); ?>',
    isRtl = <?php echo (int) is_rtl(); ?>;
</script>
<?php
do_action( 'admin_enqueue_scripts', $hook_suffix );
do_action( "admin_print_styles-$hook_suffix" );
do_action( 'admin_print_styles' );
do_action( "admin_print_scripts-$hook_suffix" );
do_action( 'admin_print_scripts' );
do_action( "admin_head-$hook_suffix" );
do_action( 'admin_head' );

// Types
do_action( 'admin_head_mncf_ajax' );

if ( get_user_setting( 'mfold' ) == 'f' )
    $admin_body_class .= ' folded';

if ( !get_user_setting( 'unfold' ) )
    $admin_body_class .= ' auto-fold';

if ( is_admin_bar_showing() )
    $admin_body_class .= ' admin-bar';

if ( is_rtl() )
    $admin_body_class .= ' rtl';

$admin_body_class .= ' branch-' . str_replace( array('.', ','), '-',
                floatval( $mn_version ) );
$admin_body_class .= ' version-' . str_replace( '.', '-',
                preg_replace( '/^([.0-9]+).*/', '$1', $mn_version ) );
$admin_body_class .= ' admin-color-' . sanitize_html_class( get_user_option( 'admin_color' ),
                'fresh' );
$admin_body_class .= ' locale-' . sanitize_html_class( strtolower( str_replace( '_',
                                '-', get_locale() ) ) );

if ( mn_is_mobile() )
    $admin_body_class .= ' mobile';

$admin_body_class .= ' no-customize-support';

?>
</head>
<body class="types-ajax admin mn-core-ui no-js <?php echo apply_filters( 'admin_body_class',
        '' ) . " $admin_body_class"; ?>">
    <script type="text/javascript">
        document.body.className = document.body.className.replace('no-js','js');
    </script>

<?php
// If the customize-loader script is enqueued, make sure the customize
// body classes are correct as early as possible.
if ( mn_script_is( 'customize-loader', 'queue' ) && current_user_can( 'edit_theme_options' ) )
    mn_customize_support_script();

?>
<div id="mncf-ajax">
    <div id="mnwrap">
        <!--<a tabindex="1" href="#mnbody-content" class="screen-reader-shortcut"><?php _e( 'Skip to main content', 'mncf' ); ?></a>-->
                <?php //require(ABSPATH . 'admin/menu-header.php'); ?>
        <div id="mncontent">

                <?php
                do_action( 'in_admin_header' );

                ?>

            <div id="mnbody">
                    <?php
                    unset( $title_class, $blog_name, $total_update_count,
                            $update_title );

                    $current_screen->set_parentage( $parent_file );

                    ?>

                <div id="mnbody-content" aria-label="<?php esc_attr_e( 'Main content', 'mncf' ); ?>" tabindex="0">
                    <?php
                    $current_screen->render_screen_meta();

//                    if ( is_network_admin() )
//                        do_action( 'network_admin_notices' );
//                    elseif ( is_user_admin() )
//                        do_action( 'user_admin_notices' );
//                    else
//                        do_action( 'admin_notices' );
//
//                    do_action( 'all_admin_notices' );
//
//                    if ( $parent_file == 'options-general.php' )
//                        require(ABSPATH . 'admin/options-head.php');
