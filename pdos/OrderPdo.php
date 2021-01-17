<?php

    function isValidRestaurant($no){
        $pdo = pdoSqlConnect();
        $query = "SELECT EXISTS(SELECT * FROM Restaurant WHERE status='active' AND restaurantNo = ?) AS exist;";

        $st = $pdo->prepare($query);
        $st->execute([$no]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();
        $st=null; $pdo = null;

        return intval($res[0]["exist"]);
    }

    function getRestaurant($latFrom, $lonFrom, $no){
        $pdo = pdoSqlConnect();
        $query = "SELECT r.restaurantNo AS no, r.name, ROUND(AVG(COALESCE(rateTotal,0.0)),1) 'rateAverage', 
                    i.cleanLevel, d.isExpress, CONCAT(d.timeRequired, '분') 'timeRequired',
                    CASE
                        WHEN r.discountMinPrice = 0 THEN ''
                        ELSE CONCAT(FORMAT(r.discountMinPrice,0), '원')
                    END AS discountMinPrice,
                    CASE
                        WHEN r.discount = 0 THEN ''
                        ELSE CONCAT(FORMAT(r.discount,0), '원')
                    END AS discount,
                    CASE
                        WHEN d.orderMinPrice = 0 THEN ''
                        ELSE CONCAT(FORMAT(d.orderMinPrice,0), '원')
                    END AS orderMinPrice,
                    pw.way 'payWay',
                    CASE
                        WHEN d.charge = 0 THEN '무료'
                        ELSE CONCAT(FORMAT(d.charge,0), '원')
                    END AS deliveryCharge, 
                    CASE
                        WHEN d.discountCharge = 0 THEN ''
                        ELSE CONCAT(FORMAT(d.discountCharge,0), '원')
                    END AS discountDelivery,
                    i.bossMessage, 
                    (SELECT COUNT(*)
                    FROM yogiyo.Restaurant r INNER JOIN yogiyo.LikeRestaurant lr ON r.restaurantNo = lr.restaurantNo
                    WHERE r.status = 'active' GROUP BY r.restaurantNo HAVING r.restaurantNo = no) AS likeCount,
                    (SELECT COUNT(m.menuNo)
                    FROM yogiyo.Restaurant r LEFT OUTER JOIN yogiyo.Menu m ON r.restaurantNo = m.restaurantNo
                    WHERE r.status = 'active' GROUP BY r.restaurantNo HAVING r.restaurantNo = no) AS menuCount,
                    CASE
                        WHEN COUNT(rvw.restaurantNo) = 0 THEN 0
                        ELSE FORMAT(COUNT(rvw.restaurantNo),0)
                    END AS reviewCount,
                    (acos( sin(radians($latFrom)) * sin(radians(i.latTo)) +  cos(radians($latFrom)) * cos(radians(i.latTo)) * cos(radians($lonFrom-i.lonTo))) * 6378.137) AS distance
                FROM yogiyo.Restaurant r
                    INNER JOIN yogiyo.Delivery d ON r.restaurantNo = d.restaurantNo
                    INNER JOIN yogiyo.Information i ON r.restaurantNo = i.restaurantNo
                    INNER JOIN yogiyo.PayWay pw ON r.payWayNo = pw.payWayNo
                    LEFT OUTER JOIN yogiyo.Review rvw ON r.restaurantNo = rvw.restaurantNo
                WHERE r.status = 'active' AND (rvw.rateTotal != 0 OR rvw.rateTotal IS NULL)
                GROUP BY r.restaurantNo
                HAVING r.restaurantNo = $no;";

        $st = $pdo->prepare($query);
        $st->execute([$latFrom, $lonFrom, $no]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();

        $query = "SELECT rmi.imageSrc FROM yogiyo.Restaurant r, yogiyo.RestaurantMainImage rmi 
                WHERE rmi.status='active' AND r.restaurantNo = rmi.restaurantNo AND rmi.restaurantNo=?;";
        $st = $pdo->prepare($query);
        $st->execute([$res[0]["no"]]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res1 = $st->fetchAll();
        for($i=0; $i<sizeof($res1); $i++){
            $res[0]["topImageSrc"][$i] = $res1[$i]["imageSrc"];
        }
        if($res[0]["topImageSrc"] == NULL) $res[0]["topImageSrc"] = array();

        $query = " SELECT parentMenuNo FROM yogiyo.Restaurant r, yogiyo.ParentMenu pm 
                WHERE pm.status='active' AND r.restaurantNo = pm.restaurantNo AND pm.restaurantNo=?;";
        $st = $pdo->prepare($query);
        $st->execute([$res[0]["no"]]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res2 = $st->fetchAll();
        for($i=0; $i<sizeof($res2); $i++){
            $res[0]["menuCategoryNo"][$i] = $res2[$i]["parentMenuNo"];
        }
        if($res[0]["menuCategoryNo"] == NULL) $res[0]["menuCategoryNo"] = array();


        $st=null; $pdo = null;

        return $res[0];
    }

    function  isValidCategory($categoryNo){
        $pdo = pdoSqlConnect();
        $query = "SELECT EXISTS(SELECT * FROM yogiyo.ParentMenu WHERE status='active' AND parentMenuNo = ?) AS exist;";

        $st = $pdo->prepare($query);
        $st->execute([$categoryNo]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();
        $st=null; $pdo = null;

        return intval($res[0]["exist"]);
    }

    function matchRestAndCategory($no, $categoryNo){
        $pdo = pdoSqlConnect();
        $query = "SELECT EXISTS(SELECT * FROM yogiyo.Restaurant r, yogiyo.ParentMenu pm 
                WHERE pm.status='active' AND r.restaurantNo = pm.restaurantNo AND r.restaurantNo=? AND parentMenuNo = ?) AS exist;";
        $st = $pdo->prepare($query);
        $st->execute([$no, $categoryNo]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();
        $st=null; $pdo = null;

        return intval($res[0]["exist"]);
    }
    
    function getMenuList($no){
        $pdo = pdoSqlConnect();
        $query = " SELECT parentMenuNo FROM yogiyo.Restaurant r, yogiyo.ParentMenu pm 
                WHERE pm.status='active' AND r.restaurantNo = pm.restaurantNo AND pm.restaurantNo=?;";
        $st = $pdo->prepare($query);
        $st->execute([$no]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res= $st->fetchAll();

        for($i=0; $i<sizeof($res); $i++){
            $query = "SELECT parentMenuNo 'categoryNo', pm.name, pm.description FROM yogiyo.Restaurant r, yogiyo.ParentMenu pm 
                    WHERE pm.status='active' AND r.restaurantNo = pm.restaurantNo AND parentMenuNo = ?;";
            $st = $pdo->prepare($query);
            $st->execute([$res[$i]["parentMenuNo"]]);
            $st->setFetchMode(PDO::FETCH_ASSOC);
            $res0 = $st->fetchAll();
            $res[$i]["category"] = $res0[0];

            $query = "SELECT menuNo, m.name, m.description, CONCAT(FORMAT(m.price,0),'원') 'price', imageSrc 
                    FROM yogiyo.Restaurant r, yogiyo.Menu m
                    WHERE m.status = 'active' AND r.restaurantNo = m.restaurantNo AND r.restaurantNo = ? AND m.parentMenuNo = ?;";
            $st = $pdo->prepare($query);
            $st->execute([$no, $res[$i]["parentMenuNo"]]);
            $st->setFetchMode(PDO::FETCH_ASSOC);
            $res1 = $st->fetchAll();
            $res[$i]["menuList"] = $res1;
        }
        $result["fullMenu"] = $res;
        $st=null; $pdo = null;

        return $result;
    }

    function isValidMenu($menuNo){
        $pdo = pdoSqlConnect();
        $query = "SELECT EXISTS(SELECT * FROM yogiyo.Menu WHERE status='active' AND menuNo = ?) AS exist;";

        $st = $pdo->prepare($query);
        $st->execute([$menuNo]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();
        $st=null; $pdo = null;

        return intval($res[0]["exist"]);
    }

    function matchRestAndMenu($no, $menuNo){
        $pdo = pdoSqlConnect();
        $query = "SELECT EXISTS(SELECT * FROM yogiyo.Restaurant r, yogiyo.Menu m 
                WHERE m.status='active' AND r.restaurantNo = m.restaurantNo AND r.restaurantNo = ? AND m.menuNo = ?) AS exist;";
        $st = $pdo->prepare($query);
        $st->execute([$no, $menuNo]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();
        $st=null; $pdo = null;

        return intval($res[0]["exist"]);
    }

    function getMenuLDetail($no, $menuNo){
        $pdo = pdoSqlConnect();
        $query = "SELECT menuNo, name, description, CONCAT(FORMAT(price,0),'원') 'price', imageSrc
                FROM yogiyo.Menu WHERE status = 'active' AND restaurantNo = ? AND menuNo = ?;";
        $st = $pdo->prepare($query);
        $st->execute([$no, $menuNo]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();

        $query = "SELECT 
                    CASE
                        WHEN COUNT(*) = 0 THEN ''
                        ELSE CONCAT('리뷰보기 (', COUNT(*), ')')
                    END 'reviewCount'
                FROM yogiyo.Review r, yogiyo.OrderList ol
                WHERE r.status = 'active' AND ol.status = 'active' 
                    AND r.orderCode = ol.orderCode AND ol.menuNo = ?
                GROUP BY ol.menuNo;";
        $st = $pdo->prepare($query);
        $st->execute([$menuNo]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res1 = $st->fetchAll();
        if($res1[0]["reviewCount"] == NULL) $res1[0]["reviewCount"] = '';
        $res[0]["reviewCount"] = $res1[0]["reviewCount"];

        //하위메뉴 개별옵션
        $query = "SELECT menuOptionNo 'optionNo', optionTitle 'title', optionMust 'must' 
                FROM Menu m, MenuOption mo
                WHERE mo.status='active' AND m.menuNo = mo.menuNo AND m.menuNo = ?;";
        $st = $pdo->prepare($query);
        $st->execute([$menuNo]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res4 = $st->fetchAll();
        $res[0]["optionList"] = $res4;

        for($i=0; $i<sizeof($res4); $i++){    
            $query = "SELECT ml.optionName 'name', 
                        CASE 
                            WHEN ml.optionPrice = 0 THEN '추가비용없음'
                            ELSE CONCAT('+', ml.optionPrice, '원')
                        END 'price'
                    FROM yogiyo.Menu m, yogiyo.MenuOption mo, yogiyo.MenuOptionList ml
                    WHERE mo.status='active' AND m.menuNo = mo.menuNo
                        AND mo.menuOptionNo = ml.menuOptionNo AND m.menuNo = ? AND ml.menuOptionNo = ?;";
            $st = $pdo->prepare($query);
            $st->execute([$menuNo, $res4[$i]["optionNo"]]);
            $st->setFetchMode(PDO::FETCH_ASSOC);
            $res5 = $st->fetchAll();
            for($j=0; $j<sizeof($res5); $j++){
                $res[0]["optionList"][$i]["options"][$j] = $res5[$j];
            }
        }

        // 상위메뉴 공통옵션
        $query = "SELECT parentOptionNo 'optionNo', optionTitle 'title', optionMust 'must' 
                FROM Menu m, ParentMenuOption po
                WHERE po.status='active' AND m.parentMenuNo = po.parentMenuNo AND m.menuNo = ?;";
        $st = $pdo->prepare($query);
        $st->execute([$menuNo]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res2 = $st->fetchAll();

        for($i=0; $i<sizeof($res2); $i++){
            $query = "SELECT pl.optionName 'name', 
                        CASE 
                            WHEN pl.optionPrice = 0 THEN '추가비용없음'
                            ELSE CONCAT('+', pl.optionPrice, '원')
                        END 'price'
                    FROM yogiyo.Menu m, yogiyo.ParentMenuOption po, yogiyo.ParentMenuOptionList pl
                    WHERE po.status='active' AND m.parentMenuNo = po.parentMenuNo 
                        AND po.parentOptionNo = pl.parentOptionNo AND m.menuNo = ? AND pl.parentOptionNo = ?;";
            $st = $pdo->prepare($query);
            $st->execute([$menuNo, $res2[$i]["optionNo"]]);
            $st->setFetchMode(PDO::FETCH_ASSOC);
            $res3 = $st->fetchAll();
            for($j=0; $j<sizeof($res3); $j++){
                $res2[$i]["options"][$j] = $res3[$j];
            }
            array_push($res[0]["optionList"], $res2[$i]);
        }
        $st=null; $pdo = null;

        return $res[0];
    }

    function satsfyMinimum($no, $sum){
        $pdo = pdoSqlConnect();
        $query = "SELECT CONCAT(FORMAT(d.orderMinPrice,0),'원') 'orderMinPrice',
                    CASE
                        WHEN $sum > d.orderMinPrice THEN 'Y'
                        WHEN $sum = d.orderMinPrice THEN 'Y'
                        WHEN $sum < d.orderMinPrice THEN 'N'
                    END 'satisfty',
                    r.mustOverMin, 
                    CASE
                        WHEN (d.orderMinPrice-$sum) < 0 THEN ''
                        ELSE CONCAT(FORMAT(d.orderMinPrice-$sum,0),'원')
                    END 'extraCharge'
                    FROM yogiyo.Restaurant r, yogiyo.Delivery d 
                    WHERE r.status = 'active' AND d.status='active' AND r.restaurantNo = d.restaurantNo  AND r.restaurantNo = $no;";
        $st = $pdo->prepare($query);
        $st->execute([$no, $sum]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();
        $st=null; $pdo = null;

        return $res[0];
    }

    function getOrderWays(){
        $pdo = pdoSqlConnect();
        $query = "SELECT orderWayNo, way FROM yogiyo.OrderWay WHERE status='active';";
        $st = $pdo->prepare($query);
        $st->execute();
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();
        $st=null; $pdo = null;

        return $res;
    }

    function createOrder($allOrderCount, $email, $restaurantNo, $sum, $deliveryCharge, $extraCharge, $discount, $totalCost, $orderWayNo, $payWayName, $address, $addressDetail, $request1, $request2, $request3, $phone, $isSafeNumber){
        $pdo = pdoSqlConnect();
        $query = "SELECT 
                    CASE 
                        CHAR_LENGTH(d.timeRequired)-CHAR_LENGTH(REPLACE(d.timeRequired,'~',''))+1
                        WHEN 2 THEN SUBSTRING_INDEX(d.timeRequired, '~', 1)
                        WHEN 1 THEN d.timeRequired
                    END 'timeRequired'
                FROM yogiyo.Restaurant r, yogiyo.Delivery d
                WHERE r.status = 'active' AND d.status = 'active' AND r.restaurantNo = d.restaurantNo AND r.restaurantNo = ?;";
        $st = $pdo->prepare($query);
        $st->execute([$restaurantNo]);
        $st->setFetchMode(PDO::FETCH_ASSOC);
        $res = $st->fetchAll();


        $query = "INSERT INTO yogiyo.Order (orderCode, date, userEmail, restaurantNo, sum, deliveryCharge, extraCharge, discount, totalCost, orderWayNo, payWayName, address, addressDetail, request1, request2, request3, phone, isSafeNumber, expectedArrival) VALUES (CONCAT((SELECT DATE_FORMAT(NOW(),'%y%m%d-%H') AS DATE FROM DUAL),'-',?), NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, (SELECT DATE_ADD(NOW(), INTERVAL ? MINUTE)));";

        $st = $pdo->prepare($query);
        $st->execute([$allOrderCount, $email, $restaurantNo, $sum, $deliveryCharge, $extraCharge, $discount, $totalCost, $orderWayNo, $payWayName, $address, $addressDetail, $request1, $request2, $request3, $phone, $isSafeNumber, $res[0]["timeRequired"]]);

        $st = null; $pdo = null;
    }

    // function createOrderList(){
    //     $query="SELECT * FROM yogiyo.Order ORDER BY date DESC LIMIT 1;";
    //     $st = $pdo->prepare($query);
    //     $st->execute();
    //     $st->setFetchMode(PDO::FETCH_ASSOC);
    //     $res = $st->fetchAll();

    //     $query = "INSERT INTO `yogiyo`.`OrderList` (`orderCode`, `date`, `userEmail`, `menuNo`, `optionName`, `count`, `price`) VALUES (?, 'z', 'z', 'z', 'z', 'z', 'z');";
    //     $st = $pdo->prepare($query);
    //     $st->execute([$res[0]["orderCode"]], $res[0]["date"], $res[0]["userEmail"], );
    // }