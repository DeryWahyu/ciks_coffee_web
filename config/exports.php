<?php

return [
    'disk' => 'local',
    // PDF and XLSX build a document in memory. CSV archives stream every row.
    'pdf_max_rows' => 500,
    'excel_max_rows' => 5000,
];
