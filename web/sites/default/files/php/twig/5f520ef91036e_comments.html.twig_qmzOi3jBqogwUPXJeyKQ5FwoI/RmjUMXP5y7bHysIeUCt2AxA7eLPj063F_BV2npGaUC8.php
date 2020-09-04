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

/* @lendos/comments.html.twig */
class __TwigTemplate_177a223e16959c9faeb6225d304ed98f1fb02328210de0916da4014ed9e9059c extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $tags = ["for" => 2, "if" => 11];
        $filters = ["escape" => 8, "raw" => 30];
        $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['for', 'if'],
                ['escape', 'raw'],
                []
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
        echo "
  ";
        // line 2
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["data"] ?? null), "lendos", []));
        foreach ($context['_seq'] as $context["_key"] => $context["w"]) {
            // line 3
            echo "
    <div class=\"new-out\">

      <div class=\"border\">
        <div class=\"avatar\">
          <img src=\"";
            // line 8
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["base_url"] ?? null)), "html", null, true);
            echo "/sites/default/files/lendos_avatar/";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute($context["w"], "avatar", [])), "html", null, true);
            echo "\" alt=\"avatar\">
          <div class=\"titles\">
            ";
            // line 10
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute($context["w"], "name", [])), "html", null, true);
            echo "
            ";
            // line 11
            if (($context["is_admin"] ?? null)) {
                // line 12
                echo "              <div class=\"edit\">
                <a href=\"";
                // line 13
                echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["base_url"] ?? null)), "html", null, true);
                echo "/admin/lendos/edit/";
                echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute($context["w"], "id", [])), "html", null, true);
                echo "\" class=\"edit\">Edit</a>
                <a href=\"";
                // line 14
                echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["base_url"] ?? null)), "html", null, true);
                echo "/admin/lendos/remove/";
                echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute($context["w"], "id", [])), "html", null, true);
                echo "\" class=\"remove\">Remove</a>
              </div>
            ";
            }
            // line 17
            echo "          </div>
        </div>
        <div class=\"date\">";
            // line 19
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute($context["w"], "date", [])), "html", null, true);
            echo "</div>

        <hr>
        ";
            // line 22
            if (($this->getAttribute($context["w"], "img", []) == "none")) {
                // line 23
                echo "
        ";
            } else {
                // line 25
                echo "          <div class=\"img\">
            <img src=\"";
                // line 26
                echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["base_url"] ?? null)), "html", null, true);
                echo "/sites/default/files/lendos_file/";
                echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute($context["w"], "img", [])), "html", null, true);
                echo "\" class=\"media-com\"
                 alt=\"Comment media file\">
          </div>
        ";
            }
            // line 30
            echo "        <div class=\"content\"><p class=\"ppp\"> ";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->renderVar($this->sandbox->ensureToStringAllowed($this->getAttribute($context["w"], "text", [])));
            echo "</p></div>

        <div class=\"tell\">
          <p><b>Telephone:</b> ";
            // line 33
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute($context["w"], "tell", [])), "html", null, true);
            echo "</p>
        </div>
        <div class=\"mail\">
          <p><b>E-mail:</b> ";
            // line 36
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute($context["w"], "mail", [])), "html", null, true);
            echo "</p>
        </div>

      </div>
    </div>
  ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['w'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 42
        echo "
";
    }

    public function getTemplateName()
    {
        return "@lendos/comments.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  152 => 42,  140 => 36,  134 => 33,  127 => 30,  118 => 26,  115 => 25,  111 => 23,  109 => 22,  103 => 19,  99 => 17,  91 => 14,  85 => 13,  82 => 12,  80 => 11,  76 => 10,  69 => 8,  62 => 3,  58 => 2,  55 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "@lendos/comments.html.twig", "/var/www/html/drupal8/web/modules/custom/lendos/templates/comments.html.twig");
    }
}
