<?php
declare(strict_types=1);

// Log erreurs PHP dans un fichier (à enlever après debug)
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/logs/php-error.log');
error_reporting(E_ALL);

try {
require_once __DIR__ . '/config/bootstrap.php';

} catch (Throwable $e) {
  error_log("
  TRAP ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString());
  http_response_code(500);
  echo "Erreur serveur (
  trap).";
  exit;
}


// DEBUG temporaire si besoin :
// ini_set('display_errors','1'); error_reporting(E_ALL);

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim((string)($_POST['email'] ?? '')));
  $password = (string)($_POST['password'] ?? '');
  $csrf = (string)($_POST['csrf'] ?? '');

  if (!csrf_check($csrf)) {
    $error = "Requête invalide (CSRF).";
  } elseif ($email === '' || $password === '') {
    $error = "Email / mot de passe requis.";
  } else {
    $pdo = db();
$stmt = $pdo->prepare("SELECT id,email,password_hash,enabled,failed_login_count,locked_until, showStock
                       FROM users WHERE email = :email LIMIT 1");

    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    $generic = "Identifiants invalides.";

    if (!$user || (int)$user['enabled'] !== 1) {
      usleep(200000);
      $error = $generic;
    } else {
      if (!empty($user['locked_until']) && strtotime((string)$user['locked_until']) > time()) {
        $error = "Compte temporairement bloqué. Réessaie plus tard.";
      } else {
        if (password_verify($password, (string)$user['password_hash'])) {
          try {
            $pdo->beginTransaction();

            $pdo->prepare("UPDATE users
                           SET failed_login_count = 0, locked_until = NULL, last_login_at = NOW()
                           WHERE id = :id")->execute([':id' => (int)$user['id']]);

            session_regenerate_id(true);
            session_bind();

            $pdo->prepare("INSERT INTO user_sessions(user_id, session_id, ip, user_agent_hash)
                           VALUES(:uid, :sid, :ip, :ua)")
                ->execute([
                  ':uid' => (int)$user['id'],
                  ':sid' => session_id(),
                  ':ip'  => ($_SERVER['REMOTE_ADDR'] ?? null),
                  ':ua'  => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''),
                ]);

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_email'] = (string)$user['email'];
            $_SESSION['sess_db_id'] = (int)$pdo->lastInsertId();
            $_SESSION['user_show_stock'] = !empty($user['showStock']) ? 1 : 0;

            

            $pdo->commit();

            header("Location: index.php");
            exit;
          } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = "Erreur interne.";
          }
        } else {
          $max = (int) envv('LOGIN_MAX_ATTEMPTS', 6);
          $lockMin = (int) envv('LOGIN_LOCK_MINUTES', 15);

          $fails = (int)$user['failed_login_count'] + 1;
          $lockedUntil = null;

          if ($fails >= $max) {
            $lockedUntil = (new DateTimeImmutable())->modify("+{$lockMin} minutes")->format('Y-m-d H:i:s');
          }

          $pdo->prepare("UPDATE users
                         SET failed_login_count = :fails, locked_until = :locked
                         WHERE id = :id")
              ->execute([
                ':fails' => $fails,
                ':locked' => $lockedUntil,
                ':id' => (int)$user['id'],
              ]);

          usleep(200000);
          $error = $generic;
        }
      }
    }
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Connexion | 3SK</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>

  <link href="https://cdn.jsdelivr.net/npm/
  trap@5.3.3/dist/css/
  trap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>

  <style>
    :root{
      --brand-primary:#ff6015;
      --brand-secondary:#356ef3;
      --bg-soft:#f6f7fb;
      --card-border: rgba(16,24,40,.10);
      --shadow-soft: 0 10px 30px rgba(16,24,40,.10);
    }
   body{
  min-height:100vh;
  background:
    radial-gradient(1200px 600px at 10% 10%, rgba(53,110,243,.16), transparent 55%),
    radial-gradient(900px 500px at 90% 20%, rgba(255,96,21,.18), transparent 55%),
    var(--bg-soft);
  display:flex;
  align-items:center;
  justify-content:center;

}

/* wrapper responsive */
.login-wrap{
  width: 100%;
  max-width: 560px;   /* desktop: carte “petite” */
}

/* optionnel: encore plus petit sur grand écran */
@media (min-width: 992px){
  .login-wrap{ max-width: 520px; }
}

    .card-soft{
      border:1px solid var(--card-border);
      border-radius: 22px;
      box-shadow: var(--shadow-soft);
      background: rgba(255,255,255,.90);
      backdrop-filter: blur(12px);
      overflow:hidden;
    }
    .brand-mark{
      width:52px;height:52px;border-radius:18px;
      background: url(./assets/img/logo_3sk.jpeg) no-repeat center/contain;
      box-shadow: 0 12px 26px rgba(107,107,107,.22);
      border: 1px solid rgba(16,24,40,.12);
      flex: 0 0 auto;
    }
    .btn-primary{
      --bs-btn-bg: var(--brand-primary);
      --bs-btn-border-color: var(--brand-primary);
      --bs-btn-hover-bg: #ff4f00;
      --bs-btn-hover-border-color: #ff4f00;
      --bs-btn-active-bg: #e64500;
      --bs-btn-active-border-color: #e64500;
    }
    .progress-bar{ background: var(--brand-secondary) !important; }
    .input-group-text{
      background: rgba(255,255,255,.85);
      border-color: rgba(16,24,40,.12);
    }
    .form-control{
      border-color: rgba(16,24,40,.12);
      border-radius: 14px;
      padding-top:.75rem; padding-bottom:.75rem;
    }

    .swiper-track{ display:flex; width:200%; transition: transform .35s ease; }
    .swiper-slide{ width:50%; padding: 24px; }
    .step-pill{
      display:inline-flex; align-items:center; gap:.45rem;
      padding:.35rem .7rem;
      border-radius:999px;
      border:1px solid rgba(16,24,40,.12);
      background: rgba(255,255,255,.75);
      font-size:.88rem;
    }
  </style>
</head>

<body>
  <div class="container py-4">
    <div class="login-wrap mx-auto">
      <div class="card-soft">
    <div class="card-soft">
      <div class="p-4 border-bottom" style="background:rgba(255,255,255,.75)">
        <div class="d-flex align-items-center gap-3">
          <div class="brand-mark"></div>
          <div class="flex-grow-1">
            <div class="fw-semibold" style="line-height:1.1;font-size:1.15rem;">3SK Parts Auto</div>
            <div class="text-muted small">Connexion au catalogue</div>
          </div>
          <span class="step-pill" id="stepPill">
            <i class="bi bi-1-circle" style="color:var(--brand-secondary)"></i>
            <span id="stepText">Email</span>
          </span>
        </div>

        <div class="progress mt-3" style="height:8px;border-radius:999px;">
          <div class="progress-bar" id="stepBar" style="width:50%;border-radius:999px;"></div>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger mt-3 mb-0" style="border-radius:16px;">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
      </div>

      <form id="loginForm" method="post" action="login.php" autocomplete="on" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

        <div class="swiper-track" id="track">
          <div class="swiper-slide">
            <div class="fw-semibold mb-1" style="font-size:1.05rem;">
              <i class="bi bi-envelope" style="color:var(--brand-secondary)"></i> Adresse email
            </div>
            <div class="text-muted small mb-3">Renseignez votre adresse email, puis cliquez sur <b>Suivant</b>.</div>

            <div class="input-group mb-3">
              <span class="input-group-text"><i class="bi bi-at"></i></span>
              <input id="email" name="email" type="email" class="form-control"
                     placeholder="client@domaine.com" required autofocus
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
            </div>

            <div class="d-grid gap-2">
              <button type="button" class="btn btn-primary py-2" id="btnNext" style="border-radius:16px;">
                Suivant <i class="bi bi-arrow-right"></i>
              </button>
             
            </div>
          </div>

          <div class="swiper-slide">
            <div class="fw-semibold mb-1" style="font-size:1.05rem;">
              <i class="bi bi-lock" style="color:var(--brand-primary)"></i> Mot de passe
            </div>
            <div class="text-muted small mb-3">Entrez votre mot de passe puis <b>Valider</b>.</div>

            <div class="input-group mb-2">
              <span class="input-group-text"><i class="bi bi-key"></i></span>
              <input id="password" name="password" type="password" class="form-control"
                     placeholder="••••••••" minlength="6" />
              <button class="input-group-text" type="button" id="btnTogglePwd">
                <i class="bi bi-eye"></i>
              </button>
            </div>

            <div class="d-grid gap-2 mt-4">
              <button type="submit" class="btn btn-primary py-2" style="border-radius:16px;">
                <i class="bi bi-check2-circle"></i> Valider
              </button>
              <button type="button" class="btn btn-outline-secondary py-2" id="btnBack" style="border-radius:16px;">
                <i class="bi bi-arrow-left"></i> Retour
              </button>
            </div>
          </div>
        </div>
      </form>

    </div>
  </div>    </div>
  </div>

<script>
  const track = document.getElementById('track');
  const email = document.getElementById('email');
  const password = document.getElementById('password');
  const btnNext = document.getElementById('btnNext');
  const btnBack = document.getElementById('btnBack');
  const btnTogglePwd = document.getElementById('btnTogglePwd');
  const stepBar = document.getElementById('stepBar');
  const stepPill = document.getElementById('stepPill');

  function setStep(n){
    track.style.transform = `translateX(${n === 0 ? '0' : '-50%'})`;
    if (n === 0) {
      password.required = false;
      stepBar.style.width = '50%';
      stepPill.innerHTML = `<i class="bi bi-1-circle" style="color:var(--brand-secondary)"></i><span>Email</span>`;
      setTimeout(()=> email.focus(), 150);
    } else {
      password.required = true;
      stepBar.style.width = '100%';
      stepPill.innerHTML = `<i class="bi bi-2-circle" style="color:var(--brand-primary)"></i><span>Password</span>`;
      setTimeout(()=> password.focus(), 150);
    }
  }

  btnNext.addEventListener('click', () => {
    if (!email.checkValidity()) return document.getElementById('loginForm').reportValidity();
    setStep(1);
  });
  btnBack.addEventListener('click', () => setStep(0));

  email.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); btnNext.click(); }
  });

  btnTogglePwd.addEventListener('click', () => {
    const isPwd = password.type === 'password';
    password.type = isPwd ? 'text' : 'password';
    btnTogglePwd.innerHTML = isPwd ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
  });

  setStep(0);
</script>
</body>
</html>