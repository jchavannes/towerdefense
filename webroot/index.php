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
    <input type='button' id='addTower' value='[+] Buy/Upgrade Tower' /><br/>
    <input type='button' id='removeTower' value='[&ndash;] Sell Tower' /><br/>
    <input type='button' id='startGame' onclick='Timeout.start();' value='[&darr;] Start Game' />
    <div class='console'>
        <h3>Welcome to WebTD &ndash;</h3>
        <p>Build towers to protect your base from creeps. Click the 'Start Game' button when you are ready to begin.</p>
        <p><i>Tip: Hold shift to select multiple cells.</i></p>
    </div>
</div>
</body>
</html>