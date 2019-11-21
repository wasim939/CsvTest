<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * Read csv file data
     *
     * @return void
     */
    public function ReadCsvFile($file)
    {
        $file = fopen($file,"r");

        $i = 0;
        $head_array = array();
        $data_array = array();
        while(! feof($file))
        {
            if($i == 0){
                $head_array = fgetcsv($file);
            }else{
                $temp = array();
                $data = fgetcsv($file);
                if($data !=''){
                    for($k = 0; $k <count($head_array); $k++){
                        $temp[$head_array[$k]] = $data[$k];
                    }
                    $data_array[] = $temp;
                }
            }
            $i++;
        }
        return $data_array;
    }

    /**
     * cache csv file data and retrieve
     *
     * @return void
     */
    public function cacheCsvData()
    {
        $file      =   PUBLIC_PATH('/uploads/transactions.csv');

        /*read csv data*/
        $allData = $this->ReadCsvFile($file);

        /*store data in cache*/
        Cache::rememberForever('csvData', function () use($allData) {
            return $allData;
        });
        $csvData = Cache::get('csvData');

        return response()->json(['status' => true, 'message' => 'csv data retrieved from cache successfully.', 'data' => $csvData], 200) ;
    }
}
