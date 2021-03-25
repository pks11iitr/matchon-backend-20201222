<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Earning;
use Illuminate\Http\Request;

class EarningController extends Controller
{
    public function index(Request $request){
        $earnings = Earning::where('id','>=',0);

        if(isset($request->fromdate))
            $earnings = $earnings->where('date', '>=', $request->fromdate.' 00:00:00');

        if(isset($request->todate))
            $earnings = $earnings->where('date', '<=', $request->todate.' 23:59:59');

        if($request->user_id)
            $earnings=$earnings->where('user_id', $request->user_id);

        $earnings =$earnings->orderBy('id', 'desc')->paginate(20);

        $customers = Customer::where('account_type','ADMIN')->get();

        return view('admin.earning.view',['earnings'=>$earnings,'customers'=>$customers]);

    }
}
