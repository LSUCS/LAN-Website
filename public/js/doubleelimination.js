$( document ).ready(function() {
    tournament = new Tournament('doubleEliminationBracket',10,10);          
    tournament.make_tournament(matches);
    tournament.draw();

    $('#tabs-2').css('overflow', 'auto');
});

function Tournament(canvas_id,x,y) {
    this.stage = new createjs.Stage(canvas_id);
    this.canvas_id = canvas_id;
    this.x = x;
    this.y = y;
    this.blockHeight = 36;
    this.blockWidth = 200;
    this.blockXMargin = 20;
    this.blockYMargin = 20;
    this.winners = [];
    this.losers = [];
    this.canvasWidth;
    this.canvasHeight;
    this.verticalLineWidth = 2;
    this.blockMainBgColour = "rgba(187,187,187,1)";
    this.blockLeftBgColour = "rgba(150,150,150,1)";
    this.textColor = "#000000";
    this.winnersTextColor = "#DD8112";
    this.get_brackets = function(matches){
        var rounds = {};
        for(var i=0;i<matches.length;i++){
            if(rounds[matches[i].round]==undefined){
                rounds[matches[i].round] = [matches[i]];
            } else {
                rounds[matches[i].round].push(matches[i]);
            }
        }
        var winners = [], losers = [];
        for(var round in rounds){
            if(round>0){
                winners.push(rounds[round]);
            } else {
                losers.push(rounds[round])
            }
        }
        losers.reverse();
        return [winners,losers];
    }
    this.set_canvas_size = function(){
        var canvasWidth = 0;
        var c = document.getElementById(this.canvas_id);
        var roughHeight = (this.winners[0].length*this.blockHeight)+(this.winners.length*this.blockHeight)+(this.winners[1].length*this.blockYMargin)+((this.winners[0].length-1)*this.blockYMargin)+(this.losers[0].length*this.blockHeight)+((this.losers[0].length-1)*this.blockYMargin);
        if(this.winners[1]>this.winners[0]){
            roughHeight = (this.winners[1].length*this.blockHeight)+(this.winners.length*this.blockHeight)+(this.winners[0].length*this.blockYMargin)+((this.winners[1].length-1)*this.blockYMargin)+(this.losers[0].length*this.blockHeight)+((this.losers[0].length-1)*this.blockYMargin);
        }
        if(Math.abs(this.losers.length-(this.winners.length-2))!=0){
            canvasWidth += ((this.blockWidth+this.blockXMargin)*(Math.abs(this.losers.length-this.winners.length)));
        }
        c.width=((this.winners.length)*this.blockWidth)+(this.winners.length*this.blockXMargin)+canvasWidth;
        c.height= roughHeight;
        this.canvasWidth = c.width;
        this.canvasHeight = c.height;
    }
    this.draw_text = function(x,y,text,textSize,textFont,textAlign,textBaseline,textColor){
        var label = new createjs.Text(String(text), textSize+"px "+textFont, textColor);
        label.x = x;
        label.y = y+(textSize/3);
        label.textBaseline = textBaseline;
        label.textAlign = textAlign;
        this.stage.addChild(label);  
    }
    this.draw_match = function(x,y,team1,team2,team1_number,team2_number,winner){
        //Define text settings
        var textBaseline = "alphabetic",
            textSize = (1/3)*this.blockHeight,
            textFont = "Arial";
        //create right block
        var rightBlock = new createjs.Shape(),
            leftBlock = new createjs.Shape();
        //Define block settings
        var blockRounding = 8;
        rightBlock.graphics.beginFill(this.blockMainBgColour).rc(x-1+(0.15*this.blockWidth), y, (0.85*this.blockWidth), this.blockHeight, 0,blockRounding,blockRounding,0);
        leftBlock.graphics.beginFill(this.blockLeftBgColour).rc(x, y, (0.15*this.blockWidth), this.blockHeight, blockRounding,0,0,blockRounding);
        this.stage.addChild(rightBlock);
        this.stage.addChild(leftBlock);
        //Text
        var team1TextColor = this.textColor,
            team2TextColor = this.textColor;
        if(winner==1){
            team1TextColor = this.winnersTextColor;
        }
        if(winner==2){
            team2TextColor = this.winnersTextColor;
        }
        this.draw_text(x+(0.175*this.blockWidth),y+((this.blockHeight/4)),team1,textSize, textFont,"left",textBaseline,team1TextColor);
        this.draw_text(x+(0.175*this.blockWidth),y+((3*this.blockHeight)/4),team2,textSize, textFont,"left",textBaseline,team2TextColor);
        this.draw_text(x+(0.075*this.blockWidth),y+((this.blockHeight/4)),team1_number,textSize, textFont,"center",textBaseline,team1TextColor);
        this.draw_text(x+(0.075*this.blockWidth),y+((3*this.blockHeight)/4),team2_number,textSize, textFont,"center",textBaseline,team2TextColor);
    }
    this.draw_horizontal_divider = function(x,y,is_first,is_last,are_winners,is_bye){
        var centerLine = new createjs.Shape(),
            leftOffset = 0,
            rightOffset = -1;
        if(!is_first && !is_bye){
            leftOffset = -(this.blockXMargin/2);
        }
        if(!is_last||!are_winners){
            rightOffset = (this.blockXMargin/2);
        }
        centerLine.graphics.ss(1).s("rgba(0,0,0,1)").mt(x+leftOffset, y+(this.blockHeight/2)).lt(x+this.blockWidth+rightOffset, y+(this.blockHeight/2)).es();
        this.stage.addChild(centerLine);
    }
    this.draw_vertical_connector = function(x,y,previousMatchY,previousRoundLength){
        var connectorLine = new createjs.Shape(),
            rightOffset = (this.blockXMargin/2)-1;
        connectorLine.graphics.ss(this.verticalLineWidth).s("rgba(0,0,0,1)").mt(x+this.blockWidth+rightOffset+(this.verticalLineWidth/2), y+1+(this.blockHeight/2)).lt(x+this.blockWidth+rightOffset+(this.verticalLineWidth/2), previousMatchY-1+(this.blockHeight/2)).es();
        this.stage.addChild(connectorLine);        
    }
    this.draw_bracket = function(data,x,y,are_winners){
        var previousRound = {};
        var finalMatch = {};
        if(this.losers.length!=(this.winners.length-2)){
            if(this.losers.length>this.winners.length&&!are_winners){
                x =  x+((this.blockWidth+this.blockXMargin)*(this.losers.length-this.winners.length));
            }
            if(this.losers.length<this.winners.length&&are_winners){
                x =  x+((this.blockWidth+this.blockXMargin)*(this.winners.length-this.losers.length));
            }
        }
        for(var round=0;round<data.length;round++){
            var roundData = data[round],
                roundMatchMargin = 0,
                previousMatchY = 0;       
            for(var match=0;match<roundData.length;match++){
                var matchX = x+(this.blockWidth*round),
                    matchY = y+(this.blockHeight*match)+(match*this.blockYMargin),
                    is_bye = false;
                if(match==0&&previousRound.roundY!=undefined){
                    matchY = previousRound.roundY+(this.blockHeight*match)+(match*this.blockYMargin);
                }
                if(round>0){
                    roundMatchMargin = ((data[round-1].length-data[round].length)/2)*this.blockHeight;
                }
                if(round>0&&match!=0&&data[round-1].length>data[round].length){
                    matchY += roundMatchMargin;
                }
                var boundingBoxHeight = (this.blockHeight*roundData.length)+(this.blockYMargin*(roundData.length-1))+((roundData.length-1)*roundMatchMargin);
                if(round>0&&data[round-1].length>1){
                    matchY += (previousRound.boundingBoxHeight/2) - (boundingBoxHeight/2);
                }
                if(round>0&&data[round-1].length<=data[round].length){
                    matchY += ((data[round].length-data[round-1].length)+1)*((this.blockHeight+this.blockYMargin)/2);
                }
                if(round>0&&data[round-1].length==1&&data[round-1].length<data[round].length){
                    matchY -= ((data[round].length-data[round-1].length)+1)*((this.blockHeight+this.blockYMargin)/2);
                }
                if(round>0&&data[round-1].length==1&&data[round].length==1){
                    matchY = previousRound.roundY;
                }
                if(round>0&&data[round-1].length>data[round].length&&data[round-1].length%2==1&&match==(data[round].length-1)){
                    matchY += ((this.blockHeight)/2)-((this.blockHeight)/22);
                }
                 if(round>(data.length-3)&&are_winners){
                    matchY = (this.canvasHeight/2)-(this.blockHeight/2);
                }
                if(((round==data.length-3)&&are_winners)||(!are_winners&&(round==data.length-1))){
                    finalMatch.x = matchX;
                    finalMatch.y = matchY;
                }
                if(match==0){
                    previousRound.roundX = matchX;
                    previousRound.roundY = matchY;                
                }
                if(round>0&&data[round-1].length<=data[round].length){
                    if((match)>=data[round-1].length||(round>0&&data[round].length>1&&data[round].length==data[round-1].length&&match==(data[round].length-1))){
                        is_bye = true;
                    }
                }
                this.draw_match(matchX,matchY,roundData[match].player1.name,roundData[match].player2.name,round,match,roundData[match].winner);
                this.draw_horizontal_divider(matchX,matchY,(round==0),(round==(data.length-1)),are_winners,is_bye);
                if(match%2==1){
                    var previousRoundLength = 0;
                    if(round>0){
                        previousRoundLength = data[round-1].length;
                    }
                    this.draw_vertical_connector(matchX,matchY,previousMatchY,previousRoundLength);
                }
                previousMatchY = matchY;
            }
            previousRound.boundingBoxHeight = (this.blockHeight*roundData.length)+(this.blockYMargin*(roundData.length-1));
            x += this.blockXMargin; 
        }
        return finalMatch;
    }
}
Tournament.prototype.make_tournament = function(matches){
    var brackets = this.get_brackets(matches);
    this.winners = brackets[0];
    this.losers = brackets[1];
    this.set_canvas_size();
    bracketLine = new createjs.Shape();
    winnersBracket = this.draw_bracket(this.winners,this.x,this.y,true);
    losersBracket = this.draw_bracket(this.losers,this.x,this.y+(this.winners.length*this.blockHeight)+(this.winners[0].length*this.blockYMargin),false);
    bracketLine.graphics.ss(this.verticalLineWidth).s("rgba(0,0,0,1)").mt(winnersBracket.x+this.blockWidth+(this.blockXMargin/2), winnersBracket.y+(this.blockHeight/2)-1).lt(losersBracket.x+this.blockWidth+(this.blockXMargin/2), losersBracket.y+(this.blockHeight/2)+1).es();
    this.stage.addChild(bracketLine);
}
Tournament.prototype.draw = function(){
    this.stage.update();
}
Tournament.prototype.clear = function(){
    this.stage.removeAllChildren();
    this.draw();
}