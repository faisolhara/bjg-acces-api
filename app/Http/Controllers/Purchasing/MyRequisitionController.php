<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Service\ArdmoreService;

class MyRequisitionController extends Controller{

    public function getRequisition(Request $request){
        $user_id    = $request->get('user_id', 'null');

        $data = \DB::select(\DB::raw(
                    "select  *
                    from    bjg_my_requistiions_v
                    where   user_id = $user_id

                "));

        return response()->json($data);
    }

    public function getRequisitionDetail(Request $request){
        $requisitionHeaderId    = $request->get('requisitionHeaderId', 'null');

        $dataHeader = \DB::select(\DB::raw(
                    "select  prha.requisition_header_id, haou.name as org_name,  
                            prha.segment1 as pr_no, prha.description, fu.user_name as created_by 
                    from    po_requisition_headers_all prha
                            join hr_all_organization_units haou
                              on prha.org_id = haou.organization_id
                            join fnd_user fu
                              on prha.created_by = fu.user_id
                    where   prha.requisition_header_id = $requisitionHeaderId
                "));

        $dataItem = \DB::select(\DB::raw(
                    "select  prla.requisition_line_id, prla.line_num, prla.item_description, prla.unit_meas_lookup_code, prla.quantity, prla.need_by_date,
                            prla.note_to_agent
                    from    po_requisition_lines_all prla
                            left join mtl_system_items_b msib
                              on prla.item_id = msib.inventory_item_id
                                 and prla.destination_organization_id = msib.organization_id
                    where   prla.requisition_header_id = $requisitionHeaderId
                            and nvl(prla.cancel_flag,'N') = 'N'
                    order by prla.line_num        
                "));

        return response()->json([
            'dataHeader'    => $dataHeader,
            'dataItem'      => $dataItem,
            ]);
    }
}
