(function() {
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
})();