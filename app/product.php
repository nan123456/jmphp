<?php
require ("../conn.php");
$flag = $_POST["flag"];
//$flag ='12';
$time = date("Y-m-d H:i:s");
switch ($flag) {
	//获取就工前的信息
    case '0':
        $id = $_POST["id"];
        $pid = $_POST["pid"];
        $modid = $_POST["modid"];
        $routeid = $_POST["routeid"];
        $sql = "select name,figure_number,pNumber,count,child_material,quantity,modid from part where id='" . $id . "' ";
        $res = $conn->query($sql);
        if ($res->num_rows > 0) {
            $i = 0;
            while ($row = $res->fetch_assoc()) {
                $arr[$i]['name'] = $row['name'];
                $arr[$i]['figure_number'] = $row['figure_number'];
				$arr[$i]['pNumber'] = $row['pNumber'];
                $arr[$i]['count'] = $row['count'];
                $arr[$i]['child_material'] = $row['child_material'];
                $arr[$i]['quantity'] = $row['quantity'];
                $i++;
            }
        }
        $sql1 = "SELECT route FROM route WHERE modid='" . $modid . "' AND pid='" . $pid . "' AND isfinish='0' ORDER by id LIMIT 1 ";
        $res1 = $conn->query($sql1);
        if ($res1->num_rows > 0) {
            $i = 0;
            while ($row1 = $res1->fetch_assoc()) {
                $arr[$i]['route'] = $row1['route'];
                $i++;
            }
        }
        $sql2 = "SELECT station,remark FROM workshop_k WHERE modid='" . $modid . "' AND routeid='" . $routeid . "' AND isfinish!='3' ORDER by id LIMIT 1 ";
        $res2 = $conn->query($sql2);
        if ($res2->num_rows > 0) {
            $i = 0;
            while ($row2 = $res2->fetch_assoc()) {
                $arr[$i]['station'] = $row2['station'];
                $arr[$i]['remark'] = $row2['remark'];
                $i++;
            }
        } else {
            //若workshop_k无数据跳出循环
            die();
        }
        $sql3 = "SELECT notNum FROM workshop_k WHERE modid='" . $modid . "' AND routeid='" . $routeid . "' AND isfinish!='3' ORDER by id LIMIT 1 ";
        $res3 = $conn->query($sql3);
        if ($res3->num_rows > 0) {
            $i = 0;
            while ($row3 = $res3->fetch_assoc()) {
                $arr[$i]['notNum'] = $row3['notNum'];
                $i++;
            }
        } else {
            //若workshop_k无数据跳出循环
            die();
        }
        $json = json_encode($arr);
        echo $json;
        break;

    case '1':
        // 获取isfinish状态
        $modid = $_POST["modid"];
        $pid = $_POST["pid"];
        $sql = "SELECT isfinish,id FROM route WHERE modid='" . $modid . "' AND pid='" . $pid . "' AND isfinish!='1' ORDER by id LIMIT 1";
        $res = $conn->query($sql);
        if ($res->num_rows > 0) {
            $i = 0;
            while ($row = $res->fetch_assoc()) {
                $arr[$i]['isfinish'] = $row['isfinish'];
                $arr[$i]['routeid'] = $row['id'];
                $i++;
            }
        }
        $json = json_encode($arr);
        echo $json;
        break;
	//即就工状态，更新isfinish状态为在建
    case '2':
        $modid = $_POST["modid"];
        $pid = $_POST["pid"];
        $routeid = $_POST["routeid"];
        $isfinish = $_POST["isfinish"];
        $route = $_POST["route"];
        $station = $_POST["station"];
        $name = $_POST["name"];
		$figure_number = $_POST["figure_number"];
		$pNumber = $_POST["pNumber"];
        $count = $_POST["count"];
        $workstate = '就工';
        $message = $name . "的" . $route . "已就工！";
        $writtenBy = $_POST["writtenBy"];
        $department = $_POST["department"];
        //正常情况
        //		if ($isfinish == "0") {
        $sql = "UPDATE workshop_k SET isfinish='2' ,route='$route' ,name='$name' ,todocount='$count' ,stime='" . date("Y-m-d H:i:s") . "' WHERE modid='" . $modid . "' and routeid='" . $routeid . "' AND isfinish='0' ORDER by id LIMIT 1";
        $conn->query($sql);
        // 更新route路线中（在建）
        $sql2 = "UPDATE route SET isfinish='2' where modid='" . $modid . "' and id='" . $routeid . "' ORDER by id LIMIT 1 ";
        $conn->query($sql2);
        //更新part部件为在建
        $sql_part = "UPDATE part SET isfinish='2' where modid='" . $modid . "' LIMIT 1 ";
        $conn->query($sql_part);
		$sql3 = "INSERT INTO warehouse (modid,pid,pNumber,figure_number) VALUES ('".$modid."','".$pid."','".$pNumber."','".$figure_number."')";
		$conn->query($sql3);
        //		} else if ($isfinish == "0"){
        //			$sql3 = "UPDATE workshop_k SET isfinish='2' WHERE modid='" . $modid . "' and routeid='" . $routeid . "' and isfinish='4' ORDER by id LIMIT 1";
        //			$conn -> query($sql3);
        //			//				die();
        //		}
        //判断route处于哪个车间
		$sql_class="SELECT workshop FROM workshop_class where route='" . $route . "'";
		$res_class = $conn->query($sql_class);
		if ($res_class->num_rows > 0) {
            while ($row = $res_class->fetch_assoc()) {
                $workshop = $row['workshop'];
            }
        }
        else{
        	$workshop ="无";
        }
        // 更新message
        $sql3 = "INSERT INTO message (content,time,department,state,workstate,route,station,cuser,workshop,count) VALUES ('" . $message . "','" . $time . "','" .$department. "','0','" . $workstate . "','" . $route . "','" . $station . "','" . $writtenBy . "','" . $workshop . "','" . $count . "')";
        $res = $conn->query($sql3);
        $messageid = $conn->insert_id;
        $data['messageid'] = $messageid;
        $json = json_encode($data);
        echo $json;
        break;

    case '3':
        // 更新isfinish状态完成
        $modid = $_POST["modid"];
        $pid = $_POST["pid"];
        $routeid = $_POST["routeid"];
        $sql = "UPDATE workshop_k SET isfinish='1' where modid='" . $modid . "' and routeid='" . $routeid . "' ORDER by id LIMIT 1 ";
        $conn->query($sql);
        // 循环检测是否所有工序完成
        $sql1 = "SELECT isfinish from workshop_k where modid='" . $modid . "' ";
        $res = $conn->query($sql1);
        if ($res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                if ($row['isfinish'] != '1') {
                    // 检测如果还有未完成则终止脚本
                    die();
                }
            }
            // 更新route进度为完成状态
            $sql2 = "SELECT id,isfinish from route where pid='" . $pid . "' and modid='" . $modid . "'";
            $res2 = $conn->query($sql2);
            if ($res2->num_rows > 0) {
                while ($row2 = $res2->fetch_assoc()) {
                    $routeid = $row2['id'];
                    if ($row2['isfinish'] == '2') {
                        $sql3 = "UPDATE route SET isfinish='1' where id='" . $routeid . "' ";
                        $conn->query($sql3);
                        die();
                    }
                }
            }
        }
        break;

    case '4':
        // 获取isfinish状态
        $modid = $_POST["modid"];
        $routeid = $_POST["routeid"];
        $sql = "SELECT id,routeid,isfinish FROM workshop_k WHERE modid='" . $modid . "' AND routeid='" . $routeid . "' AND isfinish!='3' ORDER by id LIMIT 1";
        $res = $conn->query($sql);
        if ($res->num_rows > 0) {
            $i = 0;
            while ($row = $res->fetch_assoc()) {
                $arr[$i]['isfinish'] = $row['isfinish'];
                $arr[$i]['routeid'] = $row['routeid'];
                $arr[$i]['wid'] = $row['id'];
                $i++;
            }
        } else {
            $i = 0;
            //已完工
            $arr[$i]['isfinish'] = "1";
        }
        $json = json_encode($arr);
        echo $json;
        break;
	//完工确认,更新isfinish状态完成
    case '5':
        $modid = $_POST["modid"];
        $pid = $_POST["pid"];
        $routeid = $_POST["routeid"];
        $route = $_POST["route"];
        $messageid = $_POST["messageid"];
        $station = $_POST["station"];
        $name = $_POST["name"];
		$figure_number = $_POST["figure_number"];
		$pNumber = $_POST["pNumber"];
        $todocount = $_POST["todocount"];//待完成
        $finishcount = $_POST["finishcount"];
        $workstate = '完工';
        $message = $name . "的" . $route . "已完工！";
        $writtenBy = $_POST["writtenBy"];
        $department = $_POST["department"];
		$sql6 = "UPDATE warehouse SET count='".$finishcount."' where pid='".$pid."' and figure_number='".$figure_number."' and pNumber='".$pNumber."' ";
		$conn->query($sql6);
		//完工人员信息添加
        	$sql_people = "select workuser from workshop_k where modid='" . $modid . "' and routeid='" . $routeid . "' ORDER by id LIMIT 1 ";
			$result = $conn -> query($sql_people);
			$row_num = $result -> fetch_assoc();
			if (strlen($row_num["workuser"]) > 0) {
				$workuser = $row_num["workuser"] . "," . $writtenBy;
			} else {
				$workuser = $writtenBy;
			}
        if ($todocount === $finishcount) {
            $todocount = $todocount - $finishcount;
            $sql = "UPDATE workshop_k SET isfinish='1' ,workuser='" . $workuser . "' ,todocount='" . $todocount . "' ,inspectcount=inspectcount + '" . $finishcount . "' ,ftime='" . $time . "' where modid='" . $modid . "' and routeid='" . $routeid . "' ORDER by id LIMIT 1 ";
            $conn->query($sql);
			
        } else {
            $todocount = $todocount - $finishcount;
            $sql5 = "UPDATE workshop_k SET workuser='" . $workuser . "' ,todocount='" . $todocount . "' ,inspectcount=inspectcount + '" . $finishcount . "' where modid='" . $modid . "' and routeid='" . $routeid . "'  ORDER by id LIMIT 1 ";
            $conn->query($sql5);
            //			$sql4 = "SELECT finishcount from workshop_k where modid='" . $modid . "' and routeid='" . $routeid . "' ORDER by id LIMIT 1 ";
            //			$res = $conn -> query($sql4);
            //			$row = $res -> fetch_assoc();
            //			echo $row["finishcount"];
            //			$sql3 = "UPDATE workshop_k SET finishcount='$finishcount', count='$count' where modid='" . $modid . "' and routeid='" . $routeid . "' and isfinish='2' ORDER by id LIMIT 1 ";
            //			$conn -> query($sql3);
            
        }
         //判断route处于哪个车间
		$sql_class="SELECT workshop FROM workshop_class where route='" . $route . "'";
		$res_class = $conn->query($sql_class);
		if ($res_class->num_rows > 0) {
            while ($row = $res_class->fetch_assoc()) {
                $workshop = $row['workshop'];
            }
        }
        else{
        	$workshop ="无";
        }
        //更新message
        $sql1 = "INSERT INTO message (content,time,department,state,workstate,route,cuser,workshop,count) VALUES ('" . $message . "','" . date("Y-m-d H:i:s") . "','" . $department . "','0','" . $workstate . "','" . $route . "','" . $writtenBy . "','" . $workshop . "','" . $finishcount . "')";
        $conn->query($sql1);
        $sql2 = "UPDATE message SET state='1' where id='" . $messageid . "' ";
        $conn->query($sql2);
        break;

    case '6': // 检验改变状态
        $modid = $_POST["modid"];
        //		$modid = '1000616927';
        $pid = $_POST["pid"];
        $routeid = $_POST["routeid"];
        //		$routeid = '19067';
        $route = $_POST["route"];
        $station = $_POST["station"];
        $messageid = $_POST["messageid"];
        $name = $_POST["name"];
        $figure_number = $_POST["figure_number"];
        $inspectcount = $_POST["inspectcount"];//待检验
        // $unqualified = $_POST["unqualified"];
        $finishcount = $_POST["finishcount"];
        $writtenBy = $_POST["writtenBy"];
        $inspect = $_POST["inspect"];
        $remark = $_POST["remark"];
        $isexternal=$_POST["isexternal"];//外协标志
        $workstate = '检验';
        $message = $name . "的" . $route . "已检验！";
        //检验人员信息添加
        	$sql_people = "select testuser from workshop_k where modid='" . $modid . "' and routeid='" . $routeid . "' ORDER by id LIMIT 1 ";
			$result = $conn -> query($sql_people);
			$row_num = $result -> fetch_assoc();
			if (strlen($row_num["testuser"]) > 0) {
				$testuser = $row_num["testuser"] . "," . $writtenBy;
			} else {
				$testuser = $writtenBy;
			}
        if($isexternal=="1"){//外协
        	$routeid = "";
			$workshop="外协";
        }
        else{
			//判断route处于哪个车间
			$sql_class="SELECT workshop FROM workshop_class where route='" . $route . "'";
			$res_class = $conn->query($sql_class);
			if ($res_class->num_rows > 0) {
			    while ($row = $res_class->fetch_assoc()) {
			        $workshop = $row['workshop'];
			    }
			}
			else{
				$workshop ="无";
			}
        	$routeid = $_POST["routeid"];
        }
        if ($inspect === "3") {
            // 合格处理
            if ($inspectcount === $finishcount) {
                $inspectcount = $inspectcount - $finishcount;
                $sql = "UPDATE workshop_k SET testuser='" . $testuser . "' , remark='" . $remark . "' ,utime='" . $time . "' ,inspectcount='" . $inspectcount . "' WHERE modid='" . $modid . "' and routeid='" . $routeid . "' ORDER by id LIMIT 1";
                $conn->query($sql);
                // 循环检测是否所有零件完成
                $sql3 = "SELECT todocount ,reviews,unqualified from workshop_k where modid='" . $modid . "' and routeid='" . $routeid . "'  ";
                $res = $conn->query($sql3);
                if ($res->num_rows > 0) {
                    while ($row = $res->fetch_assoc()) {
                        if ($row['todocount'] == '0' && $row['reviews'] == '0'&& $row['unqualified'] == '0') {
                            $sql4 = "UPDATE workshop_k SET testuser='" . $testuser . "' , isfinish='3'  WHERE modid='" . $modid . "' and routeid='" . $routeid . "' ORDER by id LIMIT 1";
                            $conn->query($sql4);
							//更新message
							$sql1 = "UPDATE message SET state='1' where id='" . $messageid . "' ORDER by id LIMIT 1 ";
							$conn->query($sql1);
							//将检验信息更新到消息通知
							$workstate = '合格';
							$sql2 = "INSERT INTO message (content,time,department,state,workstate,route,cuser,workshop,count) VALUES ('" . $message . "','" . date("Y-m-d H:i:s") . "','检验部','0','" . $workstate . "','" . $route . "','" . $writtenBy . "','" . $workshop . "','" . $finishcount . "')";
							$conn->query($sql2);
							$sql_update = "UPDATE message SET state='1' where id='" . $messageid . "' ";
							$conn->query($sql_update);
                        }
                    }
                }
            } else {
                $inspectcount = $inspectcount - $finishcount;
                $sql13 = "UPDATE workshop_k SET testuser='" . $testuser . "' , remark='" . $remark . "' ,utime='" . $time . "' ,inspectcount='" . $inspectcount . "' WHERE modid='" . $modid . "' and routeid='" . $routeid . "' ORDER by id LIMIT 1";
                $conn->query($sql13);
            }
        }
        //不合格处理
        else if ($inspect === "7") {
            $inspectcount = $inspectcount - $finishcount;
            $sql14 = "UPDATE workshop_k SET testuser='" . $testuser . "' , unqualified=unqualified +  '" . $finishcount . "',inspectcount='" . $inspectcount . "' WHERE modid='" . $modid . "' and routeid='" . $routeid . "'  ORDER by id LIMIT 1";
            $conn->query($sql14);
            //将检验信息更新到消息通知
			$workstate = '不合格';
			$sql2 = "INSERT INTO message (content,time,department,state,workstate,route,cuser,workshop,count) VALUES ('" . $message . "','" . date("Y-m-d H:i:s") . "','检验部','0','" . $workstate . "','" . $route . "','" . $writtenBy . "','" . $workshop . "','" . $finishcount . "')";
			$conn->query($sql2);
			$sql_update = "UPDATE message SET state='1' where id='" . $messageid . "' ";
			$conn->query($sql_update);
        }
        // 循环检测是否所有工序完成
        $sql9 = "SELECT isfinish from workshop_k where modid='" . $modid . "' and routeid='" . $routeid . "' ";
        $res1 = $conn->query($sql9);
        if ($res1->num_rows > 0) {
            while ($row1 = $res1->fetch_assoc()) {
                if ($row1['isfinish'] != '3') {
                    // 检测如果还有未完成则终止脚本
                    die();
                }
            }
            if($isexternal=="1"){//外协
             	// 更新part进度为完成状态
             	 // 更新route进度为完成状态
	            $sql10 = "UPDATE route SET isfinish='1' where modid='" . $modid . "'";
	            $conn->query($sql10);
                $sql12 = "UPDATE part SET isfinish='1' where modid='" . $modid . "' and fid='" . $pid . "' ORDER by id LIMIT 1 ";
                $res2 = $conn->query($sql12);
                $workstate = '完成';
                $message = $name . "的" . $route . "已完成！";
				$sql2 = "INSERT INTO message (content,time,department,state,workstate,route,cuser,workshop,count) VALUES ('" . $message . "','" . date("Y-m-d H:i:s") . "','检验部','0','" . $workstate . "','" . $route . "','" . $writtenBy . "','" . $workshop . "','" . $finishcount . "')";
				$conn->query($sql2);
				$sql_update = "UPDATE message SET state='1' where id='" . $messageid . "' ";
				$conn->query($sql_update);
       		}
       		else{
       			 // 更新route进度为完成状态
	            $sql10 = "UPDATE route SET isfinish='1' where modid='" . $modid . "' and id='" . $routeid . "' ORDER by id LIMIT 1 ";
	            $conn->query($sql10);
       		 	// 循环检测是否所有车间完成
	            $sql11 = "SELECT isfinish from route where modid='" . $modid . "' and pid='" . $pid . "' ";
	            $res2 = $conn->query($sql11);
	            if ($res2->num_rows > 0) {
	                while ($row2 = $res2->fetch_assoc()) {
	                    if ($row2['isfinish'] != '1') {
	                        // 检测如果还有未完成则终止脚本
	                        die();
	                    }
	                }
	                // 更新part进度为完成状态
	                $sql12 = "UPDATE part SET isfinish='1' where modid='" . $modid . "' and fid='" . $pid . "' ORDER by id LIMIT 1 ";
	                $res2 = $conn->query($sql12);
	                $workstate = '完成';
		            $message = $name . "的" . $route . "已完成！";
					$sql2 = "INSERT INTO message (content,time,department,state,workstate,route,cuser,workshop,count) VALUES ('" . $message . "','" . date("Y-m-d H:i:s") . "','检验部','0','" . $workstate . "','" . $route . "','" . $writtenBy . "','" . $workshop . "','" . $finishcount . "')";
					$conn->query($sql2);
					$sql_update = "UPDATE message SET state='1' where id='" . $messageid . "' ";
					$conn->query($sql_update);
	            }
       		}
        }
        break;

    case '7'://获取完工页面的信息
        $id = $_POST["id"];
        $pid = $_POST["pid"];
        $modid = $_POST["modid"];
        $routeid = $_POST["routeid"];
        //					$id = '7406';
        //					$pid = "17";
        //					$modid = "1000634968";
        //					$routeid = "18959";
        //			$sql = "SELECT A.modid,A.figure_number,A.name,A.count,A.child_material,A.remark,B.id AS routeid,C.route,C.id,C.notNum,C.station FROM part A,route B,workshop_k C WHERE C.isfinish = '0' AND A.modid = B.modid AND B.id = C.routeid AND B.modid = C.modid ORDER BY id LIMIT 1";
        $sql = "select name,figure_number,pNumber,child_material,quantity,modid from part where id='" . $id . "' ";
        $res = $conn->query($sql);
        if ($res->num_rows > 0) {
            $i = 0;
            while ($row = $res->fetch_assoc()) {
                $arr[$i]['name'] = $row['name'];
                $arr[$i]['figure_number'] = $row['figure_number'];
                $arr[$i]['pNumber'] = $row['pNumber'];
                $arr[$i]['child_material'] = $row['child_material'];
                $arr[$i]['quantity'] = $row['quantity'];
                $i++;
            }
        }
        $sql1 = "SELECT route FROM route WHERE modid='" . $modid . "' AND pid='" . $pid . "' AND isfinish='2' ORDER by id LIMIT 1 ";
        $res1 = $conn->query($sql1);
        if ($res1->num_rows > 0) {
            $i = 0;
            while ($row1 = $res1->fetch_assoc()) {
                $arr[$i]['route'] = $row1['route'];
                //					$arr[$i]['count'] = $row1['count'];
                $i++;
            }
        }
        $sql2 = "SELECT station,remark,todocount,finishurl FROM workshop_k WHERE modid='" . $modid . "' AND routeid='" . $routeid . "' ORDER by id LIMIT 1 ";
        $res2 = $conn->query($sql2);
        if ($res2->num_rows > 0) {
            $i = 0;
            while ($row2 = $res2->fetch_assoc()) {
                $arr[$i]['station'] = $row2['station'];
                $arr[$i]['remark'] = $row2['remark'];
                $arr[$i]['todocount'] = $row2['todocount'];
                $arr[$i]['finishurl'] = $row2['finishurl'];
                $i++;
            }
        } else {
            //若workshop_k无数据跳出循环
            die();
        }
        $sql3 = "SELECT notNum FROM workshop_k WHERE modid='" . $modid . "' AND routeid='" . $routeid . "' AND isfinish!='3' ORDER by id LIMIT 1 ";
        $res3 = $conn->query($sql3);
        if ($res3->num_rows > 0) {
            $i = 0;
            while ($row3 = $res3->fetch_assoc()) {
                $arr[$i]['notNum'] = $row3['notNum'];
                $i++;
            }
        } else {
            //若workshop_k无数据跳出循环
            die();
        }
        $json = json_encode($arr);
        echo $json;
        break;

    case '8': //获取检验的信息
        $id = $_POST["id"];
        $pid = $_POST["pid"];
        $modid = $_POST["modid"];
        $routeid = $_POST["routeid"];
        $sql = "select name,figure_number,child_material,quantity,modid from part where id='" . $id . "' ";
        $res = $conn->query($sql);
        if ($res->num_rows > 0) {
            $i = 0;
            while ($row = $res->fetch_assoc()) {
                $arr[$i]['name'] = $row['name'];
                $arr[$i]['figure_number'] = $row['figure_number'];
                $arr[$i]['child_material'] = $row['child_material'];
                $arr[$i]['quantity'] = $row['quantity'];
                $i++;
            }
        }
        $sql1 = "SELECT route FROM route WHERE modid='" . $modid . "' AND pid='" . $pid . "' AND isfinish='2' ORDER by id desc LIMIT 1 ";
        $res1 = $conn->query($sql1);
        if ($res1->num_rows > 0) {
            $i = 0;
            while ($row1 = $res1->fetch_assoc()) {
                $arr[$i]['route'] = $row1['route'];
                $i++;
            }
        }
        $sql2 = "SELECT station,remark,inspectcount,unqualified,inspecturl,unqualifiedurl FROM workshop_k WHERE modid='" . $modid . "' AND routeid='" . $routeid . "' AND isfinish!='3' ORDER by id LIMIT 1 ";
        $res2 = $conn->query($sql2);
        if ($res2->num_rows > 0) {
            $i = 0;
            while ($row2 = $res2->fetch_assoc()) {
                $arr[$i]['station'] = $row2['station'];
                $arr[$i]['remark'] = $row2['remark'];
                $arr[$i]['inspectcount'] = $row2['inspectcount'];
                $arr[$i]['unqualified'] = $row2['unqualified'];
                $arr[$i]['inspecturl'] = $row2['inspecturl'];
                $arr[$i]['unqualifiedurl'] = $row2['unqualifiedurl'];
                $i++;
            }
        } else {
            //若workshop_k无数据跳出循环
            die();
        }
        $sql3 = "SELECT notNum FROM workshop_k WHERE modid='" . $modid . "' AND routeid='" . $routeid . "' AND isfinish!='3' ORDER by id LIMIT 1 ";
        $res3 = $conn->query($sql3);
        if ($res3->num_rows > 0) {
            $i = 0;
            while ($row3 = $res3->fetch_assoc()) {
                $arr[$i]['notNum'] = $row3['notNum'];
                $i++;
            }
        } else {
            //若workshop_k无数据跳出循环
            die();
        }
        $json = json_encode($arr);
        echo $json;
        break;
//不合格处理
    case '9': // 检验改变状态
        $modid = $_POST["modid"];
        $pid = $_POST["pid"];
        $routeid = $_POST["routeid"];
        $route = $_POST["route"];
        $station = $_POST["station"];
        $messageid = $_POST["messageid"];
        $name = $_POST["name"];
        $figure_number = $_POST["figure_number"];
        $unqualified = $_POST["unqualified"];
        $unqualified = $_POST["unqualified"];
        $finishcount = $_POST["finishcount"];
        $writtenBy = $_POST["writtenBy"];
        $inspect = $_POST["inspect"];
        $remark = $_POST["remark"];
        $isexterior=$_POST["isexterior"];
        $workstate = '检验';
        $message = $name . "的" . $route .  "已检验！";
        //判断route处于哪个车间
		$sql_class="SELECT workshop FROM workshop_class where route='" . $route . "'";
		$res_class = $conn->query($sql_class);
		if ($res_class->num_rows > 0) {
		    while ($row = $res_class->fetch_assoc()) {
		        $workshop = $row['workshop'];
		    }
		}
		else{
			$workshop ="无";
		}
        //返工返修
        if ($inspect === "4") {
            $unqualified = $unqualified - $finishcount;
            //外协不合格处理
            if($isexterior=="1"){
                $sql14 = "UPDATE workshop_k SET  isfinish='2' ,inspectcount=inspectcount +  '" . $finishcount . "',notNum=notNum+1,unqualified='" . $unqualified . "' WHERE modid='" . $modid . "' ";
                $conn->query($sql14);
            }
            //非外协返工处理
            else{
//          	if ($unqualified === $finishcount) {
                $sql14 = "UPDATE workshop_k SET  isfinish='2' ,todocount=todocount +  '" . $finishcount . "',notNum=notNum+1,unqualified='" . $unqualified . "' WHERE modid='" . $modid . "' and routeid='" . $routeid . "'  ORDER by id LIMIT 1";
                $conn->query($sql14);
//	            } else {
//	                $unqualified = $unqualified - $finishcount;
//	                $sql5 = "UPDATE workshop_k  SET isfinish='2' ,todocount=todocount +  '" . $finishcount . "',notNum=notNum+1 ,unqualified='" . $unqualified . "' WHERE modid='" . $modid . "' and routeid='" . $routeid . "' ORDER by id LIMIT 1";
//	                $conn->query($sql5);
//	            }
            }
            $message = $name . "的" . $route .  "返工！";
            $sql_mes = "INSERT INTO message (content,time,department,state,workstate,route,cuser,workshop,count) VALUES ('" . $message . "','" . date("Y-m-d H:i:s") . "','检验部','0','返工','" . $route . "','" . $writtenBy . "','" . $workshop . "','" . $finishcount . "')";
			$conn->query($sql_mes);
            
        }
        //进入评审，等待评审后才能继续流程
        else if ($inspect === "5") {
        	$unqualified = $unqualified - $finishcount;
        	$sql6 = "UPDATE workshop_k SET reviews=reviews + '" . $finishcount . "' ,unqualified='" . $unqualified . "'  WHERE modid='" . $modid . "' and routeid='" . $routeid . "' ORDER by id LIMIT 1";
            $conn->query($sql6);
            //			//保存数据进review
            $sql7 = "INSERT INTO review (pid,modid,routeid,name,figure_number,reviews,route,isfinish,uuser) VALUES ('" . $pid . "','" . $modid . "','" . $routeid . "','" . $name . "','" . $figure_number . "','" . $finishcount . "','" . $route . "','5','" . $writtenBy . "')";
            $conn->query($sql7);
            $message = $name . "的" . $route .  "待评审！";
            $sql_mes = "INSERT INTO message (content,time,department,state,workstate,route,cuser,workshop,count) VALUES ('" . $message . "','" . date("Y-m-d H:i:s") . "','检验部','0','待评审','" . $route . "','" . $writtenBy . "','" . $workshop . "','" . $finishcount . "')";
			$conn->query($sql_mes);
            
        }
        //报废，默认不改变完成数量，记录检查数量作为报废数量
        else if ($inspect === "6") {
            $unqualified = $unqualified - $finishcount;
            $sql8 = "UPDATE workshop_k SET  unqualified='" . $unqualified . "' ,dumping=dumping + '" . $finishcount . "' WHERE modid='" . $modid . "' and routeid='" . $routeid . "' ORDER by id LIMIT 1";
            $conn->query($sql8);
            // 检测当前零件不合格处理是否完成
			$sql_finish = "SELECT todocount ,reviews ,inspectcount,unqualified from workshop_k where modid='".$modid."' and routeid='".$routeid."'  ";
			$res_finish = $conn -> query($sql_finish);
			if ($res_finish -> num_rows > 0) {   
				while ($row = $res_finish -> fetch_assoc()) {
					if ($row['todocount'] == '0'  && $row['reviews'] == '0' && $row['inspectcount'] == '0'&& $row['unqualified'] == '0') {
						 // 更新route进度为完成状态
						 if($isexterior=="1"){
						 	$sql10 = "UPDATE route SET isfinish='1' where modid='" . $modid . "' and pid='" . $pid . "'  ";
			           		$conn->query($sql10);
			           		$sql10 = "UPDATE part SET isfinish='1' where modid='" . $modid . "' and fid='" . $pid . "' ORDER by id LIMIT 1 ";
			           		$conn->query($sql10);
						 }else{
						 	$sql10 = "UPDATE route SET isfinish='1' where modid='" . $modid . "' and id='" . $routeid . "' ORDER by id LIMIT 1 ";
			           		$conn->query($sql10);
						 }
			            
					}
				}
			}
            //			$sql19 = "INSERT INTO scrap (modid,routeid,scrapNum) VALUES ('".$modid."','".$routeid."','".$unqualified."')";
            //			$conn -> query($sql19);
            // 不合格处理
            $message = $name . "的" . $route .  "报废！";
            $sql_mes = "INSERT INTO message (content,time,department,state,workstate,route,cuser,workshop,count) VALUES ('" . $message . "','" . date("Y-m-d H:i:s") . "','检验部','0','报废','" . $route . "','" . $writtenBy . "','" . $workshop . "','" . $finishcount . "')";
			$conn->query($sql_mes);
            
        }
        // 循环检测是否所有工序完成
        $sql9 = "SELECT isfinish from workshop_k where modid='" . $modid . "' and routeid='" . $routeid . "' ";
        $res1 = $conn->query($sql9);
        if ($res1->num_rows > 0) {
            while ($row1 = $res1->fetch_assoc()) {
                if ($row1['isfinish'] != '3') {
                    // 检测如果还有未完成则终止脚本
                    die();
                }
            }
            if($isexterior=="1"){//外协
             	// 更新part进度为完成状态
             	 // 更新route进度为完成状态
	            $sql10 = "UPDATE route SET isfinish='1' where modid='" . $modid . "' and pid='" . $pid . "' ";
	            $conn->query($sql10);
                $sql12 = "UPDATE part SET isfinish='1' where modid='" . $modid . "' and fid='" . $pid . "' ORDER by id LIMIT 1 ";
                $res2 = $conn->query($sql12);
       		}
       		else{
       			 // 更新route进度为完成状态
	            $sql10 = "UPDATE route SET isfinish='1' where modid='" . $modid . "' and id='" . $routeid . "' ORDER by id LIMIT 1 ";
	            $conn->query($sql10);
	            // 循环检测是否所有车间完成
	            $sql11 = "SELECT isfinish from route where modid='" . $modid . "' and pid='" . $pid . "' ";
	            $res2 = $conn->query($sql11);
	            if ($res2->num_rows > 0) {
	                while ($row2 = $res2->fetch_assoc()) {
	                    if ($row2['isfinish'] != '1') {
	                        // 检测如果还有未完成则终止脚本
	                        die();
	                    }
	                }
	                // 更新part进度为完成状态
	                $sql12 = "UPDATE part SET isfinish='1' where modid='" . $modid . "' and fid='" . $pid . "' ORDER by id LIMIT 1 ";
	                $res2 = $conn->query($sql12);
	            }
	       	}
        }
        break;
		
		
		case '10':
		    // 获取warehouse状态
			$figure_number = $_POST["figure_number"];
			$pid = $_POST["pid"];
		    $modid = $_POST["modid"];
		    $sql = "SELECT id,pNumber,count FROM warehouse WHERE modid='" . $modid . "' AND figure_number='" . $figure_number . "' AND count != '0' ";
		    $res = $conn->query($sql);
		    if ($res->num_rows > 0) {
		        $i = 0;
		        while ($row = $res->fetch_assoc()) {
					$arr[$i]['id'] = $row['id'];
		            $arr[$i]['shiftpNumber'] = $row['pNumber'];
		            $arr[$i]['count'] = $row['count'];
		            $i++;
		        }
		    } else {
		        $i = 0;
		        //已完工
		        $arr[$i]['isfinish'] = "1";
		    }
		    $json = json_encode($arr);
		    echo $json;
		    break;
			case '11':
			    // 获取warehouse状态
				$sid = $_POST["sid"];
				$pid = $_POST["pid"];
			    $modid = $_POST["modid"];
				$finishcount = $_POST["finishcount"];
				$pNumber = $_POST["pNumber"];
				$shiftpNumber = $_POST["shiftpNumber"];
				//调单
				$sql = "UPDATE warehouse SET count=count + '" . $finishcount . "' where pNumber='" . $pNumber . "' ";
				$res = $conn->query($sql);
				//被调单
			    $sql1 = "UPDATE warehouse SET count=count - '" . $finishcount . "' where pNumber='" . $shiftpNumber . "' and id='" . $sid . "' ";
			    $res = $conn->query($sql1);
				//调单记录
				$sql2 = "INSERT INTO shiftrecord (pNumber,shiftpNumber,shiftcount,modid) VALUES ('".$pNumber."','".$shiftpNumber."','".$finishcount."','".$modid."')";
				$res = $conn->query($sql2);
			    // if ($res->num_rows > 0) {} else {}
			    // $json = json_encode($arr);
			    // echo $json;
			    break;
		    case '12':
			    // 外协直接在ws_k表增加一条数据，以便于可以进行直接检验
				$pid = $_POST["pid"];
			    $modid = $_POST["modid"];
//			    $modid = '1000604160';
			   	$sql = "select name,count from part where modid='" . $modid . "' and isfinish='0' ";
				$res = $conn->query($sql);
		        if ($res->num_rows > 0) {
		        	while ($row = $res->fetch_assoc()) {
		        	$count=$row['count'];
		        	$name=$row['name'];
		        	}
		        }
		        $sql_line = "select route_line from route where modid='" . $modid . "' limit 1";
				$res_line = $conn->query($sql_line);
		        if ($res_line->num_rows > 0) {
		        	while ($row = $res_line->fetch_assoc()) {
		        	$route_line=$row['route_line'];
		        	}
		        }
		        $time = date("Y-m-d H:i:s");
		        $sql_exist="select modid from workshop_k where modid='".$modid."'";
		        $res_exist = $conn->query($sql_exist);
		        if ($res_exist->num_rows > 0) {
		        	$sql5 = "UPDATE workshop_k SET todocount='0'  ,inspectcount= '".$count."' where modid='" . $modid . "' ";
		            $conn->query($sql5);
		            $sql6 = "UPDATE route SET isfinish='2' where modid='" . $modid . "'";
		            $conn->query($sql6);
		            $sql6 = "UPDATE part SET isfinish='2' where modid='" . $modid . "'";
		            $conn->query($sql6);
		            $data="update";
		        }
		        else{
		        	$sql_1 =  "INSERT INTO workshop_k (modid,name,isfinish,ctime,pid,route_line) VALUES ('$modid','$name', '2','$time','$pid','$route_line')";
		        	$res_1 = $conn->query($sql_1);
		        	$data="success";
		        	$sql5 = "UPDATE workshop_k SET todocount='0'  ,inspectcount= '".$count."' where modid='" . $modid . "' ";
		            $conn->query($sql5);
		            $sql6 = "UPDATE route SET isfinish='2' where modid='" . $modid . "'";
		            $conn->query($sql6);
		            $sql6 = "UPDATE part SET isfinish='2' where modid='" . $modid . "'";
		            $conn->query($sql6);
		        }
		        
//			    $sql6 = "UPDATE warehouse SET count='".$finishcount."' where pid='".$pid."' and figure_number='".$figure_number."' and pNumber='".$pNumber."' ";
//				$conn->query($sql6);
		        //更新message
		        $route='外协';
		        $message= $name . "的" . $route . "待检验";
		        $workstate = '已完工';
		        $sql_ex = "INSERT INTO message (content,time,department,state,workstate,route,workshop,cuser) VALUES ('" . $message . "','" . date("Y-m-d H:i:s") . "','外协','0','" . $workstate . "','" . $route . "','外协','外协部件')";
		        $conn->query($sql_ex);
//		        $sql2 = "UPDATE message SET state='1' where id='" . $messageid . "' ";
//		        $conn->query($sql2);
//              echo $data;
			    break;
    }
    $conn->close();
?>
