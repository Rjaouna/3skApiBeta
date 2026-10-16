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

/* admin/order/print.html.twig */
class __TwigTemplate_7a12daa46a6d8335de5e08cfc52807b0 extends Template
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

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        $__internal_5a27a8ba21ca79b61932376b2fa922d2 = $this->extensions["Symfony\\Bundle\\WebProfilerBundle\\Twig\\WebProfilerExtension"];
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "admin/order/print.html.twig"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "admin/order/print.html.twig"));

        // line 1
        yield "<!DOCTYPE html>
<html lang=\"fr\">
<head>
  <meta charset=\"UTF-8\">
  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
  <title>Bon de commande ";
        // line 6
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 6, $this->source); })()), "orderNumber", [], "any", false, false, false, 6), "html", null, true);
        yield "</title>
  <style>
    :root {
      --print-primary: #315FB7;
      --print-accent: #FF8A00;
      --print-text: #232B38;
      --print-muted: color-mix(in srgb, var(--print-text) 72%, #FFFFFF);
      --print-line: color-mix(in srgb, var(--print-text) 18%, #FFFFFF);
      --print-soft: #F5F6F8;
    }

    * {
      box-sizing: border-box;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    body {
      margin: 0;
      background: var(--print-soft);
      color: var(--print-text);
      font-family: Arial, Helvetica, sans-serif;
      font-size: 12px;
      line-height: 1.3;
    }

    .print-page {
      width: min(100%, 920px);
      margin: 18px auto;
      padding: 24px 26px;
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 6px 18px rgba(35, 43, 56, .08);
    }

    .print-toolbar {
      width: min(100%, 920px);
      margin: 24px auto 0;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    .print-btn {
      border: 1px solid var(--print-line);
      background: #fff;
      color: var(--print-primary);
      border-radius: 10px;
      padding: 10px 14px;
      font-weight: 700;
      cursor: pointer;
    }

    .print-btn.primary {
      background: var(--print-primary);
      border-color: var(--print-primary);
      color: #fff;
    }

    .order-head {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 18px;
      align-items: start;
      padding-bottom: 14px;
      border-bottom: 2px solid var(--print-primary);
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .brand-logo {
      width: 158px;
      max-height: 60px;
      object-fit: contain;
    }

    .brand-subtitle {
      margin-top: 3px;
      color: var(--print-muted);
      font-size: 10.5px;
      max-width: 360px;
      line-height: 1.25;
    }

    .order-title {
      text-align: right;
    }

    .order-title h1 {
      margin: 0;
      color: var(--print-primary);
      font-size: 25px;
      line-height: 1;
      text-transform: uppercase;
      letter-spacing: .04em;
    }

    .order-title .number {
      margin-top: 7px;
      color: var(--print-text);
      font-weight: 700;
      font-size: 13px;
    }

    .meta-grid {
      display: grid;
      grid-template-columns: 1.1fr .9fr;
      gap: 12px;
      margin-top: 14px;
    }

    .box {
      border: 1px solid var(--print-line);
      border-radius: 11px;
      overflow: hidden;
      background: #fff;
    }

    .box-title {
      padding: 7px 10px;
      background: var(--print-soft);
      color: var(--print-primary);
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .05em;
      font-size: 10px;
    }

    .box-body {
      padding: 10px;
    }

    .client-name {
      margin-bottom: 3px;
      font-size: 14px;
      font-weight: 700;
      color: var(--print-text);
    }

    .muted {
      color: var(--print-muted);
    }

    .client-address {
      margin-top: 3px;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      padding: 4px 0;
      border-bottom: 1px solid var(--print-line);
    }

    .info-row:last-child {
      border-bottom: 0;
    }

    .info-row span:first-child {
      color: var(--print-muted);
    }

    .info-row strong {
      text-align: right;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 14px;
      border: 1px solid var(--print-line);
      border-radius: 11px;
      overflow: hidden;
    }

    thead th {
      background: var(--print-primary);
      color: #fff;
      padding: 8px 9px;
      text-align: left;
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: .05em;
    }

    tbody td {
      padding: 7px 9px;
      border-bottom: 1px solid var(--print-line);
      vertical-align: middle;
    }

    th:nth-child(1),
    td:nth-child(1) {
      width: 22%;
    }

    th:nth-child(3),
    td:nth-child(3) {
      width: 9%;
    }

    tbody tr:nth-child(even) td {
      background: var(--print-soft);
    }

    tbody tr:last-child td {
      border-bottom: 0;
    }

    tbody tr {
      page-break-inside: avoid;
    }

    .text-end {
      text-align: right;
    }

    .ref {
      color: var(--print-primary);
      font-weight: 700;
      white-space: nowrap;
    }

    .designation {
      line-height: 1.25;
    }

    .print-footer {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      margin-top: 20px;
      padding-top: 10px;
      border-top: 1px solid var(--print-line);
      color: var(--print-muted);
      font-size: 10px;
    }

    @media print {
      @page {
        size: A4;
        margin: 8mm;
      }

      body {
        background: #fff;
      }

      .print-toolbar {
        display: none;
      }

      .print-page {
        width: 100%;
        margin: 0;
        padding: 0;
        border-radius: 0;
        box-shadow: none;
      }

      .order-head {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        gap: 18px !important;
        align-items: start !important;
      }

      .order-title {
        text-align: right !important;
      }

      .meta-grid {
        display: grid !important;
        grid-template-columns: 1.1fr .9fr !important;
        gap: 12px !important;
      }

      thead th {
        background: var(--print-primary) !important;
        color: #fff !important;
      }
    }

    @media (max-width: 720px) {
      .print-page {
        margin: 0;
        padding: 18px;
        border-radius: 0;
      }

      .order-head,
      .meta-grid {
        grid-template-columns: 1fr;
      }

      .order-title {
        text-align: left;
      }
    }
  </style>
</head>
<body>
  <div class=\"print-toolbar\">
    <button class=\"print-btn\" type=\"button\" onclick=\"window.close()\">Fermer</button>
    <button class=\"print-btn primary\" type=\"button\" onclick=\"window.print()\">Imprimer</button>
  </div>

  <main class=\"print-page\">
    <header class=\"order-head\">
      <div>
        <div class=\"brand\">
          <img class=\"brand-logo\" src=\"";
        // line 322
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Symfony\Bridge\Twig\Extension\AssetExtension']->getAssetUrl("img/logo-3sk-partsauto-transparent.png"), "html", null, true);
        yield "\" alt=\"3SK Parts Auto\">
        </div>
        <div class=\"brand-subtitle\">
          IMPORTATEUR ET DISTRIBUTEUR DE PIECES AUTOMOBILES CARROSSERIES ET ACCESSOIRES
        </div>
      </div>

      <div class=\"order-title\">
        <h1>Bon de commande</h1>
        <div class=\"number\">";
        // line 331
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 331, $this->source); })()), "orderNumber", [], "any", false, false, false, 331), "html", null, true);
        yield "</div>
      </div>
    </header>

    <section class=\"meta-grid\">
      <div class=\"box\">
        <div class=\"box-title\">Informations client</div>
        <div class=\"box-body\">
          <div class=\"client-name\">";
        // line 339
        yield ((CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 339, $this->source); })()), "companyName", [], "any", false, false, false, 339)) ? ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 339, $this->source); })()), "companyName", [], "any", false, false, false, 339), "html", null, true)) : ("Client non renseigne"));
        yield "</div>
          ";
        // line 340
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 340, $this->source); })()), "customerCode", [], "any", false, false, false, 340)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 341
            yield "            <div class=\"muted\">Code client : ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 341, $this->source); })()), "customerCode", [], "any", false, false, false, 341), "html", null, true);
            yield "</div>
          ";
        }
        // line 343
        yield "          <div class=\"muted client-address\">
            ";
        // line 344
        yield ((CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 344, $this->source); })()), "address1", [], "any", false, false, false, 344)) ? ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 344, $this->source); })()), "address1", [], "any", false, false, false, 344), "html", null, true)) : ("Adresse non renseignee"));
        yield "
            ";
        // line 345
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 345, $this->source); })()), "address2", [], "any", false, false, false, 345)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield " - ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 345, $this->source); })()), "address2", [], "any", false, false, false, 345), "html", null, true);
        }
        // line 346
        yield "            ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 346, $this->source); })()), "city", [], "any", false, false, false, 346)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield " - ";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 346, $this->source); })()), "city", [], "any", false, false, false, 346), "html", null, true);
        }
        // line 347
        yield "          </div>
        </div>
      </div>

      <div class=\"box\">
        <div class=\"box-title\">Commande</div>
        <div class=\"box-body\">
          <div class=\"info-row\">
            <span>Date</span>
            <strong>";
        // line 356
        yield (((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 356, $this->source); })()), "createdAt", [], "any", false, false, false, 356)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 356, $this->source); })()), "createdAt", [], "any", false, false, false, 356), "d/m/Y H:i"), "html", null, true)) : ("-"));
        yield "</strong>
          </div>
          <div class=\"info-row\">
            <span>Statut</span>
            <strong>";
        // line 360
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 360, $this->source); })()), "statusLabel", [], "any", false, false, false, 360), "html", null, true);
        yield "</strong>
          </div>
          <div class=\"info-row\">
            <span>Articles</span>
            <strong>";
        // line 364
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(Twig\Extension\CoreExtension::length($this->env->getCharset(), CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 364, $this->source); })()), "lines", [], "any", false, false, false, 364)), "html", null, true);
        yield "</strong>
          </div>
        </div>
      </div>
    </section>

    <table>
      <thead>
        <tr>
          <th>Reference</th>
          <th>Article</th>
          <th class=\"text-end\">Qte</th>
        </tr>
      </thead>
      <tbody>
        ";
        // line 379
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, (isset($context["order"]) || array_key_exists("order", $context) ? $context["order"] : (function () { throw new RuntimeError('Variable "order" does not exist.', 379, $this->source); })()), "lines", [], "any", false, false, false, 379));
        $context['_iterated'] = false;
        foreach ($context['_seq'] as $context["_key"] => $context["line"]) {
            // line 380
            yield "          <tr>
            <td><span class=\"ref\">";
            // line 381
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "articleCode", [], "any", false, false, false, 381), "html", null, true);
            yield "</span></td>
            <td class=\"designation\">";
            // line 382
            yield ((CoreExtension::getAttribute($this->env, $this->source, $context["line"], "designation", [], "any", false, false, false, 382)) ? ($this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "designation", [], "any", false, false, false, 382), "html", null, true)) : ("-"));
            yield "</td>
            <td class=\"text-end\">";
            // line 383
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape(CoreExtension::getAttribute($this->env, $this->source, $context["line"], "quantity", [], "any", false, false, false, 383), "html", null, true);
            yield "</td>
          </tr>
        ";
            $context['_iterated'] = true;
        }
        // line 385
        if (!$context['_iterated']) {
            // line 386
            yield "          <tr>
            <td colspan=\"3\" class=\"muted\">Aucun article dans cette commande.</td>
          </tr>
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['line'], $context['_parent'], $context['_iterated']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 390
        yield "      </tbody>
    </table>

    <footer class=\"print-footer\">
      <span>3SK Parts Auto</span>
      <span>Document genere le ";
        // line 395
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Twig\Extension\CoreExtension']->formatDate("now", "d/m/Y H:i"), "html", null, true);
        yield "</span>
    </footer>
  </main>

  <script>
    window.addEventListener('load', function () {
      window.setTimeout(function () {
        window.print();
      }, 350);
    });
  </script>
</body>
</html>
";
        
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->leave($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof);

        
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->leave($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof);

        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "admin/order/print.html.twig";
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
        return array (  511 => 395,  504 => 390,  495 => 386,  493 => 385,  486 => 383,  482 => 382,  478 => 381,  475 => 380,  470 => 379,  452 => 364,  445 => 360,  438 => 356,  427 => 347,  421 => 346,  416 => 345,  412 => 344,  409 => 343,  403 => 341,  401 => 340,  397 => 339,  386 => 331,  374 => 322,  55 => 6,  48 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("<!DOCTYPE html>
<html lang=\"fr\">
<head>
  <meta charset=\"UTF-8\">
  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
  <title>Bon de commande {{ order.orderNumber }}</title>
  <style>
    :root {
      --print-primary: #315FB7;
      --print-accent: #FF8A00;
      --print-text: #232B38;
      --print-muted: color-mix(in srgb, var(--print-text) 72%, #FFFFFF);
      --print-line: color-mix(in srgb, var(--print-text) 18%, #FFFFFF);
      --print-soft: #F5F6F8;
    }

    * {
      box-sizing: border-box;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    body {
      margin: 0;
      background: var(--print-soft);
      color: var(--print-text);
      font-family: Arial, Helvetica, sans-serif;
      font-size: 12px;
      line-height: 1.3;
    }

    .print-page {
      width: min(100%, 920px);
      margin: 18px auto;
      padding: 24px 26px;
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 6px 18px rgba(35, 43, 56, .08);
    }

    .print-toolbar {
      width: min(100%, 920px);
      margin: 24px auto 0;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    .print-btn {
      border: 1px solid var(--print-line);
      background: #fff;
      color: var(--print-primary);
      border-radius: 10px;
      padding: 10px 14px;
      font-weight: 700;
      cursor: pointer;
    }

    .print-btn.primary {
      background: var(--print-primary);
      border-color: var(--print-primary);
      color: #fff;
    }

    .order-head {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 18px;
      align-items: start;
      padding-bottom: 14px;
      border-bottom: 2px solid var(--print-primary);
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .brand-logo {
      width: 158px;
      max-height: 60px;
      object-fit: contain;
    }

    .brand-subtitle {
      margin-top: 3px;
      color: var(--print-muted);
      font-size: 10.5px;
      max-width: 360px;
      line-height: 1.25;
    }

    .order-title {
      text-align: right;
    }

    .order-title h1 {
      margin: 0;
      color: var(--print-primary);
      font-size: 25px;
      line-height: 1;
      text-transform: uppercase;
      letter-spacing: .04em;
    }

    .order-title .number {
      margin-top: 7px;
      color: var(--print-text);
      font-weight: 700;
      font-size: 13px;
    }

    .meta-grid {
      display: grid;
      grid-template-columns: 1.1fr .9fr;
      gap: 12px;
      margin-top: 14px;
    }

    .box {
      border: 1px solid var(--print-line);
      border-radius: 11px;
      overflow: hidden;
      background: #fff;
    }

    .box-title {
      padding: 7px 10px;
      background: var(--print-soft);
      color: var(--print-primary);
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .05em;
      font-size: 10px;
    }

    .box-body {
      padding: 10px;
    }

    .client-name {
      margin-bottom: 3px;
      font-size: 14px;
      font-weight: 700;
      color: var(--print-text);
    }

    .muted {
      color: var(--print-muted);
    }

    .client-address {
      margin-top: 3px;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      padding: 4px 0;
      border-bottom: 1px solid var(--print-line);
    }

    .info-row:last-child {
      border-bottom: 0;
    }

    .info-row span:first-child {
      color: var(--print-muted);
    }

    .info-row strong {
      text-align: right;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 14px;
      border: 1px solid var(--print-line);
      border-radius: 11px;
      overflow: hidden;
    }

    thead th {
      background: var(--print-primary);
      color: #fff;
      padding: 8px 9px;
      text-align: left;
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: .05em;
    }

    tbody td {
      padding: 7px 9px;
      border-bottom: 1px solid var(--print-line);
      vertical-align: middle;
    }

    th:nth-child(1),
    td:nth-child(1) {
      width: 22%;
    }

    th:nth-child(3),
    td:nth-child(3) {
      width: 9%;
    }

    tbody tr:nth-child(even) td {
      background: var(--print-soft);
    }

    tbody tr:last-child td {
      border-bottom: 0;
    }

    tbody tr {
      page-break-inside: avoid;
    }

    .text-end {
      text-align: right;
    }

    .ref {
      color: var(--print-primary);
      font-weight: 700;
      white-space: nowrap;
    }

    .designation {
      line-height: 1.25;
    }

    .print-footer {
      display: flex;
      justify-content: space-between;
      gap: 12px;
      margin-top: 20px;
      padding-top: 10px;
      border-top: 1px solid var(--print-line);
      color: var(--print-muted);
      font-size: 10px;
    }

    @media print {
      @page {
        size: A4;
        margin: 8mm;
      }

      body {
        background: #fff;
      }

      .print-toolbar {
        display: none;
      }

      .print-page {
        width: 100%;
        margin: 0;
        padding: 0;
        border-radius: 0;
        box-shadow: none;
      }

      .order-head {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        gap: 18px !important;
        align-items: start !important;
      }

      .order-title {
        text-align: right !important;
      }

      .meta-grid {
        display: grid !important;
        grid-template-columns: 1.1fr .9fr !important;
        gap: 12px !important;
      }

      thead th {
        background: var(--print-primary) !important;
        color: #fff !important;
      }
    }

    @media (max-width: 720px) {
      .print-page {
        margin: 0;
        padding: 18px;
        border-radius: 0;
      }

      .order-head,
      .meta-grid {
        grid-template-columns: 1fr;
      }

      .order-title {
        text-align: left;
      }
    }
  </style>
</head>
<body>
  <div class=\"print-toolbar\">
    <button class=\"print-btn\" type=\"button\" onclick=\"window.close()\">Fermer</button>
    <button class=\"print-btn primary\" type=\"button\" onclick=\"window.print()\">Imprimer</button>
  </div>

  <main class=\"print-page\">
    <header class=\"order-head\">
      <div>
        <div class=\"brand\">
          <img class=\"brand-logo\" src=\"{{ asset('img/logo-3sk-partsauto-transparent.png') }}\" alt=\"3SK Parts Auto\">
        </div>
        <div class=\"brand-subtitle\">
          IMPORTATEUR ET DISTRIBUTEUR DE PIECES AUTOMOBILES CARROSSERIES ET ACCESSOIRES
        </div>
      </div>

      <div class=\"order-title\">
        <h1>Bon de commande</h1>
        <div class=\"number\">{{ order.orderNumber }}</div>
      </div>
    </header>

    <section class=\"meta-grid\">
      <div class=\"box\">
        <div class=\"box-title\">Informations client</div>
        <div class=\"box-body\">
          <div class=\"client-name\">{{ order.companyName ?: 'Client non renseigne' }}</div>
          {% if order.customerCode %}
            <div class=\"muted\">Code client : {{ order.customerCode }}</div>
          {% endif %}
          <div class=\"muted client-address\">
            {{ order.address1 ?: 'Adresse non renseignee' }}
            {% if order.address2 %} - {{ order.address2 }}{% endif %}
            {% if order.city %} - {{ order.city }}{% endif %}
          </div>
        </div>
      </div>

      <div class=\"box\">
        <div class=\"box-title\">Commande</div>
        <div class=\"box-body\">
          <div class=\"info-row\">
            <span>Date</span>
            <strong>{{ order.createdAt ? order.createdAt|date('d/m/Y H:i') : '-' }}</strong>
          </div>
          <div class=\"info-row\">
            <span>Statut</span>
            <strong>{{ order.statusLabel }}</strong>
          </div>
          <div class=\"info-row\">
            <span>Articles</span>
            <strong>{{ order.lines|length }}</strong>
          </div>
        </div>
      </div>
    </section>

    <table>
      <thead>
        <tr>
          <th>Reference</th>
          <th>Article</th>
          <th class=\"text-end\">Qte</th>
        </tr>
      </thead>
      <tbody>
        {% for line in order.lines %}
          <tr>
            <td><span class=\"ref\">{{ line.articleCode }}</span></td>
            <td class=\"designation\">{{ line.designation ?: '-' }}</td>
            <td class=\"text-end\">{{ line.quantity }}</td>
          </tr>
        {% else %}
          <tr>
            <td colspan=\"3\" class=\"muted\">Aucun article dans cette commande.</td>
          </tr>
        {% endfor %}
      </tbody>
    </table>

    <footer class=\"print-footer\">
      <span>3SK Parts Auto</span>
      <span>Document genere le {{ 'now'|date('d/m/Y H:i') }}</span>
    </footer>
  </main>

  <script>
    window.addEventListener('load', function () {
      window.setTimeout(function () {
        window.print();
      }, 350);
    });
  </script>
</body>
</html>
", "admin/order/print.html.twig", "F:\\3sk\\3sk (1)\\templates\\admin\\order\\print.html.twig");
    }
}
