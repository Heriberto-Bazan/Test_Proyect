<?php
/**
 * Clase para manejar la base de datos personalizada de clientes
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class Clientes_DB {

    /**
     * Nombre de la tabla
     */
    private $table_name;

    /**
     * Instancia de wpdb
     */
    private $wpdb;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'clientes_extra';
    }

    /**
     * Inicializar hooks
     */
    public function init() {
        // Hook para crear tabla si no existe
        add_action('plugins_loaded', array($this, 'maybe_create_table'));
    }

    /**
     * Crear tabla si no existe
     */
    public function maybe_create_table() {
        if (!$this->table_exists()) {
            $this->create_table();
        }
    }

    /**
     * Verificar si la tabla existe
     */
    public function table_exists() {
        $query = $this->wpdb->prepare("SHOW TABLES LIKE %s", $this->table_name);
        return $this->wpdb->get_var($query) === $this->table_name;
    }

    /**
     * Crear tabla personalizada
     */
    public function create_table() {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            post_id int(11) NOT NULL,
            origen_cliente varchar(50) NOT NULL DEFAULT 'web',
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY origen_cliente (origen_cliente)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);

        // Log del resultado
        if ($result) {
            error_log('Tabla wp_clientes_extra creada correctamente');
        } else {
            error_log('Error al crear tabla wp_clientes_extra: ' . $this->wpdb->last_error);
        }

        return $result;
    }

    /**
     * Guardar o actualizar origen del cliente
     */
    public function save_cliente_origen($post_id, $origen_cliente) {
        // Validar parámetros
        if (!$post_id || !$origen_cliente) {
            return false;
        }

        // Sanitizar origen
        $origen_cliente = sanitize_text_field($origen_cliente);
        $origenes_validos = array('web', 'feria', 'referido');

        if (!in_array($origen_cliente, $origenes_validos)) {
            error_log("Origen no válido: $origen_cliente");
            return false;
        }

        // Verificar si ya existe un registro
        $existing = $this->get_cliente_data($post_id);

        if ($existing) {
            // Actualizar registro existente
            $result = $this->wpdb->update(
                $this->table_name,
                array(
                    'origen_cliente' => $origen_cliente,
                    'updated_at' => current_time('mysql')
                ),
                array('post_id' => $post_id),
                array('%s', '%s'),
                array('%d')
            );
        } else {
            // Insertar nuevo registro
            $result = $this->wpdb->insert(
                $this->table_name,
                array(
                    'post_id' => $post_id,
                    'origen_cliente' => $origen_cliente,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s')
            );
        }

        if ($result === false) {
            error_log('Error al guardar origen del cliente: ' . $this->wpdb->last_error);
            return false;
        }

        return true;
    }

    /**
     * Obtener origen del cliente
     */
    public function get_cliente_origen($post_id) {
        if (!$post_id) {
            return '';
        }

        $query = $this->wpdb->prepare(
            "SELECT origen_cliente FROM {$this->table_name} WHERE post_id = %d",
            $post_id
        );

        $result = $this->wpdb->get_var($query);

        return $result ? $result : '';
    }

    /**
     * Obtener todos los datos del cliente de la tabla personalizada
     */
    public function get_cliente_data($post_id) {
        if (!$post_id) {
            return null;
        }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE post_id = %d",
            $post_id
        );

        return $this->wpdb->get_row($query, ARRAY_A);
    }

    /**
     * Obtener clientes por origen
     */
    public function get_clientes_by_origen($origen_cliente, $limit = -1) {
        $query = $this->wpdb->prepare(
            "SELECT post_id FROM {$this->table_name} WHERE origen_cliente = %s",
            $origen_cliente
        );

        if ($limit > 0) {
            $query .= $this->wpdb->prepare(" LIMIT %d", $limit);
        }

        return $this->wpdb->get_col($query);
    }

    /**
     * Obtener estadísticas de orígenes
     */
    public function get_origen_stats() {
        $query = "SELECT origen_cliente, COUNT(*) as total 
                 FROM {$this->table_name} 
                 GROUP BY origen_cliente 
                 ORDER BY total DESC";

        $results = $this->wpdb->get_results($query, ARRAY_A);

        $stats = array();
        foreach ($results as $row) {
            $stats[$row['origen_cliente']] = (int) $row['total'];
        }

        return $stats;
    }

    /**
     * Eliminar registro de cliente
     */
    public function delete_cliente_data($post_id) {
        if (!$post_id) {
            return false;
        }

        $result = $this->wpdb->delete(
            $this->table_name,
            array('post_id' => $post_id),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Obtener todos los clientes con datos de la tabla personalizada
     */
    public function get_all_clientes_with_extra_data($args = array()) {
        $defaults = array(
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
            'origen' => ''
        );

        $args = wp_parse_args($args, $defaults);

        // Query base
        $query_args = array(
            'post_type' => 'clientes',
            'posts_per_page' => $args['posts_per_page'],
            'post_status' => $args['post_status'],
            'orderby' => $args['orderby'],
            'order' => $args['order']
        );

        // Filtrar por origen si se especifica
        if (!empty($args['origen'])) {
            $post_ids = $this->get_clientes_by_origen($args['origen']);
            if (empty($post_ids)) {
                return array(); // No hay clientes con ese origen
            }
            $query_args['post__in'] = $post_ids;
        }

        $query = new WP_Query($query_args);
        $clientes = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                // Datos básicos del post
                $cliente = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'content' => get_the_content(),
                    'excerpt' => get_the_excerpt(),
                    'date' => get_the_date('c'),
                    'modified' => get_the_modified_date('c'),
                    'status' => get_post_status(),
                    'link' => get_permalink()
                );

                // Meta fields básicos
                $cliente['email'] = get_post_meta($post_id, '_cliente_email', true);
                $cliente['telefono'] = get_post_meta($post_id, '_cliente_telefono', true);
                $cliente['empresa'] = get_post_meta($post_id, '_cliente_empresa', true);
                $cliente['direccion'] = get_post_meta($post_id, '_cliente_direccion', true);

                // Datos de la tabla personalizada
                $extra_data = $this->get_cliente_data($post_id);
                if ($extra_data) {
                    $cliente['origen_cliente'] = $extra_data['origen_cliente'];
                    $cliente['created_at'] = $extra_data['created_at'];
                    $cliente['updated_at'] = $extra_data['updated_at'];
                } else {
                    $cliente['origen_cliente'] = 'web'; // Default
                    $cliente['created_at'] = '';
                    $cliente['updated_at'] = '';
                }

                // Imagen destacada
                if (has_post_thumbnail($post_id)) {
                    $cliente['featured_image'] = array(
                        'id' => get_post_thumbnail_id($post_id),
                        'url' => get_the_post_thumbnail_url($post_id, 'full'),
                        'thumbnail' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
                        'medium' => get_the_post_thumbnail_url($post_id, 'medium')
                    );
                }

                $clientes[] = $cliente;
            }
            wp_reset_postdata();
        }

        return $clientes;
    }

    /**
     * Limpiar tabla (para testing/desarrollo)
     */
    public function truncate_table() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return $this->wpdb->query("TRUNCATE TABLE {$this->table_name}");
        }
        return false;
    }

    /**
     * Obtener información de la tabla
     */
    public function get_table_info() {
        $query = "SELECT 
                    COUNT(*) as total_records,
                    MIN(created_at) as oldest_record,
                    MAX(updated_at) as newest_record
                 FROM {$this->table_name}";

        return $this->wpdb->get_row($query, ARRAY_A);
    }
}