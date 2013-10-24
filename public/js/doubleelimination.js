$( document ).ready(function() {
    var canvas = document.getElementById('doubleElminiationBracket');
    var stage = new createjs.Stage(canvas);
    
    
    var shape = new createjs.Shape();
    shape.graphics.beginFill('rgba(255,0,0,1)').drawRoundRect(0, 0, 120, 120, 10);
    stage.addChild(shape);
    stage.update();
});