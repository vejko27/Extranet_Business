$ErrorActionPreference = 'Stop'
$php = 'C:\xampp\php\php.exe'
$installer = Join-Path $PSScriptRoot 'install-admin.php'

if (-not (Test-Path -LiteralPath $php -PathType Leaf)) {
    throw "No se encontro PHP en $php."
}

$first = Read-Host 'Contrasena inicial de vcanova (minimo 12 caracteres)' -AsSecureString
$second = Read-Host 'Confirma la contrasena' -AsSecureString
$firstPointer = [IntPtr]::Zero
$secondPointer = [IntPtr]::Zero
try {
    $firstPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($first)
    $secondPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($second)
    $password = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($firstPointer)
    $confirmation = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($secondPointer)
    if ($password -cne $confirmation) {
        throw 'Las contrasenas no coinciden.'
    }
    if ($password.Contains("`r") -or $password.Contains("`n")) {
        throw 'La contrasena no puede contener saltos de linea.'
    }

    $OutputEncoding = New-Object System.Text.UTF8Encoding($false)
    $password | & $php $installer
    if ($LASTEXITCODE -ne 0) {
        throw 'La instalacion no se completo.'
    }
} finally {
    $password = $null
    $confirmation = $null
    if ($firstPointer -ne [IntPtr]::Zero) {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($firstPointer)
    }
    if ($secondPointer -ne [IntPtr]::Zero) {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($secondPointer)
    }
    $first.Dispose()
    $second.Dispose()
}
