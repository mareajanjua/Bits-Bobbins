param(
    [string] $OutputPath = "",
    [string] $ReportPath = ""
)

$ErrorActionPreference = "Stop"

$projectRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot "..")).Path
$projectName = Split-Path -Leaf $projectRoot
$submissionFileName = "e project Online Cart System - Marea Tayyab - 1698738.zip"

if ([string]::IsNullOrWhiteSpace($OutputPath)) {
    $OutputPath = Join-Path $projectRoot $submissionFileName
}

if ([string]::IsNullOrWhiteSpace($ReportPath)) {
    $ReportPath = Join-Path ([Environment]::GetFolderPath("UserProfile")) "Downloads\E-project-OnlineCartSystem-1698738.docx"
}

$resolvedOutputParent = Split-Path -Parent $OutputPath
if (-not (Test-Path -LiteralPath $resolvedOutputParent)) {
    New-Item -ItemType Directory -Path $resolvedOutputParent | Out-Null
}

$excludeDirs = @(
    ".git",
    ".idea",
    ".vscode",
    "vendor",
    "node_modules",
    "database/factories",
    "database/migrations",
    "resources/css",
    "resources/js",
    "resources/template-source",
    "resources/views/auth",
    "resources/views/frontend/account",
    "storage/logs",
    "storage/framework/views",
    "storage/framework/cache",
    "storage/framework/sessions",
    "storage/framework/testing",
    "bootstrap/cache",
    "public/assets/css",
    "public/assets/frontend/uploads",
    "public/assets/dashboard/uploads",
    "public/assets/frontend/img/Home Page/Products/Dolls & Accessories"
)

$excludeFiles = @(
    ".env",
    ".env.backup",
    ".env.production",
    ".phpunit.result.cache",
    "npm-debug.log",
    "yarn-error.log",
    "$projectName-submission.zip",
    $submissionFileName,
    "routes/api.php",
    "routes/channels.php",
    "app/Providers/BroadcastServiceProvider.php",
    "config/broadcasting.php",
    "config/sanctum.php",
    "app/Models/Category.php",
    "app/Models/Product.php",
    "app/Models/Order.php",
    "app/Models/OrderItem.php",
    "app/Models/Payment.php",
    "app/Models/Feedback.php",
    "app/Models/Faq.php",
    "resources/views/frontend/partials/kider-hero.blade.php",
    "public/assets/dashboard/images/spark-admin-free.png",
    "public/assets/dashboard/images/user_1.jpg",
    "public/assets/dashboard/js/auth.js",
    "public/assets/frontend/lib/animate/animate.css",
    "public/assets/frontend/lib/easing/easing.js",
    "public/assets/frontend/lib/owlcarousel/owl.carousel.js",
    "public/assets/frontend/lib/owlcarousel/assets/owl.theme.default.css",
    "public/assets/frontend/lib/owlcarousel/assets/owl.theme.default.min.css",
    "public/assets/frontend/lib/owlcarousel/assets/owl.theme.green.css",
    "public/assets/frontend/lib/owlcarousel/assets/owl.theme.green.min.css",
    "public/assets/frontend/lib/wow/wow.js",
    "public/assets/frontend/uploads/customers/customer-1.jfif",
    "public/assets/frontend/uploads/customers/customer-2.jfif",
    "public/assets/frontend/uploads/customers/customer-3.jfif",
    "public/assets/frontend/uploads/customers/customer-4.jfif",
    "public/assets/frontend/uploads/customers/customer-5.jfif",
    "public/assets/frontend/uploads/customers/customer-6.jfif",
    "public/assets/dashboard/uploads/admin/admin-1.png",
    "public/assets/dashboard/uploads/admin/admin-1.jfif",
    "public/assets/dashboard/uploads/employees/employee-2.jfif",
    "public/assets/store/products/0100001-image-front-1789619962.png",
    "public/assets/store/products/0100001-image-front-1789710539.png",
    "public/assets/store/products/0100001-image-hover-1789619962.png",
    "public/assets/store/products/0100001-image-hover-1789710539.png",
    "public/assets/store/products/0100002-image-front-1789620087.png",
    "public/assets/store/products/0100002-image-front-1789710457.png",
    "public/assets/store/products/0100002-image-hover-1789620087.png",
    "public/assets/store/products/0100002-image-hover-1789710457.png",
    "public/assets/store/products/0100003-image-front-1789620210.png",
    "public/assets/store/products/0100003-image-front-1789710505.png",
    "public/assets/store/products/0100003-image-hover-1789620210.png",
    "public/assets/store/products/0100003-image-hover-1789710505.png",
    "public/assets/store/products/0100004-image-front-1789620571.png",
    "public/assets/store/products/0100004-image-front-1789710579.png",
    "public/assets/store/products/0100004-image-hover-1789620571.png",
    "public/assets/store/products/0100004-image-hover-1789710579.png",
    "public/assets/store/products/0300006-image-front-1789972040.jfif",
    "public/assets/store/products/0300006-image-hover-1789972040.jfif",
    "public/assets/store/products/0300007-image-front-1789972041.jfif",
    "public/assets/store/products/0300007-image-hover-1789972041.jfif",
    "public/assets/frontend/img/Home Page/Hero/Kids&general.png",
    "public/assets/frontend/img/Home Page/Hero/beauty.png",
    "public/assets/frontend/img/Home Page/Hero/gifts&accessories.png",
    "public/assets/frontend/img/Home Page/Hero/wallets&bags.png",
    "public/assets/frontend/img/Home Page/Hero/Dollhouses.png",
    "public/assets/frontend/img/Home Page/Category/Gifts&Stationary.png",
    "public/assets/frontend/img/Home Page/Category/Art&Craft.png",
    "public/assets/frontend/img/Home Page/Category/Bags&Wallets.png",
    "public/assets/frontend/img/Home Page/Category/Dolls & Accessories.png",
    "public/assets/frontend/img/Home Page/Category/Beauty & Skincare.png",
    "public/assets/frontend/img/Home Page/Category/Kids (General & Lifestyle).png",
    "public/assets/frontend/img/Home Page/Category/banner.jpg"
)

$tempRoot = Join-Path ([System.IO.Path]::GetTempPath()) ("bb-submission-" + [System.Guid]::NewGuid().ToString("N"))
$stageRoot = Join-Path $tempRoot $projectName

New-Item -ItemType Directory -Path $stageRoot | Out-Null

try {
    Get-ChildItem -LiteralPath $projectRoot -Force | ForEach-Object {
        $relative = $_.Name

        if ($excludeDirs -contains $relative -or $excludeFiles -contains $relative) {
            return
        }

        Copy-Item -LiteralPath $_.FullName -Destination (Join-Path $stageRoot $relative) -Recurse -Force
    }

    if (-not (Test-Path -LiteralPath $ReportPath)) {
        throw "Report file not found: $ReportPath. Pass -ReportPath with the Word report location, or put E-project-OnlineCartSystem-1698738.docx in your Downloads folder."
    }

    Copy-Item -LiteralPath $ReportPath -Destination (Join-Path $stageRoot (Split-Path -Leaf $ReportPath)) -Force

    foreach ($dir in $excludeDirs) {
        $target = Join-Path $stageRoot $dir
        if (Test-Path -LiteralPath $target) {
            Remove-Item -LiteralPath $target -Recurse -Force
        }
    }

    foreach ($file in $excludeFiles) {
        $target = Join-Path $stageRoot $file
        if (Test-Path -LiteralPath $target) {
            Remove-Item -LiteralPath $target -Force
        }
    }

    if (Test-Path -LiteralPath $OutputPath) {
        Remove-Item -LiteralPath $OutputPath -Force
    }

    Compress-Archive -LiteralPath $stageRoot -DestinationPath $OutputPath -Force
    Write-Host "Created submission zip: $OutputPath"
} finally {
    if (Test-Path -LiteralPath $tempRoot) {
        Remove-Item -LiteralPath $tempRoot -Recurse -Force
    }
}
