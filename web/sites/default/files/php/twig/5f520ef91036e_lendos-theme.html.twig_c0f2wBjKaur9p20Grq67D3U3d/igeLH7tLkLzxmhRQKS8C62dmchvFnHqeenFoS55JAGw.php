<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* modules/custom/lendos/templates/lendos-theme.html.twig */
class __TwigTemplate_351ea0d9555f39ca835a48a7ce89e76897d9f04d945a6d4c6b84325a1de7a0fd extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $tags = ["include" => 2];
        $filters = ["escape" => 1];
        $functions = ["attach_library" => 1];

        try {
            $this->sandbox->checkSecurity(
                ['include'],
                ['escape'],
                ['attach_library']
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->getSourceContext());

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

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 1
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->env->getExtension('Drupal\Core\Template\TwigExtension')->attachLibrary("lendos/scripts_lendos"), "html", null, true);
        echo "
";
        // line 2
        $this->loadTemplate("@lendos/lendos-head.html.twig", "modules/custom/lendos/templates/lendos-theme.html.twig", 2)->display($context);
        // line 3
        echo "<section class=\"out\" >
  <div class=\"main-title\">";
        // line 4
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["data"] ?? null), "title", [])), "html", null, true);
        echo "</div>
  <div class=\"errors\">
    <div id=\"form-system-messages\" class=\"form-system-messages\">
    </div>
  </div>
  <div class=\"adds\">";
        // line 9
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["add_lendos"] ?? null)), "html", null, true);
        echo "</div>



  <div id=\"comment\">";
        // line 13
        $this->loadTemplate("@lendos/comments.html.twig", "modules/custom/lendos/templates/lendos-theme.html.twig", 13)->display($context);
        echo "</div>


</section>



";
        // line 20
        $this->loadTemplate("@lendos/lendos-footer.html.twig", "modules/custom/lendos/templates/lendos-theme.html.twig", 20)->display($context);
    }

    public function getTemplateName()
    {
        return "modules/custom/lendos/templates/lendos-theme.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  89 => 20,  79 => 13,  72 => 9,  64 => 4,  61 => 3,  59 => 2,  55 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "modules/custom/lendos/templates/lendos-theme.html.twig", "/var/www/html/drupal8/web/modules/custom/lendos/templates/lendos-theme.html.twig");
    }
}
