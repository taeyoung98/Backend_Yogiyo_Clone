<?php
require 'function.php';

const JWT_SECRET_KEY = "TEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEYTEST_KEY";

$res = (Object)Array();
header('Content-Type: json');
$req = json_decode(file_get_contents("php://input"));
$allOrderCount = 7;

try {    
    addAccessLogs($accessLogs, $req);
    switch ($handler) {
        
        /*
         * API No. 21
         * API Name : 음식점 상세 조회 API
         * 마지막 수정 날짜 : 21.01.13
         */
        case "getRestaurant":
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];
            $no = $vars["no"];

            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->isSuccess = FALSE;
                $res->code = 2007;
                $res->message = "유효하지 않은 토큰입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            $data = getDataByJWToken($jwt, JWT_SECRET_KEY);
            $email = $data->id;

            $array = getLatLon($email);
            $latFrom = $array[0];
            $lonFrom = $array[1];
            // 공백
            if(empty($no)) {
                $res->isSuccess = FALSE;
                $res->code = 0;
                $res->message = "공백이 입력됐습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if(!isValidRestaurant($no)){
                $res->isSuccess = FALSE;
                $res->code = 2022;
                $res->message = "존재하지 않는 가게입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            $result = getRestaurant($latFrom, $lonFrom, $no);
            if($result["distance"] > 3){
                $res->isSuccess = FALSE;
                $res->code = 2023;
                $res->message = "현재 위치에서 주문 가능한 음식점이 아닙니다. 주문 가능한 레스토랑을 확인해주세요.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            http_response_code(200);
            $res->result = $result;
            $res->isSuccess = TRUE;
            $res->code = 1000;
            $res->message = "음식점{$no} 상세 조회";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
         * API No. 22 
         * API Name : 메뉴 분류별 목록 조회 API
         * 마지막 수정 날짜 : 21.01.13
         */
        case "getMenuList":
            $no = $vars["no"];
            // 공백
            if(empty($no)) {
                $res->isSuccess = FALSE;
                $res->code = 0;
                $res->message = "공백이 입력됐습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if(!isValidRestaurant($no)){
                $res->isSuccess = FALSE;
                $res->code = 2022;
                $res->message = "존재하지 않는 가게입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            http_response_code(200);
            $res->result = getMenuList($no);
            $res->isSuccess = TRUE;
            $res->code = 1000;
            $res->message = "음식점{$no} 메뉴 전체 목록 조회";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;


        /*
         * API No. 23
         * API Name : 메뉴 상세 조회 API
         * 마지막 수정 날짜 : 21.01.13
         */
        case "getMenuDetail":
            $no = $vars["no"];
            $menuNo = $vars["menuNo"];
            // 공백
            if(empty($no)||empty($menuNo)) {
                $res->isSuccess = FALSE;
                $res->code = 0;
                $res->message = "공백이 입력됐습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if(!isValidRestaurant($no)){
                $res->isSuccess = FALSE;
                $res->code = 2022;
                $res->message = "존재하지 않는 가게입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            if(!isValidMenu($menuNo)){ 
                $res->isSuccess = FALSE;
                $res->code = 2024;
                $res->message = "존재하지 않는 메뉴입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            if(!matchRestAndMenu($no, $menuNo)){ // 가게와 하위메뉴가 매치하지 않으면
                $res->isSuccess = FALSE;
                $res->code = 2025;
                $res->message = "음식점과 메뉴가 일치하지 않습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }

            http_response_code(200);
            $res->result = getMenuLDetail($no, $menuNo);
            $res->isSuccess = TRUE;
            $res->code = 1000;
            $res->message = "메뉴{$menuNo} 상세 조회";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
         * API No. 24
         * API Name : 메뉴별 리뷰 목록 조회 API
         * 마지막 수정 날짜 : 21.01.12
         */
        case "getMenuReview":
           
            // http_response_code(200);
            // $res->result = ;
            // $res->isSuccess = TRUE;
            // $res->code = 1000;
            // $res->message = "";
            // echo json_encode($res, JSON_NUMERIC_CHECK);
            // break;

        /*
         * API No. 25
         * API Name : 최소주문금액 만족 여부 API
         * 마지막 수정 날짜 : 21.01.14
         */
        case "satisfyMinimum":
            $no = $vars["no"];
            $sum = $vars["sum"];
            // 공백
            if(empty($no)||empty($sum)) {
                $res->isSuccess = FALSE;
                $res->code = 0;
                $res->message = "공백이 입력됐습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if(!isValidRestaurant($no)){
                $res->isSuccess = FALSE;
                $res->code = 2022;
                $res->message = "존재하지 않는 가게입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            http_response_code(200);
            $res->result = satsfyMinimum($no, $sum);
            $res->isSuccess = TRUE;
            $res->code = 1000;
            $res->message = "최소주문 만족 여부 확인";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
         * API No. 26
         * API Name : 주문 방식 조회 API
         * 마지막 수정 날짜 : 21.01.17
         */
        case "getOrderWays":
            http_response_code(200);
            $res->result = getOrderWays();
            $res->isSuccess = TRUE;
            $res->code = 1000;
            $res->message = "주문 방식 조회";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            break;

        /*
         * API No. 27
         * API Name : 배달 주문 결제 API
         * 마지막 수정 날짜 : 21.01.17
         */
        case "order":
            $jwt = $_SERVER["HTTP_X_ACCESS_TOKEN"];

            if (!isValidHeader($jwt, JWT_SECRET_KEY)) {
                $res->isSuccess = FALSE;
                $res->code = 2007;
                $res->message = "유효하지 않은 토큰입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            $data = getDataByJWToken($jwt, JWT_SECRET_KEY);
            $email = $data->id;
            
            $restaurantNo = $req->restaurantNo;
            $sum = $req->sum;
            $deliveryCharge = $req->deliveryCharge;
            $extraCharge = $req->extraCharge;
            $discount = $req->discount;
            $totalCost = $req->totalCost;
            $orderWayNo = $req->orderWayNo;
            $payWayName = $req->payWayName;
            $address = $req->address;
            $addressDetail = $req->addressDetail;
            $request1 = $req->request1;
            $request2 = $req->request2;
            $request3 = $req->request3;
            $phone = $req->phone;
            $isSafeNumber = $req->isSafeNumber;

            // $optionName = $req->optionName;
            // $count = $req->count;
            // $price = $req->price;

            // 공백
            if(empty($email)||empty($restaurantNo)||empty($sum)||empty($totalCost)||empty($orderWayNo)||empty($payWayName)||empty($address)||empty($addressDetail)||empty($phone)||empty($isSafeNumber)){
                $res->isSuccess = FALSE;
                $res->code = 0;
                $res->message = "공백이 입력됐습니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                addErrorLogs($errorLogs, $res, $req);
                return;
            }
            if(empty($deliveryCharge)) $deliveryCharge = 0;
            if(empty($extraCharge)) $extraCharge = 0;
            if(empty($discount)) $discount = 0;
            if(empty($request1)) $request1 = "";
            if(empty($request2)) $request2 = "";
            if(empty($request3)) $request3 = "";

            if(!isValidRestaurant($restaurantNo)){
                $res->isSuccess = FALSE;
                $res->code = 2022;
                $res->message = "존재하지 않는 가게입니다.";
                echo json_encode($res, JSON_NUMERIC_CHECK);
                return;
            }
            
            http_response_code(201);
            createOrder($allOrderCount, $email, $restaurantNo, $sum, $deliveryCharge, $extraCharge, $discount, $totalCost, $orderWayNo, $payWayName, $address, $addressDetail, $request1, $request2, $request3, $phone, $isSafeNumber);
            $res->isSuccess = TRUE;
            $res->code = 1000;
            $res->message = "주문 결제 완료";
            echo json_encode($res, JSON_NUMERIC_CHECK);
            $allOrderCount++;
            break;
        
        /*
         * API No. 34
         * API Name : 배달 상태 변경 API (매번 update)
         * 마지막 수정 날짜 : 21.01.12
         */
        case "updateOrderState":

            // http_response_code(201);
            // $res->result = ;
            // $res->isSuccess = TRUE;
            // $res->code = 1000;
            // $res->message = "";
            // echo json_encode($res, JSON_NUMERIC_CHECK);
            // break;

    
        /*
         * API No. 35
         * API Name : 주문방식별 주문내역 목록 조회 API
         * 마지막 수정 날짜 : 21.01.12
         */
        case "getOrderList":
            // http_response_code(200);
            // $res->result = ;
            // $res->isSuccess = TRUE;
            // $res->code = 1000;
            // $res->message = "";
            // echo json_encode($res, JSON_NUMERIC_CHECK);
            // break;

        /*
         * API No. 36, 38
         * API Name : 주문내역 상세 조회 API, 주문내역 재주문 API (메뉴만 그대로 response)
         * 마지막 수정 날짜 : 21.01.12
         */
        case "getOrderDetail":
            // http_response_code(200);
            // $res->result = ;
            // $res->isSuccess = TRUE;
            // $res->code = 1000;
            // $res->message = "";
            // echo json_encode($res, JSON_NUMERIC_CHECK);
            // break;

        /*
         * API No. 37
         * API Name : 주문내역 취소 API
         * 마지막 수정 날짜 : 21.01.12
         */
        case "cancelOrder":
        //    // http_response_code(200);
            // $res->result = ;
            // $res->isSuccess = TRUE;
            // $res->code = 1000;
            // $res->message = "";
            // echo json_encode($res, JSON_NUMERIC_CHECK);
            // break;
    }
} catch (\Exception $e) {
    return getSQLErrorException($errorLogs, $e, $req);
}
