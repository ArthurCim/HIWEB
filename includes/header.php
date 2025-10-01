<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $page_title ?? "MIMO"; ?></title>

    <?php if (isset($page_css)): ?>
        <link rel="stylesheet" href="assets/<?php echo $page_css; ?>">
    <?php endif; ?>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg: #f5f7fb;
            --panel: #ffffff;
            --accent-1: #4e73df;
            --accent-2: #6f42c1;
            --muted: #6b7280;
            --success: #10b981;
            --danger: #ef4444;
            --radius: 10px;
            --gap: 18px;
            --max-width: 1200px;
            --glass: rgba(255, 255, 255, 0.6);
        }

        * {
            box-sizing: border-box
        }

        html,
        body {
            height: 100%
        }

        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            background: var(--bg);
            color: #111827;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            line-height: 1.4;
            padding-top: 72px;
            /* space for fixed navbar */
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 72px;
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 1050;
            box-shadow: 0 4px 18px rgba(16, 24, 40, 0.08);
        }

        .navbar .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            font-size: 18px;
        }

        .navbar .brand img {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--glass);
            padding: 4px;
        }

        .navbar .nav-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .navbar a.logout {
            color: #fff;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.12);
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 600;
        }

        .navbar a.logout:hover {
            opacity: 0.95
        }

        .container-fluid {
            margin-top: 20px;
        }

        .sidebar {
            background: var(--panel);
            border-radius: var(--radius);
            padding: 16px;
            box-shadow: 0 6px 18px rgba(16, 24, 40, 0.04);
            height: fit-content;
        }

        .sidebar h3 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: var(--muted);
        }

        .nav-list {
            list-style: none;
            padding: 0;
            margin: 12px 0 0 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav-list a {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 8px 10px;
            color: #374151;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
        }

        .nav-list a:hover {
            background: #f3f4f6
        }

        .nav-list a.active {
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
            color: #fff;
            box-shadow: 0 6px 18px rgba(78, 115, 223, 0.12);
        }

        .main {
            min-height: 60vh;
        }

        .page-header {
            margin-bottom: 16px;
        }

        .table-panel {
            overflow: auto;
            background: var(--panel);
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 6px 18px rgba(16, 24, 40, 0.04);
        }

        .mimo-btn {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: 0.2s;
            border: none;
        }

        .mimo-btn-primary {
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
            color: #fff;
        }

        .mimo-btn-primary:hover {
            opacity: 0.9;
        }

        .mimo-btn-danger {
            background: var(--danger);
            color: #fff;
        }

        .mimo-btn-danger:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
