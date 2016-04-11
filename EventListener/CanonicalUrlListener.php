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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Model\ConfigQuery;
use Thelia\Model\LangQuery;
use CanonicalUrl\Event\CanonicalUrlEvent;
use CanonicalUrl\Event\CanonicalUrlEvents;

/**
 * Class CanonicalUrlListener
 * @package CanonicalUrl\EventListener
 * @author Gilles Bourgeat <gbourgeat@openstudio.fr>
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
    public function __construct(Request $request)
    {
        $this->request = $request;

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

        $parseUrlByCurrentLocale = $this->getParsedUrlByCurrentLocale();

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
            /*if (!ConfigQuery::read('allow_slash_ended_uri', false)) {
                $canonicalUrl = rtrim($canonicalUrl, '/');
            }*/
        } else {
            $queryString = $this->request->getQueryString();

            if (! empty($queryString)) {
                $canonicalUrl .= '/?' . $queryString;
            }
        }

        $event->setUrl($canonicalUrl);
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
}
