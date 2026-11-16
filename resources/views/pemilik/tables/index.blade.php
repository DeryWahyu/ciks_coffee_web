@include('tables.workspace', [
    'workspaceLayout' => 'layouts.pemilik',
    'workspaceTitle' => 'Manajemen Meja',
    'tableRoutePrefix' => 'pemilik.tables',
    'canViewTableHistory' => true,
    'statusDotsOnly' => false,
])
