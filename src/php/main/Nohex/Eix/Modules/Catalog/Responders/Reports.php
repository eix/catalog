<?php

namespace Nohex\Eix\Modules\Catalog\Responders;

use Nohex\Eix\Modules\Catalog\Model\Reports as ReportsFactory;

/**
 * Responder for reporting-related requests.
 */
class Reports extends \Nohex\Eix\Core\Responders\Http\Page
{
    public function httpGetForHtml()
    {
        $this->response = new \Nohex\Eix\Core\Responses\Http\Html($this->getRequest());

        $id = $this->getRequest()->getParameter('id');
        if ($id) {
            // Set the template that will show the report.
            $this->response->setTemplateId('reports/view');
            // Find the report.
            $report = ReportsFactory::getInstance()->findEntity($id);
            // Set the report data in the response.
            $this->response->setData('report', array(
                'id' => $report->id,
                'type' => $report->type,
                'date' => strftime('%c', $report->createdOn->getTimestamp()),
                'details' => $report->details,
            ));
        } else {
            $this->response->setTemplateId('reports/index');
            $reports = array();
            foreach (ReportsFactory::getInstance()->getAll() as $report) {
                $reports[] = array(
                    'id' => $report->id,
                    'type' => $report->type,
                    'date' => strftime('%c', $report->createdOn->getTimestamp()),
                );
            }
            $this->response->setData('reports', $reports);
        }

        return parent::httpGetForHtml();
    }

}
