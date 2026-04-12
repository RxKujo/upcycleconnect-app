<?php $__env->startSection('title', 'Catégories'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h1 class="page-title">Catégories</h1>
    <a href="<?php echo e(route('admin.categories.create')); ?>" class="btn-primary btn-sm">+ Nouvelle</a>
</div>

<div class="table-container">

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
            <td><?php echo e($cat['id_categorie']); ?></td>
            <td style="font-weight: 600;"><?php echo e($cat['nom']); ?></td>
            <td><?php echo e($cat['description']); ?></td>
            <td>
                <div class="action-cell">
                    <a href="<?php echo e(route('admin.categories.edit', $cat['id_categorie'])); ?>" class="btn-secondary btn-sm">Modifier</a>
                    <form action="<?php echo e(route('admin.categories.destroy', $cat['id_categorie'])); ?>" method="POST" style="margin: 0;">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn-danger" onclick="return confirm('Supprimer cette catégorie ?')">Supprimer</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr>
            <td colspan="4" style="text-align: center; padding: 24px;">Aucune catégorie.</td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\klint\Documents\cours\projet annuel\Année 2\UpcycleConnect_PA\upcycleconnect-app\web\resources\views/admin/categories/index.blade.php ENDPATH**/ ?>