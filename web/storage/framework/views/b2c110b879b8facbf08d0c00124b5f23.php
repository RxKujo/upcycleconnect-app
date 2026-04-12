<?php $__env->startSection('title', 'Utilisateur #' . $utilisateur['id_utilisateur']); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h1 class="page-title">Utilisateur #<?php echo e($utilisateur['id_utilisateur']); ?></h1>
    <a href="<?php echo e(route('admin.utilisateurs.index')); ?>" class="btn-secondary btn-sm">← Retour</a>
</div>

<div class="card" style="cursor: default; transform: none;">
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Nom</span>
            <p class="info-value"><?php echo e($utilisateur['nom']); ?></p>
        </div>
        <div class="info-item">
            <span class="info-label">Prénom</span>
            <p class="info-value"><?php echo e($utilisateur['prenom']); ?></p>
        </div>
        <div class="info-item">
            <span class="info-label">Email</span>
            <p class="info-value"><?php echo e($utilisateur['email']); ?></p>
        </div>
        <div class="info-item">
            <span class="info-label">Rôle</span>
            <p class="info-value"><span class="badge badge-waiting"><?php echo e($utilisateur['role']); ?></span></p>
        </div>
        <div class="info-item">
            <span class="info-label">Téléphone</span>
            <p class="info-value"><?php echo e($utilisateur['telephone'] ?? '—'); ?></p>
        </div>
        <div class="info-item">
            <span class="info-label">Ville</span>
            <p class="info-value"><?php echo e($utilisateur['ville'] ?? '—'); ?></p>
        </div>
        <div class="info-item">
            <span class="info-label">Statut</span>
            <p class="info-value">
                <?php if($utilisateur['est_banni']): ?>
                    <span class="badge badge-refused">Banni</span>
                <?php else: ?>
                    <span class="badge badge-valid">Actif</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="info-item">
            <span class="info-label">Inscription</span>
            <p class="info-value"><?php echo e($utilisateur['date_creation'] ?? '—'); ?></p>
        </div>
    </div>
</div>

<div class="action-cell" style="margin-top: 24px;">
    <?php if($utilisateur['est_banni']): ?>
        <form action="<?php echo e(route('admin.utilisateurs.unban', $utilisateur['id_utilisateur'])); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn-success">Débannir</button>
        </form>
    <?php else: ?>
        <form action="<?php echo e(route('admin.utilisateurs.ban', $utilisateur['id_utilisateur'])); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="date_fin_ban" value="2099-12-31">
            <button type="submit" class="btn-danger">Bannir</button>
        </form>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\klint\Documents\cours\projet annuel\Année 2\UpcycleConnect_PA\upcycleconnect-app\web\resources\views/admin/utilisateurs/show.blade.php ENDPATH**/ ?>