<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($pageTitle ?? 'ZERO.Net PANEL'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/zeronet/assets/style.css" rel="stylesheet">
    <?php if (!empty($extraCss)) echo $extraCss; ?>
</head>
<body>
