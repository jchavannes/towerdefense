<html>
<head>
<style type="text/css">
.board {
    display: inline-block;
    border: 1px solid #000;
}
.col {
    display: inline-block;
    border: 1px solid #000;
    width: 20px;
    height: 20px;
}
.spawn {
    width: 20px;
    height: 20px;
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
        return "<div class='spawn " + this.getClassName() + "'></div>";
    };
    spawn.prototype.setPosition = function(cords) {
        this.getCell().html("");
        this.cords = cords;
        this.getCell().html(this.getCellHtml());
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
                this.getAdjacentCords(openCells);
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
        if (Board.checkCellEmpty(cords)) adjacentCords.push(cords);
        cords = {x: this.cords.x + 1, y: this.cords.y};
        if (Board.checkCellEmpty(cords)) adjacentCords.push(cords);
        cords = {x: this.cords.x, y: this.cords.y - 1};
        if (Board.checkCellEmpty(cords)) adjacentCords.push(cords);
        cords = {x: this.cords.x, y: this.cords.y + 1};
        if (Board.checkCellEmpty(cords)) adjacentCords.push(cords);
        for (var i = 0; i < adjacentCords.length; i++) {
            openCells.find(adjacentCords[i]).setParent(this, openCells);
        }
        return adjacentCords;
    };

    var creep = function() {spawn.apply(this, arguments);};
    creep.prototype = new spawn();
    creep.prototype.getClassName = function() {return "creep";};

    var tower = function() {spawn.apply(this, arguments);};
    tower.prototype = new spawn();
    tower.prototype.getClassName = function() {return "tower";};

    var base = function() {spawn.apply(this, arguments);};
    base.prototype = new spawn();
    base.prototype.getClassName = function() {return "base";};

    creep.prototype.findNextMove = function(shortestPaths) {
        var shortestPath = shortestPaths.find(this.cords);
        if (shortestPath && shortestPath.parent) {
            return shortestPath.parent.cords;
        }
        return this.cords;
    };

    Board = {
        Towers: [],
        Creeps: [],
        Bases: [],
        addTower: function(cords) {
            if (!this.checkCellEmpty(cords)) {
                return false;
            }
            this.Towers.push(new tower(cords));
            return true;
        },
        addCreep: function(cords) {
            if (!this.checkCellEmpty(cords)) {
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
        checkCellEmpty: function(cords) {
            var cells = this.getAllUsedCells();
            if (cords.x <= 0 || cords.y <= 0 || cords.x > boardNumCols || cords.y > boardNumRows) {
                return false;
            }
            for (i = 0; i < cells.length; i++) {
                if (cords.x == cells[i].x && cords.y == cells[i].y) {
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
        getAllUsedCells: function() {
            var cells = [], i;
            for (i = 0; i < this.Towers.length; i++) {
                cells.push(this.Towers[i].cords);
            }
            return cells;
        },
        getAllOpenCells: function() {
            var openCells = [], x, y;
            var usedCords = Board.getAllUsedCells();
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
        setShortestPaths: function() {
            this.openCells = {
                cells: Board.getAllOpenCells(),
                find: function(cords) {
                    for (var i = 0; i < this.cells.length; i++) {
                        if (this.cells[i].cords.x == cords.x && this.cells[i].cords.y == cords.y) {
                            return this.cells[i];
                        }
                    }
                    return false;
                }
            };
            this.openCells.find(this.Bases[0].cords).setParent().getAdjacentCords(this.openCells);
        },
        getShortestPaths: function() {
            if (this.openCells == null) {
                this.setShortestPaths();
            }
            return this.openCells;
        },
        doMove: function() {
            var shortestPaths = this.getShortestPaths();
            for (var i = 0; i < this.Creeps.length; i++) {
                var nextMove = this.Creeps[i].findNextMove(shortestPaths);
                if (!(nextMove.x == Board.Bases[0].cords.x && nextMove.y == Board.Bases[0].cords.y)) {
                    if (!this.checkIsCreep(nextMove)) {
                        this.Creeps[i].setPosition(nextMove);
                    }
                }
                else {
                    this.Creeps[i].getCell().html("");
                    this.Creeps.splice(i,1);
                }
            }
        }
    };

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

    Board.addCreep({x:4, y:1});
    Board.addCreep({x:5, y:1});
    Board.addCreep({x:6, y:1});
    Board.addCreep({x:7, y:1});
    Board.addCreep({x:4, y:2});
    Board.addCreep({x:5, y:2});
    Board.addCreep({x:6, y:2});
    Board.addCreep({x:7, y:2});

    setInterval(function() {Board.doMove();}, 500);

});
</script>
</head>
<body></body>
</html>