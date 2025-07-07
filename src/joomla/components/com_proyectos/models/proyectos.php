<?php
/**
 * Modelo para la lista de proyectos
 */

// Verificar acceso directo
defined('_JEXEC') or die('Restricted access');

// Importar el modelo base
jimport('joomla.application.component.modellist');

/**
 * Modelo de lista de Proyectos
 */
class ProyectosModelProyectos extends JModelList
{
    /**
     * Constructor
     */
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'p.id',
                'nombre', 'p.nombre',
                'fecha_inicio', 'p.fecha_inicio',
                'estado', 'p.estado',
                'created_at', 'p.created_at'
            );
        }

        parent::__construct($config);
    }

    /**
     * Método para obtener la consulta SQL
     */
    protected function getListQuery()
    {
        // Crear una nueva consulta
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        // Seleccionar campos de la tabla proyectos
        $query->select(
            $this->getState(
                'list.select',
                'p.id, p.nombre, p.fecha_inicio, p.estado, p.created_at, p.updated_at'
            )
        );

        $query->from('#__proyectos AS p');

        // Filtrar por estado si se especifica
        $estado = $this->getState('filter.estado');
        if (!empty($estado)) {
            $query->where('p.estado = ' . $db->quote($estado));
        }

        // Filtrar por búsqueda
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
            $query->where('(p.nombre LIKE ' . $search . ')');
        }

        // Ordenamiento
        $orderCol = $this->state->get('list.ordering', 'p.created_at');
        $orderDirn = $this->state->get('list.direction', 'DESC');

        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    /**
     * Método para obtener los elementos de la lista
     */
    public function getItems()
    {
        $items = parent::getItems();

        if ($items) {
            foreach ($items as &$item) {
                // Formatear la fecha
                if (!empty($item->fecha_inicio)) {
                    $item->fecha_inicio_formatted = JHtml::_('date', $item->fecha_inicio, 'd/m/Y');
                }

                // Añadir clase CSS según el estado
                $item->estado_class = $this->getEstadoClass($item->estado);
                $item->estado_label = $this->getEstadoLabel($item->estado);
            }
        }

        return $items;
    }

    /**
     * Obtener estadísticas de proyectos
     */
    public function getEstadisticas()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select('estado, COUNT(*) as total');
        $query->from('#__proyectos');
        $query->group('estado');

        $db->setQuery($query);
        $results = $db->loadAssocList('estado');

        // Inicializar estadísticas
        $estadisticas = array(
            'pendiente' => 0,
            'en_progreso' => 0,
            'completado' => 0,
            'cancelado' => 0,
            'total' => 0
        );

        // Llenar con datos reales
        foreach ($results as $estado => $data) {
            $estadisticas[$estado] = (int) $data['total'];
            $estadisticas['total'] += (int) $data['total'];
        }

        return $estadisticas;
    }

    /**
     * Obtener clase CSS para el estado
     */
    private function getEstadoClass($estado)
    {
        $clases = array(
            'pendiente' => 'label-warning',
            'en_progreso' => 'label-info',
            'completado' => 'label-success',
            'cancelado' => 'label-important'
        );

        return isset($clases[$estado]) ? $clases[$estado] : 'label-default';
    }

    /**
     * Obtener etiqueta legible para el estado
     */
    private function getEstadoLabel($estado)
    {
        $etiquetas = array(
            'pendiente' => 'Pendiente',
            'en_progreso' => 'En Progreso',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado'
        );

        return isset($etiquetas[$estado]) ? $etiquetas[$estado] : ucfirst($estado);
    }

    /**
     * Método para establecer filtros por defecto
     */
    protected function populateState($ordering = null, $direction = null)
    {
        $app = JFactory::getApplication();

        // Obtener filtros del formulario
        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        $estado = $app->getUserStateFromRequest($this->context . '.filter.estado', 'filter_estado', '', 'string');
        $this->setState('filter.estado', $estado);

        // Paginación
        $limit = $app->getUserStateFromRequest($this->context . '.list.limit', 'limit', $app->get('list_limit'), 'uint');
        $this->setState('list.limit', $limit);

        $limitstart = $app->getUserStateFromRequest($this->context . '.limitstart', 'limitstart', 0, 'uint');
        $this->setState('list.start', $limitstart);

        // Ordenamiento
        parent::populateState('p.created_at', 'DESC');
    }

    /**
     * Obtener una instancia de JTableProyecto
     */
    public function getTable($type = 'Proyecto', $prefix = 'ProyectosTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
    }
}