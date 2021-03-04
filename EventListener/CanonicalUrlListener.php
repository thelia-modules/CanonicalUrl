<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace CanonicalUrl\EventListener;

use CanonicalUrl\CanonicalUrl;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Log\Tlog;
use Thelia\Model\ConfigQuery;
use Thelia\Model\LangQuery;
use CanonicalUrl\Event\CanonicalUrlEvent;
use CanonicalUrl\Event\CanonicalUrlEvents;
use Thelia\Model\MetaDataQuery;

/**
 * Class CanonicalUrlListener
 * @package CanonicalUrl\EventListener
 * @author Gilles Bourgeat <gilles.bourgeat@gmail.com>
 */
class CanonicalUrlListener implements EventSubscriberInterface
{
    /** @var Request */
    protected $request;

    /** @var Session */
    protected $session;

    /**
     * @param Request $request
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();

        $this->session = $this->request->getSession();
    }

    /**
     * @param CanonicalUrlEvent $event
     */
    public function generateUrlCanonical(CanonicalUrlEvent $event)
    {
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
        $canonicalUrl = $parseUrlByCurrentLocale['scheme'] . '://' . $parseUrlByCurrentLocale['host'];

        // preserving a potential subdirectory, e.g. http://somehost.com/mydir/index.php/...
        $canonicalUrl .= $this->request->getBaseUrl();

        // Remove script name from path, e.g. http://somehost.com/index.php/...
        $canonicalUrl = preg_replace("!/index(_dev)?\.php!", '', $canonicalUrl);

        $path = $this->request->getPathInfo();

        if (!empty($path) && $path != "/") {
            $canonicalUrl .= $path;

            $canonicalUrl = rtrim($canonicalUrl, '/');
        } else {
            if (null === $queryString = $this->request->server->get('QUERY_STRING')){
                $queryString = $this->request->getQueryString();
            }

            if (! empty($queryString)) {
                $canonicalUrl .= '/?' . $queryString;
            }
        }

        try {
            $event->setUrl($canonicalUrl);
        } catch (\InvalidArgumentException $e) {
            Tlog::getInstance()->addWarning($e->getMessage());
        }
    }

    /**
     * @return array
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CanonicalUrlEvents::GENERATE_CANONICAL => [
                'generateUrlCanonical', 128
            ]
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
        // for one domain by lang
        if ((int) ConfigQuery::read('one_domain_foreach_lang', 0) === 1) {
            // We always query the DB here, as the Lang configuration (then the related URL) may change during the
            // user session lifetime, and improper URLs could be generated. This is quite odd, okay, but may happen.
            $langUrl = LangQuery::create()->findPk($this->request->getSession()->getLang()->getId())->getUrl();

            if (!empty($langUrl) && false !== $parse = parse_url($langUrl)) {
                return $parse;
            }
        }

        // Configured site URL
        $urlSite =  ConfigQuery::read('url_site');
        if (!empty($urlSite) && false !== $parse = parse_url($urlSite)) {
            return $parse;
        }

        // return current URL
        return parse_url($this->request->getUri());
    }

    /**
     * @return string|null
     */
    protected function getCanonicalOverride()
    {
        $routeParameters = $this->getRouteParameters();

        if (null === $routeParameters) {
            return null;
        }

        $metaCanonical = MetaDataQuery::create()
            ->filterByMetaKey(CanonicalUrl::SEO_CANONICAL_META_KEY)
            ->filterByElementKey($routeParameters['view'])
            ->filterByElementId($routeParameters['id'])
            ->findOne();

        if (null === $metaCanonical) {
            return null;
        }

        $canonicalValues = json_decode($metaCanonical->getValue(), true);

        $lang = $this->request->getSession()->getLang();

        if (!isset($canonicalValues[$lang->getLocale()])) {
            return null;
        }

        return $canonicalValues[$lang->getLocale()];
    }

    /**
     * @return array|null
     */
    protected function getRouteParameters()
    {
        $view = $this->request->get('view');
        if (null === $view) {
            $view = $this->request->get('_view');
        }
        if (null === $view) {
            return null;
        }

        $id = $this->request->get($view.'_id');

        if (null === $id) {
            return null;
        }

        return compact('view', 'id');
    }
}
