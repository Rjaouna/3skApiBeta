<?php
require_once __DIR__ . '/config/bootstrap.php';

auth_guard(); // redirige vers login.php si pas connecté
$canShowStock = !empty($_SESSION['user_show_stock']);

$userLabel = $_SESSION['user_name']
  ?? $_SESSION['username']
  ?? $_SESSION['user_email']
  ?? $_SESSION['email']
  ?? '';

$userLabel = trim($userLabel) !== '' ? trim($userLabel) : 'Utilisateur';

?>
<!-- ton HTML catalogue ici -->

<!doctype html>
<html lang="fr">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Catalogue — 3sk</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />

  <!-- PapaParse (CSV) -->
  <script src="https://cdn.jsdelivr.net/npm/papaparse@5.4.1/papaparse.min.js"></script>

  <!-- jsPDF (PDF) -->
  <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>

  <style>
    .user-pill {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      padding: .28rem .6rem;
      border-radius: 999px;
      border: 1px solid rgba(16, 24, 40, .12);
      background: rgba(255, 255, 255, .92);
      box-shadow: 0 10px 22px rgba(16, 24, 40, .06);
      max-width: 260px;
    }

    .user-avatar {
      width: 32px;
      height: 32px;
      border-radius: 999px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--blue-soft);
      color: var(--blue-ink);
      border: 1px solid rgba(13, 110, 253, .18);
      flex: 0 0 auto;
    }

    .user-name {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      max-width: 190px;
      font-weight: 600;
      color: rgba(16, 24, 40, .85);
    }

    /* Base */
    .stock-pill {
      display: inline-flex;
      align-items: center;
      gap: .45rem;
      padding: .32rem .65rem;
      border-radius: 999px;
      border: 1px solid transparent;

      font-size: .85rem;
      font-weight: 800;
      line-height: 1;
      white-space: nowrap;
    }

  
    /* Rouge */
    .stock-pill--red {
      background: #fee2e2;
      /* rouge clair */
      color: #7f1d1d;
      /* rouge foncé */
      border-color: #fecaca;
    }

    /* Orange */
    .stock-pill--orange {
      background: #ffedd5;
      /* orange clair */
      color: #7c2d12;
      /* orange foncé */
      border-color: #fed7aa;
    }

    /* Vert foncé */
    .stock-pill--green {
      background: #dcfce7;
      /* vert clair */
      color: #064e3b;
      /* vert foncé */
      border-color: #bbf7d0;
    }


    /* --------- Base sizing --------- */
    html {
      font-size: 14px;
      -webkit-text-size-adjust: 100%;
    }

    body {
      font-size: .95rem;
    }

    .small,
    .small-note {
      font-size: .82rem !important;
    }

    .badge {
      font-size: .72rem !important;
    }

    .btn {
      font-size: .9rem;
    }

    .form-control,
    .form-select,
    .input-group-text {
      font-size: .9rem;
    }

    /* --------- Theme (Orange & Blue) --------- */
    :root {
      --bg-soft: #f6f7fb;
      --card-border: rgba(16, 24, 40, .10);
      --shadow-soft: 0 10px 30px rgba(16, 24, 40, .08);

      --blue: #0d6efd;
      --blue-soft: rgba(13, 110, 253, .10);
      --blue-ink: #0a58ca;

      --orange: #fd7e14;
      --orange-soft: rgba(253, 126, 20, .12);
      --orange-ink: #b45309;
    }

    body {
      background: var(--bg-soft);
    }

    .app-shell {
      min-height: 100vh;
    }

    .brand-mark {
      width: 200px;
      height: 50px;
      border-radius: 14px;
      background: url(./assets/img/logo_3sk.jpeg) no-repeat center/contain;

    }

    .glass {
      background: rgba(255, 255, 255, .72);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, .55);
    }

    .filter-card {
      border: 1px solid var(--card-border);
      border-radius: 18px;
      box-shadow: var(--shadow-soft);
      background: rgba(255, 255, 255, .82);
      backdrop-filter: blur(10px);
    }

    .sticky-filters {
      position: sticky;
      top: 88px;
    }

    .modal-fullscreen {
      width: 100vw;
      max-width: none;
      height: auto !important;
      margin: 0;
    }

    .catalog-card {
      border: 1px solid var(--card-border);
      border-radius: 18px;
      box-shadow: var(--shadow-soft);
      transition: transform .15s ease, box-shadow .15s ease;
      overflow: hidden;
    }

    .catalog-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 16px 36px rgba(16, 24, 40, .12);
    }

    .badge-soft {
      background: var(--blue-soft);
      color: var(--blue-ink);
      border: 1px solid rgba(13, 110, 253, .18);
    }

    .badge-family {
      background: var(--orange-soft);
      color: var(--orange-ink);
      border: 1px solid rgba(253, 126, 20, .22);
    }

    .badge-stock-ok {
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      padding: .28rem .65rem;
      border-radius: 999px;

      font-weight: 800;
      font-size: .72rem;
      line-height: 1;
      text-transform: uppercase;
      letter-spacing: .04em;

      color: #fff;

      /* ✅ look très différent : dégradé + rayures */
      background:
        repeating-linear-gradient(45deg,
          rgba(255, 255, 255, .18) 0 6px,
          rgba(255, 255, 255, 0) 6px 12px),
        linear-gradient(180deg, #22c55e 0%, #15803d 100%);

      border: 1px solid rgba(0, 0, 0, .10);
      box-shadow: 0 10px 20px rgba(21, 128, 61, .25);
    }

    .badge-stock-no {
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      padding: .28rem .65rem;
      border-radius: 999px;

      font-weight: 800;
      font-size: .72rem;
      line-height: 1;
      text-transform: uppercase;
      letter-spacing: .04em;

      color: #fff;

      /* ✅ look très différent : dégradé + rayures */
      background:
        repeating-linear-gradient(45deg,
          rgba(255, 255, 255, .22) 0 6px,
          rgba(255, 255, 255, 0) 6px 12px),
        linear-gradient(180deg, #ef4444 0%, #b91c1c 100%);

      border: 1px solid rgba(0, 0, 0, .10);
      box-shadow: 0 10px 20px rgba(185, 28, 28, .28);
    }

    .text-orange {
      color: var(--orange) !important;
    }

    .btn-orange {
      --bs-btn-color: #fff;
      --bs-btn-bg: var(--orange);
      --bs-btn-border-color: var(--orange);
      --bs-btn-hover-bg: #e76f0f;
      --bs-btn-hover-border-color: #e76f0f;
      --bs-btn-active-bg: #d8630e;
      --bs-btn-active-border-color: #d8630e;
    }

    .btn-outline-orange {
      --bs-btn-color: var(--orange);
      --bs-btn-border-color: var(--orange);
      --bs-btn-hover-color: #fff;
      --bs-btn-hover-bg: var(--orange);
      --bs-btn-hover-border-color: var(--orange);
      --bs-btn-active-bg: #e76f0f;
      --bs-btn-active-border-color: #e76f0f;
    }

    .skeleton {
      border-radius: 18px;
      background: linear-gradient(90deg, rgba(255, 255, 255, .6), rgba(255, 255, 255, .95), rgba(255, 255, 255, .6));
      background-size: 200% 100%;
      animation: sk 1.2s infinite;
      border: 1px solid var(--card-border);
      box-shadow: var(--shadow-soft);
      height: 190px;
    }

    @keyframes sk {
      0% {
        background-position: 200% 0
      }

      100% {
        background-position: -200% 0
      }
    }

    .chip {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      padding: .35rem .6rem;
      border-radius: 999px;
      border: 1px solid rgba(17, 24, 39, .12);
      background: rgba(255, 255, 255, .75);
      box-shadow: 0 8px 18px rgba(16, 24, 40, .06);
      font-size: .875rem;
      margin: .2rem .2rem 0 0;
    }

    .chip button {
      border: 0;
      background: transparent;
      line-height: 1;
      padding: 0;
      color: #6b7280;
    }

    .muted {
      color: #6b7280;
    }

    .mono {
      font-variant-numeric: tabular-nums;
      font-feature-settings: "tnum" 1;
    }

    .small-note {
      font-size: .875rem;
      color: #6b7280;
    }

    .card-title-trunc {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;

      line-height: 1.2;
    }

    .offcanvas {
      --bs-offcanvas-width: 380px;
    }

    .thumb {
      border-radius: 16px;
      overflow: hidden;
      border: 1px solid var(--card-border);
      background: linear-gradient(135deg, var(--blue-soft), rgba(17, 24, 39, .06));
      position: relative;
    }

    .thumb img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      /* ✅ affiche l’image entière */
      object-position: center;
      /* ✅ centrée */
      display: block;
      background: #fff;
      /* optionnel : fond blanc si bandes */
    }

    .thumb-placeholder {
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      gap: .35rem;
      color: #6b7280;
      font-size: .9rem;
    }

    .thumb-placeholder i {
      font-size: 1.35rem;
      opacity: .85;
    }

    /* Topbar responsive */
    .topbar-actions {
      flex-wrap: wrap;
    }

    .topbar-search {
      min-width: 260px;
      flex: 1 1 260px;
    }

    @media (max-width: 991.98px) {
      .topbar-search {
        flex: 1 1 100%;
        min-width: 100%;
        margin-top: .5rem;
      }
    }

    /* Cart */
    .cart-item {
      border: 1px solid rgba(16, 24, 40, .10);
      border-radius: 16px;
      box-shadow: 0 10px 22px rgba(16, 24, 40, .06);
      background: rgba(255, 255, 255, .92);
      overflow: hidden;
    }

    .cart-thumb {
      width: 82px;
      height: 62px;
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid rgba(16, 24, 40, .10);
      background: linear-gradient(135deg, var(--blue-soft), rgba(17, 24, 39, .06));
      flex: 0 0 auto;
    }

    .cart-thumb img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      /* ✅ image entière */
      object-position: center;
      display: block;
      background: #fff;
    }

    .qty-btn {
      width: 34px;
      height: 34px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 10px;
    }

    .qty-input {
      width: 64px;
      text-align: center;
      border-radius: 10px;
    }

    /* ===== Photo Viewer Modal (Orange & Bleu) ===== */
    .thumb {
      cursor: zoom-in;
    }

    .thumb .pv-hint {
      position: absolute;
      right: 10px;
      bottom: 10px;
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      padding: .35rem .55rem;
      border-radius: 999px;

      color: rgba(16, 24, 40, .85);
      font-size: .85rem;
      opacity: 0;
      transform: translateY(4px);
      transition: opacity .15s ease, transform .15s ease;
      pointer-events: none;
    }

    .thumb:hover .pv-hint {
      opacity: 1;
      transform: translateY(0);
    }

    @media (hover:none) {
      .thumb .pv-hint {
        opacity: 1;
        transform: none;
      }
    }

    .photo-modal .modal-content {
      border: 0;
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 24px 60px rgba(16, 24, 40, .25);
    }

    .photo-modal .modal-header {
      border: 0;
      color: #fff;
      background: linear-gradient(297deg, rgba(13, 110, 253, .92), rgba(253, 126, 20, .90));
    }

    .photo-modal .modal-header .btn,
    .photo-modal .modal-header .btn-outline-light {
      border-color: rgba(255, 255, 255, .28) !important;
    }

    .photo-modal .modal-body {
      background: #0b1220;
      padding: 0;
    }

    .pv-stage {
      position: relative;
      height: min(78vh, 720px);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: auto;
      padding: 12px;
      background:
        radial-gradient(circle at 18% 18%, rgba(13, 110, 253, .18), transparent 46%),
        radial-gradient(circle at 82% 22%, rgba(253, 126, 20, .18), transparent 52%),
        radial-gradient(circle at 50% 120%, rgba(255, 255, 255, .06), transparent 60%),
        #0b1220;
    }

    #pvImg {
      display: block;
      border-radius: 16px;
      background: #fff;
      border: 1px solid rgba(255, 255, 255, .14);
      box-shadow: 0 20px 46px rgba(0, 0, 0, .45);
      max-width: calc(100% - 12px);
      max-height: calc(100% - 12px);
      object-fit: contain;
    }

    .pv-mode-100 #pvImg {
      max-width: none;
      max-height: none;
    }

    #pvSpinner {
      position: absolute;
      inset: auto;
    }

    .pv-footer {
      background: rgba(255, 255, 255, .06);
      border-top: 1px solid rgba(255, 255, 255, .10);
      color: rgba(255, 255, 255, .85);
    }

    .pv-meta {
      color: rgba(255, 255, 255, .72);
      font-size: .9rem;
    }
  </style>
</head>

<body>
  <div class="app-shell">
    <!-- Topbar -->
    <nav class="navbar navbar-expand-lg glass border-bottom" style="position:sticky;top:0;z-index:1030;">
      <div class="container-fluid py-2 gap-3">
        <div class="d-flex align-items-center gap-3">
          <a href="https://catalogue.3skpartsauto.com/">
            <div class="brand-mark"></div>
          </a>
          <div>
            <div class="fw-semibold" style="line-height:1.1"></div>


          </div>
        </div>

        <div class="topbar-actions d-flex align-items-center gap-2 flex-wrap flex-grow-1">

          <!-- ✅ Boutons (restent à droite, avant le user) -->
          <div class="topbar-buttons d-flex align-items-center gap-2 flex-wrap order-1 order-lg-2">

            <!-- PANIER -->
            <button class="btn btn-outline-primary d-flex" type="button"
              data-bs-toggle="offcanvas" data-bs-target="#cartCanvas" aria-controls="cartCanvas">
              <i class="bi bi-cart3"></i>
              <span class="d-none d-md-inline ms-2">Panier</span>
              <span id="cartCount" class="badge text-bg-primary ms-1">0</span>
            </button>

            <!-- PDF -->
            <button class="btn btn-outline-orange d-flex" type="button"
              data-bs-toggle="offcanvas" data-bs-target="#pdfCanvas" aria-controls="pdfCanvas">
              <i class="bi bi-filetype-pdf"></i>
              <span class="d-none d-md-inline ms-2">PDF</span>
              <span id="pdfCount" class="badge" style="background:var(--orange);color:#fff;margin-left:.25rem;">0</span>
            </button>

            <!-- Ajout rapide PDF -->
            <button id="btnAddBulk" class="btn btn-outline-primary d-flex" type="button" title="Ajout rapide PDF">
              <i class="bi bi-lightning-charge"></i>
            </button>

            <!-- Filtres mobile -->
            <button class="btn btn-outline-primary d-lg-none" type="button"
              data-bs-toggle="offcanvas" data-bs-target="#filtersCanvas" aria-controls="filtersCanvas">
              <i class="bi bi-sliders2"></i>
            </button>
          </div>

          <!-- ✅ User + Logout (collés tout à droite en face du logo) -->
          <div class="d-flex align-items-center gap-2 ms-auto order-2 order-lg-3">
            <div class="user-pill" title="<?= htmlspecialchars($userLabel, ENT_QUOTES) ?>">
              <span class="user-avatar">
                <i class="bi bi-person-fill"></i>
              </span>
              <span class="user-name d-none d-md-inline">
                <?= htmlspecialchars($userLabel) ?>
              </span>
            </div>

            <a href="/logout.php" class="btn btn-danger d-flex" type="button" title="Se déconnecter">
              <i class="bi bi-box-arrow-right fs-5"></i>
            </a>
          </div>

          <!-- ✅ Recherche (entre logo et boutons sur desktop, en dessous sur tablette/mobile) -->
          <div class="input-group topbar-search order-3 order-lg-1 flex-grow-1 me-lg-2">
            <span class="input-group-text bg-white border-0"><i class="bi bi-search"></i></span>
            <input id="qTop" type="search" class="form-control border-1 border-secondary" placeholder="Ex : Optique Audi A3... ">
            <button id="btnClearQ" class="btn btn-orange" type="button">Effacer</button>
          </div>

        </div>

      </div>
    </nav>

    <main class="container-fluid py-4">
      <div class="row g-4">
        <!-- Desktop filters -->
        <aside class="col-lg-2 d-none d-lg-block">
          <div id="filtersDesktopMount">
            <div class="filter-card p-3 sticky-filters" id="filtersCard">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-semibold"><i class="bi bi-sliders2"></i> Filtres</div>
                <button id="btnReset" class="btn btn-sm btn-outline-secondary" type="button">Réinitialiser</button>
              </div>

              <!-- Source -->
              <div class="mb-3">
                <label class="form-label fw-semibold">Source CSV</label>
                <div class="input-group">
                  <input id="csvUrl" class="form-control" type="url" />
                  <button id="btnLoad" class="btn btn-primary" type="button" title="Charger">
                    <i class="bi bi-cloud-download"></i>
                  </button>
                </div>
                <div class="small-note mt-1">
                  Si vous ouvrez le fichier en <span class="mono">file://</span>, utilisez plutôt un petit serveur (ou l’upload ci-dessous).
                </div>
                <input id="csvFile" class="form-control mt-2" type="file" accept=".csv,text/csv" />
              </div>

              <!-- Price mode -->
              <div class="mb-3">
                <label class="form-label fw-semibold">Affichage prix</label>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="priceMode" id="pmInc" value="inc" checked>
                    <label class="form-check-label" for="pmInc">TTC</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="priceMode" id="pmExc" value="exc">
                    <label class="form-check-label" for="pmExc">HT</label>
                  </div>

                  <div class="form-check form-switch ms-auto">
                    <input class="form-check-input" type="checkbox" id="hidePricesToggle">
                    <label class="form-check-label" for="hidePricesToggle">Cacher les prix</label>
                  </div>
                </div>
              </div>

              <!-- Stock mode -->
              <div class="mb-3">
                <label class="form-label fw-semibold">Affichage stock</label>
                <div class="d-flex gap-2 flex-wrap">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="stockMode" id="smAll" value="all" checked>
                    <label class="form-check-label" for="smAll">Tous</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="stockMode" id="smIn" value="in">
                    <label class="form-check-label" for="smIn">En stock</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="stockMode" id="smOut" value="out">
                    <label class="form-check-label" for="smOut">Rupture</label>
                  </div>
                </div>
              </div>

              <!-- Facets -->
              <div class="mb-3">
                <label class="form-label fw-semibold">Marque</label>
                <select id="fBrand" class="form-select">
                  <option value="">Toutes</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Famille</label>
                <select id="fFamily" class="form-select">
                  <option value="">Toutes</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Véhicule</label>
                <select id="fVehicle" class="form-select">
                  <option value="">Tous</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Année</label>
                <div class="row g-2">
                  <div class="col-6">
                    <input id="fYearMin" class="form-control" type="number" placeholder="Min" />
                  </div>
                  <div class="col-6">
                    <input id="fYearMax" class="form-control" type="number" placeholder="Max" />
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Prix</label>
                <div class="row g-2">
                  <div class="col-6">
                    <input id="fPriceMin" class="form-control" type="number" placeholder="Min" />
                  </div>
                  <div class="col-6">
                    <input id="fPriceMax" class="form-control" type="number" placeholder="Max" />
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Tri</label>
                <select id="fSort" class="form-select">
                  <option value="relevance">Pertinence</option>
                  <option value="priceAsc">Prix (croissant)</option>
                  <option value="priceDesc">Prix (décroissant)</option>
                  <option value="stockDesc">Stock (décroissant)</option>
                  <option value="az">Nom (A → Z)</option>
                </select>
              </div>

              <div class="small-note">
                Astuce : la recherche scanne aussi UID, famille, véhicule, marque…
              </div>
            </div>
          </div>
        </aside>

        <!-- Main -->
        <section class="col-lg-10">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
              <div class="fw-semibold">
                Résultats : <span id="count">0</span>
                <span class="muted fw-normal">/ <span id="total">0</span></span>
              </div>
              <div id="hint" class="small-note"></div>
              <div class="small-note d-flex align-items-center gap-2 flex-wrap">
                <span>Filtrage, panier, PDF catalogue</span>
                <span class="vr"></span>

                <span class="d-inline-flex align-items-center gap-2">
                  <i class="bi bi-clock-history text-orange"></i>
                  <span class="muted">Dern. MAJ :</span>
                  <span id="csvLastUpdate" class="last-update-pill mono fw-bold">—</span>
                </span>
              </div>

            </div>

            <div class="d-flex align-items-center gap-2">
              <button id="btnCopyUrl" class="btn btn-outline-primary btn-sm" type="button">
                <i class="bi bi-link-45deg"></i> Copier paramètres
              </button>
              <button id="btnClearAll" class="btn btn-orange btn-sm" type="button">
                Tout effacer
              </button>
            </div>
          </div>

          <div id="chips" class="mb-3"></div>

          <!-- Grid -->
          <div id="grid" class="row g-3"></div>

          <div id="empty" class="text-center py-5 d-none">
            <div class="display-6">Aucun produit</div>
            <div class="muted">Essayez d’assouplir vos filtres.</div>
          </div>
        </section>
      </div>
    </main>
  </div>

  <!-- Offcanvas filters (mobile) -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="filtersCanvas">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title"><i class="bi bi-sliders2"></i> Filtres</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
      <div id="filtersClone"></div>
    </div>
  </div>

  <!-- Offcanvas PANIER -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="cartCanvas">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title"><i class="bi bi-cart3"></i> Panier</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">
      <div id="cartEmpty" class="text-center muted py-4">
        Votre panier est vide.
        <div class="small-note mt-2">Ajoutez des articles depuis le catalogue.</div>
      </div>
      <div id="cartList" class="d-grid gap-2"></div>
    </div>

    <div class="p-3 border-top">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <div class="fw-semibold">Total</div>
        <div class="fw-semibold mono" id="cartTotal">—</div>
      </div>
      <div class="small-note mb-3" id="cartTotalHint"></div>

      <div class="d-flex gap-2">
        <button id="btnCartClear" class="btn btn-outline-secondary flex-fill" type="button">Vider</button>
        <button id="btnCartCheckout" class="btn btn-primary flex-fill" type="button">Valider commande</button>
      </div>
    </div>
  </div>

  <!-- Offcanvas PDF list -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="pdfCanvas">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title"><i class="bi bi-filetype-pdf"></i> Liste PDF</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="offcanvas-body">
      <div id="pdfListEmpty" class="text-center muted py-4">
        Aucun article sélectionné.
      </div>
      <div id="pdfList" class="list-group"></div>
    </div>

    <div class="p-3 border-top">
      <div class="d-flex gap-2">
        <button id="btnPdfClear" class="btn btn-outline-secondary flex-fill" type="button">Vider</button>
        <button id="btnPdfGenerate" class="btn btn-orange flex-fill" type="button">Générer PDF</button>
      </div>
      <div class="small-note mt-2">
        Le PDF est généré sous forme de catalogue (images + fiches produits).
      </div>
    </div>
  </div>

  <!-- Toast -->
  <div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header">
        <strong class="me-auto">Info</strong>
        <small>Maintenant</small>
        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
      </div>
      <div id="toastBody" class="toast-body"></div>
    </div>
  </div>

  <!-- Modal fullscreen : Ajout rapide PDF -->
  <div class="modal fade" id="bulkAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
      <div class="modal-content" style="border:0;border-radius:0;background:var(--bg-soft);">
        <div class="px-3 px-md-4 py-3"
          style="background:linear-gradient(297deg, rgba(13,110,253,.92), rgba(253,126,20,.90));color:#fff;">
          <div class="d-flex align-items-start align-items-md-center justify-content-between gap-3 flex-wrap">
            <div>
              <span class="badge rounded-pill px-3 py-2"
                style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.20);">
                <i class="bi bi-lightning-charge"></i> Ajout rapide PDF
              </span>
              <div class="mt-2 fw-semibold" style="font-size:1.35rem;line-height:1.2;">
                Ajoutez en masse par Marque, Véhicule ou Famille
              </div>
              <div class="mt-1" style="color:rgba(255,255,255,.80);font-size:.95rem;">
                Sélectionnez une entrée : tous les articles correspondants seront ajoutés. Doublons ignorés.
              </div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <div class="text-nowrap">
                <span class="small">Liste PDF :</span>
                <span class="badge" id="bulkPdfCount2" style="background:rgba(255,255,255,.20);border:1px solid rgba(255,255,255,.22);">0</span>
              </div>
              <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">
                <i class="bi bi-x-lg"></i> Fermer
              </button>
            </div>
          </div>

          <div class="mt-3">
            <ul class="nav nav-pills gap-2" id="bulkTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active text-white " data-bs-toggle="pill" data-bs-target="#pane-brand" type="button" role="tab">
                  <i class="bi bi-tags"></i> Marques
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link text-white" data-bs-toggle="pill" data-bs-target="#pane-vehicle" type="button" role="tab">
                  <i class="bi bi-truck"></i> Véhicules
                </button>
              </li>
              <li class="nav-item text-white" role="presentation">
                <button class="nav-link text-white" data-bs-toggle="pill" data-bs-target="#pane-family" type="button" role="tab">
                  <i class="bi bi-grid"></i> Familles
                </button>
              </li>
            </ul>
          </div>
        </div>

        <div class="px-3 px-md-4 py-4">
          <div class="row g-4">


            <div class="col-12 col-lg-12">
              <div class="filter-card p-3 p-md-4">
                <div class="tab-content">
                  <div class="tab-pane fade show active" id="pane-brand" role="tabpanel">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                      <div class="fw-semibold" style="font-size:1.05rem;"><i class="bi bi-tags"></i> Marques</div>
                      <div class="input-group" style="max-width: 360px;">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input id="bulkSearchBrand" class="form-control" placeholder="Rechercher une marque…" />
                      </div>
                    </div>
                    <div id="bulkListBrand" class="list-group"></div>
                    <div id="bulkEmptyBrand" class="small muted mt-2 d-none">Aucune marque.</div>
                  </div>

                  <div class="tab-pane fade" id="pane-vehicle" role="tabpanel">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                      <div class="fw-semibold" style="font-size:1.05rem;"><i class="bi bi-truck"></i> Véhicules</div>
                      <div class="input-group" style="max-width: 360px;">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input id="bulkSearchVehicle" class="form-control" placeholder="Rechercher un véhicule…" />
                      </div>
                    </div>
                    <div id="bulkListVehicle" class="list-group"></div>
                    <div id="bulkEmptyVehicle" class="small muted mt-2 d-none">Aucun véhicule.</div>
                  </div>

                  <div class="tab-pane fade" id="pane-family" role="tabpanel">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                      <div class="fw-semibold" style="font-size:1.05rem;"><i class="bi bi-grid"></i> Familles</div>
                      <div class="input-group" style="max-width: 360px;">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input id="bulkSearchFamily" class="form-control" placeholder="Rechercher une famille…" />
                      </div>
                    </div>
                    <div id="bulkListFamily" class="list-group"></div>
                    <div id="bulkEmptyFamily" class="small muted mt-2 d-none">Aucune famille.</div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Wizard commande -->
  <div class="modal fade" id="orderWizardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content" style="border-radius:18px; overflow:hidden;">
        <div class="modal-header">
          <div class="w-100">
            <div class="d-flex align-items-center justify-content-between gap-2">
              <div class="fw-semibold"><i class="bi bi-check2-square"></i> Validation commande</div>
              <span class="badge rounded-pill px-3 py-2"
                style="background:rgba(13,110,253,.10);border:1px solid rgba(13,110,253,.18);color:var(--blue-ink);"
                id="wizStepPill">Étape 1/5</span>
            </div>
            <div class="mt-2 fw-bold" id="wizStepTitle" style="font-size:1.1rem;">Nom du commercial</div>
            <div class="small muted mt-1" id="wizStepDesc"></div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="wiz-step" data-step="0">
            <label class="form-label fw-semibold">Nom du commercial <span class="text-danger">*</span></label>
            <input class="form-control" id="wizSales" placeholder="Ex: Ahmed…" />
            <div class="invalid-feedback">Ce champ est obligatoire.</div>
          </div>

          <div class="wiz-step d-none" data-step="1">
            <label class="form-label fw-semibold">Nom du client <span class="text-danger">*</span></label>
            <input class="form-control" id="wizClient" placeholder="Ex: Société / Client…" />
            <div class="invalid-feedback">Ce champ est obligatoire.</div>
          </div>

          <div class="wiz-step d-none" data-step="2">
            <label class="form-label fw-semibold">Adresse du client</label>
            <textarea class="form-control" id="wizAddress" rows="3" placeholder="Adresse (optionnel)"></textarea>
            <div class="small-note mt-2">Optionnel — tu peux laisser vide.</div>
          </div>

          <div class="wiz-step d-none" data-step="3">
            <label class="form-label fw-semibold">Numéro de téléphone</label>
            <input class="form-control" id="wizPhone" placeholder="Ex: +212 6…" />
            <div class="small-note mt-2">Optionnel — tu peux laisser vide.</div>
          </div>

          <div class="wiz-step d-none" data-step="4">
            <div class="fw-semibold mb-2">Récapitulatif</div>
            <div class="small muted mb-3">Vérifie les infos avant d’envoyer.</div>

            <div class="border rounded-3 p-3 bg-light">
              <div class="d-flex justify-content-between">
                <div class="muted">Commercial</div>
                <div class="fw-semibold" id="wizRecSales">—</div>
              </div>
              <div class="d-flex justify-content-between mt-2">
                <div class="muted">Client</div>
                <div class="fw-semibold" id="wizRecClient">—</div>
              </div>
              <div class="d-flex justify-content-between mt-2">
                <div class="muted">Adresse</div>
                <div class="fw-semibold text-end" id="wizRecAddress">—</div>
              </div>
              <div class="d-flex justify-content-between mt-2">
                <div class="muted">Téléphone</div>
                <div class="fw-semibold" id="wizRecPhone">—</div>
              </div>

              <hr class="my-3" />

              <div class="d-flex justify-content-between">
                <div class="muted">Total</div>
                <div class="fw-semibold mono" id="wizRecTotal">—</div>
              </div>
              <div class="small-note mt-2">
                L’envoi ouvrira votre messagerie avec un email déjà rempli vers <b id="orderToLabel">contact@3skpartsauto.ma</b>.
              </div>
            </div>
          </div>

          <div class="small-note text-danger mt-3 d-none" id="wizCartEmptyWarn">
            Votre panier est vide. Ajoutez au moins un article avant de valider.
          </div>
        </div>

        <div class="modal-footer d-flex justify-content-between">
          <button type="button" class="btn btn-outline-secondary" id="wizBack">
            <i class="bi bi-arrow-left"></i> Retour
          </button>

          <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" id="wizNext">
              Suivant <i class="bi bi-arrow-right"></i>
            </button>
            <button type="button" class="btn btn-orange d-none" id="wizSend">
              <i class="bi bi-send"></i> Envoyer
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    /***********************
     * CONFIG (LOCAL)
     * -> Ton CSV est dans ./export_images
     * IMPORTANT: adapte le nom exact ci-dessous.
     ***********************/
    const DEFAULT_CSV_URL = "./export_images/items.csv"; // <-- change ici si ton fichier a un autre nom

    // Images (robuste): on tente plusieurs noms/extends dans ./export_images
    // - UniqueId.png / UniqueId.jpg / UniqueId.jpeg
    // - DesComClear (slug) .png/.jpg/.jpeg
    const IMAGE_BASE_PATH = "./export_images/";
    const IMAGE_EXTS = [".png", ".jpg", ".jpeg", ".webp"];

    const CURRENCY = "MAD";
    const LOCALE = "fr-FR";

    const PDF_STORAGE_KEY = "catalog_pdf_selection_orangeblue_v1";
    const CART_STORAGE_KEY = "catalog_cart_orangeblue_v1";
    const HIDE_PRICES_KEY = "catalog_hide_prices_orangeblue_v1";

    const ORDER_EMAIL_TO = "contact@3skpartsauto.ma"; // change si besoin
    const PDF_COVER_LOGO_URL = "./assets/img/logo_3sk.jpeg"; // optionnel (si le fichier existe)

    const PAGE_SIZE = 100;
    const CAN_SHOW_STOCK = <?= $canShowStock ? 'true' : 'false' ?>;



    /***********************
     * DOM
     ***********************/
    const el = (id) => document.getElementById(id);

    const grid = el("grid");
    const chips = el("chips");
    const empty = el("empty");
    const count = el("count");
    const total = el("total");
    const hint = el("hint");

    // Filters
    const qTop = el("qTop");
    const btnClearQ = el("btnClearQ");
    const csvUrl = el("csvUrl");
    const btnLoad = el("btnLoad");
    const csvFile = el("csvFile");

    const fBrand = el("fBrand");
    const fFamily = el("fFamily");
    const fVehicle = el("fVehicle");
    const fYearMin = el("fYearMin");
    const fYearMax = el("fYearMax");
    const fPriceMin = el("fPriceMin");
    const fPriceMax = el("fPriceMax");
    const fSort = el("fSort");
    const btnReset = el("btnReset");
    const btnClearAll = el("btnClearAll");
    const btnCopyUrl = el("btnCopyUrl");

    // Toggles
    const hidePricesToggle = el("hidePricesToggle");

    // PDF
    const pdfCount = el("pdfCount");
    const pdfListEl = el("pdfList");
    const pdfListEmpty = el("pdfListEmpty");
    const btnPdfClear = el("btnPdfClear");
    const btnPdfGenerate = el("btnPdfGenerate");

    // Cart
    const cartCountEl = el("cartCount");
    const cartListEl = el("cartList");
    const cartEmptyEl = el("cartEmpty");
    const cartTotalEl = el("cartTotal");
    const cartTotalHintEl = el("cartTotalHint");
    const btnCartClear = el("btnCartClear");
    const btnCartCheckout = el("btnCartCheckout");

    // Bulk modal
    const btnAddBulk = el("btnAddBulk");
    const bulkAddModalEl = el("bulkAddModal");
    const bulkPdfCount2 = el("bulkPdfCount2");

    const bulkSearchBrand = el("bulkSearchBrand");
    const bulkSearchVehicle = el("bulkSearchVehicle");
    const bulkSearchFamily = el("bulkSearchFamily");

    const bulkListBrand = el("bulkListBrand");
    const bulkListVehicle = el("bulkListVehicle");
    const bulkListFamily = el("bulkListFamily");

    const bulkEmptyBrand = el("bulkEmptyBrand");
    const bulkEmptyVehicle = el("bulkEmptyVehicle");
    const bulkEmptyFamily = el("bulkEmptyFamily");

    // Wizard
    const orderWizardEl = el("orderWizardModal");
    const wizStepPill = el("wizStepPill");
    const wizStepTitle = el("wizStepTitle");
    const wizStepDesc = el("wizStepDesc");
    const wizBack = el("wizBack");
    const wizNext = el("wizNext");
    const wizSend = el("wizSend");
    const wizCartEmptyWarn = el("wizCartEmptyWarn");
    let CSV_FILE_LAST_MODIFIED = null; // Date réelle du fichier CSV (si dispo)


    const wizSales = el("wizSales");
    const wizClient = el("wizClient");
    const wizAddress = el("wizAddress");
    const wizPhone = el("wizPhone");

    const wizRecSales = el("wizRecSales");
    const wizRecClient = el("wizRecClient");
    const wizRecAddress = el("wizRecAddress");
    const wizRecPhone = el("wizRecPhone");
    const wizRecTotal = el("wizRecTotal");

    const orderToLabel = el("orderToLabel");

    /***********************
     * STATE
     ***********************/
    let allRows = [];
    let filteredRows = [];
    let renderedCount = 0;
    let infiniteObserver = null;

    let facets = {
      brands: [],
      families: [],
      vehicles: [],
      years: []
    };
    let priceMode = "inc"; // inc TTC, exc HT
    let hidePrices = false;

    // Selections
    const pdfSelection = new Map(); // key -> row
    const cart = new Map(); // key -> { row, qty }
    let allRowsByKey = new Map(); // key -> row

    // UI drafts (qty per card)
    const cardQtyDraft = new Map(); // key -> qty

    // Bulk counts
    let facetCounts = {
      brand: new Map(),
      vehicle: new Map(),
      family: new Map(),
    };

    // Wizard state
    let wizStep = 0;

    /***********************
     * UTIL
     ***********************/
    function toast(msg) {
      el("toastBody").textContent = msg;
      const t = bootstrap.Toast.getOrCreateInstance(el("toast"), {
        delay: 2200
      });
      t.show();
    }

    function stripQuotes(s) {
      if (s === null || s === undefined) return "";
      s = String(s).trim();
      if (s.startsWith('"') && s.endsWith('"')) s = s.slice(1, -1);
      return s.trim();
    }

    function toNumber(v) {
      if (v === null || v === undefined) return NaN;
      let s = String(v).trim();
      if (!s) return NaN;
      s = s.replace(/\s/g, "").replace(",", ".");
      s = s.replace(/[^0-9.\-]/g, "");
      const n = Number(s);
      return Number.isFinite(n) ? n : NaN;
    }

    function fmtMoney(n) {
      if (!Number.isFinite(n)) return "—";
      try {
        return new Intl.NumberFormat(LOCALE, {
          style: "currency",
          currency: CURRENCY,
          maximumFractionDigits: 2
        }).format(n);
      } catch {
        return n.toFixed(2) + " " + CURRENCY;
      }
    }


    function stockPillClass(qty) {
      const n = toNumber(qty);
      if (!Number.isFinite(n)) return ""; // inconnu => pas de couleur
      if (n < 5) return "stock-pill--red";
      if (n < 15) return "stock-pill--orange";
      return "stock-pill--green";
    }

    function parseSysDate(s) {
      s = String(s ?? "").trim();
      if (!s) return null;
      const d = new Date(s); // le format "Thu Jan 22 2026..." est parsé nativement
      return isNaN(d.getTime()) ? null : d;
    }

    function fmtDateTime(d) {
      if (!d) return "—";

      // ✅ +1 heure
      const d2 = new Date(d.getTime() + 60 * 60 * 1000);

      return new Intl.DateTimeFormat(LOCALE, {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
      }).format(d2);
    }


    function getLastUpdate(row) {
      // priorité: FtpFinishedAt
      const ftp = parseFtpFinishedAt(field(row, "FtpFinishedAt"));
      if (ftp) return {
        date: ftp,
        source: "ftp"
      };

      // fallback: sysModifiedDate
      const sys = parseSysDate(field(row, "sysModifiedDate"));
      if (sys) return {
        date: sys,
        source: "sys"
      };

      return {
        date: null,
        source: ""
      };
    }
    // Décalage demandé à l'affichage (heure + 1)
    const CSV_TIME_ADD_HOURS = 0;

    function parseFtpFinishedAt(s) {
      s = String(s ?? "").trim();
      if (!s) return null;

      // attendu: "2026-01-30 20:21:23"
      const iso = s.replace(" ", "T"); // -> "2026-01-30T20:21:23"
      const d = new Date(iso); // interprété en local (sans timezone)
      return isNaN(d.getTime()) ? null : d;
    }

    function parseSysDate(s) {
      s = String(s ?? "").trim();
      if (!s) return null;

      // ex: "Thu Jan 22 2026 13:01:28 GMT+0100 (UTC+01:00)"
      const d = new Date(s);
      return isNaN(d.getTime()) ? null : d;
    }

    function fmtDateTime(d) {
      if (!d) return "—";

      // ✅ +1 heure uniquement à l'affichage
      const d2 = new Date(d.getTime() + CSV_TIME_ADD_HOURS * 60 * 60 * 1000);

      return new Intl.DateTimeFormat(LOCALE, {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
      }).format(d2);
    }

    function parseFtpFinishedAt(s) {
      s = String(s ?? "").trim();
      if (!s) return null;

      // "2026-01-30 20:21:23" -> parse safe (sans surprises timezone)
      const m = s.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/);
      if (!m) return null;
      const [_, Y, Mo, D, h, mi, se] = m;
      const d = new Date(Number(Y), Number(Mo) - 1, Number(D), Number(h), Number(mi), Number(se || 0));
      return isNaN(d.getTime()) ? null : d;
    }

    function parseSysDate(s) {
      s = String(s ?? "").trim();
      if (!s) return null;
      const d = new Date(s);
      return isNaN(d.getTime()) ? null : d;
    }

    function mostFrequentDate(rows, colName, parser) {
      const counts = new Map(); // rawString -> count
      for (const r of rows) {
        const raw = field(r, colName);
        if (!raw) continue;
        counts.set(raw, (counts.get(raw) || 0) + 1);
      }
      if (!counts.size) return null;

      // prend la plus fréquente; en cas d’égalité, prend la plus récente
      let bestRaw = null;
      let bestCount = -1;
      let bestDate = null;

      for (const [raw, c] of counts.entries()) {
        const d = parser(raw);
        if (!d) continue;

        if (c > bestCount) {
          bestCount = c;
          bestRaw = raw;
          bestDate = d;
        } else if (c === bestCount && bestDate && d > bestDate) {
          bestRaw = raw;
          bestDate = d;
        }
      }
      return bestDate;
    }

    function computeCsvLastUpdate(rows) {
      const ftp = mostFrequentDate(rows, "FtpFinishedAt", parseFtpFinishedAt);
      if (ftp) return {
        date: ftp,
        source: "ftp"
      };

      const sys = mostFrequentDate(rows, "sysModifiedDate", parseSysDate);
      if (sys) return {
        date: sys,
        source: "sys"
      };

      return {
        date: null,
        source: ""
      };
    }


    function updateCsvLastUpdateUi() {
      const label = document.getElementById("csvLastUpdate");
      if (!label) return;

      // ✅ priorité: date du fichier CSV
      if (CSV_FILE_LAST_MODIFIED && !isNaN(CSV_FILE_LAST_MODIFIED.getTime())) {
        label.textContent = fmtDateTime(CSV_FILE_LAST_MODIFIED);
        label.title = "Basé sur la date du fichier CSV (Last-Modified / lastModified)";
        return;
      }

      // fallback: calcul à partir des colonnes (ton ancien comportement)
      const info = computeCsvLastUpdate(allRows || []);
      if (!info.date) {
        label.textContent = "—";
        label.title = "Date de mise à jour non disponible";
        return;
      }

      label.textContent = fmtDateTime(info.date);
      label.title = (info.source === "ftp") ?
        "Basé sur FtpFinishedAt" :
        "Basé sur sysModifiedDate (fallback)";
    }




    function safeText(v) {
      return (v ?? "").toString();
    }

    function debounce(fn, ms = 250) {
      let t;
      return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), ms);
      };
    }

    function uniqueSorted(arr) {
      return Array.from(new Set(arr.filter(Boolean))).sort((a, b) => String(a).localeCompare(String(b), "fr"));
    }

    function escHtml(s) {
      return String(s ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
    }

    // Accès champ robuste (supporte variations xx_Vehicule / xx_VEHICULE, etc.)
    function field(r, ...names) {
      for (const n of names) {
        const v = r?.[n];
        if (v !== undefined && v !== null && String(v).trim() !== "") return String(v).trim();
      }
      return "";
    }

    function displayId(r) {
      // Affichage: si "Id" existe on l'utilise, sinon on retombe sur UniqueId
      return field(r, "Id", "ID", "id") || field(r, "UniqueId");
    }


    function getPrice(row) {
      const key = (priceMode === "inc") ? "SalePriceVatIncluded" : "SalePriceVatExcluded";
      return toNumber(field(row, key));
    }

    function getStock(row) {
      return toNumber(field(row, "RealStock"));
    }

    function normalizeRowKeys(raw) {
      const out = {};
      for (const k in raw) {
        const nk = stripQuotes(k);
        out[nk] = stripQuotes(raw[k]);
      }
      return out;
    }

    function buildSearchBlob(r) {
      const parts = [
        field(r, "DesComClear"),
        field(r, "Id"),
        field(r, "xx_Marque"),
        field(r, "xx_Vehicule", "xx_VEHICULE"),
        field(r, "FamilyName"),
        field(r, "xx_Position"),
        field(r, "xx_Annee"),
      ];
      return parts.map(safeText).join(" ").toLowerCase();
    }

    function rowKey(r) {
      return field(r, "UniqueId") || field(r, "DesComClear");
    }

    function slugify(s) {
      return String(s ?? "")
        .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, "_")
        .replace(/^_+|_+$/g, "")
        .slice(0, 90);
    }

    // Images candidates (robuste)
    function getImageCandidates(row) {
      const uid = field(row, "UniqueId");
      const name = field(row, "DesComClear");
      const candidates = [];

      if (uid) {
        for (const ext of IMAGE_EXTS) {
          candidates.push(IMAGE_BASE_PATH + encodeURIComponent(uid) + ext);
        }
      }

      const sl = slugify(name);
      if (sl) {
        for (const ext of IMAGE_EXTS) {
          candidates.push(IMAGE_BASE_PATH + encodeURIComponent(sl) + ext);
        }
      }

      // Optionnel : si tu as une colonne image dans le CSV un jour
      const explicit = field(row, "Image", "ImageUrl", "Photo");
      if (explicit) {
        candidates.unshift(explicit);
      }

      // Dé-dupe
      return Array.from(new Set(candidates));
    }

    // Gestion fallback images
    function attachImgFallback(imgEl) {
      try {
        const cands = JSON.parse(imgEl.getAttribute("data-img-cands") || "[]");
        imgEl.__cands = Array.isArray(cands) ? cands : [];
        imgEl.__idx = 0;
      } catch {
        imgEl.__cands = [];
        imgEl.__idx = 0;
      }

      imgEl.addEventListener("error", () => {
        const list = imgEl.__cands || [];
        imgEl.__idx = (imgEl.__idx || 0) + 1;
        if (imgEl.__idx < list.length) {
          imgEl.src = list[imgEl.__idx];
        } else {
          // stop
          imgEl.remove();
        }
      }, {
        passive: true
      });
    }

    /***********************
     * PERSIST: hide prices
     ***********************/
    function saveHidePrices() {
      try {
        localStorage.setItem(HIDE_PRICES_KEY, hidePrices ? "1" : "0");
      } catch {}
    }

    function restoreHidePrices() {
      try {
        hidePrices = (localStorage.getItem(HIDE_PRICES_KEY) === "1");
      } catch {
        hidePrices = false;
      }
      if (hidePricesToggle) hidePricesToggle.checked = hidePrices;
    }

    function setHidePrices(v) {
      hidePrices = !!v;
      saveHidePrices();
      updatePdfUi();
      updateCartUi();
      applyFilters();
    }

    /***********************
     * CART
     ***********************/
    function saveCart() {
      try {
        const payload = Array.from(cart.entries()).map(([k, v]) => ({
          k,
          qty: v.qty
        }));
        localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(payload));
      } catch {}
    }

    function restoreCart() {
      cart.clear();
      let payload = [];
      try {
        payload = JSON.parse(localStorage.getItem(CART_STORAGE_KEY) || "[]");
        if (!Array.isArray(payload)) payload = [];
      } catch {
        payload = [];
      }

      for (const it of payload) {
        const k = it?.k;
        const qty = toNumber(it?.qty);
        if (!k || !Number.isFinite(qty) || qty <= 0) continue;
        const r = allRowsByKey.get(k);
        if (r) cart.set(k, {
          row: r,
          qty: Math.floor(qty)
        });
      }
      updateCartUi();
      refreshGridStates();
    }

    function getCartQty(key) {
      const item = cart.get(key);
      return item ? item.qty : 0;
    }

    function addToCartByKey(key, qtyToAdd) {
      const r = allRowsByKey.get(key);
      if (!r) return;
      const qty = Math.max(1, Math.floor(toNumber(qtyToAdd) || 1));
      if (cart.has(key)) cart.get(key).qty += qty;
      else cart.set(key, {
        row: r,
        qty
      });
      saveCart();
      updateCartUi();
      refreshGridStates();
      toast("Ajouté au panier.");
    }

    function setCartQty(key, qty) {
      qty = Math.floor(toNumber(qty) || 0);
      if (qty <= 0) cart.delete(key);
      else {
        const it = cart.get(key);
        if (it) it.qty = qty;
      }
      saveCart();
      updateCartUi();
      refreshGridStates();
    }

    function removeFromCart(key) {
      cart.delete(key);
      saveCart();
      updateCartUi();
      refreshGridStates();
    }

    function clearCart() {
      cart.clear();
      saveCart();
      updateCartUi();
      refreshGridStates();
      toast("Panier vidé.");
    }

    function computeCartTotal() {
      let t = 0;
      for (const {
          row,
          qty
        }
        of cart.values()) {
        const pr = getPrice(row);
        if (Number.isFinite(pr)) t += pr * qty;
      }
      return t;
    }

    function renderCartList() {
      if (!cartListEl) return;
      cartListEl.innerHTML = "";

      const arr = Array.from(cart.entries()).map(([k, v]) => ({
        k,
        ...v
      }));
      arr.sort((a, b) => {
        const ba = field(a.row, "xx_Marque");
        const bb = field(b.row, "xx_Marque");
        const c1 = ba.localeCompare(bb, "fr");
        if (c1 !== 0) return c1;
        return field(a.row, "DesComClear").localeCompare(field(b.row, "DesComClear"), "fr");
      });

      for (const it of arr) {
        const r = it.row;
        const k = it.k;

        const cap = field(r, "DesComClear") || "Produit";
        const uid = displayId(r);
        const brand = field(r, "xx_Marque");
        const cands = getImageCandidates(r);

        const unit = hidePrices ? NaN : getPrice(r);
        const subTotal = hidePrices ? NaN : (Number.isFinite(unit) ? unit : 0) * it.qty;

        const wrap = document.createElement("div");
        wrap.className = "cart-item p-2";
        wrap.setAttribute("data-cart-key", k);

        wrap.innerHTML = `
          <div class="d-flex gap-2">
            <div class="cart-thumb" data-cart-thumb>
              ${cands.length ? `<img data-img-cands='${escHtml(JSON.stringify(cands))}' src="${escHtml(cands[0])}" alt="${escHtml(cap)}" loading="lazy" />` : ""}
            </div>

            <div class="flex-grow-1" style="min-width:0;">
              <div class="fw-semibold" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escHtml(cap)}</div>
              <div class="small muted">
                ${uid ? `IDd: <span class="mono">${escHtml(uid)}</span>` : ""}
                ${brand ? ` • ${escHtml(brand)}` : ""}
              </div>

              <div class="d-flex align-items-center justify-content-between mt-2 gap-2">
                <div>
                  <div class="small muted">PU</div>
                  <div class="fw-semibold mono ${hidePrices ? "text-muted" : "text-orange"}">
                    ${hidePrices ? "Prix masqué" : (Number.isFinite(unit) ? escHtml(fmtMoney(unit)) : "—")}
                  </div>
                </div>

                <div class="text-end">
                  <div class="small muted">Sous-total</div>
                  <div class="fw-semibold mono">
                    ${hidePrices ? "Prix masqué" : escHtml(fmtMoney(subTotal))}
                  </div>
                </div>
              </div>

              <div class="d-flex align-items-center justify-content-between mt-2 gap-2">
                <div class="d-flex align-items-center gap-2">
                  <button class="btn btn-outline-secondary qty-btn" type="button" data-cart-action="dec"><i class="bi bi-dash"></i></button>
                  <input class="form-control qty-input" type="number" min="1" step="1" value="${it.qty}" data-cart-action="qty" />
                  <button class="btn btn-outline-secondary qty-btn" type="button" data-cart-action="inc"><i class="bi bi-plus"></i></button>
                </div>

                <button class="btn btn-outline-danger btn-sm" type="button" data-cart-action="del" title="Supprimer">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>
          </div>
        `;

        cartListEl.appendChild(wrap);

        // attach fallback image
        const img = wrap.querySelector("img[data-img-cands]");
        if (img) attachImgFallback(img);
      }
    }

    function updateCartUi() {
      const nItems = Array.from(cart.values()).reduce((acc, it) => acc + (it.qty || 0), 0);
      if (cartCountEl) cartCountEl.textContent = String(nItems);

      const has = cart.size > 0;
      if (cartEmptyEl) cartEmptyEl.classList.toggle("d-none", has);
      if (cartListEl) cartListEl.classList.toggle("d-none", !has);

      renderCartList();

      const t = computeCartTotal();
      if (hidePrices) {
        cartTotalEl.textContent = "Prix masqué";
        cartTotalEl.classList.add("text-muted");
        cartTotalHintEl.textContent = "Les prix sont masqués (option activée).";
      } else {
        cartTotalEl.textContent = fmtMoney(t);
        cartTotalEl.classList.remove("text-muted");
        cartTotalHintEl.textContent = `Prix affichés en ${priceMode === "inc" ? "TTC" : "HT"}.`;
      }

      btnCartCheckout.disabled = !has;
      btnCartClear.disabled = !has;
      if (orderToLabel) orderToLabel.textContent = ORDER_EMAIL_TO;
    }

    cartListEl?.addEventListener("click", (e) => {
      const wrap = e.target.closest("[data-cart-key]");
      if (!wrap) return;
      const k = wrap.getAttribute("data-cart-key");
      const actionBtn = e.target.closest("[data-cart-action]");
      if (!actionBtn) return;
      const action = actionBtn.getAttribute("data-cart-action");

      if (action === "del") return removeFromCart(k);
      if (action === "dec") return setCartQty(k, getCartQty(k) - 1);
      if (action === "inc") return setCartQty(k, getCartQty(k) + 1);
    });

    cartListEl?.addEventListener("input", debounce((e) => {
      const inp = e.target.closest("[data-cart-action='qty']");
      if (!inp) return;
      const wrap = e.target.closest("[data-cart-key]");
      if (!wrap) return;
      const k = wrap.getAttribute("data-cart-key");
      const v = toNumber(inp.value);
      setCartQty(k, v);
    }, 200));

    btnCartClear?.addEventListener("click", clearCart);

    /***********************
     * PDF SELECTION
     ***********************/
    function savePdfSelection() {
      try {
        localStorage.setItem(PDF_STORAGE_KEY, JSON.stringify(Array.from(pdfSelection.keys())));
      } catch {}
    }

    function restorePdfSelection() {
      pdfSelection.clear();
      let keys = [];
      try {
        keys = JSON.parse(localStorage.getItem(PDF_STORAGE_KEY) || "[]");
        if (!Array.isArray(keys)) keys = [];
      } catch {
        keys = [];
      }

      for (const k of keys) {
        const r = allRowsByKey.get(k);
        if (r) pdfSelection.set(k, r);
      }
      updatePdfUi();
      refreshGridStates();
    }

    function togglePdfByKey(key) {
      const r = allRowsByKey.get(key);
      if (!r) return;

      if (pdfSelection.has(key)) {
        pdfSelection.delete(key);
        savePdfSelection();
        updatePdfUi();
        refreshGridStates();
        toast("Retiré de la liste PDF.");
      } else {
        pdfSelection.set(key, r);
        savePdfSelection();
        updatePdfUi();
        refreshGridStates();
        toast("Ajouté à la liste PDF.");
      }
    }

    function clearPdf() {
      pdfSelection.clear();
      savePdfSelection();
      updatePdfUi();
      refreshGridStates();
      toast("Liste PDF vidée.");
    }

    function updatePdfUi() {
      const n = pdfSelection.size;
      if (pdfCount) pdfCount.textContent = String(n);
      if (bulkPdfCount2) bulkPdfCount2.textContent = String(n);
      renderPdfList();
    }

    function renderPdfList() {
      if (!pdfListEl || !pdfListEmpty) return;

      const arr = Array.from(pdfSelection.values());
      pdfListEl.innerHTML = "";
      pdfListEmpty.classList.toggle("d-none", arr.length !== 0);

      for (const r of arr) {
        const key = rowKey(r);
        const cap = field(r, "DesComClear") || "Produit";
        const uid = field(r, "UniqueId");
        const brand = field(r, "xx_Marque");
        const fam = field(r, "FamilyName");

        const item = document.createElement("div");
        item.className = "list-group-item d-flex align-items-start justify-content-between gap-3";

        item.innerHTML = `
          <div style="min-width:0;">
            <div class="fw-semibold">${escHtml(cap)}</div>
            <div class="small muted">
              ${brand ? ` • ${escHtml(brand)}` : ""}
              ${fam ? ` • Famille: <span class="mono">${escHtml(fam)}</span>` : ""}
            </div>
          </div>
          <button class="btn btn-outline-danger btn-sm" type="button" data-pdf-remove="${escHtml(key)}" title="Retirer">
            <i class="bi bi-trash"></i>
          </button>
        `;

        pdfListEl.appendChild(item);
      }
    }

    pdfListEl?.addEventListener("click", (e) => {
      const btn = e.target.closest("[data-pdf-remove]");
      if (!btn) return;
      const k = btn.getAttribute("data-pdf-remove");
      pdfSelection.delete(k);
      savePdfSelection();
      updatePdfUi();
      refreshGridStates();
    });

    btnPdfClear?.addEventListener("click", clearPdf);

    /***********************
     * BULK ADD (PDF)
     ***********************/
    function buildFacetCounts() {
      facetCounts.brand = new Map();
      facetCounts.vehicle = new Map();
      facetCounts.family = new Map();

      for (const r of allRows) {
        const b = field(r, "xx_Marque");
        const v = field(r, "xx_Vehicule", "xx_VEHICULE");
        const f = field(r, "FamilyName");
        if (b) facetCounts.brand.set(b, (facetCounts.brand.get(b) || 0) + 1);
        if (v) facetCounts.vehicle.set(v, (facetCounts.vehicle.get(v) || 0) + 1);
        if (f) facetCounts.family.set(f, (facetCounts.family.get(f) || 0) + 1);
      }
    }

    function getRowsByFacet(type, value) {
      if (!value) return [];
      if (type === "brand") return allRows.filter(r => field(r, "xx_Marque") === value);
      if (type === "vehicle") return allRows.filter(r => field(r, "xx_Vehicule", "xx_VEHICULE") === value);
      if (type === "family") return allRows.filter(r => field(r, "FamilyName") === value);
      return [];
    }

    function bulkAddRows(rows) {
      if (!rows || !rows.length) {
        toast("Aucun article à ajouter.");
        return;
      }
      let added = 0;
      for (const r of rows) {
        const k = rowKey(r);
        if (!k) continue;
        if (!pdfSelection.has(k)) {
          pdfSelection.set(k, r);
          added++;
        }
      }
      savePdfSelection();
      updatePdfUi();
      refreshGridStates();
      toast(added ? `Ajouté ${added} article(s) à la liste PDF.` : "Aucun ajout (tous déjà présents).");
    }

    function renderBulkList(type) {
      let values = [];
      let listEl = null;
      let emptyEl = null;
      let q = "";

      if (type === "brand") {
        values = facets.brands.slice();
        listEl = bulkListBrand;
        emptyEl = bulkEmptyBrand;
        q = (bulkSearchBrand?.value || "").trim().toLowerCase();
      } else if (type === "vehicle") {
        values = facets.vehicles.slice();
        listEl = bulkListVehicle;
        emptyEl = bulkEmptyVehicle;
        q = (bulkSearchVehicle?.value || "").trim().toLowerCase();
      } else {
        values = facets.families.slice();
        listEl = bulkListFamily;
        emptyEl = bulkEmptyFamily;
        q = (bulkSearchFamily?.value || "").trim().toLowerCase();
      }

      if (!listEl || !emptyEl) return;

      const filtered = q ? values.filter(v => v.toLowerCase().includes(q)) : values;

      listEl.innerHTML = "";
      emptyEl.classList.toggle("d-none", filtered.length !== 0);

      for (const v of filtered) {
        const cnt = (type === "brand") ?
          (facetCounts.brand.get(v) || 0) :
          (type === "vehicle") ?
          (facetCounts.vehicle.get(v) || 0) :
          (facetCounts.family.get(v) || 0);

        const item = document.createElement("div");
        item.className = "list-group-item d-flex align-items-center justify-content-between gap-3";

        item.innerHTML = `
          <div style="min-width:0;">
            <div class="fw-semibold">${escHtml(v)}</div>
            <div class="small muted">${cnt} article(s)</div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge text-bg-primary">${cnt}</span>
            <button type="button" class="btn btn-orange btn-sm"
                    data-bulk-add="${escHtml(type)}" data-bulk-val="${escHtml(v)}">
              <i class="bi bi-file-earmark-plus"></i> Ajouter
            </button>
          </div>
        `;
        listEl.appendChild(item);
      }
    }

    bulkAddModalEl?.addEventListener("click", (e) => {
      const btn = e.target.closest("[data-bulk-add]");
      if (!btn) return;
      const type = btn.getAttribute("data-bulk-add");
      const val = btn.getAttribute("data-bulk-val");
      const rows = getRowsByFacet(type, val);
      bulkAddRows(rows);
    });

    function openBulkModal() {
      if (!allRows.length) {
        toast("Chargez d’abord le CSV.");
        return;
      }
      buildFacetCounts();
      renderBulkList("brand");
      renderBulkList("vehicle");
      renderBulkList("family");
      updatePdfUi();

      const m = bootstrap.Modal.getOrCreateInstance(bulkAddModalEl);
      m.show();
      setTimeout(() => bulkSearchBrand?.focus(), 150);
    }

    btnAddBulk?.addEventListener("click", openBulkModal);
    bulkSearchBrand?.addEventListener("input", debounce(() => renderBulkList("brand"), 120));
    bulkSearchVehicle?.addEventListener("input", debounce(() => renderBulkList("vehicle"), 120));
    bulkSearchFamily?.addEventListener("input", debounce(() => renderBulkList("family"), 120));

    /***********************
     * PDF GENERATION (catalogue)
     ***********************/
  /***********************
   * PDF GENERATION (catalogue)
   * ✅ Regroupement: Véhicule (A→Z) puis Marque (A→Z)
   * ✅ Tout le reste est conservé (cover, cartes, images, header...)
   ***********************/
  async function fetchAsDataUrl(url) {
    const res = await fetch(url, { cache: "no-store" });
    if (!res.ok) throw new Error("HTTP " + res.status);
    const blob = await res.blob();
    return await new Promise((resolve, reject) => {
      const r = new FileReader();
      r.onload = () => resolve(String(r.result));
      r.onerror = reject;
      r.readAsDataURL(blob);
    });
  }

  function getImageTypeFromDataUrl(dataUrl) {
    if (dataUrl.startsWith("data:image/png")) return "PNG";
    if (dataUrl.startsWith("data:image/jpeg")) return "JPEG";
    if (dataUrl.startsWith("data:image/jpg")) return "JPEG";
    if (dataUrl.startsWith("data:image/webp")) return "WEBP";
    return "PNG";
  }

  function splitText(doc, text, maxWidth) {
    const t = String(text || "").trim();
    if (!t) return [];
    return doc.splitTextToSize(t, maxWidth);
  }

  function pdfSafeText(s) {
    return String(s ?? "")
      .replace(/[\u00A0\u202F]/g, " ")
      .replace(/\u2019/g, "'")
      .replace(/\u2013|\u2014/g, "-");
  }

  function normGroupLabel(v, fallback) {
    const s = String(v ?? "").trim();
    return s ? s : fallback;
  }

  function sortFr(a, b) {
    return String(a).localeCompare(String(b), "fr", { sensitivity: "base" });
  }

  async function generatePdfFromSelection() {
    if (!window.jspdf || !window.jspdf.jsPDF) {
      toast("Librairie PDF non chargée.");
      return;
    }
    if (pdfSelection.size === 0) {
      toast("Ajoutez au moins un article à la liste PDF.");
      return;
    }

    const items = Array.from(pdfSelection.values());

    // ✅ GROUP: Véhicule -> Marque
    const groups = new Map(); // vehicle -> Map(brand -> rows[])
    for (const r of items) {
      const veh = normGroupLabel(field(r, "xx_Vehicule", "xx_VEHICULE"), "Véhicule non renseigné");
      const br  = normGroupLabel(field(r, "xx_Marque"), "Marque non renseignée");

      if (!groups.has(veh)) groups.set(veh, new Map());
      const bm = groups.get(veh);
      if (!bm.has(br)) bm.set(br, []);
      bm.get(br).push(r);
    }

    // ✅ tri A→Z
    const vehicles = Array.from(groups.keys()).sort(sortFr);
    for (const veh of vehicles) {
      const bm = groups.get(veh);
      const brands = Array.from(bm.keys()).sort(sortFr);
      for (const br of brands) {
        const arr = bm.get(br);
        arr.sort((a, b) => {
          const na = field(a, "DesComClear");
          const nb = field(b, "DesComClear");
          const c1 = na.localeCompare(nb, "fr", { sensitivity: "base" });
          if (c1 !== 0) return c1;
          return displayId(a).localeCompare(displayId(b), "fr", { sensitivity: "base" });
        });
      }
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ unit: "mm", format: "a4" });

    const pageW = doc.internal.pageSize.getWidth();
    const pageH = doc.internal.pageSize.getHeight();

    const margin = 10;
    const headerH = 14;
    const footerH = 10;
    const topY = margin + headerH;
    const bottomY = pageH - margin - footerH;

    const gapX = 6;
    const gapY = 6;
    const colW = (pageW - margin * 2 - gapX) / 2;
    const cardH = 80;

    const imgCache = new Map();

    function drawHeader(pageNum) {
      doc.setFont("helvetica", "bold");
      doc.setFontSize(12);
      doc.setTextColor(20);
      doc.text("Catalogue", margin, margin + 8);

      doc.setFont("helvetica", "normal");
      doc.setFontSize(9);
      doc.setTextColor(110);
      doc.text(new Date().toLocaleString(LOCALE), pageW - margin, margin + 8, { align: "right" });

      doc.setDrawColor(230);
      doc.line(margin, pageH - margin - footerH, pageW - margin, pageH - margin - footerH);

      doc.setFontSize(9);
      doc.setTextColor(120);
      doc.text(`Page ${pageNum}`, pageW - margin, pageH - margin - 3, { align: "right" });
    }

    async function addCover(itemsCount) {
      doc.setFillColor(245, 247, 251);
      doc.rect(0, 0, pageW, pageH, "F");

      doc.setFillColor(219, 234, 254);
      doc.circle(22, 24, 16, "F");
      doc.setFillColor(255, 237, 213);
      doc.circle(pageW - 18, 34, 22, "F");

      const cardX = 14, cardY = 20, cardW = pageW - 28, cardHh = pageH - 40;

      doc.setDrawColor(225);
      doc.setFillColor(255, 255, 255);
      doc.roundedRect(cardX, cardY, cardW, cardHh, 10, 10, "FD");

      doc.setFillColor(13, 110, 253);
      doc.roundedRect(cardX + 10, cardY + 10, 14, 14, 4, 4, "F");
      doc.setFillColor(253, 126, 20);
      doc.roundedRect(cardX + 18, cardY + 10, 6, 14, 4, 4, "F");

      const titleY = cardY + 44;
      doc.setFont("helvetica", "bold");
      doc.setFontSize(30);
      doc.setTextColor(17);
      doc.text("Catalogue", pageW / 2, titleY, { align: "center" });

      doc.setFont("helvetica", "normal");
      doc.setFontSize(11);
      doc.setTextColor(110);

      const meta1 = pdfSafeText(`Sélection d’articles • ${itemsCount} référence${itemsCount > 1 ? "s" : ""}`);
      const meta2 = hidePrices
        ? pdfSafeText(`Généré le ${new Date().toLocaleDateString(LOCALE)} • Prix masqués`)
        : pdfSafeText(`Généré le ${new Date().toLocaleDateString(LOCALE)} • ${priceMode === "inc" ? "Prix TTC" : "Prix HT"}`);

      doc.text(meta1, pageW / 2, titleY + 10, { align: "center" });
      doc.text(meta2, pageW / 2, titleY + 16, { align: "center" });

      doc.setDrawColor(230);
      doc.setLineWidth(0.6);
      doc.line(pageW / 2 - 40, titleY + 24, pageW / 2 + 40, titleY + 24);

      try {
        const logoDataUrl = await fetchAsDataUrl(PDF_COVER_LOGO_URL);
        const imgType = getImageTypeFromDataUrl(logoDataUrl);

        let logoW = 105;
        const maxLogoW = pageW - 60;
        if (logoW > maxLogoW) logoW = maxLogoW;

        const props = doc.getImageProperties(logoDataUrl);
        let logoH = (logoW * props.height) / props.width;

        const maxH = 40;
        if (logoH > maxH) {
          logoH = maxH;
          logoW = (logoH * props.width) / props.height;
        }

        const logoX = (pageW - logoW) / 2;
        const logoY = titleY + 30;

        doc.addImage(logoDataUrl, imgType, logoX, logoY, logoW, logoH, undefined, "FAST");
      } catch (e) {}

      const footY = cardY + cardHh - 24;
      doc.setFillColor(248, 250, 252);
      doc.roundedRect(cardX + 10, footY, cardW - 20, 14, 6, 6, "F");

      doc.setFont("helvetica", "normal");
      doc.setFontSize(10);
      doc.setTextColor(90);
      doc.text(window.location.host ? window.location.host : "Catalogue", pageW / 2, footY + 9.5, { align: "center" });

      doc.addPage();
    }

    async function getDataUrl(imgUrl) {
      if (!imgUrl) return null;
      if (imgCache.has(imgUrl)) return imgCache.get(imgUrl);
      try {
        const dataUrl = await fetchAsDataUrl(imgUrl);
        imgCache.set(imgUrl, dataUrl);
        return dataUrl;
      } catch {
        imgCache.set(imgUrl, null);
        return null;
      }
    }

    // ✅ TITRES DE GROUPE (full width)
    function drawGroupTitle(text, level, xRef, yRef) {
      const fullX = margin;
      const fullW = pageW - margin * 2;

      const h = (level === 1) ? 10 : 8;
      const padX = 4;

      // si on est sur la colonne de droite -> on passe ligne suivante
      let x = xRef.value;
      let y = yRef.value;
      if (x !== margin) {
        xRef.value = margin;
        yRef.value = y + cardH + gapY;
        x = xRef.value;
        y = yRef.value;
      }

      // si pas de place -> nouvelle page
      if (y + h > bottomY) {
        doc.addPage();
        pageNumRef.value++;
        drawHeader(pageNumRef.value);
        xRef.value = margin;
        yRef.value = topY;
        y = yRef.value;
      }

      // style
      if (level === 1) {
        doc.setFillColor(219, 234, 254);
        doc.setDrawColor(210);
        doc.roundedRect(fullX, y, fullW, h, 3, 3, "FD");
        doc.setFont("helvetica", "bold");
        doc.setFontSize(11);
        doc.setTextColor(10, 88, 202);
        doc.text(pdfSafeText(text), fullX + padX, y + 6.8);
      } else {
        doc.setFillColor(255, 237, 213);
        doc.setDrawColor(220);
        doc.roundedRect(fullX, y, fullW, h, 3, 3, "FD");
        doc.setFont("helvetica", "bold");
        doc.setFontSize(10);
        doc.setTextColor(180, 83, 9);
        doc.text(pdfSafeText(text), fullX + padX, y + 5.8);
      }

      // avance
      xRef.value = margin;
      yRef.value = y + h + 4;
    }

    function drawBadge(x, y, text, mode) {
      const padX = 2.2;
      doc.setFontSize(8);
      const w = doc.getTextWidth(text) + padX * 2;
      const h = 6;

      doc.setDrawColor(230);
      if (mode === "blue") doc.setFillColor(219, 234, 254);
      else if (mode === "orange") doc.setFillColor(255, 237, 213);
      else doc.setFillColor(245, 245, 245);

      doc.roundedRect(x, y, w, h, 2, 2, "FD");
      doc.setTextColor(40);
      doc.text(text, x + padX, y + 4.3);
      return w;
    }

    // ✅ ta carte identique (inchangée)
    function drawCard(x, y, r) {
      doc.setDrawColor(225);
      doc.setFillColor(255, 255, 255);
      doc.roundedRect(x, y, colW, cardH, 4, 4, "FD");

      const imgX = x + 4, imgY = y + 4, imgW = 38, imgH = 28;
      doc.setDrawColor(230);
      doc.setFillColor(255, 255, 255);
      doc.roundedRect(imgX, imgY, imgW, imgH, 3, 3, "FD");

      const cap = field(r, "DesComClear") || "Produit";
      const uid = displayId(r);
      const brand = field(r, "xx_Marque");
      const family = field(r, "FamilyName");
      const veh = field(r, "xx_Vehicule", "xx_VEHICULE");
      const year = field(r, "xx_Annee");
      const pos = field(r, "xx_Position");
      const stock = getStock(r);
      const stockOk = Number.isFinite(stock) && stock > 0;
      const price = getPrice(r);

      const tx = imgX + imgW + 4;
      const ty = imgY + 3;
      const textW = x + colW - 4 - tx;

      doc.setFont("helvetica", "bold");
      doc.setFontSize(10.5);
      doc.setTextColor(20);
      const titleLines = splitText(doc, pdfSafeText(cap), textW);
      doc.text(titleLines.slice(0, 2), tx, ty);

      const badgeH = 6;
      let bx = x + 4;
      let by = imgY + imgH + 2;

      if (brand) bx += drawBadge(bx, by, pdfSafeText(`Mrq: ${brand}`), "blue") + 2;
      if (family) bx += drawBadge(bx, by, pdfSafeText(`Cat: ${family}`), "orange") + 2;
      if (pos) bx += drawBadge(bx, by, pdfSafeText(`Pos: ${pos}`), "soft") + 2;

      if (bx > x + colW - 6) by += badgeH + 2;

      const boxX = x + 4;
      const boxY = by + badgeH + 3;
      const boxW = colW - 8;
      const boxH = (y + cardH) - boxY - 5;

      doc.setDrawColor(240);
      doc.setFillColor(252, 253, 255);
      doc.roundedRect(boxX, boxY, boxW, boxH, 3, 3, "FD");

      const pad = 5;
      let ly = boxY + pad + 1;

      doc.setFont("helvetica", "bold");
      doc.setFontSize(10);
      doc.setTextColor(20);

      const priceLine = hidePrices
        ? "Prix masqué"
        : `${pdfSafeText(fmtMoney(price))} (${priceMode === "inc" ? "TTC" : "HT"})`;

      doc.text(priceLine, boxX + pad, ly);
      ly += 6.2;

      doc.setFont("helvetica", "normal");
      doc.setFontSize(8.8);
      doc.setTextColor(70);

      const maxY = boxY + boxH - pad;

      const lineVeh = [veh, year].filter(Boolean).join(" • ");
      if (lineVeh && ly <= maxY) {
        doc.text(splitText(doc, pdfSafeText(lineVeh), boxW - pad * 2).slice(0, 1), boxX + pad, ly);
        ly += 5;
      }

      const stTxt = stockOk ? "Stock: En stock" : "Stock: Rupture";
      if (ly <= maxY) {
        doc.text(pdfSafeText(stTxt), boxX + pad, ly);
        ly += 5;
      }

      if (uid && ly <= maxY) {
        doc.text(
          splitText(doc, pdfSafeText(`ID: ${uid}`), boxW - pad * 2).slice(0, 2),
          boxX + pad,
          ly
        );
      }

      const cands = getImageCandidates(r);
      return { imgBox: { x: imgX, y: imgY, w: imgW, h: imgH }, imgCandidates: cands };
    }

    // Cover
    await addCover(items.length);

    // pages
    let pageNum = 1;
    const pageNumRef = { value: pageNum };
    drawHeader(pageNumRef.value);

    let x = margin;
    let y = topY;
    const xRef = { value: x };
    const yRef = { value: y };

    function newPage() {
      doc.addPage();
      pageNumRef.value++;
      drawHeader(pageNumRef.value);
      xRef.value = margin;
      yRef.value = topY;
    }

    function ensureRoomForCard() {
      if (yRef.value + cardH > bottomY) newPage();
    }

    // ✅ PRINT grouped
    for (const veh of vehicles) {
      drawGroupTitle(veh, 1, xRef, yRef);

      const bm = groups.get(veh);
      const brands = Array.from(bm.keys()).sort(sortFr);

      for (const br of brands) {
        drawGroupTitle(br, 2, xRef, yRef);

        const arr = bm.get(br) || [];
        for (const r of arr) {
          ensureRoomForCard();

          const { imgBox, imgCandidates } = drawCard(xRef.value, yRef.value, r);

          // image: on tente la première qui marche
          for (const url of (imgCandidates || [])) {
            const dataUrl = await getDataUrl(url);
            if (dataUrl) {
              try {
                const props = doc.getImageProperties(dataUrl);
                const iw = props.width;
                const ih = props.height;

                const inset = 0.6;
                const aw = imgBox.w - inset * 2;
                const ah = imgBox.h - inset * 2;

                const scale = Math.min(aw / iw, ah / ih);
                const w = iw * scale;
                const h = ih * scale;

                const dx = imgBox.x + inset + (aw - w) / 2;
                const dy = imgBox.y + inset + (ah - h) / 2;

                doc.addImage(
                  dataUrl,
                  getImageTypeFromDataUrl(dataUrl),
                  dx, dy, w, h,
                  undefined,
                  "FAST"
                );

                doc.setDrawColor(230);
                doc.setLineWidth(0.2);
                doc.roundedRect(imgBox.x, imgBox.y, imgBox.w, imgBox.h, 3, 3, "S");
                break;
              } catch {}
            }
          }

          // next col/row (identique)
          if (xRef.value === margin) {
            xRef.value = margin + colW + gapX;
          } else {
            xRef.value = margin;
            yRef.value += cardH + gapY;
          }
        }
      }
    }

    const fileName = `catalogue_selection_${new Date().toISOString().slice(0,10)}.pdf`;
    doc.save(fileName);
    toast("Catalogue PDF généré (Véhicule → Marque).");
  }

  btnPdfGenerate?.addEventListener("click", generatePdfFromSelection);

    /***********************
     * RENDER HELPERS
     ***********************/
    function renderSkeleton(n = 12) {
      grid.innerHTML = "";
      for (let i = 0; i < n; i++) {
        const col = document.createElement("div");
        col.className = "col-12 col-sm-6 col-xl-2";
        col.innerHTML = `<div class="skeleton"></div>`;
        grid.appendChild(col);
      }
    }

    function infoRow(label, value) {
      value = (value ?? "").toString().trim();
      if (!value) return "";
      const esc = (s) => String(s)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;");
      return `
        <div class="d-flex justify-content-between gap-3 py-1 border-bottom">
          <div class="small muted">${esc(label)}</div>
          <div class="small text-end mono" style="max-width:65%;overflow-wrap:anywhere;">${esc(value)}</div>
        </div>
      `;
    }

    function ensureSentinel() {
      let s = document.getElementById("infiniteSentinel");
      if (!s) {
        s = document.createElement("div");
        s.id = "infiniteSentinel";
        s.className = "col-12";
        s.innerHTML = `
          <div class="py-3 text-center">
            <div id="infiniteSpinner" class="spinner-border spinner-border-sm text-secondary me-2" role="status" style="display:none"></div>
            <span id="infiniteHint" class="muted"></span>
          </div>
        `;
        grid.appendChild(s);
      }
      return s;
    }

    function setInfiniteLoading(isLoading) {
      const sp = document.getElementById("infiniteSpinner");
      if (sp) sp.style.display = isLoading ? "inline-block" : "none";
    }

    function updateInfiniteHint() {
      const hintEl = document.getElementById("infiniteHint");
      if (!hintEl) return;

      if (!filteredRows.length) {
        hintEl.textContent = "";
        return;
      }

      if (renderedCount >= filteredRows.length) {
        hintEl.textContent = "Fin des résultats.";
      } else {
        hintEl.textContent = `Affichage ${renderedCount} / ${filteredRows.length} — descendez pour charger la suite…`;
      }
    }

    function cardPriceHtml(r, k) {
      if (hidePrices) {
        return `
          <div class="d-flex align-items-center justify-content-between gap-2 mt-3">
            <div class="fw-semibold mono text-muted" data-price-text>Prix masqué</div>
            <div class="d-flex align-items-center gap-2">
              <div class="small muted" data-price-mode></div>
              <button class="btn btn-outline-orange btn-sm" type="button" data-action="reveal-price" data-key="${escHtml(k)}" title="Afficher 5s">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>
        `;
      }

      const p = getPrice(r);
      const txt = Number.isFinite(p) ? fmtMoney(p) : "Prix indisponible";
      return `
        <div class="d-flex align-items-center justify-content-between gap-2 mt-3">
          <div class="fw-bold mono text-primary" data-price-text>${escHtml(txt)}</div>
          <div class="small muted" data-price-mode>${priceMode === "inc" ? "TTC" : "HT"}</div>
        </div>
      `;
    }

    function buildCardCol(r) {
      const col = document.createElement("div");
      col.className = "col-12 col-sm-6 col-xl-2";

      const k = rowKey(r);
      if (!k) return col;

      const cap = field(r, "DesComClear") || "Produit";
      const brand = field(r, "xx_Marque");
      const family = field(r, "FamilyName");
      const veh = field(r, "xx_Vehicule", "xx_VEHICULE");
      const year = field(r, "xx_Annee");
      const pos = field(r, "xx_Position");
      const uid = displayId(r);

      const stock = getStock(r);
      const stockOk = Number.isFinite(stock) && stock > 0;

      const inCartQty = getCartQty(k);
      const draftQty = Math.max(1, Math.floor(toNumber(cardQtyDraft.get(k)) || 1));
      cardQtyDraft.set(k, draftQty);

      const pdfActive = pdfSelection.has(k);
      const cands = getImageCandidates(r);
      const upd = getLastUpdate(r);
      const lastUpdateText = upd.date ? fmtDateTime(upd.date) : "";

      const card = document.createElement("div");
      card.className = "catalog-card bg-white h-100 p-3";
      card.setAttribute("data-key", k);

      card.innerHTML = `
   <div class="thumb ratio ratio-4x3 mb-3">
  <div class="thumb-placeholder">
    <div class="text-center">
      <i class="bi bi-image"></i>
      <div class="small">Photo indisponible</div>
    </div>
  </div>

  ${cands.length ? `
    <img data-img-cands='${escHtml(JSON.stringify(cands))}'
         src="${escHtml(cands[0])}"
         alt="${escHtml(cap)}"
         loading="lazy"
         onload="this.previousElementSibling.style.display='none'"/>
  ` : ""}

  <!-- ✅ Toujours visible (même quand le placeholder disparaît) -->


  <span class="position-absolute top-0 end-0 m-2 ${inCartQty>0 ? "" : "d-none"}" data-cart-badge>
    <span class="bg-white rounded-pill px-2 py-1" style="border:1px solid rgba(13,110,253,.20);">
      <i class="bi bi-cart3 me-1 text-primary"></i>
      <span data-cart-badge-qty class="text-primary">${inCartQty}</span>
    </span>
  </span>
</div>


        <div class="d-flex flex-wrap gap-2 mb-2">
          ${brand ? `<span class="badge rounded-pill badge-soft">${escHtml(brand)}</span>` : ""}
         ${family ? `<span class="badge rounded-pill badge-family">${
  escHtml(family.length > 6 ? family.slice(0, 6) + " ..." : family)
}</span>` : ""}

   
  <span class="badge rounded-pill ${stockOk ? "badge-stock-ok" : "badge-stock-no"}">
    ${stockOk ? "Dispo" : "Rupture"}
  </span>


        </div>

        <div class="fw-semibold fs-6 card-title-trunc">${escHtml(cap)}</div>
        <div class="mt-2 small muted">${escHtml([veh, year].filter(Boolean).join(" • "))}</div>

        ${cardPriceHtml(r, k)}



        <div class="mt-3">
        ${infoRow("Référence", uid)}
	${infoRow("Position", pos)}
	${CAN_SHOW_STOCK ? `
  <div class="d-flex justify-content-between gap-3 py-1 border-bottom">
    <div class="small muted">Stock</div>
    <div class="small text-end mono">
      ${
        Number.isFinite(stock)
          ? `<span class="stock-pill ${stockPillClass(stock)}">${stock}</span>`
          : "—"
      }
    </div>
  </div>
` : ""}



        </div>

        <div class="d-flex gap-2 mt-3">
          <button type="button" class="btn ${pdfActive ? "btn-outline-orange" : "btn-orange"} btn-sm flex-fill"
                  data-action="toggle-pdf" data-key="${escHtml(k)}">
            ${pdfActive ? `<i class="bi bi-check2"></i> Ajouté` : `<i class="bi bi-file-earmark-plus"></i> Ajouter PDF`}
          </button>
        </div>

        <div class="d-flex align-items-center gap-2 mt-2">
          <div class="input-group input-group-sm" style="width: 132px;">
            <button class="btn btn-outline-secondary" type="button" data-action="qty-dec" data-key="${escHtml(k)}">
              <i class="bi bi-dash"></i>
            </button>
            <input class="form-control text-center mono" type="number" min="1" step="1"
                   value="${draftQty}" data-action="qty-input" data-key="${escHtml(k)}" />
            <button class="btn btn-outline-secondary" type="button" data-action="qty-inc" data-key="${escHtml(k)}">
              <i class="bi bi-plus"></i>
            </button>
          </div>

          <button type="button" class="btn btn-primary btn-sm flex-fill" data-action="add-cart" data-key="${escHtml(k)}">
            <i class="bi bi-cart-plus"></i>
          </button>
        </div>
      `;

      col.appendChild(card);

      // attach fallback image
      const img = card.querySelector("img[data-img-cands]");
      if (img) attachImgFallback(img);

      return col;
    }

    function appendNextBatch() {
      if (renderedCount >= filteredRows.length) {
        updateInfiniteHint();
        setInfiniteLoading(false);
        return;
      }

      const next = filteredRows.slice(renderedCount, renderedCount + PAGE_SIZE);
      const sentinel = ensureSentinel();

      setInfiniteLoading(true);

      let i = 0;
      const chunk = 24;

      function step() {
        const part = next.slice(i, i + chunk);
        for (const r of part) {
          const col = buildCardCol(r);
          grid.insertBefore(col, sentinel);
        }
        i += chunk;

        if (i < next.length) {
          requestAnimationFrame(step);
        } else {
          renderedCount += next.length;
          setInfiniteLoading(false);
          updateInfiniteHint();
        }
      }

      requestAnimationFrame(step);
    }

    function setupInfiniteObserver() {
      if (infiniteObserver) infiniteObserver.disconnect();

      const sentinel = ensureSentinel();
      infiniteObserver = new IntersectionObserver((entries) => {
        if (entries.some(e => e.isIntersecting)) {
          appendNextBatch();
        }
      }, {
        root: null,
        rootMargin: "700px 0px",
        threshold: 0.01
      });

      infiniteObserver.observe(sentinel);
    }

    function renderGrid(rows) {
      grid.innerHTML = "";
      empty.classList.toggle("d-none", rows.length !== 0);

      renderedCount = 0;

      ensureSentinel();
      updateInfiniteHint();

      appendNextBatch();
      setupInfiniteObserver();

      refreshGridStates();
    }

    function refreshGridStates() {
      grid.querySelectorAll(".catalog-card[data-key]").forEach(card => {
        const k = card.getAttribute("data-key");
        if (!k) return;

        // PDF btn
        const btnPdf = card.querySelector("[data-action='toggle-pdf']");
        if (btnPdf) {
          const active = pdfSelection.has(k);
          btnPdf.className = "btn " + (active ? "btn-outline-orange" : "btn-orange") + " btn-sm flex-fill";
          btnPdf.innerHTML = active ? `<i class="bi bi-check2"></i> Ajouté` : `<i class="bi bi-file-earmark-plus"></i> Ajouter PDF`;
        }

        // Cart badge qty
        const badge = card.querySelector("[data-cart-badge]");
        const badgeQty = card.querySelector("[data-cart-badge-qty]");
        const q = getCartQty(k);
        if (badge) badge.classList.toggle("d-none", q <= 0);
        if (badgeQty) badgeQty.textContent = String(q);

        // Draft qty input
        const inp = card.querySelector("[data-action='qty-input']");
        if (inp) {
          const d = Math.max(1, Math.floor(toNumber(cardQtyDraft.get(k)) || 1));
          if (String(inp.value) !== String(d)) inp.value = String(d);
        }
      });
    }

    /***********************
     * GRID EVENTS
     ***********************/
    function clampQty(v) {
      const n = Math.floor(toNumber(v) || 1);
      return Math.max(1, n);
    }

    grid.addEventListener("click", (e) => {
      const btn = e.target.closest("[data-action]");
      if (!btn) return;
      const action = btn.getAttribute("data-action");
      const k = btn.getAttribute("data-key");
      if (!k) return;

      if (action === "toggle-pdf") {
        togglePdfByKey(k);
        return;
      }

      if (action === "qty-dec") {
        const q = clampQty(cardQtyDraft.get(k));
        cardQtyDraft.set(k, Math.max(1, q - 1));
        refreshGridStates();
        return;
      }

      if (action === "qty-inc") {
        const q = clampQty(cardQtyDraft.get(k));
        cardQtyDraft.set(k, q + 1);
        refreshGridStates();
        return;
      }

      if (action === "add-cart") {
        const q = clampQty(cardQtyDraft.get(k));
        addToCartByKey(k, q);
        return;
      }

      if (action === "reveal-price") {
        const card = btn.closest(".catalog-card");
        if (!card) return;
        const r = allRowsByKey.get(k);
        if (!r) return;

        const price = getPrice(r);
        if (!Number.isFinite(price)) return toast("Prix indisponible.");

        const priceText = card.querySelector("[data-price-text]");
        const priceModeEl = card.querySelector("[data-price-mode]");
        if (!priceText) return;

        priceText.textContent = fmtMoney(price);
        priceText.classList.remove("text-muted");
        priceText.classList.add("text-orange");
        if (priceModeEl) priceModeEl.textContent = (priceMode === "inc") ? "TTC" : "HT";

        clearTimeout(card.__revealTimer);
        card.__revealTimer = setTimeout(() => {
          if (hidePrices) {
            priceText.textContent = "Prix masqué";
            priceText.classList.remove("text-orange");
            priceText.classList.add("text-muted");
            if (priceModeEl) priceModeEl.textContent = "";
          }
        }, 5000);
        return;
      }

    });

    grid.addEventListener("input", debounce((e) => {
      const inp = e.target.closest("[data-action='qty-input']");
      if (!inp) return;
      const k = inp.getAttribute("data-key");
      if (!k) return;
      cardQtyDraft.set(k, clampQty(inp.value));
      inp.value = String(clampQty(inp.value));
    }, 120));

    /***********************
     * FILTER UI (chips)
     ***********************/
    function sortLabel(v) {
      if (v === "priceAsc") return "Prix croissant";
      if (v === "priceDesc") return "Prix décroissant";
      if (v === "stockDesc") return "Stock décroissant";
      if (v === "az") return "Nom A → Z";
      return "Pertinence";
    }

    function renderChips(active) {
      chips.innerHTML = "";
      const items = [];

      if (active.q) items.push({
        k: "q",
        label: `Recherche: ${active.q}`
      });
      if (active.brand) items.push({
        k: "brand",
        label: `Marque: ${active.brand}`
      });
      if (active.family) items.push({
        k: "family",
        label: `Famille: ${active.family}`
      });
      if (active.vehicle) items.push({
        k: "vehicle",
        label: `Véhicule: ${active.vehicle}`
      });
      if (active.yearMin) items.push({
        k: "yearMin",
        label: `Année min: ${active.yearMin}`
      });
      if (active.yearMax) items.push({
        k: "yearMax",
        label: `Année max: ${active.yearMax}`
      });
      if (active.priceMin) items.push({
        k: "priceMin",
        label: `Prix min: ${active.priceMin}`
      });
      if (active.priceMax) items.push({
        k: "priceMax",
        label: `Prix max: ${active.priceMax}`
      });
      if (active.stockMode === "in") items.push({
        k: "stockMode",
        label: "Stock: En stock"
      });
      if (active.stockMode === "out") items.push({
        k: "stockMode",
        label: "Stock: Rupture"
      });
      if (active.sort && active.sort !== "relevance") items.push({
        k: "sort",
        label: `Tri: ${sortLabel(active.sort)}`
      });
      if (active.priceMode) items.push({
        k: "priceMode",
        label: `Prix: ${active.priceMode === "inc" ? "TTC" : "HT"}`
      });

      if (items.length === 0) {
        hint.textContent = "Aucun filtre actif. Astuce : cherchez par marque, véhicule, famille, UID…";
        return;
      }
      hint.textContent = "";

      for (const it of items) {
        const chip = document.createElement("span");
        chip.className = "chip";
        const t = document.createElement("span");
        t.textContent = it.label;

        const x = document.createElement("button");
        x.type = "button";
        x.title = "Retirer";
        x.innerHTML = `<i class="bi bi-x"></i>`;
        x.addEventListener("click", () => removeChip(it.k));

        chip.appendChild(t);
        chip.appendChild(x);
        chips.appendChild(chip);
      }
    }

    function removeChip(key) {
      if (key === "q") qTop.value = "";
      if (key === "brand") fBrand.value = "";
      if (key === "family") fFamily.value = "";
      if (key === "vehicle") fVehicle.value = "";
      if (key === "yearMin") fYearMin.value = "";
      if (key === "yearMax") fYearMax.value = "";
      if (key === "priceMin") fPriceMin.value = "";
      if (key === "priceMax") fPriceMax.value = "";
      if (key === "stockMode") el("smAll").checked = true;

      if (key === "sort") fSort.value = "relevance";
      if (key === "priceMode") {
        el("pmInc").checked = true;
        priceMode = "inc";
        updatePdfUi();
        updateCartUi();
      }
      applyFilters();
    }

    /***********************
     * CSV LOADING
     ***********************/
    async function loadCsvFromUrl(url) {
      renderSkeleton();
      try {
        const res = await fetch(url, {
          cache: "no-store"
        });
        if (!res.ok) throw new Error("HTTP " + res.status);

        // ✅ date réelle du fichier côté serveur (si le serveur l’envoie)
        const lm = res.headers.get("Last-Modified");
        CSV_FILE_LAST_MODIFIED = lm ? new Date(lm) : null;

        const text = await res.text();
        parseCsvText(text);

        // parseCsvText appelle déjà updateCsvLastUpdateUi(),
        // mais au cas où :
        updateCsvLastUpdateUi();

        toast("CSV chargé.");
      } catch (e) {
        grid.innerHTML = "";
        toast("Chargement URL impossible (chemin, CORS ou serveur). Essayez l’upload du fichier.");
        console.error(e);
      }
    }


    function parseCsvText(text) {
      renderSkeleton();

      const parseWith = (delimiter) => new Promise((resolve, reject) => {
        Papa.parse(text, {
          header: true,
          delimiter,
          skipEmptyLines: true,
          transformHeader: (h) => stripQuotes(h),
          transform: (v) => stripQuotes(v),
          complete: resolve,
          error: reject
        });
      });

      (async () => {
        let results = await parseWith(";");
        const fields = results?.meta?.fields || [];
        if (fields.length < 5) results = await parseWith(",");

        allRows = (results.data || [])
          .map(normalizeRowKeys)
          .filter(r => Object.values(r).some(v => String(v || "").trim().length));

        allRowsByKey = new Map();
        for (const r of allRows) {
          r.__search = buildSearchBlob(r);
          const k = rowKey(r);
          r.__key = k;
          if (k) allRowsByKey.set(k, r);
        }

        total.textContent = String(allRows.length);
        updateCsvLastUpdateUi();



        buildFacets();
        hydrateFromQueryString();

        restorePdfSelection();
        restoreCart();

        buildFacetCounts();
        applyFilters();
      })().catch((err) => {
        toast("Erreur parsing CSV : " + (err?.message || err));
        console.error(err);
      });
    }

    function buildFacets() {
      const brands = [];
      const families = [];
      const vehicles = [];
      const years = [];

      for (const r of allRows) {
        const b = field(r, "xx_Marque");
        const f = field(r, "FamilyName");
        const v = field(r, "xx_Vehicule", "xx_VEHICULE");
        const y = toNumber(field(r, "xx_Annee"));
        if (b) brands.push(b);
        if (f) families.push(f);
        if (v) vehicles.push(v);
        if (Number.isFinite(y)) years.push(y);
      }

      facets.brands = uniqueSorted(brands);
      facets.families = uniqueSorted(families);
      facets.vehicles = uniqueSorted(vehicles);
      facets.years = Array.from(new Set(years)).sort((a, b) => a - b);

      fillSelect(fBrand, facets.brands, "Toutes");
      fillSelect(fFamily, facets.families, "Toutes");
      fillSelect(fVehicle, facets.vehicles, "Tous");
    }

    function fillSelect(select, values, allLabel) {
      const prev = select.value;
      select.innerHTML = "";
      const optAll = document.createElement("option");
      optAll.value = "";
      optAll.textContent = allLabel;
      select.appendChild(optAll);

      for (const item of values) {
        const o = document.createElement("option");
        o.value = item;
        o.textContent = item;
        select.appendChild(o);
      }

      if (prev && values.includes(prev)) select.value = prev;
    }

    /***********************
     * FILTERING
     ***********************/
    function getActiveFilters() {
      const q = qTop.value.trim();
      const brand = fBrand.value.trim();
      const family = fFamily.value.trim();
      const vehicle = fVehicle.value.trim();

      const yearMin = toNumber(fYearMin.value);
      const yearMax = toNumber(fYearMax.value);
      const priceMin = toNumber(fPriceMin.value);
      const priceMax = toNumber(fPriceMax.value);

      return {
        q,
        brand,
        family,
        vehicle,
        yearMin: Number.isFinite(yearMin) ? yearMin : null,
        yearMax: Number.isFinite(yearMax) ? yearMax : null,
        priceMin: Number.isFinite(priceMin) ? priceMin : null,
        priceMax: Number.isFinite(priceMax) ? priceMax : null,
        stockMode: document.querySelector('input[name="stockMode"]:checked')?.value || "all",
        sort: fSort.value,
        priceMode
      };
    }

    function applyFilters() {
      if (!allRows.length) {
        count.textContent = "0";
        grid.innerHTML = "";
        return;
      }

      const active = getActiveFilters();
      renderChips(active);

      const tokens = active.q ?
        active.q.toLowerCase().split(/\s+/).filter(Boolean) : [];

      let res = allRows.filter(r => {
        if (active.brand && field(r, "xx_Marque") !== active.brand) return false;
        if (active.family && field(r, "FamilyName") !== active.family) return false;
        if (active.vehicle && field(r, "xx_Vehicule", "xx_VEHICULE") !== active.vehicle) return false;

        const y = toNumber(field(r, "xx_Annee"));
        if (active.yearMin !== null) {
          if (!Number.isFinite(y) || y < active.yearMin) return false;
        }
        if (active.yearMax !== null) {
          if (!Number.isFinite(y) || y > active.yearMax) return false;
        }

        const st = getStock(r);
        const isInStock = (Number.isFinite(st) && st > 0);
        if (active.stockMode === "in" && !isInStock) return false;
        if (active.stockMode === "out" && isInStock) return false;

        const pr = getPrice(r);
        if (active.priceMin !== null) {
          if (!Number.isFinite(pr) || pr < active.priceMin) return false;
        }
        if (active.priceMax !== null) {
          if (!Number.isFinite(pr) || pr > active.priceMax) return false;
        }

        if (tokens.length) {
          const blob = r.__search || "";
          for (const t of tokens) {
            if (!blob.includes(t)) return false;
          }
        }

        return true;
      });

      res = sortRows(res, active);

      filteredRows = res;
      count.textContent = String(filteredRows.length);
      renderGrid(filteredRows);
      persistToQueryString(active);
    }

    function sortRows(rows, active) {
      const copy = rows.slice();

      if (active.sort === "priceAsc") {
        copy.sort((a, b) => (getPrice(a) || Infinity) - (getPrice(b) || Infinity));
        return copy;
      }
      if (active.sort === "priceDesc") {
        copy.sort((a, b) => (getPrice(b) || -Infinity) - (getPrice(a) || -Infinity));
        return copy;
      }
      if (active.sort === "stockDesc") {
        copy.sort((a, b) => (getStock(b) || -Infinity) - (getStock(a) || -Infinity));
        return copy;
      }
      if (active.sort === "az") {
        copy.sort((a, b) => field(a, "DesComClear").localeCompare(field(b, "DesComClear"), "fr"));
        return copy;
      }

      const tokens = active.q ? active.q.toLowerCase().split(/\s+/).filter(Boolean) : [];
      if (!tokens.length) return copy;

      copy.sort((a, b) => {
        const sa = a.__search || "";
        const sb = b.__search || "";
        const ma = tokens.reduce((acc, t) => acc + (sa.includes(t) ? 1 : 0), 0);
        const mb = tokens.reduce((acc, t) => acc + (sb.includes(t) ? 1 : 0), 0);
        if (mb !== ma) return mb - ma;

        const sta = getStock(a),
          stb = getStock(b);
        const stockA = Number.isFinite(sta) ? sta : -1;
        const stockB = Number.isFinite(stb) ? stb : -1;
        if (stockB !== stockA) return stockB - stockA;

        return field(a, "DesComClear").localeCompare(field(b, "DesComClear"), "fr");
      });

      return copy;
    }

    /***********************
     * URL STATE
     ***********************/
    function persistToQueryString(active) {
      const p = new URLSearchParams();
      if (active.q) p.set("q", active.q);
      if (active.brand) p.set("brand", active.brand);
      if (active.family) p.set("family", active.family);
      if (active.vehicle) p.set("vehicle", active.vehicle);
      if (active.yearMin !== null) p.set("ymin", String(active.yearMin));
      if (active.yearMax !== null) p.set("ymax", String(active.yearMax));
      if (active.priceMin !== null) p.set("pmin", String(active.priceMin));
      if (active.priceMax !== null) p.set("pmax", String(active.priceMax));
      if (active.stockMode && active.stockMode !== "all") p.set("sm", active.stockMode);
      if (active.sort && active.sort !== "relevance") p.set("sort", active.sort);
      if (active.priceMode && active.priceMode !== "inc") p.set("pm", active.priceMode);

      const newUrl = window.location.pathname + (p.toString() ? ("?" + p.toString()) : "");
      window.history.replaceState({}, "", newUrl);
    }

    function hydrateFromQueryString() {
      const p = new URLSearchParams(window.location.search);

      qTop.value = p.get("q") || "";
      fBrand.value = p.get("brand") || "";
      fFamily.value = p.get("family") || "";
      fVehicle.value = p.get("vehicle") || "";
      fYearMin.value = p.get("ymin") || "";
      fYearMax.value = p.get("ymax") || "";
      fPriceMin.value = p.get("pmin") || "";
      fPriceMax.value = p.get("pmax") || "";

      const sm = p.get("sm") || "all";
      if (sm === "in") el("smIn").checked = true;
      else if (sm === "out") el("smOut").checked = true;
      else el("smAll").checked = true;

      fSort.value = p.get("sort") || "relevance";

      const pm = p.get("pm");
      if (pm === "exc") {
        el("pmExc").checked = true;
        priceMode = "exc";
      } else {
        el("pmInc").checked = true;
        priceMode = "inc";
      }
    }

    /***********************
     * RESET
     ***********************/
    function resetFilters() {
      qTop.value = "";
      fBrand.value = "";
      fFamily.value = "";
      fVehicle.value = "";
      fYearMin.value = "";
      fYearMax.value = "";
      fPriceMin.value = "";
      fPriceMax.value = "";
      el("smAll").checked = true;

      fSort.value = "relevance";
      el("pmInc").checked = true;
      priceMode = "inc";

      updatePdfUi();
      updateCartUi();
      applyFilters();
    }

    /***********************
     * WIZARD (commande)
     ***********************/
    function wizSetStep(n) {
      wizStep = Math.max(0, Math.min(4, n));

      const titles = ["Nom du commercial", "Nom du client", "Adresse client", "Téléphone client", "Récapitulatif"];
      const descs = [
        "Ce champ est obligatoire pour identifier le commercial.",
        "Ce champ est obligatoire pour identifier le client.",
        "Optionnel — ajoute une adresse si besoin.",
        "Optionnel — ajoute un numéro si besoin.",
        "Vérifie puis clique sur Envoyer."
      ];

      wizStepPill.textContent = `Étape ${wizStep+1}/5`;
      wizStepTitle.textContent = titles[wizStep];
      wizStepDesc.textContent = descs[wizStep];

      document.querySelectorAll(".wiz-step").forEach(node => {
        const s = Number(node.getAttribute("data-step"));
        node.classList.toggle("d-none", s !== wizStep);
      });

      wizBack.disabled = wizStep === 0;
      wizNext.classList.toggle("d-none", wizStep === 4);
      wizSend.classList.toggle("d-none", wizStep !== 4);

      wizValidateStep();
      if (wizStep === 4) wizFillRecap();
    }

    function wizValidateStep() {
      const cartIsEmpty = cart.size === 0;
      wizCartEmptyWarn.classList.toggle("d-none", !cartIsEmpty);
      wizNext.disabled = cartIsEmpty && wizStep < 4;
      wizSend.disabled = cartIsEmpty && wizStep === 4;

      const reqSales = (wizSales.value || "").trim();
      const reqClient = (wizClient.value || "").trim();

      wizSales.classList.remove("is-invalid");
      wizClient.classList.remove("is-invalid");

      let ok = true;
      if (wizStep === 0 && !reqSales) {
        ok = false;
        wizSales.classList.add("is-invalid");
      }
      if (wizStep === 1 && !reqClient) {
        ok = false;
        wizClient.classList.add("is-invalid");
      }

      if (wizStep < 4) wizNext.disabled = !ok || cartIsEmpty;
      else wizSend.disabled = cartIsEmpty || !reqSales || !reqClient;

      return ok && !cartIsEmpty;
    }

    function wizFillRecap() {
      wizRecSales.textContent = (wizSales.value || "").trim() || "—";
      wizRecClient.textContent = (wizClient.value || "").trim() || "—";
      wizRecAddress.textContent = (wizAddress.value || "").trim() || "—";
      wizRecPhone.textContent = (wizPhone.value || "").trim() || "—";
      wizRecTotal.textContent = hidePrices ? "Prix masqué" : fmtMoney(computeCartTotal());
      wizValidateStep();
    }

    function openWizard() {
      wizSales.value = "";
      wizClient.value = "";
      wizAddress.value = "";
      wizPhone.value = "";

      wizSetStep(0);
      const m = bootstrap.Modal.getOrCreateInstance(orderWizardEl);
      m.show();
      setTimeout(() => wizSales.focus(), 150);
    }

    wizBack?.addEventListener("click", () => wizSetStep(wizStep - 1));
    wizNext?.addEventListener("click", () => {
      if (!wizValidateStep()) return;
      wizSetStep(wizStep + 1);
      if (wizStep === 1) setTimeout(() => wizClient.focus(), 100);
      if (wizStep === 2) setTimeout(() => wizAddress.focus(), 100);
      if (wizStep === 3) setTimeout(() => wizPhone.focus(), 100);
    });

    [wizSales, wizClient].forEach(inp => inp?.addEventListener("input", wizValidateStep));

    wizSend?.addEventListener("click", () => {
      if (!wizValidateStep()) return;

      const sales = (wizSales.value || "").trim();
      const client = (wizClient.value || "").trim();
      const address = (wizAddress.value || "").trim();
      const phone = (wizPhone.value || "").trim();

      const lines = [];
      lines.push("COMMANDE");
      lines.push("------------------------------");
      lines.push(`Commercial: ${sales}`);
      lines.push(`Client: ${client}`);
      if (address) lines.push(`Adresse: ${address}`);
      if (phone) lines.push(`Téléphone: ${phone}`);
      lines.push("");

      lines.push(hidePrices ? "Mode prix: PRIX MASQUÉS" : `Mode prix: ${priceMode === "inc" ? "TTC" : "HT"}`);
      lines.push(`Date: ${new Date().toLocaleString(LOCALE)}`);
      lines.push("");
      lines.push("Articles:");
      lines.push("------------------------------");

      for (const {
          row,
          qty
        }
        of cart.values()) {
        const cap = field(row, "DesComClear") || "Produit";
        const uid = field(row, "UniqueId");
        const brand = field(row, "xx_Marque");
        lines.push(`- ${uid ? uid + " — " : ""}${cap}${brand ? " (" + brand + ")" : ""}`);
        lines.push(`  Qté: ${qty}${hidePrices ? "" : (" | PU: " + fmtMoney(getPrice(row)) + " | Total: " + fmtMoney(getPrice(row) * qty))}`);
      }

      lines.push("");
      lines.push("------------------------------");
      lines.push(hidePrices ? "TOTAL: Prix masqué" : `TOTAL: ${fmtMoney(computeCartTotal())}`);

      const subject = `Commande - ${client}`;
      const body = lines.join("\n");

      const mailto = `mailto:${encodeURIComponent(ORDER_EMAIL_TO)}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
      window.location.href = mailto;
      toast("Ouverture de votre messagerie pour envoyer la commande.");
    });

    btnCartCheckout?.addEventListener("click", () => {
      const off = bootstrap.Offcanvas.getOrCreateInstance(el("cartCanvas"));
      off.hide();
      setTimeout(openWizard, 250);
    });

    /***********************
     * EVENTS (filters / top)
     ***********************/
    const applyDebounced = debounce(applyFilters, 180);

    qTop?.addEventListener("input", applyDebounced);
    btnClearQ?.addEventListener("click", () => {
      qTop.value = "";
      applyFilters();
    });

    [fBrand, fFamily, fVehicle, fSort].forEach(x => x?.addEventListener("change", applyFilters));
    [fYearMin, fYearMax, fPriceMin, fPriceMax].forEach(x => x?.addEventListener("input", applyDebounced));

    document.querySelectorAll('input[name="stockMode"]').forEach(r => r.addEventListener("change", applyFilters));

    document.querySelectorAll("input[name=priceMode]").forEach(r => {
      r.addEventListener("change", () => {
        priceMode = document.querySelector("input[name=priceMode]:checked").value;
        updatePdfUi();
        updateCartUi();
        applyFilters();
      });
    });

    hidePricesToggle?.addEventListener("change", () => setHidePrices(hidePricesToggle.checked));

    btnReset?.addEventListener("click", resetFilters);
    btnClearAll?.addEventListener("click", resetFilters);

    btnCopyUrl?.addEventListener("click", async () => {
      try {
        await navigator.clipboard.writeText(window.location.href);
        toast("Lien (avec filtres) copié.");
      } catch {
        toast("Impossible de copier le lien.");
      }
    });

    btnLoad?.addEventListener("click", () => {
      const url = csvUrl.value.trim();
      if (!url) return toast("Veuillez saisir une URL CSV.");
      loadCsvFromUrl(url);
    });

    csvFile?.addEventListener("change", async (e) => {
      const file = e.target.files?.[0];
      CSV_FILE_LAST_MODIFIED = file?.lastModified ? new Date(file.lastModified) : null;


      if (!file) return;
      renderSkeleton();
      const text = await file.text();
      parseCsvText(text);
      toast("CSV chargé depuis le fichier.");
    });

    /***********************
     * RESPONSIVE FILTERS (move same card)
     ***********************/
    function mountFiltersResponsive() {
      const card = el("filtersCard");
      const desktopMount = el("filtersDesktopMount");
      const mobileMount = el("filtersClone");
      if (!card || !desktopMount || !mobileMount) return;

      const isMobile = window.matchMedia("(max-width: 991.98px)").matches;
      if (isMobile) {
        mobileMount.appendChild(card);
        card.classList.remove("sticky-filters");
      } else {
        desktopMount.appendChild(card);
        card.classList.add("sticky-filters");
      }
    }

    /***********************
     * INIT
     ***********************/
    (function init() {
      csvUrl.value = DEFAULT_CSV_URL;

      restoreHidePrices();
      updatePdfUi();
      updateCartUi();

      mountFiltersResponsive();
      window.addEventListener("resize", debounce(mountFiltersResponsive, 150));

      renderSkeleton();
      loadCsvFromUrl(DEFAULT_CSV_URL);
    })();
  </script>

  <!-- Modal : Photo Viewer -->
  <div class="modal fade photo-modal" id="photoViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-sm-down">
      <div class="modal-content">
        <div class="modal-header">
          <div class="d-flex align-items-center gap-2" style="min-width:0;">
            <span class="badge rounded-pill px-3 py-2"
              style="background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.22);">
              <i class="bi bi-image"></i> Photo
            </span>

            <div style="min-width:0;">
              <div class="fw-semibold text-truncate" id="pvTitle">—</div>
              <div class="pv-meta text-truncate" id="pvMeta">—</div>
            </div>
          </div>

          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-light" type="button" id="pvPrev" title="Précédent">
              <i class="bi bi-chevron-left"></i>
            </button>
            <button class="btn btn-sm btn-outline-light" type="button" id="pvNext" title="Suivant">
              <i class="bi bi-chevron-right"></i>
            </button>

            <div class="btn-group ms-1" role="group" aria-label="Zoom">
              <button class="btn btn-sm btn-outline-light" type="button" id="pvFit" title="Ajuster">
                <i class="bi bi-aspect-ratio"></i>
              </button>
              <button class="btn btn-sm btn-outline-light" type="button" id="pv100" title="100% (scroll)">
                100%
              </button>
            </div>

            <a class="btn btn-sm btn-outline-light ms-1" id="pvOpenNew" target="_blank" rel="noopener" title="Ouvrir dans un onglet">
              <i class="bi bi-box-arrow-up-right"></i>
            </a>

            <button type="button" class="btn-close btn-close-white ms-1" data-bs-dismiss="modal" aria-label="Fermer"></button>
          </div>
        </div>

        <div class="modal-body">
          <div class="pv-stage" id="pvStage">
            <div class="spinner-border text-light" role="status" id="pvSpinner" style="display:none">
              <span class="visually-hidden">Chargement...</span>
            </div>
            <img id="pvImg" alt="">
          </div>

          <div class="pv-footer px-3 py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="small">
              Astuce : <span class="opacity-75">← →</span> pour naviguer • <span class="opacity-75">100%</span> pour scroll
            </div>
            <div class="small opacity-75" id="pvCounter">—</div>
          </div>
        </div>

      </div>
    </div>
  </div>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const modalEl = document.getElementById("photoViewerModal");
      if (!modalEl) return;

      const pvModal = bootstrap.Modal.getOrCreateInstance(modalEl);

      const pvTitle = document.getElementById("pvTitle");
      const pvMeta = document.getElementById("pvMeta");
      const pvImg = document.getElementById("pvImg");
      const pvSpinner = document.getElementById("pvSpinner");
      const pvCounter = document.getElementById("pvCounter");
      const pvOpenNew = document.getElementById("pvOpenNew");
      const pvStage = document.getElementById("pvStage");

      const btnPrev = document.getElementById("pvPrev");
      const btnNext = document.getElementById("pvNext");
      const btnFit = document.getElementById("pvFit");
      const btn100 = document.getElementById("pv100");

      let pvKeys = [];
      let pvIndex = 0;
      let pvMode = "fit"; // "fit" | "100"

      function currentKeys() {
        const src = (typeof filteredRows !== "undefined" && filteredRows?.length) ? filteredRows : allRows;
        return (src || []).map(r => rowKey(r)).filter(Boolean);
      }

      function setMode(mode) {
        pvMode = mode;
        pvStage?.classList.toggle("pv-mode-100", pvMode === "100");
      }

      function tryLoadImage(url) {
        return new Promise((resolve, reject) => {
          const im = new Image();
          im.onload = () => resolve(url);
          im.onerror = reject;
          im.src = url;
        });
      }

      async function loadAt(idx) {
        if (!pvKeys.length) return;

        pvIndex = (idx + pvKeys.length) % pvKeys.length;
        const key = pvKeys[pvIndex];
        const r = allRowsByKey.get(key);
        if (!r) return;

        // Header infos
        const cap = field(r, "DesComClear") || "Produit";
        const meta = [
          displayId(r),
          field(r, "xx_Marque"),
          field(r, "FamilyName")
        ].filter(Boolean).join(" • ");

        pvTitle.textContent = cap;
        pvMeta.textContent = meta || "—";
        pvCounter.textContent = `${pvIndex + 1} / ${pvKeys.length}`;

        // Loading state
        pvSpinner.style.display = "block";
        pvImg.style.display = "none";
        pvOpenNew.removeAttribute("href");
        pvImg.removeAttribute("src");

        setMode(pvMode);

        // Try candidates
        const cands = getImageCandidates(r);
        let okUrl = "";

        for (const url of (cands || [])) {
          try {
            okUrl = await tryLoadImage(url);
            break;
          } catch {}
        }

        pvSpinner.style.display = "none";
        pvImg.style.display = "block";

        if (!okUrl) {
          // pas d’image -> on laisse vide (tu peux mettre une image placeholder si tu veux)
          pvImg.alt = "Photo indisponible";
          return;
        }

        pvImg.alt = cap;
        pvImg.src = okUrl;
        pvOpenNew.href = okUrl;
      }

      function openByKey(key) {
        pvKeys = currentKeys();
        const i = pvKeys.indexOf(key);
        pvIndex = (i >= 0) ? i : 0;
        loadAt(pvIndex);
        pvModal.show();
      }

      function prev() {
        loadAt(pvIndex - 1);
      }

      function next() {
        loadAt(pvIndex + 1);
      }

      // Click sur vignette -> ouvre modal
      document.getElementById("grid")?.addEventListener("click", (e) => {
        const thumb = e.target.closest(".thumb");
        if (!thumb) return;

        const card = thumb.closest(".catalog-card[data-key]");
        const key = card?.getAttribute("data-key");
        if (!key) return;

        openByKey(key);
      });

      btnPrev?.addEventListener("click", prev);
      btnNext?.addEventListener("click", next);

      btnFit?.addEventListener("click", () => setMode("fit"));
      btn100?.addEventListener("click", () => setMode("100"));

      // Navigation clavier
      document.addEventListener("keydown", (e) => {
        if (!modalEl.classList.contains("show")) return;
        if (e.key === "ArrowLeft") prev();
        if (e.key === "ArrowRight") next();
      });
    });
  </script>

</body>

</html>