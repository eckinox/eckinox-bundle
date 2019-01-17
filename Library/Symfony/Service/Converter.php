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

    public function excelToArray($path, $calculateFormulas = false, $formulasAsNull = true, $stripBackslashes = true, $trimLeadingRows = true, $trimTrailingRows = true, $trimTrailingColumns = true) {
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

}
