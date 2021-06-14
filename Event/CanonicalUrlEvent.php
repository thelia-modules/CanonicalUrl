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

namespace CanonicalUrl\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class CanonicalUrlEvent.
 *
 * @author Gilles Bourgeat <gilles.bourgeat@gmail.com>
 */
class CanonicalUrlEvent extends Event
{
    /** @var string|null */
    protected $url = null;

    /**
     * @return string|null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     *
     * @return $this
     */
    public function setUrl($url)
    {
        if ($url !== null && $url[0] !== '/' && filter_var($url, \FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('The value "'.(string) $url.'" is not a valid Url or Uri.');
        }

        $this->url = $url;

        return $this;
    }
}
