<?php
	require("../conn.php");
//	header("Access-Control-Allow-Origin: *"); // 允许任意域名发起的跨域请求
	class Alteration{  
	    public $name;  
	    public $value;  
	}  
	$fid = isset($_POST["fid"])?$_POST["fid"]:'';
	$data = array();
	$sql = "select isfinish,count(name) as count from part WHERE fid = '$fid' and isexterior='0' group by isfinish";
	$res=$conn->query($sql);
	if($res->num_rows>0){
		while($row=$res->fetch_assoc()){
			if($row['isfinish']=='0'){
				$row['isfinish'] = "未开工";
			}elseif($row['isfinish']=='1'){
				$row['isfinish'] = "已完成";
			}else{
				$row['isfinish'] = "已就工";
			}
			$alter = new Alteration();
			$alter->name = $row['isfinish'];
			$alter->value = intval($row['count']);  
			$data[] = $alter;
		}
	}
	
	$sql = "select count(name) as count from part WHERE fid = '$fid' and isexterior in ('1','2','3')";
	$res=$conn->query($sql);
	if($res->num_rows>0){
		while($row=$res->fetch_assoc()){
			$row['isfinish'] = "外协";
			$alter = new Alteration();
			$alter->name = $row['isfinish'];
			$alter->value = intval($row['count']);  
			$data[] = $alter;
		}
	}

	echo json_encode($data);
?>