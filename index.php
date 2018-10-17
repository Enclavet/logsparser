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
            createChart(tableData, run, "boost", "Boost vs Actual", "Boost", 0.2, boostArray);
        }
        if(iatidx != 0) {
            var iatArray = getSeriesArray(tableData,["IAT"],["#0071A7"],[1]);
            createChart(tableData, run, "iat", "IAT", "IAT", 0.5, iatArray);
        }
        if(timingidx != 0) {
            if(knocktotal) {
                var timingArray = getSeriesArray(tableData,["Timing","Knock Total (Timing pulled)"],["#0071A7","#FF404E"],[2,8]);
                createChart(tableData, run, "timing", "Timing vs Knock", "Timing (degrees)", 1, timingArray);
            } else {
                var timingArray = getSeriesArray(tableData,["Timing"],["#0071A7"],[2]);
                createChart(tableData, run, "timing", "Timing", "Timing (degrees)", 1, timingArray);
            }
        }
        if(dutyidx != 0) {
            var dutyArray = getSeriesArray(tableData,["Inj Duty Cycle"],["#0071A7"],[3]);
            createChart(tableData, run, "duty", "Inj Duty Cycle", "Inj Duty Cycle (%)", 1, dutyArray);
        }
        if(lambda1idx != 0  && lambda2idx != 0) {
            var lambdaArray =  getSeriesArray(tableData,["Lambda #1","Lambda #2"],["#0071A7","#FF404E"],[4,5]);
            createChart(tableData, run, "lambda", "Lambda 1 and 2", "Lambda", 0.5, lambdaArray);
        }
        if(maf != 0) {
            var mafArray = getSeriesArray(tableData,["MAF"],["#0071A7"],[9]);
            createChart(tableData, run, "maf", "MAF", "MAF", 10, mafArray);
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
    var knockTotalArr = [];
    var mafArr = [];

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
        knockTotalArr.push(parseFloat(data[knocktotal]));
        mafArr.push(parseFloat(data[maf]));
    });

    dataArray.push(rpmArray, iat, timing, duty, lambda1, lambda2, boostActual, boostReq, knockTotalArr, mafArr);

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
$injduty = 0;
$lambda1 = 0;
$lambda2 = 0;
$boostact = 0;
$boostreq = 0;
$maf = 0;
$knockcyl1 = 0;
$knockcyl2 = 0;
$knockcyl3 = 0;
$knockcyl4 = 0;
$knockcyl5 = 0;
$knockcyl6 = 0;
$knocktotal = 0;
$duty_detected=0;
$knock_detected=0;
$logtype = 0;

$boostambient = 970;
$stoich=14.7;

if(isset($_FILES['upload']['name'])) {
    $logtype=$_REQUEST['logtype'];
    $tmpFilePath = $_FILES['upload']['tmp_name'][0];
    if ($tmpFilePath != ""){
        $filename=uniqid() . ".csv";
        $newFilePath = "./uploads/" . $filename;
        move_uploaded_file($tmpFilePath, $newFilePath);
        print "Sharable Results: <a href=index.php?logtype=$logtype&logfile=$newFilePath>https://logs.enclavenet.com/index.php?logtype=$logtype&logfile=$newFilePath</a>";
    }
} else if(isset($_REQUEST['logfile'])) {
    $logtype=$_REQUEST['logtype'];
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
        } elseif(preg_match("/Engine load|Load \(Relative\)/i",$title)) {
            $load=$title_place;        
        } elseif(preg_match("/Intake air temperature|Intake-Air Temperature/i",$title)) {
            $iat=$title_place;
        } elseif(preg_match("/Ignition angle/i",$title)) {
            $timing=$title_place;
        } elseif(preg_match("/Injection time/i",$title)) {
            $injtime=$title_place;
            $duty_detected=1;
        } elseif(preg_match("/Actual value throttle|Throttle Angle/i",$title)) {
            $tps=$title_place;
        } elseif(preg_match("/Oxygen sensing bank 1 Lambda Value|Lambda Bank 1/i",$title)) {
            $lambda1=$title_place;
        } elseif(preg_match("/Oxygen sensing bank 2 Lambda Value|Lambda Bank 2/i",$title)) {
            $lambda2=$title_place;
        } elseif(preg_match("/Boost pressure of sensor|Manifold Absolute Pressure/i",$title)) {
            $boostact=$title_place;
        } elseif(preg_match("/Setpoint boost pressure|Target Boost Pressure/i",$title)) {
            $boostreq=$title_place;
        } elseif(preg_match("/MAF/i",$title)) {
            $maf=$title_place;
        } elseif(preg_match("/Knock Sums/i",$title)) {
            $knock_detected=1;
            if(preg_match("/Cyl\. 1/i",$title)) {
                $knockcyl1=$title_place;
            } elseif(preg_match("/Cyl\. 2/i",$title)) {
                $knockcyl2=$title_place;
            } elseif(preg_match("/Cyl\. 3/i",$title)) {
                $knockcyl3=$title_place;
            } elseif(preg_match("/Cyl\. 4/i",$title)) {
                $knockcyl4=$title_place;
            } elseif(preg_match("/Cyl\. 5/i",$title)) {
                $knockcyl5=$title_place;
            } elseif(preg_match("/Cyl\. 6/i",$title)) {
                $knockcyl6=$title_place;
            } 
        }
        $title_place++;
    }
    if($duty_detected) {
        $titlearray[] = "Inj Duty Cycle";
        $injduty=$title_place;
        $title_place++;
    }
    if($knock_detected) {
        $titlearray[] = "Knock Totals";
        $knocktotal=$title_place;
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
            $data_injtime = $rundata[$injtime];
            $data_knocktotal = min($rundata[$knockcyl1],$rundata[$knockcyl2],$rundata[$knockcyl3],$rundata[$knockcyl4],$rundata[$knockcyl5],$rundata[$knockcyl6]);
            foreach($rundata as $data) {
                if ($data_place==$injtime) {
                    print "<td>$data</td>";
                } else if($data_place==$lambda1 || $data_place==$lambda2) {
                    if($logtype==0) {
                        print "<td>". $data*$stoich . "</td>";
                    } else {
                        print "<td>$data</td>";
                    }

                } else if($data_place==$boostact || $data_place==$boostreq) {
                    if($logtype==0) {
                        print "<td>". ($data-$boostambient)/1000 . "</td>"; 
                    } else {
                        print "<td>$data</td>";
                    }
                } else {
                    print "<td>$data</td>";
                }
                $data_place++;
            }
            if($duty_detected) {
                print "<td>". number_format(($data_injtime*$data_rpm)/1200,1) . "</td>";   
            }
            if($knock_detected) {
                print "<td>". abs($data_knocktotal) . "</td>";
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
        print "<div id='graph".$run."maf'></div>";
        print "<br>";
        $run++;
    }
} else {
    print "<form action=\"index.php\" method=\"post\" enctype=\"multipart/form-data\">";
    print "<table>";
    print "<tr><td>Log Type:</td><td>";
    print "<select name=\"logtype\">";
    print "<option value=\"0\">Durametric</option>";
    print "<option value=\"1\">AccessPort</option>";
    print "</select></td></tr>";
    print "<tr><td>Log File:</td><td><input name=\"upload[]\" type=\"file\" /></td></tr>";
    print "<tr><td colspan=\"2\"><input type=\"Submit\" value=\"Submit\"></td></tr>";
    print "</form>";
    print "</table>";
}

print "<script>";
print "var rpmidx=".$rpm.";";
print "var iatidx=".$iat.";";
print "var timingidx=".$timing.";";
print "var dutyidx=".$injduty.";";
print "var lambda1idx=".$lambda1.";";
print "var lambda2idx=".$lambda2.";";
print "var boostactidx=".$boostact.";";
print "var boostreqidx=".$boostreq.";";
print "var maf=".$maf.";";
print "var knocktotal=".$knocktotal.";";
print "</script>";

?>

</body>
</html>
