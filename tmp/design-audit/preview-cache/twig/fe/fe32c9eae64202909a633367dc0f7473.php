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

/* base.html.twig */
class __TwigTemplate_6de4e3753646c41008851986dc1c707e extends Template
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
            'title' => [$this, 'block_title'],
            'stylesheets' => [$this, 'block_stylesheets'],
            'javascripts' => [$this, 'block_javascripts'],
            'importmap' => [$this, 'block_importmap'],
            'body' => [$this, 'block_body'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        $__internal_5a27a8ba21ca79b61932376b2fa922d2 = $this->extensions["Symfony\\Bundle\\WebProfilerBundle\\Twig\\WebProfilerExtension"];
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "base.html.twig"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "base.html.twig"));

        // line 1
        yield "<!DOCTYPE html>
<html>
    <head>
        <meta charset=\"UTF-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        <title>";
        // line 6
        yield from $this->unwrap()->yieldBlock('title', $context, $blocks);
        yield "</title>
        <link rel=\"icon\" type=\"image/svg+xml\" href=\"";
        // line 7
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Symfony\Bridge\Twig\Extension\AssetExtension']->getAssetUrl("favicon.svg"), "html", null, true);
        yield "?v=1\">
        <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css\" referrerpolicy=\"no-referrer\">
        <script>
            document.documentElement.classList.add('site-is-loading');
            window.setTimeout(function () {
                document.documentElement.classList.remove('site-is-loading');
            }, 7000);
        </script>
        <style>
            .site-page-loader {
                position: fixed;
                inset: 0;
                z-index: 2147483000;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 24px;
                background: var(--brand-bg);
                color: var(--bs-body-color, var(--brand-ink));
                transition: opacity .28s ease, visibility .28s ease;
            }

            html.site-is-loading .site-page-loader {
                display: flex;
            }

            html.site-is-loading body > :not(.site-page-loader):not(script):not(style) {
                opacity: 0;
                pointer-events: none;
            }

            .site-page-loader__panel {
                width: min(420px, 100%);
            }

            .site-page-loader__brand {
                display: flex;
                align-items: center;
                gap: 14px;
                margin-bottom: 18px;
            }

            .site-page-loader__mark {
                display: block;
                width: 64px;
                height: auto;
                flex-shrink: 0;
                object-fit: contain;
            }

            .site-page-loader__title {
                margin: 0;
                font: 700 1rem/1.15 system-ui, -apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif;
            }

            .site-page-loader__subtitle {
                margin: .2rem 0 0;
                color: var(--bs-secondary-color, var(--brand-muted));
                font: 500 .82rem/1.35 system-ui, -apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif;
            }

            .site-page-loader__track {
                height: 6px;
                overflow: hidden;
                border-radius: 999px;
                background: rgba(var(--brand-blue-rgb), .12);
            }

            .site-page-loader__bar {
                width: 0%;
                height: 100%;
                display: block;
                border-radius: inherit;
                background: linear-gradient(90deg, var(--bs-primary, var(--brand-blue)), var(--bs-warning, var(--brand-orange)));
                transition: width .16s ease;
            }

            .site-page-loader__meta {
                display: flex;
                justify-content: space-between;
                gap: 1rem;
                margin-top: 10px;
                color: var(--bs-secondary-color, var(--brand-muted));
                font: 600 .76rem/1.3 system-ui, -apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif;
            }
        </style>
        ";
        // line 93
        yield from $this->unwrap()->yieldBlock('stylesheets', $context, $blocks);
        // line 96
        yield "
        <link rel=\"stylesheet\" href=\"";
        // line 97
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Symfony\Bridge\Twig\Extension\AssetExtension']->getAssetUrl("styles/site-footer.css"), "html", null, true);
        yield "?v=20261016-2\">

        ";
        // line 99
        yield from $this->unwrap()->yieldBlock('javascripts', $context, $blocks);
        // line 105
        yield "    </head>
    <body class=\"";
        // line 106
        yield (((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["app"]) || array_key_exists("app", $context) ? $context["app"] : (function () { throw new RuntimeError('Variable "app" does not exist.', 106, $this->source); })()), "user", [], "any", false, false, false, 106)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("app-connected") : ("app-public"));
        yield "\">
    <div class=\"site-page-loader\" data-site-loader aria-live=\"polite\" aria-label=\"Chargement du site\">
        <div class=\"site-page-loader__panel\">
            <div class=\"site-page-loader__brand\">
                <img class=\"site-page-loader__mark\" src=\"";
        // line 110
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['App\Twig\OptimizedAssetExtension']->optimizedAsset("img/logo-min.png"), "html", null, true);
        yield "\" alt=\"3SK\" width=\"106\" height=\"104\" fetchpriority=\"high\">
                <div>
                    <p class=\"site-page-loader__title\">Préparation du site</p>
                    <p class=\"site-page-loader__subtitle\">Optimisation des visuels et du catalogue.</p>
                </div>
            </div>
            <div class=\"site-page-loader__track\" aria-hidden=\"true\">
                <span class=\"site-page-loader__bar\" data-site-loader-bar></span>
            </div>
            <div class=\"site-page-loader__meta\">
                <span>Chargement</span>
                <span data-site-loader-percent>0%</span>
            </div>
        </div>
    </div>
    <style>
   .whatsapp-float {
    position: fixed;
    right: 20px;
    top: 50%;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #25D366;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    z-index: 1040;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
    touch-action: none;
    user-select: none;
    -webkit-user-select: none;
}

.whatsapp-float:hover {
    color: #fff;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
}
</style>
";
        // line 153
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["app"]) || array_key_exists("app", $context) ? $context["app"] : (function () { throw new RuntimeError('Variable "app" does not exist.', 153, $this->source); })()), "user", [], "any", false, false, false, 153)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 154
            yield "  ";
            yield from $this->load("partials/_header.html.twig", 154)->unwrap()->yield($context);
            // line 155
            yield "  <a
    href=\"https://wa.me/212613577138?text=Bonjour%20je%20souhaite%20avoir%20des%20informations\"
    class=\"whatsapp-float\"
    target=\"_blank\"
    rel=\"noopener noreferrer\"
>
    <i class=\"fa-brands fa-whatsapp\"></i>
</a>
";
        }
        // line 164
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, (isset($context["app"]) || array_key_exists("app", $context) ? $context["app"] : (function () { throw new RuntimeError('Variable "app" does not exist.', 164, $this->source); })()), "flashes", [], "any", false, false, false, 164));
        foreach ($context['_seq'] as $context["label"] => $context["messages"]) {
            // line 165
            yield "  ";
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable($context["messages"]);
            foreach ($context['_seq'] as $context["_key"] => $context["message"]) {
                // line 166
                yield "    <div class=\"container mt-3\">
      <div class=\"alert
        ";
                // line 168
                if (($context["label"] == "success")) {
                    yield "alert-success
        ";
                } elseif ((                // line 169
$context["label"] == "danger")) {
                    yield "alert-danger
        ";
                } elseif ((                // line 170
$context["label"] == "warning")) {
                    yield "alert-warning
        ";
                } elseif ((                // line 171
$context["label"] == "info")) {
                    yield "alert-info
        ";
                } else {
                    // line 172
                    yield "alert-secondary
        ";
                }
                // line 174
                yield "        alert-dismissible fade show\"
        role=\"alert\"
      >
        ";
                // line 177
                yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($context["message"], "html", null, true);
                yield "
        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Fermer\"></button>
      </div>
    </div>
  ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['message'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['label'], $context['messages'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 183
        yield from $this->unwrap()->yieldBlock('body', $context, $blocks);
        // line 184
        yield "
";
        // line 185
        yield from $this->load("partials/_phone_contact_modal.html.twig", 185)->unwrap()->yield($context);
        // line 186
        yield "
";
        // line 187
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["app"]) || array_key_exists("app", $context) ? $context["app"] : (function () { throw new RuntimeError('Variable "app" does not exist.', 187, $this->source); })()), "user", [], "any", false, false, false, 187)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 188
            yield "  ";
            yield from $this->load("partials/_connected_theme.html.twig", 188)->unwrap()->yield($context);
            // line 189
            yield "  ";
            if ((CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, (isset($context["app"]) || array_key_exists("app", $context) ? $context["app"] : (function () { throw new RuntimeError('Variable "app" does not exist.', 189, $this->source); })()), "request", [], "any", false, false, false, 189), "attributes", [], "any", false, false, false, 189), "get", ["_route"], "method", false, false, false, 189) == "home")) {
                // line 190
                yield "    ";
                yield from $this->load("home/sections/_stats_strip.html.twig", 190)->unwrap()->yield($context);
                // line 191
                yield "  ";
            }
            // line 192
            yield "  ";
            yield from $this->load("partials/_footer.html.twig", 192)->unwrap()->yield($context);
        }
        // line 194
        yield "
";
        // line 195
        yield from $this->load("partials/_design_system.html.twig", 195)->unwrap()->yield($context);
        // line 196
        yield "<link rel=\"stylesheet\" href=\"";
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Symfony\Bridge\Twig\Extension\AssetExtension']->getAssetUrl("styles/typography.css"), "html", null, true);
        yield "?v=20261016-2\">
";
        // line 197
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["app"]) || array_key_exists("app", $context) ? $context["app"] : (function () { throw new RuntimeError('Variable "app" does not exist.', 197, $this->source); })()), "user", [], "any", false, false, false, 197)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 198
            yield "  <link rel=\"stylesheet\" href=\"";
            yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Symfony\Bridge\Twig\Extension\AssetExtension']->getAssetUrl("styles/connected-ui.css"), "html", null, true);
            yield "?v=20261016-1\">
";
        }
        // line 200
        yield "
    </body>
    <script>
window.addEventListener('DOMContentLoaded', function () {
    const whatsappBtn = document.querySelector('.whatsapp-float');
    if (!whatsappBtn) return;

    const STORAGE_KEY = 'whatsapp_float_top_v1';
    const MARGIN = 12;

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function applyTop(top) {
        const maxTop = window.innerHeight - whatsappBtn.offsetHeight - MARGIN;
        const safeTop = clamp(top, MARGIN, maxTop);
        whatsappBtn.style.top = safeTop + 'px';
        return safeTop;
    }

    const savedTop = parseFloat(localStorage.getItem(STORAGE_KEY));
    if (!Number.isNaN(savedTop)) {
        applyTop(savedTop);
    } else {
        applyTop(window.innerHeight * 0.5);
    }

    let isDragging = false;
    let moved = false;
    let startY = 0;
    let startTop = 0;

    whatsappBtn.addEventListener('pointerdown', function (e) {
        isDragging = true;
        moved = false;
        startY = e.clientY;
        startTop = parseFloat(whatsappBtn.style.top) || whatsappBtn.getBoundingClientRect().top;

        try {
            whatsappBtn.setPointerCapture(e.pointerId);
        } catch (_) {}
    });

    window.addEventListener('pointermove', function (e) {
        if (!isDragging) return;

        const deltaY = e.clientY - startY;
        if (Math.abs(deltaY) > 4) moved = true;

        applyTop(startTop + deltaY);
    }, { passive: true });

    function stopDrag() {
        if (!isDragging) return;
        isDragging = false;

        const finalTop = parseFloat(whatsappBtn.style.top) || whatsappBtn.getBoundingClientRect().top;
        localStorage.setItem(STORAGE_KEY, String(finalTop));
    }

    window.addEventListener('pointerup', stopDrag);
    window.addEventListener('pointercancel', stopDrag);

    whatsappBtn.addEventListener('click', function (e) {
        if (moved) {
            e.preventDefault();
            e.stopPropagation();
            moved = false;
        }
    }, true);

    window.addEventListener('resize', function () {
        const currentTop = parseFloat(whatsappBtn.style.top) || whatsappBtn.getBoundingClientRect().top;
        applyTop(currentTop);
    });
});
</script>
</html>
";
        
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->leave($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof);

        
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->leave($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof);

        yield from [];
    }

    // line 6
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_title(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        $__internal_5a27a8ba21ca79b61932376b2fa922d2 = $this->extensions["Symfony\\Bundle\\WebProfilerBundle\\Twig\\WebProfilerExtension"];
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "block", "title"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "block", "title"));

        yield "3sk parts auto- Pièces Auto ";
        
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->leave($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof);

        
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->leave($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof);

        yield from [];
    }

    // line 93
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_stylesheets(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        $__internal_5a27a8ba21ca79b61932376b2fa922d2 = $this->extensions["Symfony\\Bundle\\WebProfilerBundle\\Twig\\WebProfilerExtension"];
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "block", "stylesheets"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "block", "stylesheets"));

        // line 94
        yield "            ";
        yield $this->extensions['Symfony\WebpackEncoreBundle\Twig\EntryFilesTwigExtension']->renderWebpackLinkTags("app");
        yield "
        ";
        
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->leave($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof);

        
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->leave($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof);

        yield from [];
    }

    // line 99
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_javascripts(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        $__internal_5a27a8ba21ca79b61932376b2fa922d2 = $this->extensions["Symfony\\Bundle\\WebProfilerBundle\\Twig\\WebProfilerExtension"];
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "block", "javascripts"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "block", "javascripts"));

        // line 100
        yield "    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js\"></script>

            ";
        // line 102
        yield $this->extensions['Symfony\WebpackEncoreBundle\Twig\EntryFilesTwigExtension']->renderWebpackScriptTags("app");
        yield "
            ";
        // line 103
        yield from $this->unwrap()->yieldBlock('importmap', $context, $blocks);
        // line 104
        yield "        ";
        
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->leave($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof);

        
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->leave($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof);

        yield from [];
    }

    // line 103
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_importmap(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        $__internal_5a27a8ba21ca79b61932376b2fa922d2 = $this->extensions["Symfony\\Bundle\\WebProfilerBundle\\Twig\\WebProfilerExtension"];
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "block", "importmap"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "block", "importmap"));

        yield $this->env->getRuntime('Symfony\Bridge\Twig\Extension\ImportMapRuntime')->importmap("app");
        
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->leave($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof);

        
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->leave($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof);

        yield from [];
    }

    // line 183
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

        
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->leave($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof);

        
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->leave($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof);

        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "base.html.twig";
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
        return array (  534 => 183,  511 => 103,  500 => 104,  498 => 103,  494 => 102,  490 => 100,  477 => 99,  463 => 94,  450 => 93,  427 => 6,  337 => 200,  331 => 198,  329 => 197,  324 => 196,  322 => 195,  319 => 194,  315 => 192,  312 => 191,  309 => 190,  306 => 189,  303 => 188,  301 => 187,  298 => 186,  296 => 185,  293 => 184,  291 => 183,  276 => 177,  271 => 174,  267 => 172,  262 => 171,  258 => 170,  254 => 169,  250 => 168,  246 => 166,  241 => 165,  237 => 164,  226 => 155,  223 => 154,  221 => 153,  175 => 110,  168 => 106,  165 => 105,  163 => 99,  158 => 97,  155 => 96,  153 => 93,  64 => 7,  60 => 6,  53 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("<!DOCTYPE html>
<html>
    <head>
        <meta charset=\"UTF-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        <title>{% block title %}3sk parts auto- Pièces Auto {% endblock %}</title>
        <link rel=\"icon\" type=\"image/svg+xml\" href=\"{{ asset('favicon.svg') }}?v=1\">
        <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css\" referrerpolicy=\"no-referrer\">
        <script>
            document.documentElement.classList.add('site-is-loading');
            window.setTimeout(function () {
                document.documentElement.classList.remove('site-is-loading');
            }, 7000);
        </script>
        <style>
            .site-page-loader {
                position: fixed;
                inset: 0;
                z-index: 2147483000;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 24px;
                background: var(--brand-bg);
                color: var(--bs-body-color, var(--brand-ink));
                transition: opacity .28s ease, visibility .28s ease;
            }

            html.site-is-loading .site-page-loader {
                display: flex;
            }

            html.site-is-loading body > :not(.site-page-loader):not(script):not(style) {
                opacity: 0;
                pointer-events: none;
            }

            .site-page-loader__panel {
                width: min(420px, 100%);
            }

            .site-page-loader__brand {
                display: flex;
                align-items: center;
                gap: 14px;
                margin-bottom: 18px;
            }

            .site-page-loader__mark {
                display: block;
                width: 64px;
                height: auto;
                flex-shrink: 0;
                object-fit: contain;
            }

            .site-page-loader__title {
                margin: 0;
                font: 700 1rem/1.15 system-ui, -apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif;
            }

            .site-page-loader__subtitle {
                margin: .2rem 0 0;
                color: var(--bs-secondary-color, var(--brand-muted));
                font: 500 .82rem/1.35 system-ui, -apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif;
            }

            .site-page-loader__track {
                height: 6px;
                overflow: hidden;
                border-radius: 999px;
                background: rgba(var(--brand-blue-rgb), .12);
            }

            .site-page-loader__bar {
                width: 0%;
                height: 100%;
                display: block;
                border-radius: inherit;
                background: linear-gradient(90deg, var(--bs-primary, var(--brand-blue)), var(--bs-warning, var(--brand-orange)));
                transition: width .16s ease;
            }

            .site-page-loader__meta {
                display: flex;
                justify-content: space-between;
                gap: 1rem;
                margin-top: 10px;
                color: var(--bs-secondary-color, var(--brand-muted));
                font: 600 .76rem/1.3 system-ui, -apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif;
            }
        </style>
        {% block stylesheets %}
            {{ encore_entry_link_tags('app') }}
        {% endblock %}

        <link rel=\"stylesheet\" href=\"{{ asset('styles/site-footer.css') }}?v=20261016-2\">

        {% block javascripts %}
    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js\"></script>

            {{ encore_entry_script_tags('app') }}
            {% block importmap %}{{ importmap('app') }}{% endblock %}
        {% endblock %}
    </head>
    <body class=\"{{ app.user ? 'app-connected' : 'app-public' }}\">
    <div class=\"site-page-loader\" data-site-loader aria-live=\"polite\" aria-label=\"Chargement du site\">
        <div class=\"site-page-loader__panel\">
            <div class=\"site-page-loader__brand\">
                <img class=\"site-page-loader__mark\" src=\"{{ optimized_asset('img/logo-min.png') }}\" alt=\"3SK\" width=\"106\" height=\"104\" fetchpriority=\"high\">
                <div>
                    <p class=\"site-page-loader__title\">Préparation du site</p>
                    <p class=\"site-page-loader__subtitle\">Optimisation des visuels et du catalogue.</p>
                </div>
            </div>
            <div class=\"site-page-loader__track\" aria-hidden=\"true\">
                <span class=\"site-page-loader__bar\" data-site-loader-bar></span>
            </div>
            <div class=\"site-page-loader__meta\">
                <span>Chargement</span>
                <span data-site-loader-percent>0%</span>
            </div>
        </div>
    </div>
    <style>
   .whatsapp-float {
    position: fixed;
    right: 20px;
    top: 50%;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #25D366;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    z-index: 1040;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
    touch-action: none;
    user-select: none;
    -webkit-user-select: none;
}

.whatsapp-float:hover {
    color: #fff;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
}
</style>
{% if app.user %}
  {% include 'partials/_header.html.twig' %}
  <a
    href=\"https://wa.me/212613577138?text=Bonjour%20je%20souhaite%20avoir%20des%20informations\"
    class=\"whatsapp-float\"
    target=\"_blank\"
    rel=\"noopener noreferrer\"
>
    <i class=\"fa-brands fa-whatsapp\"></i>
</a>
{% endif %}
{% for label, messages in app.flashes %}
  {% for message in messages %}
    <div class=\"container mt-3\">
      <div class=\"alert
        {% if label == 'success' %}alert-success
        {% elseif label == 'danger' %}alert-danger
        {% elseif label == 'warning' %}alert-warning
        {% elseif label == 'info' %}alert-info
        {% else %}alert-secondary
        {% endif %}
        alert-dismissible fade show\"
        role=\"alert\"
      >
        {{ message }}
        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Fermer\"></button>
      </div>
    </div>
  {% endfor %}
{% endfor %}
{% block body %}{% endblock %}

{% include 'partials/_phone_contact_modal.html.twig' %}

{% if app.user %}
  {% include 'partials/_connected_theme.html.twig' %}
  {% if app.request.attributes.get('_route') == 'home' %}
    {% include 'home/sections/_stats_strip.html.twig' %}
  {% endif %}
  {% include 'partials/_footer.html.twig' %}
{% endif %}

{% include 'partials/_design_system.html.twig' %}
<link rel=\"stylesheet\" href=\"{{ asset('styles/typography.css') }}?v=20261016-2\">
{% if app.user %}
  <link rel=\"stylesheet\" href=\"{{ asset('styles/connected-ui.css') }}?v=20261016-1\">
{% endif %}

    </body>
    <script>
window.addEventListener('DOMContentLoaded', function () {
    const whatsappBtn = document.querySelector('.whatsapp-float');
    if (!whatsappBtn) return;

    const STORAGE_KEY = 'whatsapp_float_top_v1';
    const MARGIN = 12;

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function applyTop(top) {
        const maxTop = window.innerHeight - whatsappBtn.offsetHeight - MARGIN;
        const safeTop = clamp(top, MARGIN, maxTop);
        whatsappBtn.style.top = safeTop + 'px';
        return safeTop;
    }

    const savedTop = parseFloat(localStorage.getItem(STORAGE_KEY));
    if (!Number.isNaN(savedTop)) {
        applyTop(savedTop);
    } else {
        applyTop(window.innerHeight * 0.5);
    }

    let isDragging = false;
    let moved = false;
    let startY = 0;
    let startTop = 0;

    whatsappBtn.addEventListener('pointerdown', function (e) {
        isDragging = true;
        moved = false;
        startY = e.clientY;
        startTop = parseFloat(whatsappBtn.style.top) || whatsappBtn.getBoundingClientRect().top;

        try {
            whatsappBtn.setPointerCapture(e.pointerId);
        } catch (_) {}
    });

    window.addEventListener('pointermove', function (e) {
        if (!isDragging) return;

        const deltaY = e.clientY - startY;
        if (Math.abs(deltaY) > 4) moved = true;

        applyTop(startTop + deltaY);
    }, { passive: true });

    function stopDrag() {
        if (!isDragging) return;
        isDragging = false;

        const finalTop = parseFloat(whatsappBtn.style.top) || whatsappBtn.getBoundingClientRect().top;
        localStorage.setItem(STORAGE_KEY, String(finalTop));
    }

    window.addEventListener('pointerup', stopDrag);
    window.addEventListener('pointercancel', stopDrag);

    whatsappBtn.addEventListener('click', function (e) {
        if (moved) {
            e.preventDefault();
            e.stopPropagation();
            moved = false;
        }
    }, true);

    window.addEventListener('resize', function () {
        const currentTop = parseFloat(whatsappBtn.style.top) || whatsappBtn.getBoundingClientRect().top;
        applyTop(currentTop);
    });
});
</script>
</html>
", "base.html.twig", "F:\\3sk\\3sk (1)\\templates\\base.html.twig");
    }
}
