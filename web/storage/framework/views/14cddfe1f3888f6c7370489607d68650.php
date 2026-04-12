<?php $__env->startSection('title', 'Gestion Conteneur'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h1 class="page-title">Conteneur : <?php echo e($conteneur['conteneur_ref']); ?></h1>
    <a href="<?php echo e(route('admin.conteneurs.index')); ?>" class="btn-secondary">Retour aux conteneurs</a>
</div>

<div class="info-grid" style="margin-bottom: 30px;">
    <div class="card">
        <span class="info-label">Détails du conteneur</span>
        <p class="info-value"><strong>Adresse :</strong> <?php echo e($conteneur['adresse']); ?>, <?php echo e($conteneur['ville']); ?></p>
        <p class="info-value"><strong>Capacité :</strong> <?php echo e($conteneur['capacite']); ?> objets</p>
        <p class="info-value"><strong>Statut :</strong> <span class="badge badge-waiting"><?php echo e($conteneur['statut']); ?></span></p>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Scanner Code Barre</h3>
        <p>Utilisez ce champ pour lire le code avec une douchette ou copier-coller manuellement.</p>
        <form action="<?php echo e(route('admin.conteneurs.scan', $conteneur['id_conteneur'])); ?>" method="POST" style="display:flex; gap:10px;">
            <?php echo csrf_field(); ?>
            <input type="text" name="code_valeur" class="form-input" placeholder="UC-XXXXXX..." required autofocus autocomplete="off">
            <button type="submit" class="btn-primary">Valider</button>
        </form>
    </div>
</div>

<h2>Commandes Associées</h2>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID Cmd</th>
                <th>Acheteur</th>
                <th>Statut</th>
                <th>Générer Code (Dépôt)</th>
                <th>Générer Code (Récup)</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $commandes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cmd): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td>CMD-<?php echo e($cmd['id_commande']); ?></td>
                <td>Usr #<?php echo e($cmd['id_acheteur']); ?></td>
                <td>
                    <?php if(in_array($cmd['statut'], ['recuperee'])): ?>
                        <span class="badge badge-valid"><?php echo e($cmd['statut']); ?></span>
                    <?php elseif(in_array($cmd['statut'], ['deposee', 'en_conteneur'])): ?>
                        <span class="badge badge-waiting"><?php echo e($cmd['statut']); ?></span>
                    <?php else: ?>
                        <span class="badge" style="background:#eee;color:#333;"><?php echo e($cmd['statut']); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?php echo e(route('admin.commandes.barcode.pdf', ['idCommande' => $cmd['id_commande'], 'type_code' => 'depot_particulier'])); ?>" class="btn-secondary btn-sm" target="_blank">Dépôt PDF</a>
                </td>
                <td>
                    <a href="<?php echo e(route('admin.commandes.barcode.pdf', ['idCommande' => $cmd['id_commande'], 'type_code' => 'recuperation_pro'])); ?>" class="btn-secondary btn-sm" target="_blank">Récup PDF</a>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="5" style="text-align: center; padding: 24px;">Aucune commande dans ce conteneur.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<h2>Tickets Incidents</h2>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Sujet</th>
                <th>Description</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tck): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e(substr($tck['date_creation'], 0, 10)); ?></td>
                <td><?php echo e($tck['sujet']); ?></td>
                <td><?php echo e(Str::limit($tck['description'], 50)); ?></td>
                <td>
                    <?php if($tck['statut'] == 'resolu'): ?>
                        <span class="badge badge-valid"><?php echo e($tck['statut']); ?></span>
                    <?php else: ?>
                        <span class="badge badge-refused"><?php echo e($tck['statut']); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($tck['statut'] != 'resolu'): ?>
                    <form action="<?php echo e(route('admin.conteneurs.tickets.resolve', [$conteneur['id_conteneur'], $tck['id_ticket']])); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>
                        <button type="submit" class="btn-success btn-sm">Marquer Résolu</button>
                    </form>
                    <?php else: ?>
                    -
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="5" style="text-align: center; padding: 24px;">Aucun ticket incident pour ce conteneur.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\klint\Documents\cours\projet annuel\Année 2\UpcycleConnect_PA\upcycleconnect-app\web\resources\views/admin/conteneurs/show.blade.php ENDPATH**/ ?>