<?php
declare(strict_types=1);

namespace T3G\AgencyPack\FileVariants\Tests\Functional\PublicFileStorage;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use T3G\AgencyPack\FileVariants\Tests\Functional\ConcerningMetadata;

/**
 * Class ConcerningMetadataTest
 * @package T3G\AgencyPack\FileVariants\Tests\Functional\PublicFileStorage
 */
class ConcerningMetadataTest extends ConcerningMetadata
{
    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3conf/ext/file_variants/Tests/Functional/PublicFileStorage/DataSet/ConcerningMetadata/Initial/';

    /**
     * @var string
     */
    protected $assertionDataSetDirectory = 'typo3conf/ext/file_variants/Tests/Functional/PublicFileStorage/DataSet/ConcerningMetadata/AfterOperation/';

}
