<html>

<head>
<title>Durametric Log Parser</title>
<link href='https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css' rel='stylesheet'></link>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src='https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js'></script>
<script src="https://code.highcharts.com/highcharts.src.js"></script>
<style>

.title td {
    font-weight: bold;
}

</style>

<script>
var run=1;
$(document).ready( function () {
    $('table.runtable').each(function(e) {
        var table = $(this).DataTable({
             "iDisplayLength": 50
        });
        var tableData = getTableData(table);
        if(boostactidx != 0 && boostreqidx != 0) {
            var boostArray = getSeriesArray(tableData,["Boost Actual","Boost Requested"],["#0071A7","#FF404E"],[6,7]);
            createChart(tableData, run, "boost", "Boost vs Actual", "Boost (bar)", 0.2, boostArray);
        }
        if(iatidx != 0) {
            var iatArray = getSeriesArray(tableData,["IAT"],["#0071A7"],[1]);
            createChart(tableData, run, "iat", "IAT", "IAT (C)", 0.5, iatArray);
        }
        if(timingidx != 0) {
            var timingArray = getSeriesArray(tableData,["Timing"],["#0071A7"],[2]);
            createChart(tableData, run, "timing", "Timing", "Timing (degrees)", 1, timingArray);
        }
        if(dutyidx != 0) {
            var dutyArray = getSeriesArray(tableData,["Inj Duty Cycle"],["#0071A7"],[3]);
            createChart(tableData, run, "duty", "Inj Duty Cycle", "Inj Duty Cycle (%)", 1, dutyArray);
        }
        if(lambda1idx != 0  && lambda2idx != 0) {
            var lambdaArray =  getSeriesArray(tableData,["Lambda #1","Lambda #2"],["#0071A7","#FF404E"],[4,5]);
            createChart(tableData, run, "lambda", "Lambda 1 and 2", "Lambda", 0.5, lambdaArray);
        }
        run++;
    });
});

function getSeriesArray(tableData, titleArray, colorArray, dataIndex) {
    var seriesArray = [];
    for(var i in titleArray) {
        var seriesobj = {
            name: titleArray[i],
            color: colorArray[i],
            type: "line",
            data: tableData[dataIndex[i]],
        }
        seriesArray.push(seriesobj);
    }
    return seriesArray;
}

function getTableData(table) {
    var dataArray = [];
    var rpmArray = [];
    var iat=[];
    var timing=[];
    var duty=[];
    var lambda1=[];
    var lambda2=[];
    var boostActual = [];
    var boostReq = [];
    

    table.rows({ search: "applied" }).every(function() {
        var data = this.data();
        rpmArray.push(data[rpmidx]);
        iat.push(parseFloat(data[iatidx]));
        timing.push(parseFloat(data[timingidx]));
        duty.push(parseFloat(data[dutyidx]));
        lambda1.push(parseFloat(data[lambda1idx]));
        lambda2.push(parseFloat(data[lambda2idx]));
        boostActual.push(parseFloat(data[boostactidx]));
        boostReq.push(parseFloat(data[boostreqidx]));
    });

    dataArray.push(rpmArray, iat, timing, duty, lambda1, lambda2, boostActual, boostReq);

    return dataArray;
}

function createChart(data, chartnum, prefix, title, yaxis_title, yaxis_tick, seriesarray) {
    Highcharts.chart("graph"+chartnum+prefix, {
    title: {
      text: title
    },
    xAxis: [
      {
        categories: data[0],
        labels: {
          rotation: -45
        }
      }
    ],
    yAxis: [
      {
        title: {
          text: yaxis_title
        },
        tickInterval: yaxis_tick
      },
    ],
    series: seriesarray,
    tooltip: {
      shared: true
    },
    legend: {
      backgroundColor: "#ececec",
      shadow: true
    },
    credits: {
      enabled: false
    },
    noData: {
      style: {
        fontSize: "16px"
      }
    }
  });
}

</script>

</head>

<body>

<?php

$rpm = 0;
$load = 0;
$tps = 0;
$iat=0;
$timing=0;
$injtime = 0;
$lambda1 = 0;
$lambda2 = 0;
$boostact = 0;
$boostreq = 0;
$duty_detected=0;

$boostambient = 970;
$stoich=14.7;

if(isset($_FILES['upload']['name'])) {
    $tmpFilePath = $_FILES['upload']['tmp_name'][0];
    if ($tmpFilePath != ""){
        $filename=uniqid() . ".csv";
        $newFilePath = "./uploads/" . $filename;
        move_uploaded_file($tmpFilePath, $newFilePath);
        print "Sharable Results: <a href=index.php?logfile=$newFilePath>https://logs.enclavenet.com/index.php?logfile=$newFilePath</a>";
    }
} else if(isset($_REQUEST['logfile'])) {
    $csv = array_map('str_getcsv', file($_REQUEST['logfile']));
    $pull=0;
    $run=1;
    $entries=0;
    $temparray = array();
    $runarray = array();
    $titlearray = array();
    
    $title_place=0;
    foreach($csv[0] as $title) {
        $titlearray[] = $title;
        if(preg_match("/RPM/i", $title)) {
            $rpm=$title_place;
        } elseif(preg_match("/Engine load/i",$title)) {
            $load=$title_place;        
        } elseif(preg_match("/Intake air temperature/i",$title)) {
            $iat=$title_place;
        } elseif(preg_match("/Ignition angle/i",$title)) {
            $timing=$title_place;
        } elseif(preg_match("/Injection time/i",$title)) {
            $injtime=$title_place;
            $duty_detected=1;
            $titlearray[] = "Inj Duty Cycle";
        } elseif(preg_match("/Actual value throttle/i",$title)) {
            $tps=$title_place;
        } elseif(preg_match("/Oxygen sensing bank 1 Lambda Value/i",$title)) {
            $lambda1=$title_place;
        } elseif(preg_match("/Oxygen sensing bank 2 Lambda Value/i",$title)) {
            $lambda2=$title_place;
        } elseif(preg_match("/Boost pressure of sensor/i",$title)) {
            $boostact=$title_place;
        } elseif(preg_match("/Setpoint boost pressure/i",$title)) {
            $boostreq=$title_place;
        } 
       
        $title_place++;
    }
    foreach($csv as $line) {
        $startrun=0;
        $endrun=0;
        if($pull == 0) {
            if($tps != 0) {
                if($line[$tps] >= 70) {
                    $startrun=1;
                }
            } elseif($load != 0) {
                if($line[$load] >= 90) {
                    $startrun=1;
                }
            }
        } else {
            if($tps != 0) {
                if($line[$tps] < 70) {
                    $endrun=1;
                }
            } elseif($load != 0) {
                if($line[$load] < 90) {
                    $endrun=1;
                }
            }
        }
        if($startrun==1) {
            $startrun=0;
            $pull=1;
            $entries=0;
            $temparray = array();
        }
        if($endrun==1) {
            $endrun=0;
            $pull=0;
            $run++;
            if($entries > 10) {
                $runarray[] = $temparray;
            }
        }
        if($pull==1) {
            $temparray[] = $line;
            $entries++;
        }
    }

    $run=1;
    foreach($runarray as $runline) {
        print "<h1>Run #".$run."</h1>";
        print "<table id='table".$run."' class='runtable'>";
        print "<thead><tr class='title'>";
        foreach($titlearray as $title) {
            print "<th>".$title."</th>";
        }
        print "</tr></thead>";
        print "<tbody>";
        foreach($runline as $rundata) {
            print "<tr class='data'>";
            $data_place=0;
            $data_rpm = $rundata[$rpm];
            foreach($rundata as $data) {
                if ($data_place==$injtime) {
                    print "<td>$data</td>";
                    print "<td>". number_format(($data*$data_rpm)/1200,1) . "</td>";   
                    $data_place++; 
                } else if($data_place==$lambda1+$duty_detected || $data_place==$lambda2+$duty_detected) {
                    print "<td>". $data*$stoich . "</td>";

                } else if($data_place==$boostact+$duty_detected || $data_place==$boostreq+$duty_detected) {
                    print "<td>". ($data-$boostambient)/1000 . "</td>"; 
                } else {
                    print "<td>$data</td>";
                }
                $data_place++;
            }
            print "</tr>";
        }
        print "</tbody>";
        print "</table>";
        print "<div id='graph".$run."boost'></div>";
        print "<div id='graph".$run."iat'></div>";
        print "<div id='graph".$run."timing'></div>";
        print "<div id='graph".$run."duty'></div>";
        print "<div id='graph".$run."lambda'></div>";
        print "<br>";
        $run++;
    }
} else {
    print "<form action=\"index.php\" method=\"post\" enctype=\"multipart/form-data\">";
    print "<table>";
    print "<tr><td>Log File:</td><td><input name=\"upload[]\" type=\"file\" /></td></tr>";
    print "<tr><td colspan=\"2\"><input type=\"Submit\" value=\"Submit\"></td></tr>";
    print "</form>";
    print "</table>";
}

print "<script>";
print "var rpmidx=".$rpm.";";
print "var iatidx=".$iat.";";
print "var timingidx=".$timing.";";
print "var dutyidx=".($injtime+$duty_detected).";";
print "var lambda1idx=".($lambda1+$duty_detected).";";
print "var lambda2idx=".($lambda2+$duty_detected).";";
print "var boostactidx=".($boostact+$duty_detected).";";
print "var boostreqidx=".($boostreq+$duty_detected).";";
print "</script>";

?>

</body>
</html>
