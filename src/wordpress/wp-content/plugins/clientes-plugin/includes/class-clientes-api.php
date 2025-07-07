<?php
/**
 * Clase para manejar la API REST personalizada de clientes
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class Clientes_API {

    /**
     * Namespace de la API
     */
    private $namespace = 'empresa/v1';

    /**
     * Base del endpoint
     */
    private $rest_base = 'clientes';

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
        add_action('rest_api_init', array($this, 'register_routes'));
        add_filter('rest_pre_echo_response', array($this, 'add_cors_headers'), 10, 3);
    }

    /**
     * Registrar rutas de la API
     */
    public function register_routes() {
        // Endpoint principal: GET /wp-json/empresa/v1/clientes
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_clientes'),
                'permission_callback' => array($this, 'get_clientes_permissions_check'),
                'args' => $this->get_collection_params()
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_cliente'),
                'permission_callback' => array($this, 'create_cliente_permissions_check'),
                'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE)
            )
        ));

        // Endpoint individual: GET /wp-json/empresa/v1/clientes/{id}
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_cliente'),
                'permission_callback' => array($this, 'get_cliente_permissions_check'),
                'args' => array(
                    'id' => array(
                        'description' => __('ID único del cliente.', 'clientes-plugin'),
                        'type' => 'integer',
                        'required' => true,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => function($param) {
                            return is_numeric($param) && $param > 0;
                        }
                    )
                )
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_cliente'),
                'permission_callback' => array($this, 'update_cliente_permissions_check'),
                'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE)
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_cliente'),
                'permission_callback' => array($this, 'delete_cliente_permissions_check'),
                'args' => array(
                    'force' => array(
                        'type' => 'boolean',
                        'default' => false,
                        'description' => __('Si se omite la papelera y se fuerza la eliminación.', 'clientes-plugin')
                    )
                )
            )
        ));

        // Endpoint para estadísticas: GET /wp-json/empresa/v1/clientes/stats
        register_rest_route($this->namespace, '/' . $this->rest_base . '/stats', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_clientes_stats'),
            'permission_callback' => array($this, 'get_stats_permissions_check')
        ));

        // Endpoint para orígenes: GET /wp-json/empresa/v1/clientes/origenes
        register_rest_route($this->namespace, '/' . $this->rest_base . '/origenes', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_origenes'),
            'permission_callback' => '__return_true' // Público
        ));
    }

    /**
     * Obtener lista de clientes
     */
    public function get_clientes($request) {
        try {
            $db_handler = new Clientes_DB();

            // Parámetros de la consulta
            $args = array(
                'posts_per_page' => $request->get_param('per_page') ?: 10,
                'post_status' => 'publish',
                'orderby' => $request->get_param('orderby') ?: 'date',
                'order' => $request->get_param('order') ?: 'DESC',
                'origen' => $request->get_param('origen') ?: ''
            );

            // Paginación
            $page = $request->get_param('page') ?: 1;
            if ($page > 1) {
                $args['offset'] = ($page - 1) * $args['posts_per_page'];
            }

            // Búsqueda
            $search = $request->get_param('search');
            if (!empty($search)) {
                $args['s'] = sanitize_text_field($search);
            }

            // Obtener clientes con datos extra
            $clientes = $db_handler->get_all_clientes_with_extra_data($args);

            // Preparar respuesta
            $data = array();
            foreach ($clientes as $cliente) {
                $data[] = $this->prepare_cliente_for_response($cliente, $request);
            }

            // Headers para paginación
            $response = rest_ensure_response($data);

            // Obtener total para headers de paginación
            $total_args = $args;
            $total_args['posts_per_page'] = -1;
            $total_clientes = $db_handler->get_all_clientes_with_extra_data($total_args);
            $total = count($total_clientes);

            $response->header('X-WP-Total', $total);
            $response->header('X-WP-TotalPages', ceil($total / $args['posts_per_page']));

            return $response;

        } catch (Exception $e) {
            return new WP_Error(
                'clientes_api_error',
                __('Error al obtener clientes: ', 'clientes-plugin') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Obtener cliente individual
     */
    public function get_cliente($request) {
        $id = (int) $request['id'];

        // Verificar que el post existe y es del tipo correcto
        $post = get_post($id);
        if (!$post || $post->post_type !== 'clientes' || $post->post_status !== 'publish') {
            return new WP_Error(
                'cliente_not_found',
                __('Cliente no encontrado.', 'clientes-plugin'),
                array('status' => 404)
            );
        }

        try {
            $db_handler = new Clientes_DB();
            $clientes = $db_handler->get_all_clientes_with_extra_data(array(
                'post__in' => array($id),
                'posts_per_page' => 1
            ));

            if (empty($clientes)) {
                return new WP_Error(
                    'cliente_not_found',
                    __('Cliente no encontrado.', 'clientes-plugin'),
                    array('status' => 404)
                );
            }

            $cliente = $clientes[0];
            return $this->prepare_cliente_for_response($cliente, $request);

        } catch (Exception $e) {
            return new WP_Error(
                'clientes_api_error',
                __('Error al obtener cliente: ', 'clientes-plugin') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Crear nuevo cliente
     */
    public function create_cliente($request) {
        try {
            // Datos del cliente
            $title = sanitize_text_field($request->get_param('title'));
            $content = wp_kses_post($request->get_param('content'));
            $email = sanitize_email($request->get_param('email'));
            $origen = sanitize_text_field($request->get_param('origen_cliente'));

            // Validaciones
            if (empty($title)) {
                return new WP_Error(
                    'missing_title',
                    __('El título del cliente es requerido.', 'clientes-plugin'),
                    array('status' => 400)
                );
            }

            if (!empty($email) && !is_email($email)) {
                return new WP_Error(
                    'invalid_email',
                    __('El email no es válido.', 'clientes-plugin'),
                    array('status' => 400)
                );
            }

            // Crear post
            $post_data = array(
                'post_title' => $title,
                'post_content' => $content,
                'post_type' => 'clientes',
                'post_status' => 'publish',
                'post_author' => get_current_user_id()
            );

            $post_id = wp_insert_post($post_data);

            if (is_wp_error($post_id)) {
                return $post_id;
            }

            // Guardar meta fields
            if ($email) {
                update_post_meta($post_id, '_cliente_email', $email);
            }

            $telefono = sanitize_text_field($request->get_param('telefono'));
            if ($telefono) {
                update_post_meta($post_id, '_cliente_telefono', $telefono);
            }

            $empresa = sanitize_text_field($request->get_param('empresa'));
            if ($empresa) {
                update_post_meta($post_id, '_cliente_empresa', $empresa);
            }

            $direccion = sanitize_textarea_field($request->get_param('direccion'));
            if ($direccion) {
                update_post_meta($post_id, '_cliente_direccion', $direccion);
            }

            // Guardar origen en tabla personalizada
            if ($origen) {
                $db_handler = new Clientes_DB();
                $db_handler->save_cliente_origen($post_id, $origen);
            }

            // Obtener cliente creado
            $db_handler = new Clientes_DB();
            $clientes = $db_handler->get_all_clientes_with_extra_data(array(
                'post__in' => array($post_id),
                'posts_per_page' => 1
            ));

            $cliente = $clientes[0];
            $response = $this->prepare_cliente_for_response($cliente, $request);
            $response->set_status(201);

            return $response;

        } catch (Exception $e) {
            return new WP_Error(
                'clientes_api_error',
                __('Error al crear cliente: ', 'clientes-plugin') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Actualizar cliente
     */
    public function update_cliente($request) {
        $id = (int) $request['id'];

        // Verificar que el post existe
        $post = get_post($id);
        if (!$post || $post->post_type !== 'clientes') {
            return new WP_Error(
                'cliente_not_found',
                __('Cliente no encontrado.', 'clientes-plugin'),
                array('status' => 404)
            );
        }

        try {
            // Actualizar post si hay datos
            $post_data = array('ID' => $id);

            $title = $request->get_param('title');
            if (!empty($title)) {
                $post_data['post_title'] = sanitize_text_field($title);
            }

            $content = $request->get_param('content');
            if (!empty($content)) {
                $post_data['post_content'] = wp_kses_post($content);
            }

            if (count($post_data) > 1) {
                wp_update_post($post_data);
            }

            // Actualizar meta fields
            $email = $request->get_param('email');
            if (!is_null($email)) {
                if (!empty($email) && !is_email($email)) {
                    return new WP_Error(
                        'invalid_email',
                        __('El email no es válido.', 'clientes-plugin'),
                        array('status' => 400)
                    );
                }
                update_post_meta($id, '_cliente_email', sanitize_email($email));
            }

            $telefono = $request->get_param('telefono');
            if (!is_null($telefono)) {
                update_post_meta($id, '_cliente_telefono', sanitize_text_field($telefono));
            }

            $empresa = $request->get_param('empresa');
            if (!is_null($empresa)) {
                update_post_meta($id, '_cliente_empresa', sanitize_text_field($empresa));
            }

            $direccion = $request->get_param('direccion');
            if (!is_null($direccion)) {
                update_post_meta($id, '_cliente_direccion', sanitize_textarea_field($direccion));
            }

            // Actualizar origen
            $origen = $request->get_param('origen_cliente');
            if (!is_null($origen)) {
                $db_handler = new Clientes_DB();
                $db_handler->save_cliente_origen($id, sanitize_text_field($origen));
            }

            // Retornar cliente actualizado
            $db_handler = new Clientes_DB();
            $clientes = $db_handler->get_all_clientes_with_extra_data(array(
                'post__in' => array($id),
                'posts_per_page' => 1
            ));

            $cliente = $clientes[0];
            return $this->prepare_cliente_for_response($cliente, $request);

        } catch (Exception $e) {
            return new WP_Error(
                'clientes_api_error',
                __('Error al actualizar cliente: ', 'clientes-plugin') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Eliminar cliente
     */
    public function delete_cliente($request) {
        $id = (int) $request['id'];
        $force = (bool) $request->get_param('force');

        $post = get_post($id);
        if (!$post || $post->post_type !== 'clientes') {
            return new WP_Error(
                'cliente_not_found',
                __('Cliente no encontrado.', 'clientes-plugin'),
                array('status' => 404)
            );
        }

        try {
            // Obtener datos antes de eliminar
            $db_handler = new Clientes_DB();
            $clientes = $db_handler->get_all_clientes_with_extra_data(array(
                'post__in' => array($id),
                'posts_per_page' => 1
            ));

            if (empty($clientes)) {
                return new WP_Error(
                    'cliente_not_found',
                    __('Cliente no encontrado.', 'clientes-plugin'),
                    array('status' => 404)
                );
            }

            $cliente = $clientes[0];
            $previous = $this->prepare_cliente_for_response($cliente, $request);

            // Eliminar de tabla personalizada
            $db_handler->delete_cliente_data($id);

            // Eliminar post
            $result = wp_delete_post($id, $force);

            if (!$result) {
                return new WP_Error(
                    'cant_delete',
                    __('No se pudo eliminar el cliente.', 'clientes-plugin'),
                    array('status' => 500)
                );
            }

            $response = new WP_REST_Response();
            $response->set_data(array(
                'deleted' => true,
                'previous' => $previous->get_data()
            ));

            return $response;

        } catch (Exception $e) {
            return new WP_Error(
                'clientes_api_error',
                __('Error al eliminar cliente: ', 'clientes-plugin') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Obtener estadísticas de clientes
     */
    public function get_clientes_stats($request) {
        try {
            $db_handler = new Clientes_DB();

            // Estadísticas por origen
            $origen_stats = $db_handler->get_origen_stats();

            // Total de clientes
            $total_query = new WP_Query(array(
                'post_type' => 'clientes',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids'
            ));
            $total_clientes = $total_query->found_posts;

            // Información de la tabla
            $table_info = $db_handler->get_table_info();

            $stats = array(
                'total_clientes' => $total_clientes,
                'origen_distribution' => $origen_stats,
                'table_info' => $table_info,
                'origenes_disponibles' => array(
                    'web' => __('Web', 'clientes-plugin'),
                    'feria' => __('Feria', 'clientes-plugin'),
                    'referido' => __('Referido', 'clientes-plugin')
                )
            );

            return rest_ensure_response($stats);

        } catch (Exception $e) {
            return new WP_Error(
                'clientes_api_error',
                __('Error al obtener estadísticas: ', 'clientes-plugin') . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Obtener lista de orígenes disponibles
     */
    public function get_origenes($request) {
        $origenes = array(
            array(
                'value' => 'web',
                'label' => __('Web', 'clientes-plugin'),
                'description' => __('Cliente que llegó a través del sitio web', 'clientes-plugin')
            ),
            array(
                'value' => 'feria',
                'label' => __('Feria', 'clientes-plugin'),
                'description' => __('Cliente contactado en ferias comerciales', 'clientes-plugin')
            ),
            array(
                'value' => 'referido',
                'label' => __('Referido', 'clientes-plugin'),
                'description' => __('Cliente referido por otros clientes', 'clientes-plugin')
            )
        );

        return rest_ensure_response($origenes);
    }

    /**
     * Preparar cliente para respuesta
     */
    private function prepare_cliente_for_response($cliente, $request) {
        // Campos base
        $data = array(
            'id' => (int) $cliente['id'],
            'title' => $cliente['title'],
            'content' => $cliente['content'],
            'excerpt' => $cliente['excerpt'],
            'status' => $cliente['status'],
            'date' => $cliente['date'],
            'modified' => $cliente['modified'],
            'link' => $cliente['link']
        );

        // Meta fields
        $data['email'] = $cliente['email'];
        $data['telefono'] = $cliente['telefono'];
        $data['empresa'] = $cliente['empresa'];
        $data['direccion'] = $cliente['direccion'];

        // Campo de tabla personalizada
        $data['origen_cliente'] = $cliente['origen_cliente'];
        $data['created_at'] = $cliente['created_at'];
        $data['updated_at'] = $cliente['updated_at'];

        // Imagen destacada
        if (isset($cliente['featured_image'])) {
            $data['featured_image'] = $cliente['featured_image'];
        }

        return rest_ensure_response($data);
    }

    /**
     * Obtener parámetros de colección
     */
    public function get_collection_params() {
        return array(
            'page' => array(
                'description' => __('Página actual de la colección.', 'clientes-plugin'),
                'type' => 'integer',
                'default' => 1,
                'sanitize_callback' => 'absint'
            ),
            'per_page' => array(
                'description' => __('Número máximo de elementos a devolver en el conjunto de resultados.', 'clientes-plugin'),
                'type' => 'integer',
                'default' => 10,
                'minimum' => 1,
                'maximum' => 100,
                'sanitize_callback' => 'absint'
            ),
            'search' => array(
                'description' => __('Limitar resultados a aquellos que coincidan con una cadena.', 'clientes-plugin'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'orderby' => array(
                'description' => __('Ordenar colección por atributo del objeto.', 'clientes-plugin'),
                'type' => 'string',
                'default' => 'date',
                'enum' => array('date', 'title', 'modified'),
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'order' => array(
                'description' => __('Ordenar atributo de colección ascendente o descendente.', 'clientes-plugin'),
                'type' => 'string',
                'default' => 'DESC',
                'enum' => array('ASC', 'DESC'),
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'origen' => array(
                'description' => __('Filtrar por origen del cliente.', 'clientes-plugin'),
                'type' => 'string',
                'enum' => array('web', 'feria', 'referido'),
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
    }

    /**
     * Obtener argumentos para esquema de elemento
     */
    public function get_endpoint_args_for_item_schema($method = WP_REST_Server::CREATABLE) {
        $args = array(
            'title' => array(
                'description' => __('Nombre del cliente.', 'clientes-plugin'),
                'type' => 'string',
                'required' => $method === WP_REST_Server::CREATABLE,
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'content' => array(
                'description' => __('Descripción del cliente.', 'clientes-plugin'),
                'type' => 'string',
                'sanitize_callback' => 'wp_kses_post'
            ),
            'email' => array(
                'description' => __('Email del cliente.', 'clientes-plugin'),
                'type' => 'string',
                'format' => 'email',
                'sanitize_callback' => 'sanitize_email'
            ),
            'telefono' => array(
                'description' => __('Teléfono del cliente.', 'clientes-plugin'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'empresa' => array(
                'description' => __('Empresa del cliente.', 'clientes-plugin'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'direccion' => array(
                'description' => __('Dirección del cliente.', 'clientes-plugin'),
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field'
            ),
            'origen_cliente' => array(
                'description' => __('Origen del cliente.', 'clientes-plugin'),
                'type' => 'string',
                'enum' => array('web', 'feria', 'referido'),
                'default' => 'web',
                'sanitize_callback' => 'sanitize_text_field'
            )
        );

        return $args;
    }

    /**
     * Verificar permisos para obtener clientes
     */
    public function get_clientes_permissions_check($request) {
        return true; // API pública para lectura
    }

    /**
     * Verificar permisos para obtener cliente individual
     */
    public function get_cliente_permissions_check($request) {
        return true; // API pública para lectura
    }

    /**
     * Verificar permisos para crear cliente
     */
    public function create_cliente_permissions_check($request) {
        return current_user_can('edit_posts');
    }

    /**
     * Verificar permisos para actualizar cliente
     */
    public function update_cliente_permissions_check($request) {
        return current_user_can('edit_post', $request['id']);
    }

    /**
     * Verificar permisos para eliminar cliente
     */
    public function delete_cliente_permissions_check($request) {
        return current_user_can('delete_post', $request['id']);
    }

    /**
     * Verificar permisos para estadísticas
     */
    public function get_stats_permissions_check($request) {
        return current_user_can('manage_options');
    }

    /**
     * Agregar headers CORS
     */
    public function add_cors_headers($result, $server, $request) {
        $server->send_header('Access-Control-Allow-Origin', '*');
        $server->send_header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $server->send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-WP-Nonce');

        return $result;
    }
}