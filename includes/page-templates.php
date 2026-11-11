<?php
if ( ! defined( "ABSPATH" ) ) exit;

class BatchMate_Page_Templates {
    private $templates;

    public function __construct() {
        $this->templates = [
            "template-registration.php" => "BatchMate Registration Form",
            "template-members.php"      => "BatchMate Members List",
            "template-application-print.php" => "BatchMate Application Print",
            "template-ec-print.php"     => "BatchMate EC Print",
            "template-member-print.php" => "BatchMate Member Print",
            "template-print-payment.php" => "BatchMate Payment Receipt Print",
            "template-success.php"      => "BatchMate Registration Success",
        ];

        add_filter( "theme_page_templates", [ $this, "register_templates" ] );
        add_filter( "template_include", [ $this, "load_template" ], 99 );
    }

    public function register_templates( $post_templates ) {
        return array_merge( $post_templates, $this->templates );
    }

    public function load_template( $template ) {
        global $post;
        if ( ! $post ) {
            return $template;
        }

        $page_template_slug = get_post_meta( $post->ID, "_wp_page_template", true );

        if ( isset( $this->templates[ $page_template_slug ] ) ) {
            $plugin_template = ROYAL_BATCHMATE_PLUGIN_DIR . "templates/" . $page_template_slug;
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }

        return $template;
    }
}
new BatchMate_Page_Templates();

