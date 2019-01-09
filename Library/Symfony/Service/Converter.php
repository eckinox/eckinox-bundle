<?php

namespace Eckinox\Library\Symfony\Service;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Translation\TranslatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Converter {
    const DOMAIN = "services";
    protected $translator;

    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    public function excelToArray($path, $calculateFormulas = false, $formulasAsNull = true) {
        if (!file_exists($path)) {
            throw new \Exception($this->translator->trans('converter.errors.fileNotFound', [], static::DOMAIN));
        }

        $file = new File($path);
        $supportedExtensions = ['csv', 'xls', 'xlsx'];

        if (!$file->isReadable()) {
            throw new \Exception($this->translator->trans('converter.errors.fileUnreadable', [], static::DOMAIN));
        }

        if (!$file->guessExtension() || !in_array(strtolower($file->guessExtension()), $supportedExtensions)) {
            throw new \Exception($this->translator->trans('converter.errors.fileType', ['%extensions%' => implode(', ', $supportedExtensions)], static::DOMAIN));
        }

        $spreadsheet = IOFactory::load($path);

        $data = [];

        $sheetsCount = count($spreadsheet->getAllSheets());
        for ($sheetIndex = 0; $sheetIndex < $sheetsCount; $sheetIndex++) {
            $sheet = $spreadsheet->getSheet($sheetIndex);
            $highestPoint = $sheet->getHighestRowAndColumn();

            if ($formulasAsNull) {
                $rows = $sheet->rangeToArray('A1:' . $highestPoint['column'] . $highestPoint['row'], null, $calculateFormulas);
                foreach ($rows as $rowIndex => $row) {
                    foreach ($row as $colIndex => $value) {
                        if ($value && strpos($value, '=') === 0) {
                            $rows[$rowIndex][$colIndex] = null;
                        }
                    }
                }
            }

            $data[] = [
                'index' => $sheetIndex,
                'title' => $sheet->getTitle(),
                'codename' => $sheet->getCodeName(),
                'rows' => $rows
            ];
        }

        return $data;
    }

}
