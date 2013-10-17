var Board, Timeout;
$(function() {

    var boardNumRows = 30;
    var boardNumCols = 13;

    $('.board').append(function() {
        var board = "";
        for (var rowNum = 1; rowNum <= boardNumRows; rowNum++) {
            board += "<div class='row'>";
            for (var colNum = 1; colNum <= boardNumCols; colNum++) {
                board += "<div class='col'></div>";
            }
            board += "</div>";
        }
        return board;
    });
    $('.col').click(function(e) {
        if (!e.shiftKey) {
            $('.col').removeClass('selected');
        }
        if ($(this).hasClass('selected')) {
            $(this).removeClass('selected');
        }
        else {
            $(this).addClass('selected');
        }
    });
    $('#addTower').click(function() {
        var $cols = $('.col.selected');
        $cols.each(function() {
            $col = $(this);
            var rowId = $('.row').index($col.parents('.row'));
            var colId = $('.col').index($col) - (rowId * boardNumCols);
            var cords = {x:colId + 1, y:rowId + 1};
            if (Board.checkCellEmpty(cords)) {
                Board.addTower(cords);
            }
        });
    });
    $('#removeTower').click(function() {
        var $cols = $('.col.selected');
        $cols.each(function() {
            $col = $(this);
            var rowId = $('.row').index($col.parents('.row'));
            var colId = $('.col').index($col) - (rowId * boardNumCols);
            var cords = {x:colId + 1, y:rowId + 1};
            for (var i = 0; i < Board.Towers.length; i++) {
                if (cords.x == Board.Towers[i].cords.x && cords.y == Board.Towers[i].cords.y) {
                    Board.Towers[i].remove("");
                    Board.resetShortestPaths = true;
                    Board.Towers.splice(i--, 1);
                }
            }
        });
    });

    var Console = {
        getEle: function() {
            if (!this.$ele) {
                this.$ele = $('.console');
            }
            return this.$ele;
        },
        pad: function(n) {
            return (n < 10) ? '0' + n : n;
        },
        add: function(msg, noTS) {
            if (!noTS) {
                var d = new Date();
                var hours = d.getHours();
                hours = this.pad(hours > 12 ? hours - 12 : hours != 0 ? hours : 12);
                var minutes = this.pad(d.getMinutes());
                var seconds = this.pad(d.getSeconds());
                var timestamp = "[<i>" + hours + ":" + minutes + ":" + seconds + "</i>] ";
                msg = timestamp + msg + "<br/>";
            }
            this.getEle().append(msg).scrollTop(this.getEle()[0].scrollHeight);
        }
    };

    var shot;
    (function() {
        var shotId = 0;
        shot = function(startCords, endCords) {
            $('.board').append("<div class='shot' id='shot" + shotId + "'>o</div>");
            this.element = $('#shot' + shotId++);
            this.currentCords = startCords;
            this.setPosition(true);
            this.currentCords = endCords;
            this.setPosition();
        };
        shot.prototype.convertToPos = function(cords) {
            var cellSize = 25;
            return {
                top: cords.y * (cellSize + 2) - 10,
                left: cords.x * (cellSize + 2) - 10
            };
        };
        shot.prototype.setPosition = function(noAnimate) {
            var time = noAnimate ? 0 : 500;
            var pos = this.convertToPos(this.currentCords);
            var $ele = this.element;
            $ele.stop(true,false).animate({top:pos.top, left:pos.left}, time, "linear", function() {
                if (!noAnimate) $ele.remove();
            });
        };
    })();

    var spawn = function(cords) {
        if (cords != null) {
            this.cords = cords;
            this.getCell().html(this.getCellHtml());
        }
    };
    spawn.prototype.getCell = function() {
        return Board.allCells.find(this.cords).getEle();
        if (this.$ele == null) {
            var colNum = this.cords.x - 1;
            var rowNum = this.cords.y - 1;
            this.$ele = $('.row:eq(' + rowNum + ') .col:eq(' + colNum + ')');
        }
        return this.$ele;
    };
    spawn.prototype.getCellHtml = function() {
        return "<div class='spawn " + this.getClassName() + "'>" + this.getCellText() + "</div>";
    };
    spawn.prototype.getClassName = function() {return "spawn";};
    spawn.prototype.getCellText = function() {return "";};
    spawn.prototype.setPosition = function(cords) {
        var $cell = this.getCell();
        var hasSelected = false;
        $cell.html("");
        this.checkForSpawnPoint();
        if ($cell.hasClass('selected')) {
            hasSelected = true;
            $cell.removeClass('selected');
        }
        this.cords = cords;
        $cell = this.getCell();
        $cell.html(this.getCellHtml());
        if ($cell.hasClass('selected')) {
            $cell.removeClass('selected');
        }
        if (hasSelected) {
            $cell.addClass('selected');
        }
    };
    spawn.prototype.remove = function() {
        var $cell = this.getCell();
        $cell.html("");
        this.checkForSpawnPoint();
    };
    spawn.prototype.checkForSpawnPoint = function() {
        var $cell = this.getCell();
        for (var i = 0; i < Board.SpawnPoints.length; i++) {
            if (this.cords.x == Board.SpawnPoints[i].cords.x && this.cords.y == Board.SpawnPoints[i].cords.y) {
                $cell.html(Board.SpawnPoints[i].getCellHtml());
            }
        }
    };

    var creep = function(cords, health) {
        this.health = health;
        spawn.apply(this, [cords]);
    };
    creep.prototype = new spawn();
    creep.prototype.getClassName = function() {return "creep";};
    creep.prototype.getCellText = function() {
        return parseInt(this.health);
    };

    var tower = function() {
        this.level = 1;
        spawn.apply(this, arguments);
    };
    tower.prototype = new spawn();
    tower.prototype.getClassName = function() {return "tower";};
    tower.prototype.getCellText = function() {return this.level;};

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
    cell.prototype.getEle = function() {
        if (this.$ele == null) {
            var colNum = this.cords.x - 1;
            var rowNum = this.cords.y - 1;
            this.$ele = $('.row:eq(' + rowNum + ') .col:eq(' + colNum + ')');
        }
        return this.$ele;
    };
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
            return 10000;
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
        resetShortestPaths: true,
        currentLevel: 1,
        liveGame: true,
        addTower: function(cords) {
            if (!this.liveGame) return false;
            if (!this.checkCellEmpty(cords)) {
                return false;
            }
            this.Towers.push(new tower(cords));
            this.resetShortestPaths = true;
            return true;
        },
        addCreep: function(cords) {
            if (!this.checkCellNotBlocked(cords)) {
                return false;
            }
            var newCreep = new creep(cords, this.currentLevel);
            this.Creeps.push(newCreep);
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
            for (i = 0; i < this.Creeps.length; i++) {
                cells.push(this.Creeps[i].cords);
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
        getAllCells: function() {
            var cells = [], x, y;
            for (x = 1; x <= boardNumCols; x++) {
                loop: for (y = 1; y <= boardNumRows; y++) {
                    cells.push(new cell({x: x, y: y}));
                }
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
        allCells: {
            cells: [],
            find: function() {
                if (this.cells.length == 0) {
                    this.cells = Board.getAllCells();
                }
                return Board.openCells.find.apply(this, arguments);
            }
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
                    return;
                }
            }
        },
        getShortestPaths: function() {
            if (this.resetShortestPaths) {
                this.setShortestPaths();
                this.resetShortestPaths = false;
            }
            return this.openCells;
        },
        creepsRemaining: 0,
        lives: 50,
        doMove: function() {
            var shortestPaths = this.getShortestPaths();
            for (var i = 0; i < this.Creeps.length; i++) {
                var nextMove = this.Creeps[i].findNextMove(shortestPaths);
                if (this.checkIsBase(nextMove)) {
                    this.lives--;
                    Console.add("Life lost. " + this.lives + " lives left.");
                    this.Creeps[i].remove();
                    this.Creeps.splice(i--,1);
                }
                else if (this.Creeps[i].health <= 0) {
                    this.Creeps[i].remove();
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
            if (this.lives <= 0) {
                Timeout.stop();
            }
        },
        doAttack: function() {
            var i, g, toAttack, toAttackArray, distance;
            var maxDistance = 5;
            var randAdd = parseInt(Math.random() * 10);
            for (i = 0; i < this.Towers.length; i++) {
                if ((i + randAdd) % 10 != 0) continue;
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
                    toAttack.creep.health -= this.Towers[i].level;
                    Board.addShot(this.Towers[i].cords, toAttack.creep.cords);
                }
            }
        },
        addShot: function(startCords, endCords) {
            new shot(startCords, endCords);
        },
        calcDistance: function(cords1, cords2) {
            var distanceX = Math.abs(cords1.x - cords2.x);
            var distanceY = Math.abs(cords1.y - cords2.y);
            return Math.sqrt(Math.pow(distanceX, 2) + Math.pow(distanceY, 2));
        },
        nextLevel: function() {
            this.currentLevel++;
            this.creepsRemaining = 15;
            this.logLevel();
        },
        logLevel: function() {
            if (this.currentLevel % 100 == 0) {
                Console.add("Congratulations! You made it to level " + this.currentLevel + "!");
            }
            Console.add("Starting level " + this.currentLevel + ". " + this.creepsRemaining + " creeps...");
        }
    };

    Board.addSpawnPoint({x:7, y: 1});
    Board.addBase({x:7, y: 30});

    Timeout = {
        dos: [],
        curDo: 0,
        started: false,
        do: function() {
            this.curDo++;
            for (var i = 0; i < this.dos.length; i++) {
                if (this.curDo % this.dos[i].mod == 0) {
                    this.dos[i].func();
                }
            }
        },
        add: function(func, mod) {
            this.dos.push({func: func, mod: mod});
        },
        stop: function() {
            Board.liveGame = false;
            clearInterval(this.interval);
            Console.add("Game over. No lives left.");
        },
        start: function() {
            $('#startGame').hide();
            Board.creepsRemaining = 15;
            Board.currentLevel = 1;
            var self = this;
            this.interval = setInterval(function() {self.do();}, 50);
            Board.logLevel();
        }
    };

    Timeout.add(function() {Board.nextLevel();}, 200);
    Timeout.add(function() {Board.doMove();}, 3);
    Timeout.add(function() {Board.doAttack();}, 2);

    Console.add(
        "<h3>Welcome to WebTD</h3>" +
        "<p>Build towers to protect your base from creeps. Click the 'Start Game' button when you are ready to begin.</p>" +
        "<p><i>Tip: Hold shift to select multiple cells.</i></p>"
    , true);

});
