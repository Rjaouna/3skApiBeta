<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* order/show.html.twig */
class __TwigTemplate_db4e66158b98cc6687d0d61a5ec0ec3a extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'body' => [$this, 'block_body'],
        ];
    }

    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 1
        return "base.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        $__internal_5a27a8ba21ca79b61932376b2fa922d2 = $this->extensions["Symfony\\Bundle\\WebProfilerBundle\\Twig\\WebProfilerExtension"];
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "order/show.html.twig"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "order/show.html.twig"));

        $this->parent = $this->load("base.html.twig", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->leave($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof);

        
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->leave($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof);

    }

    // line 3
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_body(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        $__internal_5a27a8ba21ca79b61932376b2fa922d2 = $this->extensions["Symfony\\Bundle\\WebProfilerBundle\\Twig\\WebProfilerExtension"];
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "block", "body"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "block", "body"));

        // line 4
        $context["hasDiscount"] = (((CoreExtension::getAttribute($this->env, $this->source, ($context["order"] ?? null), "discountAmountTtc", [], "any", true, true, false, 4) &&  !(null === CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 4, $this->source); })()), "discountAmountTtc", [], "any", false, false, false, 4))) && (CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 4, $this->source); })()), "discountAmountTtc", [], "any", false, false, false, 4) != "0.00")) && (CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 4, $this->source); })()), "discountAmountTtc", [], "any", false, false, false, 4) != "0"));
        // line 5
        yield "
";
        // line 6
        $context["statusClass"] = (((CoreExtension::getAttribute($this->env, $this->source,         // line 7
(isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 7, $this->source); })()), "status", [], "any", false, false, false, 7) == "new")) ? ("status-new") : ((((CoreExtension::getAttribute($this->env, $this->source,         // line 8
(isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 8, $this->source); })()), "status", [], "any", false, false, false, 8) == "preparing")) ? ("status-preparing") : ((((CoreExtension::getAttribute($this->env, $this->source,         // line 9
(isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 9, $this->source); })()), "status", [], "any", false, false, false, 9) == "shipping")) ? ("status-shipping") : ((((CoreExtension::getAttribute($this->env, $this->source,         // line 10
(isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 10, $this->source); })()), "status", [], "any", false, false, false, 10) == "completed")) ? ("status-completed") : ("status-cancelled"))))))));
        // line 12
        yield "
";
        // line 13
        $context["statusIcon"] = (((CoreExtension::getAttribute($this->env, $this->source,         // line 14
(isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 14, $this->source); })()), "status", [], "any", false, false, false, 14) == "new")) ? ("fa-file-circle-plus") : ((((CoreExtension::getAttribute($this->env, $this->source,         // line 15
(isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 15, $this->source); })()), "status", [], "any", false, false, false, 15) == "preparing")) ? ("fa-boxes-packing") : ((((CoreExtension::getAttribute($this->env, $this->source,         // line 16
(isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 16, $this->source); })()), "status", [], "any", false, false, false, 16) == "shipping")) ? ("fa-truck-fast") : ((((CoreExtension::getAttribute($this->env, $this->source,         // line 17
(isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 17, $this->source); })()), "status", [], "any", false, false, false, 17) == "completed")) ? ("fa-circle-check") : ("fa-ban"))))))));
        // line 19
        yield "
<style>
  :root{
    --oc-radius: 22px;
    --oc-border: rgba(0,0,0,.10);
    --oc-shadow: 0 14px 34px rgba(0,0,0,.08);
    --oc-muted: rgba(0,0,0,.58);
    --oc-text: rgba(0,0,0,.84);
    --oc-glass: rgba(255,255,255,.82);
    --oc-glass-2: rgba(255,255,255,.60);
  }

  body.theme-dark{
    --oc-border: rgba(255,255,255,.14);
    --oc-shadow: 0 18px 46px rgba(0,0,0,.35);
    --oc-muted: rgba(255,255,255,.62);
    --oc-text: rgba(255,255,255,.88);
    --oc-glass: rgba(18,18,22,.76);
    --oc-glass-2: rgba(18,18,22,.58);
  }

  .order-show-hero{
    border-radius: 24px;
    border: 1px solid var(--oc-border);
    box-shadow: var(--oc-shadow);
    padding: 18px;
    background:
      radial-gradient(900px circle at 10% 10%, rgba(var(--bs-primary-rgb), .16), transparent 40%),
      radial-gradient(900px circle at 90% 30%, rgba(var(--bs-secondary-rgb), .14), transparent 38%),
      linear-gradient(180deg, rgba(0,0,0,.02), transparent);
  }

  .order-show-hero h3{
    margin: 0;
    font-weight: 650;
    color: var(--oc-text);
  }

  .order-show-hero .subtitle{
    margin-top: 6px;
    color: var(--oc-muted);
    font-size: .92rem;
    font-weight: 600;
  }

  .order-show-actions{
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .order-show-actions .btn{
    min-height: 46px;
    border-radius: 14px;
    font-weight: 600;
    padding-inline: 16px;
  }

  .order-show-card{
    border-radius: var(--oc-radius);
    border: 1px solid var(--oc-border);
    box-shadow: var(--oc-shadow);
    background: var(--oc-glass);
    backdrop-filter: blur(8px);
  }

  .order-show-card .card-body{
    padding: 18px;
  }

  .section-title{
    font-size: .82rem;
    font-weight: 600;
    color: var(--oc-muted);
    margin-bottom: 14px;
    text-transform: uppercase;
    letter-spacing: .03em;
  }

  .order-lines-table{
    margin: 0;
  }

  .order-lines-table thead th{
    border-bottom: 1px solid var(--oc-border);
    color: var(--oc-muted);
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    font-weight: 600;
    padding: 14px 12px;
    white-space: nowrap;
    background: transparent;
  }

  .order-lines-table tbody tr{
    transition: background .12s ease;
  }

  .order-lines-table tbody tr:hover{
    background: rgba(var(--bs-primary-rgb), .04);
  }

  .order-lines-table tbody td{
    padding: 16px 12px;
    border-color: rgba(0,0,0,.06);
    vertical-align: middle;
    color: var(--oc-text);
  }

  body.theme-dark .order-lines-table tbody td{
    border-color: rgba(255,255,255,.08);
  }

  .line-code{
    font-weight: 650;
    color: var(--bs-primary);
    line-height: 1.2;
  }

  .line-designation{
    margin-top: 4px;
    font-size: .84rem;
    color: var(--oc-muted);
    font-weight: 700;
  }

  .line-value{
    font-weight: 600;
    color: var(--oc-text);
    white-space: nowrap;
  }

  .summary-box,
  .address-box{
    border-radius: 18px;
    border: 1px solid var(--oc-border);
    background: rgba(255,255,255,.48);
    padding: 14px;
  }

  body.theme-dark .summary-box,
  body.theme-dark .address-box{
    background: rgba(255,255,255,.04);
  }

  .summary-row{
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
  }

  .summary-row:last-child{
    margin-bottom: 0;
  }

  .summary-label{
    color: var(--oc-muted);
    font-size: .88rem;
    font-weight: 550;
  }

  .summary-value{
    color: var(--oc-text);
    font-size: .92rem;
    font-weight: 600;
    text-align: end;
  }

  .summary-total{
    font-size: 1.15rem;
    font-weight: 650;
    color: var(--oc-text);
  }

  .address-line{
    color: var(--oc-text);
    font-weight: 700;
    line-height: 1.5;
    word-break: break-word;
  }

  .address-line.muted{
    color: var(--oc-muted);
  }

  .meta-badge{
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border-radius: 999px;
    padding: 8px 12px;
    border: 1px solid var(--oc-border);
    background: var(--oc-glass-2);
    color: var(--oc-text);
    font-size: .82rem;
    font-weight: 600;
  }

  .meta-badge i{
    color: var(--bs-primary);
  }

  .status-pill{
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border-radius: 999px;
    padding: 7px 12px;
    font-size: .78rem;
    font-weight: 600;
    border: 1px solid transparent;
    white-space: nowrap;
  }

  .status-pill i{
    font-size: .78rem;
  }

  .status-new{
    background: rgba(13,110,253,.12);
    color: rgba(13,110,253,1);
    border-color: rgba(13,110,253,.18);
  }

  .status-preparing{
    background: rgba(255,193,7,.16);
    color: rgba(176,117,0,.98);
    border-color: rgba(255,193,7,.24);
  }

  .status-shipping{
    background: rgba(111,66,193,.14);
    color: rgba(111,66,193,1);
    border-color: rgba(111,66,193,.22);
  }

  .status-completed{
    background: rgba(25,135,84,.12);
    color: rgba(25,135,84,1);
    border-color: rgba(25,135,84,.20);
  }

  .status-cancelled{
    background: rgba(220,53,69,.12);
    color: rgba(220,53,69,.98);
    border-color: rgba(220,53,69,.20);
  }

  .empty-lines{
    border-radius: 20px;
    border: 1px dashed var(--oc-border);
    background: rgba(255,255,255,.42);
    padding: 44px 18px;
    text-align: center;
    color: var(--oc-muted);
  }

  body.theme-dark .empty-lines{
    background: rgba(255,255,255,.03);
  }

  .empty-lines i{
    font-size: 2rem;
    margin-bottom: 12px;
    color: var(--bs-secondary);
  }

  .mobile-lines{
    display: none;
  }

  .mobile-line-card{
    border-radius: 18px;
    border: 1px solid var(--oc-border);
    background: rgba(255,255,255,.52);
    padding: 14px;
  }

  body.theme-dark .mobile-line-card{
    background: rgba(255,255,255,.04);
  }

  .mobile-line-top{
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
  }

  .mobile-line-code{
    font-weight: 650;
    color: var(--bs-primary);
    line-height: 1.2;
  }

  .mobile-line-designation{
    font-size: .82rem;
    color: var(--oc-muted);
    font-weight: 700;
    margin-top: 4px;
  }

  .mobile-line-grid{
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 10px;
  }

  .mobile-line-box{
    border-radius: 14px;
    border: 1px solid var(--oc-border);
    padding: 10px 12px;
    background: rgba(255,255,255,.55);
  }

  body.theme-dark .mobile-line-box{
    background: rgba(18,18,22,.50);
  }

  .mobile-line-label{
    font-size: .72rem;
    color: var(--oc-muted);
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 4px;
  }

  .mobile-line-value{
    font-size: .88rem;
    color: var(--oc-text);
    font-weight: 550;
    line-height: 1.35;
    word-break: break-word;
  }

  @media (max-width: 991.98px){
    .desktop-lines{
      display: none;
    }

    .mobile-lines{
      display: grid;
      gap: 12px;
    }
  }

  @media (max-width: 767.98px){
    .order-show-actions{
      width: 100%;
    }

    .order-show-actions .btn{
      width: 100%;
    }
  }
</style>

<div class=\"container py-4\">
  <div class=\"order-show-hero mb-3 d-flex align-items-start justify-content-between flex-wrap gap-3\">
    <div>
      <h3>Commande ";
        // line 383
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 383, $this->source); })()), "orderNumber", [], "any", false, false, false, 383), "html", null, true);
        yield "</h3>
      <div class=\"subtitle\">
        Passée le ";
        // line 385
        yield (((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 385, $this->source); })()), "createdAt", [], "any", false, false, false, 385)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 385, $this->source); })()), "createdAt", [], "any", false, false, false, 385), "d/m/Y H:i"), "html", null, true)) : ("—"));
        yield "
      </div>
    </div>

    <div class=\"d-flex align-items-center gap-2 flex-wrap\">
      <span class=\"status-pill ";
        // line 390
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["statusClass"]) || array_key_exists("statusClass", $context) ? $context["statusClass"] : (function () { throw new RuntimeError('Variable "statusClass" does not exist.', 390, $this->source); })()), "html", null, true);
        yield "\">
        <i class=\"fa-solid ";
        // line 391
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["statusIcon"]) || array_key_exists("statusIcon", $context) ? $context["statusIcon"] : (function () { throw new RuntimeError('Variable "statusIcon" does not exist.', 391, $this->source); })()), "html", null, true);
        yield "\"></i>
        ";
        // line 392
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 392, $this->source); })()), "statusLabel", [], "any", false, false, false, 392), "html", null, true);
        yield "
      </span>

      <div class=\"meta-badge\">
        <i class=\"fa-solid fa-receipt\"></i>
        <span>Total : ";
        // line 397
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 397, $this->source); })()), "totalTtc", [], "any", false, false, false, 397), "html", null, true);
        yield " DH</span>
      </div>
    </div>
  </div>

  <div class=\"mb-3 order-show-actions\">
    <a class=\"btn btn-outline-secondary\" href=\"";
        // line 403
        yield $this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("order_index");
        yield "\">
      <i class=\"fa-solid fa-arrow-left me-2\"></i>Retour
    </a>
    <a class=\"btn btn-outline-primary\" href=\"";
        // line 406
        yield $this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("cart_index");
        yield "\">
      <i class=\"fa-solid fa-cart-shopping me-2\"></i>Panier
    </a>
  </div>

  <div class=\"row g-3\">
    <div class=\"col-lg-8\">
      <div class=\"order-show-card\">
        <div class=\"card-body\">
          <div class=\"section-title\">Lignes de commande</div>

          ";
        // line 417
        if (Twig\Extension\CoreExtension::testEmpty(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 417, $this->source); })()), "lines", [], "any", false, false, false, 417))) {
            // line 418
            yield "            <div class=\"empty-lines\">
              <i class=\"fa-solid fa-box-open\"></i>
              <div class=\"fw-bold mb-1\">Aucune ligne</div>
              <div class=\"small\">Cette commande ne contient aucun article.</div>
            </div>
          ";
        } else {
            // line 424
            yield "            <div class=\"table-responsive desktop-lines\">
              <table class=\"table order-lines-table align-middle mb-0\">
                <thead>
                  <tr>
                    <th>Article</th>
                    <th class=\"text-end\">Qté</th>
                    <th class=\"text-end\">PU TTC</th>
                    <th class=\"text-end\">Total</th>
                  </tr>
                </thead>
                <tbody>
                  ";
            // line 435
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 435, $this->source); })()), "lines", [], "any", false, false, false, 435));
            foreach ($context['_seq'] as $context["_key"] => $context["line"]) {
                // line 436
                yield "                    <tr>
                      <td>
                        <div class=\"line-code\">";
                // line 438
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "articleCode", [], "any", false, false, false, 438), "html", null, true);
                yield "</div>
                        <div class=\"line-designation\">";
                // line 439
                yield ((CoreExtension::getAttribute($this->env, $this->source, $context["line"], "designation", [], "any", false, false, false, 439)) ? ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "designation", [], "any", false, false, false, 439), "html", null, true)) : ("—"));
                yield "</div>
                      </td>
                      <td class=\"text-end\">
                        <div class=\"line-value\">";
                // line 442
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "quantity", [], "any", false, false, false, 442), "html", null, true);
                yield "</div>
                      </td>
                      <td class=\"text-end\">
                        <div class=\"line-value\">";
                // line 445
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "unitPriceTtc", [], "any", false, false, false, 445), "html", null, true);
                yield " DH</div>
                      </td>
                      <td class=\"text-end\">
                        <div class=\"line-value\">";
                // line 448
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "lineTotalTtc", [], "any", false, false, false, 448), "html", null, true);
                yield " DH</div>
                      </td>
                    </tr>
                  ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['line'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 452
            yield "                </tbody>
              </table>
            </div>

            <div class=\"mobile-lines\">
              ";
            // line 457
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 457, $this->source); })()), "lines", [], "any", false, false, false, 457));
            foreach ($context['_seq'] as $context["_key"] => $context["line"]) {
                // line 458
                yield "                <div class=\"mobile-line-card\">
                  <div class=\"mobile-line-top\">
                    <div>
                      <div class=\"mobile-line-code\">";
                // line 461
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "articleCode", [], "any", false, false, false, 461), "html", null, true);
                yield "</div>
                      <div class=\"mobile-line-designation\">";
                // line 462
                yield ((CoreExtension::getAttribute($this->env, $this->source, $context["line"], "designation", [], "any", false, false, false, 462)) ? ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "designation", [], "any", false, false, false, 462), "html", null, true)) : ("—"));
                yield "</div>
                    </div>

                    <div class=\"meta-badge\">
                      <i class=\"fa-solid fa-cubes\"></i>
                      <span>x";
                // line 467
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "quantity", [], "any", false, false, false, 467), "html", null, true);
                yield "</span>
                    </div>
                  </div>

                  <div class=\"mobile-line-grid\">
                    <div class=\"mobile-line-box\">
                      <div class=\"mobile-line-label\">PU TTC</div>
                      <div class=\"mobile-line-value\">";
                // line 474
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "unitPriceTtc", [], "any", false, false, false, 474), "html", null, true);
                yield " DH</div>
                    </div>

                    <div class=\"mobile-line-box\">
                      <div class=\"mobile-line-label\">Total</div>
                      <div class=\"mobile-line-value\">";
                // line 479
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "lineTotalTtc", [], "any", false, false, false, 479), "html", null, true);
                yield " DH</div>
                    </div>
                  </div>
                </div>
              ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['line'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 484
            yield "            </div>
          ";
        }
        // line 486
        yield "        </div>
      </div>
    </div>

    <div class=\"col-lg-4\">
      <div class=\"d-grid gap-3\">
        <div class=\"order-show-card\">
          <div class=\"card-body\">
            <div class=\"section-title\">Résumé</div>

            <div class=\"summary-box\">
              <div class=\"summary-row\">
                <span class=\"summary-label\">Statut</span>
                <span class=\"status-pill ";
        // line 499
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["statusClass"]) || array_key_exists("statusClass", $context) ? $context["statusClass"] : (function () { throw new RuntimeError('Variable "statusClass" does not exist.', 499, $this->source); })()), "html", null, true);
        yield "\">
                  <i class=\"fa-solid ";
        // line 500
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["statusIcon"]) || array_key_exists("statusIcon", $context) ? $context["statusIcon"] : (function () { throw new RuntimeError('Variable "statusIcon" does not exist.', 500, $this->source); })()), "html", null, true);
        yield "\"></i>
                  ";
        // line 501
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 501, $this->source); })()), "statusLabel", [], "any", false, false, false, 501), "html", null, true);
        yield "
                </span>
              </div>

              <div class=\"summary-row\">
                <span class=\"summary-label\">Sous-total</span>
                <span class=\"summary-value\">";
        // line 507
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 507, $this->source); })()), "subtotalTtc", [], "any", false, false, false, 507), "html", null, true);
        yield " DH</span>
              </div>

              ";
        // line 510
        if ((($tmp = (isset($context["hasDiscount"]) || array_key_exists("hasDiscount", $context) ? $context["hasDiscount"] : (function () { throw new RuntimeError('Variable "hasDiscount" does not exist.', 510, $this->source); })())) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 511
            yield "                <div class=\"summary-row\">
                  <span class=\"summary-label\">
                    Remise";
            // line 513
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 513, $this->source); })()), "discountRate", [], "any", false, false, false, 513)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield " ";
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 513, $this->source); })()), "discountRate", [], "any", false, false, false, 513), "html", null, true);
                yield "%";
            }
            // line 514
            yield "                  </span>
                  <span class=\"summary-value text-danger\">-";
            // line 515
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 515, $this->source); })()), "discountAmountTtc", [], "any", false, false, false, 515), "html", null, true);
            yield " DH</span>
                </div>
              ";
        }
        // line 518
        yield "
              <hr>

              <div class=\"summary-row mb-0\">
                <span class=\"summary-label fw-bold\">Total final</span>
                <span class=\"summary-total\">";
        // line 523
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 523, $this->source); })()), "totalTtc", [], "any", false, false, false, 523), "html", null, true);
        yield " DH</span>
              </div>
            </div>
          </div>
        </div>

        <div class=\"order-show-card\">
          <div class=\"card-body\">
            <div class=\"section-title\">Adresse de commande</div>

            <div class=\"address-box\">
              <div class=\"address-line\"><strong>";
        // line 534
        yield ((CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 534, $this->source); })()), "companyName", [], "any", false, false, false, 534)) ? ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 534, $this->source); })()), "companyName", [], "any", false, false, false, 534), "html", null, true)) : ("—"));
        yield "</strong></div>
              <div class=\"address-line\">";
        // line 535
        yield ((CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 535, $this->source); })()), "address1", [], "any", false, false, false, 535)) ? ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 535, $this->source); })()), "address1", [], "any", false, false, false, 535), "html", null, true)) : ("—"));
        yield "</div>
              ";
        // line 536
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 536, $this->source); })()), "address2", [], "any", false, false, false, 536)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 537
            yield "                <div class=\"address-line\">";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 537, $this->source); })()), "address2", [], "any", false, false, false, 537), "html", null, true);
            yield "</div>
              ";
        }
        // line 539
        yield "              <div class=\"address-line\">";
        yield ((CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 539, $this->source); })()), "city", [], "any", false, false, false, 539)) ? ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 539, $this->source); })()), "city", [], "any", false, false, false, 539), "html", null, true)) : ("—"));
        yield "</div>
              <div class=\"address-line\">";
        // line 540
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 540, $this->source); })()), "customerEmail", [], "any", false, false, false, 540), "html", null, true);
        yield "</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
";
        
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->leave($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof);

        
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->leave($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof);

        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "order/show.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  745 => 540,  740 => 539,  734 => 537,  732 => 536,  728 => 535,  724 => 534,  710 => 523,  703 => 518,  697 => 515,  694 => 514,  688 => 513,  684 => 511,  682 => 510,  676 => 507,  667 => 501,  663 => 500,  659 => 499,  644 => 486,  640 => 484,  629 => 479,  621 => 474,  611 => 467,  603 => 462,  599 => 461,  594 => 458,  590 => 457,  583 => 452,  573 => 448,  567 => 445,  561 => 442,  555 => 439,  551 => 438,  547 => 436,  543 => 435,  530 => 424,  522 => 418,  520 => 417,  506 => 406,  500 => 403,  491 => 397,  483 => 392,  479 => 391,  475 => 390,  467 => 385,  462 => 383,  96 => 19,  94 => 17,  93 => 16,  92 => 15,  91 => 14,  90 => 13,  87 => 12,  85 => 10,  84 => 9,  83 => 8,  82 => 7,  81 => 6,  78 => 5,  76 => 4,  63 => 3,  40 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends 'base.html.twig' %}

{% block body %}
{% set hasDiscount = order.discountAmountTtc is defined and order.discountAmountTtc is not null and order.discountAmountTtc != '0.00' and order.discountAmountTtc != '0' %}

{% set statusClass =
  order.status == 'new' ? 'status-new' :
  (order.status == 'preparing' ? 'status-preparing' :
  (order.status == 'shipping' ? 'status-shipping' :
  (order.status == 'completed' ? 'status-completed' : 'status-cancelled')))
%}

{% set statusIcon =
  order.status == 'new' ? 'fa-file-circle-plus' :
  (order.status == 'preparing' ? 'fa-boxes-packing' :
  (order.status == 'shipping' ? 'fa-truck-fast' :
  (order.status == 'completed' ? 'fa-circle-check' : 'fa-ban')))
%}

<style>
  :root{
    --oc-radius: 22px;
    --oc-border: rgba(0,0,0,.10);
    --oc-shadow: 0 14px 34px rgba(0,0,0,.08);
    --oc-muted: rgba(0,0,0,.58);
    --oc-text: rgba(0,0,0,.84);
    --oc-glass: rgba(255,255,255,.82);
    --oc-glass-2: rgba(255,255,255,.60);
  }

  body.theme-dark{
    --oc-border: rgba(255,255,255,.14);
    --oc-shadow: 0 18px 46px rgba(0,0,0,.35);
    --oc-muted: rgba(255,255,255,.62);
    --oc-text: rgba(255,255,255,.88);
    --oc-glass: rgba(18,18,22,.76);
    --oc-glass-2: rgba(18,18,22,.58);
  }

  .order-show-hero{
    border-radius: 24px;
    border: 1px solid var(--oc-border);
    box-shadow: var(--oc-shadow);
    padding: 18px;
    background:
      radial-gradient(900px circle at 10% 10%, rgba(var(--bs-primary-rgb), .16), transparent 40%),
      radial-gradient(900px circle at 90% 30%, rgba(var(--bs-secondary-rgb), .14), transparent 38%),
      linear-gradient(180deg, rgba(0,0,0,.02), transparent);
  }

  .order-show-hero h3{
    margin: 0;
    font-weight: 650;
    color: var(--oc-text);
  }

  .order-show-hero .subtitle{
    margin-top: 6px;
    color: var(--oc-muted);
    font-size: .92rem;
    font-weight: 600;
  }

  .order-show-actions{
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .order-show-actions .btn{
    min-height: 46px;
    border-radius: 14px;
    font-weight: 600;
    padding-inline: 16px;
  }

  .order-show-card{
    border-radius: var(--oc-radius);
    border: 1px solid var(--oc-border);
    box-shadow: var(--oc-shadow);
    background: var(--oc-glass);
    backdrop-filter: blur(8px);
  }

  .order-show-card .card-body{
    padding: 18px;
  }

  .section-title{
    font-size: .82rem;
    font-weight: 600;
    color: var(--oc-muted);
    margin-bottom: 14px;
    text-transform: uppercase;
    letter-spacing: .03em;
  }

  .order-lines-table{
    margin: 0;
  }

  .order-lines-table thead th{
    border-bottom: 1px solid var(--oc-border);
    color: var(--oc-muted);
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    font-weight: 600;
    padding: 14px 12px;
    white-space: nowrap;
    background: transparent;
  }

  .order-lines-table tbody tr{
    transition: background .12s ease;
  }

  .order-lines-table tbody tr:hover{
    background: rgba(var(--bs-primary-rgb), .04);
  }

  .order-lines-table tbody td{
    padding: 16px 12px;
    border-color: rgba(0,0,0,.06);
    vertical-align: middle;
    color: var(--oc-text);
  }

  body.theme-dark .order-lines-table tbody td{
    border-color: rgba(255,255,255,.08);
  }

  .line-code{
    font-weight: 650;
    color: var(--bs-primary);
    line-height: 1.2;
  }

  .line-designation{
    margin-top: 4px;
    font-size: .84rem;
    color: var(--oc-muted);
    font-weight: 700;
  }

  .line-value{
    font-weight: 600;
    color: var(--oc-text);
    white-space: nowrap;
  }

  .summary-box,
  .address-box{
    border-radius: 18px;
    border: 1px solid var(--oc-border);
    background: rgba(255,255,255,.48);
    padding: 14px;
  }

  body.theme-dark .summary-box,
  body.theme-dark .address-box{
    background: rgba(255,255,255,.04);
  }

  .summary-row{
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
  }

  .summary-row:last-child{
    margin-bottom: 0;
  }

  .summary-label{
    color: var(--oc-muted);
    font-size: .88rem;
    font-weight: 550;
  }

  .summary-value{
    color: var(--oc-text);
    font-size: .92rem;
    font-weight: 600;
    text-align: end;
  }

  .summary-total{
    font-size: 1.15rem;
    font-weight: 650;
    color: var(--oc-text);
  }

  .address-line{
    color: var(--oc-text);
    font-weight: 700;
    line-height: 1.5;
    word-break: break-word;
  }

  .address-line.muted{
    color: var(--oc-muted);
  }

  .meta-badge{
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border-radius: 999px;
    padding: 8px 12px;
    border: 1px solid var(--oc-border);
    background: var(--oc-glass-2);
    color: var(--oc-text);
    font-size: .82rem;
    font-weight: 600;
  }

  .meta-badge i{
    color: var(--bs-primary);
  }

  .status-pill{
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border-radius: 999px;
    padding: 7px 12px;
    font-size: .78rem;
    font-weight: 600;
    border: 1px solid transparent;
    white-space: nowrap;
  }

  .status-pill i{
    font-size: .78rem;
  }

  .status-new{
    background: rgba(13,110,253,.12);
    color: rgba(13,110,253,1);
    border-color: rgba(13,110,253,.18);
  }

  .status-preparing{
    background: rgba(255,193,7,.16);
    color: rgba(176,117,0,.98);
    border-color: rgba(255,193,7,.24);
  }

  .status-shipping{
    background: rgba(111,66,193,.14);
    color: rgba(111,66,193,1);
    border-color: rgba(111,66,193,.22);
  }

  .status-completed{
    background: rgba(25,135,84,.12);
    color: rgba(25,135,84,1);
    border-color: rgba(25,135,84,.20);
  }

  .status-cancelled{
    background: rgba(220,53,69,.12);
    color: rgba(220,53,69,.98);
    border-color: rgba(220,53,69,.20);
  }

  .empty-lines{
    border-radius: 20px;
    border: 1px dashed var(--oc-border);
    background: rgba(255,255,255,.42);
    padding: 44px 18px;
    text-align: center;
    color: var(--oc-muted);
  }

  body.theme-dark .empty-lines{
    background: rgba(255,255,255,.03);
  }

  .empty-lines i{
    font-size: 2rem;
    margin-bottom: 12px;
    color: var(--bs-secondary);
  }

  .mobile-lines{
    display: none;
  }

  .mobile-line-card{
    border-radius: 18px;
    border: 1px solid var(--oc-border);
    background: rgba(255,255,255,.52);
    padding: 14px;
  }

  body.theme-dark .mobile-line-card{
    background: rgba(255,255,255,.04);
  }

  .mobile-line-top{
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
  }

  .mobile-line-code{
    font-weight: 650;
    color: var(--bs-primary);
    line-height: 1.2;
  }

  .mobile-line-designation{
    font-size: .82rem;
    color: var(--oc-muted);
    font-weight: 700;
    margin-top: 4px;
  }

  .mobile-line-grid{
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 10px;
  }

  .mobile-line-box{
    border-radius: 14px;
    border: 1px solid var(--oc-border);
    padding: 10px 12px;
    background: rgba(255,255,255,.55);
  }

  body.theme-dark .mobile-line-box{
    background: rgba(18,18,22,.50);
  }

  .mobile-line-label{
    font-size: .72rem;
    color: var(--oc-muted);
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 4px;
  }

  .mobile-line-value{
    font-size: .88rem;
    color: var(--oc-text);
    font-weight: 550;
    line-height: 1.35;
    word-break: break-word;
  }

  @media (max-width: 991.98px){
    .desktop-lines{
      display: none;
    }

    .mobile-lines{
      display: grid;
      gap: 12px;
    }
  }

  @media (max-width: 767.98px){
    .order-show-actions{
      width: 100%;
    }

    .order-show-actions .btn{
      width: 100%;
    }
  }
</style>

<div class=\"container py-4\">
  <div class=\"order-show-hero mb-3 d-flex align-items-start justify-content-between flex-wrap gap-3\">
    <div>
      <h3>Commande {{ order.orderNumber }}</h3>
      <div class=\"subtitle\">
        Passée le {{ order.createdAt ? order.createdAt|date('d/m/Y H:i') : '—' }}
      </div>
    </div>

    <div class=\"d-flex align-items-center gap-2 flex-wrap\">
      <span class=\"status-pill {{ statusClass }}\">
        <i class=\"fa-solid {{ statusIcon }}\"></i>
        {{ order.statusLabel }}
      </span>

      <div class=\"meta-badge\">
        <i class=\"fa-solid fa-receipt\"></i>
        <span>Total : {{ order.totalTtc }} DH</span>
      </div>
    </div>
  </div>

  <div class=\"mb-3 order-show-actions\">
    <a class=\"btn btn-outline-secondary\" href=\"{{ path('order_index') }}\">
      <i class=\"fa-solid fa-arrow-left me-2\"></i>Retour
    </a>
    <a class=\"btn btn-outline-primary\" href=\"{{ path('cart_index') }}\">
      <i class=\"fa-solid fa-cart-shopping me-2\"></i>Panier
    </a>
  </div>

  <div class=\"row g-3\">
    <div class=\"col-lg-8\">
      <div class=\"order-show-card\">
        <div class=\"card-body\">
          <div class=\"section-title\">Lignes de commande</div>

          {% if order.lines is empty %}
            <div class=\"empty-lines\">
              <i class=\"fa-solid fa-box-open\"></i>
              <div class=\"fw-bold mb-1\">Aucune ligne</div>
              <div class=\"small\">Cette commande ne contient aucun article.</div>
            </div>
          {% else %}
            <div class=\"table-responsive desktop-lines\">
              <table class=\"table order-lines-table align-middle mb-0\">
                <thead>
                  <tr>
                    <th>Article</th>
                    <th class=\"text-end\">Qté</th>
                    <th class=\"text-end\">PU TTC</th>
                    <th class=\"text-end\">Total</th>
                  </tr>
                </thead>
                <tbody>
                  {% for line in order.lines %}
                    <tr>
                      <td>
                        <div class=\"line-code\">{{ line.articleCode }}</div>
                        <div class=\"line-designation\">{{ line.designation ?: '—' }}</div>
                      </td>
                      <td class=\"text-end\">
                        <div class=\"line-value\">{{ line.quantity }}</div>
                      </td>
                      <td class=\"text-end\">
                        <div class=\"line-value\">{{ line.unitPriceTtc }} DH</div>
                      </td>
                      <td class=\"text-end\">
                        <div class=\"line-value\">{{ line.lineTotalTtc }} DH</div>
                      </td>
                    </tr>
                  {% endfor %}
                </tbody>
              </table>
            </div>

            <div class=\"mobile-lines\">
              {% for line in order.lines %}
                <div class=\"mobile-line-card\">
                  <div class=\"mobile-line-top\">
                    <div>
                      <div class=\"mobile-line-code\">{{ line.articleCode }}</div>
                      <div class=\"mobile-line-designation\">{{ line.designation ?: '—' }}</div>
                    </div>

                    <div class=\"meta-badge\">
                      <i class=\"fa-solid fa-cubes\"></i>
                      <span>x{{ line.quantity }}</span>
                    </div>
                  </div>

                  <div class=\"mobile-line-grid\">
                    <div class=\"mobile-line-box\">
                      <div class=\"mobile-line-label\">PU TTC</div>
                      <div class=\"mobile-line-value\">{{ line.unitPriceTtc }} DH</div>
                    </div>

                    <div class=\"mobile-line-box\">
                      <div class=\"mobile-line-label\">Total</div>
                      <div class=\"mobile-line-value\">{{ line.lineTotalTtc }} DH</div>
                    </div>
                  </div>
                </div>
              {% endfor %}
            </div>
          {% endif %}
        </div>
      </div>
    </div>

    <div class=\"col-lg-4\">
      <div class=\"d-grid gap-3\">
        <div class=\"order-show-card\">
          <div class=\"card-body\">
            <div class=\"section-title\">Résumé</div>

            <div class=\"summary-box\">
              <div class=\"summary-row\">
                <span class=\"summary-label\">Statut</span>
                <span class=\"status-pill {{ statusClass }}\">
                  <i class=\"fa-solid {{ statusIcon }}\"></i>
                  {{ order.statusLabel }}
                </span>
              </div>

              <div class=\"summary-row\">
                <span class=\"summary-label\">Sous-total</span>
                <span class=\"summary-value\">{{ order.subtotalTtc }} DH</span>
              </div>

              {% if hasDiscount %}
                <div class=\"summary-row\">
                  <span class=\"summary-label\">
                    Remise{% if order.discountRate %} {{ order.discountRate }}%{% endif %}
                  </span>
                  <span class=\"summary-value text-danger\">-{{ order.discountAmountTtc }} DH</span>
                </div>
              {% endif %}

              <hr>

              <div class=\"summary-row mb-0\">
                <span class=\"summary-label fw-bold\">Total final</span>
                <span class=\"summary-total\">{{ order.totalTtc }} DH</span>
              </div>
            </div>
          </div>
        </div>

        <div class=\"order-show-card\">
          <div class=\"card-body\">
            <div class=\"section-title\">Adresse de commande</div>

            <div class=\"address-box\">
              <div class=\"address-line\"><strong>{{ order.companyName ?: '—' }}</strong></div>
              <div class=\"address-line\">{{ order.address1 ?: '—' }}</div>
              {% if order.address2 %}
                <div class=\"address-line\">{{ order.address2 }}</div>
              {% endif %}
              <div class=\"address-line\">{{ order.city ?: '—' }}</div>
              <div class=\"address-line\">{{ order.customerEmail }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
{% endblock %}", "order/show.html.twig", "F:\\3sk\\3sk (1)\\templates\\order\\show.html.twig");
    }
}
