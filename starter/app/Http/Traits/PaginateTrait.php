<?php

namespace App\Http\Traits;
use Illuminate\Support\Collection;
Trait  PaginateTrait
{
    //===================  ApiResponse ===========================
    private function apiResponse($object = '',$message = 'success',$type='object',$code = 200,$stopSort = false,$status = 200,$orderByColumn = 'id',$extraFunction = null)
    {
        if ($type=='simple'){
            return response()->json(['data'=>$object,'message'=>$message,'code'=>intval($code)],$code==200?$status:$code);
        }
        if ($type=='web'){
            return response()->json(['data'=>$object,'message'=>$message,'code'=>intval($code)],$status);
        }
        $page = request()->get('page');
        $skip = request()->get('skip')??10;
        $orderBy = request()->get('orderBy');
        $seed = request()->get('seed')??0;

//        $random = request()->get('random')??false;

        if (!isset($orderBy) || !in_array($orderBy, ['asc', 'desc'])) {
            $orderBy = 'desc';
        }
        $by = request()->get('orderWith')??$orderByColumn;
        if ($object instanceof Collection) {

            $data = $object;
        } else {
            if($seed==0&&!$stopSort){

                $data = $object->orderBy($by, $orderBy)->get();
            }
            if($stopSort){
                $data = $object->get();
            }
        }


        if(!$stopSort){
            if($seed!=0){
                $data = $object->orderByRaw("RAND($seed)")->get();
            }else{
                if($orderBy=='asc'){
                    $data = $data->sortBy($by);
                }else{
                    $data = $data->sortByDesc($by);
                }
            }
        }

        if(!$stopSort){
            $data = $data->values();
        }

        $total = $data->count();
        $pages_count = ceil($total / $skip);

        if (isset($page)&&$page) {
            if (!($page != '' && is_numeric($page) && $page > 0)) {
                $page = 1;
            }
            $page --;
            $data = $data->skip($page*$skip)->take($skip);
            $data = $data->values();

        }
        if ($extraFunction) {
            call_user_func($extraFunction, $data);
        }

        $json = collect(['data'=>$data,"message" => $message,"code" => intval($code),"total"=>$total,'pages_count'=>$pages_count]); ;
        return response()->json($json,$status);
    }
}
