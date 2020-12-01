<?php
# src/Library/Application/pdf.php

namespace Eckinox\Library\Application;

trait pdf {
    private $pdfDefaultOptions = [
        'margin-top' => '5',
        'margin-right' => '3',
        'margin-left' => '3',
        'margin-bottom' => '8'
    ];

    public function generatePdf(String $viewPath, String $fileName, Array $params = [], Array $options = [], $download = true) {
        $pdf = $this->snappy;
        $pdf->setBinary('/usr/bin/xvfb-run /usr/bin/wkhtmltopdf-qt');
        $pdf->setOption('encoding', 'UTF-8');

        if(strpos($fileName, '.pdf') === false) {
            $fileName .= '.pdf';
        }

        $options = array_replace($this->pdfDefaultOptions, $options);
        $html = $this->renderView($viewPath, $params);

        if($download) {
            return new \Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse($pdf->getOutputFromHtml($html, $options), $fileName);
        } else {
            $response = new \Symfony\Component\HttpFoundation\Response();
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', strtr("filename=%fileName%", [ '%fileName%' => $fileName ]));

            $response->setContent($pdf->getOutputFromHtml($html, $options));

            return $response;
        }
    }

    public function savePdfTo(String $viewPath, String $filePath, Array $params = [], Array $options = []) {
        $pdf = $this->get('knp_snappy.pdf');
        $pdf->setBinary('/usr/bin/xvfb-run /usr/bin/wkhtmltopdf-qt');

        $options = array_replace($this->pdfDefaultOptions, $options);
        $html = $this->renderView($viewPath, $params);

        $pdf->generateFromHtml($html, $filePath, $options);
    }
}
