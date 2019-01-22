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

namespace CanonicalUrl;

use Thelia\Module\BaseModule;

class CanonicalUrl extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'canonicalurl';

    const SEO_CANONICAL_META_KEY = 'seo_canonical_meta';
}
