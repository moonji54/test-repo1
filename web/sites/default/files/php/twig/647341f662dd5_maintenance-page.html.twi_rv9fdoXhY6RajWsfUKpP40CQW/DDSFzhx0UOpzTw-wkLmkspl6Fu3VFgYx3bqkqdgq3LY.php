<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* themes/custom/nrgi/templates/maintenance/maintenance-page.html.twig */
class __TwigTemplate_775ee4b63621f012163ec9d499a03868 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<!-- Inline style, database may not be available, page must be standalone -->
<style type=\"text/css\">
  .c-maintenance__wrapper {
    align-items     : center;
    display         : flex;
    height          : 100vh;
    justify-content : center;
  }
  .c-maintenance {
    max-width : 800px;
  }
  @media screen and (min-height : 800px) {
    .c-maintenance {
      margin-bottom : 200px;
    }
  }
  .c-maintenance__top {
    border  : 3px solid #1e1e46;
    display : flex;
  }
  @media screen and (max-width : 540px) {
    .c-maintenance__top {
      display : block;
    }
  }
  .c-maintenance__content {
    flex-grow : 1;
    padding   : 8px;
  }
  @media screen and (max-width : 540px) {
    .c-maintenance__content {
      padding : 16px;
    }
  }
  .c-maintenance__logo-wrapper {
    align-items    : center;
    display        : flex;
    flex-direction : column;
    flex-grow      : 0;
    flex-shrink    : 0;
  }
  @media screen and (max-width : 540px) {
    .c-maintenance__logo-wrapper {
      flex-direction : row;
    }
  }
  .c-maintenance__logo {
    display    : block;
    height     : 60px;
    margin-top : -1px;
    width      : 141px;
  }
  .c-maintenance__logo-fill {
    background-color : #1e1e46;
    flex-grow        : 1;
    margin-top       : -1px;
    width            : 100%;
  }
  @media screen and (max-width : 540px) {
    .c-maintenance__logo-fill {
      height      : 58px;
      margin-left : -1px;
    }
    .c-maintenance__logo-fill--first {
      flex-grow : 0;
      width     : 0;
    }
  }
  .c-maintenance__title-wrapper {
    align-items : center;
    display     : flex;
  }
  .c-maintenance__title {
    color       : #1e1e46;
    font-family : 'Barlow', sans-serif;
    font-weight : 700;
    font-size   : 24px;
    line-height : 36px;
    padding     : 8px 16px;
  }
  @media screen and (max-width : 540px) {
    .c-maintenance__title {
      font-size   : 18px;
      line-height : 24px;
    }
  }
  .c-maintenance__bottom {
    border     : 3px solid #1e1e46;
    display    : flex;
    margin-top : 32px;
    max-width  : 800px;
  }
  .c-maintenance-message {
    color       : #1e1e46;
    font-family : 'Barlow', sans-serif;
    font-weight : 400;
    font-size   : 16px;
    line-height : 20px;
    padding     : 16px;
  }
</style>

";
        // line 103
        $context["title"] = ((twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["site_settings"] ?? null), "maintenance_page", [], "any", false, true, true, 103), "maintenance_title", [], "any", true, true, true, 103)) ? (twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["site_settings"] ?? null), "maintenance_page", [], "any", false, false, true, 103), "maintenance_title", [], "any", false, false, true, 103)) : (t("Under maintenance")));
        // line 104
        $context["text"] = ((twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["site_settings"] ?? null), "maintenance_page", [], "any", false, true, true, 104), "maintenance_text", [], "any", true, true, true, 104)) ? (twig_get_attribute($this->env, $this->source, twig_get_attribute($this->env, $this->source, ($context["site_settings"] ?? null), "maintenance_page", [], "any", false, false, true, 104), "maintenance_text", [], "any", false, false, true, 104)) : (t("Our site is under routine maintenance, please check back again shortly.")));
        // line 105
        echo "
<div class=\"c-maintenance__wrapper\">
  <div class=\"c-maintenance\">

    <div class=\"c-maintenance__top\">
      <div class=\"c-maintenance__logo-wrapper\">
        <div class=\"c-maintenance__logo-fill c-maintenance__logo-fill--first\"></div>
        ";
        // line 113
        echo "        <img
          class=\"c-maintenance__logo\"
          src=\"";
        // line 115
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->sandbox->ensureToStringAllowed(($context["base_path"] ?? null), 115, $this->source) . $this->sandbox->ensureToStringAllowed(($context["directory"] ?? null), 115, $this->source)), "html", null, true);
        echo "/assets/img/logo.png\"
          alt=\"";
        // line 116
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Logo"));
        echo "\"
        />
        <div class=\"c-maintenance__logo-fill c-maintenance__logo-fill--last\"></div>
      </div>
      <div class=\"c-maintenance__title-wrapper\">
        <h1 class=\"c-maintenance__title\">";
        // line 121
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["title"] ?? null), 121, $this->source), "html", null, true);
        echo "</h1>
      </div>
    </div>

    <div class=\"c-maintenance__bottom\">
      <div class=\"c-maintenance-message__wrapper\">
        <p class=\"c-maintenance-message\">";
        // line 127
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["text"] ?? null), 127, $this->source), "html", null, true);
        echo "</p>
      </div>
    </div>

  </div>
</div>

";
        // line 135
        echo "<link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css?family=Barlow:400,700\"/>
";
    }

    public function getTemplateName()
    {
        return "themes/custom/nrgi/templates/maintenance/maintenance-page.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  191 => 135,  181 => 127,  172 => 121,  164 => 116,  160 => 115,  156 => 113,  147 => 105,  145 => 104,  143 => 103,  39 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<!-- Inline style, database may not be available, page must be standalone -->
<style type=\"text/css\">
  .c-maintenance__wrapper {
    align-items     : center;
    display         : flex;
    height          : 100vh;
    justify-content : center;
  }
  .c-maintenance {
    max-width : 800px;
  }
  @media screen and (min-height : 800px) {
    .c-maintenance {
      margin-bottom : 200px;
    }
  }
  .c-maintenance__top {
    border  : 3px solid #1e1e46;
    display : flex;
  }
  @media screen and (max-width : 540px) {
    .c-maintenance__top {
      display : block;
    }
  }
  .c-maintenance__content {
    flex-grow : 1;
    padding   : 8px;
  }
  @media screen and (max-width : 540px) {
    .c-maintenance__content {
      padding : 16px;
    }
  }
  .c-maintenance__logo-wrapper {
    align-items    : center;
    display        : flex;
    flex-direction : column;
    flex-grow      : 0;
    flex-shrink    : 0;
  }
  @media screen and (max-width : 540px) {
    .c-maintenance__logo-wrapper {
      flex-direction : row;
    }
  }
  .c-maintenance__logo {
    display    : block;
    height     : 60px;
    margin-top : -1px;
    width      : 141px;
  }
  .c-maintenance__logo-fill {
    background-color : #1e1e46;
    flex-grow        : 1;
    margin-top       : -1px;
    width            : 100%;
  }
  @media screen and (max-width : 540px) {
    .c-maintenance__logo-fill {
      height      : 58px;
      margin-left : -1px;
    }
    .c-maintenance__logo-fill--first {
      flex-grow : 0;
      width     : 0;
    }
  }
  .c-maintenance__title-wrapper {
    align-items : center;
    display     : flex;
  }
  .c-maintenance__title {
    color       : #1e1e46;
    font-family : 'Barlow', sans-serif;
    font-weight : 700;
    font-size   : 24px;
    line-height : 36px;
    padding     : 8px 16px;
  }
  @media screen and (max-width : 540px) {
    .c-maintenance__title {
      font-size   : 18px;
      line-height : 24px;
    }
  }
  .c-maintenance__bottom {
    border     : 3px solid #1e1e46;
    display    : flex;
    margin-top : 32px;
    max-width  : 800px;
  }
  .c-maintenance-message {
    color       : #1e1e46;
    font-family : 'Barlow', sans-serif;
    font-weight : 400;
    font-size   : 16px;
    line-height : 20px;
    padding     : 16px;
  }
</style>

{% set title = site_settings.maintenance_page.maintenance_title is defined ? site_settings.maintenance_page.maintenance_title : 'Under maintenance'|t %}
{% set text = site_settings.maintenance_page.maintenance_text is defined ? site_settings.maintenance_page.maintenance_text : 'Our site is under routine maintenance, please check back again shortly.'|t %}

<div class=\"c-maintenance__wrapper\">
  <div class=\"c-maintenance\">

    <div class=\"c-maintenance__top\">
      <div class=\"c-maintenance__logo-wrapper\">
        <div class=\"c-maintenance__logo-fill c-maintenance__logo-fill--first\"></div>
        {# SBTODO Set maintenance page logo #}
        <img
          class=\"c-maintenance__logo\"
          src=\"{{ base_path ~ directory }}/assets/img/logo.png\"
          alt=\"{{ 'Logo'|t }}\"
        />
        <div class=\"c-maintenance__logo-fill c-maintenance__logo-fill--last\"></div>
      </div>
      <div class=\"c-maintenance__title-wrapper\">
        <h1 class=\"c-maintenance__title\">{{ title }}</h1>
      </div>
    </div>

    <div class=\"c-maintenance__bottom\">
      <div class=\"c-maintenance-message__wrapper\">
        <p class=\"c-maintenance-message\">{{ text }}</p>
      </div>
    </div>

  </div>
</div>

{# SBTODO Set font? #}
<link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css?family=Barlow:400,700\"/>
", "themes/custom/nrgi/templates/maintenance/maintenance-page.html.twig", "/var/www/html/web/themes/custom/nrgi/templates/maintenance/maintenance-page.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = array("set" => 103);
        static $filters = array("t" => 103, "escape" => 115);
        static $functions = array();

        try {
            $this->sandbox->checkSecurity(
                ['set'],
                ['t', 'escape'],
                []
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
