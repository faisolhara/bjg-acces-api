<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\AccessControl;

class AuthController extends Controller{

    public function login(Request $request){
        $username = strtoupper($request->get('username', 'null'));
        $password    = $request->get('password', 'null');

        $data = \DB::select(\DB::raw(
            "select fu.user_id, fu.user_name, ppf.full_name, heav.vc_emp_code, apps.fnd_web_sec.validate_login ('$username', '$password') as user_valid
            from
              fnd_user fu  
              left join per_people_f ppf
                on fu.employee_id = ppf.person_id 
                   and to_char(sysdate,'YYYYMMDD') between to_char(ppf.effective_start_date,'YYYYMMDD') and to_char(ppf.effective_end_date,'YYYYMMDD') 
               left join hd_employee_all_v heav
                 on fu.user_name = heav.initial_name
            where
              fu.end_date is null
              and upper(user_name) = upper(trim('$username'))
        "));

        return response()->json($data);
    }

    public function checkAccessControl(Request $request){
        $username  = strtoupper($request->get('username', 'null'));
        $resource  = $request->get('resource', 'null');
        $privilege = $request->get('privilege', 'null');

        $data = \DB::select(\DB::raw(
            "select count(*) as count from apps.bjg_access_control
                where username = '$username'
                and resources = '$resource'
                and access_control = '$privilege'
        "));
        if($data[0]->count > 0){
          return response()->json(1);
        }

        return response()->json(0);
    }

    public function canAccess(Request $request){
        $username  = strtoupper($request->get('username', 'null'));
        $resource  = $request->get('resource', 'null');
        $privilege = $request->get('privilege', 'null');

        $data = \DB::select(\DB::raw(
            "select resources, access_control, username from apps.bjg_access_control
                where username = '$username'
        "));

        foreach ($data as $accessControl) {
            if($accessControl->resources == $resource && $accessControl->access_control == $privilege){
              return response()->json(1);
            }
        }
        return response()->json(0);
    }

    public function getUser(Request $request){
        $username = strtoupper($request->get('username', 'null'));
        $name     = strtoupper($request->get('name', 'null'));

        $sql = "select fu.user_id, fu.user_name, ppf.full_name, heav.vc_emp_code
            from
              fnd_user fu  
              left join per_people_f ppf
                on fu.employee_id = ppf.person_id 
                   and to_char(sysdate,'YYYYMMDD') between to_char(ppf.effective_start_date,'YYYYMMDD') and to_char(ppf.effective_end_date,'YYYYMMDD') 
               left join hd_employee_all_v heav
                 on fu.user_name = heav.initial_name
            where
              fu.end_date is null";

        if(!empty($request->get('username'))){
          $sql .= " and fu.user_name = '$username'";
        }

        if(!empty($request->get('name'))){
          $sql .= " and upper(ppf.full_name) like '%$name%'";
        }

        $data = \DB::select(\DB::raw($sql));

        return response()->json($data);
    }

    public function saveAccessControl(Request $request){
        $username = strtoupper($request->get('username', 'null'));

        $model = AccessControl::where('username', $username)->get();

        foreach ($model as $model) {
            $model->delete();
        }
        
        foreach ($request->get('privileges') as $key => $resource) {
          foreach ($resource as $key2 => $privilege) {
            $model = new AccessControl();
            $model->bjg_access_control_id  = \DB::getSequence()->nextValue('bjg_access_control_seq');
            $model->username       = $username;
            $model->resources      = $key;
            $model->access_control = $key2;
            $model->save();
          }
        }

        return response()->json($model);
    }
}
