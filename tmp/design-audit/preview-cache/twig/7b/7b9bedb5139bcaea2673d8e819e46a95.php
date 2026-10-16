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

/* partials/_header_facts.html.twig */
class __TwigTemplate_5d109f9abd01536c4b8ee7fc7cf3efdb extends Template
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
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "partials/_header_facts.html.twig"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "partials/_header_facts.html.twig"));

        // line 1
        yield "<div class=\"showcase-topbar__facts\" aria-label=\"3SK Parts Auto\">
  <span><i class=\"fa-solid fa-shield-halved\" aria-hidden=\"true\"></i>Pièces et<br>carrosserie</span>
  <span><i class=\"fa-solid fa-boxes-stacked\" aria-hidden=\"true\"></i>Importateur<br>et distributeur</span>
  <span><i class=\"fa-solid fa-headset\" aria-hidden=\"true\"></i>Une équipe<br>à votre service</span>
</div>
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
        return "partials/_header_facts.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  48 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("<div class=\"showcase-topbar__facts\" aria-label=\"3SK Parts Auto\">
  <span><i class=\"fa-solid fa-shield-halved\" aria-hidden=\"true\"></i>Pièces et<br>carrosserie</span>
  <span><i class=\"fa-solid fa-boxes-stacked\" aria-hidden=\"true\"></i>Importateur<br>et distributeur</span>
  <span><i class=\"fa-solid fa-headset\" aria-hidden=\"true\"></i>Une équipe<br>à votre service</span>
</div>
", "partials/_header_facts.html.twig", "F:\\3sk\\3sk (1)\\templates\\partials\\_header_facts.html.twig");
    }
}
