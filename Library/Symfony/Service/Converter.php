<?php

namespace Eckinox\Library\Symfony\Service;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Translation\TranslatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Border;

class Converter {
    const DOMAIN = "services";

    protected $translator;

    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    public function excelToArray($path, $calculateFormulas = false, $formulasAsNull = true, $stripBackslashes = true, $trimLeadingRows = true, $trimTrailingRows = true, $trimTrailingColumns = true, $trimValues = true) {
        if (!file_exists($path)) {
            throw new \Exception($this->translator->trans('converter.errors.fileNotFound', [], static::DOMAIN));
        }

        $file = new File($path);
        $supportedExtensions = ['csv', 'xls', 'xlsx', 'xlsm', 'bin', 'txt', 'zip'];

        if (!$file->isReadable()) {
            throw new \Exception($this->translator->trans('converter.errors.fileUnreadable', [], static::DOMAIN));
        }

        if (!$file->guessExtension() || !in_array(strtolower($file->guessExtension()), $supportedExtensions)) {
            var_dump($file->guessExtension());die();
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

            # Trim cell values
            if ($trimValues) {
                $columnCount = count($rows) ? count(current($rows)) : 0;
                $filledColumns = -1;

                # Loop over every row to check which columns are empty
                foreach ($rows as $rowIndex => $cells) {
                    foreach ($cells as $cellIndex => $cell) {
                        $rows[$rowIndex][$cellIndex] = trim($cell);
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
                    $cellData = [];
                }

                $cell->setValue($cellData['value'] ?? null);

                if ($y == count($data) - 1) {
                    $this->autosizeColumn($sheet, $cell);
                }

                $this->setCellStylesFromData($cell, $cellData);
                $this->setCellPossibleValues($cell, $cellData);
            }
        }
    }

    protected function autosizeColumn(&$sheet, $cell) {
        $sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
    }

    protected function setCellStylesFromData(&$cell, $data) {
        $this->setCellStyleAndFormatFromData($cell, $data);
        $this->setCellFontStylesFromData($cell, $data);
        $this->setCellBorderFromData($cell, $data);
    }

    protected function setCellStyleAndFormatFromData(&$cell, $data) {
        if ($cellStyles = strtolower($data['cell'] ?? null)) {
            $format = $cell->getStyle()->getNumberFormat();
            $fill = $cell->getStyle()->getFill();
            $parts = array_filter(explode(' ', $cellStyles));

            $formats = [
                'text' => NumberFormat::FORMAT_GENERAL,
                'number' => NumberFormat::FORMAT_NUMBER,
                'float' => NumberFormat::FORMAT_NUMBER_00,
                'percentage' => NumberFormat::FORMAT_PERCENTAGE,
                'percentage_decimals' => NumberFormat::FORMAT_PERCENTAGE_00,
                'date' => NumberFormat::FORMAT_DATE_YYYYMMDD2,
                'datetime' => NumberFormat::FORMAT_DATE_DATETIME,
                'monetary' => '#,##0_-$',
                'monetary_k' => '[=0]"-";#,k $',
                'monetary_decimals' => '#,##0.00_-$',
            ];

            foreach ($parts as $part) {
                if (isset($formats[$part])) {
                    if (in_array($part, ['monetary', 'monetary_k', 'monetary_decimals', 'number', 'float', 'percentage', 'percentage_decimals'])) {
                        $cell->setDataType(DataType::TYPE_NUMERIC);
                    }

                    $format->setFormatCode($formats[$part]);
                } else if (substr($part, 0, 1) == '#' || substr($part, 0, 4) == 'rgb(' || substr($part, 0, 5) == 'rgba(') {
                    $fill->setFillType(Fill::FILL_SOLID);
                    $fill->setStartColor($this->getExcelValidColor($part));
                }
            }
        }

        # Adjust column width
        $width = strtolower($data['width'] ?? null);
        if ($width) {
            $columnDimension = $cell->getWorksheet()->getColumnDimension($cell->getColumn());
            $columnDimension->setAutoSize(false);
            $columnDimension->setWidth($width);
        }
    }

    protected function setCellBorderFromData(&$cell, $data) {
        if ($borderStyles = strtolower($data['border'] ?? null)) {
            $border = $cell->getStyle()->getBorders()->getAllBorders();
            $parts = array_filter(explode(' ', $borderStyles));

            foreach ($parts as $part) {
                if ($part == 'thin') {
                    $border->setBorderStyle(Border::BORDER_THIN);
                } else if ($part == 'thick') {
                    $border->setBorderStyle(Border::BORDER_THICK);
                } else if ($part == 'medium') {
                    $border->setBorderStyle(Border::BORDER_MEDIUM);
                } else if ($part == 'hair') {
                    $border->setBorderStyle(Border::BORDER_HAIR);
                } else if ($part == 'none') {
                    $border->setBorderStyle(Border::BORDER_NONE);
                } else if (substr($part, 0, 1) == '#' || substr($part, 0, 4) == 'rgb(' || substr($part, 0, 5) == 'rgba(') {
                    $border->setColor($this->getExcelValidColor($part));
                }
            }
        }
    }

    protected function setCellFontStylesFromData(&$cell, $data) {
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

    protected function setCellPossibleValues(&$cell, $data) {
        if (isset($data['possible_values'])) {
            $validation = $cell->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(false);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setErrorTitle("Valeur invalide");
            $validation->setError("Cette valeur ne fait pas partie des valeurs permises.");
            $validation->setFormula1('"' . implode(',', $data['possible_values']) . '"');
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
            $string = substr($string, 1);

            if (strlen($string) == 3) {
                $string = str_repeat($string[0], 2) . str_repeat($string[1], 2) . str_repeat($string[2], 2);
            }

            return new Color($string);
        }

        if (strpos($string, 'rgb(') === 0 || strpos($string, 'rgba(') === 0) {
            $rbgParts = explode(',', preg_replace('/[^0-9,]/', '', $string));
            $hex = sprintf("%02x%02x%02x", $rbgParts[0], $rbgParts[1], $rbgParts[2]);
            return new Color($hex);
        }
    }
}
