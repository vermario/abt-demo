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

/* core/themes/olivero/templates/content/search-result.html.twig */
class __TwigTemplate_d788c5b2c39a6c934cbd7e1401fd8ced extends Template
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
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 58
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->attachLibrary("olivero/search-results"), "html", null, true);
        yield "
";
        // line 59
        if (($context["info_split"] ?? null)) {
            // line 60
            yield "  <div class=\"search-result__meta\">
    ";
            // line 61
            if (CoreExtension::getAttribute($this->env, $this->source, ($context["info_split"] ?? null), "user", [], "any", false, false, true, 61)) {
                // line 62
                yield "      <span>
        ";
                // line 63
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("By"));
                yield " <span class=\"search-result__author\">";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["info_split"] ?? null), "user", [], "any", false, false, true, 63), "html", null, true);
                yield "</span>
      </span>
    ";
            }
            // line 66
            yield "    ";
            if (($context["info_date"] ?? null)) {
                // line 67
                yield "      <span  class=\"search-result__date\">, ";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["info_date"] ?? null), "html", null, true);
                yield "</span>
    ";
            }
            // line 69
            yield "  </div>
";
        }
        // line 71
        if (($context["title"] ?? null)) {
            // line 72
            yield "  ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_prefix"] ?? null), "html", null, true);
            yield "
  <h3";
            // line 73
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["title_attributes"] ?? null), "addClass", ["search-result__title"], "method", false, false, true, 73), "html", null, true);
            yield ">
    <a href=\"";
            // line 74
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["url"] ?? null), "html", null, true);
            yield "\">";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title"] ?? null), "html", null, true);
            yield "</a>
  </h3>
  ";
            // line 76
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_suffix"] ?? null), "html", null, true);
            yield "
";
        }
        // line 78
        if (($context["snippet"] ?? null)) {
            // line 79
            yield "  <div";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["content_attributes"] ?? null), "addClass", ["search-result__snippet", ($context["layout"] ?? null), "text-content"], "method", false, false, true, 79), "html", null, true);
            yield ">";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["snippet"] ?? null), "html", null, true);
            yield "</div>
";
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["info_split", "info_date", "title", "title_prefix", "title_attributes", "url", "title_suffix", "snippet", "content_attributes", "layout"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "core/themes/olivero/templates/content/search-result.html.twig";
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
        return array (  104 => 79,  102 => 78,  97 => 76,  90 => 74,  86 => 73,  81 => 72,  79 => 71,  75 => 69,  69 => 67,  66 => 66,  58 => 63,  55 => 62,  53 => 61,  50 => 60,  48 => 59,  44 => 58,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "core/themes/olivero/templates/content/search-result.html.twig", "/var/www/html/web/core/themes/olivero/templates/content/search-result.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 59];
        static $filters = ["escape" => 58, "t" => 63];
        static $functions = ["attach_library" => 58];

        try {
            $this->sandbox->checkSecurity(
                ['if'],
                ['escape', 't'],
                ['attach_library'],
                $this->source
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
