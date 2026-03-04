# =============================================================================
# deploy.ps1 - Genera firma.zip listo para instalar desde la web de Moodle
# Uso: .\deploy.ps1
#
# Luego en Moodle:
#   Administración del sitio > Plugins > Instalar plugins > Subir firma.zip
# =============================================================================

$ErrorActionPreference = "Stop"

$PluginSrc = "$PSScriptRoot\local\firma"
$OutputZip = "$PSScriptRoot\firma.zip"

if (Test-Path $OutputZip) {
    Remove-Item $OutputZip -Force
}

Write-Host "`nGenerando firma.zip..." -ForegroundColor Cyan

# Compress-Archive usa backslashes en rutas internas, lo que rompe ZipArchive de PHP.
# Usamos System.IO.Compression directamente para garantizar forward slashes.
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$ExcludeDirs = @('.git')

$stream = [System.IO.File]::Open($OutputZip, [System.IO.FileMode]::Create)
$archive = [System.IO.Compression.ZipArchive]::new($stream, [System.IO.Compression.ZipArchiveMode]::Create, $false)

Get-ChildItem -Path $PluginSrc -Recurse -File | ForEach-Object {
    # Calcular ruta relativa con forward slashes
    $rel = $_.FullName.Substring($PluginSrc.Length + 1).Replace('\', '/')
    # Excluir directorios no deseados
    $skip = $false
    foreach ($ex in $ExcludeDirs) {
        if ($rel.StartsWith($ex, [System.StringComparison]::OrdinalIgnoreCase)) {
            $skip = $true; break
        }
    }
    if (-not $skip) {
        $entryName = "firma/$rel"
        $entry = $archive.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
        $entryStream = $entry.Open()
        $fileStream = [System.IO.File]::OpenRead($_.FullName)
        $fileStream.CopyTo($entryStream)
        $fileStream.Dispose()
        $entryStream.Dispose()
    }
}

$archive.Dispose()
$stream.Dispose()

$size = [math]::Round((Get-Item $OutputZip).Length / 1MB, 2)

Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host " firma.zip generado ($size MB)" -ForegroundColor Green
Write-Host " $OutputZip" -ForegroundColor White
Write-Host "============================================" -ForegroundColor Green
Write-Host ""
Write-Host "Pasos para instalar en Moodle:" -ForegroundColor Yellow
Write-Host "  1. http://10.20.10.3:8083/admin/tool/installaddon/index.php" -ForegroundColor White
Write-Host "  2. Sube firma.zip y sigue el asistente" -ForegroundColor White
Write-Host ""

# Abrir carpeta del zip en el Explorador de Windows
Start-Process explorer.exe -ArgumentList "/select,`"$OutputZip`""
