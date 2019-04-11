<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class AbsenceController extends Controller{

const TERLAMBAT   = 'T';
const PULANG_AWAL = 'PA';
const SAKIT       = 'S';
const IJIN        = 'IJIN';


    public function getAbsence(Request $request){
        $empCode      = $request->get('empCode', 'null');
        $startDate    = $request->get('startDate', 'null');
        $endDate      = $request->get('endDate', 'null');

        $data = \DB::select(\DB::raw(
                    "select  vc_emp_code, vc_emp_name, dt_joind_date, vc_initial_name,
                            absen.get_total_absen@to_midori(vc_emp_code, 'T', '$startDate','$endDate') as terlambat,
                            absen.get_total_absen@to_midori(vc_emp_code, 'PA', '$startDate','$endDate') as pulang_awal,
                            absen.get_total_absen@to_midori(vc_emp_code, 'IJIN', '$startDate','$endDate') as ijin,
                            absen.get_total_absen@to_midori(vc_emp_code, 'S', '$startDate','$endDate') 
                            + absen.get_total_absen@to_midori(vc_emp_code, 'SD', '$startDate','$endDate') as sakit
                    from    hrd.hd_employee@to_midori
                    where   dt_resign_date is null
                            and vc_emp_code in
                            (
                                select  he.vc_emp_code
                                from    hrd.hd_employee@to_midori he
                                        join hrd.dt_employee@to_midori de
                                          on he.vc_emp_code = de.vc_emp_code
                                             and de.vc_status2 = 'ACTIVE'
                                             and (de.vc_assessor1 = '$empCode'
                                                  or de.vc_assessor2 = '$empCode')          
                                where   he.dt_resign_date is null
                                union
                                select  eav.vc_emp_code
                                from    hrd.mst_department@to_midori md
                                        join absen.employee_all_v@to_midori eav
                                          on md.vc_dept_code = eav.vc_dept_code                         
                                where   md.ch_active = 'Y'
                                        and (md.vc_dept_head1 = '$empCode' 
                                             or md.vc_dept_head2 = '$empCode')
                                union
                                select  he.vc_emp_code
                                from    hrd.hd_employee@to_midori he
                                where   he.vc_emp_code = '$empCode' 
                            )
                    order by vc_emp_name
                "));

        return response()->json($data);
    }

    public function getAbsenceDetail(Request $request){
        $empCode    = $request->get('empCode', 'null');
        $category   = $request->get('category', 'null');
        $startDate   = $request->get('startDate', 'null');
        $endDate   = $request->get('endDate', 'null');

        $header = \DB::select(\DB::raw(
                    "select  vc_emp_code, vc_emp_name, dt_joind_date, initial_name, dt_birthday, vc_email 
                    from    hd_employee_all_v
                    where   vc_emp_code = '$empCode'
                "));

        $lines  = \DB::select(\DB::raw(
                    "select  ioc.tanggal, to_char(ioc.tanggal,'Day') as hari, 
                            ioc.vc_start_time as shift_start, ioc.vc_end_time as shift_end, ioc.checkin, ioc.checkout,
                            case
                            when upper('$category') = 'T' then
                              'Terlambat'
                            when upper('$category') = 'PA' then
                              'Pulang Awal'
                            when upper('$category') in ('S','SD') then
                              'Sakit'
                            when upper('$category') = 'IJIN' then
                              'Ijin'
                            end cat,  
                            case
                            when '$category' = 'T' then
                              to_char(ioc.checkin,'HH24:MI') || ' (' || ioc.terlambat || ' menit)' || decode(ioc.keterangan_ijin,null,null,' - '||ioc.keterangan_ijin)   
                            when '$category' = 'PA' then
                              to_char(ioc.checkout,'HH24:MI') || ' (' || ioc.pulang_cepat || ' menit)' || decode(ioc.keterangan_ijin,null,null,' - '||ioc.keterangan_ijin)  
                            else
                              ioc.keterangan_ijin
                            end note 
                    from    absen.in_out_checkclock@to_midori ioc
                    where   ioc.vc_emp_code = '$empCode'
                            and to_char(ioc.tanggal,'YYYYMMDD') between '$startDate' and '$endDate'
                            and case 
                                when upper('$category') = 'T' and ioc.terlambat > 0 then
                                  'Y'
                                when upper('$category') = 'PA' and ioc.pulang_cepat > 0 then
                                  'Y'             
                                when upper('$category') = replace(upper(ioc.ket_sementara),'SD','S') then
                                  'Y'
                                end = 'Y'      
                    order by ioc.tanggal            
                "));

        return response()->json([
                'header' => $header,
                'lines'  => $lines,
            ]);
    }
}
