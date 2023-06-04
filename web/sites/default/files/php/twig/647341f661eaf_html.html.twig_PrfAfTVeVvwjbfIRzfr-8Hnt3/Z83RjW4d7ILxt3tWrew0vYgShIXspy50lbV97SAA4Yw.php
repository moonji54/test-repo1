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

/* themes/custom/nrgi/templates/html/html.html.twig */
class __TwigTemplate_6b67eeb8d47efd22eff5308974e9d380 extends \Twig\Template
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
        // line 26
        $context["body_classes"] = [0 => ((        // line 27
($context["logged_in"] ?? null)) ? ("user-logged-in") : ("")), 1 => (( !        // line 28
($context["root_path"] ?? null)) ? ("path-frontpage") : (("path-" . \Drupal\Component\Utility\Html::getClass($this->sandbox->ensureToStringAllowed(($context["root_path"] ?? null), 28, $this->source))))), 2 => ((        // line 29
($context["node_type"] ?? null)) ? (("page-node-type-" . \Drupal\Component\Utility\Html::getClass($this->sandbox->ensureToStringAllowed(($context["node_type"] ?? null), 29, $this->source)))) : ("")), 3 => ((        // line 30
($context["db_offline"] ?? null)) ? ("db-offline") : (""))];
        // line 32
        echo "<!DOCTYPE html>
<html";
        // line 33
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, ($context["html_attributes"] ?? null), "addClass", [0 => "no-js"], "method", false, false, true, 33), 33, $this->source), "html", null, true);
        echo ">
<head>
  <head-placeholder token=\"";
        // line 35
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["placeholder_token"] ?? null), 35, $this->source), "html", null, true);
        echo "\">
    <title>";
        // line 36
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->safeJoin($this->env, $this->sandbox->ensureToStringAllowed(($context["head_title"] ?? null), 36, $this->source), " | "));
        echo "</title>

    <link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">
    <link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>
    <link href=\"https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,400;0,500;0,600;1,400&display=swap\"
          rel=\"stylesheet\">


    ";
        // line 45
        echo "    <link rel=\"apple-touch-icon\" sizes=\"57x57\" href=\"/";
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->sandbox->ensureToStringAllowed(($context["base_path"] ?? null), 45, $this->source) . $this->sandbox->ensureToStringAllowed(($context["directory"] ?? null), 45, $this->source)), "html", null, true);
        echo "/assets/favicon/apple-icon-57x57.png\">
    <link rel=\"apple-touch-icon\" sizes=\"60x60\" href=\"/";
        // line 46
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->sandbox->ensureToStringAllowed(($context["base_path"] ?? null), 46, $this->source) . $this->sandbox->ensureToStringAllowed(($context["directory"] ?? null), 46, $this->source)), "html", null, true);
        echo "/assets/favicon/apple-icon-60x60.png\">
    <link rel=\"apple-touch-icon\" sizes=\"72x72\" href=\"/";
        // line 47
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->sandbox->ensureToStringAllowed(($context["base_path"] ?? null), 47, $this->source) . $this->sandbox->ensureToStringAllowed(($context["directory"] ?? null), 47, $this->source)), "html", null, true);
        echo "/assets/favicon/apple-icon-72x72.png\">
    <link rel=\"apple-touch-icon\" sizes=\"76x76\" href=\"/";
        // line 48
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->sandbox->ensureToStringAllowed(($context["base_path"] ?? null), 48, $this->source) . $this->sandbox->ensureToStringAllowed(($context["directory"] ?? null), 48, $this->source)), "html", null, true);
        echo "/assets/favicon/apple-icon-76x76.png\">
    <link rel=\"apple-touch-icon\" sizes=\"114x114\"
          href=\"/";
        // line 50
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->sandbox->ensureToStringAllowed(($context["base_path"] ?? null), 50, $this->source) . $this->sandbox->ensureToStringAllowed(($context["directory"] ?? null), 50, $this->source)), "html", null, true);
        echo "/assets/favicon/apple-icon-114x114.png\">
    <link rel=\"apple-touch-icon\" sizes=\"120x120\"
          href=\"/";
        // line 52
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->sandbox->ensureToStringAllowed(($context["base_path"] ?? null), 52, $this->source) . $this->sandbox->ensureToStringAllowed(($context["directory"] ?? null), 52, $this->source)), "html", null, true);
        echo "/assets/favicon/apple-icon-120x120.png\">
    <link rel=\"apple-touch-icon\" sizes=\"144x144\"
          href=\"/";
        // line 54
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->sandbox->ensureToStringAllowed(($context["base_path"] ?? null), 54, $this->source) . $this->sandbox->ensureToStringAllowed(($context["directory"] ?? null), 54, $this->source)), "html", null, true);
        echo "/assets/favicon/apple-icon-144x144.png\">
    <link rel=\"apple-touch-icon\" sizes=\"152x152\"
          href=\"/";
        // line 56
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->sandbox->ensureToStringAllowed(($context["base_path"] ?? null), 56, $this->source) . $this->sandbox->ensureToStringAllowed(($context["directory"] ?? null), 56, $this->source)), "html", null, true);
        echo "/assets/favicon/apple-icon-152x152.png\">
    <link rel=\"apple-touch-icon\" sizes=\"180x180\"
          href=\"/";
        // line 58
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->sandbox->ensureToStringAllowed(($context["base_path"] ?? null), 58, $this->source) . $this->sandbox->ensureToStringAllowed(($context["directory"] ?? null), 58, $this->source)), "html", null, true);
        echo "/assets/favicon/apple-icon-180x180.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"192x192\"
          href=\"/";
        // line 60
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->sandbox->ensureToStringAllowed(($context["base_path"] ?? null), 60, $this->source) . $this->sandbox->ensureToStringAllowed(($context["directory"] ?? null), 60, $this->source)), "html", null, true);
        echo "/assets/favicon/android-icon-192x192.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"32x32\"
          href=\"/";
        // line 62
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->sandbox->ensureToStringAllowed(($context["base_path"] ?? null), 62, $this->source) . $this->sandbox->ensureToStringAllowed(($context["directory"] ?? null), 62, $this->source)), "html", null, true);
        echo "/assets/favicon/favicon-32x32.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"96x96\"
          href=\"/";
        // line 64
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->sandbox->ensureToStringAllowed(($context["base_path"] ?? null), 64, $this->source) . $this->sandbox->ensureToStringAllowed(($context["directory"] ?? null), 64, $this->source)), "html", null, true);
        echo "/assets/favicon/favicon-96x96.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"16x16\"
          href=\"/";
        // line 66
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->sandbox->ensureToStringAllowed(($context["base_path"] ?? null), 66, $this->source) . $this->sandbox->ensureToStringAllowed(($context["directory"] ?? null), 66, $this->source)), "html", null, true);
        echo "/assets/favicon/favicon-16x16.png\">
    <link rel=\"manifest\" href=\"/";
        // line 67
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->sandbox->ensureToStringAllowed(($context["base_path"] ?? null), 67, $this->source) . $this->sandbox->ensureToStringAllowed(($context["directory"] ?? null), 67, $this->source)), "html", null, true);
        echo "/assets/favicon/manifest.json\">
    <meta name=\"msapplication-TileColor\" content=\"#ffffff\">
    <meta name=\"msapplication-TileImage\" content=\"/ms-icon-144x144.png\">
    <meta name=\"theme-color\" content=\"#1e1e46\">
    <script src=\"/";
        // line 71
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->sandbox->ensureToStringAllowed(($context["base_path"] ?? null), 71, $this->source) . $this->sandbox->ensureToStringAllowed(($context["directory"] ?? null), 71, $this->source)), "html", null, true);
        echo "/assets/js/modernizr.js\"></script>
    <css-placeholder token=\"";
        // line 72
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["placeholder_token"] ?? null), 72, $this->source), "html", null, true);
        echo "\">
      <js-placeholder token=\"";
        // line 73
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["placeholder_token"] ?? null), 73, $this->source), "html", null, true);
        echo "\">

        ";
        // line 76
        echo "        <script>
          window.markerConfig = {
            project: '6452305524a1465d862508da',
            source: 'snippet'
          };
        </script>

        <script>
          !function (e, r, a) {
            if (!e.__Marker) {
              e.__Marker = {};
              var t = [], n = { __cs: t };
              ['show', 'hide', 'isVisible', 'capture', 'cancelCapture', 'unload', 'reload', 'isExtensionInstalled', 'setReporter', 'setCustomData', 'on', 'off'].forEach(function (e) {
                n[e] = function () {
                  var r = Array.prototype.slice.call(arguments);
                  r.unshift(e), t.push(r);
                };
              }), e.Marker = n;
              var s = r.createElement('script');
              s.async = 1, s.src = 'https://edge.marker.io/latest/shim.js';
              var i = r.getElementsByTagName('script')[0];
              i.parentNode.insertBefore(s, i);
            }
          }(window, document);
        </script>


        </head>
<body";
        // line 104
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [0 => ($context["body_classes"] ?? null)], "method", false, false, true, 104), 104, $this->source), "html", null, true);
        echo ">
";
        // line 109
        echo "<a href=\"#main-content\" class=\"visually-hidden focusable skip-link\">
  ";
        // line 110
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Skip to main content"));
        echo "
</a>

";
        // line 113
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["page_top"] ?? null), 113, $this->source), "html", null, true);
        echo "
";
        // line 114
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["page"] ?? null), 114, $this->source), "html", null, true);
        echo "
";
        // line 115
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["page_bottom"] ?? null), 115, $this->source), "html", null, true);
        echo "

<js-bottom-placeholder token=\"";
        // line 117
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["placeholder_token"] ?? null), 117, $this->source), "html", null, true);
        echo "\">
</body>
</html>
";
    }

    public function getTemplateName()
    {
        return "themes/custom/nrgi/templates/html/html.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  206 => 117,  201 => 115,  197 => 114,  193 => 113,  187 => 110,  184 => 109,  180 => 104,  150 => 76,  145 => 73,  141 => 72,  137 => 71,  130 => 67,  126 => 66,  121 => 64,  116 => 62,  111 => 60,  106 => 58,  101 => 56,  96 => 54,  91 => 52,  86 => 50,  81 => 48,  77 => 47,  73 => 46,  68 => 45,  57 => 36,  53 => 35,  48 => 33,  45 => 32,  43 => 30,  42 => 29,  41 => 28,  40 => 27,  39 => 26,);
    }

    public function getSourceContext()
    {
        return new Source("{#
/**
 * @file
 * Theme override for the basic structure of a single Drupal page.
 *
 * Variables:
 * - logged_in: A flag indicating if user is logged in.
 * - root_path: The root path of the current page (e.g., node, admin, user).
 * - node_type: The content type for the current node, if the page is a node.
 * - head_title: List of text elements that make up the head_title variable.
 *   May contain one or more of the following:
 *   - title: The title of the page.
 *   - name: The name of the site.
 *   - slogan: The slogan of the site.
 * - page_top: Initial rendered markup. This should be printed before 'page'.
 * - page: The rendered page markup.
 * - page_bottom: Closing rendered markup. This variable should be printed after
 *   'page'.
 * - db_offline: A flag indicating if the database is offline.
 * - placeholder_token: The token for generating head, css, js and js-bottom
 *   placeholders.
 *
 * @see template_preprocess_html()
 */
#}
{% set body_classes = [
  logged_in ? 'user-logged-in',
  not root_path ? 'path-frontpage' : 'path-' ~ root_path|clean_class,
  node_type ? 'page-node-type-' ~ node_type|clean_class,
  db_offline ? 'db-offline',
] %}
<!DOCTYPE html>
<html{{ html_attributes.addClass('no-js') }}>
<head>
  <head-placeholder token=\"{{ placeholder_token }}\">
    <title>{{ head_title|safe_join(' | ') }}</title>

    <link rel=\"preconnect\" href=\"https://fonts.googleapis.com\">
    <link rel=\"preconnect\" href=\"https://fonts.gstatic.com\" crossorigin>
    <link href=\"https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,400;0,500;0,600;1,400&display=swap\"
          rel=\"stylesheet\">


    {# SBTODO generate favicons, eg https://www.favicon-generator.org/ #}
    <link rel=\"apple-touch-icon\" sizes=\"57x57\" href=\"/{{ base_path ~ directory }}/assets/favicon/apple-icon-57x57.png\">
    <link rel=\"apple-touch-icon\" sizes=\"60x60\" href=\"/{{ base_path ~ directory }}/assets/favicon/apple-icon-60x60.png\">
    <link rel=\"apple-touch-icon\" sizes=\"72x72\" href=\"/{{ base_path ~ directory }}/assets/favicon/apple-icon-72x72.png\">
    <link rel=\"apple-touch-icon\" sizes=\"76x76\" href=\"/{{ base_path ~ directory }}/assets/favicon/apple-icon-76x76.png\">
    <link rel=\"apple-touch-icon\" sizes=\"114x114\"
          href=\"/{{ base_path ~ directory }}/assets/favicon/apple-icon-114x114.png\">
    <link rel=\"apple-touch-icon\" sizes=\"120x120\"
          href=\"/{{ base_path ~ directory }}/assets/favicon/apple-icon-120x120.png\">
    <link rel=\"apple-touch-icon\" sizes=\"144x144\"
          href=\"/{{ base_path ~ directory }}/assets/favicon/apple-icon-144x144.png\">
    <link rel=\"apple-touch-icon\" sizes=\"152x152\"
          href=\"/{{ base_path ~ directory }}/assets/favicon/apple-icon-152x152.png\">
    <link rel=\"apple-touch-icon\" sizes=\"180x180\"
          href=\"/{{ base_path ~ directory }}/assets/favicon/apple-icon-180x180.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"192x192\"
          href=\"/{{ base_path ~ directory }}/assets/favicon/android-icon-192x192.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"32x32\"
          href=\"/{{ base_path ~ directory }}/assets/favicon/favicon-32x32.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"96x96\"
          href=\"/{{ base_path ~ directory }}/assets/favicon/favicon-96x96.png\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"16x16\"
          href=\"/{{ base_path ~ directory }}/assets/favicon/favicon-16x16.png\">
    <link rel=\"manifest\" href=\"/{{ base_path ~ directory }}/assets/favicon/manifest.json\">
    <meta name=\"msapplication-TileColor\" content=\"#ffffff\">
    <meta name=\"msapplication-TileImage\" content=\"/ms-icon-144x144.png\">
    <meta name=\"theme-color\" content=\"#1e1e46\">
    <script src=\"/{{ base_path ~ directory }}/assets/js/modernizr.js\"></script>
    <css-placeholder token=\"{{ placeholder_token }}\">
      <js-placeholder token=\"{{ placeholder_token }}\">

        {# Marker.io #}
        <script>
          window.markerConfig = {
            project: '6452305524a1465d862508da',
            source: 'snippet'
          };
        </script>

        <script>
          !function (e, r, a) {
            if (!e.__Marker) {
              e.__Marker = {};
              var t = [], n = { __cs: t };
              ['show', 'hide', 'isVisible', 'capture', 'cancelCapture', 'unload', 'reload', 'isExtensionInstalled', 'setReporter', 'setCustomData', 'on', 'off'].forEach(function (e) {
                n[e] = function () {
                  var r = Array.prototype.slice.call(arguments);
                  r.unshift(e), t.push(r);
                };
              }), e.Marker = n;
              var s = r.createElement('script');
              s.async = 1, s.src = 'https://edge.marker.io/latest/shim.js';
              var i = r.getElementsByTagName('script')[0];
              i.parentNode.insertBefore(s, i);
            }
          }(window, document);
        </script>


        </head>
<body{{ attributes.addClass(body_classes) }}>
{#
Keyboard navigation/accessibility link to main content section in
page.html.twig.
#}
<a href=\"#main-content\" class=\"visually-hidden focusable skip-link\">
  {{ 'Skip to main content'|t }}
</a>

{{ page_top }}
{{ page }}
{{ page_bottom }}

<js-bottom-placeholder token=\"{{ placeholder_token }}\">
</body>
</html>
", "themes/custom/nrgi/templates/html/html.html.twig", "/var/www/html/web/themes/custom/nrgi/templates/html/html.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = array("set" => 26);
        static $filters = array("clean_class" => 28, "escape" => 33, "safe_join" => 36, "t" => 110);
        static $functions = array();

        try {
            $this->sandbox->checkSecurity(
                ['set'],
                ['clean_class', 'escape', 'safe_join', 't'],
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
