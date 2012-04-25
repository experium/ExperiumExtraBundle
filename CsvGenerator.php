<?php

namespace Experium\ExtraBundle;

/**
 * @author Vyacheslav Salakhutdinov <salakhutdinov@experium.ru>
 * @author Alexey Shockov <shokov@experium.ru>
 */
class CsvGenerator
{
    private $outputEncoding;

    private $delimiter;

    private $escaper;

    public function __construct($outputEncoding = null, $delimiter = ';', $escaper = '"')
    {
        $this->outputEncoding = $outputEncoding;
        $this->delimiter      = $delimiter;
        $this->escaper        = $escaper;
    }

    public function generate($tableData)
    {
        $table = '';
        foreach ($tableData as $rowData) {
            $table .= $this->generateRow($rowData);
        }

        return $table;
    }

    public function generateRow($rowData)
    {
        // TODO переделать чтобы ок было.
        $rowData = str_replace('"', '`', $rowData);
        $rowData = str_replace("\r\n", " ", $rowData);
        $rowData = str_replace("\r", " ", $rowData);
        $rowData = str_replace("\n", " ", $rowData);
        // TODO Обязательно именно "\r\n"?
        $row = $this->escaper.implode($this->escaper.$this->delimiter.$this->escaper, $rowData).$this->escaper."\r\n";

        if ($this->outputEncoding) {
            $row = iconv('UTF-8', $this->outputEncoding, $row);
        }

        return $row;
    }
}
