<html>
<head>
    <title>WebTD</title>
    <link rel="stylesheet" type="text/css" href="style.css" />
    <script type="text/javascript" src="jquery.min.js"></script>
    <script type="text/javascript" src="jquery.animate-shadow-min.js"></script>
    <script type="text/javascript" src="td.js"></script>
    <script type="text/javascript">
        <? $file = "local.js"; if (file_exists($file)) include($file); ?>
    </script>
</head>
<body>
<h1>WebTD</h1>
<div class='board'></div>
<div class='control'>
    Gold: <span id="gold">0</span><br/>
    <input type='button' id='addTower' value='[+] Buy/Upgrade Tower' />
    <input type='button' id='removeTower' value='[&ndash;] Sell Tower' />
    <input type='button' id='startGame' onclick='Timeout.start();' value='[&darr;] Start Game' />
    <div class='console'></div>
</div>
</body>
</html>