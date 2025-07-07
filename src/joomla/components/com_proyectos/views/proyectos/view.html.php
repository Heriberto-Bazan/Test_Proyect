<?php
/**
 * Vista HTML para la lista de proyectos
 */

// Verificar acceso directo
defined('_JEXEC') or die('Restricted access');

// Importar la vista base
jimport('joomla.application.component.view');

/**
 * Vista HTML para Proyectos
 */
class ProyectosViewProyectos extends JViewLegacy
{
    protected $items;
    protected $pagination;
    protected $state;
    protected $estadisticas;

    /**
     * Método display
     */
    public function display($tpl = null)
    {
        // Obtener datos del modelo
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->estadisticas = $this->get('Estadisticas');

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
        $title = 'Lista de Proyectos';
        $doc->setTitle($title);

        // Agregar CSS personalizado
        $css = "
        .proyecto-lista {
            margin: 20px 0;
        }
        .proyecto-item {
            border: 1px solid #ddd;
            margin-bottom: 10px;
            padding: 15px;
            border-radius: 5px;
            background: #fff;
        }
        .proyecto-nombre {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .proyecto-meta {
            color: #666;
            font-size: 14px;
        }
        .estado-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            color: white;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .estado-pendiente { background-color: #f0ad4e; }
        .estado-en_progreso { background-color: #5bc0de; }
        .estado-completado { background-color: #5cb85c; }
        .estado-cancelado { background-color: #d9534f; }
        .estadisticas {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .estadistica-item {
            display: inline-block;
            margin-right: 20px;
            padding: 10px;
            background: white;
            border-radius: 3px;
            text-align: center;
            min-width: 80px;
        }
        .estadistica-numero {
            font-size: 24px;
            font-weight: bold;
            color: #337ab7;
        }
        .estadistica-texto {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .toolbar {
            margin-bottom: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .btn-nuevo {
            background: #5cb85c;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 3px;
            display: inline-block;
        }
        .btn-nuevo:hover {
            background: #449d44;
            color: white;
            text-decoration: none;
        }
        ";

        $doc->addStyleDeclaration($css);
    }

    /**
     * Obtener filtros activos
     */
    public function getActiveFilters()
    {
        $filters = array();

        $search = $this->state->get('filter.search');
        if (!empty($search)) {
            $filters['search'] = $search;
        }

        $estado = $this->state->get('filter.estado');
        if (!empty($estado)) {
            $filters['estado'] = $estado;
        }

        return $filters;
    }

    /**
     * Obtener URL para nuevo proyecto
     */
    public function getNuevoProyectoUrl()
    {
        return JRoute::_('index.php?option=com_proyectos&view=formulario');
    }

    /**
     * Obtener opciones de estado para filtro
     */
    public function getEstadoOptions()
    {
        return array(
            '' => 'Todos los estados',
            'pendiente' => 'Pendiente',
            'en_progreso' => 'En Progreso',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado'
        );
    }
}