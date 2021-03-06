<?php
/**
 * Plugin Name: Fat Rat Collect
 * Plugin URI: http://www.fatrat.cn
 * Description: 胖鼠采集(Fat Rat Collect) 是一款可以帮助你采集列表页面的免费开源采集小工具。支持自动采集。自动发布文章。图片本地化。如果你会一点Html JQuery知识。那更好了。完美支持你自定义任何采集规则。
 * Version: 1.11.2
 * Author: Fat Rat
 * Author URI: http://www.fatrat.cn/about
 * Disclaimer: Use at your own risk. No warranty expressed or implied is provided.
 * Text Domain: fat-rat-collect
 * License: GPL3
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

global $frc_db_version;
$frc_db_version = '2.0.0';

/**
 * Fire up Composer's autoloader
 */
require_once(__DIR__ . '/vendor/autoload.php');

/**
 * Install
 */
function frc_plugin_install(){
    global $wpdb;
    global $frc_db_version;

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $table_post      = $wpdb->prefix . 'frc_post';
    $table_options   = $wpdb->prefix . 'frc_options';
    $charset_collate = $wpdb->get_charset_collate();

    $sql =
        "CREATE TABLE IF NOT EXISTS $table_options(
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `collect_name` varchar(30) NOT NULL DEFAULT '',
          `collect_describe` varchar(200) NOT NULL DEFAULT '',
          `collect_type` varchar(20) NOT NULL DEFAULT '',
          `collect_list_url` varchar(191) NOT NULL DEFAULT '',
          `collect_list_url_paging` varchar(191) NOT NULL DEFAULT '',
          `collect_list_range` varchar(191) NOT NULL DEFAULT '',
          `collect_list_rules` varchar(191) NOT NULL DEFAULT '',
          `collect_content_range` varchar(191) NOT NULL DEFAULT '',
          `collect_content_rules` varchar(191) NOT NULL DEFAULT '',
          `collect_charset` varchar(20) NOT NULL DEFAULT 'utf-8',
          `collect_image_download` tinyint(10) NOT NULL DEFAULT '1',
          `collect_image_path` tinyint(2) NOT NULL DEFAULT '1',
          `collect_image_attribute` varchar(20) NOT NULL DEFAULT 'src',
          `collect_rendering` tinyint(2) NOT NULL DEFAULT '1',
          `collect_remove_head` tinyint(2) NOT NULL DEFAULT '1',
          `collect_auto_collect` tinyint(2) NOT NULL DEFAULT '2',
          `collect_auto_release` tinyint(2) NOT NULL DEFAULT '2',
          `collect_release` varchar(191) NOT NULL DEFAULT '{}',
          `collect_keywords_replace_rule` mediumtext NOT NULL,
          `collect_custom_content` mediumtext NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        )	$charset_collate; ";
    dbDelta( $sql );

    $sql =
        "CREATE TABLE IF NOT EXISTS $table_post(
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `option_id` int(11) NOT NULL,
            `status` tinyint(5) NOT NULL DEFAULT '1',
            `title` varchar(120) NOT NULL DEFAULT '',
            `cover` varchar(255) NOT NULL DEFAULT '',
            `content` mediumtext NOT NULL,
            `link` varchar(255) NOT NULL DEFAULT '',
            `post_id` int(11) NOT NULL DEFAULT '0',
            `message` varchar(255) NOT NULL DEFAULT '',          
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `option_id` (`option_id`),
            KEY `status` (`status`),
            KEY `link` (`link`)
        )	$charset_collate; ";
    dbDelta( $sql );

    add_option( 'frc_db_version', $frc_db_version );
    add_option( 'frc_install_time', time() );
}
register_activation_hook( __FILE__, 'frc_plugin_install' );

/**
 * Update
 */
function frc_plugin_update() {
    global $frc_db_version;
    global $wpdb;
    $table_post      = $wpdb->prefix . 'frc_post';
    $table_options   = $wpdb->prefix . 'frc_options';


    if ( get_option( 'frc_db_version' ) != $frc_db_version ) {

        // 修正数据
        if ($frc_db_version == '2.0.0'){
            if (get_option('frc_install_time')){
                add_option('frc_mysql_upgrade', '1');
            } else {
                add_option('frc_mysql_upgrade', 'upgrade complete');
            }

            $config = json_encode(['switch' => 'shutdown', 'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql')]);
            delete_option('frc_cron_publish_article');
            delete_option('frc_cron_spider');

            if (get_option(FRC_Validation::FRC_VALIDATION_FEATURED_PICTURE)){
                update_option(FRC_Validation::FRC_VALIDATION_FEATURED_PICTURE, $config);
            }
            if (get_option(FRC_Validation::FRC_VALIDATION_DYNAMIC_FIELDS)){
                update_option(FRC_Validation::FRC_VALIDATION_DYNAMIC_FIELDS, $config);
                update_option(FRC_Validation::FRC_VALIDATION_INNER_CHAIN, $config);
            }
            if (get_option(FRC_Validation::FRC_VALIDATION_AUTO_TAGS)){
                update_option(FRC_Validation::FRC_VALIDATION_AUTO_TAGS, $config);
            }


        }

        $wpdb->show_errors();

//        //Check for Exclude Image Path
//        $column_name = 'collect_image_path';
//        $checkcolumn = $wpdb->get_results($wpdb->prepare(
//            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
//            DB_NAME, $table_options, $column_name
//        )) ;
//        if ( empty( $checkcolumn ) ) {
//            $altersql = "ALTER TABLE `$table_options` ADD `{$column_name}` tinyint(2) NOT NULL DEFAULT 1 AFTER `collect_content_rules`";
//            $wpdb->query($altersql);
//        }
//        //Check for Exclude Custom Content
//        $column_name = 'collect_custom_content';
//        $checkcolumn = $wpdb->get_results($wpdb->prepare(
//            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
//            DB_NAME, $table_options, $column_name
//        )) ;
//        if ( empty( $checkcolumn ) ) {
//            $altersql = "ALTER TABLE `$table_options` ADD `{$column_name}` text NOT NULL  AFTER `collect_content_rules`";
//            $wpdb->query($altersql);
//        }
//        //Check for Exclude post_id
//        $column_name = 'post_id';
//        $checkcolumn = $wpdb->get_results($wpdb->prepare(
//            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
//            DB_NAME, $table_post, $column_name
//        )) ;
//        if ( empty( $checkcolumn ) ) {
//            $altersql = "ALTER TABLE `$table_post` ADD `{$column_name}` int(11) NOT NULL default 0 AFTER `link`";
//            $wpdb->query($altersql);
//        }
//        //Check for Exclude collect_img_attribute
//        $column_name = 'collect_image_download';
//        $checkcolumn = $wpdb->get_results($wpdb->prepare(
//            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
//            DB_NAME, $table_options, $column_name
//        )) ;
//        if ( empty( $checkcolumn ) ) {
//            $altersql = "ALTER TABLE `$table_options` ADD `{$column_name}` varchar(20) NOT NULL default '1' AFTER `collect_content_rules`";
//            $wpdb->query($altersql);
//        }
//        //Check for Exclude collect_img_attribute
//        $column_name = 'collect_image_attribute';
//        $checkcolumn = $wpdb->get_results($wpdb->prepare(
//            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
//            DB_NAME, $table_options, $column_name
//        )) ;
//        if ( empty( $checkcolumn ) ) {
//            $altersql = "ALTER TABLE `$table_options` ADD `{$column_name}` varchar(20) NOT NULL default 'src' AFTER `collect_content_rules`";
//            $wpdb->query($altersql);
//        }
//        //Check for Exclude collect_list_url_paging
//        $column_name = 'collect_list_url_paging';
//        $checkcolumn = $wpdb->get_results($wpdb->prepare(
//            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
//            DB_NAME, $table_options, $column_name
//        )) ;
//        if ( empty( $checkcolumn ) ) {
//            $altersql = "ALTER TABLE `$table_options` ADD `{$column_name}` varchar(255) NOT NULL default '' AFTER `collect_list_url`";
//            $wpdb->query($altersql);
//        }
//        //Check for Exclude collect_rendering
//        $column_name = 'collect_rendering';
//        $checkcolumn = $wpdb->get_results($wpdb->prepare(
//            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
//            DB_NAME, $table_options, $column_name
//        )) ;
//        if ( empty( $checkcolumn ) ) {
//            $altersql = "ALTER TABLE `$table_options` ADD `{$column_name}` tinyint(2) NOT NULL default '1' AFTER `collect_image_download`";
//            $wpdb->query($altersql);
//        }
//        //Check for Exclude status
//        $column_name = 'status';
//        $checkcolumn = $wpdb->get_results($wpdb->prepare(
//            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
//            DB_NAME, $table_post, $column_name
//        )) ;
//        if ( empty( $checkcolumn ) ) {
//            $altersql = "ALTER TABLE `$table_post` ADD `{$column_name}` tinyint(2) NOT NULL default '1' AFTER `id`";
//            $wpdb->query($altersql);
//        }
//        //Check for Exclude collect_remove_head
//        $column_name = 'collect_remove_head';
//        $checkcolumn = $wpdb->get_results($wpdb->prepare(
//            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
//            DB_NAME, $table_options, $column_name
//        )) ;
//        if ( !empty( $checkcolumn ) ) {
//            $altersql = "ALTER TABLE `$table_options` ALTER COLUMN `{$column_name}` SET default '1'";
//            $wpdb->query($altersql);
//        }
//
//        //Check for Exclude status
//        $column_name = 'pic_attachment';
//        $checkcolumn = $wpdb->get_results($wpdb->prepare(
//            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
//            DB_NAME, $table_post, $column_name
//        )) ;
//        if ( empty( $checkcolumn ) ) {
//            $altersql = "ALTER TABLE `$table_post` DROP `{$column_name}`";
//            $wpdb->query($altersql);
//        }

        frc_plugin_install();
    }

    update_option('frc_db_version', $frc_db_version);
}
add_action( 'plugins_loaded', 'frc_plugin_update' );

/**
 * Uninstall
 */
function frc_plugin_uninstall() {
    global $wpdb;

    $table_post     = $wpdb->prefix . 'frc_post';
    $table_options  = $wpdb->prefix . 'frc_options';

    $wpdb->query( "DROP TABLE IF EXISTS $table_options" );
    $wpdb->query( "DROP TABLE IF EXISTS $table_post" );

}
register_uninstall_hook(__FILE__, 'frc_plugin_uninstall');

/**
 * Style && Script
 */
function frc_loading_assets( $hook ) {
    global $frc_db_version;
    $allowed_pages = array(
        'frc-collect',
        'frc-spider',
        'frc-options',
        'frc-data',
        'frc-options-add-edit',
        'frc-kit',
        'frc-data-detail',
        'frc-debugging'
    );

    if (in_array(strstr($hook,"frc-"), $allowed_pages)) {
        // css
        wp_register_style('fat-rat-bootstrap-css', plugins_url('css/bootstrap.min.css', __FILE__));
        wp_enqueue_style('fat-rat-bootstrap-css');
        wp_register_style('fat-rat-css', plugins_url('css/fatrat.css', __FILE__));
        wp_enqueue_style('fat-rat-css');

        // js
        wp_register_script('fat-rat-bootstrap-js', plugins_url('js/bootstrap.min.js', __FILE__));
        wp_enqueue_script('fat-rat-bootstrap-js');
        wp_register_script('fat-rat-js', plugins_url('js/fatrat.js?a=222', __FILE__), array('jquery'), $frc_db_version, true);
        wp_enqueue_script('fat-rat-js');
    }
}
add_action( 'admin_enqueue_scripts', 'frc_loading_assets' );

/**
 * Menu
 */
function frc_loading_menu()
{
    add_menu_page(
        __('胖鼠采集', 'Fat Rat Collect'),
        __('胖鼠采集', 'Fat Rat Collect'),
        'publish_posts',
        'frc-collect',
        'frc_spider',
        plugins_url('images/', __FILE__) . 'fat-rat.png'
    );

    add_submenu_page(
        'frc-collect',
        __('采集中心', 'Fat Rat Collect'),
        __('采集中心', 'Fat Rat Collect'),
        'publish_posts',
        'frc-spider',
        'frc_spider'
    );

    add_submenu_page(
        'frc-collect',
        __('配置中心', 'Fat Rat Collect'),
        __('配置中心', 'Fat Rat Collect'),
        'publish_posts',
        'frc-options',
        'frc_options'
    );

    add_submenu_page(
        'frc-collect',
        __('数据桶中心', 'Fat Rat Collect'),
        __('数据桶中心', 'Fat Rat Collect'),
        'publish_posts',
        'frc-data',
        'frc_data_list'
    );

    add_submenu_page(
        'frc-collect',
        __('添加/修改(配置)', 'Fat Rat Collect'),
        __('添加/修改(配置)', 'Fat Rat Collect'),
        'publish_posts',
        'frc-options-add-edit',
        'frc_options_add_edit'
    );

    add_submenu_page(
        'frc-collect',
        __('Debugging', 'Fat Rat Collect'),
        __('Debugging', 'Fat Rat Collect'),
        'publish_posts',
        'frc-debugging',
        'frc_debugging'
    );

    add_submenu_page(
        '',
        __('数据列表', 'Fat Rat Collect'),
        __('数据列表', 'Fat Rat Collect'),
        'publish_posts',
        'frc-data-detail',
        'frc_data_detail'
    );


    add_menu_page(
        __('胖鼠工具箱', 'Fat Rat Collect'),
        __('胖鼠工具箱', 'Fat Rat Collect'),
        'publish_posts',
        'frc-kit',
        'frc_kit',
        plugins_url('images/', __FILE__) . 'fat-rat-kit.png'
    );

    remove_submenu_page('frc-collect', 'frc-collect');
//    remove_submenu_page('frc-collect', 'frc-data-detail');
}
add_action('admin_menu', 'frc_loading_menu');


/**
 * Require ...
 */
require_once( plugin_dir_path( __FILE__ ) . 'includes/fatrat-apierror.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/fatrat-spider.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/fatrat-options.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/fatrat-options-add-edit.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/fatrat-data.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/fatrat-data-detail.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/fatrat-validation.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/fatrat-kit.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/fatrat-debugging.php' );


add_action( 'wp_ajax_frc_interface', function (){
    if(version_compare(PHP_VERSION,'7.1.0', '<')){
        wp_send_json(['code' => 5003, 'msg' => '鼠友你好, 胖鼠采集目前要求php版本 > 7.1, 检测到你当前PHP版本为'.phpversion().'. 建议升级php版本, 或者请去胖鼠采集的Github下载使用胖鼠v5.6版本 分支名: based_php_5.6!']);
        wp_die();
    }
    $interface_type = !empty($_REQUEST['interface_type']) ? sanitize_text_field($_REQUEST['interface_type']) : null;
    if (empty($interface_type)){
        wp_send_json(['code' => 5004, 'msg' => 'interface type not found error!']);
        wp_die();
    }

    $action_func = !empty($_REQUEST['action_func']) ? sanitize_text_field($_REQUEST['action_func']) : '';
    if (empty($action_func)){
        wp_send_json(['code' => 5001, 'msg' => 'Parameter error!']);
        wp_die();
    }

    $result = null;
    if ($interface_type == '1'){
        $action_func = 'grab_'.$action_func;
        $model = new FRC_Spider();
    } elseif($interface_type == '2'){
        $action_func = 'interface_'.$action_func;
        $model = new FRC_Options();
    } elseif($interface_type == '3'){
        $action_func = 'data_'.$action_func;
        $model = new FRC_Data();
    } elseif($interface_type == '4'){
        $action_func = 'validation_'.$action_func;
        $model = new FRC_Validation();
    } else {
        $model = null;
    }

    method_exists($model, $action_func) && $result = $model->$action_func();
    if ($result != null){
        wp_send_json($result);
        wp_die();
    }
    wp_send_json(['code' => 5002, 'result' => $result, 'msg' => 'Action there is no func! or Func is error!']);
    wp_die();
});


/**
 * add cron operating time
 * @return array
 */
function frc_more_schedules() {
    return array(
        'twohourly' => array('interval' => 7200, 'display' => '每隔两小时'), // 两小时
        'fourhourly' => array('interval' => 14400, 'display' => '每隔四小时'), // 四小时
        'eighthourly' => array('interval' => 28800, 'display' => '每隔八小时'), // 八小时
//        'debug' => array('interval' => 60, 'display' => '每分钟'), // 每分钟
    );
}
add_filter('cron_schedules', 'frc_more_schedules');

function frc_spider_timing_task()
{
    $frc_spider = new FRC_Spider();
    $frc_options = new FRC_Options();
    foreach ($frc_options->options() as $option){
        $frc_spider->timing_spider($option);
    }
}

if ($frc_cron_spider = get_option('frc_cron_spider')){
    if (!wp_next_scheduled('frc_cron_spider_hook')) {
        wp_schedule_event(time(), $frc_cron_spider, 'frc_cron_spider_hook');
    }
    add_action('frc_cron_spider_hook', 'frc_spider_timing_task');
} else {
    wp_clear_scheduled_hook('frc_cron_spider_hook');
}

if ($frc_cron_release = get_option('frc_cron_release')){
    if (!wp_next_scheduled('frc_cron_release_hook')) {
        wp_schedule_event(time(), $frc_cron_release, 'frc_cron_release_hook');
    }

    add_action('frc_cron_release_hook', 'frc_cron_release_task');
    function frc_cron_release_task()
    {
        $model = new FRC_Options();
        $modelData = new FRC_Data();

        $result = [];
        foreach ($model->options() as $option){
            $data = $modelData->getDataByOption($option['id']);
            foreach ($data as $article){
                $result[] = $modelData->article_to_storage($article);
            }
        }

        return $result;
    }
} else {
    wp_clear_scheduled_hook('frc_cron_release_hook');
}

// Function to sanitize $_REQUEST data
function frc_sanitize_text( $key, $default = '', $sanitize = true ) {

    if ( isset($_REQUEST[ $key ]) && ! empty( $_REQUEST[ $key ] ) ) {
        $out = stripslashes_deep( $_REQUEST[ $key ] );
        if ( $sanitize ) {
            $out = sanitize_text_field( $out );
        }
        return $out;
    }

    return $default;
}

// Function to sanitize strings within $_REQUEST data arrays
function frc_sanitize_array( $key, $type = 'integer' ) {
    if ( isset($_REQUEST[ $key ]) && ! empty( $_REQUEST[ $key ] ) ) {

        $arr = $_REQUEST[ $key ];

        if ( ! is_array( $arr ) ) {
            return [];
        }

        if ( 'integer' === $type ) {
            return array_map( 'absint', $arr );
        } else { // strings
            $new_array = array();
            foreach ( $arr as $val ) {
                $new_array[] = sanitize_text_field( $val );
            }
        }

        return $new_array;
    }

    return [];
}