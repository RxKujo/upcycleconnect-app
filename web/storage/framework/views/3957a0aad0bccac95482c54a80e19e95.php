<?php $__env->startSection('title', 'Événements'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h1 class="page-title">Événements</h1>
</div>

<div class="table-container">

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Type</th>
            <th>Format</th>
            <th>Date début</th>
            <th>Places</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $evenements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
            <td><?php echo e($e['id_evenement']); ?></td>
            <td style="font-weight: 600;"><?php echo e($e['titre']); ?></td>
            <td><span class="badge badge-waiting"><?php echo e($e['type_evenement']); ?></span></td>
            <td><?php echo e($e['format']); ?></td>
            <td><?php echo e(\Carbon\Carbon::parse($e['date_debut'])->format('d/m/Y H:i')); ?></td>
            <td><?php echo e($e['nb_places_dispo']); ?>/<?php echo e($e['nb_places_total']); ?></td>
            <td>
                <?php if($e['statut'] === 'valide'): ?>
                    <span class="badge badge-valid">Validé</span>
                <?php elseif($e['statut'] === 'refuse'): ?>
                    <span class="badge badge-refused">Refusé</span>
                <?php elseif($e['statut'] === 'annule'): ?>
                    <span class="badge badge-refused">Annulé</span>
                <?php elseif($e['statut'] === 'termine'): ?>
                    <span class="badge badge-valid">Terminé</span>
                <?php else: ?>
                    <span class="badge badge-waiting">En attente</span>
                <?php endif; ?>
            </td>
            <td>
                <div class="action-cell">
                    <a href="<?php echo e(route('admin.evenements.show', $e['id_evenement'])); ?>" class="btn-secondary btn-sm">Voir</a>
                </div>
            </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
            <td colspan="8" style="text-align: center; padding: 24px;">Aucun événement.</td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\klint\Documents\cours\projet annuel\Année 2\UpcycleConnect_PA\upcycleconnect-app\web\resources\views/admin/evenements/index.blade.php ENDPATH**/ ?>