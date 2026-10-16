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

/* admin/order/show.html.twig */
class __TwigTemplate_0bcd9d7d3d6399b8e070c888d97f4f07 extends Template
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
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "admin/order/show.html.twig"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "admin/order/show.html.twig"));

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
        $context["globalDiscountRate"] = (((CoreExtension::getAttribute($this->env, $this->source, ($context["order"] ?? null), "discountRate", [], "any", true, true, false, 4) &&  !(null === CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 4, $this->source); })()), "discountRate", [], "any", false, false, false, 4)))) ? (CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 4, $this->source); })()), "discountRate", [], "any", false, false, false, 4)) : (null));
        // line 5
        $context["globalDiscountAmount"] = (((CoreExtension::getAttribute($this->env, $this->source, ($context["order"] ?? null), "discountAmountTtc", [], "any", true, true, false, 5) &&  !(null === CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 5, $this->source); })()), "discountAmountTtc", [], "any", false, false, false, 5)))) ? (CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 5, $this->source); })()), "discountAmountTtc", [], "any", false, false, false, 5)) : (null));
        // line 6
        $context["hasGlobalDiscount"] = (( !(null === (isset($context["globalDiscountAmount"]) || array_key_exists("globalDiscountAmount", $context) ? $context["globalDiscountAmount"] : (function () { throw new RuntimeError('Variable "globalDiscountAmount" does not exist.', 6, $this->source); })())) && ((isset($context["globalDiscountAmount"]) || array_key_exists("globalDiscountAmount", $context) ? $context["globalDiscountAmount"] : (function () { throw new RuntimeError('Variable "globalDiscountAmount" does not exist.', 6, $this->source); })()) != "0.00")) && ((isset($context["globalDiscountAmount"]) || array_key_exists("globalDiscountAmount", $context) ? $context["globalDiscountAmount"] : (function () { throw new RuntimeError('Variable "globalDiscountAmount" does not exist.', 6, $this->source); })()) != "0"));
        // line 7
        yield "
";
        // line 8
        $context["dynamicDiscountTotal"] = 0;
        // line 9
        $context["hasDynamicDiscount"] = false;
        // line 10
        yield "
";
        // line 11
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 11, $this->source); })()), "lines", [], "any", false, false, false, 11));
        foreach ($context['_seq'] as $context["_key"] => $context["line"]) {
            // line 12
            yield "  ";
            $context["currentLineDiscount"] = ((((CoreExtension::getAttribute($this->env, $this->source, $context["line"], "discountAmountTtc", [], "any", true, true, false, 12) &&  !(null === CoreExtension::getAttribute($this->env, $this->source, $context["line"], "discountAmountTtc", [], "any", false, false, false, 12)))) ? (CoreExtension::getAttribute($this->env, $this->source, $context["line"], "discountAmountTtc", [], "any", false, false, false, 12)) : (0)) + 0);
            // line 13
            yield "  ";
            if (((isset($context["currentLineDiscount"]) || array_key_exists("currentLineDiscount", $context) ? $context["currentLineDiscount"] : (function () { throw new RuntimeError('Variable "currentLineDiscount" does not exist.', 13, $this->source); })()) > 0)) {
                // line 14
                yield "    ";
                $context["hasDynamicDiscount"] = true;
                // line 15
                yield "    ";
                $context["dynamicDiscountTotal"] = ((isset($context["dynamicDiscountTotal"]) || array_key_exists("dynamicDiscountTotal", $context) ? $context["dynamicDiscountTotal"] : (function () { throw new RuntimeError('Variable "dynamicDiscountTotal" does not exist.', 15, $this->source); })()) + (isset($context["currentLineDiscount"]) || array_key_exists("currentLineDiscount", $context) ? $context["currentLineDiscount"] : (function () { throw new RuntimeError('Variable "currentLineDiscount" does not exist.', 15, $this->source); })()));
                // line 16
                yield "  ";
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['line'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 18
        yield "
";
        // line 19
        $context["statusClass"] = (((CoreExtension::getAttribute($this->env, $this->source,         // line 20
(isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 20, $this->source); })()), "status", [], "any", false, false, false, 20) == "new")) ? ("status-new") : ((((CoreExtension::getAttribute($this->env, $this->source,         // line 21
(isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 21, $this->source); })()), "status", [], "any", false, false, false, 21) == "preparing")) ? ("status-preparing") : ((((CoreExtension::getAttribute($this->env, $this->source,         // line 22
(isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 22, $this->source); })()), "status", [], "any", false, false, false, 22) == "shipping")) ? ("status-shipping") : ((((CoreExtension::getAttribute($this->env, $this->source,         // line 23
(isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 23, $this->source); })()), "status", [], "any", false, false, false, 23) == "completed")) ? ("status-completed") : ("status-cancelled"))))))));
        // line 25
        yield "
";
        // line 26
        $context["statusIcon"] = (((CoreExtension::getAttribute($this->env, $this->source,         // line 27
(isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 27, $this->source); })()), "status", [], "any", false, false, false, 27) == "new")) ? ("fa-file-circle-plus") : ((((CoreExtension::getAttribute($this->env, $this->source,         // line 28
(isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 28, $this->source); })()), "status", [], "any", false, false, false, 28) == "preparing")) ? ("fa-boxes-packing") : ((((CoreExtension::getAttribute($this->env, $this->source,         // line 29
(isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 29, $this->source); })()), "status", [], "any", false, false, false, 29) == "shipping")) ? ("fa-truck-fast") : ((((CoreExtension::getAttribute($this->env, $this->source,         // line 30
(isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 30, $this->source); })()), "status", [], "any", false, false, false, 30) == "completed")) ? ("fa-circle-check") : ("fa-ban"))))))));
        // line 32
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
    transition: background .12s ease, transform .12s ease;
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

  .price-main,
  .total-main{
    font-weight: 600;
    color: var(--oc-text);
    white-space: nowrap;
  }

  .price-before,
  .total-before{
    margin-top: 4px;
    font-size: .82rem;
    color: var(--oc-muted);
    text-decoration: line-through;
    font-weight: 700;
  }

  .discount-value{
    font-weight: 600;
    color: var(--bs-danger);
    white-space: nowrap;
  }

  .discount-subvalue{
    margin-top: 4px;
    font-size: .82rem;
    color: var(--bs-danger);
    font-weight: 700;
  }

  .summary-box,
  .customer-box{
    border-radius: 18px;
    border: 1px solid var(--oc-border);
    background: rgba(255,255,255,.48);
    padding: 14px;
  }

  body.theme-dark .summary-box,
  body.theme-dark .customer-box{
    background: rgba(255,255,255,.04);
  }

  .customer-box strong,
  .summary-box strong{
    color: var(--oc-text);
  }

  .customer-line{
    color: var(--oc-text);
    font-weight: 700;
    line-height: 1.5;
    word-break: break-word;
  }

  .customer-line.muted{
    color: var(--oc-muted);
    font-weight: 700;
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

  @media (max-width: 991.98px){
    .desktop-lines{
      display: none;
    }

    .mobile-lines{
      display: grid;
      gap: 12px;
    }
  }
</style>

<div class=\"container py-4\">
  <div class=\"order-show-hero mb-3 d-flex align-items-start justify-content-between flex-wrap gap-3\">
    <div>
      <h3>Commande ";
        // line 415
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 415, $this->source); })()), "orderNumber", [], "any", false, false, false, 415), "html", null, true);
        yield "</h3>
      <div class=\"subtitle\">";
        // line 416
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 416, $this->source); })()), "customerEmail", [], "any", false, false, false, 416), "html", null, true);
        yield "</div>
    </div>

    <div class=\"d-flex align-items-center gap-2 flex-wrap\">
      <span class=\"status-pill ";
        // line 420
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["statusClass"]) || array_key_exists("statusClass", $context) ? $context["statusClass"] : (function () { throw new RuntimeError('Variable "statusClass" does not exist.', 420, $this->source); })()), "html", null, true);
        yield "\">
        <i class=\"fa-solid ";
        // line 421
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["statusIcon"]) || array_key_exists("statusIcon", $context) ? $context["statusIcon"] : (function () { throw new RuntimeError('Variable "statusIcon" does not exist.', 421, $this->source); })()), "html", null, true);
        yield "\"></i>
        ";
        // line 422
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 422, $this->source); })()), "statusLabel", [], "any", false, false, false, 422), "html", null, true);
        yield "
      </span>

      <div class=\"meta-badge\">
        <i class=\"fa-solid fa-receipt\"></i>
        <span>Total : ";
        // line 427
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 427, $this->source); })()), "totalTtc", [], "any", false, false, false, 427), "html", null, true);
        yield " DH</span>
      </div>
    </div>
  </div>

  <div class=\"mb-3 order-show-actions\">
    <a class=\"btn btn-outline-secondary\" href=\"";
        // line 433
        yield $this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("admin_orders_index");
        yield "\">
      <i class=\"fa-solid fa-arrow-left me-2\"></i>Retour
    </a>

    <a class=\"btn btn-outline-success\" href=\"";
        // line 437
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("admin_orders_export_excel", ["id" => CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 437, $this->source); })()), "id", [], "any", false, false, false, 437)]), "html", null, true);
        yield "\">
      <i class=\"fa-solid fa-file-excel me-2\"></i>Exporter Excel
    </a>

    <a class=\"btn btn-outline-primary\" href=\"";
        // line 441
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("admin_orders_print", ["id" => CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 441, $this->source); })()), "id", [], "any", false, false, false, 441)]), "html", null, true);
        yield "\" target=\"_blank\" rel=\"noopener\">
      <i class=\"fa-solid fa-print me-2\"></i>Imprimer
    </a>

    ";
        // line 445
        if ((($tmp = (isset($context["nextStatus"]) || array_key_exists("nextStatus", $context) ? $context["nextStatus"] : (function () { throw new RuntimeError('Variable "nextStatus" does not exist.', 445, $this->source); })())) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 446
            yield "      <form method=\"post\" action=\"";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("admin_orders_advance", ["id" => CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 446, $this->source); })()), "id", [], "any", false, false, false, 446)]), "html", null, true);
            yield "\">
        <input type=\"hidden\" name=\"_token\" value=\"";
            // line 447
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Symfony\Component\Form\FormRenderer')->renderCsrfToken(("advance_order_" . CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 447, $this->source); })()), "id", [], "any", false, false, false, 447))), "html", null, true);
            yield "\">
        <button class=\"btn btn-primary\" type=\"submit\">
          <i class=\"fa-solid fa-forward-step me-2\"></i>
          Passer à ";
            // line 450
            yield (((CoreExtension::getAttribute($this->env, $this->source, ($context["statusLabels"] ?? null), (isset($context["nextStatus"]) || array_key_exists("nextStatus", $context) ? $context["nextStatus"] : (function () { throw new RuntimeError('Variable "nextStatus" does not exist.', 450, $this->source); })()), [], "array", true, true, false, 450) &&  !(null === CoreExtension::getAttribute($this->env, $this->source, (isset($context["statusLabels"]) || array_key_exists("statusLabels", $context) ? $context["statusLabels"] : (function () { throw new RuntimeError('Variable "statusLabels" does not exist.', 450, $this->source); })()), (isset($context["nextStatus"]) || array_key_exists("nextStatus", $context) ? $context["nextStatus"] : (function () { throw new RuntimeError('Variable "nextStatus" does not exist.', 450, $this->source); })()), [], "array", false, false, false, 450)))) ? ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["statusLabels"]) || array_key_exists("statusLabels", $context) ? $context["statusLabels"] : (function () { throw new RuntimeError('Variable "statusLabels" does not exist.', 450, $this->source); })()), (isset($context["nextStatus"]) || array_key_exists("nextStatus", $context) ? $context["nextStatus"] : (function () { throw new RuntimeError('Variable "nextStatus" does not exist.', 450, $this->source); })()), [], "array", false, false, false, 450), "html", null, true)) : ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["nextStatus"]) || array_key_exists("nextStatus", $context) ? $context["nextStatus"] : (function () { throw new RuntimeError('Variable "nextStatus" does not exist.', 450, $this->source); })()), "html", null, true)));
            yield "
        </button>
      </form>
    ";
        }
        // line 454
        yield "
    ";
        // line 455
        if (!CoreExtension::inFilter(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 455, $this->source); })()), "status", [], "any", false, false, false, 455), ["completed", "cancelled"])) {
            // line 456
            yield "      <form method=\"post\" action=\"";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("admin_orders_cancel", ["id" => CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 456, $this->source); })()), "id", [], "any", false, false, false, 456)]), "html", null, true);
            yield "\">
        <input type=\"hidden\" name=\"_token\" value=\"";
            // line 457
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->env->getRuntime('Symfony\Component\Form\FormRenderer')->renderCsrfToken(("cancel_order_" . CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 457, $this->source); })()), "id", [], "any", false, false, false, 457))), "html", null, true);
            yield "\">
        <button class=\"btn btn-outline-danger\" type=\"submit\">
          <i class=\"fa-solid fa-ban me-2\"></i>Annuler
        </button>
      </form>
    ";
        }
        // line 463
        yield "  </div>

  <div class=\"row g-3\">
    <div class=\"col-lg-8\">
      <div class=\"order-show-card\">
        <div class=\"card-body\">
          <div class=\"section-title\">Articles</div>

          ";
        // line 471
        if (Twig\Extension\CoreExtension::testEmpty(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 471, $this->source); })()), "lines", [], "any", false, false, false, 471))) {
            // line 472
            yield "            <div class=\"empty-lines\">
              <i class=\"fa-solid fa-box-open\"></i>
              <div class=\"fw-bold mb-1\">Aucune ligne</div>
              <div class=\"small\">Cette commande ne contient aucun article.</div>
            </div>
          ";
        } else {
            // line 478
            yield "            <div class=\"table-responsive desktop-lines\">
              <table class=\"table order-lines-table align-middle mb-0\">
                <thead>
                  <tr>
                    <th>Article</th>
                    <th class=\"text-end\">Qté</th>
                    <th class=\"text-end\">PU TTC</th>
                    <th class=\"text-end\">Remise</th>
                    <th class=\"text-end\">Total</th>
                  </tr>
                </thead>
                <tbody>
                  ";
            // line 490
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 490, $this->source); })()), "lines", [], "any", false, false, false, 490));
            foreach ($context['_seq'] as $context["_key"] => $context["line"]) {
                // line 491
                yield "                    ";
                $context["lineDiscountRate"] = (((CoreExtension::getAttribute($this->env, $this->source, $context["line"], "discountRate", [], "any", true, true, false, 491) &&  !(null === CoreExtension::getAttribute($this->env, $this->source, $context["line"], "discountRate", [], "any", false, false, false, 491)))) ? (CoreExtension::getAttribute($this->env, $this->source, $context["line"], "discountRate", [], "any", false, false, false, 491)) : (null));
                // line 492
                yield "                    ";
                $context["lineDiscountAmount"] = (((CoreExtension::getAttribute($this->env, $this->source, $context["line"], "discountAmountTtc", [], "any", true, true, false, 492) &&  !(null === CoreExtension::getAttribute($this->env, $this->source, $context["line"], "discountAmountTtc", [], "any", false, false, false, 492)))) ? (CoreExtension::getAttribute($this->env, $this->source, $context["line"], "discountAmountTtc", [], "any", false, false, false, 492)) : (null));
                // line 493
                yield "                    ";
                $context["lineUnitPriceFinal"] = (((CoreExtension::getAttribute($this->env, $this->source, $context["line"], "unitPriceFinalTtc", [], "any", true, true, false, 493) &&  !(null === CoreExtension::getAttribute($this->env, $this->source, $context["line"], "unitPriceFinalTtc", [], "any", false, false, false, 493)))) ? (CoreExtension::getAttribute($this->env, $this->source, $context["line"], "unitPriceFinalTtc", [], "any", false, false, false, 493)) : (CoreExtension::getAttribute($this->env, $this->source, $context["line"], "unitPriceTtc", [], "any", false, false, false, 493)));
                // line 494
                yield "                    ";
                $context["lineUnitPriceBefore"] = (((CoreExtension::getAttribute($this->env, $this->source, $context["line"], "unitPriceBeforeDiscountTtc", [], "any", true, true, false, 494) &&  !(null === CoreExtension::getAttribute($this->env, $this->source, $context["line"], "unitPriceBeforeDiscountTtc", [], "any", false, false, false, 494)))) ? (CoreExtension::getAttribute($this->env, $this->source, $context["line"], "unitPriceBeforeDiscountTtc", [], "any", false, false, false, 494)) : (null));
                // line 495
                yield "                    ";
                $context["lineTotalFinal"] = (((CoreExtension::getAttribute($this->env, $this->source, $context["line"], "lineTotalFinalTtc", [], "any", true, true, false, 495) &&  !(null === CoreExtension::getAttribute($this->env, $this->source, $context["line"], "lineTotalFinalTtc", [], "any", false, false, false, 495)))) ? (CoreExtension::getAttribute($this->env, $this->source, $context["line"], "lineTotalFinalTtc", [], "any", false, false, false, 495)) : (CoreExtension::getAttribute($this->env, $this->source, $context["line"], "lineTotalTtc", [], "any", false, false, false, 495)));
                // line 496
                yield "                    ";
                $context["lineTotalBefore"] = (((CoreExtension::getAttribute($this->env, $this->source, $context["line"], "lineTotalBeforeDiscountTtc", [], "any", true, true, false, 496) &&  !(null === CoreExtension::getAttribute($this->env, $this->source, $context["line"], "lineTotalBeforeDiscountTtc", [], "any", false, false, false, 496)))) ? (CoreExtension::getAttribute($this->env, $this->source, $context["line"], "lineTotalBeforeDiscountTtc", [], "any", false, false, false, 496)) : (null));
                // line 497
                yield "                    ";
                $context["hasLineDiscount"] = (( !(null === (isset($context["lineDiscountAmount"]) || array_key_exists("lineDiscountAmount", $context) ? $context["lineDiscountAmount"] : (function () { throw new RuntimeError('Variable "lineDiscountAmount" does not exist.', 497, $this->source); })())) && ((isset($context["lineDiscountAmount"]) || array_key_exists("lineDiscountAmount", $context) ? $context["lineDiscountAmount"] : (function () { throw new RuntimeError('Variable "lineDiscountAmount" does not exist.', 497, $this->source); })()) != "0.00")) && ((isset($context["lineDiscountAmount"]) || array_key_exists("lineDiscountAmount", $context) ? $context["lineDiscountAmount"] : (function () { throw new RuntimeError('Variable "lineDiscountAmount" does not exist.', 497, $this->source); })()) != "0"));
                // line 498
                yield "
                    <tr>
                      <td>
                        <div class=\"line-code\">";
                // line 501
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "articleCode", [], "any", false, false, false, 501), "html", null, true);
                yield "</div>
                        <div class=\"line-designation\">";
                // line 502
                yield ((CoreExtension::getAttribute($this->env, $this->source, $context["line"], "designation", [], "any", false, false, false, 502)) ? ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "designation", [], "any", false, false, false, 502), "html", null, true)) : ("—"));
                yield "</div>
                      </td>

                      <td class=\"text-end\">
                        <div class=\"price-main\">";
                // line 506
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "quantity", [], "any", false, false, false, 506), "html", null, true);
                yield "</div>
                      </td>

                      <td class=\"text-end\">
                        <div class=\"price-main\">";
                // line 510
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["lineUnitPriceFinal"]) || array_key_exists("lineUnitPriceFinal", $context) ? $context["lineUnitPriceFinal"] : (function () { throw new RuntimeError('Variable "lineUnitPriceFinal" does not exist.', 510, $this->source); })()), "html", null, true);
                yield " DH</div>
                        ";
                // line 511
                if (((isset($context["hasLineDiscount"]) || array_key_exists("hasLineDiscount", $context) ? $context["hasLineDiscount"] : (function () { throw new RuntimeError('Variable "hasLineDiscount" does not exist.', 511, $this->source); })()) && (isset($context["lineUnitPriceBefore"]) || array_key_exists("lineUnitPriceBefore", $context) ? $context["lineUnitPriceBefore"] : (function () { throw new RuntimeError('Variable "lineUnitPriceBefore" does not exist.', 511, $this->source); })()))) {
                    // line 512
                    yield "                          <div class=\"price-before\">";
                    yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["lineUnitPriceBefore"]) || array_key_exists("lineUnitPriceBefore", $context) ? $context["lineUnitPriceBefore"] : (function () { throw new RuntimeError('Variable "lineUnitPriceBefore" does not exist.', 512, $this->source); })()), "html", null, true);
                    yield " DH</div>
                        ";
                }
                // line 514
                yield "                      </td>

                      <td class=\"text-end\">
                        ";
                // line 517
                if ((($tmp = (isset($context["hasLineDiscount"]) || array_key_exists("hasLineDiscount", $context) ? $context["hasLineDiscount"] : (function () { throw new RuntimeError('Variable "hasLineDiscount" does not exist.', 517, $this->source); })())) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 518
                    yield "                          <div class=\"discount-value\">
                            ";
                    // line 519
                    if ((($tmp = (isset($context["lineDiscountRate"]) || array_key_exists("lineDiscountRate", $context) ? $context["lineDiscountRate"] : (function () { throw new RuntimeError('Variable "lineDiscountRate" does not exist.', 519, $this->source); })())) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        // line 520
                        yield "                              -";
                        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["lineDiscountRate"]) || array_key_exists("lineDiscountRate", $context) ? $context["lineDiscountRate"] : (function () { throw new RuntimeError('Variable "lineDiscountRate" does not exist.', 520, $this->source); })()), "html", null, true);
                        yield "%
                            ";
                    } else {
                        // line 522
                        yield "                              -";
                        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["lineDiscountAmount"]) || array_key_exists("lineDiscountAmount", $context) ? $context["lineDiscountAmount"] : (function () { throw new RuntimeError('Variable "lineDiscountAmount" does not exist.', 522, $this->source); })()), "html", null, true);
                        yield " DH
                            ";
                    }
                    // line 524
                    yield "                          </div>
                          ";
                    // line 525
                    if (((isset($context["lineDiscountRate"]) || array_key_exists("lineDiscountRate", $context) ? $context["lineDiscountRate"] : (function () { throw new RuntimeError('Variable "lineDiscountRate" does not exist.', 525, $this->source); })()) && (isset($context["lineDiscountAmount"]) || array_key_exists("lineDiscountAmount", $context) ? $context["lineDiscountAmount"] : (function () { throw new RuntimeError('Variable "lineDiscountAmount" does not exist.', 525, $this->source); })()))) {
                        // line 526
                        yield "                            <div class=\"discount-subvalue\">-";
                        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["lineDiscountAmount"]) || array_key_exists("lineDiscountAmount", $context) ? $context["lineDiscountAmount"] : (function () { throw new RuntimeError('Variable "lineDiscountAmount" does not exist.', 526, $this->source); })()), "html", null, true);
                        yield " DH</div>
                          ";
                    }
                    // line 528
                    yield "                        ";
                } else {
                    // line 529
                    yield "                          <span class=\"text-muted\">—</span>
                        ";
                }
                // line 531
                yield "                      </td>

                      <td class=\"text-end\">
                        <div class=\"total-main\">";
                // line 534
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["lineTotalFinal"]) || array_key_exists("lineTotalFinal", $context) ? $context["lineTotalFinal"] : (function () { throw new RuntimeError('Variable "lineTotalFinal" does not exist.', 534, $this->source); })()), "html", null, true);
                yield " DH</div>
                        ";
                // line 535
                if (((isset($context["hasLineDiscount"]) || array_key_exists("hasLineDiscount", $context) ? $context["hasLineDiscount"] : (function () { throw new RuntimeError('Variable "hasLineDiscount" does not exist.', 535, $this->source); })()) && (isset($context["lineTotalBefore"]) || array_key_exists("lineTotalBefore", $context) ? $context["lineTotalBefore"] : (function () { throw new RuntimeError('Variable "lineTotalBefore" does not exist.', 535, $this->source); })()))) {
                    // line 536
                    yield "                          <div class=\"total-before\">";
                    yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["lineTotalBefore"]) || array_key_exists("lineTotalBefore", $context) ? $context["lineTotalBefore"] : (function () { throw new RuntimeError('Variable "lineTotalBefore" does not exist.', 536, $this->source); })()), "html", null, true);
                    yield " DH</div>
                        ";
                }
                // line 538
                yield "                      </td>
                    </tr>
                  ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['line'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 541
            yield "                </tbody>
              </table>
            </div>

            <div class=\"mobile-lines\">
              ";
            // line 546
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 546, $this->source); })()), "lines", [], "any", false, false, false, 546));
            foreach ($context['_seq'] as $context["_key"] => $context["line"]) {
                // line 547
                yield "                ";
                $context["lineDiscountRate"] = (((CoreExtension::getAttribute($this->env, $this->source, $context["line"], "discountRate", [], "any", true, true, false, 547) &&  !(null === CoreExtension::getAttribute($this->env, $this->source, $context["line"], "discountRate", [], "any", false, false, false, 547)))) ? (CoreExtension::getAttribute($this->env, $this->source, $context["line"], "discountRate", [], "any", false, false, false, 547)) : (null));
                // line 548
                yield "                ";
                $context["lineDiscountAmount"] = (((CoreExtension::getAttribute($this->env, $this->source, $context["line"], "discountAmountTtc", [], "any", true, true, false, 548) &&  !(null === CoreExtension::getAttribute($this->env, $this->source, $context["line"], "discountAmountTtc", [], "any", false, false, false, 548)))) ? (CoreExtension::getAttribute($this->env, $this->source, $context["line"], "discountAmountTtc", [], "any", false, false, false, 548)) : (null));
                // line 549
                yield "                ";
                $context["lineUnitPriceFinal"] = (((CoreExtension::getAttribute($this->env, $this->source, $context["line"], "unitPriceFinalTtc", [], "any", true, true, false, 549) &&  !(null === CoreExtension::getAttribute($this->env, $this->source, $context["line"], "unitPriceFinalTtc", [], "any", false, false, false, 549)))) ? (CoreExtension::getAttribute($this->env, $this->source, $context["line"], "unitPriceFinalTtc", [], "any", false, false, false, 549)) : (CoreExtension::getAttribute($this->env, $this->source, $context["line"], "unitPriceTtc", [], "any", false, false, false, 549)));
                // line 550
                yield "                ";
                $context["lineTotalFinal"] = (((CoreExtension::getAttribute($this->env, $this->source, $context["line"], "lineTotalFinalTtc", [], "any", true, true, false, 550) &&  !(null === CoreExtension::getAttribute($this->env, $this->source, $context["line"], "lineTotalFinalTtc", [], "any", false, false, false, 550)))) ? (CoreExtension::getAttribute($this->env, $this->source, $context["line"], "lineTotalFinalTtc", [], "any", false, false, false, 550)) : (CoreExtension::getAttribute($this->env, $this->source, $context["line"], "lineTotalTtc", [], "any", false, false, false, 550)));
                // line 551
                yield "                ";
                $context["hasLineDiscount"] = (( !(null === (isset($context["lineDiscountAmount"]) || array_key_exists("lineDiscountAmount", $context) ? $context["lineDiscountAmount"] : (function () { throw new RuntimeError('Variable "lineDiscountAmount" does not exist.', 551, $this->source); })())) && ((isset($context["lineDiscountAmount"]) || array_key_exists("lineDiscountAmount", $context) ? $context["lineDiscountAmount"] : (function () { throw new RuntimeError('Variable "lineDiscountAmount" does not exist.', 551, $this->source); })()) != "0.00")) && ((isset($context["lineDiscountAmount"]) || array_key_exists("lineDiscountAmount", $context) ? $context["lineDiscountAmount"] : (function () { throw new RuntimeError('Variable "lineDiscountAmount" does not exist.', 551, $this->source); })()) != "0"));
                // line 552
                yield "
                <div class=\"mobile-line-card\">
                  <div class=\"mobile-line-top\">
                    <div>
                      <div class=\"mobile-line-code\">";
                // line 556
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "articleCode", [], "any", false, false, false, 556), "html", null, true);
                yield "</div>
                      <div class=\"mobile-line-designation\">";
                // line 557
                yield ((CoreExtension::getAttribute($this->env, $this->source, $context["line"], "designation", [], "any", false, false, false, 557)) ? ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "designation", [], "any", false, false, false, 557), "html", null, true)) : ("—"));
                yield "</div>
                    </div>

                    <div class=\"meta-badge\">
                      <i class=\"fa-solid fa-cubes\"></i>
                      <span>x";
                // line 562
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "quantity", [], "any", false, false, false, 562), "html", null, true);
                yield "</span>
                    </div>
                  </div>

                  <div class=\"mobile-line-grid\">
                    <div class=\"mobile-line-box\">
                      <div class=\"mobile-line-label\">PU TTC</div>
                      <div class=\"mobile-line-value\">";
                // line 569
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["lineUnitPriceFinal"]) || array_key_exists("lineUnitPriceFinal", $context) ? $context["lineUnitPriceFinal"] : (function () { throw new RuntimeError('Variable "lineUnitPriceFinal" does not exist.', 569, $this->source); })()), "html", null, true);
                yield " DH</div>
                    </div>

                    <div class=\"mobile-line-box\">
                      <div class=\"mobile-line-label\">Total</div>
                      <div class=\"mobile-line-value\">";
                // line 574
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["lineTotalFinal"]) || array_key_exists("lineTotalFinal", $context) ? $context["lineTotalFinal"] : (function () { throw new RuntimeError('Variable "lineTotalFinal" does not exist.', 574, $this->source); })()), "html", null, true);
                yield " DH</div>
                    </div>

                    <div class=\"mobile-line-box\">
                      <div class=\"mobile-line-label\">Remise</div>
                      <div class=\"mobile-line-value\">
                        ";
                // line 580
                if ((($tmp = (isset($context["hasLineDiscount"]) || array_key_exists("hasLineDiscount", $context) ? $context["hasLineDiscount"] : (function () { throw new RuntimeError('Variable "hasLineDiscount" does not exist.', 580, $this->source); })())) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 581
                    yield "                          ";
                    if ((($tmp = (isset($context["lineDiscountRate"]) || array_key_exists("lineDiscountRate", $context) ? $context["lineDiscountRate"] : (function () { throw new RuntimeError('Variable "lineDiscountRate" does not exist.', 581, $this->source); })())) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        // line 582
                        yield "                            -";
                        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["lineDiscountRate"]) || array_key_exists("lineDiscountRate", $context) ? $context["lineDiscountRate"] : (function () { throw new RuntimeError('Variable "lineDiscountRate" does not exist.', 582, $this->source); })()), "html", null, true);
                        yield "%
                          ";
                    } else {
                        // line 584
                        yield "                            -";
                        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["lineDiscountAmount"]) || array_key_exists("lineDiscountAmount", $context) ? $context["lineDiscountAmount"] : (function () { throw new RuntimeError('Variable "lineDiscountAmount" does not exist.', 584, $this->source); })()), "html", null, true);
                        yield " DH
                          ";
                    }
                    // line 586
                    yield "                        ";
                } else {
                    // line 587
                    yield "                          —
                        ";
                }
                // line 589
                yield "                      </div>
                    </div>

                    <div class=\"mobile-line-box\">
                      <div class=\"mobile-line-label\">Montant remise</div>
                      <div class=\"mobile-line-value\">
                        ";
                // line 595
                if ((($tmp = (isset($context["hasLineDiscount"]) || array_key_exists("hasLineDiscount", $context) ? $context["hasLineDiscount"] : (function () { throw new RuntimeError('Variable "hasLineDiscount" does not exist.', 595, $this->source); })())) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 596
                    yield "                          -";
                    yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["lineDiscountAmount"]) || array_key_exists("lineDiscountAmount", $context) ? $context["lineDiscountAmount"] : (function () { throw new RuntimeError('Variable "lineDiscountAmount" does not exist.', 596, $this->source); })()), "html", null, true);
                    yield " DH
                        ";
                } else {
                    // line 598
                    yield "                          —
                        ";
                }
                // line 600
                yield "                      </div>
                    </div>
                  </div>
                </div>
              ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['line'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 605
            yield "            </div>
          ";
        }
        // line 607
        yield "        </div>
      </div>
    </div>

    <div class=\"col-lg-4\">
      <div class=\"d-grid gap-3\">
        <div class=\"order-show-card\">
          <div class=\"card-body\">
            <div class=\"section-title\">Infos client</div>

            <div class=\"customer-box\">
              <div class=\"customer-line\"><strong>";
        // line 618
        yield ((CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 618, $this->source); })()), "companyName", [], "any", false, false, false, 618)) ? ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 618, $this->source); })()), "companyName", [], "any", false, false, false, 618), "html", null, true)) : ("—"));
        yield "</strong></div>
              <div class=\"customer-line\">";
        // line 619
        yield ((CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 619, $this->source); })()), "address1", [], "any", false, false, false, 619)) ? ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 619, $this->source); })()), "address1", [], "any", false, false, false, 619), "html", null, true)) : ("—"));
        yield "</div>
              ";
        // line 620
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 620, $this->source); })()), "address2", [], "any", false, false, false, 620)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 621
            yield "                <div class=\"customer-line\">";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 621, $this->source); })()), "address2", [], "any", false, false, false, 621), "html", null, true);
            yield "</div>
              ";
        }
        // line 623
        yield "              <div class=\"customer-line\">";
        yield ((CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 623, $this->source); })()), "city", [], "any", false, false, false, 623)) ? ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 623, $this->source); })()), "city", [], "any", false, false, false, 623), "html", null, true)) : ("—"));
        yield "</div>
              <div class=\"customer-line\">";
        // line 624
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 624, $this->source); })()), "customerEmail", [], "any", false, false, false, 624), "html", null, true);
        yield "</div>
              <div class=\"customer-line muted\">Code client : ";
        // line 625
        yield ((CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 625, $this->source); })()), "customerCode", [], "any", false, false, false, 625)) ? ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 625, $this->source); })()), "customerCode", [], "any", false, false, false, 625), "html", null, true)) : ("—"));
        yield "</div>
            </div>
          </div>
        </div>

        <div class=\"order-show-card\">
          <div class=\"card-body\">
            <div class=\"section-title\">Résumé</div>

            <div class=\"summary-box\">
              <div class=\"summary-row\">
                <span class=\"summary-label\">Statut</span>
                <span class=\"status-pill ";
        // line 637
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["statusClass"]) || array_key_exists("statusClass", $context) ? $context["statusClass"] : (function () { throw new RuntimeError('Variable "statusClass" does not exist.', 637, $this->source); })()), "html", null, true);
        yield "\">
                  <i class=\"fa-solid ";
        // line 638
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["statusIcon"]) || array_key_exists("statusIcon", $context) ? $context["statusIcon"] : (function () { throw new RuntimeError('Variable "statusIcon" does not exist.', 638, $this->source); })()), "html", null, true);
        yield "\"></i>
                  ";
        // line 639
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 639, $this->source); })()), "statusLabel", [], "any", false, false, false, 639), "html", null, true);
        yield "
                </span>
              </div>

              <div class=\"summary-row\">
                <span class=\"summary-label\">Sous-total</span>
                <span class=\"summary-value\">";
        // line 645
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 645, $this->source); })()), "subtotalTtc", [], "any", false, false, false, 645), "html", null, true);
        yield " DH</span>
              </div>

              ";
        // line 648
        if ((($tmp = (isset($context["hasGlobalDiscount"]) || array_key_exists("hasGlobalDiscount", $context) ? $context["hasGlobalDiscount"] : (function () { throw new RuntimeError('Variable "hasGlobalDiscount" does not exist.', 648, $this->source); })())) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 649
            yield "                <div class=\"summary-row\">
                  <span class=\"summary-label\">Remise ";
            // line 650
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["globalDiscountRate"]) || array_key_exists("globalDiscountRate", $context) ? $context["globalDiscountRate"] : (function () { throw new RuntimeError('Variable "globalDiscountRate" does not exist.', 650, $this->source); })()), "html", null, true);
            yield "%</span>
                  <span class=\"summary-value text-danger\">-";
            // line 651
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["globalDiscountAmount"]) || array_key_exists("globalDiscountAmount", $context) ? $context["globalDiscountAmount"] : (function () { throw new RuntimeError('Variable "globalDiscountAmount" does not exist.', 651, $this->source); })()), "html", null, true);
            yield " DH</span>
                </div>
              ";
        } elseif ((($tmp =         // line 653
(isset($context["hasDynamicDiscount"]) || array_key_exists("hasDynamicDiscount", $context) ? $context["hasDynamicDiscount"] : (function () { throw new RuntimeError('Variable "hasDynamicDiscount" does not exist.', 653, $this->source); })())) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 654
            yield "                <div class=\"summary-row\">
                  <span class=\"summary-label\">Remises variables</span>
                  <span class=\"summary-value text-danger\">-";
            // line 656
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Twig\Extension\CoreExtension']->formatNumber((isset($context["dynamicDiscountTotal"]) || array_key_exists("dynamicDiscountTotal", $context) ? $context["dynamicDiscountTotal"] : (function () { throw new RuntimeError('Variable "dynamicDiscountTotal" does not exist.', 656, $this->source); })()), 2, ".", ""), "html", null, true);
            yield " DH</span>
                </div>
              ";
        }
        // line 659
        yield "
              <hr>

              <div class=\"summary-row mb-0\">
                <span class=\"summary-label fw-bold\">Total final</span>
                <span class=\"summary-total\">";
        // line 664
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 664, $this->source); })()), "totalTtc", [], "any", false, false, false, 664), "html", null, true);
        yield " DH</span>
              </div>
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
        return "admin/order/show.html.twig";
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
        return array (  1013 => 664,  1006 => 659,  1000 => 656,  996 => 654,  994 => 653,  989 => 651,  985 => 650,  982 => 649,  980 => 648,  974 => 645,  965 => 639,  961 => 638,  957 => 637,  942 => 625,  938 => 624,  933 => 623,  927 => 621,  925 => 620,  921 => 619,  917 => 618,  904 => 607,  900 => 605,  890 => 600,  886 => 598,  880 => 596,  878 => 595,  870 => 589,  866 => 587,  863 => 586,  857 => 584,  851 => 582,  848 => 581,  846 => 580,  837 => 574,  829 => 569,  819 => 562,  811 => 557,  807 => 556,  801 => 552,  798 => 551,  795 => 550,  792 => 549,  789 => 548,  786 => 547,  782 => 546,  775 => 541,  767 => 538,  761 => 536,  759 => 535,  755 => 534,  750 => 531,  746 => 529,  743 => 528,  737 => 526,  735 => 525,  732 => 524,  726 => 522,  720 => 520,  718 => 519,  715 => 518,  713 => 517,  708 => 514,  702 => 512,  700 => 511,  696 => 510,  689 => 506,  682 => 502,  678 => 501,  673 => 498,  670 => 497,  667 => 496,  664 => 495,  661 => 494,  658 => 493,  655 => 492,  652 => 491,  648 => 490,  634 => 478,  626 => 472,  624 => 471,  614 => 463,  605 => 457,  600 => 456,  598 => 455,  595 => 454,  588 => 450,  582 => 447,  577 => 446,  575 => 445,  568 => 441,  561 => 437,  554 => 433,  545 => 427,  537 => 422,  533 => 421,  529 => 420,  522 => 416,  518 => 415,  133 => 32,  131 => 30,  130 => 29,  129 => 28,  128 => 27,  127 => 26,  124 => 25,  122 => 23,  121 => 22,  120 => 21,  119 => 20,  118 => 19,  115 => 18,  108 => 16,  105 => 15,  102 => 14,  99 => 13,  96 => 12,  92 => 11,  89 => 10,  87 => 9,  85 => 8,  82 => 7,  80 => 6,  78 => 5,  76 => 4,  63 => 3,  40 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("{% extends 'base.html.twig' %}

{% block body %}
{% set globalDiscountRate = order.discountRate ?? null %}
{% set globalDiscountAmount = order.discountAmountTtc ?? null %}
{% set hasGlobalDiscount = globalDiscountAmount is not null and globalDiscountAmount != '0.00' and globalDiscountAmount != '0' %}

{% set dynamicDiscountTotal = 0 %}
{% set hasDynamicDiscount = false %}

{% for line in order.lines %}
  {% set currentLineDiscount = (line.discountAmountTtc ?? 0) + 0 %}
  {% if currentLineDiscount > 0 %}
    {% set hasDynamicDiscount = true %}
    {% set dynamicDiscountTotal = dynamicDiscountTotal + currentLineDiscount %}
  {% endif %}
{% endfor %}

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
    transition: background .12s ease, transform .12s ease;
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

  .price-main,
  .total-main{
    font-weight: 600;
    color: var(--oc-text);
    white-space: nowrap;
  }

  .price-before,
  .total-before{
    margin-top: 4px;
    font-size: .82rem;
    color: var(--oc-muted);
    text-decoration: line-through;
    font-weight: 700;
  }

  .discount-value{
    font-weight: 600;
    color: var(--bs-danger);
    white-space: nowrap;
  }

  .discount-subvalue{
    margin-top: 4px;
    font-size: .82rem;
    color: var(--bs-danger);
    font-weight: 700;
  }

  .summary-box,
  .customer-box{
    border-radius: 18px;
    border: 1px solid var(--oc-border);
    background: rgba(255,255,255,.48);
    padding: 14px;
  }

  body.theme-dark .summary-box,
  body.theme-dark .customer-box{
    background: rgba(255,255,255,.04);
  }

  .customer-box strong,
  .summary-box strong{
    color: var(--oc-text);
  }

  .customer-line{
    color: var(--oc-text);
    font-weight: 700;
    line-height: 1.5;
    word-break: break-word;
  }

  .customer-line.muted{
    color: var(--oc-muted);
    font-weight: 700;
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

  @media (max-width: 991.98px){
    .desktop-lines{
      display: none;
    }

    .mobile-lines{
      display: grid;
      gap: 12px;
    }
  }
</style>

<div class=\"container py-4\">
  <div class=\"order-show-hero mb-3 d-flex align-items-start justify-content-between flex-wrap gap-3\">
    <div>
      <h3>Commande {{ order.orderNumber }}</h3>
      <div class=\"subtitle\">{{ order.customerEmail }}</div>
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
    <a class=\"btn btn-outline-secondary\" href=\"{{ path('admin_orders_index') }}\">
      <i class=\"fa-solid fa-arrow-left me-2\"></i>Retour
    </a>

    <a class=\"btn btn-outline-success\" href=\"{{ path('admin_orders_export_excel', {id: order.id}) }}\">
      <i class=\"fa-solid fa-file-excel me-2\"></i>Exporter Excel
    </a>

    <a class=\"btn btn-outline-primary\" href=\"{{ path('admin_orders_print', {id: order.id}) }}\" target=\"_blank\" rel=\"noopener\">
      <i class=\"fa-solid fa-print me-2\"></i>Imprimer
    </a>

    {% if nextStatus %}
      <form method=\"post\" action=\"{{ path('admin_orders_advance', {id: order.id}) }}\">
        <input type=\"hidden\" name=\"_token\" value=\"{{ csrf_token('advance_order_' ~ order.id) }}\">
        <button class=\"btn btn-primary\" type=\"submit\">
          <i class=\"fa-solid fa-forward-step me-2\"></i>
          Passer à {{ statusLabels[nextStatus] ?? nextStatus }}
        </button>
      </form>
    {% endif %}

    {% if order.status not in ['completed', 'cancelled'] %}
      <form method=\"post\" action=\"{{ path('admin_orders_cancel', {id: order.id}) }}\">
        <input type=\"hidden\" name=\"_token\" value=\"{{ csrf_token('cancel_order_' ~ order.id) }}\">
        <button class=\"btn btn-outline-danger\" type=\"submit\">
          <i class=\"fa-solid fa-ban me-2\"></i>Annuler
        </button>
      </form>
    {% endif %}
  </div>

  <div class=\"row g-3\">
    <div class=\"col-lg-8\">
      <div class=\"order-show-card\">
        <div class=\"card-body\">
          <div class=\"section-title\">Articles</div>

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
                    <th class=\"text-end\">Remise</th>
                    <th class=\"text-end\">Total</th>
                  </tr>
                </thead>
                <tbody>
                  {% for line in order.lines %}
                    {% set lineDiscountRate = line.discountRate ?? null %}
                    {% set lineDiscountAmount = line.discountAmountTtc ?? null %}
                    {% set lineUnitPriceFinal = line.unitPriceFinalTtc ?? line.unitPriceTtc %}
                    {% set lineUnitPriceBefore = line.unitPriceBeforeDiscountTtc ?? null %}
                    {% set lineTotalFinal = line.lineTotalFinalTtc ?? line.lineTotalTtc %}
                    {% set lineTotalBefore = line.lineTotalBeforeDiscountTtc ?? null %}
                    {% set hasLineDiscount = lineDiscountAmount is not null and lineDiscountAmount != '0.00' and lineDiscountAmount != '0' %}

                    <tr>
                      <td>
                        <div class=\"line-code\">{{ line.articleCode }}</div>
                        <div class=\"line-designation\">{{ line.designation ?: '—' }}</div>
                      </td>

                      <td class=\"text-end\">
                        <div class=\"price-main\">{{ line.quantity }}</div>
                      </td>

                      <td class=\"text-end\">
                        <div class=\"price-main\">{{ lineUnitPriceFinal }} DH</div>
                        {% if hasLineDiscount and lineUnitPriceBefore %}
                          <div class=\"price-before\">{{ lineUnitPriceBefore }} DH</div>
                        {% endif %}
                      </td>

                      <td class=\"text-end\">
                        {% if hasLineDiscount %}
                          <div class=\"discount-value\">
                            {% if lineDiscountRate %}
                              -{{ lineDiscountRate }}%
                            {% else %}
                              -{{ lineDiscountAmount }} DH
                            {% endif %}
                          </div>
                          {% if lineDiscountRate and lineDiscountAmount %}
                            <div class=\"discount-subvalue\">-{{ lineDiscountAmount }} DH</div>
                          {% endif %}
                        {% else %}
                          <span class=\"text-muted\">—</span>
                        {% endif %}
                      </td>

                      <td class=\"text-end\">
                        <div class=\"total-main\">{{ lineTotalFinal }} DH</div>
                        {% if hasLineDiscount and lineTotalBefore %}
                          <div class=\"total-before\">{{ lineTotalBefore }} DH</div>
                        {% endif %}
                      </td>
                    </tr>
                  {% endfor %}
                </tbody>
              </table>
            </div>

            <div class=\"mobile-lines\">
              {% for line in order.lines %}
                {% set lineDiscountRate = line.discountRate ?? null %}
                {% set lineDiscountAmount = line.discountAmountTtc ?? null %}
                {% set lineUnitPriceFinal = line.unitPriceFinalTtc ?? line.unitPriceTtc %}
                {% set lineTotalFinal = line.lineTotalFinalTtc ?? line.lineTotalTtc %}
                {% set hasLineDiscount = lineDiscountAmount is not null and lineDiscountAmount != '0.00' and lineDiscountAmount != '0' %}

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
                      <div class=\"mobile-line-value\">{{ lineUnitPriceFinal }} DH</div>
                    </div>

                    <div class=\"mobile-line-box\">
                      <div class=\"mobile-line-label\">Total</div>
                      <div class=\"mobile-line-value\">{{ lineTotalFinal }} DH</div>
                    </div>

                    <div class=\"mobile-line-box\">
                      <div class=\"mobile-line-label\">Remise</div>
                      <div class=\"mobile-line-value\">
                        {% if hasLineDiscount %}
                          {% if lineDiscountRate %}
                            -{{ lineDiscountRate }}%
                          {% else %}
                            -{{ lineDiscountAmount }} DH
                          {% endif %}
                        {% else %}
                          —
                        {% endif %}
                      </div>
                    </div>

                    <div class=\"mobile-line-box\">
                      <div class=\"mobile-line-label\">Montant remise</div>
                      <div class=\"mobile-line-value\">
                        {% if hasLineDiscount %}
                          -{{ lineDiscountAmount }} DH
                        {% else %}
                          —
                        {% endif %}
                      </div>
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
            <div class=\"section-title\">Infos client</div>

            <div class=\"customer-box\">
              <div class=\"customer-line\"><strong>{{ order.companyName ?: '—' }}</strong></div>
              <div class=\"customer-line\">{{ order.address1 ?: '—' }}</div>
              {% if order.address2 %}
                <div class=\"customer-line\">{{ order.address2 }}</div>
              {% endif %}
              <div class=\"customer-line\">{{ order.city ?: '—' }}</div>
              <div class=\"customer-line\">{{ order.customerEmail }}</div>
              <div class=\"customer-line muted\">Code client : {{ order.customerCode ?: '—' }}</div>
            </div>
          </div>
        </div>

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

              {% if hasGlobalDiscount %}
                <div class=\"summary-row\">
                  <span class=\"summary-label\">Remise {{ globalDiscountRate }}%</span>
                  <span class=\"summary-value text-danger\">-{{ globalDiscountAmount }} DH</span>
                </div>
              {% elseif hasDynamicDiscount %}
                <div class=\"summary-row\">
                  <span class=\"summary-label\">Remises variables</span>
                  <span class=\"summary-value text-danger\">-{{ dynamicDiscountTotal|number_format(2, '.', '') }} DH</span>
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
      </div>
    </div>
  </div>
</div>
{% endblock %}
", "admin/order/show.html.twig", "F:\\3sk\\3sk (1)\\templates\\admin\\order\\show.html.twig");
    }
}
