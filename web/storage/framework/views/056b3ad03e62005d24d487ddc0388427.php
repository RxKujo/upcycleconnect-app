<?php $__env->startSection('title', 'Prestations'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h1 class="page-title">Prestations</h1>
</div>

<div class="table-container">

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Prix</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $prestations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
            <td><?php echo e($p['id_prestation']); ?></td>
            <td style="font-weight: 600;"><?php echo e($p['titre']); ?></td>
            <td><?php echo e(number_format($p['prix'], 2, ',', ' ')); ?> €</td>
            <td>
                <?php if($p['statut'] === 'validee'): ?>
                    <span class="badge badge-valid">Validée</span>
                <?php elseif($p['statut'] === 'refusee'): ?>
                    <span class="badge badge-refused">Refusée</span>
                <?php else: ?>
                    <span class="badge badge-waiting">En attente</span>
                <?php endif; ?>
            </td>
            <td>
                <div class="action-cell">
                    <a href="<?php echo e(route('admin.prestations.show', $p['id_prestation'])); ?>" class="btn-secondary btn-sm">Voir</a>
                </div>
            </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
            <td colspan="5" style="text-align: center; padding: 24px;">Aucune prestation.</td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\klint\Documents\cours\projet annuel\Année 2\UpcycleConnect_PA\upcycleconnect-app\web\resources\views/admin/prestations/index.blade.php ENDPATH**/ ?>