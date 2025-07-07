<?php
/**
 * Modelo para proyecto individual
 */

// Verificar acceso directo
defined('_JEXEC') or die('Restricted access');

// Importar el modelo base
jimport('joomla.application.component.modeladmin');

/**
 * Modelo de Proyecto individual
 */
class ProyectosModelProyecto extends JModelAdmin
{
    /**
     * Método para obtener la tabla
     */
    public function getTable($type = 'Proyecto', $prefix = 'ProyectosTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
    }

    /**
     * Método para obtener el formulario
     */
    public function getForm($data = array(), $loadData = true)
    {
        // Obtener el formulario base
        $form = $this->loadForm('com_proyectos.proyecto', 'proyecto', array('control' => 'jform', 'load_data' => $loadData));

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Método para obtener los datos que poblarán el formulario
     */
    protected function loadFormData()
    {
        // Verificar la sesión para datos previamente ingresados
        $data = JFactory::getApplication()->getUserState('com_proyectos.edit.proyecto.data', array());

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Guardar proyecto
     */
    public function save($data)
    {
        $db = $this->getDbo();

        // Validar datos
        if (empty($data['nombre'])) {
            $this->setError('El nombre del proyecto es requerido');
            return false;
        }

        if (empty($data['fecha_inicio'])) {
            $this->setError('La fecha de inicio es requerida');
            return false;
        }

        // Validar estado
        $estados_validos = array('pendiente', 'en_progreso', 'completado', 'cancelado');
        if (!in_array($data['estado'], $estados_validos)) {
            $data['estado'] = 'pendiente';
        }

        // Validar formato de fecha
        $fecha = DateTime::createFromFormat('Y-m-d', $data['fecha_inicio']);
        if (!$fecha) {
            $this->setError('Formato de fecha no válido');
            return false;
        }

        try {
            // Insertar nuevo proyecto
            $query = $db->getQuery(true);

            $columns = array('nombre', 'fecha_inicio', 'estado', 'created_at', 'updated_at');
            $values = array(
                $db->quote($data['nombre']),
                $db->quote($data['fecha_inicio']),
                $db->quote($data['estado']),
                $db->quote(date('Y-m-d H:i:s')),
                $db->quote(date('Y-m-d H:i:s'))
            );

            $query
                ->insert($db->quoteName('#__proyectos'))
                ->columns($db->quoteName($columns))
                ->values(implode(',', $values));

            $db->setQuery($query);
            $result = $db->execute();

            if ($result) {
                // Obtener el ID del proyecto insertado
                $proyecto_id = $db->insertid();
                $this->setState($this->getName() . '.id', $proyecto_id);
                return $proyecto_id;
            } else {
                $this->setError('Error al guardar en la base de datos: ' . $db->getErrorMsg());
                return false;
            }

        } catch (Exception $e) {
            $this->setError('Error al guardar el proyecto: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener proyecto por ID
     */
    public function getItem($pk = null)
    {
        if (!$pk) {
            $pk = $this->getState($this->getName() . '.id');
        }

        if (!$pk) {
            return new stdClass();
        }

        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select('*');
        $query->from('#__proyectos');
        $query->where('id = ' . (int) $pk);

        $db->setQuery($query);

        try {
            $item = $db->loadObject();

            if (!$item) {
                return new stdClass();
            }

            return $item;

        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Obtener todos los proyectos
     */
    public function getAllProyectos()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select('*');
        $query->from('#__proyectos');
        $query->order('created_at DESC');

        $db->setQuery($query);

        try {
            return $db->loadObjectList();
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return array();
        }
    }

    /**
     * Obtener proyecto por ID para shortcode
     */
    public function getProyectoForShortcode($id)
    {
        if (!$id || !is_numeric($id)) {
            return null;
        }

        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select('id, nombre, fecha_inicio, estado');
        $query->from('#__proyectos');
        $query->where('id = ' . (int) $id);

        $db->setQuery($query);

        try {
            return $db->loadObject();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Eliminar proyecto
     */
    public function delete(&$pks)
    {
        if (!is_array($pks)) {
            $pks = array($pks);
        }

        $db = $this->getDbo();

        foreach ($pks as $pk) {
            $query = $db->getQuery(true);
            $query->delete('#__proyectos');
            $query->where('id = ' . (int) $pk);

            $db->setQuery($query);

            try {
                $db->execute();
            } catch (Exception $e) {
                $this->setError($e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * Obtener estados disponibles
     */
    public function getEstados()
    {
        return array(
            'pendiente' => 'Pendiente',
            'en_progreso' => 'En Progreso',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado'
        );
    }
}