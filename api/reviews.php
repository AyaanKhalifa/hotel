<?php
error_reporting(0);
require_once dirname(__DIR__).'/includes/config.php';
require_once dirname(__DIR__).'/includes/db.php';
header('Content-Type: application/json; charset=utf-8');

$action = clean($_POST['action'] ?? $_GET['action'] ?? 'get');

function timeAgo(string $d):string{$s=time()-strtotime($d);if($s<60)return 'just now';if($s<3600)return floor($s/60).'m ago';if($s<86400)return floor($s/3600).'h ago';if($s<2592000)return floor($s/86400).'d ago';return date('d M Y',strtotime($d));}

switch($action){
    case 'get':
        $rtId=(int)($_GET['room_type_id']??$_POST['room_type_id']??0);
        $page=max(1,(int)($_GET['page']??1));
        $pp=6;$off=($page-1)*$pp;
        $sort=clean($_GET['sort']??'recent');
        if(!$rtId){echo json_encode(['error'=>'Missing room_type_id']);exit;}
        $ob=['helpful'=>'helpful_count DESC,rr.created_at DESC','highest'=>'rr.rating DESC','lowest'=>'rr.rating ASC'][$sort] ?? 'rr.created_at DESC';
        $tot=$pdo->prepare("SELECT COUNT(*) FROM room_ratings WHERE room_type_id=? AND is_approved=1");
        $tot->execute([$rtId]);$total=(int)$tot->fetchColumn();
        $stmt=$pdo->prepare("SELECT rr.*,u.name as udname FROM room_ratings rr LEFT JOIN users u ON rr.user_id=u.id WHERE rr.room_type_id=? AND rr.is_approved=1 ORDER BY $ob LIMIT ? OFFSET ?");
        $stmt->execute([$rtId,$pp,$off]);$reviews=$stmt->fetchAll();
        // fetch media for each review
        foreach($reviews as &$r){
            $mstmt=$pdo->prepare("SELECT id,type,url,filename FROM review_media WHERE review_id=?");
            try{$mstmt->execute([$r['id']]);$r['media']=$mstmt->fetchAll();}catch(Exception $e){$r['media']=[];}
            $r['display_name']=$r['guest_name']??$r['udname']??'Anonymous';
            $r['avatar_char']=strtoupper(substr($r['display_name'],0,1));
            $r['time_ago']=timeAgo($r['created_at']);
        }
        $dist=$pdo->prepare("SELECT rating,COUNT(*) as cnt FROM room_ratings WHERE room_type_id=? AND is_approved=1 GROUP BY rating");
        $dist->execute([$rtId]);$distribution=array_fill(1,5,0);
        foreach($dist->fetchAll() as $d)$distribution[$d['rating']]=(int)$d['cnt'];
        $avg=$pdo->prepare("SELECT AVG(rating),COUNT(*) FROM room_ratings WHERE room_type_id=? AND is_approved=1");
        $avg->execute([$rtId]);[$avgR,$cnt]=$avg->fetch(PDO::FETCH_NUM);
        echo json_encode(['reviews'=>$reviews,'avg'=>round($avgR??0,1),'count'=>(int)$cnt,'total'=>$total,'pages'=>ceil($total/$pp),'distribution'=>$distribution]);
        break;

    case 'submit':
        if(!isLoggedIn()){echo json_encode(['error'=>'Login required']);exit;}
        $rtId=(int)($_POST['room_type_id']??0);$rating=(int)($_POST['rating']??0);
        $title=clean(trim($_POST['title']??''));$review=clean(trim($_POST['review']??''));
        $uid=(int)$_SESSION['user_id'];
        if(!$rtId||$rating<1||$rating>5){echo json_encode(['error'=>'Please select a room and rating']);exit;}
        if(strlen($review)<20){echo json_encode(['error'=>'Please write at least 20 characters']);exit;}
        $isVerified=0;$bookingRef=null;$guestName=$_SESSION['username']??'Guest';
        $bk=$pdo->prepare("SELECT b.booking_ref FROM bookings b JOIN booked_rooms br ON br.booking_ref=b.booking_ref WHERE b.user_id=? AND br.room_type_id=? AND b.status IN ('confirmed','checked_out') LIMIT 1");
        $bk->execute([$uid,$rtId]);$bkRow=$bk->fetch();
        if($bkRow){$isVerified=1;$bookingRef=$bkRow['booking_ref'];}
        $pdo->prepare("INSERT INTO room_ratings (room_type_id,user_id,booking_ref,guest_name,rating,title,review,is_verified,is_approved) VALUES (?,?,?,?,?,?,?,?,1)")
            ->execute([$rtId,$uid,$bookingRef,$guestName,$rating,$title?:null,$review,$isVerified]);
        $reviewId=(int)$pdo->lastInsertId();
        // Handle media uploads
        $mediaUploaded=0;
        if(!empty($_FILES['media']['name'][0])){
            $uploadDir=dirname(__DIR__).'/uploads/reviews/media/';
            if(!is_dir($uploadDir))@mkdir($uploadDir,0755,true);
            foreach($_FILES['media']['name'] as $k=>$fname){
                if($_FILES['media']['error'][$k]!==UPLOAD_ERR_OK)continue;
                $ext=strtolower(pathinfo($fname,PATHINFO_EXTENSION));
                $isVideo=in_array($ext,['mp4','mov','webm','avi']);
                $isImage=in_array($ext,['jpg','jpeg','png','webp','gif']);
                if(!$isVideo&&!$isImage)continue;
                if($_FILES['media']['size'][$k]>50*1024*1024)continue; // 50MB max
                $newName='rev_'.$reviewId.'_'.$k.'_'.time().'.'.$ext;
                if(move_uploaded_file($_FILES['media']['tmp_name'][$k],$uploadDir.$newName)){
                    $url=BASE.'/uploads/reviews/media/'.$newName;
                    $type=$isVideo?'video':'image';
                    $pdo->prepare("INSERT INTO review_media (review_id,type,filename,url) VALUES (?,?,?,?)")->execute([$reviewId,$type,$newName,$url]);
                    $mediaUploaded++;
                }
            }
        }
        $pdo->prepare("UPDATE room_types SET avg_rating=(SELECT AVG(rating) FROM room_ratings WHERE room_type_id=? AND is_approved=1),review_count=(SELECT COUNT(*) FROM room_ratings WHERE room_type_id=? AND is_approved=1) WHERE id=?")->execute([$rtId,$rtId,$rtId]);
        // Loyalty bonus
        try{$pdo->prepare("INSERT IGNORE INTO loyalty_points (user_id,total_points,lifetime_points) VALUES (?,0,0)")->execute([$uid]);$pdo->prepare("UPDATE loyalty_points SET total_points=total_points+50,lifetime_points=lifetime_points+50 WHERE user_id=?")->execute([$uid]);}catch(Exception $e){}
        echo json_encode(['success'=>true,'is_verified'=>$isVerified,'media_uploaded'=>$mediaUploaded,'message'=>$isVerified?'Review published! +50 loyalty points earned.':'Review submitted successfully! +50 pts earned.']);
        break;

    case 'helpful':
        $rid=(int)($_POST['review_id']??0);
        if($rid){$pdo->prepare("UPDATE room_ratings SET helpful_count=COALESCE(helpful_count,0)+1 WHERE id=?")->execute([$rid]);echo json_encode(['success'=>true]);}
        break;

    default: echo json_encode(['error'=>'Unknown action']);
}
?>