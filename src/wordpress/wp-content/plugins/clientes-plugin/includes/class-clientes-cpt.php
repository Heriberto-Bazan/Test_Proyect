<?php
/**
 * Clase para manejar el Custom Post Type de Clientes
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class Clientes_CPT {

    /**
     * Constructor
     */
    public function __construct() {
        // Los hooks se registran en init()
    }

    /**
     * Inicializar hooks
     */
    public function init() {
        // CAMBIO: Llamar directamente en lugar de hook
        $this->register_post_type();

        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_filter('manage_clientes_posts_columns', array($this, 'add_admin_columns'));
        add_action('manage_clientes_posts_custom_column', array($this, 'display_admin_columns'), 10, 2);
        add_filter('manage_edit-clientes_sortable_columns', array($this, 'sortable_columns'));
    }

    /**
     * Registrar Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Clientes', 'Post type general name', 'clientes-plugin'),
            'singular_name'         => _x('Cliente', 'Post type singular name', 'clientes-plugin'),
            'menu_name'             => _x('Clientes', 'Admin Menu text', 'clientes-plugin'),
            'name_admin_bar'        => _x('Cliente', 'Add New on Toolbar', 'clientes-plugin'),
            'add_new'               => __('Añadir Nuevo', 'clientes-plugin'),
            'add_new_item'          => __('Añadir Nuevo Cliente', 'clientes-plugin'),
            'new_item'              => __('Nuevo Cliente', 'clientes-plugin'),
            'edit_item'             => __('Editar Cliente', 'clientes-plugin'),
            'view_item'             => __('Ver Cliente', 'clientes-plugin'),
            'all_items'             => __('Todos los Clientes', 'clientes-plugin'),
            'search_items'          => __('Buscar Clientes', 'clientes-plugin'),
            'parent_item_colon'     => __('Cliente Padre:', 'clientes-plugin'),
            'not_found'             => __('No se encontraron clientes.', 'clientes-plugin'),
            'not_found_in_trash'    => __('No se encontraron clientes en la papelera.', 'clientes-plugin'),
            'featured_image'        => _x('Imagen del Cliente', 'Overrides the "Featured Image" phrase', 'clientes-plugin'),
            'set_featured_image'    => _x('Establecer imagen del cliente', 'Overrides the "Set featured image" phrase', 'clientes-plugin'),
            'remove_featured_image' => _x('Eliminar imagen del cliente', 'Overrides the "Remove featured image" phrase', 'clientes-plugin'),
            'use_featured_image'    => _x('Usar como imagen del cliente', 'Overrides the "Use as featured image" phrase', 'clientes-plugin'),
            'archives'              => _x('Archivo de Clientes', 'The post type archive label used in nav menus', 'clientes-plugin'),
            'insert_into_item'      => _x('Insertar en cliente', 'Overrides the "Insert into post" phrase', 'clientes-plugin'),
            'uploaded_to_this_item' => _x('Subido a este cliente', 'Overrides the "Uploaded to this post" phrase', 'clientes-plugin'),
            'filter_items_list'     => _x('Filtrar lista de clientes', 'Screen reader text for the filter links', 'clientes-plugin'),
            'items_list_navigation' => _x('Navegación de lista de clientes', 'Screen reader text for the pagination', 'clientes-plugin'),
            'items_list'            => _x('Lista de clientes', 'Screen reader text for the items list', 'clientes-plugin'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_nav_menus'  => true,
            'show_in_admin_bar'  => true,
            'show_in_rest'       => true, // Habilitar para Gutenberg y API REST
            'query_var'          => true,
            'rewrite'            => array('slug' => 'clientes'),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-businessperson',
            'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'taxonomies'         => array(), // Agregar taxonomías si es necesario
            'rest_base'          => 'clientes',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        );

        register_post_type('clientes', $args);

        // DEBUG TEMPORAL - agregar estas líneas
        error_log('=== DEBUG CLIENTES CPT ===');
        error_log('Custom Post Type registrado: ' . (post_type_exists('clientes') ? 'SÍ' : 'NO'));
        error_log('=========================');
    }

    /**
     * Agregar meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'clientes_info',
            __('Información del Cliente', 'clientes-plugin'),
            array($this, 'render_info_meta_box'),
            'clientes',
            'normal',
            'high'
        );

        add_meta_box(
            'clientes_origen',
            __('Origen del Cliente', 'clientes-plugin'),
            array($this, 'render_origen_meta_box'),
            'clientes',
            'side',
            'default'
        );
    }

    /**
     * Renderizar meta box de información
     */
    public function render_info_meta_box($post) {
        // Nonce para seguridad
        wp_nonce_field('clientes_meta_box', 'clientes_meta_box_nonce');

        // Obtener valores existentes
        $email = get_post_meta($post->ID, '_cliente_email', true);
        $telefono = get_post_meta($post->ID, '_cliente_telefono', true);
        $empresa = get_post_meta($post->ID, '_cliente_empresa', true);
        $direccion = get_post_meta($post->ID, '_cliente_direccion', true);

        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="cliente_email"><?php _e('Email', 'clientes-plugin'); ?></label>
                </th>
                <td>
                    <input type="email" id="cliente_email" name="cliente_email"
                           value="<?php echo esc_attr($email); ?>" class="regular-text" />
                    <p class="description"><?php _e('Dirección de correo electrónico del cliente', 'clientes-plugin'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cliente_telefono"><?php _e('Teléfono', 'clientes-plugin'); ?></label>
                </th>
                <td>
                    <input type="tel" id="cliente_telefono" name="cliente_telefono"
                           value="<?php echo esc_attr($telefono); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cliente_empresa"><?php _e('Empresa', 'clientes-plugin'); ?></label>
                </th>
                <td>
                    <input type="text" id="cliente_empresa" name="cliente_empresa"
                           value="<?php echo esc_attr($empresa); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="cliente_direccion"><?php _e('Dirección', 'clientes-plugin'); ?></label>
                </th>
                <td>
                    <textarea id="cliente_direccion" name="cliente_direccion"
                              rows="3" class="large-text"><?php echo esc_textarea($direccion); ?></textarea>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Renderizar meta box de origen
     */
    public function render_origen_meta_box($post) {
        // Obtener origen actual - con verificación de clase
        $origen_actual = '';
        if (class_exists('Clientes_DB')) {
            $db_handler = new Clientes_DB();
            $origen_actual = $db_handler->get_cliente_origen($post->ID);
        }

        $origenes = array(
            'web' => __('Web', 'clientes-plugin'),
            'feria' => __('Feria', 'clientes-plugin'),
            'referido' => __('Referido', 'clientes-plugin')
        );

        ?>
        <p>
            <label for="cliente_origen_select">
                <strong><?php _e('Seleccionar origen:', 'clientes-plugin'); ?></strong>
            </label>
        </p>
        <select id="cliente_origen_select" name="cliente_origen" style="width: 100%;">
            <?php foreach ($origenes as $valor => $etiqueta): ?>
                <option value="<?php echo esc_attr($valor); ?>"
                    <?php selected($origen_actual, $valor); ?>>
                    <?php echo esc_html($etiqueta); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php _e('El origen determina cómo llegó este cliente a la empresa.', 'clientes-plugin'); ?>
        </p>

        <?php if ($origen_actual): ?>
            <p><strong><?php _e('Origen actual:', 'clientes-plugin'); ?></strong>
                <span class="origen-badge origen-<?php echo esc_attr($origen_actual); ?>">
               <?php echo esc_html(isset($origenes[$origen_actual]) ? $origenes[$origen_actual] : $origen_actual); ?>
           </span>
            </p>
        <?php endif; ?>

        <style>
            .origen-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .origen-web { background: #46b450; color: white; }
            .origen-feria { background: #ffb900; color: white; }
            .origen-referido { background: #826eb4; color: white; }
        </style>
        <?php
    }

    /**
     * Guardar meta boxes
     */
    public function save_meta_boxes($post_id) {
        // Verificaciones de seguridad
        if (!isset($_POST['clientes_meta_box_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['clientes_meta_box_nonce'], 'clientes_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Solo procesar para el CPT clientes
        if (get_post_type($post_id) !== 'clientes') {
            return;
        }

        // Guardar campos de información básica
        $campos_info = array('cliente_email', 'cliente_telefono', 'cliente_empresa', 'cliente_direccion');

        foreach ($campos_info as $campo) {
            if (isset($_POST[$campo])) {
                $valor = sanitize_text_field($_POST[$campo]);
                update_post_meta($post_id, '_' . $campo, $valor);
            }
        }

        // Guardar origen en tabla personalizada - con verificación de clase
        if (isset($_POST['cliente_origen']) && class_exists('Clientes_DB')) {
            $origen = sanitize_text_field($_POST['cliente_origen']);
            $db_handler = new Clientes_DB();
            $db_handler->save_cliente_origen($post_id, $origen);
        }
    }

    /**
     * Agregar columnas personalizadas en el admin
     */
    public function add_admin_columns($columns) {
        // Insertar columnas después del título
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['cliente_email'] = __('Email', 'clientes-plugin');
                $new_columns['cliente_origen'] = __('Origen', 'clientes-plugin');
                $new_columns['cliente_empresa'] = __('Empresa', 'clientes-plugin');
            }
        }
        return $new_columns;
    }

    /**
     * Mostrar contenido de columnas personalizadas
     */
    public function display_admin_columns($column, $post_id) {
        switch ($column) {
            case 'cliente_email':
                $email = get_post_meta($post_id, '_cliente_email', true);
                if ($email) {
                    echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
                } else {
                    echo '—';
                }
                break;

            case 'cliente_origen':
                if (class_exists('Clientes_DB')) {
                    $db_handler = new Clientes_DB();
                    $origen = $db_handler->get_cliente_origen($post_id);
                    if ($origen) {
                        $origenes_labels = array(
                            'web' => __('Web', 'clientes-plugin'),
                            'feria' => __('Feria', 'clientes-plugin'),
                            'referido' => __('Referido', 'clientes-plugin')
                        );
                        $label = isset($origenes_labels[$origen]) ? $origenes_labels[$origen] : $origen;
                        echo '<span class="origen-badge origen-' . esc_attr($origen) . '">' . esc_html($label) . '</span>';
                    } else {
                        echo '—';
                    }
                } else {
                    echo '—';
                }
                break;

            case 'cliente_empresa':
                $empresa = get_post_meta($post_id, '_cliente_empresa', true);
                echo $empresa ? esc_html($empresa) : '—';
                break;
        }
    }

    /**
     * Hacer columnas ordenables
     */
    public function sortable_columns($columns) {
        $columns['cliente_email'] = 'cliente_email';
        $columns['cliente_empresa'] = 'cliente_empresa';
        $columns['cliente_origen'] = 'cliente_origen';
        return $columns;
    }

    /**
     * Obtener todos los clientes
     */
    public function get_clientes($args = array()) {
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
     * Obtener clientes para select
     */
    public function get_clientes_for_select() {
        $clientes = array();
        $query = $this->get_clientes(array('posts_per_page' => -1));

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                $origen = '';
                if (class_exists('Clientes_DB')) {
                    $db_handler = new Clientes_DB();
                    $origen = $db_handler->get_cliente_origen($post_id);
                }

                $clientes[] = array(
                    'value' => $post_id,
                    'label' => get_the_title(),
                    'email' => get_post_meta($post_id, '_cliente_email', true),
                    'origen' => $origen
                );
            }
            wp_reset_postdata();
        }

        return $clientes;
    }

    /**
     * Verificar si existe el cliente
     */
    public function cliente_exists($post_id) {
        $post = get_post($post_id);
        return $post && $post->post_type === 'clientes' && $post->post_status === 'publish';
    }
}