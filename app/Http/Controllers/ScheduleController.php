<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use lluminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Appointment;
use App\Models\Doctor;
use Illuminate\Support\Facades\Route;

use Calendar;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $apps = Appointment::all();
        $app_list = [];
        
        foreach ($apps as $keys => $app) {
            if ($app->status == 0) {
                $color = '#ffff66';
            } elseif ($app->status == 1) {
                $color = '#5cd65c';
            } else {
                $color = '#ff4d4d';
            }
            if (Auth::check() and Auth::user()->role === 'doctor') {
                if ($app->doctor->user->client->id == Auth::user()->client->id) {
                    $app_list[] = Calendar::event(
                        $app->client->firstname,
                        false,
                        new \DateTime($app->start_date),
                        new \DateTime($app->end_date),
                        1,
                        [
                            'textColor' => '#000000',
                            'color' => $color,
                            'url' => '/appointment/'.$app->id
                        ]
                    );
                }
            } elseif (Auth::check() and Auth::user()->role === 'client') {
                if ($app->status == 1 or $app->client->id == Auth::user()->client->id) {
                    $url = '';
                    if ($app->client->id == Auth::user()->client->id) {
                        $url = '/appointment/'.$app->id;
                    }
                    if ($app->status == 1 and $app->client->id == Auth::user()->client->id) {
                        $color = '#29a329';
                    }
                    $app_list[] = Calendar::event(
                        $app->client->firstname,
                        false,
                        new \DateTime($app->start_date),
                        new \DateTime($app->end_date),
                        1,
                        [
                            'textColor' => '#000000',
                            'color' => $color,
                            'url' => $url
                        ]
                    );
                }
            } else {
                if ($app->status == 1) {
                    $app_list[] = Calendar::event(
                        $app->client->firstname,
                        false,
                        new \DateTime($app->start_date),
                        new \DateTime($app->end_date),
                        1,
                        [
                            'textColor' => '#000000',
                            'color' => $color,
                        ]
                    );
                }
            }
        }
        $calendar_details = Calendar::addEvents($app_list);

        $doctors = Doctor::get();

        return view('/schedule', compact('calendar_details', 'doctors'));
    }

    public function addApp(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'doctor' => 'required'
        ]);

        if ($validator->fails()) {
            \Session::flash('warning', 'โปรดใส่ข้อมูลให้ถูกต้อง');
            return redirect('/schedule');
        }

        $app = new Appointment();
        $app->name = $request['name'];
        $app->description = $request['description'];
        $app->start_date = $request['start_date'];
        $app->end_date = $request['end_date'];
        $app->status = 0;
        $app->doctor_id = $request['doctor'];
        $app->client_id = Auth::user()->client->id;
        $app->save();

        \Session::flash('success', 'ยื่นคำร้องขอจองเวลาตรวจเรียบร้อย โปรดรอผลการจอง');
        return redirect('/schedule');
    }
}
