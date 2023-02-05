

function processListTable(fConfig) {

    this.parentEl = false;
    this.tableEl = false;

    this.headerRowObj = false;
    this.rowsObj = {};


    this.init = function (fParentElSelector) {
        //var parentEl = $(fParentElSelector)[0];
        var parentEl = $('#frame_procl');

        //console.log(['init', parentEl]);

        //parentEl.css("background-color", "red");
        this.parentEl = parentEl;

        this.tableEl = createDomEl("table");

        this.parentEl.append(this.tableEl);
        
        this.createHeader();
    };
    
    this.createHeader = function(){
        
        var config = {
            data: false,
            processListTable: this
        };

        this.headerRowObj = new processListRow(config);
    };

    this.updateRows = function (fProcArr) {
        var procID, procRow, tableRow;

        //console.log(['updateRows', fProcArr]);
        

        for (var key in fProcArr) {
            procRow = fProcArr[key];
            procID = procRow.Id;
            tableRow = this.rowsObj[procID];

            //console.log(['updateRows 2', procRow, procID, tableRow]);

            if (tableRow) {
                tableRow.update(procRow);
            } else {
                var config = {
                    data: procRow,
                    processListTable: this
                };

                tableRow = new processListRow(config);

                this.rowsObj[procID] = tableRow;
            }
        }
    };

    //private
    this.registerRowEl = function (fRowEl) {
        this.tableEl.appendChild(fRowEl);
    };

    this.init(fConfig.parentSelector);
};



function processListRow(fConfig) {
    
    this.processListTable = fConfig.processListTable;
    this.rowEl = false;

    this.cellElObj = {
        Id: false,
        User: false,
        Host: false,
        db: false,
        Command: false,
        Time: false,
        State: false,
        Info: false
    };

    var cellEl, textNode, objVal;
    this.init = function (fData) {
        //console.log(['processListRow.processListRow', fData]);

        this.rowEl = createDomEl("tr");
        this.rowEl.rowObject = this;

        //cellEl.text(JSON.stringify(fData));
        //var textNode = document.createTextNode(JSON.stringify(fData));
        //cellEl.appendChild(textNode);
        for (var key in this.cellElObj) {
            objVal = fData[key];

            if (fData) {
                cellEl = createDomEl("td");
                textNode = document.createTextNode(objVal);
                cellEl.appendChild(textNode);
            } else {
                cellEl = createDomEl("th");
                textNode = document.createTextNode(key);
                cellEl.appendChild(textNode);
            }


            this.cellElObj[key] = cellEl;

            this.rowEl.appendChild(cellEl);
        }



        this.processListTable.registerRowEl(this.rowEl);
    };

    this.update = function (fData) {
        var el, oldVal, newVal;
        for (var key in this.cellElObj) {
            el = this.cellElObj[key];
            oldVal = el.value;
            newVal = fData[key];
            
            if(oldVal != newVal){
                el.innerHTML = newVal;
            }
        }
    };

    this.init(fConfig.data);
}

rpcCallArray = [
    {
        key: "processList",
        method: "getProcessList",
        jsFn: updateProcesslist
    }, {
        key: "connectionVissualisationObject",
        method: "getConnectionVissualisation",
        jsFn: updateConnectionVissualisation
    }, {
        key: "connectionStatsObject",
        method: "getConnectionStats",
        jsFn: updateConnectionStats
    }
];



function updateProcesslist(fParam) {
    //console.log(["updateProcesslist", fParam]);

    processListTableObject.updateRows(fParam);
}

function updateConnectionVissualisation(fParam) {
    
    console.log(["updateConnectionVissualisation", fParam]);
}

function updateConnectionStats(fParam) {
    if(!fParam){
        return;
    }
        
    var out = '';
    
    console.log(["updateProcesslist", fParam, frameStatsDivEl]);
    
    out += '<span style="font-weight: bold; font-size: 80%; margin-right: 10px; background-color: #000000; color: #FFFFFF;">Runtime stats</span>';
    out += ' <span style="font-weight: bold; font-size: 80%; margin-right: 10px; background-color: #000000; color: #FFFFFF;">last</span> ' + Math.round(fParam.lastRuntime * 1000, 0 ) + 'ms';
    out += ' <span style="font-weight: bold; font-size: 80%; margin-right: 10px; background-color: #000000; color: #FFFFFF;">min</span> ' + Math.round(fParam.minRuntime * 1000, 0 ) + 'ms';
    out += ' <span style="font-weight: bold; font-size: 80%; margin-right: 10px; background-color: #000000; color: #FFFFFF;">avg</span> ' + Math.round(fParam.avgRuntime * 1000, 0 ) + 'ms';
    out += ' <span style="font-weight: bold; font-size: 80%; margin-right: 10px; background-color: #000000; color: #FFFFFF;">max</span> ' + Math.round(fParam.maxRuntime * 1000, 0 ) + 'ms';
    out += ' <span style="font-weight: bold; font-size: 80%; margin-right: 10px; background-color: #000000; color: #FFFFFF;">int</span> ' + Math.round(fParam.intervalRuntime * 1000, 0 ) + 'ms';
    out += ' <span style="font-weight: bold; font-size: 80%; margin-right: 10px; background-color: #000000; color: #FFFFFF;">poll</span> ' + fParam.pollCount + '';
    out += ' ' + getConnectionStatus(fParam.lastUpdatedTimeStamp);
    
    //Need a better number
//    out += ' <span style="font-weight: bold; font-size: 80%; margin-right: 10px; background-color: #000000; color: #FFFFFF;">usage</span> ' + Math.round(fParam.peakMemoryUsage / 1024, 2 ) + 'KB';

    frameStatsDivEl.innerHTML = out;
    
}

function getConnectionStatus(fLastUpdateTimeStamp){
    var stateOut = '';
    var timeSinceLastUpdate = microtime(true) - fLastUpdateTimeStamp;
    
    if(timeSinceLastUpdate < 2){
        //is active
        stateOut = '<div class="status-block status-positive">Active</div>';
    }else if(timeSinceLastUpdate < 5){
        stateOut = '<div class="status-block status-negative">Stall ' + Math.round(fLastUpdateTimeStamp * 1000, 0 ) + 's</div>';
    }else{
        stateOut = '<div class="status-block status-critical">Stopped ' + Math.round(fLastUpdateTimeStamp / 60, 0 ) + 'm</div>';
    }

    return stateOut;
}


function doRpcCall(fCallArr) {
    $.ajax({
        method: "POST",
        url: "?",
        data: {
            page: "rpc",
            request_list: fCallArr
        }
    }).done(completeRpcCall);
}

function completeRpcCall(fResponseMsg) {
    //console.log(fMsg);
    var respObj = jQuery.parseJSON(fResponseMsg);

    var respItem, confObj, jsFn;
    for (var key in respObj) {
        respItem = respObj[key];
        confObj = getRpcConfigObj(key);

        //console.log([key, respItem, confObj]);

        if (confObj) {
            jsFn = confObj.jsFn;
            jsFn(respItem);
        }
    }
}

function getRpcConfigObj(fKey) {
    var resObj = false;

    var confObj, key;
    for (key in rpcCallArray) {
        confObj = rpcCallArray[key];

        if (confObj.key == fKey) {
            resObj = confObj;
            break;
        }
    }

    return resObj;
}

function createDomEl(fTag) {
    var domEl = document.createElement(fTag);
    //var domEl = $(fTag);

    return domEl;
}

function init() {
    processListTableObject = new processListTable("#frame_procl");
    
    var frameStatsEl = $('#frame_stats');
    frameStatsDivEl = createDomEl("div");    
    frameStatsEl.append(frameStatsDivEl);
    
}

function repeatUpdateAll() {
//                updateAll();
    doRpcCall(rpcCallArray);

    setTimeout(repeatUpdateAll, 500);
}


// Generic functions (move later?) \\

function microtime(get_as_float) {
  //  discuss at: http://phpjs.org/functions/microtime/
  // original by: Paulo Freitas
  //   example 1: timeStamp = microtime(true);
  //   example 1: timeStamp > 1000000000 && timeStamp < 2000000000
  //   returns 1: true

  var now = new Date()
    .getTime() / 1000;
  var s = parseInt(now, 10);

  return (get_as_float) ? now : (Math.round((now - s) * 1000) / 1000) + ' ' + s;
}