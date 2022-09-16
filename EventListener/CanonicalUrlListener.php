<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CanonicalUrl\EventListener;

use BetterSeo\Model\BetterSeoQuery;
use CanonicalUrl\CanonicalUrl;
use CanonicalUrl\Event\CanonicalUrlEvent;
use CanonicalUrl\Event\CanonicalUrlEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Log\Tlog;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Lang;
use Thelia\Model\LangQuery;
use Thelia\Model\MetaDataQuery;

/**
 * Class CanonicalUrlListener.
 *
 * @author Gilles Bourgeat <gilles.bourgeat@gmail.com>
 */
class CanonicalUrlListener implements EventSubscriberInterface
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var Session */
    protected $session;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function generateUrlCanonical(CanonicalUrlEvent $event): void
    {
        /** @var Request $request */
        if (null === $request = $this->requestStack->getCurrentRequest()) {
            return;
        }

        if ($event->getUrl() !== null) {
            return;
        }

        if (null !== $canonicalOverride = $this->getCanonicalOverride()) {
            try {
                $event->setUrl($canonicalOverride);

                return;
            } catch (\InvalidArgumentException $e) {
                Tlog::getInstance()->addWarning($e->getMessage());
            }
        }

        $parseUrlByCurrentLocale = $this->getParsedUrlByCurrentLocale();

        if (empty($parseUrlByCurrentLocale['host'])) {
            return;
        }

        // Be sure to use the proper domain name
        $canonicalUrl = $parseUrlByCurrentLocale['scheme'].'://'.$parseUrlByCurrentLocale['host'];

        // preserving a potential subdirectory, e.g. http://somehost.com/mydir/index.php/...
        $canonicalUrl .= $request->getBaseUrl();

        // Remove script name from path, e.g. http://somehost.com/index.php/...
        $canonicalUrl = preg_replace("!/index(_dev)?\.php!", '', $canonicalUrl);

        $path = $request->getPathInfo();

        if (!empty($path) && $path != '/') {
            $canonicalUrl .= $path;

            $canonicalUrl = rtrim($canonicalUrl, '/');
        } else {
            $canonicalUrl .= '/?'. (array_key_exists("query", $parseUrlByCurrentLocale)) ? $parseUrlByCurrentLocale['query'] : "";
        }

        try {
            $event->setUrl($canonicalUrl);
        } catch (\InvalidArgumentException $e) {
            Tlog::getInstance()->addWarning($e->getMessage());
        }
    }

    /**
     * @return array
     *               {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CanonicalUrlEvents::GENERATE_CANONICAL => [
                'generateUrlCanonical', 128,
            ],
        ];
    }

    /**
     * @return array
     *
     * At least one element will be present within the array.
     * Potential keys within this array are:
     * scheme - e.g. http
     * host
     * port
     * user
     * pass
     * path
     * query - after the question mark ?
     * fragment - after the hashmark #
     */
    protected function getParsedUrlByCurrentLocale()
    {
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        // for one domain by lang
        if ((int) ConfigQuery::read('one_domain_foreach_lang', 0) === 1) {
            // We always query the DB here, as the Lang configuration (then the related URL) may change during the
            // user session lifetime, and improper URLs could be generated. This is quite odd, okay, but may happen.
            $langUrl = LangQuery::create()->findPk($request->getSession()->getLang()->getId())->getUrl();

            if (!empty($langUrl) && false !== $parse = parse_url($langUrl)) {
                return $parse;
            }
        }

        // Configured site URL
        $urlSite = ConfigQuery::read('url_site');
        if (!empty($urlSite) && false !== $parse = parse_url($urlSite)) {
            return $parse;
        }

        // return current URL
        return parse_url($request->getUri());
    }

    /**
     * @return string|null
     */
    protected function getCanonicalOverride()
    {
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();
        $lang = $request->getSession()->getLang();

        $routeParameters = $this->getRouteParameters();

        if (null === $routeParameters) {
            return null;
        }

        $url = null;

        $metaCanonical = MetaDataQuery::create()
            ->filterByMetaKey(CanonicalUrl::SEO_CANONICAL_META_KEY)
            ->filterByElementKey($routeParameters['view'])
            ->filterByElementId($routeParameters['id'])
            ->findOne();

        if (null !== $metaCanonical) {
            $canonicalValues = json_decode($metaCanonical->getValue(), true);

            $url = $canonicalValues[$lang->getLocale()] !== "" ? $canonicalValues[$lang->getLocale()] :null;
        }

        // Try to get old field of BetterSeoModule
        if (null === $url && class_exists("BetterSeo\BetterSeo")) {
            try {
                $betterSeoData = BetterSeoQuery::create()
                    ->filterByObjectType($routeParameters['view'])
                    ->filterByObjectId($routeParameters['id'])
                    ->findOne();

                $url = $betterSeoData->setLocale($lang->getLocale())
                    ->getCanonicalField();
            } catch (\Throwable $exception) {
                //Catch if field doesn't exist but do nothing
            }
        }

        if (null === $url) {
            return null;
        }

        if (false === filter_var($url, \FILTER_VALIDATE_URL)) {
            return rtrim($this->getSiteBaseUrlForLocale($lang), "/")."/".$url;
        }

        return $url;
    }

    protected function getSiteBaseUrlForLocale(Lang $lang = null)
    {
        if (null === $lang) {
            $lang = $this->requestStack->getCurrentRequest()->getSession()->getLang();
        }
        if ((int) ConfigQuery::read('one_domain_foreach_lang', 0) === 1) {
            // We always query the DB here, as the Lang configuration (then the related URL) may change during the
            // user session lifetime, and improper URLs could be generated. This is quite odd, okay, but may happen.
            $langUrl = LangQuery::create()->findPk($lang->getId())->getUrl();
            return $langUrl;
        }

        // Configured site URL
        $urlSite = ConfigQuery::read('url_site');
        return $urlSite;
    }

    /**
     * @return array|null
     */
    protected function getRouteParameters()
    {
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        $view = $request->get('view');
        if (null === $view) {
            $view = $request->get('_view');
        }
        if (null === $view) {
            return null;
        }

        $id = $request->get($view.'_id');

        if (null === $id) {
            return null;
        }

        return compact('view', 'id');
    }
}
