<?php
/*
Plugin Name: Relatos de Experiência
Plugin URI: https://github.com/bireme/relatos-wp-plugin
Description: Search experience report records.
Author: BIREME/OPAS/OMS
Author URI: http://reddes.bvsalud.org/
Version: 1.0
*/

define('RELATOS_PLUGIN_VERSION', '1.4' );

define('RELATOS_SYMBOLIC_LINK', false );
define('RELATOS_PLUGIN_DIRNAME', 'relatos' );
define('RELATOS_PLUGIN_BASENAME', plugin_basename( __FILE__ ));

if(RELATOS_SYMBOLIC_LINK == true) {
    define('RELATOS_PLUGIN_PATH',  ABSPATH . 'wp-content/plugins/' . RELATOS_PLUGIN_DIRNAME );
} else {
    define('RELATOS_PLUGIN_PATH',  plugin_dir_path(__FILE__) );
}

define('RELATOS_PLUGIN_DIR', plugin_basename( RELATOS_PLUGIN_PATH ) );
define('RELATOS_PLUGIN_URL', plugin_dir_url(__FILE__) );

require_once(RELATOS_PLUGIN_PATH . '/settings.php');
require_once(RELATOS_PLUGIN_PATH . '/template-functions.php');
require_once(RELATOS_PLUGIN_PATH . '/widgets.php');

if(!class_exists('Relatos_Plugin')) {
    class Relatos_Plugin {

        private $plugin_slug = 'relatos';
        private $service_url = 'https://experiencias.bvsalud.org';
        private $similar_docs_url = 'http://similardocs.bireme.org/SDService';
        private $solr_service_url = 'http://plugins-idx.bvsalud.org:8983';

	/**
         * Construct the plugin object
         */
        public function __construct() {
            // register actions

            add_action('init', array(&$this, 'load_translation'));
            add_action('admin_init', array(&$this, 'register_settings'));
            add_action('admin_menu', array(&$this, 'admin_menu'));
            add_action('admin_enqueue_scripts', array(&$this, 'admin_styles_scripts'));
            add_action('plugins_loaded', array(&$this, 'plugin_init'));
            add_action('wp_head', array(&$this, 'google_analytics_code'));
            add_action('template_redirect', array(&$this, 'template_redirect'));
            add_action('widgets_init', array(&$this, 'register_sidebars'));
            add_action('after_setup_theme', array(&$this, 'title_tag_setup'));
            add_filter('get_search_form', array(&$this, 'search_form'));
            add_filter('document_title_separator', array(&$this, 'title_tag_sep'));
            add_filter('document_title_parts', array(&$this, 'theme_slug_render_title'));
            add_filter('wp_title', array(&$this, 'theme_slug_render_wp_title'));
            add_filter('plugin_action_links_'.RELATOS_PLUGIN_BASENAME, array(&$this, 'settings_link'));

        } // END public function __construct

        /**
         * Activate the plugin
         */
        public static function activate()
        {
            // Do nothing
        } // END public static function activate

        /**
         * Deactivate the plugin
         */
        public static function deactivate()
        {
            // Do nothing
        } // END public static function deactivate


        function load_translation(){
            // Translations
            load_plugin_textdomain( 'relatos', false,  RELATOS_PLUGIN_DIR . '/languages' );
        }

        function plugin_init() {
            global $relatos_texts;

            $relatos_config = get_option('relatos_config');
            $relatos_config['use_translation'] = true;

            if ($relatos_config && $relatos_config['plugin_slug'] != ''){
                $this->plugin_slug = $relatos_config['plugin_slug'];
            }
            if ($relatos_config['use_translation']){
                $site_language = strtolower(get_bloginfo('language'));
                $lang = substr($site_language,0,2);

                $relatos_texts = @parse_ini_file(RELATOS_PLUGIN_PATH . "/languages/texts_".$lang.".ini", true);
                if ( !$relatos_texts ) {
                    $relatos_texts = @parse_ini_file(RELATOS_PLUGIN_PATH . "/languages/texts_".$lang."-SAMPLE.ini", true);
                    if ( !$relatos_texts ) {
                        $relatos_texts = @parse_ini_file(RELATOS_PLUGIN_PATH . "/languages/texts_en-SAMPLE.ini", true);
                    }
                }
            }
        }

        function admin_menu() {
            add_options_page(
                __('Experience Reports settings', 'relatos'),
                __('Experience Reports', 'relatos'),
                'manage_options',
                'relatos-settings',
                'relatos_page_admin'
            );
        }

        function settings_link( $links ) {
    		// Build and escape the URL.
    		$url = esc_url( add_query_arg(
    			'page',
    			'relatos-settings',
    			get_admin_url() . 'admin.php'
    		) );

    		// Create the link.
    		$settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';

    		// Adds the link to the end of the array.
    		array_push(
    			$links,
    			$settings_link
    		);

    		return $links;
    	}

        function template_redirect() {
            global $wp, $relatos_service_url, $relatos_plugin_slug, $similar_docs_url, $solr_service_url;
            $pagename = '';

            // check if request contains plugin slug string
            $pos_slug = strpos($wp->request, $this->plugin_slug);
            if ( $pos_slug !== false ){
                $pagename = substr($wp->request, $pos_slug);
            }

            if ( is_404() && $pos_slug !== false ){
                $relatos_service_url = $this->service_url;
                $relatos_plugin_slug = $this->plugin_slug;
                $similar_docs_url = $this->similar_docs_url;
                $solr_service_url = $this->solr_service_url;

                if ($pagename == $this->plugin_slug ||
                    $pagename == $this->plugin_slug . '/resource' ||
                    $pagename == $this->plugin_slug . '/relatos-feed') {

                    add_action( 'wp_enqueue_scripts', array(&$this, 'page_template_styles_scripts'), 999);
                    add_filter( 'pll_the_languages', array(&$this, 'relatos_language_switcher'), 10, 2 );

                    if ($pagename == $this->plugin_slug) {
                        $template = RELATOS_PLUGIN_PATH . '/template/home.php';
                    } elseif ($pagename == $this->plugin_slug . '/relatos-feed') {
                        header("Content-Type: text/xml; charset=UTF-8");
                        $template = RELATOS_PLUGIN_PATH . '/template/rss.php';
                    } else {
                        $template = RELATOS_PLUGIN_PATH . '/template/resource.php';
                    }

                    // force status to 200 - OK
                    status_header(200);

                    // redirect to page and finish execution
                    include($template);
                    die();
                }
            }
        }

        function register_sidebars(){
            $args = array(
                'name' => __('Experience Reports sidebar', 'relatos'),
                'id'   => 'relatos-home',
                'before_widget' => '<section id="%1$s" class="row-fluid marginbottom25 widget_categories">',
                'after_widget'  => '</section>',
                'before_title'  => '<header class="row-fluid border-bottom marginbottom15"><h1 class="h1-header">',
                'after_title'   => '</h1></header>',
            );
            register_sidebar( $args );

            $args2 = array(
                'name' => __('Experience Reports header', 'relatos'),
                'id'   => 'relatos-header',
                'before_widget' => '<section id="%1$s" class="row-fluid widget %2$s">',
                'after_widget'  => '</section>',
                'before_title'  => '<header class="row-fluid border-bottom marginbottom15"><h1 class="h1-header">',
                'after_title'   => '</h1></header>',
            );
            register_sidebar( $args2 );

        }

        function title_tag_sep(){
            return '|';
        }

        function theme_slug_render_title($title) {
            global $wp, $relatos_plugin_title;
            $pagename = '';

            // check if request contains plugin slug string
            $pos_slug = strpos($wp->request, $this->plugin_slug);
            if ( $pos_slug !== false ){
                $pagename = substr($wp->request, $pos_slug);
            }

            if ( is_404() && $pos_slug !== false ){
                $relatos_config = get_option('relatos_config');
                if ( function_exists( 'pll_the_languages' ) ) {
                    $current_lang = pll_current_language();
                    $relatos_plugin_title = $relatos_config['plugin_title_' . $current_lang];
                }else{
                    $relatos_plugin_title = $relatos_config['plugin_title'];
                }
                $title['title'] = $relatos_plugin_title;
            }

            return $title;
        }

        function theme_slug_render_wp_title($title) {
            global $wp, $relatos_plugin_title;
            $pagename = '';
            $sep = ' | ';

            // check if request contains plugin slug string
            $pos_slug = strpos($wp->request, $this->plugin_slug);
            if ( $pos_slug !== false ){
                $pagename = substr($wp->request, $pos_slug);
            }

            if ( is_404() && $pos_slug !== false ){
                $relatos_config = get_option('relatos_config');

                if ( function_exists( 'pll_the_languages' ) ) {
                    $current_lang = pll_current_language();
                    $relatos_plugin_title = $relatos_config['plugin_title_' . $current_lang];
                } else {
                    $relatos_plugin_title = $relatos_config['plugin_title'];
                }

                if ( $relatos_plugin_title )
                    $title = $relatos_plugin_title . ' | ';
                else
                    $title = '';
            }

            return $title;
        }

        function title_tag_setup() {
            add_theme_support( 'title-tag' );
        }

        function search_form( $form ) {
            global $wp;
            $pagename = $wp->query_vars["pagename"];

            if ($pagename == $this->plugin_slug || $pagename == $this->plugin_slug .'/resource') {
                $form = preg_replace('/action="([^"]*)"(.*)/','action="' . home_url($this->plugin_slug) . '"',$form);
            }

            return $form;
        }

        function page_template_styles_scripts(){
            wp_enqueue_script('jquery');
            wp_enqueue_script('bootstrap-popper', '//cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js', array(), '1.14.7', true);
            wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js', array(), '4.3.1', true);
            wp_enqueue_script('relatos-tooltipster', RELATOS_PLUGIN_URL . 'template/js/jquery.tooltipster.min.js');
            wp_enqueue_script('slick-js', '//cdn.jsdelivr.net/gh/kenwheeler/slick@1.8.1/slick/slick.min.js');
            wp_enqueue_script('relatos', RELATOS_PLUGIN_URL . 'template/js/functions.js', array(), RELATOS_PLUGIN_VERSION);
            wp_enqueue_script('fontawesome-js', '//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/js/all.min.js');
            wp_enqueue_script('lightbox-js', '//cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js');

            // wp_enqueue_style('fontawesome', RELATOS_PLUGIN_URL . 'template/css/font-awesome/css/font-awesome.min.css');
            wp_enqueue_style('fontawesome-css', '//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css');
            wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css');
            wp_enqueue_style('relatos-tooltipster', RELATOS_PLUGIN_URL . 'template/css/tooltipster.css');
            wp_enqueue_style('slick-css', '//cdn.jsdelivr.net/gh/kenwheeler/slick@1.8.1/slick/slick.css');
            wp_enqueue_style('slick-theme-css', '//cdn.jsdelivr.net/gh/kenwheeler/slick@1.8.1/slick/slick-theme.css');
            wp_enqueue_style('lightbox-css', '//cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css');
            wp_enqueue_style('relatos-styles',  RELATOS_PLUGIN_URL . 'template/css/style.css', array(), RELATOS_PLUGIN_VERSION);
        }

        function admin_styles_scripts(){
            wp_enqueue_script('lightbox-js', '//cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js');
            wp_enqueue_style('lightbox-css', '//cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css');
        }

        function register_settings(){
            register_setting('relatos-settings-group', 'relatos_config', array(&$this, 'relatos_plugin_settings'));
            wp_enqueue_style('relatos',  RELATOS_PLUGIN_URL . 'template/css/admin.css');
            wp_enqueue_script('jquery-ui-sortable');
        }

        function relatos_plugin_settings( $config ){
            $relatos_config = get_option('relatos_config');
            $custom_banner = $_FILES['custom_banner'];

            if ( $custom_banner['name'] ) {
                $override = array(
                    'test_form' => false,
                );

                $filename = basename($relatos_config['custom_banner']);
                if ( $filename == $custom_banner['name']) {
                    $upload_dir = wp_upload_dir();
                    $filepath = $upload_dir['path'].'/'.$filename;
                    unlink($filepath);
                }

                $uploaded_file = wp_handle_upload( $custom_banner, $override );
                if ( !is_wp_error( $uploaded_file ) ) {
                    $config['custom_banner'] = $uploaded_file['url'];
                }
            }

            $config = array_merge($relatos_config, $config);
            return $config;
        }

        function google_analytics_code(){
            global $wp;

            $pagename = $wp->query_vars["name"];
            $relatos_config = get_option('relatos_config');

            // check if is defined GA code and pagename starts with plugin slug
            if ($relatos_config['google_analytics_code'] != '' && strpos($pagename, $this->plugin_slug) === 0) {

                $google_analytics_code = explode(PHP_EOL, $relatos_config['google_analytics_code']);

                foreach ($google_analytics_code as $ga_code) {
        ?>

        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $ga_code; ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo $ga_code; ?>');
        </script>

        <?php
                }
            } //endif
        }

        function relatos_language_switcher( $output, $args ) {
            if ( defined( 'POLYLANG_VERSION' ) ) {
                $current_language = strtolower(get_bloginfo('language'));
                $site_lang = substr($current_language, 0,2);
                $default_language = pll_default_language();
                $translations = pll_the_languages(array('raw'=>1));

                $output = "<ul>\n";
                foreach ($translations as $key => $value) :
                    if ($site_lang == $key) continue;
                    $search = ($site_lang != $default_language) ? $site_lang.'/'.$this->plugin_slug : $this->plugin_slug;
                    $replace = ($key != $default_language) ? $key.'/'.$this->plugin_slug : $this->plugin_slug;
                    $url = str_replace($search, $replace, $_SERVER['REQUEST_URI']);
                    $output .= "<li class=\"" . $value['classes'][2] . "\"><a href=\"" . $url . "\"><img src=\"" . $value['flag']. "\" title=\"" . $value['name'] . "\" alt=\"" . $value['name'] . "\" /> " . $value['name'] . "</a></li>\n";
                endforeach;
                $output .= "</ul>";
            }

            return $output;
        }

    } // END class Relatos_Plugin
} // END if(!class_exists('Relatos_Plugin'))

if(class_exists('Relatos_Plugin'))
{
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('Relatos_Plugin', 'activate'));
    register_deactivation_hook(__FILE__, array('Relatos_Plugin', 'deactivate'));

    // instantiate the plugin class
    $wp_plugin_template = new Relatos_Plugin();
}

?>
