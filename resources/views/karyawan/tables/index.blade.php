@include('tables.workspace', [
    'workspaceLayout' => 'layouts.karyawan',
    'workspaceTitle' => 'Ketersediaan Meja',
    'tableRoutePrefix' => 'karyawan.tables',
    'canViewTableHistory' => false,
    'statusDotsOnly' => true,
])
