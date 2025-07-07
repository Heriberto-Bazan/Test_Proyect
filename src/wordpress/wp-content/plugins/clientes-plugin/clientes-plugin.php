<?php
/**
 * Plugin Name: Clientes Plugin
 * Plugin URI: https://ejemplo.com/clientes-plugin
 * Description: Plugin personalizado para gestión de clientes con Custom Post Type, tabla personalizada y bloque Gutenberg.
 * Version: 1.0.0
 * Author: Desarrollador
 * License: GPL v2 or later
 * Text Domain: clientes-plugin
 * Domain Path: /languages
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('CLIENTES_PLUGIN_VERSION', '1.0.0');
define('CLIENTES_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CLIENTES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CLIENTES_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Cargar dependencias inmediatamente
 */
function clientes_plugin_load_dependencies() {
    // Cargar clases principales
    require_once CLIENTES_PLUGIN_PATH . 'includes/class-clientes-db.php';
    require_once CLIENTES_PLUGIN_PATH . 'includes/class-clientes-cpt.php';
    require_once CLIENTES_PLUGIN_PATH . 'includes/class-clientes-api.php';

    // Verificar si Gutenberg está activo
    if (function_exists('register_block_type')) {
        require_once CLIENTES_PLUGIN_PATH . 'includes/class-clientes-blocks.php';
    }
}

// Cargar dependencias ANTES de cualquier hook
clientes_plugin_load_dependencies();

/**
 * Clase principal del plugin
 */
class ClientesPlugin {

    /**
     * Instancia única del plugin
     */
    private static $instance = null;

    /**
     * Obtener instancia única
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Inicializar el plugin
     */
    private function init() {
        // Hooks de activación y desactivación
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Hook de inicialización
        add_action('init', array($this, 'init_plugin'));

        // Hook para cargar scripts y estilos del admin
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        // Hook para cargar scripts del frontend
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
    }

    /**
     * Activación del plugin
     */
    public function activate() {
        // Crear tabla personalizada
        if (class_exists('Clientes_DB')) {
            $db_handler = new Clientes_DB();
            $db_handler->create_table();
        }

        // Registrar CPT y flush rewrite rules
        if (class_exists('Clientes_CPT')) {
            $cpt_handler = new Clientes_CPT();
            $cpt_handler->register_post_type();
        }

        flush_rewrite_rules();

        // Crear página de opciones por defecto
        $this->create_default_options();

        // Log de activación
        error_log('Clientes Plugin activado correctamente');
    }

    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Log de desactivación
        error_log('Clientes Plugin desactivado');
    }

    /**
     * Inicializar componentes del plugin
     */
    public function init_plugin() {
        // DEBUG - agregar al principio
        error_log('=== INIT PLUGIN CLIENTES ===');

        // Inicializar Custom Post Type
        if (class_exists('Clientes_CPT')) {
            $cpt_handler = new Clientes_CPT();
            $cpt_handler->init();
            error_log('CPT Clientes inicializado');
        }

        // Inicializar manejador de base de datos
        if (class_exists('Clientes_DB')) {
            $db_handler = new Clientes_DB();
            $db_handler->init();
            error_log('DB Clientes inicializado');
        }

        // Inicializar API REST
        if (class_exists('Clientes_API')) {
            $api_handler = new Clientes_API();
            $api_handler->init();
            error_log('API Clientes inicializado');
        }

        // Inicializar bloques Gutenberg
        if (function_exists('register_block_type') && class_exists('Clientes_Blocks')) {
            $blocks_handler = new Clientes_Blocks();
            $blocks_handler->init();
            error_log('Blocks Clientes inicializado');
        }

        // Cargar textdomain para traducciones
        load_plugin_textdomain(
            'clientes-plugin',
            false,
            dirname(CLIENTES_PLUGIN_BASENAME) . '/languages'
        );

        // DEBUG - agregar al final
        error_log('=== FIN INIT PLUGIN ===');
    }

    /**
     * Cargar scripts y estilos del admin
     */
    public function admin_enqueue_scripts($hook) {
        // Solo cargar en páginas relevantes
        global $post_type;

        if ('clientes' === $post_type || 'edit.php' === $hook) {
            wp_enqueue_style(
                'clientes-admin-style',
                CLIENTES_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                CLIENTES_PLUGIN_VERSION
            );

            wp_enqueue_script(
                'clientes-admin-script',
                CLIENTES_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                CLIENTES_PLUGIN_VERSION,
                true
            );

            // Pasar datos al script
            wp_localize_script('clientes-admin-script', 'clientesAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('clientes_nonce'),
                'strings' => array(
                    'save_success' => __('Cliente guardado correctamente', 'clientes-plugin'),
                    'save_error' => __('Error al guardar cliente', 'clientes-plugin'),
                )
            ));
        }
    }

    /**
     * Cargar scripts del frontend
     */
    public function frontend_enqueue_scripts() {
        wp_enqueue_style(
            'clientes-frontend-style',
            CLIENTES_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            CLIENTES_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'clientes-frontend-script',
            CLIENTES_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            CLIENTES_PLUGIN_VERSION,
            true
        );
    }

    /**
     * Crear opciones por defecto
     */
    private function create_default_options() {
        $default_options = array(
            'clientes_options' => array(
                'enable_api' => true,
                'default_origen' => 'web',
                'show_in_gutenberg' => true,
                'items_per_page' => 10
            )
        );

        foreach ($default_options as $option_name => $option_value) {
            if (!get_option($option_name)) {
                add_option($option_name, $option_value);
            }
        }
    }

    /**
     * Obtener configuración del plugin
     */
    public static function get_option($key, $default = null) {
        $options = get_option('clientes_options', array());
        return isset($options[$key]) ? $options[$key] : $default;
    }

    /**
     * Actualizar configuración del plugin
     */
    public static function update_option($key, $value) {
        $options = get_option('clientes_options', array());
        $options[$key] = $value;
        update_option('clientes_options', $options);
    }
}

/**
 * Función helper para obtener la instancia del plugin
 */
function clientes_plugin() {
    return ClientesPlugin::get_instance();
}

/**
 * Inicializar el plugin
 */
clientes_plugin();

/**
 * Funciones helper para usar en temas
 */

/**
 * Obtener clientes
 */
function get_clientes($args = array()) {
    $defaults = array(
        'post_type' => 'clientes',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    );

    $args = wp_parse_args($args, $defaults);
    return new WP_Query($args);
}

/**
 * Obtener origen del cliente
 */
function get_cliente_origen($post_id) {
    if (class_exists('Clientes_DB')) {
        $db_handler = new Clientes_DB();
        return $db_handler->get_cliente_origen($post_id);
    }
    return '';
}

/**
 * Mostrar información del cliente
 */
function display_cliente_info($post_id, $show_origen = true) {
    $post = get_post($post_id);
    if (!$post || 'clientes' !== $post->post_type) {
        return false;
    }

    $nombre = get_the_title($post_id);
    $email = get_post_meta($post_id, '_cliente_email', true);
    $origen = $show_origen ? get_cliente_origen($post_id) : '';

    $output = '<div class="cliente-info">';
    $output .= '<h3>' . esc_html($nombre) . '</h3>';
    if ($email) {
        $output .= '<p><strong>Email:</strong> ' . esc_html($email) . '</p>';
    }
    if ($origen) {
        $output .= '<p><strong>Origen:</strong> ' . esc_html($origen) . '</p>';
    }
    $output .= '</div>';

    return $output;
}