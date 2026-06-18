<?php

namespace App\Modules\Admin\Outlays\Services;

use App\Modules\Admin\Outlays\Models\Outlay;
use App\Modules\Admin\Outlays\Requests\OutlayRequest;
use App\Services\Response\ResponseService;
use Illuminate\Database\Eloquent\Collection;

class OutlayService
{
    public function getOutlays($request): Collection
    {

        $outlayList = Outlay::where('execution_date', '>=', (new \DateTime($request['dateStart']))->format('Y-m-d') . ' 00:00:00')
            ->where('execution_date', '<=', (new \DateTime($request['dateEnd']))->format('Y-m-d') . ' 23:59:59')
            ->orderBy('execution_date', 'ASC') ->get();

//        if ($request['subdivision_id']){
//            $outlayList->where('subdivision_id', $request['subdivision_id']);
//        }
//        $outlayList = $outlayList->get();

//        dd($outlayList);
//        foreach ($outlayList as $outlay){
//            $date = new \DateTime($outlay->execution_date);
//            $outlay->execution_date = $date->format('Y-m-d H:i:s');
//        }

        return $outlayList;
    }

    public function save(OutlayRequest $request, Outlay $outlay) : Outlay
    {

        //timestamp format transfiguration
//        $request['execution_date'] = $this->getTimestamp($request['execution_date'], $request['hours'], $request['minutes']);

        $execDate = new \DateTime($request['execution_date']);

//        dd($execDate);die;
        $request['execution_date'] = $execDate->format('Y-m-d H:i:s');

        $outlay->fill($request->only($outlay->getFillable()));
        $outlay->save();
        return $outlay;
    }
//
//    private function getTimestamp($date, $hours = 0, $minutes = 0) : int
//    {
//        return strtotime($date) + ($hours * 60 * 60) + ($minutes * 60);
//
//    }
}
