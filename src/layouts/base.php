<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $site_name; ?></title>
    <!-- Removed unnecessary spaces within the <title> tag -->
    <!-- <link rel="shortcut icon" href="./assets/img/logo/favicon.ico"> Uncomment or remove based on necessity -->
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <?php '../layouts/base.php'; ?> <!-- Changed from a string to an include statement -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/modal.css">
    <?php '../layouts/styles.php'; ?> <!-- Changed from a string to an include statement -->
</head>

<body class="<?php echo $in_concat ? 'data-bs-theme="light"' : 'bg-dark'; ?>">
    <!-- Simplified the PHP conditional logic for body class -->
    <div class="<?php echo $in_concat ? '' : 'page'; ?>">
        <!-- Applied conditional logic for div class -->
        <?php echo $content; ?>
    </div>
</body>

</html>
