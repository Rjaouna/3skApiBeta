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

/* partials/_phone_contact_modal.html.twig */
class __TwigTemplate_30129a1ab1d8fbdfb68f7f1b77b021ba extends Template
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
        $__internal_5a27a8ba21ca79b61932376b2fa922d2->enter($__internal_5a27a8ba21ca79b61932376b2fa922d2_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "partials/_phone_contact_modal.html.twig"));

        $__internal_6f47bbe9983af81f1e7450e9a3e3768f = $this->extensions["Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension"];
        $__internal_6f47bbe9983af81f1e7450e9a3e3768f->enter($__internal_6f47bbe9983af81f1e7450e9a3e3768f_prof = new \Twig\Profiler\Profile($this->getTemplateName(), "template", "partials/_phone_contact_modal.html.twig"));

        // line 1
        yield "<style>
  .phone-contact-trigger {
    border: 0;
    font: inherit;
    cursor: pointer;
  }

  .public-shell .public-topbar .phone-contact-trigger {
    color: var(--public-blue);
    text-decoration: none;
    font-weight: 500;
    line-height: 1;
    white-space: nowrap;
    background: transparent;
    padding: 0;
    text-transform: uppercase;
  }

  .public-shell .public-topbar .phone-contact-trigger:hover {
    color: var(--public-orange);
  }

  .public-shell .public-topbar-contact .phone-contact-trigger + a::before {
    content: \"\";
    display: inline-block;
    width: 1px;
    height: 22px;
    margin: 0 22px 0 18px;
    background: rgba(var(--brand-blue-rgb), .2);
    vertical-align: middle;
  }

  .site-footer .phone-contact-trigger {
    display: inline;
    color: var(--public-muted, var(--brand-muted));
    background: transparent;
    padding: 0;
    text-align: left;
  }

  .site-footer .phone-contact-trigger:hover {
    color: var(--public-blue, var(--brand-blue));
  }

  .phone-contact-modal .modal-content {
    border: 0;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 28px 70px rgba(var(--brand-anthracite-rgb), .24);
  }

  .phone-contact-modal__head {
    padding: 1.1rem 1.25rem;
    background: var(--brand-blue);
    color: #fff;
  }

  .phone-contact-modal__icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: inline-grid;
    place-items: center;
    background: rgba(255,255,255,.14);
    color: #fff;
  }

  .phone-contact-list {
    display: grid;
    gap: .75rem;
  }

  .phone-contact-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .9rem;
    padding: .85rem;
    border: 1px solid rgba(var(--brand-blue-rgb), .12);
    border-radius: 16px;
    background: rgba(var(--brand-blue-rgb), .035);
  }

  .phone-contact-item__label {
    display: block;
    color: var(--public-muted, var(--brand-muted));
    font-size: .76rem;
    font-weight: 500;
  }

  .phone-contact-item__number {
    display: block;
    color: var(--public-ink, var(--brand-ink));
    font-size: .98rem;
    font-weight: 600;
    letter-spacing: .01em;
  }

  .phone-contact-item__call {
    flex: 0 0 auto;
    min-width: 44px;
    height: 44px;
    border-radius: 14px;
    display: inline-grid;
    place-items: center;
    color: #fff !important;
    background: var(--public-blue, var(--brand-blue));
    text-decoration: none;
    box-shadow: 0 12px 22px rgba(var(--brand-blue-rgb), .18);
  }

  .phone-contact-item__call:hover {
    background: var(--brand-blue-dark);
    color: #fff !important;
  }

  @media (max-width: 575.98px) {
    .public-shell .public-topbar-contact .phone-contact-trigger + a::before {
      display: none;
    }

    .phone-contact-item {
      padding: .75rem;
    }
  }
</style>

<div class=\"modal fade phone-contact-modal\" id=\"phoneContactModal\" tabindex=\"-1\" aria-labelledby=\"phoneContactModalLabel\" aria-hidden=\"true\">
  <div class=\"modal-dialog modal-dialog-centered\">
    <div class=\"modal-content\">
      <div class=\"phone-contact-modal__head d-flex align-items-center gap-3\">
        <span class=\"phone-contact-modal__icon\">
          <i class=\"fa-solid fa-headset\"></i>
        </span>
        <div>
          <h5 class=\"modal-title mb-1 text-white\" id=\"phoneContactModalLabel\">Contacter le service client</h5>
          <p class=\"mb-0 small opacity-75\">Choisissez le numero que vous souhaitez appeler.</p>
        </div>
        <button type=\"button\" class=\"btn-close btn-close-white ms-auto\" data-bs-dismiss=\"modal\" aria-label=\"Fermer\"></button>
      </div>

      <div class=\"modal-body p-3 p-sm-4\">
        <div class=\"phone-contact-list\">
          <div class=\"phone-contact-item\">
            <span>
              <span class=\"phone-contact-item__label\">Ligne principale</span>
              <span class=\"phone-contact-item__number\">+212 5 22 71 35 72</span>
            </span>
            <a class=\"phone-contact-item__call\" href=\"tel:+212522713572\" aria-label=\"Appeler +212 5 22 71 35 72\">
              <i class=\"fa-solid fa-phone\"></i>
            </a>
          </div>

          <div class=\"phone-contact-item\">
            <span>
              <span class=\"phone-contact-item__label\">Ligne principale</span>
              <span class=\"phone-contact-item__number\">+212 5 22 71 35 77</span>
            </span>
            <a class=\"phone-contact-item__call\" href=\"tel:+212522713577\" aria-label=\"Appeler +212 5 22 71 35 77\">
              <i class=\"fa-solid fa-phone\"></i>
            </a>
          </div>

          <div class=\"phone-contact-item\">
            <span>
              <span class=\"phone-contact-item__label\">Ligne principale</span>
              <span class=\"phone-contact-item__number\">+212 5 20 84 25 26</span>
            </span>
            <a class=\"phone-contact-item__call\" href=\"tel:+212520842526\" aria-label=\"Appeler +212 5 20 84 25 26\">
              <i class=\"fa-solid fa-phone\"></i>
            </a>
          </div>

          <div class=\"phone-contact-item\">
            <span>
              <span class=\"phone-contact-item__label\">Ligne principale</span>
              <span class=\"phone-contact-item__number\">+212 5 20 84 25 27</span>
            </span>
            <a class=\"phone-contact-item__call\" href=\"tel:+212520842527\" aria-label=\"Appeler +212 5 20 84 25 27\">
              <i class=\"fa-solid fa-phone\"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
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
        return "partials/_phone_contact_modal.html.twig";
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
        return new Source("<style>
  .phone-contact-trigger {
    border: 0;
    font: inherit;
    cursor: pointer;
  }

  .public-shell .public-topbar .phone-contact-trigger {
    color: var(--public-blue);
    text-decoration: none;
    font-weight: 500;
    line-height: 1;
    white-space: nowrap;
    background: transparent;
    padding: 0;
    text-transform: uppercase;
  }

  .public-shell .public-topbar .phone-contact-trigger:hover {
    color: var(--public-orange);
  }

  .public-shell .public-topbar-contact .phone-contact-trigger + a::before {
    content: \"\";
    display: inline-block;
    width: 1px;
    height: 22px;
    margin: 0 22px 0 18px;
    background: rgba(var(--brand-blue-rgb), .2);
    vertical-align: middle;
  }

  .site-footer .phone-contact-trigger {
    display: inline;
    color: var(--public-muted, var(--brand-muted));
    background: transparent;
    padding: 0;
    text-align: left;
  }

  .site-footer .phone-contact-trigger:hover {
    color: var(--public-blue, var(--brand-blue));
  }

  .phone-contact-modal .modal-content {
    border: 0;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 28px 70px rgba(var(--brand-anthracite-rgb), .24);
  }

  .phone-contact-modal__head {
    padding: 1.1rem 1.25rem;
    background: var(--brand-blue);
    color: #fff;
  }

  .phone-contact-modal__icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: inline-grid;
    place-items: center;
    background: rgba(255,255,255,.14);
    color: #fff;
  }

  .phone-contact-list {
    display: grid;
    gap: .75rem;
  }

  .phone-contact-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .9rem;
    padding: .85rem;
    border: 1px solid rgba(var(--brand-blue-rgb), .12);
    border-radius: 16px;
    background: rgba(var(--brand-blue-rgb), .035);
  }

  .phone-contact-item__label {
    display: block;
    color: var(--public-muted, var(--brand-muted));
    font-size: .76rem;
    font-weight: 500;
  }

  .phone-contact-item__number {
    display: block;
    color: var(--public-ink, var(--brand-ink));
    font-size: .98rem;
    font-weight: 600;
    letter-spacing: .01em;
  }

  .phone-contact-item__call {
    flex: 0 0 auto;
    min-width: 44px;
    height: 44px;
    border-radius: 14px;
    display: inline-grid;
    place-items: center;
    color: #fff !important;
    background: var(--public-blue, var(--brand-blue));
    text-decoration: none;
    box-shadow: 0 12px 22px rgba(var(--brand-blue-rgb), .18);
  }

  .phone-contact-item__call:hover {
    background: var(--brand-blue-dark);
    color: #fff !important;
  }

  @media (max-width: 575.98px) {
    .public-shell .public-topbar-contact .phone-contact-trigger + a::before {
      display: none;
    }

    .phone-contact-item {
      padding: .75rem;
    }
  }
</style>

<div class=\"modal fade phone-contact-modal\" id=\"phoneContactModal\" tabindex=\"-1\" aria-labelledby=\"phoneContactModalLabel\" aria-hidden=\"true\">
  <div class=\"modal-dialog modal-dialog-centered\">
    <div class=\"modal-content\">
      <div class=\"phone-contact-modal__head d-flex align-items-center gap-3\">
        <span class=\"phone-contact-modal__icon\">
          <i class=\"fa-solid fa-headset\"></i>
        </span>
        <div>
          <h5 class=\"modal-title mb-1 text-white\" id=\"phoneContactModalLabel\">Contacter le service client</h5>
          <p class=\"mb-0 small opacity-75\">Choisissez le numero que vous souhaitez appeler.</p>
        </div>
        <button type=\"button\" class=\"btn-close btn-close-white ms-auto\" data-bs-dismiss=\"modal\" aria-label=\"Fermer\"></button>
      </div>

      <div class=\"modal-body p-3 p-sm-4\">
        <div class=\"phone-contact-list\">
          <div class=\"phone-contact-item\">
            <span>
              <span class=\"phone-contact-item__label\">Ligne principale</span>
              <span class=\"phone-contact-item__number\">+212 5 22 71 35 72</span>
            </span>
            <a class=\"phone-contact-item__call\" href=\"tel:+212522713572\" aria-label=\"Appeler +212 5 22 71 35 72\">
              <i class=\"fa-solid fa-phone\"></i>
            </a>
          </div>

          <div class=\"phone-contact-item\">
            <span>
              <span class=\"phone-contact-item__label\">Ligne principale</span>
              <span class=\"phone-contact-item__number\">+212 5 22 71 35 77</span>
            </span>
            <a class=\"phone-contact-item__call\" href=\"tel:+212522713577\" aria-label=\"Appeler +212 5 22 71 35 77\">
              <i class=\"fa-solid fa-phone\"></i>
            </a>
          </div>

          <div class=\"phone-contact-item\">
            <span>
              <span class=\"phone-contact-item__label\">Ligne principale</span>
              <span class=\"phone-contact-item__number\">+212 5 20 84 25 26</span>
            </span>
            <a class=\"phone-contact-item__call\" href=\"tel:+212520842526\" aria-label=\"Appeler +212 5 20 84 25 26\">
              <i class=\"fa-solid fa-phone\"></i>
            </a>
          </div>

          <div class=\"phone-contact-item\">
            <span>
              <span class=\"phone-contact-item__label\">Ligne principale</span>
              <span class=\"phone-contact-item__number\">+212 5 20 84 25 27</span>
            </span>
            <a class=\"phone-contact-item__call\" href=\"tel:+212520842527\" aria-label=\"Appeler +212 5 20 84 25 27\">
              <i class=\"fa-solid fa-phone\"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
", "partials/_phone_contact_modal.html.twig", "F:\\3sk\\3sk (1)\\templates\\partials\\_phone_contact_modal.html.twig");
    }
}
