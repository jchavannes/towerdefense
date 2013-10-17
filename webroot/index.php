<html>
<head>
    <link rel="stylesheet" type="text/css" href="style.css" />
    <script type="text/javascript" src="jquery.min.js"></script>
    <script type="text/javascript" src="td.js"></script>
    <script type="text/javascript">
        <? $file = "local.js"; if (file_exists($file)) include($file); ?>
    </script>
</head>
<body>
<div class='board'></div>
<div class='control'>
    <input type='button' id='addTower' value='Add Tower' />
</div>
<div class='console'>
    Console!
</div>
</body>
</html>