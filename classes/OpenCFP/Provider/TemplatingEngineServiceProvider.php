<?php namespace OpenCFP\Provider;

use Silex\Application;
use Ciconia\Ciconia;
use Silex\ServiceProviderInterface;
use Silex\Provider\TwigServiceProvider;
use Aptoma\Twig\Extension\MarkdownExtension;
use Symfony\Component\HttpFoundation\Request;
use Ciconia\Extension\Gfm\WhiteSpaceExtension;
use Ciconia\Extension\Gfm\InlineStyleExtension;
use Silex\Provider\UrlGeneratorServiceProvider;

class TemplatingEngineServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app->register(new TwigServiceProvider(), [
            'twig.path' => $app->templatesPath(),
            'options' => [
                'debug' => !$app->isProduction()
            ]
        ]);

        $app->register(new UrlGeneratorServiceProvider());
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        $app['twig']->addGlobal('site', $app->config('application'));

        $app->before(function (Request $request, Application $app) {
            $app['twig']->addGlobal('current_page', $request->getRequestUri());

            if ($app['sentry']->check()) {
                $app['twig']->addGlobal('user', $app['sentry']->getUser());
            }

            if ($app['session']->has('flash')) {
                $app['twig']->addGlobal('flash', $app['session']->get('flash'));
                $app['session']->set('flash', null);
            }
        });

        // Twig Markdown Extension
        $markdown = new Ciconia();
        $markdown->addExtension(new InlineStyleExtension);
        $markdown->addExtension(new WhiteSpaceExtension);
        $engine = new CiconiaEngine($markdown);

        $app['twig']->addExtension(new MarkdownExtension($engine));
    }
}
