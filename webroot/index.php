<html>
<head>
<style type="text/css">
.board {
    display: inline-block;
    border: 1px solid #000;
    position: relative;
}
.col {
    display: inline-block;
    position: relative;
    border: 1px solid #000;
    width: 20px;
    height: 20px;
}
.spawn {
    position: absolute;
    width: 20px;
    height: 18px;
    padding-top: 2px;
    text-align: center;
    font-weight: bold;
    font-family: sans-serif;
    font-size: 12px;
}
.spawn.creep {
    background: rgba(255,0,0,0.5);
}
.spawn.tower {
    background: rgba(0,0,255,0.5);
}
.spawn.base {
    background: rgba(0,255,0,0.5);
}
.shot {
    font-family: monospace;
    font-weight: bold;
    font-size: 18px;
    color: rgba(0,0,0,0.5);
    height: 20px;
    width: 20px;
    margin-top: -11px;
    margin-left: -10px;
    text-align: center;
    position: absolute;
}
</style>
<script type="text/javascript" src="jquery.min.js"></script>
<script type="text/javascript">
var Board;
$(function() {

    var boardNumRows = 30;
    var boardNumCols = 13;

    $('body').append(function() {
        var board = "<div class='board'>";
        for (var rowNum = 1; rowNum <= boardNumRows; rowNum++) {
            board += "<div class='row'>";
            for (var colNum = 1; colNum <= boardNumCols; colNum++) {
                board += "<div class='col'></div>";
            }
            board += "</div>";
        }
        board += "</div>";
        return board;
    });

    var shot;
    (function() {
        var shotId = 0;
        shot = function(startCords, endCords) {
            this.currentCords = {
                x: startCords.x,
                y: startCords.y
            };
            this.endCords = {
                x: endCords.x,
                y: endCords.y
            };
            shotId++;
            $('.board').append("<div class='shot' id='shot" + shotId + "'>o</div>");
            this.element = $('#shot' + shotId);
            this.setPosition(true);
        };
        shot.prototype.convertToPos = function(cords) {
            return {
                top: cords.y * 22 - 10,
                left: cords.x * 22 - 10
            };
        };
        shot.prototype.setPosition = function(noAnimate) {
            var time = noAnimate ? 0 : 500;
            var pos = this.convertToPos(this.currentCords);
            this.element.stop(true,false).animate({
                top: pos.top,
                left: pos.left
            }, time, "linear");
        };
        shot.prototype.move = function() {
            var maxMove = 5;
            var distance = Board.calcDistance(this.currentCords, this.endCords);
            if (distance < 1) {
                this.element.remove();
                return false;
            }
            var percent = maxMove / distance;
            var distanceX = this.endCords.x - this.currentCords.x;
            var distanceY = this.endCords.y - this.currentCords.y;
            if (percent < 1  && percent > 0) {
                distanceX *= percent;
                distanceY *= percent;
            }
            this.currentCords.x += distanceX;
            this.currentCords.y += distanceY;
            this.setPosition();
            return true;
        };
    })();

    var spawn = function(cords) {
        if (cords != null) {
            this.cords = cords;
            this.getCell().html(this.getCellHtml());
        }
    };
    spawn.prototype.getCell = function() {
        var colNum = this.cords.x - 1;
        var rowNum = this.cords.y - 1;
        return $('.row:eq(' + rowNum + ') .col:eq(' + colNum + ')');
    };
    spawn.prototype.getCellHtml = function() {
        return "<div class='spawn " + this.getClassName() + "'>" + this.getCellText() + "</div>";
    };
    spawn.prototype.getClassName = function() {return "spawn";};
    spawn.prototype.getCellText = function() {return "";};
    spawn.prototype.setPosition = function(cords) {
        this.getCell().html("");
        this.cords = cords;
        this.getCell().html(this.getCellHtml());
    };

    var creep = function() {
        this.health = 100;
        spawn.apply(this, arguments);
    };
    creep.prototype = new spawn();
    creep.prototype.getClassName = function() {return "creep";};
    creep.prototype.getCellText = function() {return this.health;};

    var tower = function() {spawn.apply(this, arguments);};
    tower.prototype = new spawn();
    tower.prototype.getClassName = function() {return "tower";};

    var base = function() {spawn.apply(this, arguments);};
    base.prototype = new spawn();
    base.prototype.getClassName = function() {return "base";};

    var spawnPoint = function() {spawn.apply(this, arguments);};
    spawnPoint.prototype = new spawn();
    spawnPoint.prototype.getClassName = function() {return "spawnPoint";};

    creep.prototype.findNextMove = function(shortestPaths) {
        var shortestPath = shortestPaths.find(this.cords);
        if (shortestPath && shortestPath.parent) {
            return shortestPath.parent.cords;
        }
        return this.cords;
    };

    var cell = function(cords) {
        this.cords = cords;
    };
    var count = 0;
    cell.prototype.setParent = function(parent, openCells) {
        if (parent == null) {
            this.parent = "home";
            this.distance = 0;
        }
        else {
            var distance = parent.getDistance() + 1;
            var currentDistance = this.getDistance();
            if (currentDistance == null || distance < currentDistance) {
                this.parent = parent;
            }
        }
        return this;
    };
    cell.prototype.getDistance = function() {
        if (this.parent == "home") {
            return 0;
        }
        else if (this.parent == null) {
            return 1000;
        }
        else {
            return this.parent.getDistance() + 1;
        }
    };
    cell.prototype.getAdjacentCords = function(openCells) {
        var adjacentCords = [];
        var cords;
        cords = {x: this.cords.x - 1, y: this.cords.y};
        if (Board.checkCellNotBlocked(cords)) adjacentCords.push(cords);
        cords = {x: this.cords.x + 1, y: this.cords.y};
        if (Board.checkCellNotBlocked(cords)) adjacentCords.push(cords);
        cords = {x: this.cords.x, y: this.cords.y - 1};
        if (Board.checkCellNotBlocked(cords)) adjacentCords.push(cords);
        cords = {x: this.cords.x, y: this.cords.y + 1};
        if (Board.checkCellNotBlocked(cords)) adjacentCords.push(cords);
        for (var i = 0; i < adjacentCords.length; i++) {
            openCells.find(adjacentCords[i]).setParent(this, openCells);
        }
        return adjacentCords;
    };

    Board = {
        Towers: [],
        Creeps: [],
        Bases: [],
        SpawnPoints: [],
        Shots: [],
        addTower: function(cords) {
            if (!this.checkCellEmpty(cords)) {
                return false;
            }
            this.Towers.push(new tower(cords));
            return true;
        },
        addCreep: function(cords) {
            if (!this.checkCellNotBlocked(cords)) {
                return false;
            }
            this.Creeps.push(new creep(cords));
            return true;
        },
        addBase: function(cords) {
            if (!this.checkCellEmpty(cords)) {
                return false;
            }
            this.Bases.push(new base(cords));
            return true;
        },
        addSpawnPoint: function(cords) {
            if (!this.checkCellEmpty(cords)) {
                return false;
            }
            this.SpawnPoints.push(new spawnPoint(cords));
            return true;
        },
        checkCellEmpty: function(cords) {
            var usedCells = this.getAllUsedCells();
            if (cords.x <= 0 || cords.y <= 0 || cords.x > boardNumCols || cords.y > boardNumRows) {
                return false;
            }
            for (i = 0; i < usedCells.length; i++) {
                if (cords.x == usedCells[i].x && cords.y == usedCells[i].y) {
                    return false;
                }
            }
            return true;
        },
        checkCellNotBlocked: function(cords) {
            var usedCells = this.getAllTowerCells();
            if (cords.x <= 0 || cords.y <= 0 || cords.x > boardNumCols || cords.y > boardNumRows) {
                return false;
            }
            for (i = 0; i < usedCells.length; i++) {
                if (cords.x == usedCells[i].x && cords.y == usedCells[i].y) {
                    return false;
                }
            }
            return true;
        },
        checkIsCreep: function(cords) {
            for (i = 0; i < this.Creeps.length; i++) {
                if (cords.x == this.Creeps[i].cords.x && cords.y == this.Creeps[i].cords.y) {
                    return true;
                }
            }
            return false;
        },
        checkIsBase: function(cords) {
            for (i = 0; i < this.Bases.length; i++) {
                if (cords.x == this.Bases[i].cords.x && cords.y == this.Bases[i].cords.y) {
                    return true;
                }
            }
            return false;
        },
        getAllUsedCells: function() {
            var cells = [], i;
            for (i = 0; i < this.Towers.length; i++) {
                cells.push(this.Towers[i].cords);
            }
            for (i = 0; i < this.Bases.length; i++) {
                cells.push(this.Bases[i].cords);
            }
            for (i = 0; i < this.SpawnPoints.length; i++) {
                cells.push(this.SpawnPoints[i].cords);
            }
            return cells;
        },
        getAllTowerCells: function() {
            var cells = [], i;
            for (i = 0; i < this.Towers.length; i++) {
                cells.push(this.Towers[i].cords);
            }
            return cells;
        },
        getAllOpenCells: function() {
            var openCells = [], x, y;
            var usedCords = Board.getAllTowerCells();
            for (x = 1; x <= boardNumCols; x++) {
                loop: for (y = 1; y <= boardNumRows; y++) {
                    for (i = 0; i < usedCords.length; i++) {
                        if (usedCords[i].x == x && usedCords[i].y == y) {
                            continue loop;
                        }
                    }
                    openCells.push(new cell({x: x, y: y}));
                }
            }
            return openCells;
        },
        openCells: {
            cells: [],
            find: function(cords) {
                for (var i = 0; i < this.cells.length; i++) {
                    if (this.cells[i].cords.x == cords.x && this.cells[i].cords.y == cords.y) {
                        return this.cells[i];
                    }
                }
                return false;
            }
        },
        setShortestPaths: function() {
            this.openCells.cells = Board.getAllOpenCells();
            var i;
            for (i = 0; i < this.Bases.length; i++) {
                this.openCells.find(this.Bases[i].cords).setParent();
            }
            for (var startObj = 0, endObj = 1; startObj != endObj; startObj = endObj, endObj = JSON.stringify(this.openCells)) {
                for (i = 0; i < this.openCells.cells.length; i++) {
                    this.openCells.cells[i].getAdjacentCords(this.openCells);
                }
            }
            for (i = 0; i < this.openCells.cells.length; i++) {
                if (this.openCells.cells[i].parent == null) {
                    alert("Error! Unable to find path.");
                    return;
                }
            }
        },
        creepsRemaining: 50,
        lives: 50,
        doMove: function() {
            for (var i = 0; i < this.Creeps.length; i++) {
                var nextMove = this.Creeps[i].findNextMove(this.openCells);
                if (this.checkIsBase(nextMove)) {
                    this.lives--;
                    console.log("Life lost. " + this.lives + " lives left.");
                    this.Creeps[i].getCell().html("");
                    this.Creeps.splice(i--,1);
                }
                else if (this.Creeps[i].health <= 0) {
                    this.Creeps[i].getCell().html("");
                    this.Creeps.splice(i--,1);
                }
                else {
                    if (!this.checkIsCreep(nextMove)) {
                        this.Creeps[i].setPosition(nextMove);
                    }
                }
            }
            if (this.creepsRemaining > 0) {
                for (i = 0; i < this.SpawnPoints.length; i++) {
                    if (!this.checkIsCreep(this.SpawnPoints[i].cords)) {
                        Board.addCreep(this.SpawnPoints[i].cords);
                        this.creepsRemaining--;
                    }
                }
            }
        },
        doAttack: function() {
            var i, g, toAttack, toAttackArray, distance;
            var maxDistance = 5;
            for (i = 0; i < this.Towers.length; i++) {
                toAttackArray = [];
                for (g = 0; g < this.Creeps.length; g++) {
                    distance = this.calcDistance(this.Towers[i].cords, this.Creeps[g].cords);
                    if (distance < maxDistance && this.Creeps[g].health > 0) {
                        toAttackArray.push({
                            id: g,
                            creep: this.Creeps[g],
                            distance: distance
                        });
                    }
                }
                toAttack = null;
                for (g = 0; g < toAttackArray.length; g++) {
                    if (toAttack == null || toAttackArray[g].distance < toAttack.distance) {
                        toAttack = toAttackArray[g];
                    }
                }
                if (toAttack != null) {
                    toAttack.creep.health -= 5;
                    Board.addShot(this.Towers[i].cords, toAttack.creep.cords);
                }
            }
        },
        addShot: function(startCords, endCords) {
            this.Shots.push(new shot(startCords, endCords));
        },
        moveShots: function() {
            for (var i = 0; i < this.Shots.length; i++) {
                if (!this.Shots[i].move()) {
                    this.Shots.splice(i--,1);
                }
            }
        },
        calcDistance: function(cords1, cords2) {
            var distanceX = Math.abs(cords1.x - cords2.x);
            var distanceY = Math.abs(cords1.y - cords2.y);
            return Math.sqrt(Math.pow(distanceX, 2) + Math.pow(distanceY, 2));
        }
    };

    Board.addSpawnPoint({x:7, y: 1});
    Board.addBase({x:7, y: 30});

    Board.addTower({x:1, y:10});
    Board.addTower({x:2, y:10});
    Board.addTower({x:3, y:10});
    Board.addTower({x:4, y:10});
    Board.addTower({x:5, y:10});
    Board.addTower({x:6, y:10});
    Board.addTower({x:7, y:10});
    Board.addTower({x:8, y:10});
    Board.addTower({x:9, y:10});
    Board.addTower({x:10, y:10});
    Board.addTower({x:11, y:10});

    Board.addTower({x:3, y:12});
    Board.addTower({x:4, y:12});
    Board.addTower({x:5, y:12});
    Board.addTower({x:6, y:12});
    Board.addTower({x:7, y:12});
    Board.addTower({x:8, y:12});
    Board.addTower({x:9, y:12});
    Board.addTower({x:10, y:12});
    Board.addTower({x:11, y:12});
    Board.addTower({x:12, y:12});
    Board.addTower({x:13, y:12});

    Board.addTower({x:1, y:14});
    Board.addTower({x:2, y:14});
    Board.addTower({x:3, y:14});
    Board.addTower({x:4, y:14});
    Board.addTower({x:5, y:14});
    Board.addTower({x:6, y:14});
    Board.addTower({x:7, y:14});
    Board.addTower({x:8, y:14});
    Board.addTower({x:9, y:14});
    Board.addTower({x:10, y:14});
    Board.addTower({x:11, y:14});

    Board.setShortestPaths();

    setInterval(function() {Board.doMove();}, 400);
    setInterval(function() {Board.doAttack();}, 1000);
    setInterval(function() {Board.moveShots();}, 500);

});
</script>
</head>
<body></body>
</html>