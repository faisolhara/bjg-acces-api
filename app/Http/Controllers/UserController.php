<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Service\ArdmoreService;

class UserController extends Controller{

    public function getUser(Request $request){
     
        $data = \DB::connection('chardonnay')
                ->table('iapsys.meeting')
                ->orderBy('meeting.meeting_date', 'DESC')
                ->distinct()
                ->get();

        return response()->json($data);

    }

    public function getItem(){

        $data = \DB::table('inv.mtl_system_items_b')
                ->select(
                    'mtl_system_items_b.segment1',
                    'mtl_system_items_b.segment2',
                    'mtl_system_items_b.segment3',
                    'mtl_system_items_b.INVENTORY_ITEM_ID',
                    'mtl_system_items_b.description'
                    )
                ->where('segment1', '=', '2016')
                ->where('organization_id', '=', '124')
                ->where('purchasing_item_flag', '=', 'Y')
                ->where('INVENTORY_ITEM_STATUS_CODE', '=', 'Active')
                ->distinct()
                ->take(10)
                ->get();

        return response()->json($data);
    }

    public function executeProcedure($procedureName, $bindings, $returnType = PDO::PARAM_STMT)
    {
        $command = sprintf('begin %s(:%s, :cursor); end;', $procedureName, implode(', :', array_keys($bindings)));
        $stmt = $this->getPdo()->prepare($command);
        foreach ($bindings as $bindingName => &$bindingValue) {
            $stmt->bindParam(':' . $bindingName, $bindingValue);
        }
        $cursor = null;
        $stmt->bindParam(':cursor', $cursor, $returnType);
        $stmt->execute();
        if ($returnType === PDO::PARAM_STMT) {
            $statement = new Statement($cursor, $this->getPdo(), $this->getPdo()->getOptions());
            $statement->execute();
            $results = $statement->fetchAll(PDO::FETCH_ASSOC);
            $statement->closeCursor();
            return $results;
        }
        return $cursor;
    }

    public function getJoin(){
       // $data = \DB::select(\DB::raw('select user_id, user_name, description
       //                                 from apps.fnd_user@to_ardmore'));

         // $data = \DB::select(\DB::raw('select absen.get_test(15,15) from dual'));
        // $bindings = [
        //     'p_a'   => 15,
        //     'p_b'   => 15
        // ];  

//        $data = \DB::executeProcedure('test2',$bindings);

 $pdo = \DB::getPdo();
$p1 = 15;
$p2 = 15;

        $stmt = $pdo->prepare("begin test2(17,17); end;");
        // $stmt->bindParam(':p1', $p1, PDO::PARAM_INT);
        // $stmt->bindParam(':p2', $p2, PDO::PARAM_INT);
        $stmt->execute();

        $data = \DB::insert('insert into absen.a values (12,12)');
        
        $data = \DB::table('absen.a')->insert(
            ['a' => 13, 'b' => 13]
        );

        $return = [
                    'data' => $data,
                    'msg'  => $data ? 'success' : 'failed',
        ];

        return response()->json($return);
    }

    public function getItemrma(Request $request){

        $data = \DB::table('inv.mtl_system_items_b')
                ->select(
                    'mtl_system_items_b.segment1',
                    'mtl_system_items_b.segment2',
                    'mtl_system_items_b.segment3',
                    'mtl_system_items_b.INVENTORY_ITEM_ID',
                    'mtl_system_items_b.description'
                    )
                ->where('segment1', '=', $request->get('p_segment1'))
                ->where('organization_id', '=', '124')
                ->where('purchasing_item_flag', '=', 'Y')
                ->where('INVENTORY_ITEM_STATUS_CODE', '=', 'Active')
                ->distinct()
                ->take(10)
                ->get();

        return response()->json($data);
    }

    public function getOnhandIndex(Request $request){
        $p_organization_id = $request->get('p_organization_id', 'null');
        $p_project_code    = $request->get('p_project_code', 'null');

        // $data = \DB::select(\DB::raw("select * from bjg_onhand_v where organization_id = $p_organization_id and nvl(upper(project_code),'$$') like upper(nvl('$p_project_code',nvl(upper(project_code),'$$'))) order by item_code"));

        $data = \DB::select(\DB::raw("select  organization_id, org_name, inventory_item_id, item_code, description, sum(onhand) as onhand from bjg_onhand_v where organization_id = nvl($p_organization_id,organization_id) and nvl(upper(project_code),'$$') like upper(nvl('$p_project_code',nvl(upper(project_code),'$$'))) group by organization_id, org_name, inventory_item_id, item_code, description order by item_code"));

        return response()->json($data);
    }

    public function getOnhandDetail(Request $request){
        $p_organization_id      = $request->get('p_organization_id', 'null');
        $p_inventory_item_id    = $request->get('p_inventory_item_id', 'null');

        $data = \DB::select(\DB::raw("select org_name, item_code, description, item_category, subinventory_code, locator_name, project_code, project_name, lot_number, onhand from bjg_onhand_v where   organization_id = $p_organization_id and inventory_item_id = $p_inventory_item_id order by onhand desc"));
        return response()->json($data);
    }

}
