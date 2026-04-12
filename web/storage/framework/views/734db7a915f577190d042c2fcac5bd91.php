<?php $__env->startSection('title', 'Conteneurs'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h1 class="page-title">Conteneurs</h1>
</div>

<div class="card" style="margin-bottom: 30px;">
    <h3>Nouveau Conteneur</h3>
    <form action="<?php echo e(route('admin.conteneurs.store')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <div class="info-grid">
            <div class="form-group">
                <label class="form-label">Référence / Nom</label>
                <input type="text" name="conteneur_ref" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Capacité (objets)</label>
                <input type="number" name="capacite" class="form-input" value="50" required>
            </div>
            <div class="form-group">
                <label class="form-label">Adresse</label>
                <input type="text" name="adresse" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Ville</label>
                <input type="text" name="ville" class="form-input" required>
            </div>
        </div>
        <button type="submit" class="btn-primary">Ajouter Conteneur</button>
    </form>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Référence</th>
                <th>Adresse</th>
                <th>Capacité</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $conteneurs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e($c['id_conteneur']); ?></td>
                <td><?php echo e($c['conteneur_ref']); ?></td>
                <td><?php echo e($c['adresse']); ?>, <?php echo e($c['ville']); ?></td>
                <td><?php echo e($c['capacite']); ?></td>
                <td>
                    <?php if($c['statut'] == 'actif'): ?>
                        <span class="badge badge-valid">Actif</span>
                    <?php else: ?>
                        <span class="badge badge-waiting"><?php echo e($c['statut']); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="action-cell">
                        <a href="<?php echo e(route('admin.conteneurs.show', $c['id_conteneur'])); ?>" class="btn-secondary btn-sm">Gérer</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 24px;">Aucun conteneur trouvé.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\klint\Documents\cours\projet annuel\Année 2\UpcycleConnect_PA\upcycleconnect-app\web\resources\views/admin/conteneurs/index.blade.php ENDPATH**/ ?>