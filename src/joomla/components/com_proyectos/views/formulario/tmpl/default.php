<?php
/**
 * Template por defecto para el formulario de proyectos
 */

// Verificar acceso directo
defined('_JEXEC') or die('Restricted access');

$app = JFactory::getApplication();
?>

<div class="formulario-proyecto">
    <div class="form-header">
        <h1>Crear Nuevo Proyecto</h1>
        <p>Complete los siguientes campos para crear un nuevo proyecto</p>
    </div>

    <form method="post" action="<?php echo JRoute::_('index.php?option=com_proyectos&task=guardar'); ?>"
          onsubmit="return validarFormulario();" id="formularioProyecto">

        <!-- Campo Nombre -->
        <div class="form-group">
            <label for="nombre">
                Nombre del Proyecto <span class="required">*</span>
            </label>
            <input type="text"
                   id="nombre"
                   name="nombre"
                   class="form-control"
                   placeholder="Ingrese el nombre del proyecto"
                   maxlength="255"
                   required />
            <div class="help-text">Máximo 255 caracteres</div>
        </div>

        <!-- Campo Fecha de Inicio -->
        <div class="form-group">
            <label for="fecha_inicio">
                Fecha de Inicio <span class="required">*</span>
            </label>
            <input type="date"
                   id="fecha_inicio"
                   name="fecha_inicio"
                   class="form-control"
                   value="<?php echo $this->getFechaActual(); ?>"
                   required />
            <div class="help-text">Seleccione la fecha de inicio del proyecto</div>
        </div>

        <!-- Campo Estado -->
        <div class="form-group">
            <label for="estado">
                Estado del Proyecto
            </label>
            <select id="estado" name="estado" class="form-control">
                <?php foreach ($this->estados as $valor => $etiqueta): ?>
                    <option value="<?php echo $valor; ?>" <?php echo ($valor == 'pendiente') ? 'selected' : ''; ?>>
                        <?php echo $etiqueta; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="help-text">Estado inicial del proyecto</div>
        </div>

        <!-- Información adicional -->
        <div class="form-group">
            <div style="background: #f0f8ff; padding: 15px; border-radius: 3px; border-left: 4px solid #337ab7;">
                <strong>💡 Información:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <li>Una vez creado, el proyecto aparecerá en la lista principal</li>
                    <li>Podrás usar el shortcode <code>[proyecto:ID]</code> en artículos de Joomla</li>
                    <li>El estado puede cambiarse posteriormente</li>
                </ul>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                💾 Guardar Proyecto
            </button>

            <a href="<?php echo $this->getVolverUrl(); ?>" class="btn btn-secondary">
                ← Volver a la Lista
            </a>
        </div>

        <!-- Campos ocultos -->
        <input type="hidden" name="task" value="guardar" />
        <input type="hidden" name="option" value="com_proyectos" />
        <?php echo JHtml::_('form.token'); ?>
    </form>
</div>

<!-- Vista previa de shortcode -->
<div style="max-width: 600px; margin: 20px auto; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
    <h3 style="margin-top: 0; color: #856404;">📋 Cómo usar el shortcode</h3>
    <p><strong>Una vez creado el proyecto</strong>, podrás usar el shortcode en cualquier artículo de Joomla:</p>
    <div style="background: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; margin: 10px 0;">
        [proyecto:ID]
    </div>
    <p style="margin-bottom: 0; font-size: 14px; color: #6c757d;">
        Donde <strong>ID</strong> es el número del proyecto que se asignará automáticamente.
        El shortcode mostrará: nombre, fecha de inicio y estado del proyecto.
    </p>
</div>

<script>
    // Agregar validación en tiempo real
    document.getElementById('nombre').addEventListener('input', function() {
        var value = this.value.trim();
        if (value.length > 0) {
            this.style.borderColor = '#5cb85c';
        } else {
            this.style.borderColor = '#ddd';
        }
    });

    document.getElementById('fecha_inicio').addEventListener('change', function() {
        var value = this.value;
        if (value) {
            this.style.borderColor = '#5cb85c';
        } else {
            this.style.borderColor = '#ddd';
        }
    });

    // Mostrar preview del shortcode (simulado)
    document.getElementById('formularioProyecto').addEventListener('submit', function(e) {
        var nombre = document.getElementById('nombre').value.trim();
        if (nombre && confirm('¿Está seguro de crear el proyecto "' + nombre + '"?')) {
            return true;
        } else if (!nombre) {
            return false;
        } else {
            e.preventDefault();
            return false;
        }
    });
</script>