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

/* partials/_header_contacts.html.twig */
class __TwigTemplate_fa03ece407a2bbfbe222ef7e17228741 extends Template
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
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "partials/_header_contacts.html.twig"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "partials/_header_contacts.html.twig"));

        // line 1
        yield "<button class=\"showcase-contact phone-contact-trigger\" type=\"button\" data-bs-toggle=\"modal\" data-bs-target=\"#phoneContactModal\">
  <i class=\"fa-solid fa-phone\" aria-hidden=\"true\"></i>
  <span>Nous appeler<strong>+212 5 22 71 35 72 / 77</strong></span>
</button>
<a class=\"showcase-contact showcase-contact--email\" href=\"mailto:3sk.partsauto@gmail.com\">
  <i class=\"fa-solid fa-envelope\" aria-hidden=\"true\"></i><span>3sk.partsauto@gmail.com</span>
</a>
<a class=\"showcase-contact showcase-contact--location\" href=\"https://www.google.com/maps?q=33.528667,-7.550028\" target=\"_blank\" rel=\"noopener noreferrer\">
  <i class=\"fa-solid fa-location-dot\" aria-hidden=\"true\"></i><span>Casablanca, Maroc<small>Au service des professionnels</small></span>
</a>
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
        return "partials/_header_contacts.html.twig";
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
        return new Source("<button class=\"showcase-contact phone-contact-trigger\" type=\"button\" data-bs-toggle=\"modal\" data-bs-target=\"#phoneContactModal\">
  <i class=\"fa-solid fa-phone\" aria-hidden=\"true\"></i>
  <span>Nous appeler<strong>+212 5 22 71 35 72 / 77</strong></span>
</button>
<a class=\"showcase-contact showcase-contact--email\" href=\"mailto:3sk.partsauto@gmail.com\">
  <i class=\"fa-solid fa-envelope\" aria-hidden=\"true\"></i><span>3sk.partsauto@gmail.com</span>
</a>
<a class=\"showcase-contact showcase-contact--location\" href=\"https://www.google.com/maps?q=33.528667,-7.550028\" target=\"_blank\" rel=\"noopener noreferrer\">
  <i class=\"fa-solid fa-location-dot\" aria-hidden=\"true\"></i><span>Casablanca, Maroc<small>Au service des professionnels</small></span>
</a>
", "partials/_header_contacts.html.twig", "F:\\3sk\\3sk (1)\\templates\\partials\\_header_contacts.html.twig");
    }
}
