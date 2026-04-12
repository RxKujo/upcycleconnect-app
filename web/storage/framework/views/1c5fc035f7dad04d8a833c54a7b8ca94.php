<?php $__env->startSection('title', 'Utilisateurs'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h1 class="page-title">Utilisateurs</h1>
</div>

<div class="table-container">

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $utilisateurs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
            <td><?php echo e($u['id_utilisateur']); ?></td>
            <td><?php echo e($u['nom']); ?></td>
            <td><?php echo e($u['prenom']); ?></td>
            <td><?php echo e($u['email']); ?></td>
            <td><span class="badge badge-waiting"><?php echo e($u['role']); ?></span></td>
            <td>
                <?php if($u['est_banni']): ?>
                    <span class="badge badge-refused">Banni</span>
                <?php else: ?>
                    <span class="badge badge-valid">Actif</span>
                <?php endif; ?>
            </td>
            <td>
                <div class="action-cell">
                    <a href="<?php echo e(route('admin.utilisateurs.show', $u['id_utilisateur'])); ?>" class="btn-secondary btn-sm">Voir</a>
                </div>
            </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
            <td colspan="7" style="text-align: center; padding: 24px;">Aucun utilisateur trouvé.</td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\klint\Documents\cours\projet annuel\Année 2\UpcycleConnect_PA\upcycleconnect-app\web\resources\views/admin/utilisateurs/index.blade.php ENDPATH**/ ?>