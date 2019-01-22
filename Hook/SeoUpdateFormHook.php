<?php

namespace CanonicalUrl\Hook;

use CanonicalUrl\CanonicalUrl;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Model\MetaDataQuery;

class SeoUpdateFormHook extends BaseHook
{
    public function addInputs(HookRenderEvent $event)
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
                'canonical' => $canonical
            ]
        ));
    }
}
