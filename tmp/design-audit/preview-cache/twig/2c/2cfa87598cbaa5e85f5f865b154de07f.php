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

/* partials/_site_footer.html.twig */
class __TwigTemplate_1ce741d4b16a474bdb62b55abb2641e2 extends Template
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
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "partials/_site_footer.html.twig"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "partials/_site_footer.html.twig"));

        // line 1
        $context["footer_connected"] = ((array_key_exists("footer_connected", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["footer_connected"]) || array_key_exists("footer_connected", $context) ? $context["footer_connected"] : (function () { throw new RuntimeError('Variable "footer_connected" does not exist.', 1, $this->source); })()), (((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["app"]) || array_key_exists("app", $context) ? $context["app"] : (function () { throw new RuntimeError('Variable "app" does not exist.', 1, $this->source); })()), "user", [], "any", false, false, false, 1)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (true) : (false)))) : ((((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["app"]) || array_key_exists("app", $context) ? $context["app"] : (function () { throw new RuntimeError('Variable "app" does not exist.', 1, $this->source); })()), "user", [], "any", false, false, false, 1)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (true) : (false))));
        // line 2
        $context["footer_home_href"] = ((array_key_exists("footer_home_href", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["footer_home_href"]) || array_key_exists("footer_home_href", $context) ? $context["footer_home_href"] : (function () { throw new RuntimeError('Variable "footer_home_href" does not exist.', 2, $this->source); })()), $this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("home"))) : ($this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("home")));
        // line 3
        $context["footer_anchor_prefix"] = ((array_key_exists("footer_anchor_prefix", $context)) ? (Twig\Extension\CoreExtension::default((isset($context["footer_anchor_prefix"]) || array_key_exists("footer_anchor_prefix", $context) ? $context["footer_anchor_prefix"] : (function () { throw new RuntimeError('Variable "footer_anchor_prefix" does not exist.', 3, $this->source); })()), ((((isset($context["footer_home_href"]) || array_key_exists("footer_home_href", $context) ? $context["footer_home_href"] : (function () { throw new RuntimeError('Variable "footer_home_href" does not exist.', 3, $this->source); })()) == $this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("home"))) ? ($this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("home")) : ("")))) : (((((isset($context["footer_home_href"]) || array_key_exists("footer_home_href", $context) ? $context["footer_home_href"] : (function () { throw new RuntimeError('Variable "footer_home_href" does not exist.', 3, $this->source); })()) == $this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("home"))) ? ($this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("home")) : (""))));
        // line 4
        $context["footer_photo"] = $this->extensions['App\Twig\OptimizedAssetExtension']->optimizedAsset("img/slides/080920251757318688-69ef174b1e570-6a246a350e402.webp");
        // line 5
        yield "
<footer class=\"site-footer site-footer--showcase\" style=\"--footer-photo: url('";
        // line 6
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["footer_photo"]) || array_key_exists("footer_photo", $context) ? $context["footer_photo"] : (function () { throw new RuntimeError('Variable "footer_photo" does not exist.', 6, $this->source); })()), "html", null, true);
        yield "')\">
  <div class=\"site-footer__main\">
    <div class=\"site-footer__inner\">
      <div class=\"site-footer__identity\">
        <a class=\"site-footer__logo\" href=\"";
        // line 10
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["footer_home_href"]) || array_key_exists("footer_home_href", $context) ? $context["footer_home_href"] : (function () { throw new RuntimeError('Variable "footer_home_href" does not exist.', 10, $this->source); })()), "html", null, true);
        yield "\" aria-label=\"3SK Parts Auto, accueil\">
          <img src=\"";
        // line 11
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['App\Twig\OptimizedAssetExtension']->optimizedAsset("img/logo-3sk-partsauto-transparent.png"), "html", null, true);
        yield "\" alt=\"3SK Parts Auto\" width=\"290\" height=\"78\" loading=\"lazy\" decoding=\"async\">
        </a>
        <p class=\"site-footer__lead\">IMPORTATEUR ET DISTRIBUTEUR DE PIECES AUTOMOBILES CARROSSERIES ET ACCESSOIRES</p>
        <p class=\"site-footer__address\"><i class=\"fa-solid fa-location-dot\" aria-hidden=\"true\"></i><span>HAY ENNASR 2 Nr 6 lahraouyine Casablanca</span></p>
        <div class=\"site-footer__contacts\">
          <p><i class=\"fa-solid fa-phone\" aria-hidden=\"true\"></i><span>Service client : <button class=\"phone-contact-trigger\" type=\"button\" data-bs-toggle=\"modal\" data-bs-target=\"#phoneContactModal\">Voir les numéros</button></span></p>
          <p><i class=\"fa-solid fa-envelope\" aria-hidden=\"true\"></i><span>Email : <a href=\"mailto:3sk.partsauto@gmail.com\">3sk.partsauto@gmail.com</a></span></p>
        </div>
        <div class=\"site-footer__social\" aria-label=\"Réseaux sociaux\">
          <a href=\"#\" aria-label=\"Facebook\"><i class=\"fa-brands fa-facebook-f\" aria-hidden=\"true\"></i></a>
          <a href=\"#\" aria-label=\"Instagram\"><i class=\"fa-brands fa-instagram\" aria-hidden=\"true\"></i></a>
          <a href=\"https://wa.me/212613577138\" target=\"_blank\" rel=\"noopener noreferrer\" aria-label=\"WhatsApp\"><i class=\"fa-brands fa-whatsapp\" aria-hidden=\"true\"></i></a>
        </div>
      </div>

      <div class=\"site-footer__support\">
        <section class=\"site-footer__panel\" aria-labelledby=\"footer-payment-title\">
          <div class=\"site-footer__heading-row\">
            <i class=\"fa-solid fa-shield-halved\" aria-hidden=\"true\"></i>
            <h2 class=\"site-footer__heading\" id=\"footer-payment-title\">Paiement sécurisé</h2>
          </div>
          <div class=\"site-footer__payments\">
            <span class=\"site-footer__payment\"><img src=\"";
        // line 33
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Symfony\Bridge\Twig\Extension\AssetExtension']->getAssetUrl("img/payment/cmi-logo.png"), "html", null, true);
        yield "\" alt=\"CMI\" width=\"60\" height=\"40\" loading=\"lazy\" decoding=\"async\"></span>
            <span class=\"site-footer__payment site-footer__payment--mastercard\" role=\"img\" aria-label=\"Mastercard\"><span></span><span></span></span>
            <span class=\"site-footer__payment\"><img src=\"";
        // line 35
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Symfony\Bridge\Twig\Extension\AssetExtension']->getAssetUrl("img/payment/visa-logo.png"), "html", null, true);
        yield "\" alt=\"VISA\" width=\"60\" height=\"19\" loading=\"lazy\" decoding=\"async\"></span>
          </div>
        </section>
        <section class=\"site-footer__panel\" aria-labelledby=\"footer-help-title\">
          <div class=\"site-footer__heading-row\">
            <i class=\"fa-solid fa-headset\" aria-hidden=\"true\"></i>
            <h2 class=\"site-footer__heading\" id=\"footer-help-title\">Besoin d'aide ?</h2>
          </div>
          <ul class=\"site-footer__list\">
            <li><a href=\"";
        // line 44
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["footer_anchor_prefix"]) || array_key_exists("footer_anchor_prefix", $context) ? $context["footer_anchor_prefix"] : (function () { throw new RuntimeError('Variable "footer_anchor_prefix" does not exist.', 44, $this->source); })()), "html", null, true);
        yield "#qui-sommes-nous\"><i class=\"fa-solid fa-circle-info\" aria-hidden=\"true\"></i><span>À propos</span><i class=\"fa-solid fa-chevron-right\" aria-hidden=\"true\"></i></a></li>
            <li><a href=\"";
        // line 45
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["footer_anchor_prefix"]) || array_key_exists("footer_anchor_prefix", $context) ? $context["footer_anchor_prefix"] : (function () { throw new RuntimeError('Variable "footer_anchor_prefix" does not exist.', 45, $this->source); })()), "html", null, true);
        yield "#services\"><i class=\"fa-solid fa-gear\" aria-hidden=\"true\"></i><span>Services</span><i class=\"fa-solid fa-chevron-right\" aria-hidden=\"true\"></i></a></li>
            <li><a href=\"";
        // line 46
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["footer_anchor_prefix"]) || array_key_exists("footer_anchor_prefix", $context) ? $context["footer_anchor_prefix"] : (function () { throw new RuntimeError('Variable "footer_anchor_prefix" does not exist.', 46, $this->source); })()), "html", null, true);
        yield "#contact\"><i class=\"fa-solid fa-headset\" aria-hidden=\"true\"></i><span>Contact</span><i class=\"fa-solid fa-chevron-right\" aria-hidden=\"true\"></i></a></li>
          </ul>
        </section>
      </div>

      <nav class=\"site-footer__navigation\" aria-labelledby=\"footer-nav-title\">
        <div class=\"site-footer__heading-row\">
          <i class=\"fa-solid fa-compass\" aria-hidden=\"true\"></i>
          <h2 class=\"site-footer__heading\" id=\"footer-nav-title\">Navigation</h2>
        </div>
        <ul class=\"site-footer__list\">
          <li><a href=\"";
        // line 57
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["footer_anchor_prefix"]) || array_key_exists("footer_anchor_prefix", $context) ? $context["footer_anchor_prefix"] : (function () { throw new RuntimeError('Variable "footer_anchor_prefix" does not exist.', 57, $this->source); })()), "html", null, true);
        yield "#accueil\"><i class=\"fa-solid fa-house\" aria-hidden=\"true\"></i><span>Accueil</span><i class=\"fa-solid fa-chevron-right\" aria-hidden=\"true\"></i></a></li>
          <li><a href=\"";
        // line 58
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["footer_anchor_prefix"]) || array_key_exists("footer_anchor_prefix", $context) ? $context["footer_anchor_prefix"] : (function () { throw new RuntimeError('Variable "footer_anchor_prefix" does not exist.', 58, $this->source); })()), "html", null, true);
        yield "#marques\"><i class=\"fa-solid fa-car-side\" aria-hidden=\"true\"></i><span>Marques</span><i class=\"fa-solid fa-chevron-right\" aria-hidden=\"true\"></i></a></li>
          <li><a href=\"";
        // line 59
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape((isset($context["footer_anchor_prefix"]) || array_key_exists("footer_anchor_prefix", $context) ? $context["footer_anchor_prefix"] : (function () { throw new RuntimeError('Variable "footer_anchor_prefix" does not exist.', 59, $this->source); })()), "html", null, true);
        yield "#contact\"><i class=\"fa-solid fa-paper-plane\" aria-hidden=\"true\"></i><span>Demande de contact</span><i class=\"fa-solid fa-chevron-right\" aria-hidden=\"true\"></i></a></li>
          <li><a href=\"";
        // line 60
        yield (((($tmp = CoreExtension::getAttribute($this->env, $this->source, (isset($context["app"]) || array_key_exists("app", $context) ? $context["app"] : (function () { throw new RuntimeError('Variable "app" does not exist.', 60, $this->source); })()), "user", [], "any", false, false, false, 60)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ($this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("account_profile")) : ($this->extensions['Symfony\Bridge\Twig\Extension\RoutingExtension']->getPath("app_login")));
        yield "\"><i class=\"fa-solid fa-user\" aria-hidden=\"true\"></i><span>Espace client</span><i class=\"fa-solid fa-chevron-right\" aria-hidden=\"true\"></i></a></li>
        </ul>
      </nav>

      <section class=\"site-footer__panel site-footer__location\" aria-labelledby=\"footer-location-title\">
        <div class=\"site-footer__heading-row\">
          <i class=\"fa-solid fa-location-dot\" aria-hidden=\"true\"></i>
          <h2 class=\"site-footer__heading\" id=\"footer-location-title\">Notre localisation</h2>
        </div>
        <div class=\"site-footer__map\">
          <iframe title=\"Localisation 3SK Parts Auto Casablanca\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\" src=\"https://www.google.com/maps?q=33.528667,-7.550028&z=12&output=embed\"></iframe>
        </div>
        <p class=\"site-footer__map-caption\"><i class=\"fa-solid fa-location-dot\" aria-hidden=\"true\"></i><span>HAY ENNASR 2 Nr 6 lahraouyine Casablanca</span></p>
      </section>
    </div>
  </div>
  <div class=\"site-footer__bottom\">
    <div class=\"site-footer__bottom-inner\">
      <span>3SK Parts Auto - Tous droits réservés ";
        // line 78
        yield $this->env->getRuntime('Twig\Runtime\EscaperRuntime')->escape($this->extensions['Twig\Extension\CoreExtension']->formatDate("now", "Y"), "html", null, true);
        yield "</span>
      <span>Importateur et distributeur de pièces automobiles, carrosseries et accessoires</span>
    </div>
  </div>
</footer>
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
        return "partials/_site_footer.html.twig";
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
        return array (  167 => 78,  146 => 60,  142 => 59,  138 => 58,  134 => 57,  120 => 46,  116 => 45,  112 => 44,  100 => 35,  95 => 33,  70 => 11,  66 => 10,  59 => 6,  56 => 5,  54 => 4,  52 => 3,  50 => 2,  48 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("{% set footer_connected = footer_connected|default(app.user ? true : false) %}
{% set footer_home_href = footer_home_href|default(path('home')) %}
{% set footer_anchor_prefix = footer_anchor_prefix|default(footer_home_href == path('home') ? path('home') : '') %}
{% set footer_photo = optimized_asset('img/slides/080920251757318688-69ef174b1e570-6a246a350e402.webp') %}

<footer class=\"site-footer site-footer--showcase\" style=\"--footer-photo: url('{{ footer_photo }}')\">
  <div class=\"site-footer__main\">
    <div class=\"site-footer__inner\">
      <div class=\"site-footer__identity\">
        <a class=\"site-footer__logo\" href=\"{{ footer_home_href }}\" aria-label=\"3SK Parts Auto, accueil\">
          <img src=\"{{ optimized_asset('img/logo-3sk-partsauto-transparent.png') }}\" alt=\"3SK Parts Auto\" width=\"290\" height=\"78\" loading=\"lazy\" decoding=\"async\">
        </a>
        <p class=\"site-footer__lead\">IMPORTATEUR ET DISTRIBUTEUR DE PIECES AUTOMOBILES CARROSSERIES ET ACCESSOIRES</p>
        <p class=\"site-footer__address\"><i class=\"fa-solid fa-location-dot\" aria-hidden=\"true\"></i><span>HAY ENNASR 2 Nr 6 lahraouyine Casablanca</span></p>
        <div class=\"site-footer__contacts\">
          <p><i class=\"fa-solid fa-phone\" aria-hidden=\"true\"></i><span>Service client : <button class=\"phone-contact-trigger\" type=\"button\" data-bs-toggle=\"modal\" data-bs-target=\"#phoneContactModal\">Voir les numéros</button></span></p>
          <p><i class=\"fa-solid fa-envelope\" aria-hidden=\"true\"></i><span>Email : <a href=\"mailto:3sk.partsauto@gmail.com\">3sk.partsauto@gmail.com</a></span></p>
        </div>
        <div class=\"site-footer__social\" aria-label=\"Réseaux sociaux\">
          <a href=\"#\" aria-label=\"Facebook\"><i class=\"fa-brands fa-facebook-f\" aria-hidden=\"true\"></i></a>
          <a href=\"#\" aria-label=\"Instagram\"><i class=\"fa-brands fa-instagram\" aria-hidden=\"true\"></i></a>
          <a href=\"https://wa.me/212613577138\" target=\"_blank\" rel=\"noopener noreferrer\" aria-label=\"WhatsApp\"><i class=\"fa-brands fa-whatsapp\" aria-hidden=\"true\"></i></a>
        </div>
      </div>

      <div class=\"site-footer__support\">
        <section class=\"site-footer__panel\" aria-labelledby=\"footer-payment-title\">
          <div class=\"site-footer__heading-row\">
            <i class=\"fa-solid fa-shield-halved\" aria-hidden=\"true\"></i>
            <h2 class=\"site-footer__heading\" id=\"footer-payment-title\">Paiement sécurisé</h2>
          </div>
          <div class=\"site-footer__payments\">
            <span class=\"site-footer__payment\"><img src=\"{{ asset('img/payment/cmi-logo.png') }}\" alt=\"CMI\" width=\"60\" height=\"40\" loading=\"lazy\" decoding=\"async\"></span>
            <span class=\"site-footer__payment site-footer__payment--mastercard\" role=\"img\" aria-label=\"Mastercard\"><span></span><span></span></span>
            <span class=\"site-footer__payment\"><img src=\"{{ asset('img/payment/visa-logo.png') }}\" alt=\"VISA\" width=\"60\" height=\"19\" loading=\"lazy\" decoding=\"async\"></span>
          </div>
        </section>
        <section class=\"site-footer__panel\" aria-labelledby=\"footer-help-title\">
          <div class=\"site-footer__heading-row\">
            <i class=\"fa-solid fa-headset\" aria-hidden=\"true\"></i>
            <h2 class=\"site-footer__heading\" id=\"footer-help-title\">Besoin d'aide ?</h2>
          </div>
          <ul class=\"site-footer__list\">
            <li><a href=\"{{ footer_anchor_prefix }}#qui-sommes-nous\"><i class=\"fa-solid fa-circle-info\" aria-hidden=\"true\"></i><span>À propos</span><i class=\"fa-solid fa-chevron-right\" aria-hidden=\"true\"></i></a></li>
            <li><a href=\"{{ footer_anchor_prefix }}#services\"><i class=\"fa-solid fa-gear\" aria-hidden=\"true\"></i><span>Services</span><i class=\"fa-solid fa-chevron-right\" aria-hidden=\"true\"></i></a></li>
            <li><a href=\"{{ footer_anchor_prefix }}#contact\"><i class=\"fa-solid fa-headset\" aria-hidden=\"true\"></i><span>Contact</span><i class=\"fa-solid fa-chevron-right\" aria-hidden=\"true\"></i></a></li>
          </ul>
        </section>
      </div>

      <nav class=\"site-footer__navigation\" aria-labelledby=\"footer-nav-title\">
        <div class=\"site-footer__heading-row\">
          <i class=\"fa-solid fa-compass\" aria-hidden=\"true\"></i>
          <h2 class=\"site-footer__heading\" id=\"footer-nav-title\">Navigation</h2>
        </div>
        <ul class=\"site-footer__list\">
          <li><a href=\"{{ footer_anchor_prefix }}#accueil\"><i class=\"fa-solid fa-house\" aria-hidden=\"true\"></i><span>Accueil</span><i class=\"fa-solid fa-chevron-right\" aria-hidden=\"true\"></i></a></li>
          <li><a href=\"{{ footer_anchor_prefix }}#marques\"><i class=\"fa-solid fa-car-side\" aria-hidden=\"true\"></i><span>Marques</span><i class=\"fa-solid fa-chevron-right\" aria-hidden=\"true\"></i></a></li>
          <li><a href=\"{{ footer_anchor_prefix }}#contact\"><i class=\"fa-solid fa-paper-plane\" aria-hidden=\"true\"></i><span>Demande de contact</span><i class=\"fa-solid fa-chevron-right\" aria-hidden=\"true\"></i></a></li>
          <li><a href=\"{{ app.user ? path('account_profile') : path('app_login') }}\"><i class=\"fa-solid fa-user\" aria-hidden=\"true\"></i><span>Espace client</span><i class=\"fa-solid fa-chevron-right\" aria-hidden=\"true\"></i></a></li>
        </ul>
      </nav>

      <section class=\"site-footer__panel site-footer__location\" aria-labelledby=\"footer-location-title\">
        <div class=\"site-footer__heading-row\">
          <i class=\"fa-solid fa-location-dot\" aria-hidden=\"true\"></i>
          <h2 class=\"site-footer__heading\" id=\"footer-location-title\">Notre localisation</h2>
        </div>
        <div class=\"site-footer__map\">
          <iframe title=\"Localisation 3SK Parts Auto Casablanca\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\" src=\"https://www.google.com/maps?q=33.528667,-7.550028&z=12&output=embed\"></iframe>
        </div>
        <p class=\"site-footer__map-caption\"><i class=\"fa-solid fa-location-dot\" aria-hidden=\"true\"></i><span>HAY ENNASR 2 Nr 6 lahraouyine Casablanca</span></p>
      </section>
    </div>
  </div>
  <div class=\"site-footer__bottom\">
    <div class=\"site-footer__bottom-inner\">
      <span>3SK Parts Auto - Tous droits réservés {{ \"now\"|date(\"Y\") }}</span>
      <span>Importateur et distributeur de pièces automobiles, carrosseries et accessoires</span>
    </div>
  </div>
</footer>
", "partials/_site_footer.html.twig", "F:\\3sk\\3sk (1)\\templates\\partials\\_site_footer.html.twig");
    }
}
