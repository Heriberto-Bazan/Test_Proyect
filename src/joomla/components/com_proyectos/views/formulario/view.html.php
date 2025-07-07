<?php
/**
 * Vista HTML para el formulario de proyectos
 */

// Verificar acceso directo
defined('_JEXEC') or die('Restricted access');

// Importar la vista base
jimport('joomla.application.component.view');

/**
 * Vista HTML para Formulario de Proyectos
 */
class ProyectosViewFormulario extends JViewLegacy
{
    protected $form;
    protected $item;
    protected $estados;

    /**
     * Método display
     */
    public function display($tpl = null)
    {
        // Obtener datos del modelo
        $this->item = $this->get('Item');
        $this->estados = $this->getModel()->getEstados();

        // Verificar errores
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode('\n', $errors));
            return false;
        }

        // Preparar el documento
        $this->prepareDocument();

        // Mostrar la plantilla
        parent::display($tpl);
    }

    /**
     * Preparar el documento
     */
    protected function prepareDocument()
    {
        $app = JFactory::getApplication();
        $doc = JFactory::getDocument();

        // Establecer título de la página
        $title = 'Nuevo Proyecto';
        $doc->setTitle($title);

        // Agregar CSS personalizado
        $css = "
        .formulario-proyecto {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: #337ab7;
            outline: none;
            box-shadow: 0 0 5px rgba(51, 122, 183, 0.3);
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin-right: 10px;
        }
        .btn-primary {
            background: #337ab7;
            color: white;
        }
        .btn-primary:hover {
            background: #286090;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #545b62;
            color: white;
            text-decoration: none;
        }
        .form-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .form-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .form-header h1 {
            margin: 0;
            color: #333;
        }
        .form-header p {
            margin: 10px 0 0 0;
            color: #666;
        }
        .required {
            color: #d9534f;
        }
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        select.form-control {
            height: 42px;
        }
        ";

        $doc->addStyleDeclaration($css);

        // Agregar JavaScript para validación
        $js = "
        function validarFormulario() {
            var nombre = document.getElementById('nombre').value.trim();
            var fecha = document.getElementById('fecha_inicio').value.trim();
            
            if (nombre === '') {
                alert('El nombre del proyecto es requerido');
                document.getElementById('nombre').focus();
                return false;
            }
            
            if (fecha === '') {
                alert('La fecha de inicio es requerida');
                document.getElementById('fecha_inicio').focus();
                return false;
            }
            
            // Validar formato de fecha
            var fechaRegex = /^\d{4}-\d{2}-\d{2}$/;
            if (!fechaRegex.test(fecha)) {
                alert('La fecha debe tener el formato YYYY-MM-DD');
                document.getElementById('fecha_inicio').focus();
                return false;
            }
            
            return true;
        }
        ";

        $doc->addScriptDeclaration($js);
    }

    /**
     * Obtener URL de vuelta a la lista
     */
    public function getVolverUrl()
    {
        return JRoute::_('index.php?option=com_proyectos&view=proyectos');
    }

    /**
     * Obtener fecha actual en formato Y-m-d
     */
    public function getFechaActual()
    {
        return date('Y-m-d');
    }
}