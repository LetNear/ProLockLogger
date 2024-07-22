<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Home extends Controller
{
    //
    function index()
    {
        return view('home');
    }
    public function comlabtimeintimeout()
    {

        $date =  \Carbon\Carbon::now();
        $gettop10 = DB::table('computer_lab_attendances')
        ->select(
            'computer_lab_attendances.id as comlabid',
            'computer_lab_attendances.student_id',
            'computer_lab_attendances.seat_number',
            'computer_lab_attendances.time_in',
            'computer_lab_attendances.time_out',
            'computer_lab_attendances.logdate',
            'students.lrn',
            DB::raw('CONCAT(students.last_name, ", ", students.first_name, " ", IFNULL(students.middle_name, "")) AS name'),
            'students.photo'
        )
        ->leftjoin('students', 'students.id', '=', 'computer_lab_attendances.student_id')
        ->whereDate('logdate' , "like", "%{$date->today()->toDateString()}%")
        ->orderBy('comlabid','DESC')
        ->limit(10)
        ->paginate(3);

          //dd($gettop10);
        return view('comlabtimeintimeout',compact('gettop10'));
    }

}