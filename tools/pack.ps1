param(
  [string]$Src  = "E:\OneDong",
  [string]$Ver  = "6.3.8",
  [string]$Root = "onedong"
)

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$excludeDirs  = @('.git', 'tools')
$excludeFiles = @('DEV_NOTES.md', '.gitignore')

$srcFull = (Resolve-Path $Src).Path
$tmpZip  = Join-Path $env:TEMP ("onedong-v{0}.zip" -f $Ver)
if (Test-Path $tmpZip) { Remove-Item -Force $tmpZip }

$files = Get-ChildItem -Path $srcFull -Recurse -File | Where-Object {
  $rel = $_.FullName.Substring($srcFull.Length).TrimStart('\')
  $parts = $rel.Split('\')
  $inExDir = $false
  foreach ($p in $parts[0..($parts.Length - 2)]) { if ($excludeDirs -contains $p) { $inExDir = $true } }
  (-not $inExDir) -and ($excludeFiles -notcontains $_.Name) -and ($_.Extension -ne '.zip')
}

$fs  = [System.IO.File]::Open($tmpZip, [System.IO.FileMode]::CreateNew)
$arc = New-Object System.IO.Compression.ZipArchive($fs, [System.IO.Compression.ZipArchiveMode]::Create)
foreach ($f in $files) {
  $rel   = $f.FullName.Substring($srcFull.Length).TrimStart('\')
  $entry = $Root + '/' + ($rel -replace '\\', '/')
  $e     = $arc.CreateEntry($entry, [System.IO.Compression.CompressionLevel]::Optimal)
  $es    = $e.Open()
  $bytes = [System.IO.File]::ReadAllBytes($f.FullName)
  $es.Write($bytes, 0, $bytes.Length)
  $es.Dispose()
}
$arc.Dispose()
$fs.Dispose()

Write-Output ("staged: {0}  files: {1}" -f $tmpZip, $files.Count)
