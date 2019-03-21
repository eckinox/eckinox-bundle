<?php

namespace Eckinox\Library\Symfony\Service;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Translation\TranslatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Color;

class Converter {
    const DOMAIN = "services";

    protected $translator;

    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    public function excelToArray($path, $calculateFormulas = false, $formulasAsNull = true, $stripBackslashes = true, $trimLeadingRows = true, $trimTrailingRows = true, $trimTrailingColumns = true) {
        if (!file_exists($path)) {
            throw new \Exception($this->translator->trans('converter.errors.fileNotFound', [], static::DOMAIN));
        }

        $file = new File($path);
        $supportedExtensions = ['csv', 'xls', 'xlsx', 'xlsm'];

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

            $rows = $sheet->rangeToArray('A1:' . $highestPoint['column'] . $highestPoint['row'], null, $calculateFormulas);

            foreach ($rows as $rowIndex => $row) {
                foreach ($row as $colIndex => $value) {
                    if ($formulasAsNull) {
                        if ($value && strpos($value, '=') === 0) {
                            $rows[$rowIndex][$colIndex] = null;
                        }
                    }

                    if ($stripBackslashes && $rows[$rowIndex][$colIndex]) {
                        $rows[$rowIndex][$colIndex] = str_replace('\\', '', $rows[$rowIndex][$colIndex]);
                    }
                }
            }

            # Trim trailing empty rows
            if ($trimLeadingRows) {
                for ($i = 0; $i < count($rows); $i++) {
                    foreach ($rows[$i] as $cellValue) {
                        if ($cellValue) {
                            # The current row isn't empty, therefore, all trailing empty rows have been removed.
                            break 2;
                        }
                    }

                    unset($rows[$i]);
                }
            }

            # Trim trailing empty rows
            if ($trimTrailingRows) {
                for ($i = count($rows) - 1; $i >= 0; $i--) {
                    foreach ($rows[$i] as $cellValue) {
                        if ($cellValue) {
                            # The current row isn't empty, therefore, all trailing empty rows have been removed.
                            break 2;
                        }
                    }

                    unset($rows[$i]);
                }

                $rows = array_values($rows);
            }

            # Trim trailing empty rows
            if ($trimTrailingRows) {
                for ($i = count($rows) - 1; $i >= 0; $i--) {
                    foreach ($rows[$i] as $cellValue) {
                        if ($cellValue) {
                            # The current row isn't empty, therefore, all trailing empty rows have been removed.
                            break 2;
                        }
                    }

                    unset($rows[$i]);
                }
            }

            # Trim trailing empty columns
            if ($trimTrailingColumns) {
                $columnCount = count($rows) ? count(current($rows)) : 0;
                $filledColumns = -1;

                # Loop over every row to check which columns are empty
                foreach ($rows as $cells) {
                    for ($i = count($cells) - 1; $i >= 0; $i--) {
                        if ($cells[$i] && $i > $filledColumns) {
                            $filledColumns = $i;
                        }

                        if ($filledColumns >= $columnCount) {
                            break 2;
                        }
                    }
                }

                # Remove the empty columns if any
                if ($filledColumns + 1 < $columnCount) {
                    foreach ($rows as $rowIndex => $row) {
                        for ($columnIndex = $filledColumns + 1; $columnIndex < $columnCount; $columnIndex++) {
                            unset($rows[$rowIndex][$columnIndex]);
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

    public function arrayToExcel($array, $saveFilepath = null, $download = null) {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        for ($i = 0; $i < count($array); $i++) {
            $sheetData = $array[$i];
            $sheet = new Worksheet($spreadsheet, $sheetData['title'] ?? null);
            $spreadsheet->addSheet($sheet, $i);

            $this->fillWorksheetFromArray($sheet, $sheetData['cells'] ?? []);
        }

        $writer = new Xlsx($spreadsheet);

        if ($saveFilepath) {
            $writer->save($saveFilepath);
        }

        if ($download) {
            $filename = is_string($download) ? $download : 'Export ' . date('Y-m-d h:i');

            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'. $filename .'.xlsx"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            die();
        }
    }

    protected function fillWorksheetFromArray(&$sheet, $data) {
        for ($y = 0; $y < count($data); $y++) {
            $row = $data[$y];

            if (!is_array($row)) {
                continue;
            }

            for ($x = 0; $x < count($row); $x++) {
                $coordinate = Coordinate::stringFromColumnIndex($x + 1) . ($y + 1);
                $cell = $sheet->getCell($coordinate);

                try {
                    $cellData = is_array($row[$x]) ? $row[$x] : ['value' => $row[$x]];
                } catch (\Exception $e){
                    dump($row, $x);
                }

                $cell->setValue($cellData['value'] ?? null);
                $this->setCellStylesFromData($cell, $cellData);
            }
        }
    }

    protected function setCellStylesFromData(&$cell, $data) {
        if ($fontStyles = strtolower($data['font'] ?? null)) {
            $font = $cell->getStyle()->getFont();
            $alignment = $cell->getStyle()->getAlignment();
            $parts = array_filter(explode(' ', $fontStyles));

            foreach ($parts as $part) {
                if ($part == 'bold') {
                    $font->setBold(true);
                } else if ($part == 'italic') {
                    $font->setItalic(true);
                } else if ($part == 'underline') {
                    $font->setUnderline(true);
                } else if ($part == 'sub') {
                    $font->setSubscript(true);
                } else if ($part == 'sup') {
                    $font->setSuperscript(true);
                } else if ($part == 'strike') {
                    $font->setStrikethrough(true);
                } else if ($part == 'center') {
                    $alignment->setHorizontal($alignment::HORIZONTAL_CENTER);
                } else if ($part == 'left') {
                    $alignment->setHorizontal($alignment::HORIZONTAL_LEFT);
                } else if ($part == 'right') {
                    $alignment->setHorizontal($alignment::HORIZONTAL_RIGHT);
                } else if ($part == 'justify') {
                    $alignment->setHorizontal($alignment::HORIZONTAL_JUSTIFY);
                } else if ($part == 'wrap') {
                    $alignment->setWrapText(true);
                } else if (substr($part, 0, 1) == '#' || substr($part, 0, 4) == 'rgb(' || substr($part, 0, 5) == 'rgba(') {
                    $font->setColor($this->getExcelValidColor($part));
                } else if (is_numeric($part)) {
                    $font->setSize($part);
                } else {
                    $font->setName($part);
                }
            }
        }
    }

    protected function getExcelValidColor($string) {
        $knownColors = [
            'black' => '000000',
            'white' => 'FFFFFF',
            'blue' => '0000FF',
            'red' => 'FF0000',
            'yellow' => 'FFEF00',
            'orange' => 'FF9D00',
            'purple' => 'C400FF',
            'green' => '33D419',
            'gray' => 'ADADAD',
            'pink' => 'EF40E6'
        ];

        if (isset($knownColors[$string])) {
            return new Color($knownColors[$string]);
        }

        if (strpos($string, '#') === 0) {
            return new Color(substr($string, 1));
        }

        if (strpos($string, 'rgb(') === 0 || strpos($string, 'rgba(') === 0) {
            $rbgParts = explode(',', preg_replace('/[^0-9,]/', '', $string));
            $hex = sprintf("%02x%02x%02x", $rbgParts[0], $rbgParts[1], $rbgParts[2]);
            return new Color($hex);
        }
    }
}
