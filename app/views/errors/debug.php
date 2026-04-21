<!DOCTYPE html>
<html>

<head>
    <title>Debug</title>
    <style>
        body {
            background: #1e1e1e;
            color: #fff;
            font-family: Consolas, monospace;
            padding: 20px;
        }

        .box {
            background: #2d2d2d;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .title {
            color: #ff6b6b;
            font-size: 22px;
            margin-bottom: 10px;
        }

        .info p {
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #444;
            padding: 8px;
            text-align: left;
            font-size: 13px;
        }

        th {
            background: #333;
        }

        tr:nth-child(even) {
            background: #2a2a2a;
        }

        .file {
            color: #00d4ff;
        }

        .line {
            color: #ffd166;
        }
    </style>
</head>

<body>

    <div class="box">
        <div class="title">⚠️ Exception Debug</div>

        <div class="info">
            <p><strong>Message:</strong> <?= $e->getMessage(); ?></p>
            <p><strong>Code:</strong> <?= $e->getCode(); ?></p>
            <p><strong>File:</strong> <span class="file"><?= $e->getFile(); ?></span></p>
            <p><strong>Line:</strong> <span class="line"><?= $e->getLine(); ?></span></p>
        </div>
    </div>

    <div class="box">
        <div class="title">📍 Stack Trace</div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Function</th>
                    <th>File</th>
                    <th>Line</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($e->getTrace() as $index => $trace): ?>
                    <tr>
                        <td><?= $index ?></td>

                        <td>
                            <?= isset($trace['class']) ? $trace['class'] . '::' : '' ?>
                            <?= $trace['function'] ?? '' ?>
                        </td>

                        <td class="file">
                            <?= $trace['file'] ?? 'N/A' ?>
                        </td>

                        <td class="line">
                            <?= $trace['line'] ?? '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</body>

</html>