<?php

namespace Charcoal\Cms\Route;

// From PSR-7 (HTTP Messaging)
use \Psr\Http\Message\RequestInterface;
use \Psr\Http\Message\ResponseInterface;

// From Pimple
use \Pimple\Container;

// From 'charcoal-app'
use \Charcoal\App\Route\TemplateRoute;

/**
 * News Route Handler
 */
class NewsRoute extends TemplateRoute
{
    /**
     * URI path.
     *
     * @var string
     */
    private $path;

    /**
     * The news entry matching the URI path.
     *
     * @var \Charcoal\Cms\NewsInterface|\Charcoal\Object\RoutableInterface
     */
    private $news;

    /**
     * The news entry model.
     *
     * @var string
     */
    private $objType = 'charcoal/cms/news';

    /**
     * @param array|\ArrayAccess $data Class depdendencies.
     */
    public function __construct($data)
    {
        parent::__construct($data);
        $this->path = ltrim($data['path'], '/');
    }

    /**
     * Determine if the URI path resolves to an object.
     *
     * @param  Container $container A DI (Pimple) container.
     * @return boolean
     */
    public function pathResolvable(Container $container)
    {
        $news = $this->loadNewsFromPath($container);
        return !!$news->id();
    }

    /**
     * @param  Container         $container A DI (Pimple) container.
     * @param  RequestInterface  $request   A PSR-7 compatible Request instance.
     * @param  ResponseInterface $response  A PSR-7 compatible Response instance.
     * @return ResponseInterface
     */
    public function __invoke(Container $container, RequestInterface $request, ResponseInterface $response)
    {
        $config = $this->config();

        $news = $this->loadNewsFromPath($container);

        $templateIdent      = $news->templateIdent();
        $templateController = $news->templateIdent();

        $templateFactory = $container['template/factory'];

        $template = $templateFactory->create($templateController);
        $template->init($request);

        // Set custom data from config.
        $template->setData($config['template_data']);
        $template->setNews($news);

        $templateContent = $container['view']->render($templateIdent, $template);

        $response->write($templateContent);

        return $response;
    }

    /**
     * @param Container $container Pimple DI container.
     * @return \Charcoal\Cms\NewsInterface
     */
    protected function loadNewsFromPath(Container $container)
    {
        if (!$this->news) {
            $config  = $this->config();
            $objType = (isset($config['obj_type']) ? $config['obj_type'] : $this->objType);

            $this->news = $container['model/factory']->create($objType);

            $translator = TranslationConfig::instance();

            $langs = $translator->availableLanguages();
            $lang  = $this->news->loadFromL10n('slug', $this->path, $langs);
            $translator->setCurrentLanguage($lang);
        }

        return $this->news;
    }
}
