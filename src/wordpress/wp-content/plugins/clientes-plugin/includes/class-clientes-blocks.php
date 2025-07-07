<?php
/**
 * Clase para manejar los bloques Gutenberg de clientes
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class Clientes_Blocks {

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
        add_action('init', array($this, 'register_blocks'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_action('enqueue_block_assets', array($this, 'enqueue_block_assets'));
        add_action('wp_ajax_get_clientes_for_select', array($this, 'ajax_get_clientes_for_select'));
        add_action('wp_ajax_nopriv_get_clientes_for_select', array($this, 'ajax_get_clientes_for_select'));
    }

    /**
     * Registrar bloques
     */
    public function register_blocks() {
        // Verificar que Gutenberg está disponible
        if (!function_exists('register_block_type')) {
            return;
        }

        // Registrar bloque de cliente destacado
        register_block_type('clientes-plugin/cliente-destacado', array(
            'editor_script' => 'clientes-blocks-editor',
            'editor_style' => 'clientes-blocks-editor-style',
            'style' => 'clientes-blocks-style',
            'render_callback' => array($this, 'render_cliente_destacado_block'),
            'attributes' => array(
                'clienteId' => array(
                    'type' => 'number',
                    'default' => 0
                ),
                'backgroundColor' => array(
                    'type' => 'string',
                    'default' => '#f8f9fa'
                ),
                'textColor' => array(
                    'type' => 'string',
                    'default' => '#333333'
                ),
                'showEmail' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showOrigen' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showEmpresa' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'alignment' => array(
                    'type' => 'string',
                    'default' => 'left'
                ),
                'borderRadius' => array(
                    'type' => 'number',
                    'default' => 8
                ),
                'padding' => array(
                    'type' => 'number',
                    'default' => 20
                )
            )
        ));
    }

    /**
     * Cargar assets del editor de bloques
     */
    public function enqueue_block_editor_assets() {
        // Script del editor
        wp_enqueue_script(
            'clientes-blocks-editor',
            CLIENTES_PLUGIN_URL . 'assets/js/blocks-editor.js',
            array(
                'wp-blocks',
                'wp-element',
                'wp-editor',
                'wp-components',
                'wp-i18n',
                'wp-api-fetch'
            ),
            CLIENTES_PLUGIN_VERSION,
            true
        );

        // Estilos del editor
        wp_enqueue_style(
            'clientes-blocks-editor-style',
            CLIENTES_PLUGIN_URL . 'assets/css/blocks-editor.css',
            array('wp-edit-blocks'),
            CLIENTES_PLUGIN_VERSION
        );

        // Pasar datos al script
        wp_localize_script('clientes-blocks-editor', 'clientesBlocks', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('clientes_blocks_nonce'),
            'apiUrl' => rest_url('empresa/v1/clientes'),
            'strings' => array(
                'title' => __('Cliente Destacado', 'clientes-plugin'),
                'description' => __('Muestra información de un cliente destacado', 'clientes-plugin'),
                'selectClient' => __('Seleccionar Cliente', 'clientes-plugin'),
                'noClients' => __('No hay clientes disponibles', 'clientes-plugin'),
                'backgroundColor' => __('Color de Fondo', 'clientes-plugin'),
                'textColor' => __('Color de Texto', 'clientes-plugin'),
                'showEmail' => __('Mostrar Email', 'clientes-plugin'),
                'showOrigen' => __('Mostrar Origen', 'clientes-plugin'),
                'showEmpresa' => __('Mostrar Empresa', 'clientes-plugin'),
                'alignment' => __('Alineación', 'clientes-plugin'),
                'borderRadius' => __('Radio del Borde', 'clientes-plugin'),
                'padding' => __('Espaciado Interno', 'clientes-plugin'),
                'left' => __('Izquierda', 'clientes-plugin'),
                'center' => __('Centro', 'clientes-plugin'),
                'right' => __('Derecha', 'clientes-plugin')
            )
        ));
    }

    /**
     * Cargar assets para frontend y editor
     */
    public function enqueue_block_assets() {
        // Estilos para frontend y editor
        wp_enqueue_style(
            'clientes-blocks-style',
            CLIENTES_PLUGIN_URL . 'assets/css/blocks.css',
            array(),
            CLIENTES_PLUGIN_VERSION
        );
    }

    /**
     * Renderizar bloque de cliente destacado
     */
    public function render_cliente_destacado_block($attributes) {
        $cliente_id = isset($attributes['clienteId']) ? (int) $attributes['clienteId'] : 0;

        if (!$cliente_id) {
            return $this->render_empty_block();
        }

        // Verificar que el cliente existe
        $post = get_post($cliente_id);
        if (!$post || $post->post_type !== 'clientes' || $post->post_status !== 'publish') {
            return $this->render_error_block(__('Cliente no encontrado', 'clientes-plugin'));
        }

        // Obtener datos del cliente
        $nombre = get_the_title($cliente_id);
        $email = get_post_meta($cliente_id, '_cliente_email', true);
        $empresa = get_post_meta($cliente_id, '_cliente_empresa', true);
        $telefono = get_post_meta($cliente_id, '_cliente_telefono', true);

        // Obtener origen de la tabla personalizada
        $db_handler = new Clientes_DB();
        $origen = $db_handler->get_cliente_origen($cliente_id);

        // Configuración del bloque
        $background_color = isset($attributes['backgroundColor']) ? $attributes['backgroundColor'] : '#f8f9fa';
        $text_color = isset($attributes['textColor']) ? $attributes['textColor'] : '#333333';
        $show_email = isset($attributes['showEmail']) ? $attributes['showEmail'] : true;
        $show_origen = isset($attributes['showOrigen']) ? $attributes['showOrigen'] : true;
        $show_empresa = isset($attributes['showEmpresa']) ? $attributes['showEmpresa'] : false;
        $alignment = isset($attributes['alignment']) ? $attributes['alignment'] : 'left';
        $border_radius = isset($attributes['borderRadius']) ? (int) $attributes['borderRadius'] : 8;
        $padding = isset($attributes['padding']) ? (int) $attributes['padding'] : 20;

        // Estilos inline
        $block_styles = sprintf(
            'background-color: %s; color: %s; text-align: %s; border-radius: %dpx; padding: %dpx;',
            esc_attr($background_color),
            esc_attr($text_color),
            esc_attr($alignment),
            $border_radius,
            $padding
        );

        // Obtener etiqueta del origen
        $origenes_labels = array(
            'web' => __('Web', 'clientes-plugin'),
            'feria' => __('Feria', 'clientes-plugin'),
            'referido' => __('Referido', 'clientes-plugin')
        );
        $origen_label = isset($origenes_labels[$origen]) ? $origenes_labels[$origen] : $origen;

        // Construir HTML
        ob_start();
        ?>
        <div class="wp-block-clientes-plugin-cliente-destacado cliente-destacado-block" style="<?php echo $block_styles; ?>">
            <div class="cliente-destacado-content">
                <h3 class="cliente-nombre"><?php echo esc_html($nombre); ?></h3>

                <?php if ($show_email && !empty($email)): ?>
                    <div class="cliente-email">
                        <span class="cliente-label"><?php _e('Email:', 'clientes-plugin'); ?></span>
                        <a href="mailto:<?php echo esc_attr($email); ?>" class="cliente-email-link">
                            <?php echo esc_html($email); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($show_empresa && !empty($empresa)): ?>
                    <div class="cliente-empresa">
                        <span class="cliente-label"><?php _e('Empresa:', 'clientes-plugin'); ?></span>
                        <span class="cliente-empresa-name"><?php echo esc_html($empresa); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($show_origen && !empty($origen)): ?>
                    <div class="cliente-origen">
                        <span class="cliente-label"><?php _e('Origen:', 'clientes-plugin'); ?></span>
                        <span class="cliente-origen-badge origen-<?php echo esc_attr($origen); ?>">
                            <?php echo esc_html($origen_label); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($telefono)): ?>
                    <div class="cliente-telefono">
                        <span class="cliente-label"><?php _e('Teléfono:', 'clientes-plugin'); ?></span>
                        <a href="tel:<?php echo esc_attr($telefono); ?>" class="cliente-telefono-link">
                            <?php echo esc_html($telefono); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <div class="cliente-actions">
                    <a href="<?php echo get_permalink($cliente_id); ?>" class="cliente-view-link">
                        <?php _e('Ver perfil completo', 'clientes-plugin'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderizar bloque vacío
     */
    private function render_empty_block() {
        return sprintf(
            '<div class="wp-block-clientes-plugin-cliente-destacado cliente-destacado-empty">
                <p>%s</p>
            </div>',
            __('Selecciona un cliente en el editor para mostrar su información.', 'clientes-plugin')
        );
    }

    /**
     * Renderizar bloque de error
     */
    private function render_error_block($message) {
        return sprintf(
            '<div class="wp-block-clientes-plugin-cliente-destacado cliente-destacado-error">
                <p><strong>%s:</strong> %s</p>
            </div>',
            __('Error', 'clientes-plugin'),
            esc_html($message)
        );
    }

    /**
     * AJAX: Obtener clientes para select
     */
    public function ajax_get_clientes_for_select() {
        // Verificar nonce
        if (!wp_verify_nonce($_REQUEST['nonce'], 'clientes_blocks_nonce')) {
            wp_die(__('Error de seguridad', 'clientes-plugin'));
        }

        $cpt_handler = new Clientes_CPT();
        $clientes = $cpt_handler->get_clientes_for_select();

        wp_send_json_success($clientes);
    }
}

// Crear archivos CSS y JS para los bloques
add_action('init', function() {
    // Crear directorio de assets si no existe
    $assets_dir = CLIENTES_PLUGIN_PATH . 'assets';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
        wp_mkdir_p($assets_dir . '/css');
        wp_mkdir_p($assets_dir . '/js');
    }

    // Crear archivo CSS de bloques si no existe
    $blocks_css_file = $assets_dir . '/css/blocks.css';
    if (!file_exists($blocks_css_file)) {
        $blocks_css = '/* Estilos para bloques de clientes */
.cliente-destacado-block {
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    margin: 1em 0;
}

.cliente-destacado-block:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.cliente-destacado-content {
    position: relative;
}

.cliente-nombre {
    margin: 0 0 15px 0;
    font-size: 1.5em;
    font-weight: bold;
    line-height: 1.3;
}

.cliente-email,
.cliente-empresa,
.cliente-origen,
.cliente-telefono {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.cliente-label {
    font-weight: bold;
    font-size: 0.9em;
    opacity: 0.8;
}

.cliente-email-link,
.cliente-telefono-link {
    color: inherit;
    text-decoration: none;
}

.cliente-email-link:hover,
.cliente-telefono-link:hover {
    text-decoration: underline;
}

.cliente-origen-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.origen-web {
    background-color: #46b450;
    color: white;
}

.origen-feria {
    background-color: #ffb900;
    color: white;
}

.origen-referido {
    background-color: #826eb4;
    color: white;
}

.cliente-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid rgba(0,0,0,0.1);
}

.cliente-view-link {
    display: inline-block;
    padding: 8px 16px;
    background-color: rgba(0,0,0,0.05);
    color: inherit;
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.9em;
    transition: background-color 0.3s ease;
}

.cliente-view-link:hover {
    background-color: rgba(0,0,0,0.1);
    text-decoration: none;
}

.cliente-destacado-empty,
.cliente-destacado-error {
    padding: 20px;
    text-align: center;
    border: 2px dashed #ccc;
    border-radius: 8px;
    background-color: #f9f9f9;
    color: #666;
}

.cliente-destacado-error {
    border-color: #dc3232;
    background-color: #ffeaea;
    color: #dc3232;
}

/* Responsive */
@media (max-width: 768px) {
    .cliente-email,
    .cliente-empresa,
    .cliente-origen,
    .cliente-telefono {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    
    .cliente-nombre {
        font-size: 1.3em;
    }
}';

        file_put_contents($blocks_css_file, $blocks_css);
    }

    // Crear archivo CSS del editor si no existe
    $editor_css_file = $assets_dir . '/css/blocks-editor.css';
    if (!file_exists($editor_css_file)) {
        $editor_css = '/* Estilos para el editor de bloques */
.wp-block-clientes-plugin-cliente-destacado {
    position: relative;
}

.cliente-destacado-preview {
    min-height: 100px;
}

.components-panel__body .cliente-config-section {
    margin-bottom: 20px;
}

.cliente-config-section h4 {
    margin-bottom: 10px;
    font-size: 14px;
    font-weight: bold;
}

.components-base-control__field {
    margin-bottom: 15px;
}

.cliente-preview-disabled {
    opacity: 0.6;
    pointer-events: none;
}';

        file_put_contents($editor_css_file, $editor_css);
    }

    // Crear archivo JS del editor si no existe
    $editor_js_file = $assets_dir . '/js/blocks-editor.js';
    if (!file_exists($editor_js_file)) {
        $editor_js = '(function() {
    var el = wp.element.createElement;
    var Component = wp.element.Component;
    var Fragment = wp.element.Fragment;
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var SelectControl = wp.components.SelectControl;
    var ColorPicker = wp.components.ColorPicker;
    var ToggleControl = wp.components.ToggleControl;
    var RangeControl = wp.components.RangeControl;
    var ServerSideRender = wp.serverSideRender;
    var __ = wp.i18n.__;

    var ClienteDestacadoEdit = function(props) {
        var attributes = props.attributes;
        var setAttributes = props.setAttributes;
        
        // Obtener lista de clientes
        var clientes = [
            { value: 0, label: clientesBlocks.strings.selectClient }
        ];
        
        // Aquí podrías hacer una llamada AJAX para obtener clientes reales
        // Por ahora usamos datos de ejemplo
        
        return el(Fragment, {},
            el(InspectorControls, {},
                el(PanelBody, {
                    title: clientesBlocks.strings.title,
                    initialOpen: true
                },
                    el(SelectControl, {
                        label: clientesBlocks.strings.selectClient,
                        value: attributes.clienteId,
                        options: clientes,
                        onChange: function(value) {
                            setAttributes({ clienteId: parseInt(value) });
                        }
                    }),
                    
                    el("div", { className: "cliente-config-section" },
                        el("h4", {}, clientesBlocks.strings.backgroundColor),
                        el(ColorPicker, {
                            color: attributes.backgroundColor,
                            onChangeComplete: function(color) {
                                setAttributes({ backgroundColor: color.hex });
                            }
                        })
                    ),
                    
                    el("div", { className: "cliente-config-section" },
                        el("h4", {}, clientesBlocks.strings.textColor),
                        el(ColorPicker, {
                            color: attributes.textColor,
                            onChangeComplete: function(color) {
                                setAttributes({ textColor: color.hex });
                            }
                        })
                    ),
                    
                    el(ToggleControl, {
                        label: clientesBlocks.strings.showEmail,
                        checked: attributes.showEmail,
                        onChange: function(value) {
                            setAttributes({ showEmail: value });
                        }
                    }),
                    
                    el(ToggleControl, {
                        label: clientesBlocks.strings.showOrigen,
                        checked: attributes.showOrigen,
                        onChange: function(value) {
                            setAttributes({ showOrigen: value });
                        }
                    }),
                    
                    el(ToggleControl, {
                        label: clientesBlocks.strings.showEmpresa,
                        checked: attributes.showEmpresa,
                        onChange: function(value) {
                            setAttributes({ showEmpresa: value });
                        }
                    }),
                    
                    el(SelectControl, {
                        label: clientesBlocks.strings.alignment,
                        value: attributes.alignment,
                        options: [
                            { value: "left", label: clientesBlocks.strings.left },
                            { value: "center", label: clientesBlocks.strings.center },
                            { value: "right", label: clientesBlocks.strings.right }
                        ],
                        onChange: function(value) {
                            setAttributes({ alignment: value });
                        }
                    }),
                    
                    el(RangeControl, {
                        label: clientesBlocks.strings.borderRadius,
                        value: attributes.borderRadius,
                        onChange: function(value) {
                            setAttributes({ borderRadius: value });
                        },
                        min: 0,
                        max: 50
                    }),
                    
                    el(RangeControl, {
                        label: clientesBlocks.strings.padding,
                        value: attributes.padding,
                        onChange: function(value) {
                            setAttributes({ padding: value });
                        },
                        min: 0,
                        max: 100
                    })
                )
            ),
            
            el(ServerSideRender, {
                block: "clientes-plugin/cliente-destacado",
                attributes: attributes
            })
        );
    };

    registerBlockType("clientes-plugin/cliente-destacado", {
        title: clientesBlocks.strings.title,
        description: clientesBlocks.strings.description,
        icon: "businessperson",
        category: "widgets",
        keywords: [__("cliente"), __("destacado"), __("perfil")],
        
        edit: ClienteDestacadoEdit,
        
        save: function() {
            return null; // Renderizado del lado del servidor
        }
    });
})();';

        file_put_contents($editor_js_file, $editor_js);
    }
});