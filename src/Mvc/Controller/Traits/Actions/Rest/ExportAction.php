<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace PhalconKit\Mvc\Controller\Traits\Actions\Rest;

use Phalcon\Http\ResponseInterface;
use PhalconKit\Exception\HttpException;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractExport;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractExpose;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractModel;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractQuery;

trait ExportAction
{
    use AbstractExpose;
    use AbstractExport;
    use AbstractModel;
    use AbstractQuery;
    
    /**
     * Export records matching the prepared REST query.
     *
     * The action finds the result set, applies the export exposure rules, and
     * delegates response generation to the export trait. Content negotiation and
     * supported export formats are owned by `export()`.
     *
     * @throws HttpException When the requested export content type is not
     *     supported.
     * @throws \League\Csv\Exception When CSV generation fails.
     */
    public function exportAction(): ResponseInterface
    {
        $resultset = $this->find();
        $data = $this->exportExpose($resultset);
        return $this->export($data);
    }
}
