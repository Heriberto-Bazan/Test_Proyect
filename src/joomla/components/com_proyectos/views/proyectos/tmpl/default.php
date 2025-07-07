<?php
/**
 * Template por defecto para la lista de proyectos
 */

// Verificar acceso directo
defined('_JEXEC') or die('Restricted access');

$app = JFactory::getApplication();
$user = JFactory::getUser();
$userId = $user->get('id');
?>

<div class="proyectos-lista">
    <h1>Gestión de Proyectos</h1>

    <!-- Estadísticas -->
    <?php if (!empty($this->estadisticas)): ?>
        <div class="estadisticas">
            <h3>Resumen de Proyectos</h3>
            <div class="estadisticas-container">
                <div class="estadistica-item">
                    <div class="estadistica-numero"><?php echo $this->estadisticas['total']; ?></div>
                    <div class="estadistica-texto">Total</div>
                </div>
                <div class="estadistica-item">
                    <div class="estadistica-numero"><?php echo $this->estadisticas['pendiente']; ?></div>
                    <div class="estadistica-texto">Pendientes</div>
                </div>
                <div class="estadistica-item">
                    <div class="estadistica-numero"><?php echo $this->estadisticas['en_progreso']; ?></div>
                    <div class="estadistica-texto">En Progreso</div>
                </div>
                <div class="estadistica-item">
                    <div class="estadistica-numero"><?php echo $this->estadisticas['completado']; ?></div>
                    <div class="estadistica-texto">Completados</div>
                </div>
                <div class="estadistica-item">
                    <div class="estadistica-numero"><?php echo $this->estadisticas['cancelado']; ?></div>
                    <div class="estadistica-texto">Cancelados</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Toolbar -->
    <div class="toolbar">
        <a href="<?php echo $this->getNuevoProyectoUrl(); ?>" class="btn-nuevo">
            ➕ Nuevo Proyecto
        </a>

        <!-- Filtros -->
        <form method="post" name="adminForm" id="adminForm" style="display: inline-block; margin-left: 20px;">
            <label for="filter_search">Buscar:</label>
            <input type="text" name="filter_search" id="filter_search"
                   value="<?php echo $this->escape($this->state->get('filter.search')); ?>"
                   placeholder="Nombre del proyecto..." />

            <label for="filter_estado" style="margin-left: 15px;">Estado:</label>
            <select name="filter_estado" id="filter_estado">
                <?php foreach ($this->getEstadoOptions() as $value => $text): ?>
                    <option value="<?php echo $value; ?>" <?php echo ($this->state->get('filter.estado') == $value) ? 'selected' : ''; ?>>
                        <?php echo $text; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" style="margin-left: 10px;">Filtrar</button>

            <input type="hidden" name="task" value="" />
            <input type="hidden" name="option" value="com_proyectos" />
            <?php echo JHtml::_('form.token'); ?>
        </form>
    </div>

    <!-- Lista de Proyectos -->
    <div class="proyecto-lista">
        <?php if (!empty($this->items)): ?>
            <?php foreach ($this->items as $i => $item): ?>
                <div class="proyecto-item">
                    <div class="proyecto-nombre">
                        <?php echo $this->escape($item->nombre); ?>
                        <span class="estado-badge estado-<?php echo $item->estado; ?>">
                            <?php echo $item->estado_label; ?>
                        </span>
                    </div>
                    <div class="proyecto-meta">
                        <strong>Fecha de inicio:</strong>
                        <?php echo isset($item->fecha_inicio_formatted) ? $item->fecha_inicio_formatted : JHtml::_('date', $item->fecha_inicio, 'd/m/Y'); ?>

                        <?php if (!empty($item->created_at)): ?>
                            | <strong>Creado:</strong> <?php echo JHtml::_('date', $item->created_at, 'd/m/Y H:i'); ?>
                        <?php endif; ?>

                        | <strong>ID:</strong> #<?php echo $item->id; ?>
                    </div>

                    <!-- Demostración del shortcode -->
                    <div style="margin-top: 10px; padding: 10px; background: #f0f0f0; border-radius: 3px; font-size: 12px;">
                        <strong>Shortcode:</strong> [proyecto:<?php echo $item->id; ?>]
                        <em>(Para usar en artículos de Joomla)</em>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Paginación -->
            <?php if ($this->pagination->getNumLinks() > 1): ?>
                <div class="pagination">
                    <?php echo $this->pagination->getListFooter(); ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="no-proyectos" style="text-align: center; padding: 40px; color: #666;">
                <h3>No hay proyectos</h3>
                <p>No se encontraron proyectos. <a href="<?php echo $this->getNuevoProyectoUrl(); ?>">Crear el primer proyecto</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Auto-submit del formulario de filtros
    document.getElementById('filter_search').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('adminForm').submit();
        }
    });

    document.getElementById('filter_estado').addEventListener('change', function() {
        document.getElementById('adminForm').submit();
    });
</script>