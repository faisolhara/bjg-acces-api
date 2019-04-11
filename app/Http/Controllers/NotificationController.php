<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Service\ArdmoreService;


class NotificationController extends Controller{

const ATASAN_LANGSUNG   = 'Atasan Langsung';
const HRD_1             = 'HRD - 1';
const HRD_2             = 'HRD - 2';
const DIREKSI           = 'Direksi';


    public function getNotification(Request $request){
        $username    = $request->get('username', 'null');

        $data = \DB::select(\DB::raw(
                    "select     notify_type, document_id, document_date, subject1, subject2, vc_emp_name, ch_sex, key_id
                  from         bjg_notifications_v
                  where        app_user_name = '$username'
                  order by     document_date desc
                "));

        return response()->json($data);
    }

    public function getNotificationAbsence(Request $request){
        $documentId    = $request->get('documentId', 'null');

        $data = \DB::select(\DB::raw(
                    "select  no_document, dt_document, approval_type, vc_emp_name || ' (' || vc_initial_name || ')' as vc_emp_name,
                        vc_dept_name, dt_join_date, absence_cat, 
                        to_char(dt_start_date,'DD-Mon-YYYY') || ' till ' || to_char(dt_end_date,'DD-Mon-YYYY') as period_name,
                        decode(start_leave,null,null,start_leave || ' - ' || finish_leave) as time_name,
                        remarks  
                    from    bjg_approval_absence_v
                    where   no_document = '$documentId'
                "));

        return response()->json($data);
    }



    public function saveApproveAbsence(Request $request){
        $noDocument   = $request->get('noDocument', 'null');
        $note         = $request->get('note', 'null');
        $approveType  = $request->get('approveType', 'null');
        $buttonType   = strtoupper($request->get('buttonType', 'null'));
        $empCode      = $request->get('empCode', 'null');

        try {

            $connAPPS = ArdmoreService::connect();

            $sql = 'BEGIN APPS.bjg_approval_absence_proc( :noDocument, :buttonType, :note, :approveType, :empCode, :output); END;';
            $stmt = oci_parse($connAPPS,$sql);

            //  Bind the input parameter
            oci_bind_by_name($stmt,':noDocument',$noDocument,500);
            oci_bind_by_name($stmt,':buttonType',$buttonType,500);
            oci_bind_by_name($stmt,':note',$note,500);
            oci_bind_by_name($stmt,':approveType',$approveType,500);
            oci_bind_by_name($stmt,':empCode',$empCode,500);

            // Bind the output parameter
            oci_bind_by_name($stmt,':output',$output);

            oci_execute($stmt);

            \DB::commit();    
            return response()->json($output);
        } catch (Exception $e) {
            \DB::rollBack();    
            return response()->json('E');
        }
    }

    public function getNotificationRequisition(Request $request){
        $documentId    = $request->get('documentId', 'null');

        $dataHeader = \DB::select(\DB::raw(
                    "select  prha.requisition_header_id, haou.name as org_name, prha.creation_date, 
                     prha.segment1 as pr_no, prha.description, fu.user_name as created_by
                    from    po_requisition_headers_all prha
                            join hr_all_organization_units haou
                              on prha.org_id = haou.organization_id
                            join fnd_user fu
                              on prha.created_by = fu.user_id
                    where   wf_item_key = '$documentId'
                "));

        $requisitionHeaderId = $dataHeader[0]->requisition_header_id;

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

        $dataHistory = \DB::select(\DB::raw(
                    "select  pah.sequence_num+1 as sequence_num, papf.full_name, pah.action_code, pah.action_date, pah.note 
                    from    po_action_history pah
                            join per_all_people_f papf
                              on pah.employee_id = papf.person_id
                                 and to_char(sysdate,'YYYYMMDD') between to_char(papf.effective_start_date,'YYYYMMDD') and to_char(papf.effective_end_date,'YYYYMMDD') 
                    where   pah.object_type_code = 'REQUISITION'
                            and pah.object_id = $requisitionHeaderId 
                    order by pah.sequence_num
                "));

        return response()->json([
            'dataHeader'    => $dataHeader,
            'dataItem'      => $dataItem,
            'dataHistory'   => $dataHistory,
            ]);
    }

    public function saveApproveRequisition(Request $request){
        
        $keyId       = $request->get('keyId', 'null');
        $approveType  = strtoupper($request->get('approveType', ''));
        $note         = $request->get('note', 'Approved');

        \DB::beginTransaction();

        try {

            $connAPPS = ArdmoreService::connect();

            $sql = 'BEGIN APPS.bjg_approval_requisition_proc( :id, :status, :comment, :output); END;';
            $stmt = oci_parse($connAPPS,$sql);

            //  Bind the input parameter
            oci_bind_by_name($stmt,':id',$id,32);
            oci_bind_by_name($stmt,':status',$status,500);
            oci_bind_by_name($stmt,':comment',$comment,500);

            // Bind the output parameter
            oci_bind_by_name($stmt,':output',$output);

            $id         = $keyId;
            $status     = $approveType;
            $comment    = $note;

            oci_execute($stmt);

            \DB::commit();    
            return response()->json($output);
        } catch (Exception $e) {
            \DB::rollBack();    
            return response()->json('E');
        }

    }

    public function getNotificationSpkl(Request $request){
        $documentId    = $request->get('documentId', 'null');

        $dataHeader = \DB::select(\DB::raw(
                    "select  *
                    from    bjg_approval_spkl_v
                    where   no_spkl = '$documentId'
                "));

        $approveType = $dataHeader[0]->approval_type;

        $dataLines = \DB::select(\DB::raw(
                    "select  eav.vc_emp_code, eav.vc_emp_name, sd.vc_pekerjaan as description, 
                            sd.waktu_awal as plan_start, sd.waktu_akhir as plan_end,
                            sd.real_awal as actual_start, sd.real_akhir as actual_end,
                            sd.hasil_lembur as result_note
                    from    absen.spkl_detail@to_midori sd
                            join absen.employee_all_v@to_midori eav
                              on sd.vc_emp_code = eav.vc_emp_code
                    where   sd.no_spkl = '$documentId'  
                            and case
                                when upper('$approveType') in (upper('Atasan - Pengajuan'),upper('HRD - 1'),upper('DIREKSI - 1')) 
                                     and sd.aktif = 'Y' then
                                  'Y'
                                when upper('$approveType') in (upper('Atasan - Hasil lembur'),upper('HRD - 2'),upper('DIREKSI - 2')) 
                                     and sd.aktif = 'Y' and sd.real_awal is not null then
                                  'Y'
                                end = 'Y'
                    order by upper(eav.vc_emp_name)  
                "));

        return response()->json([
            'dataHeader'    => $dataHeader,
            'dataLines'     => $dataLines,
            ]);
    }

    public function saveApproveSpkl(Request $request){
        $noDocument   = $request->get('noDocument', 'null');
        $note         = $request->get('note', 'null');
        $approveType  = $request->get('approveType', 'null');
        $buttonType   = strtoupper($request->get('buttonType', 'null'));
        $empCode      = $request->get('empCode', 'null');

        try {

            $connAPPS = ArdmoreService::connect();

            $sql = 'BEGIN APPS.bjg_approval_spkl_proc( :noDocument, :buttonType, :note, :approveType, :empCode, :output); END;';
            $stmt = oci_parse($connAPPS,$sql);

            //  Bind the input parameter
            oci_bind_by_name($stmt,':noDocument',$noDocument,500);
            oci_bind_by_name($stmt,':buttonType',$buttonType,500);
            oci_bind_by_name($stmt,':note',$note,500);
            oci_bind_by_name($stmt,':approveType',$approveType,500);
            oci_bind_by_name($stmt,':empCode',$empCode,500);

            // Bind the output parameter
            oci_bind_by_name($stmt,':output',$output);

            oci_execute($stmt);

            \DB::commit();    
            return response()->json($output);
        } catch (Exception $e) {
            \DB::rollBack();    
            return response()->json('E');
        }
    }

    public function getNotificationQuote(Request $request){
        $documentId    = $request->get('documentId', 'null');

        $dataHeader = \DB::select(\DB::raw(
                    "select  hp.party_name as customer_name, ooha.attribute1 as project_code_so, 
                            ooha.quote_date, ooha.quote_number, ottt.name as order_type, papf.full_name as sales_name,  
                            ooha.transactional_curr_code 
                    from    oe_order_headers_all ooha
                            join oe_transaction_types_tl ottt
                              on ooha.order_type_id = ottt.transaction_type_id
                            join oe_transaction_types_all otta
                              on ooha.order_type_id = otta.transaction_type_id
                            left join jtf_rs_salesreps jrs
                              on ooha.salesrep_id = jrs.salesrep_id
                            left join per_all_people_f papf
                              on jrs.person_id = papf.person_id
                                 and to_char(sysdate,'YYYYMMDD') between to_char(papf.effective_start_date,'YYYYMMDD') and to_char(papf.effective_end_date,'YYYYMMDD')
                            join apps.hz_cust_accounts hca
                              on hca.cust_account_id = ooha.sold_to_org_id
                            join hz_parties hp on hp.party_id = hca.party_id
                    where   ooha.header_id = $documentId
                "));

        $dataLines = \DB::select(\DB::raw(
                    "select  oola.line_number, oola.ordered_item, mcb.segment1 as item_category,
                            msib.description, 
                            case 
                            when upper(otta.order_category_code) = 'RETURN' then
                              oola.ordered_quantity * -1
                            else
                              oola.ordered_quantity
                            end ordered_quantity, 
                            oola.order_quantity_uom,
                            oola.unit_selling_price, 
                            case 
                            when upper(otta.order_category_code) = 'RETURN' then
                              -1 * oola.ordered_quantity * oola.unit_selling_price
                            else
                              oola.ordered_quantity * oola.unit_selling_price
                            end subtotal,
                            case 
                            when upper(otta.order_category_code) = 'RETURN' then
                                case
                                when oola.attribute1 > 1 then
                                  -1 * oola.ordered_quantity * to_number(oola.attribute1)
                                when oola.order_quantity_uom = 'KG' then
                                  -1 * oola.ordered_quantity
                                else
                                  -1 * oola.ordered_quantity * 1/mucc.conversion_rate
                                end
                            else
                                case
                                when oola.attribute1 > 1 then
                                  oola.ordered_quantity * to_number(oola.attribute1)
                                when oola.order_quantity_uom = 'KG' then
                                  oola.ordered_quantity
                                else
                                  oola.ordered_quantity * 1/mucc.conversion_rate
                                end
                            end total_weight
                    from    oe_order_headers_all ooha
                            join oe_transaction_types_tl ottt
                              on ooha.order_type_id = ottt.transaction_type_id
                            join oe_transaction_types_all otta
                              on ooha.order_type_id = otta.transaction_type_id
                            left join jtf_rs_salesreps jrs
                              on ooha.salesrep_id = jrs.salesrep_id
                            left join per_all_people_f papf
                              on jrs.person_id = papf.person_id
                                 and to_char(sysdate,'YYYYMMDD') between to_char(papf.effective_start_date,'YYYYMMDD') and to_char(papf.effective_end_date,'YYYYMMDD')
                            join oe_order_lines_all oola
                              on ooha.header_id = oola.header_id and ooha.org_id = oola.org_id
                            join apps.hz_cust_accounts hca
                              on hca.cust_account_id = ooha.sold_to_org_id
                            join hz_parties hp on hp.party_id = hca.party_id
                            join mtl_system_items_b msib
                              on msib.inventory_item_id = oola.inventory_item_id
                                 and msib.organization_id = oola.ship_from_org_id
                            left join mtl_uom_class_conversions mucc 
                              on msib.inventory_item_id = mucc.inventory_item_id
                                 and mucc.to_uom_code in ('KG','Kilogram')
                                 and mucc.disable_date is null
                            left join mtl_item_categories mic
                              on msib.organization_id = mic.organization_id
                                 and msib.inventory_item_id = mic.inventory_item_id
                                 and mic.category_set_id = 1100000041 -- bjg category set
                            left join mtl_categories_b mcb
                              on mic.category_id = mcb.category_id
                    where   ooha.header_id = $documentId
                            and oola.ordered_quantity > 0
                    order by oola.line_number
                "));

        return response()->json([
            'dataHeader'    => $dataHeader,
            'dataLines'     => $dataLines,
            ]);
    }


}
