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

namespace CanonicalUrl\Hook;

use CanonicalUrl\CanonicalUrl;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Model\MetaDataQuery;

class SeoUpdateFormHook extends BaseHook
{
    public function addInputs(HookRenderEvent $event): void
    {
        $id = $event->getArgument('id');
        $type = $event->getArgument('type');

        $canonical = null;
        $canonicalMetaData = MetaDataQuery::create()
            ->filterByMetaKey(CanonicalUrl::SEO_CANONICAL_META_KEY)
            ->filterByElementKey($type)
            ->filterByElementId($id)
            ->findOneOrCreate();

        $canonicalMetaDataValues = json_decode($canonicalMetaData->getValue(), true);

        $lang = $this->getSession()->getAdminEditionLang();

        if (isset($canonicalMetaDataValues[$lang->getLocale()])) {
            $canonical = $canonicalMetaDataValues[$lang->getLocale()];
        }

        $event->add($this->render(
            'hook-seo-update-form.html',
            [
                'form' => $event->getArgument('form'),
                'canonical' => $canonical,
            ]
        ));
    }
}
