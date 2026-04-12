<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — UpcycleConnect Admin</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <style>
        :root {
            --cherry: #A4243B;
            --wheat: #D8C99B;
            --coffee: #120309;
            --cream: #F5F0E1;
            --shadow: 5px 5px 0px #120309;
            --shadow-sm: 3px 3px 0px #120309;
            --shadow-hover: 2px 2px 0px #120309;
            --border: 3px solid #120309;
        }
        body { background-color: var(--cream); font-family: 'Outfit', sans-serif; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .font-bebas { font-family: 'Bebas Neue', sans-serif; letter-spacing: 0.1em; text-transform: uppercase; }
        .font-mono { font-family: 'DM Mono', monospace; text-transform: uppercase; letter-spacing: 0.05em; }
    </style>
</head>
<body>
    <div style="width: 100%; max-width: 440px; padding: 20px;">
        <div style="text-align: center; margin-bottom: 40px;">
            <h1 class="font-bebas" style="font-size: 2.8rem; color: var(--coffee); margin: 0;">UpcycleConnect</h1>
            <p class="font-mono" style="font-size: 0.75rem; color: var(--cherry); margin-top: 4px;">Panel Administrateur</p>
        </div>

        <div style="background: var(--cream); border: var(--border); box-shadow: var(--shadow); padding: 36px 32px;">
            <?php if(session('error')): ?>
                <div style="padding: 12px 16px; border: var(--border); background: #f8d7da; color: var(--cherry); margin-bottom: 20px; font-size: 0.9rem;">
                    <?php echo e(session('error')); ?>

                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(url('/admin/login')); ?>">
                <?php echo csrf_field(); ?>
                <div style="margin-bottom: 20px;">
                    <label class="font-mono" style="font-size: 0.8rem; color: var(--coffee); display: block; margin-bottom: 6px;">Email</label>
                    <input type="email" name="email" value="<?php echo e(old('email')); ?>" required
                           style="width: 100%; padding: 10px 14px; border: 2px solid var(--coffee); background: white; font-family: 'Outfit', sans-serif; font-size: 1rem; outline: none; box-sizing: border-box;"
                           onfocus="this.style.borderColor='var(--cherry)'" onblur="this.style.borderColor='var(--coffee)'">
                </div>
                <div style="margin-bottom: 28px;">
                    <label class="font-mono" style="font-size: 0.8rem; color: var(--coffee); display: block; margin-bottom: 6px;">Mot de passe</label>
                    <input type="password" name="password" required
                           style="width: 100%; padding: 10px 14px; border: 2px solid var(--coffee); background: white; font-family: 'Outfit', sans-serif; font-size: 1rem; outline: none; box-sizing: border-box;"
                           onfocus="this.style.borderColor='var(--cherry)'" onblur="this.style.borderColor='var(--coffee)'">
                </div>
                <button type="submit"
                        class="font-bebas"
                        style="width: 100%; padding: 12px; background: var(--cherry); color: var(--cream); border: 3px solid var(--coffee); font-size: 1.2rem; letter-spacing: 0.1em; cursor: pointer; box-shadow: var(--shadow-sm); transition: all 0.15s;"
                        onmouseover="this.style.transform='translate(2px,2px)'; this.style.boxShadow='var(--shadow-hover)'"
                        onmouseout="this.style.transform='none'; this.style.boxShadow='var(--shadow-sm)'">
                    Se connecter
                </button>
            </form>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\Users\klint\Documents\cours\projet annuel\Année 2\UpcycleConnect_PA\upcycleconnect-app\web\resources\views/admin/login.blade.php ENDPATH**/ ?>