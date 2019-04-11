<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class BirthdayController extends Controller{

    public function getBirthday(Request $request){
        $userName      = $request->get('userName', 'null');

        $data = \DB::select(\DB::raw(
                "select  vc_initial_name, vc_emp_name, dt_birthday,
                        case
                        when to_date(to_char(sysdate,'YYYY') || to_char(dt_birthday,'MMDD') || '235959','YYYYMMDDHH24MISS') - sysdate >= 0 then
                          ceil(to_date(to_char(sysdate,'YYYY') || to_char(dt_birthday,'MMDD'),'YYYYMMDD') - sysdate)
                        else
                          ceil(to_date(to_char(sysdate + numtoyminterval(1,'year'),'YYYY') || to_char(dt_birthday,'MMDD'),'YYYYMMDDHH24MISS') - sysdate)
                        end hari,
                        case
                        when to_date(to_char(sysdate,'YYYY') || to_char(dt_birthday,'MMDD') || '235959','YYYYMMDDHH24MISS') - sysdate >= 0 then
                          to_char(sysdate,'YYYY') - to_char(dt_birthday,'YYYY')
                        else
                          to_char(sysdate + numtoyminterval(1,'year'),'YYYY') - to_char(dt_birthday,'YYYY')
                        end umur        
                from    absen.employee_all_v@to_midori
                where   vc_initial_name not in ('USE') 
                        and vc_initial_name is not null
                        and vc_dept_code in
                        (select  vc_dept_code
                         from    hd_employee_all_v
                         where   initial_name = '$userName'
                        )
                order by 4        
      
                "));

        return response()->json($data);
    }
}
